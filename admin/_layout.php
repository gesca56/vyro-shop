<?php
require_once __DIR__ . '/../vyro-config.php';

function admin_header(string $title, string $active = ''): void
{
    $admin = require_admin();
    expire_unpaid_orders();
    $pending = (int)q("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed','preparing')")->fetchColumn();
    $unread = (int)q('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    $low = (int)q('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= ?', [LOW_STOCK_THRESHOLD])->fetchColumn();
    $menu = [
        'dashboard' => ['index.php', 'Tableau de bord', ''],
        'orders' => ['orders.php', 'Commandes', $pending],
        'products' => ['products.php', 'Produits', $low ? '!' : ''],
        'categories' => ['categories.php', 'Catégories & collections', ''],
        'posts' => ['posts.php', 'Journal', ''],
        'customers' => ['customers.php', 'Clients', ''],
        'promos' => ['promos.php', 'Promotions', ''],
        'reviews' => ['reviews.php', 'Avis', ''],
        'messages' => ['messages.php', 'Messages', $unread],
        'newsletter' => ['newsletter.php', 'Newsletter', ''],
    ];
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — Admin VYRO</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="<?= url('assets/img/favicon.png') ?>" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<div class="admin-backdrop" id="adminBackdrop"></div>
<aside class="admin-side" id="adminSide">
    <a href="<?= url('admin/') ?>" class="logo admin-logo"><img src="<?= url('assets/img/logo-light.png') ?>" alt="VYRO" width="480" height="339"> <small>ADMIN</small></a>
    <nav>
        <?php foreach ($menu as $key => [$href, $label, $badge]): ?>
            <a href="<?= url('admin/' . $href) ?>" class="<?= $active === $key ? 'active' : '' ?>">
                <?= e($label) ?><?php if ($badge): ?><span class="nav-badge"><?= e($badge) ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="admin-side-foot">
        <a href="<?= url() ?>" target="_blank">↗ Voir la boutique</a>
        <a href="<?= url('logout.php') ?>">Déconnexion (<?= e($admin['first_name']) ?>)</a>
    </div>
</aside>
<div class="admin-main">
    <header class="admin-top">
        <button class="icon-btn" id="adminMenu" aria-label="Menu"><svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
        <h1><?= e($title) ?></h1>
    </header>
    <div class="toast-zone" id="toastZone">
        <?php foreach (get_flashes() as $f): ?>
            <div class="toast toast-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>
    </div>
    <div class="admin-content">
    <?php
}

function admin_footer(): void
{
    ?>
    </div>
</div>
<script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
    <?php
}
