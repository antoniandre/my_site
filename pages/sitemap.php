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
$pagesTree = getChildrenPages('sitemap');
$content .= '<ul class="lvl0 glyph">';
$content .= displayTree($pagesTree['home'], 1);
$content .= '</ul>';

$tpl = new Template();
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_var(['content' => $content]);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//

/**
 * getChildrenPages() function retrieves the tree of children pages recursively.
 * The tree is constructed with or without backstage branch according to the user rights,
 * and without the pages in array $skipPages.
 *
 * @param string $page: the starting page to look for children recursively.
 * @return array: the new pages tree.
 */
function getChildrenPages($page)
{
	global $pages, $skipPages, $user;
	$language = Language::getCurrent();
	$pagesTree = [];

	foreach ($pages as $pageId => $thePage)
	{
		if ($thePage->parent === $page && (!in_array($pageId, $skipPages)
			&& ($pageId !== 'backstage' || ($pageId == 'backstage' && $user->isAdmin()))))
		{
			$pagesTree[$pageId]['id'] = $pageId;
			$pagesTree[$pageId]['title'] = $thePage->title->$language;
			$count = count($children = getChildrenPages($pageId));
			if ($count) $pagesTree[$pageId]['children'] = $children;
		}
	}
	return $pagesTree;
}

/**
 * displayTree() function outputs the calculated html sitemap for the given tree (multidimensional array).
 * Renders the sitemap recursively.
 *
 * @param array $tree: the pages tree to render.
 * @param int $depth: the depth level of the current list for css class. 
 * @return string: the final html of the sitemap.
 */
function displayTree($tree, $depth)
{
	$count = isset($tree['children']) ? count($tree['children']) : 0;

	$html = "<li class=\"$tree[id]".($count ? ' parent' : '')."\"><a href=\"".url($tree['id'])."\">$tree[title]</a>";
	if ($count)
	{
		$html .= "<ul class=\"lvl$depth\">";
		foreach ($tree['children'] as $pageId => $thePage) $html .= displayTree($thePage, $depth+1);
		$html .= "</ul>";
	}
	$html .= "</li>";

	return $html;
}
?>