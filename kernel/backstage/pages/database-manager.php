<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$tpl = newPageTpl();
$tpl->set_var('content', "The page content goes here for page \"$page->page\".");

foreach (Page::getAllPages() as $id => $thePage)
{
	dbg($thePage);
}

$page->setContent($tpl->parse('display', $page->page))->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>