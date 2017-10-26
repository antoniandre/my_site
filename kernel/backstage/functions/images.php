<?php

/**
 * display any url requested image.
 * Look into default root images/ dir, uploads/ dir, and themes/[current_theme]/images/ dir.
 * If the image requested is not found but an original size of the same image is found then generate a new version.
 * If the image requested cannot be found or created, display an empty 1x1 transparent gif.
 */
function displayImage()
{
    $settings = Settings::get();

    //every 5 images the url function redirects to another static domain: img1,img2,etc.
    // $staticSubDom = new StdClass();
    // $staticSubDom->nbImgPerDom = 5;//number of images to load per static domain
    // $staticSubDom->imgCount = 0;
    // $staticSubDom->currImgDom = 1;
    // $static = isset($_GET['s']) && $_GET['s'];// If static image domain.

    define('USE_IMAGICK_CLI',    true);
    define('LOCAL_CONVERT_PATH', '/usr/local/bin/convert');// A path to the convert command from Imagick CLI in Localhost.
    define('IMAGES_PATH',        './');
    define('UPLOADS_PATH',       '../uploads/');
    define('THEME_PATH',         '../themes/' . $settings->theme . '/');
    define('THEME_IMAGES',       THEME_PATH . 'images/');

    ob_start(substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? 'ob_gzhandler' : null);

    outputImage(...getImageAndMimeType());
}


/**
 * Get the image and the mime type of the requested image from the url.
 *
 * @return (array) [$image, $mimeType]
 */
function getImageAndMimeType()
{
    $imgAndMime = function($imagePath)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        $image = file_get_contents($imagePath);

        return [$image, $mimeType];
    };

    // Strlen < 120 for more security.
    $t = isset($_GET['t']) && strlen($_GET['t']) < 120 ? $_GET['t'] : null;
    $u = isset($_GET['u']) && strlen($_GET['u']) < 120 ? $_GET['u'] : null;
    $i = isset($_GET['i']) && strlen($_GET['i']) < 120 ? $_GET['i'] : null;

    // f = fallback if main image fails to load.
    $imageFallback    = isset($_GET['f']) && strlen($_GET['f']) < 120 ? addslashes($_GET['f']) : null;
    $imageFallbackSrc = $imageFallback && isset($_GET['fs']) && strlen($_GET['fs']) === 1 ? $_GET['fs'] : null;

    $imagePath = '';
    if ($t)     $imagePath = THEME_IMAGES . addslashes($t);
    if ($u)     $imagePath = UPLOADS_PATH . addslashes($u);
    elseif ($i) $imagePath = IMAGES_PATH  . addslashes($i);

    // The file is existing on the server, just output the file to the browser with correct mime type.
    if (is_file($imagePath)) list($image, $mimeType) = $imgAndMime($imagePath);

    // The requested file is an image with a size that does not exist on the server:
    // Generate new image size on the fly if asked image does not exist but original does.
    elseif (preg_match('~(.*)_(xs|s|m|l|xl|xxl)\.(jpe?g|png|gif)~', $imagePath, $matches)
            && is_file("$matches[1]_o.$matches[3]"))
    {
        list(, $name, $size, $extension) = $matches;
        list($image, $mimeType) = resizeImage($name, $size, $extension);
    }

    elseif ($imageFallback
            && ($fs = $imageFallbackSrc === 't' ? THEME_IMAGES : ($imageFallbackSrc === 'u' ? UPLOADS_PATH : IMAGES_PATH))
            && is_file("$fs$imageFallback"))
    {
        list($image, $mimeType) = $imgAndMime("$fs$imageFallback");
    }

    // The requested file is not found on the server. Display an empty image.
    else
    {
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');// Blank 1x1 transparent gif image.
        $mimeType = 'image/gif';
    }

    return [$image, $mimeType];
}


/**
 * Use ImageMagick PHP built-in lib (php extension) to resize an image to the requested size.
 *
 * @param (string) $name      the file basename of the image without size and extension.
 * @param (string) $size      the size of the image we want. One of: [xs, s, m, l, xl, xxl, o].
 * @param (string) $extension the extension of the image we want. One of: [jpeg, jpg, gif, png].
 * @return (array) [$image, $mimeType]
 */
function resizeImage($name, $size, $extension)
{
    $originalImagePath = __DIR__ . "/{$name}_o.$extension";
    $newImagePath      = __DIR__ . "/{$name}_$size.$extension";

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
    $sizes = ['xs'  => 220 * 1.3334,// 1.3334 for retina display (should be x2 but too costy).
              's'   => 300 * 1.3334,
              'm'   => 450 * 1.3334,
              'l'   => 700 * 1.3334,
              'xl'  => 1600,
              'xxl' => 2000,
              'o'   => 2048];


    // Using ImageMagick from the built-in php library.
    if (!USE_IMAGICK_CLI)
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
        $image->resizeImage($width = $sizes[$size], $height = 0, $filter = Imagick::FILTER_CATROM, $blur = 1);
        $image->normalizeImage();

        $image->unsharpMaskImage($radius = 2, $sigma = .5, $amount = .6, $unsharpThreshold = 0);// mine.
        // $image->unsharpMaskImage(0, .5, 1, .5);// from http://php.net/manual/fr/imagick.unsharpmaskimage.php.
        // $image->unsharpMaskImage(0.3, 0, 400, 0);// from http://content.photojojo.com/tutorials/photoshop-sharpening/.

        $image->setImageFormat($extension);
        $image->setCompressionQuality(90);

        // Save to a file.
        $image->writeImage($newImagePath);
    }

    // Using ImageMagick through command line...
    else
    {
        $convertPath = IS_LOCAL ? LOCAL_CONVERT_PATH : 'convert';

        // Convert the image to the $size resized format using Imagemagick lib.
        exec("$convertPath '$originalImagePath' -resize $sizes[$size]x\> -unsharp 2x0.5+0.6+0 -quality 90 '$newImagePath' 2>&1;", $output);

        // $output contains the return (message) of the shell if any, 1 array entry per line returned.
        if (!count($output) || !$output[0]) $image = file_get_contents($newImagePath);
        else dbg(implode("\n", $output));
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $newImagePath);
    finfo_close($finfo);

    return [$image, $mimeType];
}


/**
 * Output the given image to the browser with correct mime type and exit script.
 *
 * @param (resource) $image   the file basename of the image without size and extension.
 * @param (string) $mimeType  the mime type of the image we want to display.
 * @return void.
 */
function outputImage($image, $mimeType)
{
    // HEADERS SET IN images/.htaccess.
    // header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');

    // Set headers to NOT cache a page
    // header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
    // header("Pragma: no-cache"); //HTTP 1.0
    // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

    // Or, if you DO want a file to cache, use:
    // header("Pragma: public");
    // header("Cache-Control: max-age=60, public"); //7 days.

    header("Content-type: $mimeType");
    die("$image");
}

?>