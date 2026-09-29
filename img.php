<?php
/**
 * VYRO — Visuels produits générés (SVG) utilisés tant qu'aucune photo n'est uploadée.
 * img.php?v=tee&c=111111[&bg=d]
 */
$v = preg_replace('/[^a-z]/', '', $_GET['v'] ?? 'tee');
$c = preg_match('/^[0-9A-Fa-f]{6}$/', $_GET['c'] ?? '') ? '#' . $_GET['c'] : '#111111';
// Fond noir par défaut (thème du site) ; bg=l pour la version claire
$dark = ($_GET['bg'] ?? 'd') !== 'l';

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=604800');

function shade(string $hex, float $f): string
{
    $hex = ltrim($hex, '#');
    $rgb = array_map('hexdec', str_split($hex, 2));
    foreach ($rgb as &$x) {
        $x = $f < 0 ? $x * (1 + $f) : $x + (255 - $x) * $f;
        $x = max(0, min(255, (int)round($x)));
    }
    return sprintf('#%02x%02x%02x', ...$rgb);
}

$r = hexdec(substr($c, 1, 2)); $g = hexdec(substr($c, 3, 2)); $b = hexdec(substr($c, 5, 2));
$lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
$light = $lum > 0.6;
$bg = $dark ? '#0c0c0c' : '#ECECEA';
$bg2 = $dark ? '#000000' : '#E2E2DF';
$stroke = $light ? shade($c, -0.25) : shade($c, $dark ? 0.25 : -0.35);
$detail = $light ? shade($c, -0.12) : shade($c, 0.12);
$ink = $light ? '#111111' : '#F4F4F2';
$accent = '#C8C8C8'; // détail argenté (charte VYRO monochrome)

$s = 'fill="' . $c . '" stroke="' . $stroke . '" stroke-width="3" stroke-linejoin="round"';
$d = 'fill="none" stroke="' . $stroke . '" stroke-width="3" stroke-linecap="round"';

$shapes = [];
$shapes['tee'] = <<<SVG
<path $s d="M205 170 L258 146 Q300 182 342 146 L395 170 L478 244 L432 306 L396 280 L396 604 L204 604 L204 280 L168 306 L122 244 Z"/>
<path $d d="M258 146 Q300 196 342 146"/>
<path $d d="M204 280 L204 250 M396 280 L396 250" opacity=".5"/>
<text x="300" y="330" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="46" letter-spacing="6" fill="$ink">VYRO</text>
SVG;
$sweatBody = <<<SVG
<path $s d="M204 176 L256 154 Q300 186 344 154 L396 176 L452 250 L482 530 L436 540 L402 310 L402 600 L198 600 L198 310 L164 540 L118 530 L148 250 Z"/>
<path $d d="M256 154 Q300 200 344 154"/>
<rect x="198" y="578" width="204" height="22" fill="$detail" stroke="$stroke" stroke-width="3"/>
<rect x="116" y="520" width="48" height="22" rx="4" fill="$detail" stroke="$stroke" stroke-width="3" transform="rotate(4 140 531)"/>
<rect x="436" y="520" width="48" height="22" rx="4" fill="$detail" stroke="$stroke" stroke-width="3" transform="rotate(-4 460 531)"/>
SVG;
$shapes['sweat'] = $sweatBody . <<<SVG
<text x="300" y="320" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="44" letter-spacing="6" fill="$ink">VYRO</text>
SVG;
$shapes['hoodie'] = <<<SVG
<path fill="$detail" stroke="$stroke" stroke-width="3" d="M236 180 Q226 92 300 82 Q374 92 364 180 Z"/>
$sweatBody
<path $s d="M246 168 Q300 222 354 168 Q352 118 300 112 Q248 118 246 168 Z"/>
<path $d d="M282 205 L278 285 M318 205 L322 285"/>
<path fill="$detail" stroke="$stroke" stroke-width="3" d="M236 450 L364 450 L384 540 L216 540 Z"/>
<text x="300" y="360" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="40" letter-spacing="6" fill="$ink">VYRO</text>
SVG;
$shapes['jacket'] = <<<SVG
$sweatBody
<path fill="$detail" stroke="$stroke" stroke-width="3" d="M250 150 L300 190 L350 150 L360 128 Q300 112 240 128 Z"/>
<path d="M300 190 L300 600" stroke="$ink" stroke-width="4"/>
<path $d d="M225 400 L270 400 M330 400 L375 400"/>
<rect x="318" y="226" width="44" height="12" fill="$accent"/>
SVG;
$pants = <<<SVG
<path $s d="M212 138 L388 138 L404 616 L318 616 L300 300 L282 616 L196 616 Z"/>
<rect x="212" y="138" width="176" height="28" fill="$detail" stroke="$stroke" stroke-width="3"/>
<path $d d="M300 166 L300 250 M236 170 Q250 220 222 240 M364 170 Q350 220 378 240"/>
<rect x="196" y="596" width="86" height="20" fill="$detail" stroke="$stroke" stroke-width="3"/>
<rect x="318" y="596" width="86" height="20" fill="$detail" stroke="$stroke" stroke-width="3"/>
<rect x="208" y="330" width="10" height="120" fill="$accent"/>
SVG;
$shapes['pants'] = $pants;
$shapes['set'] = '<g transform="translate(60 20) scale(.8)">' . $sweatBody .
    '<text x="300" y="320" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="44" letter-spacing="6" fill="' . $ink . '">VYRO</text></g>' .
    '<g transform="translate(250 230) scale(.62)">' . $pants . '</g>';

