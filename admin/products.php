<?php
require __DIR__ . '/_layout.php';
require_admin();

/** Duplique un produit (en masqué) avec ses photos */
function duplicate_product(int $id): ?int
{
    $p = q('SELECT * FROM products WHERE id = ?', [$id])->fetch();
    if (!$p) return null;
    q('INSERT INTO products (category_id, collection_id, name, slug, description, price, old_price, sizes, colors, stock, visual, video_url, is_new, is_featured, is_active)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)', [
        $p['category_id'], $p['collection_id'], $p['name'] . ' (copie)', $p['slug'] . '-copie-' . substr(uniqid(), -4), $p['description'], $p['price'],
        $p['old_price'], $p['sizes'], $p['colors'], $p['stock'], $p['visual'], $p['video_url'], $p['is_new'], $p['is_featured'],
    ]);
    $newId = (int)db()->lastInsertId();
    foreach (q('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order', [$id])->fetchAll() as $img) {
        $ext = pathinfo($img['path'], PATHINFO_EXTENSION);
        $name = slugify($p['name']) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (@copy(UPLOAD_DIR . $img['path'], UPLOAD_DIR . $name)) {
            q('INSERT INTO product_images (product_id, path, sort_order) VALUES (?,?,?)', [$newId, $name, $img['sort_order']]);
        }
    }
    return $newId;
}

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [$_POST['id'] ?? 0]))));
    $back = 'admin/products.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'cat', 'stock', 'status'])));

    if ($action === 'stock') {
        q('UPDATE products SET stock = ? WHERE id = ?', [max(0, (int)$_POST['stock']), (int)$_POST['id']]);
        if (is_ajax()) json_response(['ok' => true]);
        flash('success', 'Stock mis à jour.');
        redirect($back);
    }
    if (!$ids) {
        flash('error', 'Aucun produit sélectionné.');
        redirect($back);
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    switch ($action) {
        case 'activate': q("UPDATE products SET is_active = 1 WHERE id IN ($in)", $ids); flash('success', count($ids) . ' produit(s) mis en ligne.'); break;
        case 'hide': q("UPDATE products SET is_active = 0 WHERE id IN ($in)", $ids); flash('success', count($ids) . ' produit(s) masqué(s).'); break;
        case 'toggle': q("UPDATE products SET is_active = 1 - is_active WHERE id IN ($in)", $ids); flash('success', 'Visibilité mise à jour.'); break;
        case 'new_on': q("UPDATE products SET is_new = 1 WHERE id IN ($in)", $ids); flash('success', 'Badge NEW ajouté.'); break;
        case 'new_off': q("UPDATE products SET is_new = 0 WHERE id IN ($in)", $ids); flash('success', 'Badge NEW retiré.'); break;
        case 'duplicate':
            $last = null;
            foreach ($ids as $id) $last = duplicate_product($id);
            flash('success', count($ids) . ' produit(s) dupliqué(s) — les copies sont masquées, pensez à les vérifier.');
            if (count($ids) === 1 && $last) redirect('admin/product-edit.php?id=' . $last);
            break;
        case 'delete':
            foreach (q("SELECT path FROM product_images WHERE product_id IN ($in)", $ids)->fetchAll(PDO::FETCH_COLUMN) as $path) {
                @unlink(UPLOAD_DIR . $path);
            }
            q("DELETE FROM products WHERE id IN ($in)", $ids);
            flash('success', count($ids) . ' produit(s) supprimé(s).');
            break;
    }
    redirect($back);
}

$search = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$stockF = $_GET['stock'] ?? '';
$status = $_GET['status'] ?? '';
$where = ['1'];
$params = [];
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.slug LIKE ?)'; array_push($params, "%$search%", "%$search%"); }
if ($cat) { $where[] = '(p.category_id = ? OR c.parent_id = ?)'; array_push($params, $cat, $cat); }
if ($stockF === 'low') { $where[] = 'p.stock <= ' . LOW_STOCK_THRESHOLD; }
if ($status === 'online') { $where[] = 'p.is_active = 1'; }
if ($status === 'hidden') { $where[] = 'p.is_active = 0'; }

$products = q('SELECT p.*, c.name cat FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.created_at DESC, p.id DESC', $params)->fetchAll();
$tree = categories_tree();

