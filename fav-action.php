<?php
require __DIR__ . '/vyro-config.php';

if (!is_post()) redirect('account.php?tab=favoris');
csrf_check();

$user = current_user();
if (!$user) {
    if (is_ajax()) json_response(['ok' => false, 'login' => url('login.php'), 'message' => 'Connectez-vous pour enregistrer vos favoris.'], 401);
    redirect('login.php');
}

$pid = (int)($_POST['product_id'] ?? 0);
$exists = q('SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?', [$user['id'], $pid])->fetchColumn();
if ($exists) {
    q('DELETE FROM favorites WHERE user_id = ? AND product_id = ?', [$user['id'], $pid]);
} elseif (q('SELECT 1 FROM products WHERE id = ?', [$pid])->fetchColumn()) {
    q('INSERT INTO favorites (user_id, product_id) VALUES (?, ?)', [$user['id'], $pid]);
}

if (is_ajax()) {
    json_response(['ok' => true, 'active' => !$exists, 'message' => $exists ? 'Retiré des favoris' : 'Ajouté aux favoris ♥']);
}
redirect($_SERVER['HTTP_REFERER'] ?? 'account.php?tab=favoris');
