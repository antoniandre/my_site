<?php
//======================= VARS ========================//
// Array of pages to hide on the sitemap page.
$skipPages = ['sitemap', 'not-found', 'forbidden', 'article'];
$content = '';
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
Page::getInstance()->setBreadcrumbsVisiblity(false);// Disable breadcrumbs on home page only.
$pagesTree = getChildrenPages('sitemap', 0);
$content .= '<ul class="lvl0 glyph">';
displayTree($pagesTree['home'], 1);
$content .= '</ul>';

$tpl = new Template('.');
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_var(['content' => $content]);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//

function getChildrenPages($page, $depth)
{
	global $pages, $skipPages, $language;
	$pagesTree = [];

	foreach ($pages as $pageId => $thePage) if ($thePage->parent === $page && !in_array($pageId, $skipPages))
	{
		$pagesTree[$pageId]['id'] = $pageId;
		$pagesTree[$pageId]['title'] = $thePage->title->$language;
		$count = count($children= getChildrenPages($pageId, $depth+1));
		if ($count) $pagesTree[$pageId]['children'] = $children;
	}

	return $pagesTree;
}

function displayTree($tree, $depth)
{
	global $content;

	$count = isset($tree['children']) ? count($tree['children']) : 0;

	$content .= "<li class=\"$tree[id]".($count ? ' parent' : '')."\"><a href=\"".url($tree['id'])."\">$tree[title]</a>";
	if ($count)
	{
		$content .= "<ul class=\"lvl$depth\">";
		foreach ($tree['children'] as $pageId => $thePage) displayTree($thePage, $depth+1);
		$content .= "</ul>";
	}
	$content .= "</li>";
}
?>