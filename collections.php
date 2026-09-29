<?php
require __DIR__ . '/vyro-config.php';

$collections = q('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.is_active = 1) n
                  FROM collections c ORDER BY sort_order, name')->fetchAll();

$pageTitle = 'Collections';
$pageDesc = 'Les collections VYRO : New Drop, Essentials, Street Collection, Summer Collection.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb"><a href="<?= url() ?>">Accueil</a> / <span>Collections</span></nav>
        <h1 class="vt-title">Collections</h1>
        <p><?= count($collections) ?> univers, une même attitude. Choisis le tien.</p>
    </div>
</section>

<div class="container collections-page">
    <?php foreach ($collections as $i => $c):
        [$vis, $col] = listing_visual($c['slug']) ?? ['tee', 'Noir'];
        $items = products_query('p.collection_id = ?', [$c['id']], 'p.is_new DESC, p.sales_count DESC', 4); ?>
        <section class="collection-block reveal" id="<?= e($c['slug']) ?>">
            <a href="<?= url('shop.php?collection=' . $c['slug']) ?>" class="collection-banner <?= $i % 2 ? 'flip' : '' ?>" data-vt>
                <div class="collection-banner-media"><img src="<?= visual_url($vis, $col) ?>" alt="" loading="<?= $i ? 'lazy' : 'eager' ?>"></div>
                <div class="collection-banner-body">
                    <span class="kicker">Collection · <?= (int)$c['n'] ?> pièces</span>
                    <h3><?= e($c['name']) ?></h3>
                    <p><?= e($c['tagline']) ?></p>
                    <span class="btn btn-outline">Voir la collection</span>
                </div>
            </a>
            <?php if ($items): ?>
                <div class="product-grid scroll-x">
                    <?php foreach ($items as $p) echo product_card($p); ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
