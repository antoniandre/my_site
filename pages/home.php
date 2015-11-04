<?php
//======================= VARS ========================//
$page = Page::getInstance();
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
// Disable breadcrumbs on home page only.
$page = Page::getInstance();
$page->setBreadcrumbsVisiblity(false);

// Disable h1 title on home page only.
$page->setH1(null);

$tpl = new Template('.');
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_block("$page->page-page", 'latestArticleBlock', 'theLatestArticleBlock');
$tpl->set_block('latestArticleBlock', 'articleBlock', 'theArticleBlock');

// Retrieve published articles from DB.
$db = database::getInstance();
$q = $db->query();
$q->select('articles',
		   [$q->col("content_$language"),
		    $q->col("page")->as('page'),
		    $q->col("title_$language")->as('title'),
		    $q->col('created'),
		    $q->concat($q->colIn('firstName', 'users'), ' ', $q->colIn('lastName', 'users'))->as('author')])
  ->relate('articles.author', 'users.id')
  ->relate('articles.id', 'pages.article')
  ->relate('articles.category', 'article_categories.id');
$w = $q->where();
$w->col('published')->eq(1);//->and($w->colIn('name', 'article_categories')->eq('travel'));

$articles = $q->run()
              ->loadObjects();

if (count($articles))
{
	foreach ($articles as $k => $article)
	{
		$created = new DateTime($article->created);
		$tpl->set_var(['articleLink' => url($article->page),
					   'articleTitle' => $article->title,
					   'publishedByOn' => text(21,
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
										])]);
		$tpl->parse('theArticleBlock', 'articleBlock', true);
	}

	$tpl->set_var('latestArticlesTitle', text(44));
	$tpl->parse('theLatestArticleBlock', 'latestArticleBlock', true);
}
else $tpl->set_var('theLatestArticles', '');

$tpl->set_var('ROOT', $settings->root);

$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
/**
 * Render the articles list.
 *
 * @param  Array $articles: An array of objects.
 * @return String: the output html.
 */
function renderArticles($articles)
{
	global $tpl, $latestArticlesUsePictures;
	$settings = Settings::get();
	$language = Language::getCurrent();
	$latestArticlesUsePictures = $settings->latestArticlesUsePictures;

	if (count($articles))
	{
		foreach ($articles as $k => $article)
		{
			$created = new DateTime($article->created);

			$tpl->set_var(['articleLink' => url($article->page),
						   'articleTitle' => $article->title,
						   'publishedByOn' => text(21, [
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
			if ($latestArticlesUsePictures)
			{
				$imgClass = 'img'.$article->id;
				$image = preg_replace('~(u(?=ploads)|i(?=mages))(?:mages|ploads)%2F~', $settings->root.'images/?$1=', urlencode($article->image));
				$tpl->set_var(['articleImgSrc' => $image, 'imgClass' => $imgClass]);
				$tpl->parse('theArticleBlockImages', 'articleBlockImages', true);
			}
			else
			{
				$tpl->set_var('theArticleBlockImages', '');
				$tpl->parse('theArticleBlock', 'articleBlock', true);
			}
		}

		$tpl->set_var(['latestArticlesTitle' => text('Derniers articles'),
					   'theLatestArticlesBlock'.($latestArticlesUsePictures ? '' : 'Images') => '']);
		$tpl->parse('theLatestArticlesBlock'.($latestArticlesUsePictures ? 'Images' : ''), 'latestArticlesBlock'.($latestArticlesUsePictures ? 'Images' : ''), true);
	}
	else $tpl->set_var(['theLatestArticles' => '', 'theLatestArticlesImages' => '']);

	return $tpl->get_var('theArticleBlockImages');
}

/**
 * Get the articles from database.
 *
 * @param Integer $limitFrom: specify a number from which to retrieve articles from DB.
 * @param Integer $quantity: specify a number of articles to retrieve from DB.
 * @return Array: an array of objects.
 */
function getArticles($limitFrom = 0, $quantity = 10)
{
	$language = Language::getCurrent();

	// Retrieve published articles from DB.
	$db = database::getInstance();
	$q = $db->query();
	$q->select('articles',
			   [$q->col("content_$language"),
			    $q->col("page")->as('page'),
			    $q->col("title_$language")->as('title'),
			    $q->col('image'),
			    $q->colIn('id', 'articles'),
			    $q->colIn('created', 'articles'),
			    $q->concat($q->colIn('firstName', 'users'), ' ', $q->colIn('lastName', 'users'))->as('author')])
	  ->relate('articles.author', 'users.id')
	  ->relate('articles.id', 'pages.article')
	  ->relate('articles.category', 'article_categories.id');
	$w = $q->where();
	$w->col('published')->eq(1)->and($w->colIn('name', 'article_categories')->eq('travel'));
	$q->orderBy('created', 'desc')
	  ->limit($limitFrom, $quantity);

	return $q->run()->loadObjects();
}
?>