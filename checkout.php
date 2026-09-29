<?php
require __DIR__ . '/vyro-config.php';

expire_unpaid_orders();

$lines = cart_lines();
if (!$lines) {
    flash('info', 'Votre panier est vide.');
    redirect('cart.php');
}

$user = current_user();
$savedAddr = $user ? q('SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$user['id']])->fetch() : null;
$cities = ['Abidjan', 'Bouaké', 'Yamoussoukro', 'San-Pédro', 'Daloa', 'Korhogo', 'Grand-Bassam', 'Man', 'Gagnoa', 'Autre'];
$countries = ['Côte d\'Ivoire', 'Sénégal', 'Burkina Faso', 'Mali', 'Togo', 'Bénin', 'Ghana', 'France'];

$f = [
    'first_name' => $user['first_name'] ?? '', 'last_name' => $user['last_name'] ?? '', 'phone' => $user['phone'] ?? '', 'email' => $user['email'] ?? '',
    'country' => $savedAddr['country'] ?? 'Côte d\'Ivoire', 'city' => $savedAddr['city'] ?? 'Abidjan', 'commune' => $savedAddr['commune'] ?? '',
    'address' => $savedAddr['address'] ?? '', 'delivery' => 'home', 'pickup_point' => '', 'notes' => '', 'payment' => 'wave',
];
$errors = [];

if (is_post()) {
    csrf_check();
    foreach ($f as $k => $v) {
        $f[$k] = trim((string)($_POST[$k] ?? $v));
    }
    if ($f['first_name'] === '') $errors['first_name'] = 'Prénom requis';
    if ($f['last_name'] === '') $errors['last_name'] = 'Nom requis';
    if (!preg_match('/^\+?[\d\s.-]{8,20}$/', $f['phone'])) $errors['phone'] = 'Numéro de téléphone invalide';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide';
    if ($f['city'] === '') $errors['city'] = 'Ville requise';
    if ($f['delivery'] === 'pickup') {
        if (!in_array($f['pickup_point'], pickup_points(), true)) $errors['pickup_point'] = 'Choisissez un point de retrait';
        if ($f['address'] === '') $f['address'] = $f['pickup_point'];
    } elseif ($f['address'] === '') {
        $errors['address'] = 'Adresse requise';
    }
    if (!isset(payment_methods()[$f['payment']])) $errors['payment'] = 'Choisissez un moyen de paiement';

    $createAccount = !$user && !empty($_POST['create_account']);
    $password = $_POST['password'] ?? '';
    if ($createAccount) {
        if (strlen($password) < 6) $errors['password'] = '6 caractères minimum';
        if (q('SELECT 1 FROM users WHERE email = ?', [$f['email']])->fetchColumn()) $errors['email'] = 'Un compte existe déjà avec cet email — connectez-vous.';
    }

    // Vérification du stock
    $need = [];
    foreach ($lines as $l) $need[$l['product']['id']] = ($need[$l['product']['id']] ?? 0) + $l['qty'];
    foreach ($need as $pid => $qty) {
        $stock = (int)q('SELECT stock FROM products WHERE id = ?', [$pid])->fetchColumn();
        if ($stock < $qty) {
            $name = q('SELECT name FROM products WHERE id = ?', [$pid])->fetchColumn();
            $errors['stock'] = "Stock insuffisant pour « $name » ($stock disponible). Modifiez votre panier.";
        }
    }

    if (!$errors) {
        $totals = cart_totals($lines, $_SESSION['promo_code'] ?? null, $f['city']);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($createAccount) {
                q('INSERT INTO users (first_name,last_name,email,phone,password) VALUES (?,?,?,?,?)',
                    [$f['first_name'], $f['last_name'], $f['email'], $f['phone'], password_hash($password, PASSWORD_DEFAULT)]);
                $newId = (int)$pdo->lastInsertId();
                q('INSERT INTO addresses (user_id,country,city,commune,address,phone) VALUES (?,?,?,?,?,?)',
                    [$newId, $f['country'], $f['city'], $f['commune'], $f['address'], $f['phone']]);
                $user = ['id' => $newId];
            }
            $number = generate_order_number();
            q('INSERT INTO orders (number,user_id,first_name,last_name,phone,email,country,city,commune,address,pickup_point,notes,subtotal,discount,shipping,total,promo_code,payment_method)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $number, $user['id'] ?? null, $f['first_name'], $f['last_name'], $f['phone'], $f['email'], $f['country'], $f['city'], $f['commune'],
                $f['address'], $f['delivery'] === 'pickup' ? $f['pickup_point'] : null, $f['notes'] ?: null,
                $totals['subtotal'], $totals['discount'], $totals['shipping'], $totals['total'], $totals['promo']['code'] ?? null, $f['payment'],
            ]);
            $orderId = (int)$pdo->lastInsertId();
            foreach ($lines as $l) {
                q('INSERT INTO order_items (order_id,product_id,name,visual,price,size,color,qty) VALUES (?,?,?,?,?,?,?,?)',
                    [$orderId, $l['product']['id'], $l['product']['name'], $l['product']['visual'], $l['unit'], $l['size'], $l['color'], $l['qty']]);
                q('UPDATE products SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ?', [$l['qty'], $l['qty'], $l['product']['id']]);
            }
            if ($totals['promo']) {
                q('UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?', [$totals['promo']['id']]);
            }
            add_status_history($orderId, 'confirmed', 'Commande enregistrée');
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }

        send_order_email(q('SELECT * FROM orders WHERE id = ?', [$orderId])->fetch(), 'VYRO — Commande ' . $number . ' confirmée', 'Votre commande a bien été enregistrée.');
        if ($createAccount) login_user(['id' => $user['id']]);
        $_SESSION['my_orders'][] = $number;
        cart_clear();

        if ($f['payment'] === 'cod') {
            redirect('order-confirmation.php?n=' . $number);
        }
        redirect('payment.php?n=' . $number);
    }
}

