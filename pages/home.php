<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
Page::getInstance()->setBreadcrumbsVisiblity(false);// Disable breadcrumbs on home page only.
$tpl = new Template('.');
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>