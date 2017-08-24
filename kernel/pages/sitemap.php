<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$settings = Settings::get();
$page->setBreadcrumbsVisiblity(false);// Disable breadcrumbs on sitemap page.
$content = getTree('sitemap', ['[article]'], ['showIcons' => $settings->sitemapMenuIcons]);

$tpl = newPageTpl();
$tpl->set_var(['content'          => $content,
               'articlesList'     => $page->renderArticlesList(),
               'articlesListText' => text('Tous les articles')]);
$page->setContent($tpl->parse('display', $page->page))->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>