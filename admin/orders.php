<?php
require __DIR__ . '/_layout.php';
require_admin();

$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = ['1'];
$params = [];
if (isset(order_statuses()[$status])) { $where[] = 'status = ?'; $params[] = $status; }
if ($search !== '') {
    $where[] = '(number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    array_push($params, ...array_fill(0, 5, "%$search%"));
}
$orders = q('SELECT o.*, (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) n FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 200', $params)->fetchAll();
$counts = q('SELECT status, COUNT(*) FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

admin_header('Commandes', 'orders');
?>
<div class="chip-row">
    <a href="orders.php" class="chip <?= !$status ? 'active' : '' ?>">Toutes (<?= array_sum($counts) ?>)</a>
    <?php foreach (order_statuses() as $k => $l): ?>
        <a href="?status=<?= $k ?>" class="chip <?= $status === $k ? 'active' : '' ?>"><?= e($l) ?> (<?= (int)($counts[$k] ?? 0) ?>)</a>
    <?php endforeach; ?>
</div>
<form method="get" class="toolbar-filters" style="margin-bottom:16px">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="N° commande, nom, téléphone, email…">
    <button class="btn btn-dark">Rechercher</button>
</form>

<div class="panel table-wrap">
    <table class="table responsive">
        <thead><tr><th>N°</th><th>Date</th><th>Client</th><th>Articles</th><th>Total</th><th>Paiement</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr class="clickable" data-href="order.php?id=<?= (int)$o['id'] ?>">
                <td data-label="N°"><a href="order.php?id=<?= (int)$o['id'] ?>"><b><?= e($o['number']) ?></b></a></td>
                <td data-label="Date"><?= time_fr($o['created_at'], true) ?></td>
                <td data-label="Client"><?= e($o['first_name'] . ' ' . $o['last_name']) ?><br><small class="muted"><?= e($o['phone']) ?> · <?= e($o['city']) ?></small></td>
                <td data-label="Articles"><?= (int)$o['n'] ?></td>
                <td data-label="Total"><b><?= price($o['total']) ?></b></td>
                <td data-label="Paiement"><?= e(payment_label($o['payment_method'])) ?><br><span class="status-pill <?= $o['payment_status'] === 'paid' ? 'ok' : 'wait' ?>"><?= e(payment_status_label($o['payment_status'])) ?></span></td>
                <td data-label="Statut"><span class="status-pill st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="7" class="center muted">Aucune commande.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
