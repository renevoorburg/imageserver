<?php declare(strict_types=1);
ini_set("memory_limit","80M");

require "./ImageProcessor.php";

function showErrorPage($code = "404", $message = "page not found")
{
    header("HTTP/1.0 $code");
    echo "<html lang='en'><head><title>$code</title></head><body><h1>Error $code</h1><p>Error $code: $message</p></body></html>";
    exit;
}

$config = json_decode(file_get_contents('../config/imageserver.json'), true);

// parse url:
$uri_parts = explode('/', $_SERVER['DOCUMENT_URI']);
$command = $uri_parts[1] ?? null;
$params = $uri_parts[2] ?? null;
$image = implode('/', array_slice($uri_parts, 3)) ?? null;
$source_image_file = $_SERVER['DOCUMENT_ROOT']."/auto/".$image;

// validate request:
if (!file_exists($source_image_file)) { showErrorPage(); }
if (!in_array($command, ["contain", "cover", "size", "crop"])) { showErrorPage(); }
if (empty($params)) { showErrorPage(); }

// parse width & height params:
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

// validate width & height params:
if ( (isset($requested_width) && ($requested_width < $config['minWidth'] || $requested_width > $config['maxWidth'])) ||
    (isset($requested_height) && ($requested_height < $config['minHeight'] || $requested_height > $config['maxHeight']))) {
    showErrorPage("500" , "server error, image size out of bounds");
}
if (($command !== "size") && (!isset($requested_width) || !isset($requested_height) )) {
    showErrorPage("500" , "server error, image size out of bounds");
}

// process request:
$image_obj = new ImageProcessor($source_image_file);
$image_obj->{$command}($requested_width, $requested_height);
header($image_obj->getHeader());
echo $image_obj->getImageBlob();