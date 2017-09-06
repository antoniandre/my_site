<?php
//======================= VARS ========================//
define('USE_LAZY_FROM', -1);// If lazyload is activated in config.ini, use it from this image.
$settings = Settings::get();

// Initiate vars to default.
$facebookImage = null;
$articleContent = text(76);
$articleCreated = '';
$articlePrevLink = '';
$articleNextLink = '';
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$tpl = newPageTpl('article');

// Get the current article.
$article     = Article::get($page->article->id);
$articleTags = '';
if (isset($article->tags))
{
    $articleTags .= '<div class="article-tags clear">';
    foreach ($article->tags as $tagId => $tag)
    {
        $articleTags .= "<span class='article-tag' data-id='$tagId'>{$tag->{"text$language"}}</span>";
    }
    $articleTags .= '</div>';
}

// If article is not published just display a message saying so.
if (!isset($article->status) || $article->status !== 'published') new Message(text(37), 'info', 'info', 'content');

// If article is published.
elseif ($article && $article->status === 'published')
{
    // Like an image or other media (from like button on each figure) will trigger an ajax call.
	handleAjax(function()
	{
		$gets = Userdata::get();
		if (isset($gets->like)) return ['likes' => likeItem($gets->like)];
	});

	$created = new DateTime($article->created);
	$content = $article->content;

	// If the content of the article is empty, suggest the content in another translation.
	if (!$content)
	{
		$translations = Article::getTranslations($page->article->id);

		$content = '';
		if (count($translations))
		{
			$content = '<p>'.text(75).'</p><ul>';
			foreach ($translations as $lang => $translation)
			{
				$content .= "<li>In $translation->languageLabel: <a href=\""
							.url($translation->article->page, ['language' => $lang])
							."\">{$translation->article->title}</a></li>";
			}
			$content .= '</ul>';
		}
		else dbg(text('This article is empty and there is no other translations.'));
	}


	$likes = getAllLikes($page->article->id);

    // For each media file (image, video, audio):
    // - inject right root path in src url
    // - inject the likes count (to be used by JS).
    // - lazy load pictures.
	$cnt = 0;
	$articleContent = preg_replace_callback('~<(img|iframe|audio)(.+?)src="([^"]+)"(.*?)(/?>(?![^<]*?</audio>)|<\1>)~i', function($matches) use ($likes)
	{
		global $cnt;// $cnt is not just used inside function, it has to be updated outside! (so 'use($cnt)' is not enough).
		$dontLazyload = false;
        $settings = Settings::get();

        list(, $tag, $attributes, $src, $anything, $closing) = $matches;

		// Get the likes of the current picture or iframe.
		$src = urldecode($src);
		$dataLikes = isset($likes[$src]['likes']) ? intval($likes[$src]['likes']) : 0;
		$dataLiked = isset($likes[$src]['liked']) ? intval($likes[$src]['liked']) : 0;

		// Set correct src paths for img tags.
		$src = strpos($src, 'images/?') === 0 ? $settings->root.$src : $src;

        if ($tag === 'audio' || $tag === 'iframe') $dontLazyload = true;

		// Replace 'src' with 'data-original' from the image number (int)USE_LAZY_FROM if JS lazyload is active.
		return "<div class=\"imageWrapper\"><$tag$attributes"
               .($settings->useLazyLoad && $cnt++ > USE_LAZY_FROM && !$dontLazyload ? 'data-original="' : 'src="')
               ."$src\" data-likes=\"$dataLikes\" data-liked=\"$dataLiked\"$anything$closing</div>";
	}, $content);



	// Highlight hash tags with a wrapping span.
    $articleContent = convertHashTags($articleContent);

	// Convert '<3' to a heart icon.
	// @todo: add smilies from Icomoon.
    $articleContent = convertSmilies($articleContent);

	// Add "Article created on [date] by [author]" on top right and format the date.
	$articleCreated = text(21,
					  [
	                      'contexts' => 'article',
                          'formats'  =>
                          [
                              'sprintf' =>
                              [
                                  "<span class=\"author\"> $article->author</span>",
                                  '<span class="date i-calendar"> ' . $created->format($language == 'fr' ? 'd/m/Y' : 'Y-m-d'),
                                  $created->format($language == 'fr' ? 'H\hi' : 'H:i') . '</span>'
                              ]
                          ]
					  ]);

	// Now get the image representing the article for Facebook.
	$image = preg_replace('~(u(?=ploads)|i(?=mages))(?:mages|ploads)%2F~', $settings->root.'images/?$1=', urlencode($article->image));
	$page->addSocial($image);

    // Render the previous and next article buttons at the end of the article.
    list($articlePrevLink, $articleNextLink) = Article::getPrevNext($page->article->id, $article->category);
}


