<?php
require __DIR__ . '/vyro-config.php';

$journal = q("SELECT * FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll();
$looks = [['hoodie', 'Noir', 'Instagram'], ['sneaker', 'Blanc', 'TikTok'], ['set', 'Bleu nuit', 'Instagram'], ['cap', 'Beige', 'TikTok'],
    ['pants', 'Kaki', 'Instagram'], ['runner', 'Blanc', 'Instagram'], ['jacket', 'Noir', 'TikTok'], ['tee', 'Blanc', 'Instagram'], ['bag', 'Noir', 'Instagram']];

$pageTitle = 'Communauté';
$pageDesc = 'Follow the VYRO movement : Instagram ' . INSTAGRAM_HANDLE . ', TikTok ' . TIKTOK_HANDLE . ' et WhatsApp.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb"><a href="<?= url() ?>">Accueil</a> / <span>Communauté</span></nav>
        <h1 class="vt-title">Follow the VYRO movement</h1>
        <p>Partage ton look avec <b>#VYROSTYLE</b> : les meilleurs apparaissent ici et sur nos réseaux.</p>
    </div>
</section>

<div class="container">
    <div class="social-cards reveal">
        <a href="<?= INSTAGRAM_URL ?>" target="_blank" rel="noopener" class="social-card">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
            <span class="kicker">Instagram</span>
            <strong><?= e(INSTAGRAM_HANDLE) ?></strong>
            <small>Drops, lookbooks et coulisses</small>
            <span class="link-arrow">S’abonner</span>
        </a>
        <a href="<?= TIKTOK_URL ?>" target="_blank" rel="noopener" class="social-card">
            <svg viewBox="0 0 24 24"><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5M14 3c.5 2.5 2.3 4.2 5 4.5"/></svg>
            <span class="kicker">TikTok</span>
            <strong><?= e(TIKTOK_HANDLE) ?></strong>
            <small>Try-on, unboxing et vidéos du drop</small>
            <span class="link-arrow">Suivre</span>
        </a>
        <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode('Bonjour VYRO 👋') ?>" target="_blank" rel="noopener" class="social-card">
            <svg viewBox="0 0 24 24"><path d="M4 20l1.3-4A8 8 0 1 1 8 18.7L4 20z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2L9 9.5z"/></svg>
            <span class="kicker">WhatsApp</span>
            <strong><?= e(CONTACT_PHONE) ?></strong>
            <small>Conseils taille, commandes, SAV</small>
            <span class="link-arrow">Écrire</span>
        </a>
    </div>

    <div class="section-head">
        <div><span class="kicker">#VYROSTYLE</span><h2>Les looks de la communauté</h2></div>
    </div>
    <div class="looks-grid reveal">
        <?php foreach ($looks as [$v, $c, $net]): ?>
            <a href="<?= $net === 'TikTok' ? TIKTOK_URL : INSTAGRAM_URL ?>" target="_blank" rel="noopener" class="social-tile">
                <img src="<?= visual_url($v, $c) ?>" alt="Look VYRO partagé sur <?= $net ?>" loading="lazy">
                <span class="social-net"><?= $net ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($journal): ?>
        <div class="section-head" style="margin-top:56px">
            <div><span class="kicker">Le Journal</span><h2>Dernières histoires</h2></div>
            <a href="<?= url('blog.php') ?>" class="link-arrow">Tout lire</a>
        </div>
        <div class="post-grid reveal">
            <?php foreach ($journal as $i => $p): ?>
                <article class="post-card" data-vt style="--i:<?= $i ?>">
                    <a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>" class="post-media"><img src="<?= e(post_cover($p)) ?>" alt="" loading="lazy"><span class="post-cat"><?= e($p['category']) ?></span></a>
                    <div class="post-body">
                        <small class="muted"><?= time_fr($p['published_at']) ?></small>
                        <h3><a href="<?= url('article.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="section-spacer"></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
