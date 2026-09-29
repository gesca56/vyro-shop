<?php
/**
 * VYRO — Webhook Wave (à déclarer dans le portail Wave Business) :
 *   URL      : https://VOTRE-DOMAINE/wave-webhook.php
 *   Événement: checkout.session.completed
 * Le secret fourni par Wave va dans WAVE_WEBHOOK_SECRET (config.local.php).
 */
require __DIR__ . '/vyro-config.php';

header('Content-Type: application/json');
$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_WAVE_SIGNATURE'] ?? '';

if (!wave_verify_signature($payload, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'signature invalide']);
    exit;
}

$event = json_decode($payload, true) ?: [];
$type = $event['type'] ?? '';
$data = $event['data'] ?? [];

if ($type === 'checkout.session.completed' && ($data['payment_status'] ?? '') === 'succeeded') {
    $order = q('SELECT * FROM orders WHERE number = ?', [$data['client_reference'] ?? ''])->fetch();
    if ($order && (int)($data['amount'] ?? 0) >= (int)$order['total']) {
        if (empty($order['payment_session'])) {
            q('UPDATE orders SET payment_session = ? WHERE id = ?', [$data['id'] ?? null, $order['id']]);
        }
        mark_order_paid($order, $data['transaction_id'] ?? ($data['id'] ?? 'WAVE'), 'Paiement Wave confirmé par webhook (' . ($data['transaction_id'] ?? $data['id'] ?? '') . ')');
    } elseif ($order) {
        add_status_history((int)$order['id'], 'confirmed', 'Webhook Wave : montant reçu (' . ($data['amount'] ?? '?') . ') inférieur au total — à vérifier');
    }
}

// Toujours répondre 200 aux événements valides (Wave réessaie sinon)
echo json_encode(['received' => true]);
