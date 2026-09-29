<?php
require __DIR__ . '/_layout.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$post = $id ? q('SELECT * FROM posts WHERE id = ?', [$id])->fetch() : null;
if ($id && !$post) redirect('admin/posts.php');

$visuals = ['tee' => 'T-shirt', 'hoodie' => 'Hoodie', 'sweat' => 'Sweat', 'pants' => 'Pantalon', 'set' => 'Ensemble', 'jacket' => 'Veste',
    'sneaker' => 'Sneaker', 'runner' => 'Running', 'lifestyle' => 'Lifestyle', 'bag' => 'Sac', 'cap' => 'Casquette', 'jewelry' => 'Bijou', 'glasses' => 'Lunettes'];

$f = $post ?: ['title' => '', 'slug' => '', 'category' => 'News', 'excerpt' => '', 'content' => '', 'cover' => null, 'visual' => 'hoodie',
    'visual_color' => 'Noir', 'product_ids' => '', 'status' => 'draft', 'is_featured' => 0, 'published_at' => null, 'views' => 0];
$errors = [];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $id) {
        if ($post['cover']) @unlink(POST_UPLOAD_DIR . $post['cover']);
        q('DELETE FROM posts WHERE id = ?', [$id]);
        flash('success', 'Article supprimé.');
        redirect('admin/posts.php');
    }

    $f['title'] = trim($_POST['title'] ?? '');
    $f['slug'] = slugify(trim($_POST['slug'] ?? '') ?: $f['title']);
    $f['category'] = in_array($_POST['category'] ?? '', post_categories(), true) ? $_POST['category'] : 'News';
    $f['excerpt'] = trim($_POST['excerpt'] ?? '') ?: null;
    $f['content'] = trim($_POST['content'] ?? '');
    $f['visual'] = array_key_exists($_POST['visual'] ?? '', $visuals) ? $_POST['visual'] : 'hoodie';
    $f['visual_color'] = array_key_exists($_POST['visual_color'] ?? '', color_map()) ? $_POST['visual_color'] : 'Noir';
    $f['product_ids'] = implode(',', array_unique(array_filter(array_map('intval', (array)($_POST['products'] ?? []))))) ?: null;
    $f['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $f['status'] = $action === 'publish' ? 'published' : ($action === 'draft' ? 'draft' : (($_POST['status'] ?? '') === 'published' ? 'published' : 'draft'));
    $pub = trim($_POST['published_at'] ?? '');
    $f['published_at'] = $pub ? date('Y-m-d H:i:s', strtotime($pub)) : ($f['status'] === 'published' ? date('Y-m-d H:i:s') : null);

    if ($f['title'] === '') $errors[] = 'Le titre est requis.';
    if (mb_strlen($f['content']) < 20) $errors[] = 'Le contenu doit faire au moins 20 caractères.';
    if (q('SELECT 1 FROM posts WHERE slug = ? AND id <> ?', [$f['slug'], $id])->fetchColumn()) $f['slug'] .= '-' . substr(uniqid(), -4);

    // Couverture
    if (isset($_POST['remove_cover']) && $f['cover']) {
        @unlink(POST_UPLOAD_DIR . $f['cover']);
        $f['cover'] = null;
    }
    if (!$errors && !empty($_FILES['cover']['tmp_name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $name = save_uploaded_image($_FILES['cover']['tmp_name'], $_FILES['cover']['size'], POST_UPLOAD_DIR, $f['slug']);
        if ($name) {
            if ($f['cover']) @unlink(POST_UPLOAD_DIR . $f['cover']);
            $f['cover'] = $name;
        } else {
            $errors[] = 'Image de couverture refusée (JPG, PNG ou WEBP, 5 Mo max).';
        }
    }

    if (!$errors) {
        $vals = [$f['title'], $f['slug'], $f['category'], $f['excerpt'], $f['content'], $f['cover'], $f['visual'], $f['visual_color'],
            $f['product_ids'], $f['status'], $f['is_featured'], $f['published_at']];
        if ($id) {
            q('UPDATE posts SET title=?, slug=?, category=?, excerpt=?, content=?, cover=?, visual=?, visual_color=?, product_ids=?, status=?, is_featured=?, published_at=? WHERE id=?', [...$vals, $id]);
        } else {
            q('INSERT INTO posts (title, slug, category, excerpt, content, cover, visual, visual_color, product_ids, status, is_featured, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', $vals);
            $id = (int)db()->lastInsertId();
        }
        flash('success', $f['status'] === 'published' ? 'Article publié.' : 'Brouillon enregistré.');
        redirect('admin/post-edit.php?id=' . $id);
    }
}

$allProducts = q('SELECT id, name, price FROM products ORDER BY name')->fetchAll();
$selected = array_map('intval', csv_list($f['product_ids']));

admin_header($id ? 'Modifier l\'article' : 'Nouvel article', 'posts');
?>
<a href="posts.php" class="link-arrow">← Retour au Journal</a>
<?php if ($errors): ?><div class="alert alert-error" style="margin-top:16px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form" id="postForm">
    <?= csrf_field() ?>
    <input type="hidden" name="status" value="<?= e($f['status']) ?>">
    <button name="action" value="save" class="hp" aria-hidden="true" tabindex="-1"></button><!-- touche Entrée : enregistre sans changer le statut -->
    <div class="admin-grid wide">
        <div>
            <section class="panel form">
                <label class="field">Titre *<input name="title" value="<?= e($f['title']) ?>" required maxlength="190" class="input-lg" placeholder="Ex : New Drop — ce qu'il faut savoir"></label>
                <label class="field">Chapeau (résumé affiché dans les listes)<textarea name="excerpt" rows="2" maxlength="300"><?= e($f['excerpt']) ?></textarea></label>

                <div class="field">
                    <div class="editor-head">
                        <span>Contenu *</span>
                        <div class="editor-tabs" role="tablist">
                            <button type="button" class="active" data-editor-tab="write">Écrire</button>
                            <button type="button" data-editor-tab="preview">Aperçu</button>
                        </div>
                    </div>
                    <div class="editor-toolbar" data-editor-toolbar>
                        <button type="button" data-md="h2" title="Titre">H2</button>
                        <button type="button" data-md="h3" title="Sous-titre">H3</button>
                        <button type="button" data-md="bold" title="Gras"><b>G</b></button>
                        <button type="button" data-md="italic" title="Italique"><i>I</i></button>
                        <button type="button" data-md="ul" title="Liste">• Liste</button>
                        <button type="button" data-md="ol" title="Liste numérotée">1. Liste</button>
                        <button type="button" data-md="quote" title="Citation">❝</button>
                        <button type="button" data-md="link" title="Lien">🔗 Lien</button>
                    </div>
                    <textarea name="content" id="postContent" rows="18" required class="editor-area"><?= e($f['content']) ?></textarea>
                    <div class="editor-preview prose article-content" id="postPreview" hidden></div>
                    <small class="muted">Mise en forme : <code>## Titre</code>, <code>**gras**</code>, <code>*italique*</code>, <code>- liste</code>, <code>&gt; citation</code>, <code>[texte](shop.php?cat=sneakers)</code>. Laissez une ligne vide entre les paragraphes.</small>
                </div>
            </section>

            <section class="panel form">
                <h2 class="h3">Produits associés <small class="muted">(« Shopper le look » en bas de l'article)</small></h2>
                <input type="search" placeholder="Filtrer les produits…" data-filter-list="#productPick">
                <div class="pick-list" id="productPick">
                    <?php foreach ($allProducts as $p): ?>
                        <label><input type="checkbox" name="products[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $selected, true) ? 'checked' : '' ?>> <span><?= e($p['name']) ?></span> <small class="muted"><?= price($p['price']) ?></small></label>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div>
            <section class="panel form">
                <h2 class="h3">Publication</h2>
                <p class="small">Statut : <span class="status-pill <?= $f['status'] === 'published' ? 'ok' : '' ?>"><?= $f['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
                    <?php if ($id): ?> · <?= (int)$f['views'] ?> vue(s)<?php endif; ?></p>
                <label class="field">Catégorie
                    <select name="category"><?php foreach (post_categories() as $c): ?><option <?= $f['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">Date de publication <small class="muted">(future = programmé)</small>
                    <input type="datetime-local" name="published_at" value="<?= $f['published_at'] ? date('Y-m-d\TH:i', strtotime($f['published_at'])) : '' ?>">
                </label>
                <label class="field">Slug (URL)<input name="slug" value="<?= e($f['slug']) ?>" placeholder="généré depuis le titre"></label>
                <label class="check"><input type="checkbox" name="is_featured" <?= $f['is_featured'] ? 'checked' : '' ?>> À la une du Journal</label>
                <div class="form-actions">
                    <button name="action" value="publish" class="btn btn-accent btn-block"><?= $f['status'] === 'published' ? 'Mettre à jour' : 'Publier' ?></button>
                    <button name="action" value="draft" class="btn btn-outline btn-block" formnovalidate><?= $f['status'] === 'published' ? 'Dépublier (brouillon)' : 'Enregistrer le brouillon' ?></button>
                    <?php if ($id): ?>
                        <a href="<?= url('article.php?slug=' . urlencode($f['slug']) . '&preview=1') ?>" target="_blank" class="btn btn-ghost btn-block">Aperçu sur le site ↗</a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel form">
                <h2 class="h3">Image de couverture</h2>
                <div class="cover-preview"><img src="<?= e(post_cover($f + ['cover' => $f['cover']])) ?>" alt="" id="coverPreview"></div>
                <?php if ($f['cover']): ?><label class="check"><input type="checkbox" name="remove_cover"> Retirer l'image (visuel généré à la place)</label><?php endif; ?>
                <label class="field">Nouvelle image (JPG, PNG, WEBP — 5 Mo max)<input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-preview="#coverPreview"></label>
                <div class="grid-2">
                    <label class="field">Visuel de secours
                        <select name="visual"><?php foreach ($visuals as $k => $l): ?><option value="<?= $k ?>" <?= $f['visual'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
                    </label>
                    <label class="field">Couleur
                        <select name="visual_color"><?php foreach (array_keys(color_map()) as $c): ?><option <?= $f['visual_color'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
                    </label>
                </div>
            </section>

            <?php if ($id): ?>
                <section class="panel">
                    <button type="submit" form="deletePost" class="btn btn-outline btn-block danger-btn">Supprimer l'article</button>
                </section>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($id): ?>
    <form method="post" id="deletePost" data-confirm="Supprimer définitivement cet article ?" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
    </form>
<?php endif; ?>

<script src="<?= asset('js/admin-editor.js') ?>"></script>
<?php admin_footer(); ?>
