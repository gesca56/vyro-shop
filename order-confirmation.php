<?php
require __DIR__ . '/vyro-config.php';

$order = find_own_order($_GET['n'] ?? '');
if (!$order) {
    redirect('track.php');
}
// Paiement en ligne pas encore effectué : retour à la page de paiement
if ($order['payment_status'] === 'pending' && $order['payment_method'] !== 'cod' && $order['status'] !== 'cancelled' && empty($order['payment_ref'])) {
    redirect('payment.php?n=' . $order['number']);
}
$items = q('SELECT * FROM order_items WHERE order_id = ?', [$order['id']])->fetchAll();

$pageTitle = 'Commande confirmée';
require __DIR__ . '/includes/header.php';
?>

<div class="container narrow">
    <ol class="steps-bar">
        <li class="done">Panier</li>
        <li class="done">Paiement</li>
        <li class="active">Confirmation</li>
    </ol>

    <div class="panel confirm-panel">
        <div class="confirm-icon">✓</div>
        <h1>Merci <?= e($order['first_name']) ?>, votre commande est confirmée !</h1>
        <p>Un récapitulatif a été envoyé à <b><?= e($order['email']) ?></b>. Nous vous contacterons au <b><?= e($order['phone']) ?></b> pour la livraison.</p>

        <div class="confirm-number">
            <span>Numéro de commande</span>
            <strong><?= e($order['number']) ?></strong>
            <button class="link-btn" data-copy="<?= e($order['number']) ?>">Copier</button>
        </div>

        <div class="grid-2 confirm-meta">
            <div><span class="muted">Paiement</span><b><?= e(payment_label($order['payment_method'])) ?></b>
                <small class="status-pill <?= $order['payment_status'] === 'paid' ? 'ok' : 'wait' ?>"><?= $order['payment_method'] === 'cod' ? 'À régler à la livraison' : ($order['payment_status'] === 'pending' ? 'Paiement en vérification' : e(payment_status_label($order['payment_status']))) ?></small></div>
            <div><span class="muted">Livraison</span><b><?= e($order['pickup_point'] ?: $order['address']) ?></b><small><?= e(trim($order['commune'] . ', ' . $order['city'], ', ')) ?></small></div>
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
        <dl class="totals">
            <div><dt>Sous-total</dt><dd><?= price($order['subtotal']) ?></dd></div>
            <?php if ($order['discount']): ?><div class="discount"><dt>Réduction</dt><dd>−<?= price($order['discount']) ?></dd></div><?php endif; ?>
            <div><dt>Livraison</dt><dd><?= $order['shipping'] ? price($order['shipping']) : 'Offerte' ?></dd></div>
            <div class="grand"><dt>Total</dt><dd><?= price($order['total']) ?></dd></div>
        </dl>

        <div class="confirm-actions">
            <a href="<?= url('track.php?n=' . urlencode($order['number']) . '&c=' . urlencode($order['email'])) ?>" class="btn btn-dark btn-lg btn-block">SUIVRE MA COMMANDE</a>
            <a href="<?= url('shop.php') ?>" class="btn btn-outline btn-block">Continuer mes achats</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
