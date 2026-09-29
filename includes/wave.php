<?php
/**
 * VYRO — Intégration Wave Business (API Checkout)
 * Documentation : https://docs.wave.com/checkout
 *
 * Flux :
 *  1. wave_create_checkout() crée une session → on redirige le client vers « wave_launch_url »
 *     (ouvre l'application Wave sur mobile, ou un QR code sur ordinateur).
 *  2. Wave renvoie le client sur success_url / error_url → wave-return.php revérifie la session
 *     auprès de l'API (on ne fait jamais confiance au seul retour navigateur).
 *  3. Wave appelle aussi wave-webhook.php (événement « checkout.session.completed »),
 *     signé en HMAC-SHA256 : c'est la confirmation de référence, même si le client ferme son navigateur.
 */

const WAVE_API = 'https://api.wave.com/v1';

/** Jeton signé d'accès à une commande (liens de retour Wave) */
function order_token(string $number): string
{
    return substr(hash_hmac('sha256', $number, APP_SECRET), 0, 24);
}

function wave_enabled(): bool
{
    return WAVE_API_KEY !== '';
}

/** URL absolue (Wave exige des URLs complètes en https pour les retours) */
function absolute_url(string $path): string
{
    if (SITE_URL !== '') {
        return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

/**
 * Appel à l'API Wave.
 * @return array{0:int,1:array} [code HTTP, réponse JSON décodée]
 */
function wave_request(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
{
    $headers = ['Authorization: Bearer ' . WAVE_API_KEY, 'Accept: application/json'];
    if ($body !== null) $headers[] = 'Content-Type: application/json';
    if ($idempotencyKey) $headers[] = 'Idempotency-Key: ' . $idempotencyKey;

    $ch = curl_init(WAVE_API . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        error_log('[Wave] Erreur réseau : ' . $err);
        return [0, ['message' => 'Impossible de joindre Wave. Réessayez dans un instant.']];
    }
    $json = json_decode($raw, true) ?: [];
    if ($code >= 400) {
        error_log('[Wave] HTTP ' . $code . ' : ' . $raw);
    }
    return [$code, $json];
}

/** Crée (ou réutilise) une session de paiement Wave pour une commande */
function wave_create_checkout(array $order): array
{
    // Réutilise la session encore ouverte (évite les doublons si le client revient en arrière)
    if (!empty($order['payment_session'])) {
        [$code, $s] = wave_request('GET', '/checkout/sessions/' . rawurlencode($order['payment_session']));
        if ($code === 200 && ($s['checkout_status'] ?? '') === 'open' && !empty($s['wave_launch_url'])) {
            return ['ok' => true, 'session' => $s];
        }
    }

    $payload = [
        'amount' => (string)(int)$order['total'],   // XOF : montant entier, en chaîne
        'currency' => 'XOF',
        'client_reference' => $order['number'],
        'success_url' => absolute_url('wave-return.php?n=' . rawurlencode($order['number']) . '&t=' . order_token($order['number']) . '&r=success'),
        'error_url' => absolute_url('wave-return.php?n=' . rawurlencode($order['number']) . '&t=' . order_token($order['number']) . '&r=error'),
    ];
    [$code, $s] = wave_request('POST', '/checkout/sessions', $payload, 'vyro-' . $order['number'] . '-' . time());

    if ($code >= 200 && $code < 300 && !empty($s['wave_launch_url'])) {
        q('UPDATE orders SET payment_session = ? WHERE id = ?', [$s['id'], $order['id']]);
        return ['ok' => true, 'session' => $s];
    }
    $msg = match (true) {
        $code === 401 => 'Configuration Wave invalide (clé API). Contactez VYRO.',
        $code === 0 => $s['message'],
        default => $s['message'] ?? 'Wave a refusé la demande de paiement.',
    };
    return ['ok' => false, 'message' => $msg];
}

function wave_get_session(string $id): ?array
{
    [$code, $s] = wave_request('GET', '/checkout/sessions/' . rawurlencode($id));
    return $code === 200 ? $s : null;
}

/** Marque la commande payée (idempotent). Retourne true si elle vient d'être payée. */
function mark_order_paid(array $order, string $ref, string $note): bool
{
    $done = q("UPDATE orders SET payment_status = 'paid', payment_ref = ? WHERE id = ? AND payment_status <> 'paid'", [$ref, $order['id']])->rowCount();
    if ($done) {
        add_status_history((int)$order['id'], 'confirmed', $note);
        send_order_email($order, 'VYRO — Paiement reçu pour la commande ' . $order['number'], 'Nous avons bien reçu ton paiement. Ta commande est en préparation.');
    }
    return (bool)$done;
}

/** Vérifie une session Wave et met la commande à jour si elle est payée */
function wave_sync_order(array $order): string
{
    if (empty($order['payment_session'])) return 'none';
    $s = wave_get_session($order['payment_session']);
    if (!$s) return 'unknown';
    $status = $s['payment_status'] ?? '';
    if ($status === 'succeeded' && (int)($s['amount'] ?? 0) >= (int)$order['total']) {
        mark_order_paid($order, $s['transaction_id'] ?? $s['id'], 'Paiement Wave confirmé (' . ($s['transaction_id'] ?? $s['id']) . ')');
        return 'paid';
    }
    return $status ?: ($s['checkout_status'] ?? 'unknown');
}

/**
 * Vérifie la signature d'un webhook Wave.
 * En-tête « Wave-Signature: t=1639081943,v1=<hex>[,v1=<hex>] », signé sur « timestamp + corps brut ».
 */
function wave_verify_signature(string $payload, string $header, int $tolerance = 300): bool
{
    if (WAVE_WEBHOOK_SECRET === '' || $header === '') return false;
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') $timestamp = $v;
        if ($k === 'v1') $signatures[] = $v;
    }
    if (!$timestamp || !$signatures || abs(time() - (int)$timestamp) > $tolerance) return false;
    $expected = hash_hmac('sha256', $timestamp . $payload, WAVE_WEBHOOK_SECRET);
    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) return true;
    }
    return false;
}
