<?php declare(strict_types=1);

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);
//
//echo "aaa";
//phpinfo();
//
//exit;

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
$requested_image_path = implode('/', array_slice($uri_parts, 3)) ?? null;
$source_image_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $config['sourceDir'] . '/' . $requested_image_path;

// validate request:
if (!file_exists($source_image_path)) { showErrorPage(); }
if (!in_array($command, ["contain", "cover", "size", "crop"])) { showErrorPage(); }
if (empty($params)) { showErrorPage(); }

// parse /validate width & height params:
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

// validate range of width & height params:
if ( (isset($requested_width) && ($requested_width < $config['minWidth'] || $requested_width > $config['maxWidth'])) ||
    (isset($requested_height) && ($requested_height < $config['minHeight'] || $requested_height > $config['maxHeight']))) {
    showErrorPage("500" , "server error, image size out of bounds");
}
if (($command !== "size") && (!isset($requested_width) || !isset($requested_height) )) {
    showErrorPage("500" , "server error, image size out of bounds");
}
$requested_width = isset($requested_width) ? (int) $requested_width : null;
$requested_height = isset($requested_height) ? (int) $requested_height: null;

// process image according request:
$image_obj = new ImageProcessor($source_image_path);
$image_obj->{$command}($requested_width, $requested_height);
header($image_obj->getHeader());
echo $image_obj->getImageBlob();

// is this a cacheable image?:
if (preg_match($config['cachePattern'], $command . '/' . $params)) {
    // cache image:
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $command . '/' . $params . '/'. dirname($requested_image_path);
    if (!file_exists($dir)) { @mkdir($dir, 0777, true); }
    file_put_contents($dir . '/' . basename($requested_image_path), $image_obj->getImageBlob());
}