admin_header('Produits (' . count($products) . ')', 'products');
?>
<div class="toolbar">
    <form method="get" class="toolbar-filters">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Rechercher un produit…">
        <select name="cat" onchange="this.form.submit()">
            <option value="">Toutes catégories</option>
            <?php foreach ($tree as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $cat === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php foreach ($t['children'] as $ch): ?>
                    <option value="<?= $ch['id'] ?>" <?= $cat === (int)$ch['id'] ? 'selected' : '' ?>>— <?= e($ch['name']) ?></option>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </select>
        <select name="status" onchange="this.form.submit()">
            <option value="">En ligne + masqués</option>
            <option value="online" <?= $status === 'online' ? 'selected' : '' ?>>En ligne</option>
            <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Masqués</option>
        </select>
        <select name="stock" onchange="this.form.submit()">
            <option value="">Tous les stocks</option>
            <option value="low" <?= $stockF === 'low' ? 'selected' : '' ?>>Stock faible (≤ <?= LOW_STOCK_THRESHOLD ?>)</option>
        </select>
    </form>
    <a href="product-edit.php" class="btn btn-accent">+ Ajouter un produit</a>
</div>

<form method="post" data-bulk id="bulkForm">
    <?= csrf_field() ?>
    <div class="bulk-bar" hidden>
        <span><b data-bulk-count>0</b> sélectionné(s)</span>
        <button name="action" value="activate" class="btn btn-sm btn-dark">Mettre en ligne</button>
        <button name="action" value="hide" class="btn btn-sm btn-outline">Masquer</button>
        <button name="action" value="new_on" class="btn btn-sm btn-outline">+ NEW</button>
        <button name="action" value="new_off" class="btn btn-sm btn-outline">− NEW</button>
        <button name="action" value="duplicate" class="btn btn-sm btn-outline">Dupliquer</button>
        <button name="action" value="delete" class="btn btn-sm btn-outline danger-btn" data-confirm-click="Supprimer définitivement les produits sélectionnés ?">Supprimer</button>
    </div>
</form>

<div class="panel table-wrap">
    <table class="table responsive">
        <thead><tr><th class="col-check"><input type="checkbox" data-check-all form="bulkForm" aria-label="Tout sélectionner"></th><th></th><th>Produit</th><th>Prix</th><th>Stock</th><th>Ventes</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td class="col-check"><input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" form="bulkForm" aria-label="Sélectionner <?= e($p['name']) ?>"></td>
                <td class="col-thumb"><img src="<?= e(product_thumb($p)) ?>" alt="" class="tbl-thumb"></td>
                <td data-label="Produit">
                    <a href="product-edit.php?id=<?= (int)$p['id'] ?>"><b><?= e($p['name']) ?></b></a>
                    <?php if ($p['is_new']): ?><span class="badge badge-new">NEW</span><?php endif; ?>
                    <?php if ($p['is_featured']): ?><span class="badge badge-out">★</span><?php endif; ?>
                    <br><small class="muted"><?= e($p['cat']) ?> · <?= e(str_replace(',', ' ', $p['sizes'])) ?></small>
                </td>
                <td data-label="Prix"><?= price($p['price']) ?><?php if ($p['old_price']): ?><br><s class="muted small"><?= price($p['old_price']) ?></s><?php endif; ?></td>
                <td data-label="Stock">
                    <form method="post" class="inline-stock" data-inline-stock>
                        <?= csrf_field() ?><input type="hidden" name="action" value="stock"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0" inputmode="numeric" aria-label="Stock" class="<?= $p['stock'] <= 0 ? 'is-out' : ($p['stock'] <= LOW_STOCK_THRESHOLD ? 'is-low' : '') ?>">
                    </form>
                </td>
                <td data-label="Ventes"><?= (int)$p['sales_count'] ?></td>
                <td data-label="Statut">
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="status-pill <?= $p['is_active'] ? 'ok' : '' ?>" style="border:0" title="Cliquer pour changer"><?= $p['is_active'] ? 'En ligne' : 'Masqué' ?></button>
                    </form>
                </td>
                <td class="actions">
                    <a href="product-edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                    <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="link-btn">Voir ↗</a>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="link-btn">Dupliquer</button></form>
                    <form method="post" data-confirm="Supprimer définitivement « <?= e($p['name']) ?> » ?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="link-btn danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="8" class="center muted">Aucun produit. <a href="product-edit.php">Ajouter le premier →</a></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
