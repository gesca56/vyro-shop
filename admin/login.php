<?php
require __DIR__ . '/../vyro-config.php';

if (is_admin()) redirect('admin/');

/* Protection contre les essais de mots de passe : 8 échecs max par adresse IP sur 15 minutes */
const LOGIN_MAX_FAILS = 8;
const LOGIN_WINDOW = 900;
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '?');
$ip = trim(explode(',', $ip)[0]);
$throttleFile = sys_get_temp_dir() . '/vyro-admin-login.json';
$fails = is_file($throttleFile) ? (json_decode((string)file_get_contents($throttleFile), true) ?: []) : [];
$fails = array_filter($fails, fn($t) => is_array($t) && $t && max($t) > time() - LOGIN_WINDOW);
$myFails = array_filter($fails[$ip] ?? [], fn($t) => $t > time() - LOGIN_WINDOW);
$blocked = count($myFails) >= LOGIN_MAX_FAILS;

$error = null;
$login = '';
if (is_post()) {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    if ($blocked) {
        $error = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } else {
        // Connexion par nom d'utilisateur ou par email
        $u = q('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_admin = 1 LIMIT 1', [$login, $login])->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            unset($fails[$ip]);
            @file_put_contents($throttleFile, json_encode($fails), LOCK_EX);
            login_user($u);
            redirect('admin/');
        }
        $fails[$ip][] = time();
        @file_put_contents($throttleFile, json_encode($fails), LOCK_EX);
        usleep(400000); // ralentit les essais automatisés
        $error = 'Identifiants incorrects ou accès non autorisé.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Admin VYRO</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="<?= url('assets/img/favicon.png') ?>" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-login">
    <form method="post" class="panel form auth-box">
        <?= csrf_field() ?>
        <span class="logo"><img src="<?= url('assets/img/logo-dark.png') ?>" alt="VYRO" width="480" height="339"></span>
        <h1>Administration</h1>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <label class="field">Nom d'utilisateur ou email<input type="text" name="login" value="<?= e($login) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus></label>
        <label class="field">Mot de passe<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="btn btn-dark btn-lg btn-block">Se connecter</button>
        <a href="<?= url() ?>" class="center small muted">← Retour à la boutique</a>
    </form>
</body>
</html>
