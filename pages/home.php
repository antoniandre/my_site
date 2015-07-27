<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
// Disable breadcrumbs on home page only.
Page::getInstance()->setBreadcrumbsVisiblity(false);

$tpl = new Template('.');
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_block("$page->page-page", 'articleBlock', 'theArticleBlock');

// Retrieve published articles.
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
  ->where()->col('published')->eq(1);
  dbg($q);
$articles = $q->run()
              ->loadObjects();

foreach ($articles as $k => $article)
{
	dbg($article);
	$tpl->set_var(['articleLink' => url($article->page),
				   'articleTitle' => $article->title,
				   'publishedByOn' => textf('By %s on %s', $article->author, $article->created)]);
	$tpl->parse('theArticleBlock', 'articleBlock', true);
}

$tpl->set_var(['latestArticlesTitle' => text('Latest Articles')]);

$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>