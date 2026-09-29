<?php
require __DIR__ . '/vyro-config.php';

$cat = $_GET['cat'] ?? '';
if (!in_array($cat, post_categories(), true)) $cat = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

$where = "status = 'published' AND published_at <= NOW()";
$params = [];
if ($cat) {
    $where .= ' AND category = ?';
    $params[] = $cat;
}
$total = (int)q("SELECT COUNT(*) FROM posts WHERE $where", $params)->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$posts = q("SELECT * FROM posts WHERE $where ORDER BY is_featured DESC, published_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params)->fetchAll();
$featured = ($page === 1 && !$cat && $posts) ? array_shift($posts) : null;
$counts = q("SELECT category, COUNT(*) FROM posts WHERE status = 'published' AND published_at <= NOW() GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = $cat ? 'Journal — ' . $cat : 'Journal';
$pageDesc = 'Le Journal VYRO : drops, lookbooks, guides de style et culture streetwear à Abidjan.';
require __DIR__ . '/includes/header.php';

function post_card(array $p, int $i = 0): string
{
    ob_start(); ?>
    <article class="post-card" data-vt style="--i:<?= $i ?>">
        <a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>" class="post-media">
            <img src="<?= e(post_cover($p)) ?>" alt="" loading="lazy">
            <span class="post-cat"><?= e($p['category']) ?></span>
        </a>
        <div class="post-body">
            <small class="muted"><?= time_fr($p['published_at']) ?> · <?= reading_time($p['content']) ?> min de lecture</small>
            <h3><a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
            <?php if ($p['excerpt']): ?><p><?= e($p['excerpt']) ?></p><?php endif; ?>
            <a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>" class="link-arrow">Lire l'article →</a>
        </div>
    </article>
    <?php return ob_get_clean();
}
?>

<div id="swapRoot" data-swap-root>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb"><a href="<?= url() ?>">Accueil</a> / <a href="<?= url('blog.php') ?>">Journal</a></nav>
            <h1 class="vt-title">Le Journal<?= $cat ? ' — ' . e($cat) : '' ?></h1>
            <p>Drops, lookbooks, guides de style et culture streetwear.</p>
        </div>
    </section>

    <div class="container">
        <div class="chip-row" data-swap-nav>
            <a href="<?= url('blog.php') ?>" class="chip <?= !$cat ? 'active' : '' ?>">Tout</a>
            <?php foreach (post_categories() as $c): if (empty($counts[$c])) continue; ?>
                <a href="<?= url('blog.php?cat=' . urlencode($c)) ?>" class="chip <?= $cat === $c ? 'active' : '' ?>"><?= e($c) ?> <small><?= (int)$counts[$c] ?></small></a>
            <?php endforeach; ?>
        </div>

        <?php if ($featured): ?>
            <a href="<?= url('article.php?slug=' . urlencode($featured['slug'])) ?>" class="post-featured reveal" data-vt>
                <div class="post-featured-media"><img src="<?= e(post_cover($featured, 'd')) ?>" alt=""></div>
                <div class="post-featured-body">
                    <span class="post-cat"><?= e($featured['category']) ?></span>
                    <h2><?= e($featured['title']) ?></h2>
                    <p><?= e($featured['excerpt']) ?></p>
                    <small><?= time_fr($featured['published_at']) ?> · <?= reading_time($featured['content']) ?> min de lecture</small>
                    <span class="btn btn-accent">Lire l'article</span>
                </div>
            </a>
        <?php endif; ?>

        <?php if ($posts): ?>
            <div class="post-grid">
                <?php foreach ($posts as $i => $p) echo post_card($p, $i); ?>
            </div>
        <?php elseif (!$featured): ?>
            <div class="empty-state"><h3>Aucun article pour le moment</h3><p>Revenez bientôt, de nouveaux contenus arrivent.</p></div>
        <?php endif; ?>

        <?php if ($pages > 1): ?>
            <nav class="pagination" data-swap-nav>
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="<?= url('blog.php?' . http_build_query(array_filter(['cat' => $cat, 'page' => $i]))) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
        <div class="section-spacer"></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
