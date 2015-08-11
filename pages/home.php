<?php
//======================= VARS ========================//
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
?>