$sole = $light ? '#1A1A1A' : '#F4F4F2';
$shapes['sneaker'] = <<<SVG
<path fill="$sole" stroke="$stroke" stroke-width="3" d="M96 470 L506 470 Q530 472 524 500 L516 514 L108 514 Q88 508 92 488 Z"/>
<path $s d="M104 470 Q98 400 140 382 L258 350 Q288 298 330 290 L364 290 Q384 332 426 360 Q500 390 508 436 L508 470 Z"/>
<path fill="none" stroke="$ink" stroke-width="12" stroke-linecap="round" d="M176 446 Q316 432 456 380"/>
<path $d d="M300 316 L330 346 M318 304 L350 332 M336 296 L370 322"/>
<path $d d="M104 432 L200 432" opacity=".5"/>
<rect x="470" y="400" width="30" height="16" fill="$accent"/>
SVG;
$shapes['runner'] = <<<SVG
<path fill="$accent" stroke="$stroke" stroke-width="3" d="M90 468 L512 450 Q540 452 530 490 L520 520 L110 528 Q84 520 86 494 Z"/>
<path $s d="M98 468 Q92 410 132 392 L262 356 Q300 300 346 296 L372 300 Q398 342 444 364 Q512 392 512 450 Z"/>
<path fill="none" stroke="$ink" stroke-width="8" stroke-linecap="round" d="M150 440 L260 400 L340 430 L450 380"/>
<path $d d="M306 326 L336 352 M324 314 L356 338 M342 306 L376 330"/>
SVG;
$shapes['lifestyle'] = <<<SVG
<path fill="$sole" stroke="$stroke" stroke-width="3" d="M112 470 L500 470 Q522 472 518 498 L510 514 L122 514 Q104 508 108 488 Z"/>
<path $s d="M120 470 L118 250 Q120 222 150 220 L230 222 Q246 300 290 320 L410 356 Q496 382 502 430 L502 470 Z"/>
<path $d d="M160 240 L230 240 M190 280 L250 300 M205 318 L270 338"/>
<circle cx="170" cy="380" r="30" fill="none" stroke="$ink" stroke-width="8"/>
<rect x="122" y="440" width="380" height="14" fill="$detail"/>
SVG;
$shapes['cap'] = <<<SVG
<path $s d="M166 392 Q162 238 300 230 Q438 238 434 392 Z"/>
<path fill="$detail" stroke="$stroke" stroke-width="3" d="M146 388 Q300 360 530 402 Q480 440 300 424 Q176 418 146 388 Z"/>
<circle cx="300" cy="232" r="10" fill="$detail" stroke="$stroke" stroke-width="3"/>
<path $d d="M300 240 L300 380 M230 250 Q210 320 214 384 M370 250 Q390 320 386 384" opacity=".6"/>
<text x="300" y="330" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="38" letter-spacing="4" fill="$ink">VYRO</text>
SVG;
$shapes['bag'] = <<<SVG
<path fill="none" stroke="$stroke" stroke-width="16" stroke-linecap="round" d="M196 330 Q300 70 404 330"/>
<rect x="160" y="320" width="280" height="240" rx="34" $s/>
<path $d d="M180 380 L420 380"/>
<rect x="386" y="372" width="16" height="30" rx="4" fill="$accent"/>
<text x="300" y="485" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="36" letter-spacing="6" fill="$ink">VYRO</text>
SVG;
$shapes['jewelry'] = <<<SVG
<ellipse cx="300" cy="330" rx="150" ry="190" fill="none" stroke="$c" stroke-width="10" stroke-dasharray="14 8"/>
<ellipse cx="300" cy="330" rx="150" ry="190" fill="none" stroke="$stroke" stroke-width="2" stroke-dasharray="14 8" opacity=".6"/>
<path $s d="M300 510 L338 560 L300 620 L262 560 Z"/>
<text x="300" y="574" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="22" fill="$ink">V</text>
SVG;
$shapes['glasses'] = <<<SVG
<rect x="112" y="310" width="164" height="112" rx="40" $s/>
<rect x="324" y="310" width="164" height="112" rx="40" $s/>
<path fill="none" stroke="$stroke" stroke-width="10" d="M276 346 Q300 326 324 346"/>
<path fill="none" stroke="$stroke" stroke-width="8" d="M112 330 L70 300 M488 330 L530 300"/>
<path fill="none" stroke="#ffffff" stroke-width="6" stroke-linecap="round" opacity=".35" d="M140 340 L180 330 M352 340 L392 330"/>
SVG;

