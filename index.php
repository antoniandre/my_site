<?php
//======================= VARS ========================//
define('ROOT', __DIR__.'/');
//=====================================================//


//===================== INCLUDES ======================//
include ROOT.'kernel/backstage/functions/core.php';
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
// @todo: write a Cache class.
/*if ($settings->useCache && !Userdata::is_set('post'))
{
    include(ROOT."kernel/backstage/cache/$page->path$page->page.html");
}*/


include checkInTheme(getPagePath());
//============================================ end of MAIN =============================================//
//======================================================================================================//

function getPagePath()
{
	$page = Page::getCurrent();

	if ($page->isArticle())
	{
		$article     = Page::get('article');
		$includePath = $article->path . $article->page;
	}
	else
	{
		if (!is_file(ROOT."kernel/$page->path$page->page.php")) $page = Page::get('not-found');
		$includePath = $page->path . $page->page;
	}

    $backstage   = strpos($includePath, 'backstage/pages/') === 0 ? 'backstage/' : '';
    $includePath = str_replace(['backstage/', 'pages/'], '', $includePath);

	return "kernel/{$backstage}pages/$includePath.php";
}
?>