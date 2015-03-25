<?php
//======================= VARS ========================//
//every 5 images the url function redirects to another static domain: img1,img2,etc.
$staticSubDom= new StdClass();
$staticSubDom->nbImgPerDom= 5;//number of images to load per static domain
$staticSubDom->imgCount= 0;
$staticSubDom->currImgDom= 1;
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$static= isset($_GET['s']) && $_GET['s'];// If static image domain
$image= isset($_GET['i']) && strlen($_GET['i'])< 120? addslashes($_GET['i']) : '';// Strlen<120 for more security.
$imageParts= pathinfo($image);

if (is_file(__DIR__."/$image"))
{
	$image= file_get_contents(__DIR__."/$image");
	$extension= $imageParts['extension'];
	$extension= $extension== 'ico'? 'x-icon' : $extension;
}
else
{
	$image= base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==');//blank 1x1 gif image
	$extension= 'gif';
}

//if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler');else ob_start();
// TODO: if static is provided, curl to the static domain

// TODO: find the right cache for images
// header("Pragma: public");
// header("Cache-Control: maxage=$expires");
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
// header('Content-Type: text/html; charset=utf-8');
// header('Content-language: '.strtolower($language));
header("Content-type: image/$extension");
echo $image;
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>