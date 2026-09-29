<?php
/**
 * VYRO — Configuration générale
 *
 * Pour la mise en ligne, NE MODIFIEZ PAS ce fichier : copiez
 * « config.local.example.php » en « config.local.php » et mettez-y
 * les accès de l'hébergeur et les clés Wave. Ce fichier local est
 * chargé en premier et ses valeurs remplacent celles ci-dessous.
 */
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
function cfg(string $name, $value): void
{
    defined($name) || define($name, $value);
}

// Base de données : variables d'environnement (Render, Docker…), sinon XAMPP par défaut
cfg('DB_HOST', getenv('DB_HOST') ?: 'localhost');
cfg('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
cfg('DB_NAME', getenv('DB_NAME') ?: 'vyro');
cfg('DB_USER', getenv('DB_USER') ?: 'root');
cfg('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
cfg('DB_SSL', getenv('DB_SSL') === '1'); // bases managées (Aiven, TiDB…) : connexion chiffrée

// Derrière un proxy HTTPS (Render, Cloudflare…) : la connexion du visiteur est sécurisée
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// URL de base : détectée automatiquement (« /VYRO » en local, « » à la racine d'un domaine)
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appDir = realpath(__DIR__);
    $base = ($docRoot && $appDir && str_starts_with(strtolower($appDir), strtolower($docRoot)))
        ? str_replace('\\', '/', substr($appDir, strlen($docRoot)))
        : '/VYRO';
    define('BASE_URL', rtrim($base, '/'));
}
cfg('SITE_URL', getenv('SITE_URL') ?: (getenv('RENDER_EXTERNAL_URL') ?: '')); // ex. « https://vyroshop225.com » en production (liens absolus, retours Wave)

// Boutique
cfg('SHOP_NAME', 'VYRO');
cfg('CURRENCY', 'FCFA');
cfg('SHIPPING_FEE', 2000);              // Frais de livraison Abidjan
cfg('SHIPPING_FEE_OTHER', 3500);        // Frais de livraison hors Abidjan
cfg('FREE_SHIPPING_THRESHOLD', 60000);  // Livraison offerte à partir de
cfg('LOW_STOCK_THRESHOLD', 5);

// Contact & réseaux sociaux
cfg('CONTACT_EMAIL', 'contact@vyroshop225.com');
cfg('CONTACT_PHONE', '+225 05 56 77 40 58');
cfg('WHATSAPP_NUMBER', '2250556774058');
cfg('INSTAGRAM_URL', 'https://www.instagram.com/vyroshop225');
cfg('INSTAGRAM_HANDLE', '@vyroshop225');
cfg('TIKTOK_URL', 'https://www.tiktok.com/@vyro.shop0');
cfg('TIKTOK_HANDLE', '@vyro.shop0');
cfg('SNAPCHAT_URL', ''); // vide = masqué sur le site

// Paiement
cfg('PAYMENT_PHONE', '+225 05 56 77 40 58'); // numéro qui reçoit les paiements Mobile Money
cfg('PAYMENT_NAME', 'VYRO');                 // nom affiché au client pour le transfert
// Wave Business — API Checkout (https://docs.wave.com/checkout)
// Laisser vide tant que le compte Wave Business n'est pas activé : le client paie alors
// par transfert Wave vers PAYMENT_PHONE et l'admin valide le paiement.
cfg('WAVE_API_KEY', getenv('WAVE_API_KEY') ?: '');
cfg('WAVE_WEBHOOK_SECRET', getenv('WAVE_WEBHOOK_SECRET') ?: '');
cfg('CARD_PAYMENT_ENABLED', false); // pas de passerelle carte branchée pour l'instant
// Clé secrète du site (signatures des liens de retour). À personnaliser dans config.local.php en production.
cfg('APP_SECRET', getenv('APP_SECRET') ?: hash('sha256', __DIR__ . DB_NAME . DB_PASS . 'vyro'));

// Uploads
cfg('UPLOAD_DIR', __DIR__ . '/uploads/products/');
define('UPLOAD_URL', BASE_URL . '/uploads/products/');

date_default_timezone_set('Africa/Abidjan');

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => (BASE_URL ?: '') . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/wave.php';
