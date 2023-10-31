<?php declare(strict_types=1);
ini_set("memory_limit","80M");

/*
 /contain/w175xh200/ 	crop 0 keep 1 contains	> 0
/crop/w175xh200/ 	crop 1 keep 1 size	> 2
/size/w175/		crop 0 keep 1 size	> 3
/size/h175/		crop 0 keep 1 size	> 4
/size/w175xh200/   	crop 0 keep 0 size  	> 5
/cover/w175xh200/ 	crop 0 keep 1 cover	> 6
 */


function showErrorPage($code = "404", $message = "page not found")
{
    header("HTTP/1.0 $code");
    echo "<html lang='en'><head><title>$code</title></head><body><h1>Error $code</h1><p>Error $code: $message</p></body></html>";
    exit;
}

function autoRotateImage(Imagick $image)
{
    switch($image->getImageOrientation()) {
        case imagick::ORIENTATION_BOTTOMRIGHT:
            $image->rotateimage("#000", 180); // rotate 180 degrees
            break;
        case imagick::ORIENTATION_RIGHTTOP:
            $image->rotateimage("#000", 90); // rotate 90 degrees CW
            break;
        case imagick::ORIENTATION_LEFTBOTTOM:
            $image->rotateimage("#000", -90); // rotate 90 degrees CCW
            break;
    }
    // ensure the EXIF data is correct:
    $image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
}

$config = json_decode(file_get_contents('../config/imageserver.json'), true);

$uri_parts = explode('/', $_SERVER['DOCUMENT_URI']);
$command = $uri_parts[1] ?? null;
$params = $uri_parts[2] ?? null;
$image = implode('/', array_slice($uri_parts, 3)) ?? null;
$source_image_file = $_SERVER['DOCUMENT_ROOT']."/auto/".$image;

if (!file_exists($source_image_file)) { showErrorPage(); }
if (!in_array($command, ["contain", "cover", "size", "crop"])) { showErrorPage(); }
if (empty($params)) { showErrorPage(); }

preg_match('/^w(\d+)$|^h(\d+)$|^(w(\d+)xh(\d+))$/', $params, $matches);

if (!empty($matches[1])) {
    $requested_width = $matches[1];
} elseif (!empty($matches[2])) {
    $requested_height = $matches[2];
} elseif (!empty($matches[4]) && !empty($matches[5])) {
    $requested_width = $matches[4];
    $requested_height = $matches[5];
} else {
    showErrorPage();
}

if ( (isset($requested_width) && ($requested_width < $config['minWidth'] || $requested_width > $config['maxWidth'])) ||
    (isset($requested_height) && ($requested_height < $config['minHeight'] || $requested_height > $config['maxHeight']))) {
    showErrorPage("500" , "server error, image size out of bounds");
}
if (($command !== "size") && (!isset($requested_width) || !isset($requested_height) )) {
    showErrorPage("500" , "server error, image size out of bounds");
}

$image_obj = new Imagick($source_image_file);
autoRotateImage($image_obj);
$source_width  = $image_obj->getImageWidth();
$source_height = $image_obj->getImageHeight();

$do_cache_result = preg_match('@/size/w6/@', '/' . $command . '/' . $params . '/');

switch ($command) {
    case "contain":
        // 0 -preserve aspect ratio, fit image in contains of specified box:
        $new_width = $requested_width ;
        $new_height = $source_height * $new_width / $source_width;
        if ($new_height > $requested_height) {
            $new_height = $requested_height;
            $new_width = $source_width * $new_height / $source_height;
        }
        break;
    case "cover":
        // 6 -preserve aspect ratio, fit image to fully cover specified box:
        $factor_height = $requested_height / $source_height;
        $factor_width = $requested_width / $source_width;
        $factor = ($factor_height < $factor_width) ? $factor_width : $factor_height;
        $new_width = (int) ($source_width * $factor);
        $new_height = (int) ($source_height * $factor);
        break;
    case "crop":
        // 2 -preserve aspect ratio, fit image to specified box using cropping:
        $requested_aspect_rate = $requested_width / $requested_height;
        $source_aspect_rate = $source_width / $source_height;
        if ($requested_aspect_rate > $source_aspect_rate) {
            $trim = $source_height - ($source_width / $requested_aspect_rate);
            $image_obj->cropImage((int)$source_width, (int)($source_height - $trim), 0, (int)($trim/2));
        } else {
            $trim = $source_width - ($source_height * $requested_aspect_rate);
            $image_obj->cropImage((int)($source_width - $trim), (int)$source_height, (int)($trim/2), 0);
        }
        $new_width = $requested_width;
        $new_height = $requested_height;
        break;
    case "size":
        if (isset($requested_width) && !isset($requested_height)) {
            //  3 -preserve aspect ratio, fit width to specified box:
            $new_width = $requested_width;
            $new_height = $source_height * $requested_width / $source_width;
        } elseif (isset($requested_height) && !isset($requested_width)) {
            // 4 -preserve aspect ratio, fit height to specified box:
            $new_height = $requested_height;
            $new_width = $source_width * $requested_height / $source_height;
        } else {
            // 5 - fit to specified box, regardless the aspect ratio:
            $new_width = $requested_width;
            $new_height = $requested_height;
        }
        break;
    default:
        showErrorPage();
}

$image_obj->resizeImage((int)$new_width, (int)$new_height, imagick::FILTER_LANCZOS, 1);

switch ($image_obj->getImageFormat()) {
    case 'JPEG':
        header('Content-Type: image/jpeg');
        break;
    case 'PNG':
        header('Content-Type: image/png');
        break;
    case 'GIF':
        header('Content-Type: image/gif');
        break;
}

echo $image_obj->getImageBlob();