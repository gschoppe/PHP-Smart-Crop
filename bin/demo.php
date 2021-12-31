<?php

require(__DIR__ . '/../src/smart_crop.php');

$w = $h = 0;
// allow setting dimensions by query string
if (isset($_GET['w'])) {
    $maybeW = intval($_GET['w']);
    if ($maybeW > 0) {
        $w = $h = $maybeW;
    }
}
if (isset($_GET['h'])) {
    $maybeH = intval($_GET['h']);
    if ($maybeH > 0) {
        $h = $maybeH;
        if ($w == 0)
            $w = $maybeH;
    }
}

// default to 150x150
if ($w == 0)
    $w = 150;
if ($h == 0)
    $h = 150;
// load file to GD2 resource
$img = imagecreatefromjpeg('./tests/testimg.jpg');
// initialize and crop
$cropper = new smart_crop($img);
$img2 = $cropper->get_resized($w, $h);
// output image
header('Content-Type: image/jpeg');
imagejpeg($img2);
// cleanup
imagedestroy($img);
imagedestroy($img2);