// Main display.
$tpl->set_var(['articleId'       => $page->article->id,
               'articleTags'     => $articleTags,
               'content'         => $articleContent,
			   'social'          => '<div class="social clearfix"></div>',
			   'created'         => $articleCreated,
			   'prevArticleLink' => $articlePrevLink,
			   'nextArticleLink' => $articleNextLink ? ($articlePrevLink ? ' | ' : '') . $articleNextLink : '',
			   'comments'        => Utility::generateCommentSystem('comments.created', 'DESC')// Add comment system.
			  ]);

$page->setContent($tpl->parse('display', $page->page))->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//

/**
 * Wrap figure captions hashtags into a span.
 * E.g. #mySuperPicture becomes <span class=\"hashtag\">#mySuperPicture</span>"
 * @param  [type] $articleContent [description]
 * @return [type]                 [description]
 */
function convertHashTags($articleContent)
{
	return preg_replace('~(?:<figcaption|(?!^)\G)[^<#]*(?:(?:<(?!/figcaption\b)|#\B)[^<#]*)*\K#\w+~', "<span class=\"hashtag\">\$0</span>", $articleContent);
}

function convertSmilies($articleContent)
{
	$smiliesTable = [
		'<3'    => 'i-heart',
		'&lt;3' => 'i-heart',
		'(y)' => 'i-thumbup',
		'0:)' => 'i-angel',
		'0:-)' => 'i-angel',
		':)' => 'i-smile',
		':-)' => 'i-smile',
		':-(' => 'i-sad',
		':(' => 'i-sad',
		'-_-' => 'i-sad',
		':\'(' => 'i-crying',
		'T_T' => 'i-crying',
		'*o*' => 'i-shocked',
		':o' => 'i-shocked',
		':-o' => 'i-shocked',
		'o_O' => 'i-baffled',
		'0_o' => 'i-baffled',
		'0_0' => 'i-baffled',
		';)' => 'i-wink',
		':D' => 'i-happy',
		'8)' => 'i-cool',
		'>:)' => 'i-evil',
		'&gt;:)' => 'i-evil',
		':/' => 'i-wondering',
		':s' => 'i-confused',
		':P' => 'i-tongue',
		'XP' => 'i-tongue',
		':|' => 'i-neutral',
		'XD' => 'i-grin',
	];
	/*foreach($smiliesTable as $smiley => &$iconClass)
	{
		$iconClass = "<span class=\"$iconClass\"></span>";
	}
	return str_ireplace(array_keys($smiliesTable), array_values($smiliesTable), $articleContent);*/

	foreach($smiliesTable as $smiley => $iconClass)
	{
		$articleContent = preg_replace('~(\s+|</?\w+[^>]*?/?>)'.preg_quote($smiley, '~').'~is',
									   "$1<span class=\"smiley $iconClass\"></span>",
									   $articleContent);
	}
	return $articleContent;
}

/**
 * get all the picture likes for the current article,
 * and return an array to be converted in json for later use in JS.
 *
 * @return array: the array of likes per item. ["item1" => ["likes" => (int),"liked" => true/false], ...] to be converted
 *                into a json string. The array is indexed by item source (can be image or video or comment id).
 */
function getAllLikes($articleId)
{
	$user = User::getCurrent();
	$ip = $user->getIp();
	$items = [];// Pictures or comments.

	$q = Database::getInstance()->query();
	$q->select('likes', [$q->col('item'), $q->col('ip')])
	  ->where()->col('article')->eq($articleId)
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
 * Like an item : a picture, a video or a comment.
 * The Item has a sum of likes and when liking or unliking, the suum will update and be returned.
 *
 * @param String/int $item: picture src or comment id.
 * @return int: the updated sum of likes for this item.
 */
function likeItem($item)
{
	global $article;
	$likes = 0;
	$ip = User::getCurrent()->getIp();

	$item = urldecode($item);

	$q = database::getInstance()->query();
	$q->select('likes', [$q->col('ip')])
	  ->where()->col('item')->eq($item)->and()->col('article')->eq($article->id);
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