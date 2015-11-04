<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
include 'backstage/functions/core.php';
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
if ($page->isArticle())
{
	$article = getPageByProperty('id', 'article', $language);
	$includePath = __DIR__."/$article->path$article->page.php";
}
else
{
	if (!is_file(__DIR__."/$page->path$page->page.php")) $page = getPageByProperty('id', 'notFound', $language);
	$includePath = __DIR__."/$page->path$page->page.php";
}

include($includePath);

echo Page::getInstance()->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>