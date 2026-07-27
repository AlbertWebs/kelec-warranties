<?php

$path = dirname(__DIR__).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo.png';
$src = imagecreatefrompng($path);
if (! $src) {
    fwrite(STDERR, "Failed to load logo at {$path}\n");
    exit(1);
}

$w = imagesx($src);
$h = imagesy($src);

$dst = imagecreatetruecolor($w, $h);
imagealphablending($dst, false);
imagesavealpha($dst, true);

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        if ($r < 40 && $g < 40 && $b < 40) {
            $color = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        } else {
            $color = imagecolorallocatealpha($dst, $r, $g, $b, 0);
        }

        imagesetpixel($dst, $x, $y, $color);
    }
}

imagepng($dst, $path);
imagedestroy($src);
imagedestroy($dst);

echo "Transparent logo saved ({$w}x{$h})\n";
