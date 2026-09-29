<?php
require __DIR__ . '/vyro-config.php';

if (!is_post()) redirect('cart.php');
csrf_check();

$action = $_POST['action'] ?? '';
$back = $_SERVER['HTTP_REFERER'] ?? url('cart.php');

switch ($action) {
    case 'add':
    case 'buy':
        [$ok, $msg] = cart_add((int)($_POST['product_id'] ?? 0), trim($_POST['size'] ?? ''), trim($_POST['color'] ?? ''), max(1, (int)($_POST['qty'] ?? 1)));
        if (is_ajax()) {
            json_response(['ok' => $ok, 'message' => $msg, 'count' => cart_count()], $ok ? 200 : 422);
        }
        flash($ok ? 'success' : 'error', $msg);
        if ($ok && $action === 'buy') redirect('checkout.php');
        redirect($back);

    case 'update':
        foreach ((array)($_POST['qty'] ?? []) as $key => $qty) {
            cart_set((string)$key, (int)$qty);
        }
        break;

    case 'remove':
        cart_set((string)($_POST['key'] ?? ''), 0);
        flash('info', 'Article retiré du panier.');
        break;

    case 'promo':
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $totals = cart_totals(cart_lines(), $code);
        if ($totals['promo']) {
            $_SESSION['promo_code'] = $code;
            flash('success', 'Code ' . $code . ' appliqué : ' . promo_label($totals['promo']));
        } else {
            flash('error', $totals['promo_error'] ?? 'Code promo invalide.');
        }
        break;

    case 'remove_promo':
        unset($_SESSION['promo_code']);
        break;
}

redirect('cart.php');
