<?php
/**
 * VYRO — Génère les logos transparents à partir de assets/img/vyro-poster.png
 * (logo argenté sur fond noir). L'opacité est calculée d'après la luminosité.
 * Usage : php database/make_logo.php
 */
$src = imagecreatefrompng(__DIR__ . '/../assets/img/vyro-poster.png');
$out = __DIR__ . '/../assets/img/';

/**
 * @param array $box  [x, y, largeur, hauteur] dans l'image source
 * @param string $mode 'light' = argent (fonds sombres), 'dark' = noir (fonds clairs)
 */
function extract_logo($src, array $box, int $targetW, string $mode): GdImage
{
    [$bx, $by, $bw, $bh] = $box;
    $targetH = (int)round($targetW * $bh / $bw);
    $crop = imagecreatetruecolor($bw, $bh);
    imagecopy($crop, $src, 0, 0, $bx, $by, $bw, $bh);
    $img = imagecreatetruecolor($targetW, $targetH);
    imagecopyresampled($img, $crop, 0, 0, 0, 0, $targetW, $targetH, $bw, $bh);

    $res = imagecreatetruecolor($targetW, $targetH);
    imagealphablending($res, false);
    imagesavealpha($res, true);
    $lo = 28;   // en dessous : fond (transparent)
    $hi = 150;  // au-dessus : opaque
    for ($y = 0; $y < $targetH; $y++) {
        for ($x = 0; $x < $targetW; $x++) {
            $c = imagecolorat($img, $x, $y);
            $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;
            $l = 0.299 * $r + 0.587 * $g + 0.114 * $b;
            $a = max(0, min(1, ($l - $lo) / ($hi - $lo)));        // 0 = transparent, 1 = opaque
            $gdAlpha = (int)round(127 - $a * 127);                  // GD : 0 opaque, 127 transparent
            if ($mode === 'light') {
                // Garde la texture métallique, éclaircie
                $v = (int)min(255, 90 + $l * 0.75);
                $col = imagecolorallocatealpha($res, $v, $v, $v, $gdAlpha);
            } else {
                $v = (int)max(0, 40 - $l * 0.15);
                $col = imagecolorallocatealpha($res, $v, $v, $v, $gdAlpha);
            }
            imagesetpixel($res, $x, $y, $col);
        }
    }
    return $res;
}

$full = [135, 175, 990, 700];   // couronne + VYRO + anneau + coulures
$tag = [135, 175, 990, 790];    // + « STREETWEAR × LIFESTYLE × YOU »

foreach (['light', 'dark'] as $mode) {
    imagepng(extract_logo($src, $full, 480, $mode), $out . "logo-$mode.png", 9);
    imagepng(extract_logo($src, $full, 1200, $mode), $out . "logo-$mode@2x.png", 9);
}
imagepng(extract_logo($src, $tag, 1400, 'light'), $out . 'logo-tagline-light.png', 9);

// Favicon / icône : couronne seule
$crown = [470, 180, 320, 250];
imagepng(extract_logo($src, $crown, 256, 'light'), $out . 'crown-light.png', 9);

echo "Logos générés dans assets/img/\n";

// Favicon & icône iOS : couronne argentée sur fond noir
$crownImg = imagecreatefrompng($out . 'crown-light.png');
foreach (['favicon.png' => 64, 'apple-touch-icon.png' => 180] as $file => $size) {
    $ico = imagecreatetruecolor($size, $size);
    imagefill($ico, 0, 0, imagecolorallocate($ico, 0, 0, 0));
    imagealphablending($ico, true);
    $w = (int)round($size * 0.8);
    $h = (int)round($w * imagesy($crownImg) / imagesx($crownImg));
    imagecopyresampled($ico, $crownImg, (int)(($size - $w) / 2), (int)(($size - $h) / 2), 0, 0, $w, $h, imagesx($crownImg), imagesy($crownImg));
    imagepng($ico, $out . $file, 9);
}
echo "Favicons générés.\n";
