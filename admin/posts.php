<?php
require __DIR__ . '/_layout.php';
require_admin();

function delete_post_files(array $ids): void
{
    if (!$ids) return;
    $in = implode(',', array_fill(0, count($ids), '?'));
    foreach (q("SELECT cover FROM posts WHERE id IN ($in) AND cover IS NOT NULL", $ids)->fetchAll(PDO::FETCH_COLUMN) as $c) {
        @unlink(POST_UPLOAD_DIR . $c);
    }
}

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [$_POST['id'] ?? 0]))));

    if (!$ids) {
        flash('error', 'Aucun article sélectionné.');
    } else {
        $in = implode(',', array_fill(0, count($ids), '?'));
        switch ($action) {
            case 'publish':
                q("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id IN ($in)", $ids);
                flash('success', count($ids) . ' article(s) publié(s).');
                break;
            case 'draft':
                q("UPDATE posts SET status = 'draft' WHERE id IN ($in)", $ids);
                flash('success', count($ids) . ' article(s) repassé(s) en brouillon.');
                break;
            case 'feature':
                q('UPDATE posts SET is_featured = 1 - is_featured WHERE id IN (' . $in . ')', $ids);
                flash('success', 'Mise en avant mise à jour.');
                break;
            case 'duplicate':
                foreach ($ids as $id) {
                    $p = q('SELECT * FROM posts WHERE id = ?', [$id])->fetch();
                    if (!$p) continue;
                    q("INSERT INTO posts (title, slug, category, excerpt, content, visual, visual_color, product_ids, status)
                       VALUES (?,?,?,?,?,?,?,?, 'draft')",
                        [$p['title'] . ' (copie)', $p['slug'] . '-copie-' . substr(uniqid(), -4), $p['category'], $p['excerpt'], $p['content'], $p['visual'], $p['visual_color'], $p['product_ids']]);
                }
                flash('success', 'Article dupliqué en brouillon.');
                break;
            case 'delete':
                delete_post_files($ids);
                q("DELETE FROM posts WHERE id IN ($in)", $ids);
                flash('success', count($ids) . ' article(s) supprimé(s).');
                break;
        }
    }
    redirect('admin/posts.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'status', 'cat']))));
}

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$cat = $_GET['cat'] ?? '';
$where = ['1'];
$params = [];
if ($search !== '') { $where[] = '(title LIKE ? OR content LIKE ?)'; array_push($params, "%$search%", "%$search%"); }
if (in_array($status, ['draft', 'published'], true)) { $where[] = 'status = ?'; $params[] = $status; }
if (in_array($cat, post_categories(), true)) { $where[] = 'category = ?'; $params[] = $cat; }
$posts = q('SELECT * FROM posts WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(published_at, created_at) DESC', $params)->fetchAll();
$counts = q('SELECT status, COUNT(*) FROM posts GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

admin_header('Journal', 'posts');
?>
<div class="toolbar">
    <form method="get" class="toolbar-filters">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Rechercher un article…">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tous (<?= array_sum($counts) ?>)</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publiés (<?= (int)($counts['published'] ?? 0) ?>)</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillons (<?= (int)($counts['draft'] ?? 0) ?>)</option>
        </select>
        <select name="cat" onchange="this.form.submit()">
            <option value="">Toutes catégories</option>
            <?php foreach (post_categories() as $c): ?><option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select>
    </form>
    <a href="post-edit.php" class="btn btn-accent">+ Nouvel article</a>
</div>

<form method="post" id="bulkForm" data-bulk>
    <?= csrf_field() ?>
    <div class="bulk-bar" hidden>
        <span><b data-bulk-count>0</b> sélectionné(s)</span>
        <button name="action" value="publish" class="btn btn-sm btn-dark">Publier</button>
        <button name="action" value="draft" class="btn btn-sm btn-outline">Brouillon</button>
        <button name="action" value="duplicate" class="btn btn-sm btn-outline">Dupliquer</button>
        <button name="action" value="delete" class="btn btn-sm btn-outline danger-btn" data-confirm-click="Supprimer définitivement les articles sélectionnés ?">Supprimer</button>
    </div>

    <div class="panel table-wrap">
        <table class="table responsive">
            <thead><tr><th class="col-check"><input type="checkbox" data-check-all aria-label="Tout sélectionner"></th><th></th><th>Article</th><th>Catégorie</th><th>Statut</th><th>Vues</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($posts as $p): $scheduled = $p['status'] === 'published' && strtotime((string)$p['published_at']) > time(); ?>
                <tr>
                    <td class="col-check"><input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" aria-label="Sélectionner"></td>
                    <td class="col-thumb"><img src="<?= e(post_cover($p)) ?>" alt="" class="tbl-thumb wide"></td>
                    <td data-label="Article">
                        <a href="post-edit.php?id=<?= (int)$p['id'] ?>"><b><?= e($p['title']) ?></b></a>
                        <?php if ($p['is_featured']): ?><span class="badge badge-new">À LA UNE</span><?php endif; ?>
                        <br><small class="muted"><?= $p['published_at'] ? time_fr($p['published_at'], true) : 'Créé le ' . time_fr($p['created_at']) ?> · <?= reading_time($p['content']) ?> min</small>
                    </td>
                    <td data-label="Catégorie"><?= e($p['category']) ?></td>
                    <td data-label="Statut"><span class="status-pill <?= $p['status'] === 'published' ? ($scheduled ? 'wait' : 'ok') : '' ?>"><?= $p['status'] === 'published' ? ($scheduled ? 'Programmé' : 'Publié') : 'Brouillon' ?></span></td>
                    <td data-label="Vues"><?= (int)$p['views'] ?></td>
                    <td class="actions">
                        <a href="post-edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                        <a href="<?= url('article.php?slug=' . urlencode($p['slug']) . '&preview=1') ?>" target="_blank" class="link-btn">Voir ↗</a>
                        <button name="action" value="feature" formaction="posts.php" class="link-btn" onclick="this.form.querySelectorAll('[name=&quot;ids[]&quot;]').forEach(c=>c.checked=c.value==='<?= (int)$p['id'] ?>')"><?= $p['is_featured'] ? 'Retirer la une' : 'Mettre à la une' ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$posts): ?><tr><td colspan="7" class="center muted">Aucun article. <a href="post-edit.php">Écrire le premier →</a></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</form>
<?php admin_footer(); ?>
