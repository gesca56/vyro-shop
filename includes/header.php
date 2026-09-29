<?php
$pageTitle = isset($pageTitle) ? $pageTitle . ' — ' . SHOP_NAME : SHOP_NAME . ' — Streetwear × Lifestyle × You';
$pageDesc = $pageDesc ?? 'VYRO, boutique streetwear premium en Côte d\'Ivoire. Vêtements, sneakers et accessoires. Livraison rapide, paiement Mobile Money sécurisé.';
$current = basename($_SERVER['SCRIPT_NAME']);
$navCat = $_GET['cat'] ?? '';
$navFilter = $_GET['filter'] ?? '';
$fp = featured_promo();
$tree = categories_tree();
$navCollections = q('SELECT name, slug FROM collections ORDER BY sort_order, name')->fetchAll();
// Catégorie principale active (aussi quand on est sur une sous-catégorie)
$navTop = $navCat;
foreach ($tree as $t) {
    foreach ($t['children'] as $ch) {
        if ($ch['slug'] === $navCat) $navTop = $t['slug'];
    }
}
$isShop = $current === 'shop.php';
$logoImg = '<img src="' . url('assets/img/logo-light.png') . '" srcset="' . url('assets/img/logo-light.png') . ' 1x, ' . url('assets/img/logo-light@2x.png') . ' 2x" alt="VYRO" width="480" height="339">';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <script>document.documentElement.classList.add('js');if('onpagereveal' in window)document.documentElement.classList.add('vt');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="theme-color" content="#000000">
    <meta name="color-scheme" content="dark">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= url('assets/img/vyro-poster.png') ?>">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <link rel="icon" href="<?= url('assets/img/favicon.png') ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= url('assets/img/apple-touch-icon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/motion.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/pages.css') ?>">
</head>
<body class="page-<?= e(pathinfo($current, PATHINFO_FILENAME)) ?>">

<!-- Transition entre les pages -->
<div class="page-curtain" id="pageCurtain" aria-hidden="true">
    <img src="<?= url('assets/img/crown-light.png') ?>" alt="" width="256" height="200">
    <span>VYRO</span>
    <i></i>
</div>

<div class="promo-bar" id="promoBar">
    <div class="promo-rotator" aria-live="polite">
        <span class="on">Livraison offerte dès <?= price(FREE_SHIPPING_THRESHOLD) ?> — Abidjan 24–48h</span>
        <?php if ($fp): ?><span><?= e($fp['label']) ?> · <?= e($fp['description']) ?> · Code <b><?= e($fp['code']) ?></b></span><?php endif; ?>
        <span>Paiement 100 % Wave · Rapide et sécurisé</span>
        <span>Nouveau drop disponible</span>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="header-inner">
        <div class="header-left">
            <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Menu">
                <svg viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
            </button>
            <nav class="desk-nav" id="deskNav" aria-label="Navigation principale">
                <div class="desk-item has-mega">
                    <a href="<?= url('shop.php') ?>" class="<?= $isShop ? 'active' : '' ?>">Shop <svg viewBox="0 0 24 24" class="chev"><path d="M6 9l6 6 6-6"/></svg></a>
                    <div class="mega">
                        <div class="mega-inner">
                            <?php foreach ($tree as $navItem): ?>
                                <div class="mega-col">
                                    <a href="<?= url('shop.php?cat=' . $navItem['slug']) ?>" class="mega-title <?= $navTop === $navItem['slug'] ? 'active' : '' ?>"><?= e($navItem['name']) ?></a>
                                    <?php foreach ($navItem['children'] as $ch): ?>
                                        <a href="<?= url('shop.php?cat=' . $ch['slug']) ?>" class="<?= $navCat === $ch['slug'] ? 'active' : '' ?>"><?= e($ch['name']) ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="mega-col">
                                <span class="mega-title">Collections</span>
                                <?php foreach ($navCollections as $c): ?>
                                    <a href="<?= url('shop.php?collection=' . $c['slug']) ?>"><?= e($c['name']) ?></a>
                                <?php endforeach; ?>
                                <a href="<?= url('shop.php?sort=best') ?>">Best-sellers</a>
                                <a href="<?= url('avis.php') ?>">Avis clients</a>
                                <a href="<?= url('shop.php?filter=promo') ?>" class="nav-promo">Promotions</a>
                                <a href="<?= url('shop.php') ?>" class="mega-all">Tout voir →</a>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="<?= url('shop.php?filter=new') ?>" class="<?= $navFilter === 'new' ? 'active' : '' ?>">Nouveautés</a>
                <a href="<?= url('blog.php') ?>" class="<?= in_array($current, ['blog.php', 'article.php'], true) ? 'active' : '' ?>">Journal</a>
                <a href="<?= url('collections.php') ?>" class="<?= $current === 'collections.php' ? 'active' : '' ?>">Collections</a>
            </nav>
        </div>

        <a href="<?= url() ?>" class="logo" aria-label="VYRO — accueil"><?= $logoImg ?></a>

        <div class="header-actions">
            <a href="<?= url('account.php') ?>" class="icon-btn hide-xs" aria-label="Compte">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3.2"/><path d="M6.5 18.6c1.2-2 3.2-3.1 5.5-3.1s4.3 1.1 5.5 3.1"/></svg>
            </a>
            <button class="icon-btn" id="searchToggle" aria-label="Recherche">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            </button>
            <a href="<?= url('cart.php') ?>" class="icon-btn cart-btn" aria-label="Panier">
                <svg viewBox="0 0 24 24"><path d="M2.5 3h2l2.2 11.2a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.5L21 7H6"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/></svg>
                <span class="cart-count" id="cartCount" <?= cart_count() ? '' : 'hidden' ?>><?= cart_count() ?></span>
            </a>
        </div>
    </div>

    <div class="search-panel" id="searchPanel">
        <form action="<?= url('shop.php') ?>" method="get" class="container search-form">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <input type="search" name="q" placeholder="Rechercher un produit, une collection…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            <button type="button" class="icon-btn" id="searchClose" aria-label="Fermer">
                <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </form>
        <div class="container search-tags">
            <span>Populaire</span>
            <a href="<?= url('shop.php?q=hoodie') ?>">Hoodie</a>
            <a href="<?= url('shop.php?q=cargo') ?>">Cargo</a>
            <a href="<?= url('shop.php?cat=sneakers') ?>">Sneakers</a>
            <a href="<?= url('shop.php?q=tee') ?>">Tee</a>
        </div>
    </div>
