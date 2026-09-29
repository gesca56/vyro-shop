<?php
/**
 * VYRO — Mise à jour de la base SANS perte de données (ajoute le Journal / blog).
 * Navigateur : http://localhost/VYRO/upgrade.php   |   CLI : php upgrade.php
 */
require __DIR__ . '/vyro-config.php';
require __DIR__ . '/database/seed_posts.php';

if (PHP_SAPI !== 'cli' && !is_admin()) {
    redirect('admin/login.php');
}

$out = [];
$exists = db()->query("SHOW TABLES LIKE 'posts'")->fetchColumn();
if (!$exists) {
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    preg_match('/CREATE TABLE posts \(.*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', $sql, $m);
    db()->exec($m[0]);
    $out[] = 'Table « posts » créée.';
    $out[] = seed_posts(db()) . ' articles de démonstration ajoutés.';
} else {
    $out[] = 'La table « posts » existe déjà.';
}
if (!db()->query("SHOW TABLES LIKE 'newsletter'")->fetchColumn()) {
    preg_match('/CREATE TABLE newsletter \(.*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', file_get_contents(__DIR__ . '/database/schema.sql'), $m);
    db()->exec($m[0]);
    $out[] = 'Table « newsletter » créée.';
}
if (!db()->query("SHOW COLUMNS FROM orders LIKE 'payment_session'")->fetchColumn()) {
    db()->exec('ALTER TABLE orders ADD payment_session VARCHAR(80) NULL AFTER payment_ref');
    $out[] = 'Colonne orders.payment_session ajoutée (Wave).';
}
$out[] = 'Mise à jour terminée.';

echo PHP_SAPI === 'cli' ? implode(PHP_EOL, $out) . PHP_EOL : '<pre>' . e(implode("\n", $out)) . '</pre><a href="admin/">Administration</a>';
