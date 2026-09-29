<?php
require __DIR__ . '/_layout.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$order = q('SELECT * FROM orders WHERE id = ?', [$id])->fetch();
if (!$order) redirect('admin/orders.php');

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        $new = $_POST['status'] ?? '';
        $note = trim($_POST['note'] ?? '') ?: null;
        if (isset(order_statuses()[$new]) && $new !== $order['status']) {
            if ($new === 'cancelled') {
                restore_order_stock($id);
            } elseif ($order['status'] === 'cancelled') {
                // Réactivation : on reprend le stock
                foreach (q('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL', [$id])->fetchAll() as $it) {
                    q('UPDATE products SET stock = GREATEST(0, stock - ?), sales_count = sales_count + ? WHERE id = ?', [$it['qty'], $it['qty'], $it['product_id']]);
                }
            }
            q('UPDATE orders SET status = ? WHERE id = ?', [$new, $id]);
            if ($new === 'delivered' && $order['payment_method'] === 'cod') {
                q("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$id]);
            }
            add_status_history($id, $new, $note);
            send_order_email($order, 'VYRO — Commande ' . $order['number'] . ' : ' . status_label($new), 'Le statut de votre commande a changé : ' . status_label($new) . '.');
            flash('success', 'Statut mis à jour : ' . status_label($new));
        }
    }

    if ($action === 'ship') {
        $tracking = trim($_POST['tracking_number'] ?? '');
        q("UPDATE orders SET status = 'shipped', tracking_number = ? WHERE id = ?", [$tracking ?: null, $id]);
        add_status_history($id, 'shipped', $tracking ? 'Numéro de suivi : ' . $tracking : 'Expédition confirmée');
        send_order_email($order, 'VYRO — Votre commande ' . $order['number'] . ' est expédiée', 'Bonne nouvelle, votre commande est en route !' . ($tracking ? " Numéro de suivi : $tracking" : ''));
        flash('success', 'Expédition confirmée.');
    }

    if ($action === 'tracking') {
        q('UPDATE orders SET tracking_number = ? WHERE id = ?', [trim($_POST['tracking_number'] ?? '') ?: null, $id]);
        flash('success', 'Numéro de suivi enregistré.');
    }

    if ($action === 'payment') {
        $ps = $_POST['payment_status'] ?? '';
        if (in_array($ps, ['pending', 'paid', 'failed', 'refunded'], true)) {
            if ($ps === 'paid') {
                mark_order_paid($order, $order['payment_ref'] ?: 'MANUEL', 'Paiement ' . payment_label($order['payment_method']) . ' vérifié par l’admin');
            } else {
                q('UPDATE orders SET payment_status = ? WHERE id = ?', [$ps, $id]);
            }
            flash('success', 'Statut de paiement : ' . payment_status_label($ps));
        }
    }
    redirect('admin/order.php?id=' . $id);
}