$body = $shapes[$v] ?? $shapes['tee'];
$isShoe = in_array($v, ['sneaker', 'runner', 'lifestyle'], true);
$shadowY = $isShoe ? 530 : 640;
$haloY = $isShoe ? '60%' : '48%';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 750" width="600" height="750">
    <defs>
        <radialGradient id="g" cx="50%" cy="<?= $haloY ?>" r="60%">
            <stop offset="0" stop-color="<?= $bg ?>"/>
            <stop offset="1" stop-color="<?= $bg2 ?>"/>
        </radialGradient>
        <?php if ($dark): ?>
        <!-- Halo lumineux autour du produit (style « studio » sur fond noir) -->
        <radialGradient id="halo" cx="50%" cy="<?= $haloY ?>" r="42%">
            <stop offset="0" stop-color="#ffffff" stop-opacity=".16"/>
            <stop offset=".55" stop-color="#ffffff" stop-opacity=".05"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <filter id="glow" x="-30%" y="-30%" width="160%" height="160%">
            <feFlood flood-color="#ffffff" result="w"/>
            <feComposite in="w" in2="SourceAlpha" operator="in"/>
            <feGaussianBlur stdDeviation="16"/>
        </filter>
        <?php endif; ?>
    </defs>
    <rect width="600" height="750" fill="url(#g)"/>
    <?php if ($dark): ?>
        <rect width="600" height="750" fill="url(#halo)"/>
        <g filter="url(#glow)" opacity=".38"><?= $body ?></g>
    <?php else: ?>
        <text x="300" y="712" text-anchor="middle" font-family="Arial Black,Arial" font-weight="900" font-size="120" letter-spacing="20" fill="#000" opacity=".04">VYRO</text>
        <ellipse cx="300" cy="<?= $shadowY ?>" rx="200" ry="18" fill="#000" opacity=".12"/>
    <?php endif; ?>
    <?= $body ?>
</svg>
