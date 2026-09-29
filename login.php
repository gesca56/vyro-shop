<?php
require __DIR__ . '/vyro-config.php';

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? url('account.php');
if (!str_starts_with($redirect, BASE_URL . '/')) $redirect = url('account.php');
if (current_user()) redirect($redirect);

$email = '';
$error = null;
if (is_post()) {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $u = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        login_user($u);
        flash('success', 'Bon retour, ' . $u['first_name'] . ' 👋');
        redirect($redirect);
    }
    $error = 'Email ou mot de passe incorrect.';
}

$pageTitle = 'Connexion';
require __DIR__ . '/includes/header.php';
?>
<div class="container auth-wrap">
    <div class="panel auth-box">
        <h1>Connexion</h1>
        <p class="muted">Accédez à vos commandes, favoris et adresses.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <label class="field">Email<input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></label>
            <label class="field">Mot de passe<input type="password" name="password" autocomplete="current-password" required></label>
            <button class="btn btn-dark btn-lg btn-block">Se connecter</button>
        </form>
        <p class="center">Pas encore de compte ? <a href="<?= url('register.php?redirect=' . urlencode($redirect)) ?>"><b>Créer un compte</b></a></p>
        <p class="center small muted">Mot de passe oublié ? <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener">Contactez-nous</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
