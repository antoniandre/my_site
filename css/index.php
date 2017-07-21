<?php
/**
 * This file is not accessible directly, it is included from functions/core.php.
 * The classes and vars Settings, Userdata, $page are defined in functions/core.php.
 * The folder css/ and its content are not accessible from the site.
 */

//======================= VARS ========================//
if (!class_exists('Userdata') || !class_exists('Settings')) die('You can\'t access this file directly.');
$gets = Userdata::get();
$settings = Settings::get();

$min = $settings->useMinified ? '.min' : '';
$css = ["common$min", "form$min", 'vendor/slick/slick.css'];// CSS files to load.

$useCompress = !IS_LOCAL;// Do not minify on localhost.
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
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
    // If in vendor.
	if (strpos($filename, 'vendor') === 0 && is_file(ROOT.$filename))
	{
	    $cssFiles .=  ($k ? "\n\n\n" : '').file_get_contents(ROOT.$filename);
	}
    // If in normal css folder.
	elseif ($filename && is_file(ROOT."css/$filename.css"))
	{
	    $cssFiles .=  ($k ? "\n\n\n" : '').file_get_contents(ROOT."css/$filename.css");
	}
}

// Preg_replace to replace css 'url(/path)' with 'url(ROOT.'css/path)' except if 'data:' is found.
$cssOutput = preg_replace('~url\( ?(?:([\'"])(?!data:)(.+?)\1|(?!data:)([^\'" ]+?)) ?\)~', 'url("'.$settings->root.'css/$2$3")', $cssFiles);
$cssOutput = "@charset \"UTF-8\";\n$cssOutput";


// @TODO: find the right caching.
// header("Pragma: public");
// header("Cache-Control: maxage=$expires");
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');

header('Content-type: text/css; charset=utf-8');
die((string)($useCompress ? compress($cssOutput) : $cssOutput));
//============================================ end of MAIN =============================================//
//======================================================================================================//


function compress($cssOutput)
{
    //!\ Don't change the sequence bellow.
    // 1.
    $patternComments               = '/\*.*?\*/';// Remove comments.
    $patternWhiteSpaces            = '[\t\r\n\f\v\e]';// Remove tabs, carret returns, line feeds...

    // 2.
    // $patternSpaceBefore            = ' +(?=[@,(){};!>~])';// Remove all spaces before following characters '@,(){}};!>~'.
    $patternSpaceBefore            = ' +(?=[@,{};!>~])';// Remove all spaces before following characters '@,{}};!>~'.
    $patternSpaceB4BracketIfNotAnd = '(?<!and) +(?=[(])';// Remove all spaces before '(' only if previous word is different than 'and'.
                                                         // Yes! It seems that the media query is not understood like "@media screen
                                                         // and(max-width: 550px)... Lame."

    $patternSpaceAfter             = '(?<=[,(){}:;>~]) +';// Remove all spaces after following characters ',(){}}:;>~'.
    $patternUselessSemiColumn      = ';(?=[};])';// Remove every ';' right before '}' or ';'.
    $patternDecimal                = '(?<=,|\()0(?=\.)';// Replace ',0.1' with ',.1'.

    return preg_replace(
    [
        "~$patternComments|$patternWhiteSpaces~s",
        "`$patternSpaceBefore|$patternSpaceAfter|$patternUselessSemiColumn|$patternDecimal`",
    ], '', $cssOutput);
}
?>