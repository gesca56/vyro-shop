<?php
/**
 * VYRO — Paiement de la commande.
 *  - Wave + clé API configurée : paiement instantané via Wave Checkout (voir includes/wave.php).
 *  - Sinon : transfert Wave vers PAYMENT_PHONE puis le client
 *    déclare son paiement (ID de transaction) ; l'admin vérifie et marque la commande « Payée ».
 */
require __DIR__ . '/vyro-config.php';

$order = find_own_order($_GET['n'] ?? '');
if (!$order) {
    flash('error', 'Commande introuvable.');
    redirect('track.php');
}
if ($order['payment_status'] === 'paid' || $order['payment_method'] === 'cod') {
    redirect('order-confirmation.php?n=' . $order['number']);
}
if ($order['status'] === 'cancelled') {
    flash('error', 'Cette commande a été annulée.');
    redirect('cart.php');
}

$methods = payment_methods();
if (!isset($methods[$order['payment_method']])) {
    // Moyen désactivé depuis (ex. carte) : on bascule sur Wave
    q("UPDATE orders SET payment_method = 'wave' WHERE id = ?", [$order['id']]);
    $order['payment_method'] = 'wave';
}
$method = $methods[$order['payment_method']];
$isWaveApi = $order['payment_method'] === 'wave' && wave_enabled();
$error = null;

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        q("UPDATE orders SET status = 'cancelled', payment_status = 'failed' WHERE id = ?", [$order['id']]);
        restore_order_stock((int)$order['id']);
        add_status_history((int)$order['id'], 'cancelled', 'Commande annulée par le client avant paiement');
        flash('info', 'Ta commande a été annulée.');
        redirect('shop.php');
    }

    if ($action === 'change_method') {
        $new = $_POST['method'] ?? '';
        if (isset($methods[$new])) {
            q('UPDATE orders SET payment_method = ? WHERE id = ?', [$new, $order['id']]);
        }
        redirect($new === 'cod' ? 'order-confirmation.php?n=' . $order['number'] : 'payment.php?n=' . $order['number']);
    }

    // Paiement Wave via l'API : création de la session puis redirection vers Wave
    if ($action === 'wave' && $isWaveApi) {
        $res = wave_create_checkout($order);
        if ($res['ok']) {
            add_status_history((int)$order['id'], 'confirmed', 'Redirection vers Wave (session ' . $res['session']['id'] . ')');
            header('Location: ' . $res['session']['wave_launch_url']);
            exit;
        }
        $error = $res['message'];
    }

    // Transfert manuel : le client déclare son paiement
    if ($action === 'declare' && !$isWaveApi) {
        $ref = strtoupper(trim(preg_replace('/[^A-Za-z0-9._\- ]/', '', $_POST['tx_ref'] ?? '')));
        $payer = preg_replace('/[^\d+ ]/', '', $_POST['payer_phone'] ?? '');
        if (strlen($ref) < 4 && strlen(preg_replace('/\D/', '', $payer)) < 8) {
            $error = 'Indique l’ID de la transaction (reçu par SMS) ou le numéro qui a payé.';
        } else {
            $decl = $ref ?: ('TEL ' . $payer);
            q('UPDATE orders SET payment_ref = ? WHERE id = ?', [mb_substr($decl, 0, 60), $order['id']]);
            add_status_history((int)$order['id'], 'confirmed', 'Paiement ' . $method['label'] . ' déclaré par le client — réf. ' . $decl . ($payer ? ' — depuis ' . $payer : '') . ' (à vérifier)');
            flash('success', 'Merci ! On vérifie ton paiement et on confirme ta commande très vite.');
            redirect('order-confirmation.php?n=' . $order['number']);
        }
    }
}

$amount = price($order['total']);
$payPhone = PAYMENT_PHONE;
$payDigits = preg_replace('/\D/', '', preg_replace('/^\+?225\s*/', '', $payPhone));
$payLocal = trim(chunk_split($payDigits, 2, ' ')); // « 05 56 77 40 58 »

$pageTitle = 'Paiement';
require __DIR__ . '/includes/header.php';
?>

