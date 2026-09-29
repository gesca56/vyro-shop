<?php
require __DIR__ . '/vyro-config.php';

$cat = $_GET['cat'] ?? '';
$collection = $_GET['collection'] ?? '';
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'new';
$size = $_GET['size'] ?? '';
$color = $_GET['color'] ?? '';
$maxPrice = (int)($_GET['max'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = ['1'];
$params = [];
$title = 'Boutique';
$subtitle = 'Toute la collection VYRO';
$catRow = null;

if ($cat) {
    $catRow = q('SELECT * FROM categories WHERE slug = ?', [$cat])->fetch();
    if ($catRow) {
        $where[] = '(p.category_id = ? OR c.parent_id = ?)';
        array_push($params, $catRow['id'], $catRow['id']);
        $title = $catRow['name'];
        $subtitle = $catRow['parent_id'] ? 'Sélection ' . $catRow['name'] : 'Tous nos ' . mb_strtolower($catRow['name']);
    }
}
if ($collection) {
    $colRow = q('SELECT * FROM collections WHERE slug = ?', [$collection])->fetch();
    if ($colRow) {
        $where[] = 'p.collection_id = ?';
        $params[] = $colRow['id'];
        $title = $colRow['name'];
        $subtitle = $colRow['tagline'];
    }
}
if ($filter === 'new') {
    $where[] = 'p.is_new = 1';
    $title = 'Nouveautés';
    $subtitle = 'Les dernières pièces du drop';
} elseif ($filter === 'promo') {
    $where[] = 'p.old_price IS NOT NULL AND p.old_price > p.price';
    $title = 'Promotions';
    $subtitle = 'Les meilleures offres VYRO du moment';
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
    $title = 'Recherche : « ' . $search . ' »';
    $subtitle = '';
}
if ($size !== '') {
    $where[] = 'FIND_IN_SET(?, p.sizes)';
    $params[] = $size;
}
if ($color !== '') {
    $where[] = 'FIND_IN_SET(?, p.colors)';
    $params[] = $color;
}
if ($maxPrice > 0) {
    $where[] = 'p.price <= ?';
    $params[] = $maxPrice;
}

$orders = [
    'new' => 'p.is_new DESC, p.created_at DESC',
    'best' => 'p.sales_count DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'promo' => '(COALESCE(p.old_price,p.price) - p.price) / COALESCE(p.old_price,p.price) DESC',
];
$orderBy = $orders[$sort] ?? $orders['new'];
$whereSql = implode(' AND ', $where);

$total = (int)q("SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND $whereSql", $params)->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$products = products_query($whereSql, $params, $orderBy . ', p.id DESC', $perPage, ($page - 1) * $perPage);

// Filtres disponibles
$allSizes = ['S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '39', '40', '41', '42', '43', '44', '45'];
$allColors = [];
foreach (q('SELECT colors FROM products WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN) as $cs) {
    foreach (csv_list($cs) as $c) $allColors[$c] = true;
}
$allColors = array_keys($allColors);
$tree = categories_tree();
$collections = q('SELECT * FROM collections ORDER BY sort_order')->fetchAll();

function shop_url(array $override = []): string
{
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return url('shop.php' . ($params ? '?' . http_build_query($params) : ''));
}

$heroVisual = $search !== '' ? null : listing_visual($cat ?: ($collection ?: ($filter ?: 'all')));

$pageTitle = $title;
require __DIR__ . '/includes/header.php';
?>

<div id="swapRoot" data-swap-root>
<section class="page-hero <?= $heroVisual ? 'has-media' : '' ?>">
    <div class="container">
        <div class="page-hero-text">
            <nav class="breadcrumb"><a href="<?= url() ?>">Accueil</a> / <a href="<?= url('shop.php') ?>">Boutique</a><?php if ($catRow && $catRow['parent_id']):
                $parent = q('SELECT * FROM categories WHERE id = ?', [$catRow['parent_id']])->fetch(); ?> / <a href="<?= url('shop.php?cat=' . $parent['slug']) ?>"><?= e($parent['name']) ?></a><?php endif; ?></nav>
            <h1 class="vt-title"><?= e($title) ?></h1>
            <?php if ($subtitle): ?><p><?= e($subtitle) ?></p><?php endif; ?>
        </div>
        <?php if ($heroVisual): ?>
            <div class="page-hero-media vt-media"><img src="<?= e(visual_url($heroVisual[0], $heroVisual[1])) ?>" alt=""></div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <?php if ($catRow): $parentId = $catRow['parent_id'] ?: $catRow['id']; ?>
        <div class="chip-row">
            <a href="<?= url('shop.php?cat=' . $tree[$parentId]['slug']) ?>" class="chip <?= !$catRow['parent_id'] ? 'active' : '' ?>">Tout</a>
            <?php foreach ($tree[$parentId]['children'] as $ch): ?>
                <a href="<?= url('shop.php?cat=' . $ch['slug']) ?>" class="chip <?= $ch['slug'] === $cat ? 'active' : '' ?>"><?= e($ch['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="chip-row">
            <a href="<?= url('shop.php') ?>" class="chip <?= !$collection && !$filter ? 'active' : '' ?>">Tout</a>
            <a href="<?= url('shop.php?filter=new') ?>" class="chip <?= $filter === 'new' ? 'active' : '' ?>">Nouveautés</a>
            <a href="<?= url('shop.php?filter=promo') ?>" class="chip <?= $filter === 'promo' ? 'active' : '' ?>">Promos</a>
            <?php foreach ($collections as $c): ?>
                <a href="<?= url('shop.php?collection=' . $c['slug']) ?>" class="chip <?= $collection === $c['slug'] ? 'active' : '' ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="shop-layout">
        <aside class="filters" id="filters">
            <div class="filters-head">
                <strong>Filtres</strong>
                <button class="icon-btn" data-close-filters aria-label="Fermer"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
            </div>
            <form method="get" action="<?= url('shop.php') ?>">
                <?php foreach (['cat', 'collection', 'filter', 'q', 'sort'] as $k): if (!empty($_GET[$k])): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= e($_GET[$k]) ?>">
                <?php endif; endforeach; ?>

                <div class="filter-group">
                    <h4>Catégories</h4>
                    <?php foreach ($tree as $t): ?>
                        <a href="<?= url('shop.php?cat=' . $t['slug']) ?>" class="filter-link <?= $cat === $t['slug'] ? 'active' : '' ?>"><?= e($t['name']) ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h4>Taille</h4>
                    <div class="size-options">
                        <?php foreach ($allSizes as $s): ?>
                            <label class="opt"><input type="radio" name="size" value="<?= e($s) ?>" <?= $size === $s ? 'checked' : '' ?>><span><?= e($s) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Couleur</h4>
                    <div class="color-options">
                        <?php foreach ($allColors as $c): ?>
                            <label class="opt-color" title="<?= e($c) ?>"><input type="radio" name="color" value="<?= e($c) ?>" <?= $color === $c ? 'checked' : '' ?>><span style="--sw:<?= color_hex($c) ?>"></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Prix maximum</h4>
                    <select name="max">
                        <option value="">Tous les prix</option>
                        <?php foreach ([25000, 40000, 60000, 80000, 100000] as $m): ?>
                            <option value="<?= $m ?>" <?= $maxPrice === $m ? 'selected' : '' ?>>≤ <?= price($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-dark btn-block">Appliquer</button>
                    <a href="<?= shop_url(['size' => '', 'color' => '', 'max' => '', 'page' => '']) ?>" class="btn btn-outline btn-block">Réinitialiser</a>
                </div>
            </form>
        </aside>

        <div class="shop-main">
            <div class="shop-toolbar">
                <button class="btn btn-outline btn-sm filters-toggle" data-open-filters>
                    <svg viewBox="0 0 24 24" width="16"><path d="M4 6h16M7 12h10M10 18h4"/></svg> Filtres
                </button>
                <span class="muted"><?= $total ?> produit<?= $total > 1 ? 's' : '' ?></span>
                <form method="get" class="sort-form">
                    <?php foreach ($_GET as $k => $v): if ($k !== 'sort' && $k !== 'page' && is_string($v)): ?>
                        <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
                    <?php endif; endforeach; ?>
                    <select name="sort" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Trier">
                        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Nouveautés</option>
                        <option value="best" <?= $sort === 'best' ? 'selected' : '' ?>>Meilleures ventes</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                        <option value="promo" <?= $sort === 'promo' ? 'selected' : '' ?>>Meilleures réductions</option>
                    </select>
                </form>
            </div>

            <?php if ($size || $color || $maxPrice): ?>
                <div class="active-filters">
                    <?php if ($size): ?><a href="<?= shop_url(['size' => '', 'page' => '']) ?>" class="chip active">Taille <?= e($size) ?> ✕</a><?php endif; ?>
                    <?php if ($color): ?><a href="<?= shop_url(['color' => '', 'page' => '']) ?>" class="chip active"><?= e($color) ?> ✕</a><?php endif; ?>
                    <?php if ($maxPrice): ?><a href="<?= shop_url(['max' => '', 'page' => '']) ?>" class="chip active">≤ <?= price($maxPrice) ?> ✕</a><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($products): ?>
                <div class="product-grid">
                    <?php foreach ($products as $p) echo product_card($p); ?>
                </div>
                <?php if ($pages > 1): ?>
                    <nav class="pagination">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <a href="<?= shop_url(['page' => $i]) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Aucun produit trouvé</h3>
                    <p>Essayez d'autres filtres ou parcourez toute la boutique.</p>
                    <a href="<?= url('shop.php') ?>" class="btn btn-dark">Voir toute la boutique</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div><!-- /swapRoot -->

<?php require __DIR__ . '/includes/footer.php'; ?>