$totals = cart_totals($lines, $_SESSION['promo_code'] ?? null, $f['city']);
$methods = payment_methods();

$pageTitle = 'Commande';
require __DIR__ . '/includes/header.php';

function err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<small class="field-error">' . e($errors[$k]) . '</small>' : '';
}
?>

<div class="container">
    <ol class="steps-bar">
        <li class="done"><a href="<?= url('cart.php') ?>">Panier</a></li>
        <li class="active">Livraison & paiement</li>
        <li>Confirmation</li>
    </ol>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?= isset($errors['stock']) ? e($errors['stock']) : 'Veuillez corriger les champs indiqués.' ?></div>
    <?php endif; ?>

    <form method="post" class="checkout-layout" id="checkoutForm" novalidate>
        <?= csrf_field() ?>
        <div class="checkout-main">
            <?php if (!$user): ?>
                <div class="notice">Déjà client ? <a href="<?= url('login.php?redirect=' . urlencode(url('checkout.php'))) ?>">Connectez-vous</a> pour aller plus vite.</div>
            <?php endif; ?>

            <fieldset class="panel">
                <legend><span class="step-num">1</span> Vos informations</legend>
                <div class="grid-2">
                    <label class="field">Prénom *<input name="first_name" value="<?= e($f['first_name']) ?>" autocomplete="given-name" required><?= err($errors, 'first_name') ?></label>
                    <label class="field">Nom *<input name="last_name" value="<?= e($f['last_name']) ?>" autocomplete="family-name" required><?= err($errors, 'last_name') ?></label>
                    <label class="field">Téléphone *<input type="tel" name="phone" value="<?= e($f['phone']) ?>" placeholder="+225 07 00 00 00 00" autocomplete="tel" inputmode="tel" required><?= err($errors, 'phone') ?></label>
                    <label class="field">Email *<input type="email" name="email" value="<?= e($f['email']) ?>" autocomplete="email" inputmode="email" required><?= err($errors, 'email') ?></label>
                </div>
            </fieldset>

            <fieldset class="panel">
                <legend><span class="step-num">2</span> Livraison</legend>
                <div class="radio-cards two">
                    <label class="radio-card"><input type="radio" name="delivery" value="home" <?= $f['delivery'] === 'home' ? 'checked' : '' ?>><span><b>🏠 À domicile</b><small>24–48h à Abidjan</small></span></label>
                    <label class="radio-card"><input type="radio" name="delivery" value="pickup" <?= $f['delivery'] === 'pickup' ? 'checked' : '' ?>><span><b>📍 Point de retrait</b><small>Store & points relais</small></span></label>
                </div>
                <div class="grid-2">
                    <label class="field">Pays *
                        <select name="country">
                            <?php foreach ($countries as $c): ?><option <?= $f['country'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">Ville *
                        <select name="city" id="citySelect">
                            <?php foreach ($cities as $c): ?><option <?= $f['city'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                        </select><?= err($errors, 'city') ?>
                    </label>
                    <label class="field">Commune / Quartier
                        <input name="commune" value="<?= e($f['commune']) ?>" list="communes" placeholder="Ex : Cocody">
                        <datalist id="communes"><?php foreach (communes_abidjan() as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
                    </label>
                    <label class="field home-only">Adresse *<input name="address" value="<?= e($f['address']) ?>" placeholder="Rue, résidence, repère…" autocomplete="street-address"><?= err($errors, 'address') ?></label>
                </div>
                <label class="field pickup-only">Point de retrait *
                    <select name="pickup_point">
                        <option value="">Choisir un point</option>
                        <?php foreach (pickup_points() as $pp): ?><option <?= $f['pickup_point'] === $pp ? 'selected' : '' ?>><?= e($pp) ?></option><?php endforeach; ?>
                    </select><?= err($errors, 'pickup_point') ?>
                </label>
                <label class="field">Instructions pour le livreur (facultatif)<input name="notes" value="<?= e($f['notes']) ?>" placeholder="Ex : appeler avant de venir"></label>
            </fieldset>

            <fieldset class="panel">
                <legend><span class="step-num">3</span> Paiement</legend>
                <div class="radio-cards pay">
                    <?php foreach ($methods as $key => $m): ?>
                        <label class="radio-card">
                            <input type="radio" name="payment" value="<?= $key ?>" <?= $f['payment'] === $key ? 'checked' : '' ?>>
                            <span><i class="pay-dot" style="--pc:<?= $m['color'] ?>"></i><b><?= e($m['label']) ?></b>
                                <small><?= $key === 'wave' ? (wave_enabled() ? 'Paiement instantané dans l’app Wave' : 'Transfert Wave au ' . e(PAYMENT_PHONE)) : ($m['type'] === 'mobile' ? 'Transfert au ' . e(PAYMENT_PHONE) : ($m['type'] === 'card' ? 'Visa, Mastercard' : 'Espèces ou Mobile Money au livreur')) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= err($errors, 'payment') ?>
                <p class="muted small">🔒 Paiements reçus au <b><?= e(PAYMENT_PHONE) ?></b> (<?= e(PAYMENT_NAME) ?>). VYRO ne stocke aucune donnée bancaire.</p>
            </fieldset>

            <?php if (!$user): ?>
                <fieldset class="panel">
                    <label class="check"><input type="checkbox" name="create_account" value="1" id="createAccount" <?= !empty($_POST['create_account']) ? 'checked' : '' ?>> Créer mon compte VYRO pour suivre mes commandes</label>
                    <label class="field account-only">Mot de passe<input type="password" name="password" minlength="6" autocomplete="new-password"><?= err($errors, 'password') ?></label>
                </fieldset>
            <?php endif; ?>
        </div>

        <aside class="summary">
            <h3>Votre commande</h3>
            <div class="summary-items">
                <?php foreach ($lines as $l): ?>
                    <div class="summary-item">
                        <div class="thumb"><img src="<?= e(product_thumb($l['product'], $l['color'] ?: null)) ?>" alt=""><span><?= $l['qty'] ?></span></div>
                        <div><b><?= e($l['product']['name']) ?></b><small><?= e(trim($l['color'] . ($l['size'] && $l['size'] !== 'Unique' ? ' · ' . $l['size'] : ''))) ?></small></div>
                        <span><?= price($l['total']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <dl class="totals" id="checkoutTotals"
                data-subtotal="<?= $totals['subtotal'] - $totals['discount'] ?>"
                data-free="<?= FREE_SHIPPING_THRESHOLD ?>" data-fee="<?= SHIPPING_FEE ?>" data-fee-other="<?= SHIPPING_FEE_OTHER ?>"
                data-freeship="<?= ($totals['promo']['type'] ?? '') === 'free_shipping' ? 1 : 0 ?>">
                <div><dt>Sous-total</dt><dd><?= price($totals['subtotal']) ?></dd></div>
                <?php if ($totals['discount']): ?><div class="discount"><dt>Réduction (<?= e($totals['promo']['code']) ?>)</dt><dd>−<?= price($totals['discount']) ?></dd></div><?php endif; ?>
                <div><dt>Livraison</dt><dd id="shipAmount"><?= $totals['shipping'] ? price($totals['shipping']) : '<b class="free">Offerte</b>' ?></dd></div>
                <div class="grand"><dt>Total</dt><dd id="totalAmount"><?= price($totals['total']) ?></dd></div>
            </dl>
            <?php if (!$totals['promo']): ?><p class="small"><a href="<?= url('cart.php') ?>">Vous avez un code promo ?</a></p><?php endif; ?>
            <button class="btn btn-accent btn-lg btn-block">CONFIRMER ET PAYER</button>
            <p class="muted small center">En validant, vous acceptez nos <a href="<?= url('page.php?p=cgv') ?>" target="_blank">CGV</a>.</p>
        </aside>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
