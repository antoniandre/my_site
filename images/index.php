<?php
//======================= VARS ========================//
//every 5 images the url function redirects to another static domain: img1,img2,etc.
// $staticSubDom = new StdClass();
// $staticSubDom->nbImgPerDom = 5;//number of images to load per static domain
// $staticSubDom->imgCount = 0;
// $staticSubDom->currImgDom = 1;
$imagePath = '';
// $static = isset($_GET['s']) && $_GET['s'];// If static image domain.
$useImagickCLI = true;

define('IMAGES_PATH',  './');
define('UPLOADS_PATH', __DIR__ . '/../uploads/');
define('THEME_PATH',   __DIR__ . '/../themes/' . trim(file_get_contents('../themes/active')) . '/');
define('THEME_IMAGES', THEME_PATH . 'images/');

// Strlen < 120 for more security.
$t = isset($_GET['t']) && strlen($_GET['t']) < 120 ? $_GET['t'] : null;
$u = isset($_GET['u']) && strlen($_GET['u']) < 120 ? $_GET['u'] : null;
$i = isset($_GET['i']) && strlen($_GET['i']) < 120 ? $_GET['i'] : null;

// f = fallback if main image fails to load.
$imageFallback = isset($_GET['f']) && strlen($_GET['f']) < 120 ? addslashes($_GET['f']) : null;
$imageFallbackSrc = $imageFallback && isset($_GET['fs']) && strlen($_GET['fs']) === 1 ? $_GET['fs'] : null;
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
ob_start(substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? 'ob_gzhandler' : null);

if ($t)     $imagePath = THEME_IMAGES . addslashes($_GET['t']);
if ($u)     $imagePath = UPLOADS_PATH . addslashes($_GET['u']);
elseif ($i) $imagePath = addslashes($_GET['i']);

// The file is existing on the server, just output the file to the browser with correct mime type.
if (is_file($imagePath)) list($image, $mimeType) = displayExistingFile($imagePath);

// The requested file is an image with a size that does not exist on the server:
// Generate new image size on the fly if asked image does not exist but original does.
elseif (preg_match('~(.*)_(xs|s|m|l|xl|xxl)\.(jpe?g|png|gif)~', $imagePath, $matches)
        && is_file(IMAGES_PATH . "$matches[1]_o.$matches[3]"))
{
    list(, $name, $size, $extension) = $matches;
    resizeAndOutputImage($name, $size, $extension);
}

elseif ($imageFallback
        && ($fs = $imageFallbackSrc === 't' ? THEME_IMAGES : ($imageFallbackSrc === 'u' ? UPLOADS_PATH : IMAGES_PATH))
        && is_file("$fs$imageFallback"))
{
    list($image, $mimeType) = displayExistingFile("$fs$imageFallback");
}

// The requested file is not found on the server. Display an empty image.
else
{
    $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');// Blank 1x1 transparent gif image.
    $mimeType = 'image/gif';
}

// HEADERS SET IN images/.htaccess.
// header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');

//set headers to NOT cache a page
// header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
// header("Pragma: no-cache"); //HTTP 1.0
// header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

//or, if you DO want a file to cache, use:
// header("Pragma: public");
// header("Cache-Control: max-age=60, public"); //7 days.

header("Content-type: $mimeType");
die("$image");
//============================================ end of MAIN =============================================//
//======================================================================================================//



function displayExistingFile($imagePath)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $imagePath);
    finfo_close($finfo);

    $image = file_get_contents($imagePath);

    return [$image, $mimeType];
}


function resizeAndOutputImage($name, $size, $extension)
{
    global $useImagickCLI;
    $originalImagePath = __DIR__."/{$name}_o.$extension";
    $newImagePath = __DIR__."/{$name}_$size.$extension";
    // Sizes by total number of pixels.
    /*$sizes = ['xs' => 152100,// 450*338
              's'  => 270000,// 600*3/4
              'm'  => 607500,// 900*900*3/4
              'l'  => 1470000,// 1400*1400*3/4
              'xl' => 2500000,// 2000*2000*3/4
              'o'  => 3145728];*/// 2048*2048*3/4
    // To use with '@':
    // exec("$convertPath '$originalImagePath' -resize $sizes[$size]@ -unsharp 2x0.5+0.6+0 -quality 90 '$newImagePath';");
    // Sizes by width.
    $sizes = ['xs' => 220*1.3334,// 1.3334 for retina display (should be *2 but too costy).
              's'  => 300*1.3334,
              'm'  => 450*1.3334,
              'l'  => 700*1.3334,
              'xl' => 1600,
              'xxl' => 2000,
              'o'  => 2048];


    // Using imagemagick from the built-in php library (php extension).
    if (!$useImagickCLI)
    {
        //------------- Simple test ---------------//
        // $image = new Imagick($originalImagePath);
        // $image->thumbnailImage(200, 0);
        // header('Content-type: image/jpeg');
        // die($image);
        //-----------------------------------------//

        $image = new Imagick($originalImagePath);// Start from original picture.

        // Leave a width or height param to 0 to keep ratio.
        // The FILTER_CATROM method is the best compromise between fast and best result.
        $image->resizeImage($width = $sizes[$size], $height = 0 , $filter = Imagick::FILTER_CATROM, $blur = 1);
        $image->normalizeImage();

        $image->unsharpMaskImage($radius = 2, $sigma = .5, $amount = .6, $unsharpThreshold = 0);// mine.
        // $image->unsharpMaskImage(0, .5, 1, .5);// from http://php.net/manual/fr/imagick.unsharpmaskimage.php.
        // $image->unsharpMaskImage(0.3, 0, 400, 0);// from http://content.photojojo.com/tutorials/photoshop-sharpening/.

        $image->setImageFormat($extension);
        $image->setCompressionQuality(90);

        // Save to a file.
        $image->writeImage($newImagePath);
    }

    // Using imagemagick through command line...
    else
    {
        $convertPath = $_SERVER['SERVER_ADDR'] === '127.0.0.1' ? '/usr/local/bin/convert' : 'convert';

        // Convert the image to the $size resized format using Imagemagick lib.
        exec("$convertPath '$originalImagePath' -resize $sizes[$size]x\> -unsharp 2x0.5+0.6+0 -quality 90 '$newImagePath';");

        $image = file_get_contents($newImagePath);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $newImagePath);
    finfo_close($finfo);

    header("Content-type: $mimeType");
    die($image);
}
?>