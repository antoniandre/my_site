<?php
//====================== = VARS ========================//
$imgWidth = 175;
$imgHeight = 45;
$font = 'arial.ttf';
$fontSize = $imgHeight*0.85;
$bgColor = (isset($_GET['bg']) && $_GET['bg']!='undefined') ? '#'.strtoupper($_GET['bg']) : '';#1E295C
$x = 5;
//=====================================================//


//======================= INCLUDES ====================//
//=====================================================//


//====================== TOP MISC =====================//
$img = imagecreatetruecolor($imgWidth, $imgHeight);//creates the image
//=====================================================//


//======================================================================================================//
//============================================ = MAIN ===================================================//
imagesavealpha($img, true);//This will make it transparent

$bgColor = $bgColor ? imagecolorallocate($img, hexdec(substr($bgColor, 1, 2)), hexdec(substr($bgColor, 3, 2)),
							 		     hexdec(substr($bgColor, 5, 2)))
		  		  : imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $bgColor);


//-------------------------------------- security code creation -------------------------------------//
$code = mt_rand(1000, 9999).chr(mt_rand(65, 90)).chr(mt_rand(65, 90));//mt_rand is faster than rand.
//uppercase alphabetic letters are found between 65 & 90

if (!headers_sent() && !isset($_SESSION)) {ob_start();session_start();}//to avoid errors...
$_SESSION['securityCode'] = md5(strtolower($code));
//---------------------------------------------------------------------------------------------------//


//-------------------------------- writting security code in image ----------------------------------//
$shadowColor = imagecolorallocatealpha($img, 100, 100, 100, 80);

foreach (str_split($code) as $char)//each character has different angle size and color
{
	$randFontSize = mt_rand($fontSize-20, $fontSize);
	$randAngle = mt_rand(-20, 20);
	$randAlpha = mt_rand(10, 50);
	$fontColor = imagecolorallocatealpha($img, 100, 100, 100, $randAlpha);

	$bbox = imagettfbbox($randFontSize, 0, $font, $char);
	$y = $bbox[1]+(imagesy($img)/2)-($bbox[5]/2);

	imagettftext($img, $randFontSize, $randAngle, $x, $y, $fontColor, $font, $char);
	imagettftext($img, $randFontSize, $randAngle, $x+3, $y+3, $shadowColor, $font, $char);
	$x += $bbox[4]+2;//2 = characters interval
}
//---------------------------------------------------------------------------------------------------//

for($i=5;$i<($x*$y)/150;$i++)//adding some noise
	imagefilledellipse($img, mt_rand(0, $x), mt_rand(0, $y), mt_rand(1, 10), mt_rand(1, 10), $shadowColor);


header("Content-type:image/png");
header("Content-Disposition:inline;filename=captcha.png");
imagepng($img);
imagedestroy($img);//empty cache
?>