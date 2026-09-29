<?php
require __DIR__ . '/vyro-config.php';

$post = q("SELECT * FROM posts WHERE slug = ?", [$_GET['slug'] ?? ''])->fetch();
$preview = $post && is_admin() && isset($_GET['preview']);
if (!$post || (!$preview && ($post['status'] !== 'published' || strtotime($post['published_at']) > time()))) {
    http_response_code(404);
    $pageTitle = 'Article introuvable';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container empty-state"><h1>Article introuvable</h1><p>Cet article n\'existe pas ou n\'est plus en ligne.</p><a class="btn btn-dark" href="' . url('blog.php') . '">Voir le Journal</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
if (!$preview) {
    q('UPDATE posts SET views = views + 1 WHERE id = ?', [$post['id']]);
}

$ids = array_filter(array_map('intval', csv_list($post['product_ids'])));
$products = $ids ? products_query('p.id IN (' . implode(',', $ids) . ')', [], 'FIELD(p.id,' . implode(',', $ids) . ')') : [];
$more = q("SELECT * FROM posts WHERE status = 'published' AND published_at <= NOW() AND id <> ? ORDER BY category = ? DESC, published_at DESC LIMIT 3", [$post['id'], $post['category']])->fetchAll();
$shareUrl = (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . url('article.php?slug=' . urlencode($post['slug']));

$pageTitle = $post['title'];
$pageDesc = $post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 155);
require __DIR__ . '/includes/header.php';
?>

<?php if ($preview && $post['status'] !== 'published'): ?>
    <div class="demo-note" style="margin:0;border-radius:0">Aperçu — cet article est en brouillon et n'est pas visible par les clients.</div>
<?php endif; ?>

<article class="article">
    <header class="article-hero">
        <img src="<?= e(post_cover($post, 'd')) ?>" alt="" class="article-hero-img vt-media <?= $post['cover'] ? '' : 'is-generated' ?>">
        <div class="container article-hero-body">
            <nav class="breadcrumb"><a href="<?= url() ?>">Accueil</a> / <a href="<?= url('blog.php') ?>">Journal</a> / <a href="<?= url('blog.php?cat=' . urlencode($post['category'])) ?>"><?= e($post['category']) ?></a></nav>
            <span class="post-cat"><?= e($post['category']) ?></span>
            <h1 class="vt-title"><?= e($post['title']) ?></h1>
            <p class="article-meta"><?= time_fr($post['published_at'] ?? $post['created_at']) ?> · <?= reading_time($post['content']) ?> min de lecture</p>
        </div>
    </header>

    <div class="container article-layout">
        <div class="prose article-content">
            <?php if ($post['excerpt']): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
            <?= render_content($post['content']) ?>
        </div>
        <aside class="article-share">
            <span class="kicker">Partager</span>
            <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($post['title'] . ' ' . $shareUrl) ?>">WhatsApp</a>
            <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>">Facebook</a>
            <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($post['title']) ?>">X</a>
            <button class="btn btn-ghost btn-sm" data-copy="<?= e($shareUrl) ?>">Copier le lien</button>
        </aside>
    </div>

    <?php if ($products): ?>
        <section class="section section-alt">
            <div class="container">
                <div class="section-head"><div><span class="kicker">Dans cet article</span><h2>Shopper le look</h2></div></div>
                <div class="product-grid scroll-x reveal">
                    <?php foreach ($products as $p) echo product_card($p); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($more): ?>
        <section class="section">
            <div class="container">
                <div class="section-head"><h2>À lire aussi</h2><a href="<?= url('blog.php') ?>" class="link-arrow">Tout le Journal →</a></div>
                <div class="post-grid reveal">
                    <?php foreach ($more as $i => $m): ?>
                        <article class="post-card" data-vt style="--i:<?= $i ?>">
                            <a href="<?= url('article.php?slug=' . urlencode($m['slug'])) ?>" class="post-media"><img src="<?= e(post_cover($m)) ?>" alt="" loading="lazy"><span class="post-cat"><?= e($m['category']) ?></span></a>
                            <div class="post-body">
                                <small class="muted"><?= time_fr($m['published_at']) ?></small>
                                <h3><a href="<?= url('article.php?slug=' . urlencode($m['slug'])) ?>"><?= e($m['title']) ?></a></h3>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
