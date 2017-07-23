<?php

/**
 * Idea: give an array of page ids (e.g ['site-map', 'home', 'contact-us']) and it will generate the menu html markup.
 * @todo To develop and use.
 * @param  array  $items     [description]
 * @param  string $class     [description]
 * @param  string $ariaLabel [description]
 * @return [type]            [description]
 */
function doMenu($items = [], $class = '', $ariaLabel = '')
{
	$html = '';

	if (count($items))
	{
		foreach ($items as $pageId => $thePage)
		{
			$html .= "<li><a href=\"".url($thePage['id'])."\">$thePage[title]</a></li>";
		}

		return "<nav class='menu' role='navigation' aria-label=''><ul>$html</ul></nav>";
	}
}
?>