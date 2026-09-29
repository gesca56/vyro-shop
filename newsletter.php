<?php
require __DIR__ . '/vyro-config.php';

if (!is_post()) redirect('index.php');
csrf_check();

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $ok = false;
    $msg = 'Adresse email invalide.';
} else {
    q('INSERT IGNORE INTO newsletter (email) VALUES (?)', [mb_strtolower($email)]);
    $ok = true;
    $msg = 'C\'est noté ! Tu seras prévenu(e) du prochain drop 🔥';
}
if (is_ajax()) json_response(['ok' => $ok, 'message' => $msg], $ok ? 200 : 422);
flash($ok ? 'success' : 'error', $msg);
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
