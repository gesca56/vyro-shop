<?php
require __DIR__ . '/_layout.php';
require_admin();

$types = ['percent' => 'Pourcentage (-x %)', 'fixed' => 'Montant fixe (FCFA)', 'free_shipping' => 'Livraison gratuite', 'bogo50' => '1 acheté = 1 à -50 %'];
$edit = isset($_GET['edit']) ? q('SELECT * FROM promo_codes WHERE id = ?', [(int)$_GET['edit']])->fetch() : null;

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        q('DELETE FROM promo_codes WHERE id = ?', [$id]);
        flash('success', 'Code supprimé.');
    } elseif ($action === 'toggle') {
        q('UPDATE promo_codes SET is_active = 1 - is_active WHERE id = ?', [$id]);
    } elseif ($action === 'save') {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['code'] ?? ''));
        $type = array_key_exists($_POST['type'] ?? '', $types) ? $_POST['type'] : 'percent';
        $value = (int)($_POST['value'] ?? 0);
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        if ($code === '') {
            flash('error', 'Le code est requis.');
        } elseif ($type === 'percent' && ($value < 1 || $value > 90)) {
            flash('error', 'Le pourcentage doit être entre 1 et 90.');
        } elseif (q('SELECT 1 FROM promo_codes WHERE code = ? AND id <> ?', [$code, $id])->fetchColumn()) {
            flash('error', 'Ce code existe déjà.');
        } else {
            if ($featured) q('UPDATE promo_codes SET is_featured = 0');
            $vals = [$code, $type, $value, (int)($_POST['min_amount'] ?? 0), trim($_POST['label'] ?? '') ?: null, trim($_POST['description'] ?? '') ?: null,
                isset($_POST['is_active']) ? 1 : 0, $featured, ($_POST['usage_limit'] ?? '') !== '' ? (int)$_POST['usage_limit'] : null,
                $_POST['starts_at'] ?: null, $_POST['ends_at'] ?: null];
            if ($id) {
                q('UPDATE promo_codes SET code=?, type=?, value=?, min_amount=?, label=?, description=?, is_active=?, is_featured=?, usage_limit=?, starts_at=?, ends_at=? WHERE id=?', [...$vals, $id]);
            } else {
                q('INSERT INTO promo_codes (code,type,value,min_amount,label,description,is_active,is_featured,usage_limit,starts_at,ends_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)', $vals);
            }
            flash('success', 'Code ' . $code . ' enregistré.');
            redirect('admin/promos.php');
        }
    }
    redirect('admin/promos.php' . ($id && $action === 'save' ? '?edit=' . $id : ''));
}

$promos = q('SELECT * FROM promo_codes ORDER BY is_featured DESC, created_at DESC')->fetchAll();
$f = $edit ?: ['id' => 0, 'code' => '', 'type' => 'percent', 'value' => 10, 'min_amount' => 0, 'label' => '', 'description' => '', 'is_active' => 1, 'is_featured' => 0, 'usage_limit' => '', 'starts_at' => '', 'ends_at' => ''];

admin_header('Promotions', 'promos');
?>
<div class="admin-grid wide">
    <section class="panel table-wrap">
        <h2 class="h3">Codes promo</h2>
        <table class="table">
            <thead><tr><th>Code</th><th>Réduction</th><th>Conditions</th><th>Utilisations</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($promos as $p): $expired = $p['ends_at'] && $p['ends_at'] < date('Y-m-d'); ?>
                <tr>
                    <td><b class="code"><?= e($p['code']) ?></b><?php if ($p['is_featured']): ?> <span class="badge badge-new">EN AVANT</span><?php endif; ?><br><small class="muted"><?= e($p['label']) ?></small></td>
                    <td><?= e(promo_label($p)) ?></td>
                    <td class="small"><?= $p['min_amount'] ? 'Min. ' . price($p['min_amount']) . '<br>' : '' ?><?= $p['ends_at'] ? 'Jusqu\'au ' . time_fr($p['ends_at']) : 'Sans limite de date' ?></td>
                    <td><?= (int)$p['used_count'] ?><?= $p['usage_limit'] !== null ? ' / ' . (int)$p['usage_limit'] : '' ?></td>
                    <td>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="status-pill <?= $p['is_active'] && !$expired ? 'ok' : '' ?>" style="border:0"><?= $expired ? 'Expiré' : ($p['is_active'] ? 'Actif' : 'Inactif') ?></button>
                        </form>
                    </td>
                    <td class="actions">
                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                        <form method="post" data-confirm="Supprimer le code <?= e($p['code']) ?> ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="link-btn danger">Supprimer</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <form method="post" class="panel form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <h2 class="h3"><?= $edit ? 'Modifier ' . e($f['code']) : 'Créer un code promo' ?></h2>
        <label class="field">Code *<input name="code" value="<?= e($f['code']) ?>" placeholder="VYRO20" required style="text-transform:uppercase"></label>
        <div class="grid-2">
            <label class="field">Type
                <select name="type">
                    <?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $f['type'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="field">Valeur (% ou FCFA)<input name="value" type="number" min="0" value="<?= e($f['value']) ?>"></label>
            <label class="field">Achat minimum (FCFA)<input name="min_amount" type="number" min="0" step="1000" value="<?= e($f['min_amount']) ?>"></label>
            <label class="field">Limite d'utilisations<input name="usage_limit" type="number" min="1" value="<?= e($f['usage_limit']) ?>" placeholder="Illimité"></label>
            <label class="field">Début<input name="starts_at" type="date" value="<?= e($f['starts_at']) ?>"></label>
            <label class="field">Fin<input name="ends_at" type="date" value="<?= e($f['ends_at']) ?>"></label>
        </div>
        <label class="field">Nom de l'opération<input name="label" value="<?= e($f['label']) ?>" placeholder="DROP WEEKEND"></label>
        <label class="field">Message affiché<input name="description" value="<?= e($f['description']) ?>" placeholder="-20 % sur toute la collection"></label>
        <label class="check"><input type="checkbox" name="is_active" <?= $f['is_active'] ? 'checked' : '' ?>> Actif</label>
        <label class="check"><input type="checkbox" name="is_featured" <?= $f['is_featured'] ? 'checked' : '' ?>> Mettre en avant sur l'accueil (bannière + barre promo)</label>
        <button class="btn btn-accent btn-block">Enregistrer</button>
        <?php if ($edit): ?><a href="promos.php" class="btn btn-ghost btn-block">Annuler</a><?php endif; ?>
        <p class="muted small">Pour une promo sur un produit précis (prix barré), renseignez « Prix avant réduction » dans la fiche produit.</p>
    </form>
</div>
<?php admin_footer(); ?>
