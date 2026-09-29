<?php
require __DIR__ . '/vyro-config.php';

$reviews = q('SELECT r.*, p.name product_name, p.slug product_slug, p.visual, p.colors
              FROM reviews r LEFT JOIN products p ON p.id = r.product_id
              WHERE r.approved = 1 ORDER BY r.created_at DESC')->fetchAll();
$stats = q('SELECT COUNT(*) n, COALESCE(AVG(rating), 0) a FROM reviews WHERE approved = 1')->fetch();
$dist = q('SELECT rating, COUNT(*) FROM reviews WHERE approved = 1 GROUP BY rating')->fetchAll(PDO::FETCH_KEY_PAIR);
$verified = count(array_filter($reviews, fn($r) => $r['verified']));
$happy = 2500 + (int)q("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();

$pageTitle = 'Avis clients';
$pageDesc = 'Ils portent VYRO : les avis vérifiés de nos clients à Abidjan et partout en Côte d’Ivoire.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb"><a href="<?= url() ?>">Accueil</a> / <span>Avis clients</span></nav>
        <h1 class="vt-title">Ils portent VYRO</h1>
        <p>+<?= number_format($happy, 0, ',', ' ') ?> clients satisfaits. Voici ce qu’ils en disent.</p>
    </div>
</section>

<div class="container">
    <div class="rating-summary reveal">
        <div class="rating-big">
            <strong><?= number_format((float)$stats['a'], 1, ',', '') ?></strong>
            <?= stars((float)$stats['a']) ?>
            <span class="muted"><?= (int)$stats['n'] ?> avis · <?= $verified ?> achats vérifiés</span>
        </div>
        <div class="rating-bars">
            <?php for ($s = 5; $s >= 1; $s--): $n = (int)($dist[$s] ?? 0); $pct = $stats['n'] ? round($n * 100 / $stats['n']) : 0; ?>
                <div class="rating-bar">
                    <span><?= $s ?> ★</span>
                    <i><b style="width:<?= $pct ?>%"></b></i>
                    <span class="muted"><?= $n ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="reviews-wall">
        <?php foreach ($reviews as $i => $r): ?>
            <figure class="review-card" style="--i:<?= min($i, 12) ?>">
                <div class="review-top">
                    <span class="avatar"><?= e(mb_substr($r['name'], 0, 1)) ?></span>
                    <div>
                        <strong><?= e($r['name']) ?></strong>
                        <small><?= e($r['city'] ? $r['city'] . ' · ' : '') ?><?= time_fr($r['created_at']) ?></small>
                    </div>
                    <?php if ($r['verified']): ?><span class="verified">✓ Achat vérifié</span><?php endif; ?>
                </div>
                <?= stars((float)$r['rating'], 'sm') ?>
                <blockquote>« <?= e($r['comment']) ?> »</blockquote>
                <?php if ($r['product_name']): ?>
                    <figcaption>
                        <a href="<?= url('product.php?slug=' . urlencode($r['product_slug'])) ?>" class="review-product">
                            <img src="<?= visual_url($r['visual'], csv_list($r['colors'])[0] ?? 'Noir') ?>" alt="" loading="lazy">
                            <span><?= e($r['product_name']) ?></span>
                        </a>
                    </figcaption>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>

    <div class="page-cta">
        <h2>Toi aussi, donne ton avis</h2>
        <p class="muted">Rends-toi sur la fiche du produit que tu as acheté, en bas de page.</p>
        <a href="<?= url('shop.php?sort=best') ?>" class="btn btn-light">Voir les best-sellers</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
