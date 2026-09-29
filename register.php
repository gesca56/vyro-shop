<?php
require __DIR__ . '/vyro-config.php';

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? url('account.php');
if (!str_starts_with($redirect, BASE_URL . '/')) $redirect = url('account.php');
if (current_user()) redirect($redirect);

$f = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];
$errors = [];
if (is_post()) {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($f['first_name'] === '') $errors[] = 'Le prénom est requis.';
    if ($f['last_name'] === '') $errors[] = 'Le nom est requis.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if (!preg_match('/^\+?[\d\s.-]{8,20}$/', $f['phone'])) $errors[] = 'Téléphone invalide.';
    if (strlen($pass) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    if ($pass !== ($_POST['password2'] ?? '')) $errors[] = 'Les mots de passe ne correspondent pas.';
    if (!$errors && q('SELECT 1 FROM users WHERE email = ?', [$f['email']])->fetchColumn()) $errors[] = 'Un compte existe déjà avec cet email.';

    if (!$errors) {
        q('INSERT INTO users (first_name,last_name,email,phone,password) VALUES (?,?,?,?,?)',
            [$f['first_name'], $f['last_name'], $f['email'], $f['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
        login_user(['id' => db()->lastInsertId()]);
        flash('success', 'Bienvenue chez VYRO, ' . $f['first_name'] . ' ! Utilisez WELCOME10 pour -10 % sur votre première commande.');
        redirect($redirect);
    }
}

$pageTitle = 'Créer un compte';
require __DIR__ . '/includes/header.php';
?>
<div class="container auth-wrap">
    <div class="panel auth-box">
        <h1>Créer un compte</h1>
        <p class="muted">Suivez vos commandes, enregistrez vos favoris et commandez plus vite.</p>
        <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <div class="grid-2">
                <label class="field">Prénom<input name="first_name" value="<?= e($f['first_name']) ?>" autocomplete="given-name" required></label>
                <label class="field">Nom<input name="last_name" value="<?= e($f['last_name']) ?>" autocomplete="family-name" required></label>
            </div>
            <label class="field">Email<input type="email" name="email" value="<?= e($f['email']) ?>" autocomplete="email" required></label>
            <label class="field">Téléphone<input type="tel" name="phone" value="<?= e($f['phone']) ?>" placeholder="+225 07 00 00 00 00" autocomplete="tel" required></label>
            <div class="grid-2">
                <label class="field">Mot de passe<input type="password" name="password" minlength="6" autocomplete="new-password" required></label>
                <label class="field">Confirmation<input type="password" name="password2" minlength="6" autocomplete="new-password" required></label>
            </div>
            <button class="btn btn-dark btn-lg btn-block">Créer mon compte</button>
        </form>
        <p class="center">Déjà client ? <a href="<?= url('login.php?redirect=' . urlencode($redirect)) ?>"><b>Se connecter</b></a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
