<?php
//======================= VARS ========================//
$js = ['jquery', 'common'];// JS files to load
$readyFunctions = [];
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$lang = isset($_GET['l']) && in_array($_GET['l'], ['en', 'fr']) ? $_GET['l'] : 'en';
$page = isset($_GET['p']) ? $_GET['p'] : '';

if (isset($_GET['backstage']))
{
	$js[] = 'backstage.common';
	$readyFunctions[] = 'backstage';
}

// Prepare an array that lists all the scripts available and whether they have an associated CSS or not
// and whether they are loaded or not.
$scripts = [];
$existingJsFiles = scandir(__DIR__);
foreach ($existingJsFiles as $file) if (substr($file, -3, 3) === '.js')
{
    $filename = basename($file, '.js');
    $scripts[$filename] = ['loaded' => in_array($filename, $js), 'css' => is_file(__DIR__."/../css/$filename.css")];
}

// This file (index.php) is called with a requested page JS behavior in param.
$requestedJs = (isset($_GET['backstage']) ? 'backstage.' : '').$_GET['p'];
// Only add the requested JS if it exists.
// TODO: Should better secure this with an array of allowed scripts.
if (array_key_exists($requestedJs, $scripts))
{
    $js[] = $requestedJs;
	$readyFunctions[] = str_replace('-', '', $_GET['p']);
}

// Prepare the single output js file.
$jsFiles = '';
foreach($js as $k => $filename) if ($filename && is_file("$filename.js"))
{
	$jsFiles .=  ($k?"\n\n\n":'').file_get_contents("$filename.js");
}

// Create an array of functions to call when DOM is ready.
$onReady = '';
if (count($readyFunctions)) foreach ($readyFunctions as $function)
{
	$onReady .= "{$function}Ready();";
}

// Append few vars and array of ready functions to the output.
$jsOutput = "var l = '$lang',\n    page = '$page',    scripts = ".json_encode($scripts).";\n\n$jsFiles\n\n\$(document).ready(function(){commonReady();$onReady});";

// TODO: find the right cache for images
// header("Pragma: public");
// header("Cache-Control: maxage=$expires");
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
// header('Content-Type: text/html; charset=utf-8');
// header('Content-language: '.strtolower($language));

//if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler');else ob_start();
header('Content-type: text/javascript');
echo $jsOutput;
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>