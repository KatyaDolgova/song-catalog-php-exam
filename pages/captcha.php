<?php
session_start();

// Код капчи хранится в сессии и проверяется в login.php/register.php.
// Символы без похожих друг на друга (0/O, 1/l) — чтобы было легче ввести вручную.
$alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
$code = substr(str_shuffle($alphabet), 0, 6);
$_SESSION['captcha_code'] = $code;

$width = 160;
$height = 50;
$image = imagecreatetruecolor($width, $height);

$background = imagecolorallocate($image, 255, 255, 255);
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Линии-шум, чтобы затруднить автоматическое распознавание
for ($i = 0; $i < 8; $i++) {
    $lineColor = imagecolorallocate($image, rand(160, 210), rand(160, 210), rand(160, 210));
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
}

$textColor = imagecolorallocate($image, 40, 40, 40);
for ($i = 0; $i < strlen($code); $i++) {
    imagechar($image, 5, 12 + $i * 24, rand(8, 20), $code[$i], $textColor);
}

for ($i = 0; $i < 120; $i++) {
    $dotColor = imagecolorallocate($image, rand(170, 220), rand(170, 220), rand(170, 220));
    imagesetpixel($image, rand(0, $width - 1), rand(0, $height - 1), $dotColor);
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

imagepng($image);
imagedestroy($image);
