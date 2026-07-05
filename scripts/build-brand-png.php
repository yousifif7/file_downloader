<?php

function createBrandPng(string $path, int $size): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $radius = (int) round($size * 0.22);
    $pad = (int) round($size * 0.0625);

    for ($y = $pad; $y < $size - $pad; $y++) {
        $t = ($y - $pad) / max(1, ($size - $pad * 2 - 1));
        $r = (int) (124 + (217 - 124) * $t);
        $g = (int) (58 + (70 - 58) * $t);
        $b = (int) (237 + (239 - 237) * $t);
        $color = imagecolorallocate($img, $r, $g, $b);

        for ($x = $pad; $x < $size - $pad; $x++) {
            if (! isInsideRoundedRect($x, $y, $pad, $pad, $size - $pad - 1, $size - $pad - 1, $radius)) {
                continue;
            }
            imagesetpixel($img, $x, $y, $color);
        }
    }

    $white = imagecolorallocate($img, 255, 255, 255);
    $cx = (int) ($size / 2);
    $stroke = max(2, (int) round($size * 0.055));

    // Arrow shaft
    imageline($img, $cx, (int) round($size * 0.28), $cx, (int) round($size * 0.56), $white);
    // Arrow head
    imageline($img, $cx, (int) round($size * 0.56), (int) round($size * 0.38), (int) round($size * 0.44), $white);
    imageline($img, $cx, (int) round($size * 0.56), (int) round($size * 0.62), (int) round($size * 0.44), $white);
    // Tray
    imageline($img, (int) round($size * 0.31), (int) round($size * 0.69), (int) round($size * 0.69), (int) round($size * 0.69), $white);

    for ($t = 1; $t < $stroke; $t++) {
        imageline($img, $cx - $t, (int) round($size * 0.28), $cx - $t, (int) round($size * 0.56), $white);
        imageline($img, $cx + $t, (int) round($size * 0.28), $cx + $t, (int) round($size * 0.56), $white);
    }

    imagepng($img, $path, 9);
    imagedestroy($img);
}

function isInsideRoundedRect(int $x, int $y, int $x1, int $y1, int $x2, int $y2, int $r): bool
{
    if ($x < $x1 || $x > $x2 || $y < $y1 || $y > $y2) {
        return false;
    }

    $corners = [
        [$x1 + $r, $y1 + $r],
        [$x2 - $r, $y1 + $r],
        [$x1 + $r, $y2 - $r],
        [$x2 - $r, $y2 - $r],
    ];

    if ($x < $x1 + $r && $y < $y1 + $r) {
        return hypot($x - $corners[0][0], $y - $corners[0][1]) <= $r;
    }
    if ($x > $x2 - $r && $y < $y1 + $r) {
        return hypot($x - $corners[1][0], $y - $corners[1][1]) <= $r;
    }
    if ($x < $x1 + $r && $y > $y2 - $r) {
        return hypot($x - $corners[2][0], $y - $corners[2][1]) <= $r;
    }
    if ($x > $x2 - $r && $y > $y2 - $r) {
        return hypot($x - $corners[3][0], $y - $corners[3][1]) <= $r;
    }

    return true;
}

$base = dirname(__DIR__).'/public/images';
createBrandPng($base.'/favicon.png', 192);
createBrandPng($base.'/logo.png', 256);

echo "done\n";
