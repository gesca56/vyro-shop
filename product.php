<?php
require __DIR__ . '/vyro-config.php';

$slug = $_GET['slug'] ?? '';
$rows = products_query('p.slug = ?', [$slug], 'p.id', 1);
$p = $rows[0] ?? null;
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container empty-state"><h1>Produit introuvable</h1><p>Ce produit n\'existe plus ou a été déplacé.</p><a class="btn btn-dark" href="' . url('shop.php') . '">Retour à la boutique</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();

// Dépôt d'un avis
if (is_post() && ($_POST['action'] ?? '') === 'review') {
    csrf_check();
    if (!$user) {
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if (mb_strlen($comment) < 10) {
        flash('error', 'Votre avis doit contenir au moins 10 caractères.');
    } else {
        $bought = (bool)q("SELECT 1 FROM order_items oi JOIN orders o ON o.id = oi.order_id
                           WHERE o.user_id = ? AND oi.product_id = ? AND o.status <> 'cancelled' LIMIT 1", [$user['id'], $p['id']])->fetchColumn();
        q('INSERT INTO reviews (product_id,user_id,name,rating,comment,verified,approved) VALUES (?,?,?,?,?,?,?)',
            [$p['id'], $user['id'], $user['first_name'], $rating, $comment, $bought ? 1 : 0, 1]);
        flash('success', 'Merci pour votre avis !');
    }
    redirect('product.php?slug=' . urlencode($slug) . '#avis');
}

$gallery = product_gallery($p);
$sizes = csv_list($p['sizes']);
$colors = csv_list($p['colors']);
$pct = discount_percent($p);
$rating = ['avg' => round((float)$p['rating_avg'], 1), 'count' => (int)$p['rating_count']];
$reviews = q('SELECT * FROM reviews WHERE product_id = ? AND approved = 1 ORDER BY created_at DESC', [$p['id']])->fetchAll();
$related = products_query('p.category_id = ? AND p.id <> ?', [$p['category_id'], $p['id']], 'p.sales_count DESC', 4);
if (count($related) < 4) {
    $related = array_merge($related, products_query('p.collection_id = ? AND p.id <> ? AND p.category_id <> ?', [$p['collection_id'], $p['id'], $p['category_id']], 'RAND()', 4 - count($related)));
}
$isFav = in_array((int)$p['id'], favorite_ids(), true);
$stock = (int)$p['stock'];
$isShoe = in_array($p['visual'], ['sneaker', 'runner', 'lifestyle'], true);
$isUnique = $sizes === ['Unique'];
$parentCat = q('SELECT c2.* FROM categories c1 LEFT JOIN categories c2 ON c2.id = c1.parent_id WHERE c1.id = ?', [$p['category_id']])->fetch();

$pageTitle = $p['name'];
$pageDesc = mb_substr($p['description'], 0, 155);
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= url() ?>">Accueil</a> /
        <?php if ($parentCat && $parentCat['id']): ?><a href="<?= url('shop.php?cat=' . $parentCat['slug']) ?>"><?= e($parentCat['name']) ?></a> /<?php endif; ?>
        <a href="<?= url('shop.php?cat=' . $p['category_slug']) ?>"><?= e($p['category_name']) ?></a> /
        <span><?= e($p['name']) ?></span>
    </nav>

    <div class="product-layout">
        <!-- Galerie -->
        <div class="gallery">
            <div class="gallery-main" id="galleryMain">
                <?php foreach ($gallery as $i => $g): ?>
                    <img src="<?= e($g['src']) ?>" alt="<?= e($p['name']) ?> — vue <?= $i + 1 ?>" data-color="<?= e($g['color']) ?>" <?= $i ? 'loading="lazy"' : '' ?>>
                <?php endforeach; ?>
                <?php if ($p['video_url']): ?>
                    <div class="gallery-video">
                        <?php if (preg_match('~(?:youtu\.be/|v=)([\w-]{11})~', $p['video_url'], $m)): ?>
                            <iframe src="https://www.youtube.com/embed/<?= e($m[1]) ?>" title="Vidéo produit" allowfullscreen loading="lazy"></iframe>
                        <?php else: ?>
                            <video src="<?= e($p['video_url']) ?>" controls playsinline muted></video>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="gallery-thumbs">
                <?php foreach ($gallery as $i => $g): ?>
                    <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"><img src="<?= e($g['src']) ?>" alt=""></button>
                <?php endforeach; ?>
                <?php if ($p['video_url']): ?>
                    <button type="button" data-index="<?= count($gallery) ?>" class="thumb-video">▶</button>
                <?php endif; ?>
            </div>
            <div class="card-badges gallery-badges">
                <?php if ($p['is_new']): ?><span class="badge badge-new">NEW</span><?php endif; ?>
                <?php if ($pct): ?><span class="badge badge-sale">-<?= $pct ?>%</span><?php endif; ?>
            </div>
        </div>

        <!-- Infos -->
        <div class="product-info">
            <span class="kicker"><?= e($p['category_name']) ?></span>
            <h1><?= e($p['name']) ?></h1>
            <a href="#avis" class="rating-line"><?= stars($rating['avg']) ?> <span><?= $rating['count'] ? number_format($rating['avg'], 1, ',', '') . ' (' . $rating['count'] . ' avis)' : 'Aucun avis' ?></span> · <span><?= (int)$p['sales_count'] ?> vendus</span></a>

            <div class="product-price">
                <span class="price"><?= price($p['price']) ?></span>
                <?php if ($pct): ?>
                    <s class="old-price"><?= price($p['old_price']) ?></s>
                    <span class="badge badge-sale">-<?= $pct ?>%</span>
                <?php endif; ?>
            </div>
            <?php if ($pct): ?><p class="saving">Vous économisez <?= price($p['old_price'] - $p['price']) ?></p><?php endif; ?>

            <form method="post" action="<?= url('cart-action.php') ?>" class="buy-form" id="buyForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                <?php if ($colors): ?>
                    <div class="opt-block">
                        <div class="opt-label">Couleur : <b id="colorName"><?= e($colors[0]) ?></b></div>
                        <div class="color-options lg">
                            <?php foreach ($colors as $i => $c): ?>
                                <label class="opt-color" title="<?= e($c) ?>">
                                    <input type="radio" name="color" value="<?= e($c) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                                    <span style="--sw:<?= color_hex($c) ?>"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($sizes && !$isUnique): ?>
                    <div class="opt-block">
                        <div class="opt-label">
                            Taille : <b id="sizeName">Choisir</b>
                            <button type="button" class="link-btn" data-modal="sizeGuide">📏 Guide des tailles</button>
                        </div>
                        <div class="size-options lg">
                            <?php foreach ($sizes as $s): ?>
                                <label class="opt"><input type="radio" name="size" value="<?= e($s) ?>" required><span><?= e($s) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="size" value="<?= e($sizes[0] ?? '') ?>">
                <?php endif; ?>

                <div class="opt-block">
                    <div class="opt-label">Quantité</div>
                    <div class="qty-row">
                        <div class="qty">
                            <button type="button" data-qty="-1" aria-label="Moins">−</button>
                            <input type="number" name="qty" value="1" min="1" max="<?= max(1, $stock) ?>" inputmode="numeric">
                            <button type="button" data-qty="1" aria-label="Plus">+</button>
                        </div>
                        <?php if ($stock <= 0): ?>
                            <span class="stock stock-out">● Rupture de stock</span>
                        <?php elseif ($stock <= LOW_STOCK_THRESHOLD): ?>
                            <span class="stock stock-low">● Plus que <?= $stock ?> en stock</span>
                        <?php else: ?>
                            <span class="stock stock-ok">● En stock, expédié sous 24h</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($stock > 0): ?>
                    <div class="buy-actions" id="buyActions">
                        <button class="btn btn-dark btn-lg btn-block" name="action" value="add">AJOUTER AU PANIER</button>
                        <button class="btn btn-accent btn-lg btn-block" name="action" value="buy">ACHETER MAINTENANT</button>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline btn-lg btn-block" href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode('Bonjour, je souhaite être prévenu(e) du retour de : ' . $p['name']) ?>" target="_blank" rel="noopener">Me prévenir du retour (WhatsApp)</a>
                <?php endif; ?>
                <button type="button" class="btn btn-ghost btn-block fav-inline <?= $isFav ? 'active' : '' ?>" data-fav="<?= (int)$p['id'] ?>">
                    <svg viewBox="0 0 24 24" width="18"><path d="M12 21s-7.5-4.6-9.5-9.2C1.1 8.5 3.2 5 6.6 5c2 0 3.4 1.1 4.4 2.5C12 6.1 13.4 5 15.4 5c3.4 0 5.5 3.5 4.1 6.8C19.5 16.4 12 21 12 21z"/></svg>
                    <span><?= $isFav ? 'Dans vos favoris' : 'Ajouter aux favoris' ?></span>
                </button>
            </form>

            <ul class="perks">
                <li>🚚 Livraison 24–48h à Abidjan — offerte dès <?= price(FREE_SHIPPING_THRESHOLD) ?></li>
                <li>📱 Paiement Wave, Orange Money, MTN, Moov ou à la livraison</li>
                <li>↩️ Retours et échanges sous 7 jours</li>
            </ul>

            <div class="accordion">
                <details open>
                    <summary>Description</summary>
                    <div><?= nl2br(e($p['description'])) ?></div>
                </details>
                <details>
                    <summary>Livraison</summary>
                    <div>
                        <p><b>Abidjan :</b> 24 à 48h — <?= price(SHIPPING_FEE) ?>.</p>
                        <p><b>Intérieur du pays :</b> 2 à 5 jours ouvrés — <?= price(SHIPPING_FEE_OTHER) ?>.</p>
                        <p>Livraison gratuite dès <?= price(FREE_SHIPPING_THRESHOLD) ?> d'achat. Retrait possible en point relais.</p>
                        <a href="<?= url('page.php?p=livraison') ?>">Politique de livraison →</a>
                    </div>
                </details>
                <details>
                    <summary>Retours & échanges</summary>
                    <div>
                        <p>Vous disposez de 7 jours après réception pour échanger ou retourner un article non porté, avec ses étiquettes.</p>
                        <a href="<?= url('page.php?p=retours') ?>">Politique de retour →</a>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <!-- Avis -->
    <section class="section" id="avis">
        <div class="section-head">
            <div>
                <span class="kicker">Avis clients</span>
                <h2><?= $rating['count'] ? number_format($rating['avg'], 1, ',', '') . '/5' : 'Soyez le premier à donner votre avis' ?></h2>
                <?php if ($rating['count']): ?><p class="muted"><?= stars($rating['avg']) ?> <?= $rating['count'] ?> avis</p><?php endif; ?>
            </div>
        </div>
        <div class="reviews-layout">
            <div class="review-list">
                <?php foreach ($reviews as $r): ?>
                    <div class="review-item">
                        <div class="review-top">
                            <span class="avatar"><?= e(mb_substr($r['name'], 0, 1)) ?></span>
                            <div>
                                <strong><?= e($r['name']) ?></strong>
                                <small><?= e($r['city'] ? $r['city'] . ' · ' : '') ?><?= time_fr($r['created_at']) ?></small>
                            </div>
                            <?php if ($r['verified']): ?><span class="verified">✓ Achat vérifié</span><?php endif; ?>
                        </div>
                        <?= stars((float)$r['rating'], 'sm') ?>
                        <p><?= e($r['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$reviews): ?><p class="muted">Aucun avis pour le moment.</p><?php endif; ?>
            </div>
            <div class="review-form-box">
                <h3>Donner votre avis</h3>
                <?php if ($user): ?>
                    <form method="post" class="form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="review">
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="r<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="r<?= $i ?>" title="<?= $i ?>/5">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="comment" rows="4" placeholder="Qualité, taille, confort…" required minlength="10"></textarea>
                        <button class="btn btn-dark btn-block">Publier mon avis</button>
                    </form>
                <?php else: ?>
                    <p class="muted">Connectez-vous pour laisser un avis.</p>
                    <a class="btn btn-outline btn-block" href="<?= url('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Se connecter</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($related): ?>
        <section class="section">
            <div class="section-head"><h2>Vous aimerez aussi</h2></div>
            <div class="product-grid scroll-x">
                <?php foreach ($related as $rp) echo product_card($rp); ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php if ($stock > 0): ?>
    <div class="sticky-buy" id="stickyBuy">
        <div>
            <strong><?= e($p['name']) ?></strong>
            <span><?= price($p['price']) ?></span>
        </div>
        <button class="btn btn-dark" type="submit" form="buyForm" name="action" value="add">Ajouter</button>
    </div>
<?php endif; ?>

<!-- Guide des tailles -->
<div class="modal" id="sizeGuide" role="dialog" aria-modal="true" aria-labelledby="sgTitle">
    <div class="modal-box">
        <button class="icon-btn modal-close" data-close-modal aria-label="Fermer"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        <h3 id="sgTitle">Guide des tailles</h3>
        <?php if ($isShoe): ?>
            <table class="table">
                <thead><tr><th>EU</th><th>US</th><th>UK</th><th>Longueur pied (cm)</th></tr></thead>
                <tbody>
                <?php foreach ([[39, 6.5, 5.5, 24.5], [40, 7, 6, 25], [41, 8, 7, 26], [42, 8.5, 7.5, 26.5], [43, 9.5, 8.5, 27.5], [44, 10, 9, 28], [45, 11, 10, 29]] as $r): ?>
                    <tr><td><?= $r[0] ?></td><td><?= $r[1] ?></td><td><?= $r[2] ?></td><td><?= $r[3] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted">Astuce : mesurez votre pied le soir, talon contre un mur. Entre deux tailles, prenez la plus grande.</p>
        <?php elseif (preg_match('/^\d/', $sizes[0] ?? '')): ?>
            <table class="table">
                <thead><tr><th>Taille</th><th>Tour de taille (cm)</th><th>Tour de hanches (cm)</th></tr></thead>
                <tbody>
                <?php foreach ([[28, 71, 88], [30, 76, 93], [32, 81, 98], [34, 86, 103], [36, 91, 108], [38, 96, 113]] as $r): ?>
                    <tr><td><?= $r[0] ?></td><td><?= $r[1] ?></td><td><?= $r[2] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Taille</th><th>Poitrine (cm)</th><th>Longueur (cm)</th><th>Taille conseillée</th></tr></thead>
                <tbody>
                <?php foreach ([['S', '96–101', 70, '1m60–1m70'], ['M', '101–106', 72, '1m70–1m78'], ['L', '106–112', 74, '1m75–1m83'], ['XL', '112–118', 76, '1m80–1m88'], ['XXL', '118–124', 78, '1m85+']] as $r): ?>
                    <tr><td><b><?= $r[0] ?></b></td><td><?= $r[1] ?></td><td><?= $r[2] ?></td><td><?= $r[3] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted">Nos coupes oversized taillent large : pour un rendu ajusté, prenez une taille en dessous.</p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
