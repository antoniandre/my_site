<?php
//======================= VARS ========================//
$css= ['common'];// CSS files to load

// Detect if old browser with get var 'ie={version}' and add css file to the stack.
if (isset($_GET['ie']))
{
    // Add a generic 'old-browsers' css file to the stack. (adding support for HTML5 tags)
    $css[]= 'old-browsers';
    // Add a possible more specific 'ie{version}' css file to the stack. E.g. 'ie7.css'.
    if (in_array($_GET['ie'], [6, 7, 8])) $css[]= 'ie'.$_GET['ie'];
}
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
if (!isset($_GET['p'])) die;
$page= isset($_GET['p'])? $_GET['p'] : '';

if (isset($_GET['backstage']))
{
    $css[]= 'backstage';
}

if (isset($_GET['article']))
{
    $css[]= 'article';
}

if (1 == 1)// TODO: secure this:
{
    $css[]= (isset($_GET['backstage']) ? 'backstage.' : '').$_GET['p'];
}

$cssFiles= '';
foreach($css as $k => $filename) if ($filename && is_file("$filename.css"))
{
    $cssFiles.=  ($k?"\n\n\n":'').file_get_contents("$filename.css");
}


$cssOutput= $cssFiles;

// TODO: find the right cache for images
// header("Pragma: public");
// header("Cache-Control: maxage=$expires");
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
// header('Content-Type: text/html; charset=utf-8');
// header('Content-language: '.strtolower($language));

//if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler');else ob_start();
header('Content-type: text/css');
echo $cssOutput;
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>