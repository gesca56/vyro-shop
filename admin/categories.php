<?php
require __DIR__ . '/_layout.php';
require_admin();

$themes = ['dark' => 'Noir', 'light' => 'Clair', 'accent' => 'Lime', 'sand' => 'Sable'];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = slugify(trim($_POST['slug'] ?? '') ?: $name);
    $sort = (int)($_POST['sort_order'] ?? 0);

    switch ($action) {
        case 'cat_save':
            $parent = (int)($_POST['parent_id'] ?? 0) ?: null;
            if ($name === '') { flash('error', 'Le nom est requis.'); break; }
            if ($parent === $id) $parent = null;
            if (q('SELECT 1 FROM categories WHERE slug = ? AND id <> ?', [$slug, $id])->fetchColumn()) { flash('error', 'Ce slug existe déjà.'); break; }
            if ($id) {
                // Une catégorie principale qui a des sous-catégories ne peut pas devenir sous-catégorie
                if ($parent && q('SELECT 1 FROM categories WHERE parent_id = ?', [$id])->fetchColumn()) { flash('error', 'Cette catégorie a des sous-catégories : elle doit rester principale.'); break; }
                q('UPDATE categories SET name = ?, slug = ?, parent_id = ?, sort_order = ? WHERE id = ?', [$name, $slug, $parent, $sort, $id]);
            } else {
                q('INSERT INTO categories (name, slug, parent_id, sort_order) VALUES (?,?,?,?)', [$name, $slug, $parent, $sort]);
            }
            flash('success', 'Catégorie « ' . $name . ' » enregistrée.');
            break;

        case 'cat_delete':
            $n = (int)q('SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE c.id = ? OR c.parent_id = ?', [$id, $id])->fetchColumn();
            if ($n) {
                flash('error', "Impossible : $n produit(s) utilisent cette catégorie. Déplacez-les d'abord.");
            } else {
                q('DELETE FROM categories WHERE id = ?', [$id]);
                flash('success', 'Catégorie supprimée.');
            }
            break;

        case 'col_save':
            if ($name === '') { flash('error', 'Le nom est requis.'); break; }
            if (q('SELECT 1 FROM collections WHERE slug = ? AND id <> ?', [$slug, $id])->fetchColumn()) { flash('error', 'Ce slug existe déjà.'); break; }
            $theme = array_key_exists($_POST['theme'] ?? '', $themes) ? $_POST['theme'] : 'dark';
            $tagline = trim($_POST['tagline'] ?? '') ?: null;
            if ($id) {
                q('UPDATE collections SET name = ?, slug = ?, tagline = ?, theme = ?, sort_order = ? WHERE id = ?', [$name, $slug, $tagline, $theme, $sort, $id]);
            } else {
                q('INSERT INTO collections (name, slug, tagline, theme, sort_order) VALUES (?,?,?,?,?)', [$name, $slug, $tagline, $theme, $sort]);
            }
            flash('success', 'Collection « ' . $name . ' » enregistrée.');
            break;

        case 'col_delete':
            q('DELETE FROM collections WHERE id = ?', [$id]);
            flash('success', 'Collection supprimée (les produits sont conservés).');
            break;
    }
    redirect('admin/categories.php');
}

$tree = categories_tree();
$catCounts = q('SELECT category_id, COUNT(*) FROM products GROUP BY category_id')->fetchAll(PDO::FETCH_KEY_PAIR);
$collections = q('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id) n FROM collections c ORDER BY sort_order, name')->fetchAll();
$editCat = isset($_GET['cat']) ? q('SELECT * FROM categories WHERE id = ?', [(int)$_GET['cat']])->fetch() : null;
$editCol = isset($_GET['col']) ? q('SELECT * FROM collections WHERE id = ?', [(int)$_GET['col']])->fetch() : null;

