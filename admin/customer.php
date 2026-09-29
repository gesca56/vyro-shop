<?php
require __DIR__ . '/_layout.php';
require_admin();

$c = q('SELECT * FROM users WHERE id = ?', [(int)($_GET['id'] ?? 0)])->fetch();
if (!$c) redirect('admin/customers.php');
$orders = q('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', [$c['id']])->fetchAll();
$addresses = q('SELECT * FROM addresses WHERE user_id = ?', [$c['id']])->fetchAll();
$favs = q('SELECT p.name FROM favorites f JOIN products p ON p.id = f.product_id WHERE f.user_id = ?', [$c['id']])->fetchAll(PDO::FETCH_COLUMN);
$spent = array_sum(array_map(fn($o) => $o['status'] !== 'cancelled' && $o['payment_status'] === 'paid' ? $o['total'] : 0, $orders));

admin_header($c['first_name'] . ' ' . $c['last_name'], 'customers');
?>
<a href="customers.php" class="link-arrow">← Retour aux clients</a>
<div class="kpi-grid" style="margin-top:16px">
    <div class="kpi"><span>Commandes</span><strong><?= count($orders) ?></strong></div>
    <div class="kpi"><span>Total dépensé</span><strong><?= price($spent) ?></strong></div>
    <div class="kpi"><span>Panier moyen</span><strong><?= price(count($orders) ? $spent / max(1, count(array_filter($orders, fn($o) => $o['payment_status'] === 'paid' && $o['status'] !== 'cancelled'))) : 0) ?></strong></div>
    <div class="kpi"><span>Client depuis</span><strong style="font-size:20px"><?= time_fr($c['created_at']) ?></strong></div>
</div>
<div class="admin-grid wide">
    <section class="panel table-wrap">
        <h2 class="h3">Historique des commandes</h2>
        <table class="table">
            <thead><tr><th>N°</th><th>Date</th><th>Total</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr class="clickable" onclick="location='order.php?id=<?= (int)$o['id'] ?>'">
                    <td><b><?= e($o['number']) ?></b></td><td><?= time_fr($o['created_at']) ?></td><td><?= price($o['total']) ?></td>
                    <td><span class="status-pill st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="4" class="muted center">Aucune commande.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
    <section class="panel">
        <h2 class="h3">Coordonnées</h2>
        <p><?= e($c['email']) ?><br><?= e($c['phone']) ?></p>
        <h2 class="h3">Adresses</h2>
        <?php foreach ($addresses as $a): ?><p class="small"><b><?= e($a['label']) ?></b><br><?= e($a['address']) ?>, <?= e(trim($a['commune'] . ' ' . $a['city'])) ?></p><?php endforeach; ?>
        <?php if (!$addresses): ?><p class="muted small">Aucune adresse enregistrée.</p><?php endif; ?>
        <h2 class="h3">Favoris</h2>
        <p class="small"><?= $favs ? e(implode(', ', $favs)) : '<span class="muted">Aucun</span>' ?></p>
    </section>
</div>
<?php admin_footer(); ?>
