<?php
require __DIR__ . '/_layout.php';
require_admin();

$period = (int)($_GET['period'] ?? 30);
if (!in_array($period, [7, 30, 90, 0], true)) $period = 30;
$since = $period ? date('Y-m-d 00:00:00', strtotime('-' . ($period - 1) . ' days')) : '2000-01-01';

$paidCond = "o.status <> 'cancelled' AND o.payment_status = 'paid'";
$k = q("SELECT COALESCE(SUM(CASE WHEN $paidCond THEN o.total END),0) revenue,
               SUM(o.status <> 'cancelled') orders,
               SUM($paidCond) paid_orders
        FROM orders o WHERE o.created_at >= ?", [$since])->fetch();
$revenue = (int)$k['revenue'];
$ordersCount = (int)$k['orders'];
$avgCart = $k['paid_orders'] ? (int)round($revenue / $k['paid_orders']) : 0;
$clients = (int)q('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
$newClients = (int)q('SELECT COUNT(*) FROM users WHERE is_admin = 0 AND created_at >= ?', [$since])->fetchColumn();
$toProcess = (int)q("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed','preparing')")->fetchColumn();

// CA quotidien (max 30 derniers jours affichés)
$days = $period && $period <= 30 ? $period : 30;
$rows = q("SELECT DATE(o.created_at) d, SUM(o.total) t FROM orders o WHERE $paidCond AND o.created_at >= ? GROUP BY DATE(o.created_at)",
    [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))])->fetchAll(PDO::FETCH_KEY_PAIR);
$series = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $series[$d] = (int)($rows[$d] ?? 0);
}
$maxVal = max(1, max($series));
$scaleMax = (int)(ceil($maxVal / 50000) * 50000);

$top = q("SELECT oi.name, oi.product_id, SUM(oi.qty) qty, SUM(oi.qty * oi.price) amount
          FROM order_items oi JOIN orders o ON o.id = oi.order_id
          WHERE o.status <> 'cancelled' AND o.created_at >= ?
          GROUP BY oi.product_id, oi.name ORDER BY qty DESC LIMIT 5", [$since])->fetchAll();
$recent = q('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6')->fetchAll();
$lowStock = q('SELECT * FROM products WHERE is_active = 1 AND stock <= ? ORDER BY stock ASC LIMIT 6', [LOW_STOCK_THRESHOLD])->fetchAll();
$byMethod = q("SELECT payment_method m, COUNT(*) n FROM orders o WHERE $paidCond AND o.created_at >= ? GROUP BY payment_method ORDER BY n DESC", [$since])->fetchAll();

admin_header('Tableau de bord', 'dashboard');
?>

<div class="period-tabs">
    <?php foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours', 0 => 'Tout'] as $p => $l): ?>
        <a href="?period=<?= $p ?>" class="chip <?= $period === $p ? 'active' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="kpi-grid">
    <div class="kpi"><span>Chiffre d'affaires</span><strong><?= price($revenue) ?></strong><small>Commandes payées</small></div>
    <div class="kpi"><span>Commandes</span><strong><?= $ordersCount ?></strong><small><?= $toProcess ?> à traiter</small></div>
    <div class="kpi"><span>Panier moyen</span><strong><?= price($avgCart) ?></strong><small>Sur commandes payées</small></div>
    <div class="kpi"><span>Clients</span><strong><?= $clients ?></strong><small>+<?= $newClients ?> sur la période</small></div>
</div>

<div class="admin-grid">
    <section class="panel">
        <div class="panel-head">
            <h2 class="h3">Chiffre d'affaires quotidien — <?= $days ?> derniers jours</h2>
        </div>
        <figure class="bar-chart" role="img" aria-label="Chiffre d'affaires quotidien, maximum <?= price($maxVal) ?>">
            <div class="bc-axis">
                <span><?= number_format($scaleMax / 1000, 0, ',', ' ') ?>k</span>
                <span><?= number_format($scaleMax / 2000, 0, ',', ' ') ?>k</span>
                <span>0</span>
            </div>
            <div class="bc-plot">
                <div class="bc-grid"><i></i><i></i><i></i></div>
                <?php foreach ($series as $d => $v): ?>
                    <div class="bc-col" tabindex="0">
                        <div class="bc-bar" style="height:<?= $v ? max(1, $v * 100 / $scaleMax) : 0 ?>%"></div>
                        <div class="bc-tip"><b><?= price($v) ?></b><?= time_fr($d) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bc-x">
                <span><?= time_fr(array_key_first($series)) ?></span>
                <span>Aujourd'hui</span>
            </div>
        </figure>
        <details class="table-view">
            <summary>Voir les données</summary>
            <table class="table">
                <thead><tr><th>Jour</th><th>CA</th></tr></thead>
                <tbody><?php foreach (array_reverse($series, true) as $d => $v): if (!$v) continue; ?><tr><td><?= time_fr($d) ?></td><td><?= price($v) ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </details>
    </section>

    <section class="panel">
        <div class="panel-head"><h2 class="h3">Produits les plus vendus</h2></div>
        <?php if ($top): $topMax = max(array_column($top, 'qty')); ?>
            <ol class="rank-list">
                <?php foreach ($top as $t): ?>
                    <li>
                        <div class="rank-row"><b><?= e($t['name']) ?></b><span><?= (int)$t['qty'] ?> vendus · <?= price($t['amount']) ?></span></div>
                        <div class="rank-bar"><span style="width:<?= $t['qty'] * 100 / $topMax ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?><p class="muted">Aucune vente sur la période.</p><?php endif; ?>

        <h3 class="h3">Moyens de paiement</h3>
        <?php foreach ($byMethod as $m): ?>
            <div class="rank-row"><span><?= e(payment_label($m['m'])) ?></span><b><?= (int)$m['n'] ?></b></div>
        <?php endforeach; ?>
    </section>
</div>

<div class="admin-grid">
    <section class="panel">
        <div class="panel-head"><h2 class="h3">Dernières commandes</h2><a href="orders.php" class="link-arrow">Tout voir →</a></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $o): ?>
                    <tr onclick="location='order.php?id=<?= (int)$o['id'] ?>'" class="clickable">
                        <td><b><?= e($o['number']) ?></b><br><small class="muted"><?= time_fr($o['created_at'], true) ?></small></td>
                        <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
                        <td><?= price($o['total']) ?></td>
                        <td><span class="status-pill st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <div class="panel-head"><h2 class="h3">⚠️ Stock faible</h2><a href="products.php?stock=low" class="link-arrow">Gérer →</a></div>
        <?php foreach ($lowStock as $p): ?>
            <a href="product-edit.php?id=<?= (int)$p['id'] ?>" class="rank-row stock-row">
                <span><?= e($p['name']) ?></span>
                <b class="<?= $p['stock'] <= 0 ? 'stock-out' : 'stock-low' ?>"><?= $p['stock'] <= 0 ? 'Épuisé' : (int)$p['stock'] . ' restant(s)' ?></b>
            </a>
        <?php endforeach; ?>
        <?php if (!$lowStock): ?><p class="muted">Tous les stocks sont OK.</p><?php endif; ?>
    </section>
</div>

<?php admin_footer(); ?>
