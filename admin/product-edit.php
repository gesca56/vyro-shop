<?php
require __DIR__ . '/_layout.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$p = $id ? q('SELECT * FROM products WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) redirect('admin/products.php');

$visuals = ['tee' => 'T-shirt', 'hoodie' => 'Hoodie', 'sweat' => 'Sweat', 'pants' => 'Pantalon', 'set' => 'Ensemble', 'jacket' => 'Veste',
    'sneaker' => 'Sneaker', 'runner' => 'Running', 'lifestyle' => 'Lifestyle (montante)', 'bag' => 'Sac', 'cap' => 'Casquette', 'jewelry' => 'Bijou', 'glasses' => 'Lunettes'];
$sizePresets = ['S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', '39', '40', '41', '42', '43', '44', '45', 'Unique'];

$f = $p ?: ['name' => '', 'slug' => '', 'category_id' => '', 'collection_id' => '', 'description' => '', 'price' => '', 'old_price' => '',
    'sizes' => 'S,M,L,XL,XXL', 'colors' => 'Noir,Blanc', 'stock' => 10, 'visual' => 'tee', 'video_url' => '', 'is_new' => 1, 'is_featured' => 0, 'is_active' => 1];
$errors = [];

if (is_post()) {
    csrf_check();

    if (($_POST['action'] ?? '') === 'delete_image') {
        $img = q('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int)$_POST['image_id'], $id])->fetch();
        if ($img) {
            @unlink(UPLOAD_DIR . $img['path']);
            q('DELETE FROM product_images WHERE id = ?', [$img['id']]);
            flash('success', 'Photo supprimée.');
        }
        redirect('admin/product-edit.php?id=' . $id);
    }

    // Réordonner les photos : la première est la photo principale
    if (($_POST['action'] ?? '') === 'image_move') {
        $list = q('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$id])->fetchAll(PDO::FETCH_COLUMN);
        $pos = array_search((int)$_POST['image_id'], array_map('intval', $list), true);
        if ($pos !== false) {
            $dir = $_POST['dir'] ?? '';
            $target = $dir === 'first' ? 0 : ($dir === 'left' ? $pos - 1 : $pos + 1);
            if ($target >= 0 && $target < count($list) && $target !== $pos) {
                $moved = array_splice($list, $pos, 1);
                array_splice($list, $target, 0, $moved);
                foreach ($list as $i => $imgId) q('UPDATE product_images SET sort_order = ? WHERE id = ?', [$i + 1, $imgId]);
                flash('success', $dir === 'first' ? 'Photo principale mise à jour.' : 'Ordre des photos mis à jour.');
            }
        }
        redirect('admin/product-edit.php?id=' . $id . '#photos');
    }

    $f['name'] = trim($_POST['name'] ?? '');
    $f['slug'] = slugify(trim($_POST['slug'] ?? '') ?: $f['name']);
    $f['category_id'] = (int)($_POST['category_id'] ?? 0);
    $f['collection_id'] = (int)($_POST['collection_id'] ?? 0) ?: null;
    $f['description'] = trim($_POST['description'] ?? '');
    $f['price'] = (int)preg_replace('/\D/', '', $_POST['price'] ?? '');
    $f['old_price'] = (int)preg_replace('/\D/', '', $_POST['old_price'] ?? '') ?: null;
    $sizes = array_merge((array)($_POST['sizes'] ?? []), csv_list($_POST['sizes_extra'] ?? ''));
    $f['sizes'] = implode(',', array_unique(array_map('trim', $sizes)));
    $colors = array_merge((array)($_POST['colors'] ?? []), csv_list($_POST['colors_extra'] ?? ''));
    $f['colors'] = implode(',', array_unique(array_map('trim', $colors)));
    $f['stock'] = max(0, (int)($_POST['stock'] ?? 0));
    $f['visual'] = array_key_exists($_POST['visual'] ?? '', $visuals) ? $_POST['visual'] : 'tee';
    $f['video_url'] = trim($_POST['video_url'] ?? '') ?: null;
    $f['is_new'] = isset($_POST['is_new']) ? 1 : 0;
    $f['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $f['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($f['name'] === '') $errors[] = 'Le nom est requis.';
    if (!$f['category_id']) $errors[] = 'Choisissez une catégorie.';
    if ($f['price'] <= 0) $errors[] = 'Le prix est requis.';
    if ($f['old_price'] && $f['old_price'] <= $f['price']) $errors[] = 'Le prix avant réduction doit être supérieur au prix de vente.';
    if ($f['sizes'] === '') $errors[] = 'Indiquez au moins une taille (ou « Unique »).';
    if (q('SELECT 1 FROM products WHERE slug = ? AND id <> ?', [$f['slug'], $id])->fetchColumn()) $f['slug'] .= '-' . substr(uniqid(), -4);

    if (!$errors) {
        $vals = [$f['category_id'], $f['collection_id'], $f['name'], $f['slug'], $f['description'], $f['price'], $f['old_price'], $f['sizes'], $f['colors'],
            $f['stock'], $f['visual'], $f['video_url'], $f['is_new'], $f['is_featured'], $f['is_active']];
        if ($id) {
            q('UPDATE products SET category_id=?, collection_id=?, name=?, slug=?, description=?, price=?, old_price=?, sizes=?, colors=?, stock=?, visual=?, video_url=?, is_new=?, is_featured=?, is_active=? WHERE id=?', [...$vals, $id]);
        } else {
            q('INSERT INTO products (category_id,collection_id,name,slug,description,price,old_price,sizes,colors,stock,visual,video_url,is_new,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $vals);
            $id = (int)db()->lastInsertId();
        }

        // Upload des photos
        if (!empty($_FILES['photos']['name'][0])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $order = (int)q('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id = ?', [$id])->fetchColumn();
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                if (!isset($allowed[$mime]) || $_FILES['photos']['size'][$i] > 5 * 1024 * 1024) {
                    flash('error', 'Photo ignorée : ' . $_FILES['photos']['name'][$i] . ' (JPG/PNG/WEBP, 5 Mo max).');
                    continue;
                }
                $name = $f['slug'] . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                if (move_uploaded_file($tmp, UPLOAD_DIR . $name)) {
                    q('INSERT INTO product_images (product_id, path, sort_order) VALUES (?,?,?)', [$id, $name, ++$order]);
                }
            }
        }
        flash('success', 'Produit enregistré.');
        redirect('admin/product-edit.php?id=' . $id);
    }
}

$tree = categories_tree();
$collections = q('SELECT * FROM collections ORDER BY sort_order')->fetchAll();
$images = $id ? q('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$id])->fetchAll() : [];
$curSizes = csv_list($f['sizes']);
$curColors = csv_list($f['colors']);

admin_header($id ? 'Modifier : ' . $f['name'] : 'Nouveau produit', 'products');
?>
<a href="products.php" class="link-arrow">← Retour aux produits</a>
<?php if ($errors): ?><div class="alert alert-error" style="margin-top:16px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="admin-grid wide">
        <div>
            <section class="panel form">
                <h2 class="h3">Informations</h2>
                <label class="field">Nom du produit *<input name="name" value="<?= e($f['name']) ?>" required></label>
                <label class="field">Slug (URL)<input name="slug" value="<?= e($f['slug']) ?>" placeholder="généré automatiquement"></label>
                <label class="field">Description<textarea name="description" rows="6"><?= e($f['description']) ?></textarea></label>
                <div class="grid-2">
                    <label class="field">Catégorie *
                        <select name="category_id" required>
                            <option value="">Choisir</option>
                            <?php foreach ($tree as $t): ?>
                                <optgroup label="<?= e($t['name']) ?>">
                                    <?php foreach ($t['children'] as $ch): ?>
                                        <option value="<?= $ch['id'] ?>" <?= (int)$f['category_id'] === (int)$ch['id'] ? 'selected' : '' ?>><?= e($ch['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">Collection
                        <select name="collection_id">
                            <option value="">Aucune</option>
                            <?php foreach ($collections as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (int)$f['collection_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </section>

            <section class="panel form">
                <h2 class="h3">Prix & stock</h2>
                <div class="grid-2">
                    <label class="field">Prix de vente (FCFA) *<input name="price" type="number" min="0" step="100" value="<?= e($f['price']) ?>" required></label>
                    <label class="field">Prix avant réduction (FCFA)<input name="old_price" type="number" min="0" step="100" value="<?= e($f['old_price']) ?>" placeholder="Laisser vide si pas de promo"></label>
                    <label class="field">Stock disponible<input name="stock" type="number" min="0" value="<?= e($f['stock']) ?>"></label>
                </div>
            </section>

            <section class="panel form">
                <h2 class="h3">Tailles</h2>
                <div class="size-options">
                    <?php foreach ($sizePresets as $s): ?>
                        <label class="opt"><input type="checkbox" name="sizes[]" value="<?= e($s) ?>" <?= in_array($s, $curSizes, true) ? 'checked' : '' ?>><span><?= e($s) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <label class="field">Autres tailles (séparées par des virgules)<input name="sizes_extra" value="<?= e(implode(',', array_diff($curSizes, $sizePresets))) ?>"></label>

                <h2 class="h3">Couleurs</h2>
                <div class="color-check">
                    <?php foreach (color_map() as $name => $hex): ?>
                        <label><input type="checkbox" name="colors[]" value="<?= e($name) ?>" <?= in_array($name, $curColors, true) ? 'checked' : '' ?>><span class="swatch" style="--sw:<?= $hex ?>"></span> <?= e($name) ?></label>
                    <?php endforeach; ?>
                </div>
                <label class="field">Autres couleurs<input name="colors_extra" value="<?= e(implode(',', array_diff($curColors, array_keys(color_map())))) ?>"></label>
            </section>
        </div>

        <div>
            <section class="panel form">
                <h2 class="h3">Visibilité</h2>
                <label class="check"><input type="checkbox" name="is_active" <?= $f['is_active'] ? 'checked' : '' ?>> En ligne</label>
                <label class="check"><input type="checkbox" name="is_new" <?= $f['is_new'] ? 'checked' : '' ?>> Badge « NEW » (Nouveautés)</label>
                <label class="check"><input type="checkbox" name="is_featured" <?= $f['is_featured'] ? 'checked' : '' ?>> Incontournable (mis en avant)</label>
                <button class="btn btn-accent btn-lg btn-block">Enregistrer</button>
                <?php if ($id): ?>
                    <a href="<?= url('product.php?slug=' . urlencode($f['slug'])) ?>" target="_blank" class="btn btn-outline btn-block">Voir sur le site ↗</a>
                    <div class="grid-2">
                        <button type="submit" form="dupProduct" class="btn btn-ghost btn-sm">Dupliquer</button>
                        <button type="submit" form="delProduct" class="btn btn-ghost btn-sm danger">Supprimer</button>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel form" id="photos">
                <h2 class="h3">Photos <small class="muted">(la 1re est la photo principale)</small></h2>
                <?php if ($images): ?>
                    <div class="img-grid">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="img-item <?= $i === 0 ? 'is-main' : '' ?>">
                                <img src="<?= e(UPLOAD_URL . $img['path']) ?>" alt="">
                                <?php if ($i === 0): ?><span class="img-main-tag">Principale</span><?php endif; ?>
                                <button type="submit" form="delImg<?= $img['id'] ?>" class="img-del" aria-label="Supprimer la photo">✕</button>
                                <div class="img-tools">
                                    <?php if ($i > 0): ?>
                                        <button type="submit" form="mvImg" name="image_id" value="<?= $img['id'] ?>" onclick="this.form.dir.value='left'" aria-label="Déplacer à gauche">←</button>
                                        <button type="submit" form="mvImg" name="image_id" value="<?= $img['id'] ?>" onclick="this.form.dir.value='first'" title="Définir comme principale">★</button>
                                    <?php endif; ?>
                                    <?php if ($i < count($images) - 1): ?>
                                        <button type="submit" form="mvImg" name="image_id" value="<?= $img['id'] ?>" onclick="this.form.dir.value='right'" aria-label="Déplacer à droite">→</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted small">Aucune photo : un visuel généré est affiché en attendant.</p>
                    <img src="<?= e(visual_url($f['visual'], $curColors[0] ?? 'Noir')) ?>" alt="" class="preview-visual">
                <?php endif; ?>
                <label class="field">Ajouter des photos (JPG, PNG, WEBP — 5 Mo max)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-preview-list="#newPhotos"></label>
                <div class="img-grid new-photos" id="newPhotos"></div>
                <label class="field">Visuel de remplacement
                    <select name="visual">
                        <?php foreach ($visuals as $k => $l): ?><option value="<?= $k ?>" <?= $f['visual'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="field">Vidéo produit (URL YouTube ou .mp4)<input type="url" name="video_url" value="<?= e($f['video_url']) ?>" placeholder="https://…"></label>
            </section>
        </div>
    </div>
</form>

<?php foreach ($images as $img): ?>
    <form method="post" id="delImg<?= $img['id'] ?>" data-confirm="Supprimer cette photo ?" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="delete_image"><input type="hidden" name="image_id" value="<?= $img['id'] ?>">
    </form>
<?php endforeach; ?>
<?php if ($id): ?>
    <form method="post" id="mvImg" hidden><?= csrf_field() ?><input type="hidden" name="action" value="image_move"><input type="hidden" name="dir" value=""></form>
    <form method="post" action="products.php" id="dupProduct" hidden><?= csrf_field() ?><input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?= $id ?>"></form>
    <form method="post" action="products.php" id="delProduct" data-confirm="Supprimer définitivement ce produit et ses photos ?" hidden><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>"></form>
<?php endif; ?>
<?php admin_footer(); ?>
