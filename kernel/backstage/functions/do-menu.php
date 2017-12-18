<?php

/**
 * Idea: give an array of page ids (e.g ['site-map', 'home', 'contact-us']) and it will generate the menu html markup.
 *
 * @param  array  $params    [description]
 * @return [type]            [description]
 */
function doMenu($items = [], $params)
{
    global $language;
    $pages         = Page::getAllPages();
	$html          = '';
    $defaults      = ['class' => '', 'ariaLabel' => '', 'showChildren' => true, 'showIcons' => false];
    $params        = array_merge($defaults, $params);
    $class         = $params['class'];
    $ariaLabel     = $params['ariaLabel'];
    $showChildren  = $params['showChildren'];
    $showIcons     = $params['showIcons'];
    $excludeArray  = $params['exclude'];

    if (count($items))
    {
        foreach ($items as $pageId)
        {
            $page = $pages[$pageId];
            $submenu = '';
            $linkIconClass = $showIcons ? " class=\"$page->icon\"" : '';

            if ($showChildren && $sub = displayTree(getChildrenPages($pageId, $excludeArray)))
            {
                $submenu = '<ul>' . $sub . '</ul>';
            }
			$html .= "<li class=\"$pageId" . ($submenu ? ' parent' : '') . "\"><a href=\"" . url($pageId) . "\"$linkIconClass><span>{$pages[$pageId]->title->$language}</span></a>$submenu</li>";
		}

        return "<nav class='menu $class' role='navigation' aria-label='$ariaLabel'><ul>$html</ul></nav>";
	}
}
?>