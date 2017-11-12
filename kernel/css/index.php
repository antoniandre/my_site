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
// $oneCss & $extraCss can both be array or string. E.g: 'o[]=css1&o[]=css2' or 'o=css'.
$oneCss   = isset($gets->o) ? $gets->o : '';
$font     = isset($gets->font) ? $gets->font : '';

define('KERNEL_PATH', ROOT . 'kernel/css/');
define('THEME_PATH',  ROOT . "themes/$settings->theme/css/");
define('VENDOR_PATH', ROOT . 'vendor/');
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
if ($font) doFontOutput($font);
elseif (!$page && !$oneCss) die('/* No CSS requested. */');

// If only one css file is needed.
$cssContents = $oneCss ? addCssContents((array)$oneCss) : (addCommonCss() . addSpecificCss());

doOutput($cssContents);
//============================================ end of MAIN =============================================//
//======================================================================================================//


function getContents($fileName, $from = 'k')
{
    $settings = Settings::get();
    $min      = $settings->useMinified ? '.min' : '';
    $k        = KERNEL_PATH;
    $t        = THEME_PATH;
    $v        = VENDOR_PATH;

    return is_file($$from . "$fileName$min.css") ? "\n\n\n" . file_get_contents($$from . "$fileName$min.css") : null;
}


function addSpecificCss()
{
    $page        = Page::getCurrent();
    $gets        = Userdata::get();
    $user        = User::getCurrent();
    $extraCss    = isset($gets->e) ? $gets->e : '';
    $cssContents = '';

    // Detect if old browser with get var 'ie={version}' and add a generic 'old-browsers' css (adding support for HTML5 tags).
    if (isset($gets->ie)) $cssContents .= getContents('old-browsers', 'k') . getContents('old-browsers', 't');

	if ($extraCss) $cssContents .= addCssContents((array)$extraCss);

    if ($page->isBackstage())   $cssContents .= getContents('backstage.common', 'k') . getContents('backstage.common', 't');
	elseif ($page->isArticle()) $cssContents .= getContents('article', 'k') . getContents('article', 't');
	elseif ($page->type !== 'page') $cssContents .= getContents($page->type, 't');

    // Backstage css files won't be added to generated css if user is not admin.
    $css = ($page->isBackstage() && $user->isAdmin() ? 'backstage.' : '') . $page->page;
    $cssContents .= getContents($css, 'k') . getContents($css, 't');

    return $cssContents;
}


function addCommonCss()
{
    $settings    = Settings::get();
    $cssContents = '';

    foreach ($settings->commonCssList as $i => $fileName)
    {
        $v = null;
        $k = null;
        $t = null;
        list($fileName, $from) = array_pad(explode(':', $fileName), 2, 'k');

        if ($fileName)
        {
            if (strpos($from, 'v') !== false && $v = getContents($fileName, 'v')) $cssContents .= $v;
            if (strpos($from, 'k') !== false && $k = getContents($fileName, 'k')) $cssContents .= $k;
            if (strpos($from, 't') !== false && $t = getContents($fileName, 't')) $cssContents .= $t;
        }
    }

    return $cssContents;
}


function addCssContents($css)
{
    // Now add each css file in the output string if the file exists.
    $cssContents = '';
    if (count($css)) foreach($css as $fileName) if ($fileName)
    {
        // If in vendor.
        if (strpos($fileName, 'vendor') === 0 && $v = getContents($fileName, 'v')) $cssContents .= $v;

        // If in normal css folder.
        elseif ($k = getContents($fileName, 'k')) $cssContents .= $k;

        // If in theme css folder.
        if ($t = getContents($fileName, 't')) $cssContents .= $t;
    }

    return $cssContents;
}


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


function doOutput($cssContents)
{
    $settings    = Settings::get();
    $useCompress = !IS_LOCAL;// Do not minify on localhost.

    // Preg_replace to replace css 'url(/path)' with 'url(ROOT.'css/path)' except if 'data:' is found.
    $cssOutput = preg_replace('~url\( ?(?:([\'"])(?!data:|\{THEME_IMAGES\})(.+?)\1|(?!data:)([^\'" ]+?)) ?\)~',
                              'url("'.$settings->root.'css/$2$3")',
                              trim($cssContents));
    $cssOutput = str_replace('{THEME_IMAGES}', $settings->root.'images?t=', $cssOutput);
    $cssOutput = "@charset \"UTF-8\";\n$cssOutput";

    // The right caching is done in the main .htaccess.
    // header("Pragma: public");
    // header("Cache-Control: maxage=$expires");
    // header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');

    header('Content-type: text/css; charset=utf-8');
    die((string)($useCompress ? compress($cssOutput) : $cssOutput));
}


function getFont($font)
{
    $src = null;
    if     (is_file($t = THEME_PATH  . "fonts/$font")) $src = $t;
    elseif (is_file($k = KERNEL_PATH . "fonts/$font")) $src = $k;

    return $src ? file_get_contents($src) : '';
}


function doFontOutput($font)
{
    header('Content-type: application/font-woff; charset=utf-8');
    die((string)getFont($font));
}

?>