</header>

<!-- Menu tiroir (mobile / tablette) -->
<nav class="main-nav" id="mainNav" aria-label="Menu">
    <div class="nav-head">
        <span class="logo"><?= $logoImg ?></span>
        <button class="icon-btn" id="menuClose" aria-label="Fermer">
            <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <a href="<?= url('shop.php') ?>" class="<?= $isShop && !$navCat && !$navFilter ? 'active' : '' ?>">Shop — tout voir</a>
    <?php foreach ($tree as $navItem): ?>
        <div class="nav-item has-sub">
            <div class="nav-row">
                <a href="<?= url('shop.php?cat=' . $navItem['slug']) ?>" class="<?= $navTop === $navItem['slug'] ? 'active' : '' ?>"><?= e($navItem['name']) ?></a>
                <button type="button" class="sub-toggle" aria-expanded="false" aria-label="Afficher les sous-catégories <?= e($navItem['name']) ?>">
                    <svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
            <div class="subnav">
                <div class="subnav-inner">
                    <?php foreach ($navItem['children'] as $ch): ?>
                        <a href="<?= url('shop.php?cat=' . $ch['slug']) ?>" class="<?= $navCat === $ch['slug'] ? 'active' : '' ?>"><?= e($ch['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <a href="<?= url('shop.php?filter=new') ?>" class="<?= $navFilter === 'new' ? 'active' : '' ?>">Nouveautés</a>
    <a href="<?= url('shop.php?sort=best') ?>">Best-sellers</a>
    <a href="<?= url('collections.php') ?>" class="<?= $current === 'collections.php' ? 'active' : '' ?>">Collections</a>
    <a href="<?= url('shop.php?filter=promo') ?>" class="nav-promo <?= $navFilter === 'promo' ? 'active' : '' ?>">Promotions</a>
    <a href="<?= url('blog.php') ?>" class="<?= in_array($current, ['blog.php', 'article.php'], true) ? 'active' : '' ?>">Journal</a>
    <a href="<?= url('avis.php') ?>" class="<?= $current === 'avis.php' ? 'active' : '' ?>">Avis clients</a>
    <a href="<?= url('communaute.php') ?>" class="<?= $current === 'communaute.php' ? 'active' : '' ?>">Communauté</a>
    <div class="nav-extra">
        <a href="<?= url('account.php') ?>">Mon compte</a>
        <a href="<?= url('track.php') ?>">Suivre ma commande</a>
        <a href="<?= url('faq.php') ?>">FAQ</a>
        <a href="<?= url('contact.php') ?>">Contact</a>
    </div>
    <p class="nav-tagline">Good drip · Better days</p>
</nav>
<div class="overlay" id="overlay"></div>

<div class="toast-zone" id="toastZone">
    <?php foreach (get_flashes() as $f): ?>
        <div class="toast toast-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
</div>

<main id="main">
