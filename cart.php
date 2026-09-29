<?php
require __DIR__ . '/vyro-config.php';

$lines = cart_lines();
$totals = cart_totals($lines, $_SESSION['promo_code'] ?? null);
if (!empty($_SESSION['promo_code']) && !$totals['promo']) {
    unset($_SESSION['promo_code']);
}
$suggest = $lines ? products_query('p.is_featured = 1 AND p.stock > 0', [], 'RAND()', 4) : products_query('1', [], 'p.sales_count DESC', 4);

$pageTitle = 'Panier';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-title-row">
        <h1>Mon panier <?php if ($lines): ?><span class="muted">(<?= cart_count() ?>)</span><?php endif; ?></h1>
    </div>

    <?php if (!$lines): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" width="56"><path d="M5 7h14l-1.2 12.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 7z"/><path d="M9 7V6a3 3 0 0 1 6 0v1"/></svg>
            <h3>Votre panier est vide</h3>
            <p>Découvrez les nouveautés et les best sellers VYRO.</p>
            <a href="<?= url('shop.php') ?>" class="btn btn-dark btn-lg">Continuer mes achats</a>
        </div>
    <?php else: ?>
        <?php if ($totals['free_left'] > 0): ?>
            <div class="free-ship">
                Plus que <b><?= price($totals['free_left']) ?></b> pour profiter de la livraison offerte 🚚
                <div class="progress"><span style="width:<?= min(100, round(($totals['subtotal'] - $totals['discount']) * 100 / FREE_SHIPPING_THRESHOLD)) ?>%"></span></div>
            </div>
        <?php else: ?>
            <div class="free-ship done">🎉 Livraison offerte sur votre commande !</div>
        <?php endif; ?>

        <div class="cart-layout">
            <form method="post" action="<?= url('cart-action.php') ?>" class="cart-items" id="cartForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <?php foreach ($lines as $l): $p = $l['product']; ?>
                    <div class="cart-item">
                        <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" class="cart-thumb">
                            <img src="<?= e(product_thumb($p, $l['color'] ?: null)) ?>" alt="<?= e($p['name']) ?>">
                        </a>
                        <div class="cart-info">
                            <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" class="cart-name"><?= e($p['name']) ?></a>
                            <div class="cart-variant">
                                <?php if ($l['color']): ?><span><span class="swatch sm" style="--sw:<?= color_hex($l['color']) ?>"></span> <?= e($l['color']) ?></span><?php endif; ?>
                                <?php if ($l['size'] && $l['size'] !== 'Unique'): ?><span>Taille <?= e($l['size']) ?></span><?php endif; ?>
                            </div>
                            <div class="cart-unit"><?= price($l['unit']) ?>
                                <?php if (discount_percent($p)): ?><s class="old-price"><?= price($p['old_price']) ?></s><?php endif; ?>
                            </div>
                            <div class="cart-controls">
                                <div class="qty sm">
                                    <button type="button" data-qty="-1" aria-label="Moins">−</button>
                                    <input type="number" name="qty[<?= e($l['key']) ?>]" value="<?= $l['qty'] ?>" min="0" max="<?= (int)$p['stock'] ?>" data-autosubmit>
                                    <button type="button" data-qty="1" aria-label="Plus">+</button>
                                </div>
                                <button type="submit" form="rm-<?= md5($l['key']) ?>" class="link-btn danger">Supprimer</button>
                            </div>
                        </div>
                        <div class="cart-line-total"><?= price($l['total']) ?></div>
                    </div>
                <?php endforeach; ?>
                <noscript><button class="btn btn-outline">Mettre à jour le panier</button></noscript>
            </form>

            <?php foreach ($lines as $l): ?>
                <form method="post" action="<?= url('cart-action.php') ?>" id="rm-<?= md5($l['key']) ?>" hidden>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="key" value="<?= e($l['key']) ?>">
                </form>
            <?php endforeach; ?>

            <aside class="summary">
                <h3>Récapitulatif</h3>
                <form method="post" action="<?= url('cart-action.php') ?>" class="promo-form">
                    <?= csrf_field() ?>
                    <?php if ($totals['promo']): ?>
                        <input type="hidden" name="action" value="remove_promo">
                        <div class="promo-applied">
                            <span>🏷️ <b><?= e($totals['promo']['code']) ?></b> — <?= e(promo_label($totals['promo'])) ?></span>
                            <button class="link-btn danger">Retirer</button>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="action" value="promo">
                        <input type="text" name="code" placeholder="Code promo" aria-label="Code promo" autocapitalize="characters">
                        <button class="btn btn-outline">Appliquer</button>
                    <?php endif; ?>
                </form>
                <dl class="totals">
                    <div><dt>Sous-total</dt><dd><?= price($totals['subtotal']) ?></dd></div>
                    <?php if ($totals['discount']): ?><div class="discount"><dt>Réduction</dt><dd>−<?= price($totals['discount']) ?></dd></div><?php endif; ?>
                    <div><dt>Livraison <small>(Abidjan)</small></dt><dd><?= $totals['shipping'] ? price($totals['shipping']) : '<b class="free">Offerte</b>' ?></dd></div>
                    <div class="grand"><dt>Total</dt><dd><?= price($totals['total']) ?></dd></div>
                </dl>
                <p class="muted small">Frais de livraison hors Abidjan : <?= price(SHIPPING_FEE_OTHER) ?>, calculés à l'étape suivante.</p>
                <a href="<?= url('checkout.php') ?>" class="btn btn-accent btn-lg btn-block">PASSER À LA COMMANDE</a>
                <a href="<?= url('shop.php') ?>" class="btn btn-ghost btn-block">Continuer mes achats</a>
                <div class="pay-mini">
                    <span style="--pc:#FF7900">Orange</span><span style="--pc:#FFCC00">MTN</span><span style="--pc:#0066B3">Moov</span><span style="--pc:#1DC8FF">Wave</span><span style="--pc:#6B6B6B">Livraison</span>
                </div>
            </aside>
        </div>
    <?php endif; ?>

    <?php if ($suggest): ?>
        <section class="section">
            <div class="section-head"><h2><?= $lines ? 'Complétez votre look' : 'Best sellers' ?></h2></div>
            <div class="product-grid scroll-x">
                <?php foreach ($suggest as $p) echo product_card($p); ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php if ($lines): ?>
    <div class="sticky-buy">
        <div><strong>Total</strong><span><?= price($totals['total']) ?></span></div>
        <a href="<?= url('checkout.php') ?>" class="btn btn-accent">Commander</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