admin_header('Catégories & collections', 'categories');
?>
<div class="admin-grid wide">
    <section class="panel">
        <h2 class="h3">Catégories</h2>
        <div class="cat-admin-list">
            <?php foreach ($tree as $t): $total = ($catCounts[$t['id']] ?? 0) + array_sum(array_map(fn($c) => $catCounts[$c['id']] ?? 0, $t['children'])); ?>
                <div class="cat-admin-group">
                    <div class="cat-admin-row main">
                        <b><?= e($t['name']) ?></b> <small class="muted">/<?= e($t['slug']) ?> · <?= $total ?> produit(s)</small>
                        <span class="actions">
                            <a href="?cat=<?= $t['id'] ?>#catForm" class="link-btn">Modifier</a>
                            <form method="post" data-confirm="Supprimer la catégorie « <?= e($t['name']) ?> » et ses sous-catégories vides ?"><?= csrf_field() ?><input type="hidden" name="action" value="cat_delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="link-btn danger">Supprimer</button></form>
                        </span>
                    </div>
                    <?php foreach ($t['children'] as $c): ?>
                        <div class="cat-admin-row">
                            <span>↳ <?= e($c['name']) ?> <small class="muted">/<?= e($c['slug']) ?> · <?= (int)($catCounts[$c['id']] ?? 0) ?> produit(s)</small></span>
                            <span class="actions">
                                <a href="<?= url('shop.php?cat=' . $c['slug']) ?>" target="_blank" class="link-btn">Voir ↗</a>
                                <a href="?cat=<?= $c['id'] ?>#catForm" class="link-btn">Modifier</a>
                                <form method="post" data-confirm="Supprimer « <?= e($c['name']) ?> » ?"><?= csrf_field() ?><input type="hidden" name="action" value="cat_delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="link-btn danger">Supprimer</button></form>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="h3">Collections</h2>
        <div class="table-wrap">
            <table class="table responsive">
                <thead><tr><th>Collection</th><th>Thème</th><th>Produits</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($collections as $c): ?>
                    <tr>
                        <td data-label="Collection"><b><?= e($c['name']) ?></b><br><small class="muted"><?= e($c['tagline']) ?></small></td>
                        <td data-label="Thème"><span class="theme-dot theme-<?= e($c['theme']) ?>"></span> <?= e($themes[$c['theme']] ?? $c['theme']) ?></td>
                        <td data-label="Produits"><?= (int)$c['n'] ?></td>
                        <td class="actions">
                            <a href="?col=<?= $c['id'] ?>#colForm" class="btn btn-outline btn-sm">Modifier</a>
                            <form method="post" data-confirm="Supprimer la collection « <?= e($c['name']) ?> » ? Les produits restent en ligne."><?= csrf_field() ?><input type="hidden" name="action" value="col_delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="link-btn danger">Supprimer</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div>
        <form method="post" class="panel form" id="catForm">
            <?= csrf_field() ?><input type="hidden" name="action" value="cat_save"><input type="hidden" name="id" value="<?= (int)($editCat['id'] ?? 0) ?>">
            <h2 class="h3"><?= $editCat ? 'Modifier « ' . e($editCat['name']) . ' »' : 'Nouvelle catégorie' ?></h2>
            <label class="field">Nom *<input name="name" value="<?= e($editCat['name'] ?? '') ?>" required></label>
            <label class="field">Catégorie parente
                <select name="parent_id">
                    <option value="">— Aucune (catégorie principale) —</option>
                    <?php foreach ($tree as $t): if ($editCat && (int)$t['id'] === (int)$editCat['id']) continue; ?>
                        <option value="<?= $t['id'] ?>" <?= (int)($editCat['parent_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="grid-2">
                <label class="field">Slug<input name="slug" value="<?= e($editCat['slug'] ?? '') ?>" placeholder="auto"></label>
                <label class="field">Ordre<input type="number" name="sort_order" value="<?= (int)($editCat['sort_order'] ?? 0) ?>"></label>
            </div>
            <button class="btn btn-accent btn-block">Enregistrer</button>
            <?php if ($editCat): ?><a href="categories.php" class="btn btn-ghost btn-block">Annuler</a><?php endif; ?>
        </form>

        <form method="post" class="panel form" id="colForm">
            <?= csrf_field() ?><input type="hidden" name="action" value="col_save"><input type="hidden" name="id" value="<?= (int)($editCol['id'] ?? 0) ?>">
            <h2 class="h3"><?= $editCol ? 'Modifier « ' . e($editCol['name']) . ' »' : 'Nouvelle collection' ?></h2>
            <label class="field">Nom *<input name="name" value="<?= e($editCol['name'] ?? '') ?>" required></label>
            <label class="field">Accroche<input name="tagline" value="<?= e($editCol['tagline'] ?? '') ?>" placeholder="Ex : Léger, frais, prêt pour le soleil"></label>
            <div class="grid-2">
                <label class="field">Thème de la tuile
                    <select name="theme"><?php foreach ($themes as $k => $l): ?><option value="<?= $k ?>" <?= ($editCol['theme'] ?? 'dark') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">Ordre<input type="number" name="sort_order" value="<?= (int)($editCol['sort_order'] ?? 0) ?>"></label>
            </div>
            <label class="field">Slug<input name="slug" value="<?= e($editCol['slug'] ?? '') ?>" placeholder="auto"></label>
            <button class="btn btn-accent btn-block">Enregistrer</button>
            <?php if ($editCol): ?><a href="categories.php" class="btn btn-ghost btn-block">Annuler</a><?php endif; ?>
        </form>
    </div>
</div>
<?php admin_footer(); ?>
