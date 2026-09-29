<?php
require __DIR__ . '/_layout.php';
require_admin();

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    match ($_POST['action'] ?? '') {
        'toggle' => q('UPDATE reviews SET approved = 1 - approved WHERE id = ?', [$id]),
        'verify' => q('UPDATE reviews SET verified = 1 - verified WHERE id = ?', [$id]),
        'delete' => q('DELETE FROM reviews WHERE id = ?', [$id]),
        default => null,
    };
    flash('success', 'Avis mis à jour.');
    redirect('admin/reviews.php');
}

$reviews = q('SELECT r.*, p.name product FROM reviews r LEFT JOIN products p ON p.id = r.product_id ORDER BY r.created_at DESC')->fetchAll();
admin_header('Avis clients', 'reviews');
?>
<div class="panel table-wrap">
    <table class="table">
        <thead><tr><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $r): ?>
            <tr>
                <td><b><?= e($r['name']) ?></b><br><small class="muted"><?= time_fr($r['created_at']) ?></small></td>
                <td class="small"><?= e($r['product'] ?? '—') ?></td>
                <td><?= stars((float)$r['rating'], 'sm') ?></td>
                <td class="small" style="max-width:340px"><?= e($r['comment']) ?></td>
                <td>
                    <span class="status-pill <?= $r['approved'] ? 'ok' : 'wait' ?>"><?= $r['approved'] ? 'Publié' : 'Masqué' ?></span>
                    <?php if ($r['verified']): ?><span class="verified">✓ Achat vérifié</span><?php endif; ?>
                </td>
                <td class="actions">
                    <?php foreach (['toggle' => $r['approved'] ? 'Masquer' : 'Publier', 'verify' => $r['verified'] ? 'Retirer « vérifié »' : 'Marquer vérifié'] as $a => $l): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $a ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="link-btn"><?= $l ?></button></form>
                    <?php endforeach; ?>
                    <form method="post" data-confirm="Supprimer cet avis ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="link-btn danger">Supprimer</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
