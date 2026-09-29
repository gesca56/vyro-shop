<?php
require __DIR__ . '/vyro-config.php';

$number = strtoupper(trim($_GET['n'] ?? $_POST['n'] ?? ''));
$contact = trim($_GET['c'] ?? $_POST['c'] ?? '');
$order = null;
$error = null;

if ($number !== '') {
    $o = q('SELECT * FROM orders WHERE number = ?', [$number])->fetch();
    $digits = fn($s) => substr(preg_replace('/\D/', '', $s), -8);
    $own = $o && (find_own_order($number) !== null);
    if ($o && ($own || ($contact !== '' && (strcasecmp($contact, $o['email']) === 0 || ($digits($contact) !== '' && $digits($contact) === $digits($o['phone'])))))) {
        $order = $o;
    } elseif ($contact !== '' || !$own) {
        $error = $contact === '' ? 'Indiquez aussi le téléphone ou l\'email utilisé lors de la commande.' : 'Aucune commande trouvée avec ces informations.';
    }
}

if ($order) {
    $items = q('SELECT * FROM order_items WHERE order_id = ?', [$order['id']])->fetchAll();
    $history = q('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at, id', [$order['id']])->fetchAll();
    $reached = [];
    foreach ($history as $h) $reached[$h['status']] = $reached[$h['status']] ?? $h['created_at'];
    $flow = ['confirmed', 'preparing', 'shipped', 'out_for_delivery', 'delivered'];
    $currentIdx = array_search($order['status'], $flow, true);
}
$icons = ['confirmed' => '✅', 'preparing' => '📦', 'shipped' => '🚚', 'out_for_delivery' => '🛵', 'delivered' => '🏠'];

$pageTitle = 'Suivre ma commande';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>SUIVRE MA COMMANDE</h1>
        <p>Entrez votre numéro de commande et le téléphone ou l'email utilisé.</p>
    </div>
</section>

<div class="container narrow">
    <form method="get" class="panel track-form">
        <label class="field">Numéro de commande<input name="n" value="<?= e($number) ?>" placeholder="VY2609291A2B" required autocapitalize="characters"></label>
        <label class="field">Téléphone ou email<input name="c" value="<?= e($contact) ?>" placeholder="+225 07… ou email" required></label>
        <button class="btn btn-dark btn-lg btn-block">Suivre</button>
    </form>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($order): ?>
        <div class="panel">
            <div class="track-head">
                <div>
                    <span class="muted">Commande du <?= time_fr($order['created_at']) ?></span>
                    <h2><?= e($order['number']) ?></h2>
                </div>
                <span class="status-pill st-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span>
            </div>

            <?php if ($order['status'] === 'cancelled'): ?>
                <div class="alert alert-error">Cette commande a été annulée. Contactez-nous sur WhatsApp pour toute question.</div>
            <?php else: ?>
                <ol class="timeline">
                    <?php foreach ($flow as $i => $st): $done = $i <= $currentIdx; ?>
                        <li class="<?= $done ? 'done' : '' ?> <?= $i === $currentIdx ? 'current' : '' ?>">
                            <span class="tl-icon"><?= $icons[$st] ?></span>
                            <div>
                                <b><?= e(status_label($st)) ?></b>
                                <?php if (isset($reached[$st])): ?><small><?= time_fr($reached[$st], true) ?></small><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if ($order['tracking_number']): ?>
                <div class="notice">Numéro de suivi transporteur : <b><?= e($order['tracking_number']) ?></b></div>
            <?php endif; ?>

            <div class="grid-2 confirm-meta">
                <div><span class="muted">Livraison</span><b><?= e($order['first_name'] . ' ' . $order['last_name']) ?></b><small><?= e($order['pickup_point'] ?: $order['address']) ?>, <?= e(trim($order['commune'] . ' ' . $order['city'])) ?></small></div>
                <div><span class="muted">Paiement</span><b><?= e(payment_label($order['payment_method'])) ?></b><small><?= e(payment_status_label($order['payment_status'])) ?></small></div>
            </div>

            <div class="summary-items">
                <?php foreach ($items as $it): ?>
                    <div class="summary-item">
                        <div class="thumb"><img src="<?= e(visual_url($it['visual'] ?: 'tee', $it['color'] ?: 'Noir')) ?>" alt=""><span><?= (int)$it['qty'] ?></span></div>
                        <div><b><?= e($it['name']) ?></b><small><?= e(trim($it['color'] . ($it['size'] && $it['size'] !== 'Unique' ? ' · ' . $it['size'] : ''))) ?></small></div>
                        <span><?= price($it['price'] * $it['qty']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <dl class="totals"><div class="grand"><dt>Total</dt><dd><?= price($order['total']) ?></dd></div></dl>
            <?php if ($order['payment_status'] === 'pending' && $order['payment_method'] !== 'cod' && $order['status'] !== 'cancelled' && empty($order['payment_ref']) && find_own_order($order['number'])): ?>
                <a href="<?= url('payment.php?n=' . $order['number']) ?>" class="btn btn-accent btn-block">Finaliser le paiement</a>
            <?php endif; ?>
            <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode('Bonjour VYRO, j\'ai une question sur ma commande ' . $order['number']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-block">Une question ? WhatsApp</a>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
