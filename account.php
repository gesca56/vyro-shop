<?php
require __DIR__ . '/vyro-config.php';

$user = require_login();
$tab = $_GET['tab'] ?? 'commandes';

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $fn = trim($_POST['first_name'] ?? '');
        $ln = trim($_POST['last_name'] ?? '');
        $em = trim($_POST['email'] ?? '');
        $ph = trim($_POST['phone'] ?? '');
        if ($fn === '' || $ln === '' || !filter_var($em, FILTER_VALIDATE_EMAIL) || $ph === '') {
            flash('error', 'Merci de remplir correctement tous les champs.');
        } elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$em, $user['id']])->fetchColumn()) {
            flash('error', 'Cet email est déjà utilisé.');
        } else {
            q('UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?', [$fn, $ln, $em, $ph, $user['id']]);
            flash('success', 'Informations mises à jour.');
        }
        redirect('account.php?tab=infos');
    }

    if ($action === 'password') {
        if (!password_verify($_POST['current'] ?? '', $user['password'])) {
            flash('error', 'Mot de passe actuel incorrect.');
        } elseif (strlen($_POST['new'] ?? '') < 6) {
            flash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
        } else {
            q('UPDATE users SET password = ? WHERE id = ?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Mot de passe modifié.');
        }
        redirect('account.php?tab=infos');
    }

    if ($action === 'address_add') {
        $city = trim($_POST['city'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        if ($city === '' || $addr === '') {
            flash('error', 'Ville et adresse sont requises.');
        } else {
            q('INSERT INTO addresses (user_id,label,country,city,commune,address,phone) VALUES (?,?,?,?,?,?,?)', [
                $user['id'], trim($_POST['label'] ?? '') ?: 'Adresse', trim($_POST['country'] ?? '') ?: 'Côte d\'Ivoire',
                $city, trim($_POST['commune'] ?? ''), $addr, trim($_POST['phone'] ?? ''),
            ]);
            flash('success', 'Adresse ajoutée.');
        }
        redirect('account.php?tab=adresses');
    }

    if ($action === 'address_delete') {
        q('DELETE FROM addresses WHERE id = ? AND user_id = ?', [(int)$_POST['id'], $user['id']]);
        flash('info', 'Adresse supprimée.');
        redirect('account.php?tab=adresses');
    }
}

$orders = q('SELECT o.*, (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) n_items FROM orders o WHERE user_id = ? ORDER BY created_at DESC', [$user['id']])->fetchAll();
$active = array_filter($orders, fn($o) => !in_array($o['status'], ['delivered', 'cancelled'], true));
$past = array_filter($orders, fn($o) => in_array($o['status'], ['delivered', 'cancelled'], true));
$addresses = q('SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC', [$user['id']])->fetchAll();
$favorites = products_query('p.id IN (SELECT product_id FROM favorites WHERE user_id = ?)', [$user['id']], 'p.name');
$flow = ['confirmed', 'preparing', 'shipped', 'out_for_delivery', 'delivered'];

$tabs = ['commandes' => 'Commandes', 'favoris' => 'Favoris (' . count($favorites) . ')', 'adresses' => 'Adresses', 'infos' => 'Informations'];

function order_row(array $o, array $flow): string
{
    $idx = array_search($o['status'], $flow, true);
    $pct = $idx === false ? 0 : ($idx / (count($flow) - 1)) * 100;
    ob_start(); ?>
    <a href="<?= url('track.php?n=' . urlencode($o['number'])) ?>" class="order-row">
        <div>
            <b><?= e($o['number']) ?></b>
            <small><?= time_fr($o['created_at']) ?> · <?= (int)$o['n_items'] ?> article<?= $o['n_items'] > 1 ? 's' : '' ?></small>
        </div>
        <span class="status-pill st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span>
        <b class="order-total"><?= price($o['total']) ?></b>
        <?php if ($o['status'] !== 'cancelled'): ?><div class="mini-progress"><span style="width:<?= $pct ?>%"></span></div><?php endif; ?>
    </a>
    <?php return ob_get_clean();
}

$pageTitle = 'Mon compte';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container account-hero">
        <div>
            <span class="kicker">Mon compte</span>
            <h1>Salut <?= e($user['first_name']) ?> 👋</h1>
            <p><?= count($orders) ?> commande<?= count($orders) > 1 ? 's' : '' ?> · Membre depuis <?= time_fr($user['created_at']) ?></p>
        </div>
        <div class="account-hero-actions">
            <?php if ($user['is_admin']): ?><a href="<?= url('admin/') ?>" class="btn btn-accent btn-sm">Administration</a><?php endif; ?>
            <a href="<?= url('logout.php') ?>" class="btn btn-outline btn-sm">Déconnexion</a>
        </div>
    </div>
</section>

<div class="container">
    <nav class="tabs">
        <?php foreach ($tabs as $k => $label): ?>
            <a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($tab === 'commandes'): ?>
        <h2 class="h3">Suivi des commandes en cours</h2>
        <?php if ($active): ?>
            <div class="order-list"><?php foreach ($active as $o) echo order_row($o, $flow); ?></div>
        <?php else: ?>
            <p class="muted">Aucune commande en cours. <a href="<?= url('shop.php') ?>">Découvrir la boutique →</a></p>
        <?php endif; ?>
        <h2 class="h3">Commandes précédentes</h2>
        <?php if ($past): ?>
            <div class="order-list"><?php foreach ($past as $o) echo order_row($o, $flow); ?></div>
        <?php else: ?>
            <p class="muted">Aucune commande précédente.</p>
        <?php endif; ?>

    <?php elseif ($tab === 'favoris'): ?>
        <?php if ($favorites): ?>
            <div class="product-grid"><?php foreach ($favorites as $p) echo product_card($p); ?></div>
        <?php else: ?>
            <div class="empty-state"><h3>Aucun favori</h3><p>Touchez le ♥ sur un produit pour le retrouver ici.</p><a href="<?= url('shop.php') ?>" class="btn btn-dark">Explorer</a></div>
        <?php endif; ?>

    <?php elseif ($tab === 'adresses'): ?>
        <div class="address-grid">
            <?php foreach ($addresses as $a): ?>
                <div class="panel address-card">
                    <b><?= e($a['label']) ?></b>
                    <p><?= e($a['address']) ?><br><?= e(trim($a['commune'] . ', ' . $a['city'], ', ')) ?><br><?= e($a['country']) ?><?= $a['phone'] ? '<br>' . e($a['phone']) : '' ?></p>
                    <form method="post" onsubmit="return confirm('Supprimer cette adresse ?')">
                        <?= csrf_field() ?><input type="hidden" name="action" value="address_delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button class="link-btn danger">Supprimer</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <form method="post" class="panel form">
                <?= csrf_field() ?><input type="hidden" name="action" value="address_add">
                <h3>Nouvelle adresse</h3>
                <div class="grid-2">
                    <label class="field">Libellé<input name="label" placeholder="Domicile, Bureau…"></label>
                    <label class="field">Téléphone<input name="phone" type="tel"></label>
                    <label class="field">Ville *<input name="city" value="Abidjan" required></label>
                    <label class="field">Commune<input name="commune" list="communes2"></label>
                </div>
                <datalist id="communes2"><?php foreach (communes_abidjan() as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
                <label class="field">Adresse *<input name="address" required></label>
                <input type="hidden" name="country" value="Côte d'Ivoire">
                <button class="btn btn-dark">Ajouter</button>
            </form>
        </div>

    <?php else: ?>
        <div class="grid-2 account-forms">
            <form method="post" class="panel form">
                <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <h3>Informations personnelles</h3>
                <div class="grid-2">
                    <label class="field">Prénom<input name="first_name" value="<?= e($user['first_name']) ?>" required></label>
                    <label class="field">Nom<input name="last_name" value="<?= e($user['last_name']) ?>" required></label>
                </div>
                <label class="field">Email<input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
                <label class="field">Téléphone<input type="tel" name="phone" value="<?= e($user['phone']) ?>" required></label>
                <button class="btn btn-dark">Enregistrer</button>
            </form>
            <form method="post" class="panel form">
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <h3>Mot de passe</h3>
                <label class="field">Mot de passe actuel<input type="password" name="current" required autocomplete="current-password"></label>
                <label class="field">Nouveau mot de passe<input type="password" name="new" minlength="6" required autocomplete="new-password"></label>
                <button class="btn btn-outline">Modifier</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