<div class="container narrow">
    <ol class="steps-bar">
        <li class="done">Panier</li>
        <li class="active">Paiement</li>
        <li>Confirmation</li>
    </ol>

    <div class="panel pay-panel">
        <div class="pay-head">
            <i class="pay-dot lg" style="--pc:<?= $method['color'] ?>"></i>
            <div>
                <span class="muted">Commande <?= e($order['number']) ?></span>
                <h1><?= e($method['label']) ?></h1>
            </div>
            <div class="pay-amount"><?= $amount ?></div>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <?php if ($isWaveApi): ?>
            <!-- Wave Checkout (API) -->
            <p>Tu vas être redirigé vers <b>Wave</b> pour valider le paiement de <b><?= $amount ?></b>. Sur téléphone, l’application Wave s’ouvre directement ; sur ordinateur, scanne le QR code avec ton app.</p>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="wave">
                <button class="btn btn-wave btn-lg btn-block" data-loading="Ouverture de Wave…">
                    <svg viewBox="0 0 24 24" width="22"><path d="M2 14c2.5 0 2.5-4 5-4s2.5 4 5 4 2.5-4 5-4 2.5 4 5 4"/></svg>
                    Payer <?= $amount ?> avec Wave
                </button>
            </form>
            <p class="muted small center">Paiement sécurisé par Wave. La commande est confirmée automatiquement dès que Wave valide le paiement.</p>

        <?php else: ?>
            <!-- Transfert Mobile Money vers le numéro VYRO -->
            <ol class="pay-steps">
                <li><div>
                    <b>Ouvre <?= e($method['label']) ?></b> et choisis <i><?= $order['payment_method'] === 'wave' ? 'Envoyer' : 'Transfert d’argent' ?></i>.
                </div></li>
                <li><div>
                    <b>Envoie exactement</b>
                    <button type="button" class="copy-line" data-copy="<?= (int)$order['total'] ?>"><span><?= $amount ?></span><small>Copier</small></button>
                </div></li>
                <li><div>
                    <b>Au numéro</b> <?= e(PAYMENT_NAME) ?>
                    <button type="button" class="copy-line" data-copy="<?= e($payDigits) ?>"><span><?= e($payLocal) ?></span><small>Copier</small></button>
                </div></li>
                <li><div>
                    <b>Indique en motif</b>
                    <button type="button" class="copy-line" data-copy="<?= e($order['number']) ?>"><span><?= e($order['number']) ?></span><small>Copier</small></button>
                </div></li>
            </ol>
            <form method="post" class="form pay-declare">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="declare">
                <h3>C’est fait ? Confirme ton paiement</h3>
                <label class="field">ID de la transaction (dans le SMS de confirmation)
                    <input name="tx_ref" placeholder="Ex : TX2609291234" autocomplete="off" autocapitalize="characters">
                </label>
                <label class="field">Numéro qui a payé
                    <input type="tel" name="payer_phone" value="<?= e($order['phone']) ?>" inputmode="tel">
                </label>
                <button class="btn btn-accent btn-lg btn-block" data-loading="Envoi…">J’ai payé <?= $amount ?></button>
                <p class="muted small center">Ta commande est validée dès que nous voyons le paiement (en général quelques minutes). Une question ?
                    <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode('Bonjour VYRO, j’ai payé la commande ' . $order['number']) ?>" target="_blank" rel="noopener">WhatsApp</a></p>
            </form>
        <?php endif; ?>

        <?php if (count($methods) > 1): ?>
            <details class="pay-alt">
                <summary>Changer de moyen de paiement</summary>
                <form method="post" class="pay-switch">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_method">
                    <?php foreach ($methods as $k => $m): if ($k === $order['payment_method']) continue; ?>
                        <button name="method" value="<?= $k ?>" class="btn btn-outline btn-sm"><i class="pay-dot" style="--pc:<?= $m['color'] ?>"></i> <?= e($m['label']) ?></button>
                    <?php endforeach; ?>
                </form>
            </details>
        <?php endif; ?>

        <form method="post" onsubmit="return confirm('Annuler la commande ?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <button class="link-btn danger">Annuler la commande</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
