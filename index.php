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
// dbgd(UserData::get()->font);
if (isset(UserData::get()->js))  include ROOT . 'kernel/js/index.php';
if (isset(UserData::get()->css)) include ROOT . 'kernel/css/index.php';
if (isset(UserData::get()->font)) include ROOT . 'kernel/css/fonts/'.UserData::get()->font;


include getPagePath();
//============================================ end of MAIN =============================================//
//======================================================================================================//

function getPagePath()
{
	$page = Page::getInstance();

	if ($page->isArticle())
	{
		$article     = getPageByProperty('id', 'article', $language);
		$includePath = $article->path . $article->page;
	}
	else
	{
		if (!is_file(ROOT."kernel/$page->path$page->page.php")) $page = getPageByProperty('id', 'notFound', $language);
		$includePath = $page->path . $page->page;
	}

    $backstage   = strpos($includePath, 'backstage/pages/') === 0 ? 'backstage/' : '';
    $includePath = str_replace(['backstage/', 'pages/'], '', $includePath);

	return "kernel/{$backstage}pages/$includePath.php";
}
?>