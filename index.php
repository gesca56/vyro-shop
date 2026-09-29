<?php
require __DIR__ . '/vyro-config.php';

$newProducts = products_query('p.is_new = 1', [], 'p.created_at DESC', 8);
$bestSellers = products_query('1', [], 'p.sales_count DESC', 8);
foreach ($bestSellers as &$b) $b['show_sales'] = true;
unset($b);
$musts = products_query('p.is_featured = 1', [], 'RAND()', 4);
$collections = q('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.is_active = 1) n FROM collections c ORDER BY sort_order')->fetchAll();
$homeTree = [];
foreach (categories_tree() as $c) $homeTree[$c['slug']] = $c;
$journal = q("SELECT * FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY is_featured DESC, published_at DESC LIMIT 3")->fetchAll();
$reviews = q('SELECT r.*, p.name product_name, p.slug product_slug FROM reviews r LEFT JOIN products p ON p.id = r.product_id
              WHERE r.approved = 1 AND r.rating >= 4 ORDER BY r.created_at DESC LIMIT 8')->fetchAll();
$stats = q('SELECT COUNT(*) n, AVG(rating) a FROM reviews WHERE approved = 1')->fetch();
$happy = 2500 + (int)q("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
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

<!-- NOUVEAU DROP -->
<section class="section" id="drop">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Just dropped</span>
                <h2>Nouveau drop</h2>
            </div>
            <a href="<?= url('shop.php?filter=new') ?>" class="link-arrow">Tout voir</a>
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
                        <span class="cat-index">0<?= $i + 1 ?></span>
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

<!-- BEST SELLERS -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Best sellers</span>
                <h2>Les plus demandés</h2>
            </div>
            <a href="<?= url('shop.php?sort=best') ?>" class="link-arrow">Tout voir</a>
        </div>
        <div class="product-grid scroll-x reveal">
            <?php foreach ($bestSellers as $p) echo product_card($p); ?>
        </div>
    </div>
</section>

<!-- COLLECTIONS -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Collections</span>
                <h2>Tendances du moment</h2>
            </div>
        </div>
        <div class="collection-grid reveal">
            <?php foreach ($collections as $c): [$vis, $col] = listing_visual($c['slug']) ?? ['tee', 'Noir']; ?>
                <a href="<?= url('shop.php?collection=' . $c['slug']) ?>" class="collection-tile" data-vt>
                    <img src="<?= visual_url($vis, $col) ?>" alt="" loading="lazy">
                    <div class="collection-body">
                        <h3><?= e($c['name']) ?></h3>
                        <p><?= e($c['tagline']) ?></p>
                        <span class="link-arrow"><?= (int)$c['n'] ?> pièces</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- INCONTOURNABLES -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Must-have</span>
                <h2>Les incontournables</h2>
            </div>
        </div>
        <div class="product-grid reveal">
            <?php foreach ($musts as $p) echo product_card($p); ?>
        </div>
    </div>
</section>

<!-- AVIS -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head center">
            <div>
                <span class="kicker">Ils portent VYRO</span>
                <h2>+<?= number_format($happy, 0, ',', ' ') ?> clients satisfaits</h2>
                <p class="muted"><?= stars((float)$stats['a']) ?> <b><?= number_format((float)$stats['a'], 1, ',', '') ?>/5</b> · <?= (int)$stats['n'] ?> avis vérifiés</p>
            </div>
        </div>
        <div class="review-grid scroll-x reveal">
            <?php foreach ($reviews as $r): ?>
                <figure class="review-card">
                    <div class="review-top">
                        <span class="avatar"><?= e(mb_substr($r['name'], 0, 1)) ?></span>
                        <div>
                            <strong><?= e($r['name']) ?></strong>
                            <small><?= e($r['city']) ?></small>
                        </div>
                        <?php if ($r['verified']): ?><span class="verified">✓ Achat vérifié</span><?php endif; ?>
                    </div>
                    <?= stars((float)$r['rating'], 'sm') ?>
                    <blockquote>« <?= e($r['comment']) ?> »</blockquote>
                    <?php if ($r['product_name']): ?>
                        <figcaption><a href="<?= url('product.php?slug=' . urlencode($r['product_slug'])) ?>"><?= e($r['product_name']) ?></a></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- JOURNAL -->
<?php if ($journal): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="kicker">Le Journal</span>
                <h2>Drops, lookbooks & guides</h2>
            </div>
            <a href="<?= url('blog.php') ?>" class="link-arrow">Tout lire</a>
        </div>
        <div class="post-grid reveal">
            <?php foreach ($journal as $i => $p): ?>
                <article class="post-card" data-vt style="--i:<?= $i ?>">
                    <a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>" class="post-media"><img src="<?= e(post_cover($p)) ?>" alt="" loading="lazy"><span class="post-cat"><?= e($p['category']) ?></span></a>
                    <div class="post-body">
                        <small class="muted"><?= time_fr($p['published_at']) ?> · <?= reading_time($p['content']) ?> min</small>
                        <h3><a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- RÉSEAUX SOCIAUX -->
<section class="section social-section">
    <div class="container">
        <div class="section-head center">
            <div>
                <span class="kicker"><?= e(INSTAGRAM_HANDLE) ?> · <?= e(TIKTOK_HANDLE) ?></span>
                <h2>Follow the VYRO movement</h2>
                <p class="muted">Partage ton look avec <b>#VYROSTYLE</b> pour apparaître ici.</p>
            </div>
        </div>
        <div class="social-grid reveal">
            <?php
            $posts = [['hoodie', 'Noir', 'Instagram'], ['sneaker', 'Blanc', 'TikTok'], ['set', 'Bleu nuit', 'Instagram'],
                ['cap', 'Beige', 'TikTok'], ['pants', 'Kaki', 'Instagram'], ['runner', 'Blanc', 'Instagram']];
            foreach ($posts as [$v, $c, $net]): ?>
                <a href="<?= $net === 'TikTok' ? TIKTOK_URL : INSTAGRAM_URL ?>" target="_blank" rel="noopener" class="social-tile">
                    <img src="<?= visual_url($v, $c) ?>" alt="Post <?= $net ?> VYRO" loading="lazy">
                    <span class="social-net"><?= $net ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="social-cta">
            <a href="<?= INSTAGRAM_URL ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Instagram <?= e(INSTAGRAM_HANDLE) ?></a>
            <a href="<?= TIKTOK_URL ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">TikTok <?= e(TIKTOK_HANDLE) ?></a>
            <?php if (SNAPCHAT_URL): ?><a href="<?= SNAPCHAT_URL ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Snapchat</a><?php endif; ?>
            <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">WhatsApp</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
