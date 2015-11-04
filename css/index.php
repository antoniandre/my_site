<?php
//======================= VARS ========================//
$css = ['common'.($settings->useMinified ? '.min' : '')];// CSS files to load.
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$gets = Userdata::get();
$settings = Settings::get();

// $onlyCss & $extraCss can both be array or string. E.g: 'o[]=css1&o[]=css2' or 'o=css'.
$onlyCss = isset($gets->o) ? $gets->o : '';
$extraCss = isset($gets->e) ? $gets->e : '';

if (!$page && !$extraCss && !$onlyCss) die;


// Detect if old browser with get var 'ie={version}' and add css file to the stack.
if (isset($gets->ie))
{
    // Add a generic 'old-browsers' css file to the stack. (adding support for HTML5 tags).
    $css[] = 'old-browsers';
    // Add a possible more specific 'ie{version}' css file to the stack. E.g. 'ie7.css'.
    if (in_array($gets->ie, [6, 7, 8])) $css[] = 'ie'.$gets->ie;
}

// If only one css file is needed.
if ($onlyCss) $css = (array)$onlyCss;
else
{
	if ($extraCss) $css = array_merge($css, (array)$extraCss);
	if ($page->isBackstage()) $css[] = 'backstage.common';
	elseif ($page->isArticle()) $css[] = 'article';

	$css[] = ($page->isBackstage() && $user->isAdmin() ? 'backstage.' : '').$page->page;	
}

// Now add each css file in the output string if the file exists.
$cssFiles = '';
foreach($css as $k => $filename)
{
	if ($filename && is_file(__DIR__."/$filename.css"))
	{
	    $cssFiles .=  ($k ? "\n\n\n" : '').file_get_contents(__DIR__."/$filename.css");
	}
}

// Preg_replace to replace css 'url(/path)' with 'url(ROOT.'css/path)' except if 'data:' is found.
$cssOutput = preg_replace('~url\( ?(?:([\'"])(?!data:)(.+?)\1|(?!data:)([^\'" ]+?)) ?\)~', 'url("'.$settings->root.'css/$2$3")', $cssFiles);


// @TODO: find the right cache for images.
// header("Pragma: public");
// header("Cache-Control: maxage=$expires");
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
// header('Content-Type: text/html; charset=utf-8');
// header('Content-language: '.strtolower($language));

header('Content-type: text/css; charset=utf-8');
die("$cssOutput");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>