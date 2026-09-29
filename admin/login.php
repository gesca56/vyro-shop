<?php
require __DIR__ . '/../vyro-config.php';

if (is_admin()) redirect('admin/');
$error = null;
if (is_post()) {
    csrf_check();
    $u = q('SELECT * FROM users WHERE email = ? AND is_admin = 1', [trim($_POST['email'] ?? '')])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        login_user($u);
        redirect('admin/');
    }
    $error = 'Identifiants incorrects ou accès non autorisé.';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Admin VYRO</title>
    <meta name="robots" content="noindex">
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
        <label class="field">Email<input type="email" name="email" required autofocus></label>
        <label class="field">Mot de passe<input type="password" name="password" required></label>
        <button class="btn btn-dark btn-lg btn-block">Se connecter</button>
        <a href="<?= url() ?>" class="center small muted">← Retour à la boutique</a>
    </form>
</body>
</html>
