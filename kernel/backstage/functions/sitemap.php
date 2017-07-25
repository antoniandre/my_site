<?php

function getTree($page = 'sitemap', $exclude = [])
{
	$defaultSkipPages = ['sitemap', 'not-found', 'forbidden', 'article', '[article]'];
	$exclude = array_merge($defaultSkipPages, $exclude);

	$pagesTree = getChildrenPages($page, $exclude);

	// Put home page at the same level as home page descendents.
	$pagesTree = array_merge(
		['home' => ['id' => $pagesTree['home']['id'], 'title' => $pagesTree['home']['title']]],
		$pagesTree['home']['children']
	);

	return '<ul class="lvl0 glyph">'.displayTree($pagesTree, 1).'</ul>';
}

/**
 * getChildrenPages() function retrieves the tree of children pages recursively.
 * The tree is constructed with or without backstage branch according to the user rights,
 * and without the pages in array $skipPages.
 *
 * @param string $page: the starting page to look for children recursively.
 * @return array: the new pages tree.
 */
function getChildrenPages($page, $exclude = [])
{
	global $user;
	$pages = Page::getAllPages();
	$language = Language::getCurrent();
	$pagesTree = [];

	// If '[article]' is in exclude, exclude all the articles.
	$excludeArticles = array_search('[article]', $exclude);
	if ($excludeArticles !== false) unset($exclude[$excludeArticles]);

	foreach ($pages as $pageId => $thePage)
	{
		if ($thePage->parent === $page && (!in_array($pageId, $exclude)
			&& ($pageId !== 'backstage' || ($pageId == 'backstage' && $user->isAdmin()))))
		{
			if ($excludeArticles !== false && $thePage->article) continue;
			$pagesTree[$pageId]['id'] = $pageId;
			$pagesTree[$pageId]['title'] = $thePage->title->$language;
			$count = count($children = getChildrenPages($pageId, $exclude));
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
function displayTree($tree, $depth = 0)
{
	$html = '';
	foreach ($tree as $pageId => $thePage)
	{
		$count = isset($thePage['children']) ? count($thePage['children']) : 0;
		$html .= "<li class=\"$thePage[id]".($count ? ' parent' : '')."\"><a href=\"".url($thePage['id'])."\">$thePage[title]</a>";
		if ($count)
		{
			$html .= "<ul class=\"lvl$depth\">";
			$html .= displayTree($thePage['children'], $depth+1);
			$html .= "</ul>";
		}
		$html .= "</li>";
	}

	return $html;
}
?>