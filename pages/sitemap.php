<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$page->setBreadcrumbsVisiblity(false);// Disable breadcrumbs on sitemap page.
$content = getTree('sitemap', ['[article]']);

$tpl = new Template();
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_var(['content'          => $content,
               'articlesList'     => $page->renderArticlesList(),
               'articlesListText' => text('Tous les articles')]);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>