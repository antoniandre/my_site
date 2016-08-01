<?php

function getTree($page = 'sitemap', $exclude = [])
{
	$defaultSkipPages = ['sitemap', 'not-found', 'forbidden', 'article', '[article]'];
	$exclude = array_merge($defaultSkipPages, $exclude);

	$pagesTree = getChildrenPages($page, $exclude);
	return '<ul class="lvl0 glyph">'.displayTree($pagesTree['home'], 1).'</ul>';
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
	global $pages, $user;
	$language = Language::getCurrent();
	$pagesTree = [];
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