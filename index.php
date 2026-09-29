<?php
require __DIR__ . '/vyro-config.php';

$newProducts = products_query('p.is_new = 1', [], 'p.created_at DESC', 4);
$homeTree = [];
foreach (categories_tree() as $c) $homeTree[$c['slug']] = $c;
$stats = q('SELECT COUNT(*) n, AVG(rating) a FROM reviews WHERE approved = 1')->fetch();
$happy = 2500 + (int)q("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
$totalSold = (int)q('SELECT SUM(sales_count) FROM products WHERE is_active = 1')->fetchColumn();
$nbCollections = (int)q('SELECT COUNT(*) FROM collections')->fetchColumn();
$fp = featured_promo();

$icoGlobe = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/></svg>';
$icoCrown = '<svg viewBox="0 0 24 24"><path d="M3 8l4.5 4L12 5l4.5 7L21 8l-2 10H5L3 8z"/></svg>';
$icoStar = '<svg viewBox="0 0 24 24"><path d="M12 2c.6 5.6 4.4 9.4 10 10-5.6.6-9.4 4.4-10 10-.6-5.6-4.4-9.4-10-10 5.6-.6 9.4-4.4 10-10z" fill="currentColor" stroke="none"/></svg>';

require __DIR__ . '/includes/header.php';
?>

<!-- HERO — inspiré de l'affiche VYRO -->
<section class="hero-drop">
    <div class="hero-spot" aria-hidden="true"></div>
    <div class="hero-grain" aria-hidden="true"></div>
    <div class="hero-sparks" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>

    <div class="hero-corner tl">
        <b>VYRO</b><span>Clothing</span><span>Sneakers</span><span>Accessories</span><hr>
    </div>
    <div class="hero-corner tr">
        <span>More</span><span>than just</span><span>fashion</span><hr><?= $icoGlobe ?>
    </div>

    <div class="hero-center">
        <span class="hero-kicker"><span class="dot"></span> Collection disponible</span>
        <h1 class="hero-title"><span>Nouveau</span><span class="outline">drop</span></h1>
        <p class="hero-tagline">Streetwear <i>×</i> Lifestyle <i>×</i> You</p>
        <div class="hero-cta">
            <a href="<?= url('shop.php?collection=new-drop') ?>" class="btn btn-light btn-lg">Nouveau drop</a>
            <a href="<?= url('shop.php') ?>" class="btn btn-ghost-light btn-lg">Shop</a>
        </div>
        <div class="hero-icons" aria-hidden="true"><?= $icoGlobe ?><span></span><?= $icoCrown ?><span></span><?= $icoStar ?></div>
    </div>

    <div class="hero-corner bl hero-marker" aria-hidden="true">
        <?= $icoCrown ?><span>Good<br>drip<br>better<br>days</span>
    </div>
    <a href="#drop" class="hero-scroll" aria-label="Voir le nouveau drop"><span>Scroll</span><i></i></a>
</section>

<!-- PROMO -->
<?php if ($fp): ?>
<section class="promo-banner">
    <div class="container promo-inner">
        <div>
            <span class="promo-kicker"><?= e($fp['label']) ?></span>
            <h2><?= e($fp['description']) ?></h2>
            <?php if ($fp['ends_at']): ?><p class="countdown" data-end="<?= e($fp['ends_at']) ?> 23:59:59"></p><?php endif; ?>
        </div>
        <button class="code-chip" data-copy="<?= e($fp['code']) ?>">Code <b><?= e($fp['code']) ?></b> <small>Copier</small></button>
    </div>
</section>
<?php endif; ?>

<!-- NOUVEAU DROP (aperçu) -->
<section class="section" id="drop">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Just dropped</span>
                <h2>Nouveau drop</h2>
            </div>
            <a href="<?= url('shop.php?filter=new') ?>" class="link-arrow">Toutes les nouveautés</a>
        </div>
        <div class="product-grid scroll-x reveal">
            <?php foreach ($newProducts as $p) echo product_card($p); ?>
        </div>
    </div>
</section>

<!-- CATÉGORIES -->
<section class="section section-tight">
    <div class="container">
        <div class="cat-grid reveal">
            <?php foreach (['vetements', 'chaussures', 'accessoires'] as $i => $slug): [$vis, $col] = listing_visual($slug);
                $cat = $homeTree[$slug] ?? null;
                if (!$cat) continue; ?>
                <a href="<?= url('shop.php?cat=' . $slug) ?>" class="cat-tile" data-vt style="--i:<?= $i ?>">
                    <img src="<?= visual_url($vis, $col) ?>" alt="<?= e($cat['name']) ?>" loading="lazy">
                    <div class="cat-tile-body">
                        <h3><?= e($cat['name']) ?></h3>
                        <p><?= e(implode(' · ', array_column($cat['children'], 'name'))) ?></p>
                        <span class="link-arrow">Découvrir</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BANDEAU DÉFILANT -->
<div class="band" aria-hidden="true">
    <div class="band-track">
        <?php for ($i = 0; $i < 2; $i++): ?>
            <span>Streetwear</span><?= $icoStar ?><span>Lifestyle</span><?= $icoCrown ?><span>You</span><?= $icoStar ?><span>Good drip</span><?= $icoGlobe ?><span>Better days</span><?= $icoStar ?>
        <?php endfor; ?>
    </div>
</div>

<!-- EXPLORER : chaque univers a sa page -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Explorer VYRO</span>
                <h2>Continue la visite</h2>
            </div>
        </div>
        <div class="explore-grid reveal">
            <a href="<?= url('shop.php?sort=best') ?>" class="explore-tile" data-vt>
                <img src="<?= visual_url('hoodie', 'Noir') ?>" alt="" loading="lazy">
                <div class="explore-body">
                    <span class="kicker">Les plus demandés</span>
                    <h3>Best-sellers</h3>
                    <p><?= number_format($totalSold, 0, ',', ' ') ?> pièces vendues</p>
                </div>
            </a>
            <a href="<?= url('collections.php') ?>" class="explore-tile" data-vt>
                <img src="<?= visual_url('pants', 'Kaki') ?>" alt="" loading="lazy">
                <div class="explore-body">
                    <span class="kicker"><?= $nbCollections ?> univers</span>
                    <h3>Collections</h3>
                    <p>New Drop, Essentials, Street, Summer</p>
                </div>
            </a>
            <a href="<?= url('avis.php') ?>" class="explore-tile" data-vt>
                <img src="<?= visual_url('sneaker', 'Blanc') ?>" alt="" loading="lazy">
                <div class="explore-body">
                    <span class="kicker"><?= number_format((float)$stats['a'], 1, ',', '') ?>/5 · <?= (int)$stats['n'] ?> avis</span>
                    <h3>Ils portent VYRO</h3>
                    <p>+<?= number_format($happy, 0, ',', ' ') ?> clients satisfaits</p>
                </div>
            </a>
            <a href="<?= url('blog.php') ?>" class="explore-tile" data-vt>
                <img src="<?= visual_url('cap', 'Beige') ?>" alt="" loading="lazy">
                <div class="explore-body">
                    <span class="kicker">Drops · Lookbooks · Guides</span>
                    <h3>Le Journal</h3>
                    <p>Styles, conseils et coulisses</p>
                </div>
            </a>
        </div>
        <a href="<?= url('communaute.php') ?>" class="community-strip reveal">
            <span><b>Follow the VYRO movement</b> — <?= e(INSTAGRAM_HANDLE) ?> · <?= e(TIKTOK_HANDLE) ?></span>
            <span class="link-arrow">Communauté</span>
        </a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
