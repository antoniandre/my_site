<?php
//======================= VARS ========================//
define('USE_LAZY_FROM', 5);// If lazyload is activated in config.ini, use it from this image.
$facebookImage = null;
$settings = Settings::get();
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$db = database::getInstance();
$q = $db->query();
$q->select('articles',
		   [$q->colIn('id', 'articles'),
		    $q->col("content_$language"),
		    $q->colIn('created', 'articles'),
		    $q->concat($q->colIn('firstName', 'users'), ' ', $q->colIn('lastName', 'users'))->as('author'),
		    $q->col('page'),
		    $q->col('image'),
		    $q->col('published'),
		    $q->colIn("url_$language", 'pages')])
  ->relate('articles.author', 'users.id')
  ->relate('pages.article', 'articles.id')
  ->relate('articles.category', 'article_categories.id')
  ->orderBy('articles.created', 'desc');
$w = $q->where();
$w->colIn('name', 'article_categories')->eq('travel');
$q->orderBy('created', 'desc');
$articles = $q->run()
            ->loadObjects('id');
$tpl = new Template();
$tpl->set_file("$page->page-page", "backstage/templates/article.html");

// Get the current article.
$article = $articles[$page->article->id];
if ($article && $article->published)
{
	handleAjax(function()
	{
		$gets = Userdata::get();
		if (isset($gets->getLikes)) return getAllLikes();
		elseif (isset($gets->like)) return likeItem($gets->like);
	});

	$created = new DateTime($article->created);
	$content = $article->{"content_$language"};
	// Set correct src paths for img tags and replace 'src' with 'data-original'
	// from image USE_LAZY_FROM if JS lazyload is active.
	$cnt = 0;
	$articleContent = preg_replace_callback('~<img(.+?)src="(?:(images\/\?(?:i|u)=[^"]+)|(https?:\/\/[^"]+))(?=")~i', function($matches)
	{
		global $cnt;
		$settings = Settings::get();
		$src = $matches[2] ? $settings->root.$matches[2] : $matches[3];
		return '<img'.$matches[1].($settings->useLazyLoad && $cnt++ > USE_LAZY_FROM ? 'data-original="' : 'src="').$src;
	}, $content);

	$tpl->set_var(['articleId' => $page->article->id,
				   'content' => $articleContent,
				   'created' => text(21,
				   					[
				   					    'contexts' => 'article',
				   						'formats' =>
				   						[
				   							'sprintf' =>
				   							[
		   										$article->author,
											  	$created->format($language == 'fr' ? 'd/m/Y' : 'Y-m-d'),
											 	$created->format($language == 'fr' ? 'H\hi' : 'H:i')
											]
										]
									])
				   ]);

	// Now get the image representing the article for Facebook.
	$image = preg_replace('~(u(?=ploads)|i(?=mages))(?:mages|ploads)%2F~', $settings->root.'images/?$1=', urlencode($article->image));
	$page->addSocial($image);
}
else
{
	if (!$article->published) new Message(text(37), 'info', 'info', 'content');
	$tpl->set_var(['articleId' => $page->article->id,
				   'content' => 'No content.',
				   'created' => '']);
}

$articlesVal = array_values($articles);
$i = 0;
foreach ($articles as $k => $article)
{
	if ($k == $page->article->id)
	{
		$next = isset($articlesVal[$i-1]) ? $articlesVal[$i-1] : null;
		$prev = isset($articlesVal[$i+1]) ? $articlesVal[$i+1] : null;
		break;
	}
	$i++;
}

$tpl->set_var(['prevArticleLink' => $prev ? '<a href="'.url($prev->page).'">Article précédent</a>' : '',
			   'nextArticleLink' => $next ? ($prev ? ' | ' : '').'<a href="'.url($next->page).'">Article suivant</a>' : '',
			  ]);


$content =  $tpl->parse('display', "$page->page-page");

// Add comment system.
$content .= Utility::generateCommentSystem('comments.created', 'DESC');
//============================================ end of MAIN =============================================//
//======================================================================================================//

/**
 * get all the picture likes for the current article,
 * and return an array to be converted in json for later use in JS.
 *
 * @return array: the array of likes to be converted into a json string.
 */
function getAllLikes()
{
	global $article;
	$user = User::getInstance();
	$ip = $user->getIp();
	$items = [];// Pictures or comments.

	$q = Database::getInstance()->query();
	$q->select('likes', [$q->col('item'), $q->col('ip')])
	  ->where()->col('article')->eq($article->id)
	  ->and()->col('likes')->gt(0);

	$itemsLikes = $q->run()->loadObjects();

	if (count($itemsLikes)) foreach ($itemsLikes as $item)
	{
		$ipList = (array)unserialize($item->ip);
		if ($likes = count($ipList))
		{
			// Only keep rated items in json output for lighter weight.
			$items[$item->item] = ['likes' => $likes, 'liked' => array_search($ip, $ipList) !== false];
		}
	}

	return $items;
}

/**
 * Like an item : a picture or a comment.
 *
 * @param String/int $item: picture src or comment id.
 * @return json: the list of likes per item. {"item1":{"likes":(int),"liked":true/false}, ...}
 */
function likeItem($item)
{
	global $article;
	$likes = 0;
	$ip = User::getInstance()->getIp();

	$item = urldecode($item);

	$q = database::getInstance()->query();
	$q->select('likes', [$q->col('ip')]);
	$w = $q->where();
	$w->col('item')->eq($item)->and($w->col('article')->eq($article->id));
	$dbRow = $q->run()->loadObject();

	// Update.
	if ($dbRow)
	{
		$ipList = (array)unserialize($dbRow->ip);
		$userIpPos = array_search($ip, $ipList);
		// Ip found - User has already liked: remove his/her ip address.
		if ($userIpPos !== false) array_splice($ipList, $userIpPos, 1);

		//  Ip not found - User has not yet liked: add his/her ip address.
		else
		{
			$ipList[] = $ip;
			$ipList = array_unique($ipList);// Make sure there is no duplicates.
		}

		$q->update('likes',
				   ['likes' => $likes = count($ipList), 'ip' => serialize($ipList), 'article' => (int)$article->id]);
		$w = $q->where();
		$w->col('item')->eq($item);
		$q->run();		
	}
	else
	{
		$q->insert('likes',
				   ['item' => $item, 'likes' => $likes = 1, 'ip' => serialize([$ip]), 'article' => (int)$article->id])
		  ->run();
	}

	return $likes;
}

?>