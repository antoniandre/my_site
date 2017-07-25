<?php
//======================= VARS ========================//
//=====================================================//


//======================= INCLUDES ====================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$homePage    = Page::get('home');
$sitemapPage = Page::get('sitemap');
$tpl         = newPageTpl();

$tpl->set_var(['noPageFound'  => text('No page was found here.<br>Would you like to go to check those pages?'),
               'homeUrl'      => url($homePage->page),
               'homeLabel'    => $homePage->getTitle(),
               'sitemapUrl'   => url($sitemapPage->page),
               'sitemapLabel' => $sitemapPage->getTitle()]);

header("HTTP/1.0 404 Not Found");
$page->setContent($tpl->parse('display', $page->page))->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>