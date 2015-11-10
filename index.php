<?php
//======================= VARS ========================//
define('ROOT', __DIR__.'/');
//=====================================================//


//===================== INCLUDES ======================//
include ROOT.'backstage/functions/core.php';
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
if ($page->isArticle())
{
	$article = getPageByProperty('id', 'article', $language);
	$includePath = ROOT."$article->path$article->page.php";
}
else
{
	if (!is_file(ROOT."$page->path$page->page.php")) $page = getPageByProperty('id', 'notFound', $language);
	$includePath = ROOT."$page->path$page->page.php";
}

include($includePath);

echo Page::getInstance()->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>