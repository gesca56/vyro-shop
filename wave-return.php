<?php
/**
 * VYRO — Retour du client après Wave Checkout.
 * Le statut est revérifié auprès de l'API Wave : les paramètres de l'URL ne suffisent jamais.
 */
require __DIR__ . '/vyro-config.php';

$number = $_GET['n'] ?? '';
$order = q('SELECT * FROM orders WHERE number = ?', [$number])->fetch();
if (!$order || !hash_equals(order_token($number), (string)($_GET['t'] ?? ''))) {
    redirect('track.php');
}
// Lien signé valide : le client revient de Wave, on lui rend l'accès à sa commande
if (!in_array($number, $_SESSION['my_orders'] ?? [], true)) {
    $_SESSION['my_orders'][] = $number;
}

$result = $order['payment_status'] === 'paid' ? 'paid' : (wave_enabled() ? wave_sync_order($order) : 'none');

if ($result === 'paid') {
    redirect('order-confirmation.php?n=' . $order['number']);
}
if (in_array($result, ['processing', 'open'], true)) {
    flash('info', 'Paiement Wave en cours de validation… Rafraîchis cette page dans quelques secondes.');
} else {
    flash('error', ($_GET['r'] ?? '') === 'error'
        ? 'Le paiement Wave n’a pas abouti. Tu peux réessayer ou choisir un autre moyen de paiement.'
        : 'Paiement non confirmé pour l’instant. Réessaie ou contacte-nous sur WhatsApp.');
}
redirect('payment.php?n=' . $order['number']);