$items = q('SELECT * FROM order_items WHERE order_id = ?', [$id])->fetchAll();
$history = q('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC, id DESC', [$id])->fetchAll();
$customerOrders = $order['user_id'] ? (int)q('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$order['user_id']])->fetchColumn() : 0;

admin_header('Commande ' . $order['number'], 'orders');
?>
<a href="orders.php" class="link-arrow">← Retour aux commandes</a>
<div class="order-head">
    <span class="status-pill st-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span>
    <span class="status-pill <?= $order['payment_status'] === 'paid' ? 'ok' : 'wait' ?>"><?= e(payment_label($order['payment_method'])) ?> — <?= e(payment_status_label($order['payment_status'])) ?></span>
    <span class="muted">Passée le <?= time_fr($order['created_at'], true) ?></span>
</div>

<?php if ($order['payment_status'] === 'pending' && $order['payment_ref'] && $order['status'] !== 'cancelled'): ?>
    <div class="pay-check">
        <div>
            <b>Paiement déclaré par le client — à vérifier</b>
            <span><?= e(payment_label($order['payment_method'])) ?> · <?= price($order['total']) ?> · réf. <b><?= e($order['payment_ref']) ?></b></span>
            <small>Vérifie la réception sur le <?= e(PAYMENT_PHONE) ?> avant de confirmer.</small>
        </div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="payment"><input type="hidden" name="payment_status" value="paid">
            <button class="btn btn-accent">Paiement reçu ✓</button>
        </form>
    </div>
<?php endif; ?>

<div class="admin-grid wide">
    <div>
        <section class="panel">
            <h2 class="h3">Articles</h2>
            <div class="summary-items">
                <?php foreach ($items as $it): ?>
                    <div class="summary-item">
                        <div class="thumb"><img src="<?= e(visual_url($it['visual'] ?: 'tee', $it['color'] ?: 'Noir')) ?>" alt=""><span><?= (int)$it['qty'] ?></span></div>
                        <div><b><?= e($it['name']) ?></b><small><?= e($it['color']) ?> · Taille <?= e($it['size']) ?> · <?= price($it['price']) ?> × <?= (int)$it['qty'] ?></small></div>
                        <span><?= price($it['price'] * $it['qty']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <dl class="totals">
                <div><dt>Sous-total</dt><dd><?= price($order['subtotal']) ?></dd></div>
                <?php if ($order['discount']): ?><div class="discount"><dt>Réduction (<?= e($order['promo_code']) ?>)</dt><dd>−<?= price($order['discount']) ?></dd></div><?php endif; ?>
                <div><dt>Livraison</dt><dd><?= $order['shipping'] ? price($order['shipping']) : 'Offerte' ?></dd></div>
                <div class="grand"><dt>Total</dt><dd><?= price($order['total']) ?></dd></div>
            </dl>
            <?php if ($order['payment_ref']): ?><p class="small muted">Référence paiement : <?= e($order['payment_ref']) ?></p><?php endif; ?>
        </section>

        <section class="panel">
            <h2 class="h3">Historique</h2>
            <ol class="history">
                <?php foreach ($history as $h): ?>
                    <li><b><?= e(status_label($h['status'])) ?></b> <span class="muted small"><?= time_fr($h['created_at'], true) ?></span><?php if ($h['note']): ?><br><small><?= e($h['note']) ?></small><?php endif; ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>

    <div>
        <section class="panel form">
            <h2 class="h3">Statut de la commande</h2>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="action" value="status">
                <select name="status">
                    <?php foreach (order_statuses() as $k => $l): ?><option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select>
                <input name="note" placeholder="Note (optionnelle, visible dans l'historique)">
                <button class="btn btn-dark btn-block">Mettre à jour</button>
            </form>
        </section>

        <section class="panel form">
            <h2 class="h3">Expédition</h2>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <label class="field">Numéro de suivi<input name="tracking_number" value="<?= e($order['tracking_number']) ?>" placeholder="Ex : CI123456789"></label>
                <?php if (in_array($order['status'], ['confirmed', 'preparing'], true)): ?>
                    <button name="action" value="ship" class="btn btn-accent btn-block">Confirmer l'expédition</button>
                <?php endif; ?>
                <button name="action" value="tracking" class="btn btn-outline btn-block">Enregistrer le numéro</button>
            </form>
        </section>

        <section class="panel form">
            <h2 class="h3">Paiement</h2>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="action" value="payment">
                <select name="payment_status" onchange="this.form.submit()">
                    <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $ps): ?><option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= e(payment_status_label($ps)) ?></option><?php endforeach; ?>
                </select>
            </form>
        </section>

        <section class="panel">
            <h2 class="h3">Client</h2>
            <p><b><?= e($order['first_name'] . ' ' . $order['last_name']) ?></b><br>
                <a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a><br>
                <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></p>
            <?php if ($order['user_id']): ?><p><a href="customer.php?id=<?= (int)$order['user_id'] ?>" class="link-arrow"><?= $customerOrders ?> commande(s) → fiche client</a></p><?php else: ?><p class="muted small">Commande invité</p><?php endif; ?>
            <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://wa.me/<?= preg_replace('/\D/', '', $order['phone']) ?>?text=<?= rawurlencode('Bonjour ' . $order['first_name'] . ', ici VYRO à propos de votre commande ' . $order['number'] . '.') ?>">WhatsApp</a>
            <h2 class="h3">Livraison</h2>
            <p><?php if ($order['pickup_point']): ?><b>Point de retrait :</b> <?= e($order['pickup_point']) ?><br><?php else: ?><?= e($order['address']) ?><br><?php endif; ?>
                <?= e(trim($order['commune'] . ', ' . $order['city'], ', ')) ?><br><?= e($order['country']) ?></p>
            <?php if ($order['notes']): ?><p class="notice">📝 <?= e($order['notes']) ?></p><?php endif; ?>
        </section>
    </div>
</div>
<?php admin_footer(); ?>
