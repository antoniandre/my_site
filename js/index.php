<?php

//======================= VARS ========================//
$js = ['jquery', 'form', 'common'.($settings->useMinified ? '.min' : ''), 'jquery.lazyload'];// JS files to load.
$css = ['common'];// CSS files to load
$readyFunctions = [];
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
if ($page->isArticle())
{
    $js[] = 'article';
	$readyFunctions[] = 'article';
}

elseif ($page->isBackstage())
{
	$js[] = 'backstage.common';
	$readyFunctions[] = 'backstage';
}

// Prepare an array that lists all the scripts available and whether they have an associated CSS or not
// and whether they are loaded or not.
$scripts = [];
$existingJsFiles = array_diff(scandir(__DIR__), ['.', '..']);
foreach ($existingJsFiles as $file) if (substr($file, -3, 3) === '.js')
{
	// Do not list backstage js files if user is not admin.
	if ((!$user->isAdmin() && strpos($file, 'backstage') !== false)) continue;

    $filename = basename($file, '.js');
    $scripts[$filename] = ['loaded' => in_array($filename, $js), 'css' => is_file(ROOT."css/$filename.css")];
}

// This file (index.php) is called with a requested page JS behavior in param.
$requestedJs = ($page->isBackstage() ? 'backstage.' : '').$page->page;
// Only add the requested JS if it exists.
// TODO: Should better secure this with an array of allowed scripts.
if (array_key_exists($requestedJs, $scripts))
{
    $js[] = $requestedJs;
	$readyFunctions[] = str_replace('-', '', $page->page);
}

// Prepare the single output js file.
$jsFiles = '';
foreach($js as $k => $filename) if ($filename && is_file(ROOT."js/$filename.js"))
{
	$jsFiles .=  ($k?"\n\n\n":'').file_get_contents(ROOT."js/$filename.js");
}

// Create an array of functions to call when DOM is ready.
$onReady = '';
if (count($readyFunctions)) foreach ($readyFunctions as $function)
{
	$onReady .= "{$function}Ready();";
}

// Append few vars and array of ready functions to the output.
$jsOutput = "var l = '$language',\n    localhost= ".(int)IS_LOCAL.",\n    page = '$page',\n    scripts = ".json_encode($scripts).";\n\n$jsFiles\n\n\$(document).ready(function(){commonReady();$onReady});";

// TODO: find the right cache for images
// header("Pragma: public");
// header("Cache-Control: maxage=$expires");
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
// header('Content-Type: text/html; charset=utf-8');
// header('Content-language: '.strtolower($language));

header('Content-type: text/javascript');
die("$jsOutput");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>