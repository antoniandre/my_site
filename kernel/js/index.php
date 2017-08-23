<?php
/**
 * This file is not accessible directly, it is included from functions/core.php.
 * The classes and vars Settings, Userdata, $page are defined in functions/core.php.
 * The folder css/ and its content are not accessible from the site.
 */

if (!class_exists('Userdata') || !class_exists('Settings')) die('You can\'t access this file directly.');

//======================= VARS ========================//
$settings = Settings::get();
$gets     = Userdata::get();

define('KERNEL_PATH', ROOT . 'kernel/js/');
define('THEME_PATH',  ROOT . "themes/$settings->theme/js/");
define('VENDOR_PATH', ROOT . "vendor/");
define('INIT_ACTION', !isset($gets->get));

// $getJs can be array or string. E.g: 'get[]=js1&get[]=js2' or 'get=js'.
$getJs = INIT_ACTION ? $gets->get : null;
$readyFunctions = [];
$loadedJs       = [];
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
if (!$page && !$getJs) die('// No JS requested.');

list($files, $fileLocations) = getAllFiles();
$requestedFiles              = getRequestedFiles();
$contents                    = getFilesContents($requestedFiles);

doOutput(INIT_ACTION ? addJsVars($contents) : $contents);
//============================================ end of MAIN =============================================//
//======================================================================================================//


function getAllFiles()
{
    $files = [];
    $fileLocations = [];

    foreach (scandir(KERNEL_PATH) as $file) if (substr($file, -3, 3) === '.js')
    {
        $fileName = str_replace('.min', '', basename($file, '.js'));
        $files[$fileName] = 1;
        $fileLocations["$fileName:k"] = 1;
    }
    foreach (scandir(THEME_PATH) as $file) if (substr($file, -3, 3) === '.js')
    {
        $fileName = str_replace('.min', '', basename($file, '.js'));
        $files[$fileName] = 1;
        $fileLocations["$fileName:t"] = 1;
    }

    return [array_keys($files), array_keys($fileLocations)];
}

function getRequestedFiles()
{
    $settings = Settings::get();
    $getJs    = INIT_ACTION ? null : Userdata::get()->get;

    return $getJs ? $getJs : array_merge($settings->commonJsList, getPageRelatedFiles());
}

function getPageRelatedFiles()
{
    global $readyFunctions;

    $page       = Page::getCurrent();
    $language   = Language::getCurrent();
    $user       = User::getInstance();
    $files      = [];

    // If page is in backstage load the backstage.common script.
    if ($page->isBackstage())  $files[] = 'backstage.common';

    // If page is an article load the article script.
    elseif ($page->isArticle()) $files[] = 'article';
	elseif ($page->type !== 'page') $files[] = $page->type;

    // Load the current page script if existing.
    $files[] = ($page->isBackstage() && $user->isAdmin() ? 'backstage.' : '') . $page->page;

    $readyFunctions = array_merge($readyFunctions, (array)$files);

    return $files;
}

function getFilesContents($files)
{
    global $loadedJs;

    $jsContents = '';
    foreach ((array)$files as $file)
    {
        $v = null;
        $k = null;
        $t = null;
        $c = null;
        list($file, $from) = array_pad(explode(':', $file), 2, null);

        if ($file)
        {
            if (!$from && ($c = getContents($file, null))) $jsContents .= $c;
            if (strpos($from, 'v') !== false && ($v = getContents($file, 'v'))) $jsContents .= $v;
            if (strpos($from, 'k') !== false && ($k = getContents($file, 'k'))) $jsContents .= $k;
            if (strpos($from, 't') !== false && ($t = getContents($file, 't'))) $jsContents .= $t;

            if ($v || $k || $t || $c) $loadedJs = array_merge($loadedJs, (array)$file);
        }
    }

    return trim($jsContents);
}

function getContents($fileName, $from = null)
{
    global $fileLocations;

    $settings = Settings::get();
    $min      = $settings->useMinified ? '.min' : '';
    $k        = KERNEL_PATH;
    $t        = THEME_PATH;
    $v        = VENDOR_PATH;
    $fallback = null;

    $ok =  ($from && in_array("$fileName:$from", $fileLocations))
        || (!$from && ($fallback = 't') && in_array("$fileName:t", $fileLocations))
        || (!$from && ($fallback = 'k') && in_array("$fileName:k", $fileLocations))
        || ($from === 'v' && is_file($$from . "$fileName$min.js"));

    $from = $from ? $from : $fallback;

    return $ok ? "\n\n\n" . file_get_contents($$from . "$fileName$min.js") : null;
}


function addJsVars($jsContents)
{
    global $readyFunctions, $loadedJs, $files;

    $settings = Settings::get();
    $page     = Page::getCurrent();
    $language = Language::getCurrent();
    $user     = User::getInstance();

    // Create an array of functions to call when DOM is ready.
    $onReady  = '';
    if (count($readyFunctions)) foreach ($readyFunctions as $function)
    {
        if (in_array($function, $files)) $onReady .= str_replace('-', '', $function).'Ready();';
    }

    // Prepare an array that lists all the scripts available and whether they have an associated CSS or not
    // and whether they are loaded or not.
    // E.g.
    // scripts = {
    //    jquery:  {loaded: true,  css: false},
    //    common:  {loaded: true,  css: true},
    //    contact: {loaded: true,  css: true},
    //    home:    {loaded: false, css: true},
    //    ... List all the existing JS files.
    // }
    $scripts = [];
    foreach ($files as $file)
    {
        // Do not list backstage js files if user is not admin.
        if ((!$user->isAdmin() && strpos($file, 'backstage') !== false)) continue;

        $fileName = basename($file, '.js');
        $scripts[$fileName] = ['loaded' => in_array($fileName, $loadedJs), 'css' => is_file(ROOT."kernel/css/$filename.css")];
    }

    // Append few vars and array of ready functions to the output.
    $jsOutput = "var l         = '$language',\n"
              . "    ROOT      = '$settings->root',\n"
              . "    localhost = ".(int)IS_LOCAL.",\n"
              . "    page      = '$page->page',\n"
              . "    scripts   = ".json_encode($scripts).";\n\n"
              . "$jsContents\n\n"
              . "\$(document).ready(function(){commonReady();$onReady});";

    return $jsOutput;
}

function doOutput($jsContents)
{
    // The right caching is done in the main .htaccess.
    // header("Pragma: public");
    // header("Cache-Control: maxage=$expires");
    // header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');

    header('Content-type: text/javascript; charset=utf-8');
    die("$jsContents");
}

?>