<?php
require __DIR__ . '/_layout.php';
require_admin();

$search = trim($_GET['q'] ?? '');
$params = [];
$where = 'u.is_admin = 0';
if ($search !== '') {
    $where .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $params = array_fill(0, 4, "%$search%");
}
$customers = q("SELECT u.*, COUNT(o.id) n_orders,
                       COALESCE(SUM(CASE WHEN o.status <> 'cancelled' AND o.payment_status = 'paid' THEN o.total END),0) spent,
                       MAX(o.created_at) last_order
                FROM users u LEFT JOIN orders o ON o.user_id = u.id
                WHERE $where GROUP BY u.id ORDER BY u.created_at DESC", $params)->fetchAll();

admin_header('Clients', 'customers');
?>
<form method="get" class="toolbar-filters" style="margin-bottom:16px">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Nom, email, téléphone…">
    <button class="btn btn-dark">Rechercher</button>
</form>
<div class="panel table-wrap">
    <table class="table">
        <thead><tr><th>Client</th><th>Contact</th><th>Inscrit le</th><th>Commandes</th><th>Total dépensé</th><th>Dernière commande</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr class="clickable" onclick="location='customer.php?id=<?= (int)$c['id'] ?>'">
                <td><a href="customer.php?id=<?= (int)$c['id'] ?>"><b><?= e($c['first_name'] . ' ' . $c['last_name']) ?></b></a></td>
                <td><?= e($c['email']) ?><br><small class="muted"><?= e($c['phone']) ?></small></td>
                <td><?= time_fr($c['created_at']) ?></td>
                <td><?= (int)$c['n_orders'] ?></td>
                <td><b><?= price($c['spent']) ?></b></td>
                <td><?= $c['last_order'] ? time_fr($c['last_order']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$customers): ?><tr><td colspan="6" class="center muted">Aucun client.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
