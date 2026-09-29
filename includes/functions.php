<?php
/**
 * VYRO — Fonctions utilitaires
 */

/* ---------- Général ---------- */

function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = __DIR__ . '/../assets/' . $path;
    $v = file_exists($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path): void
{
    if (!preg_match('#^(https?:)?/#', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function price(int|float|null $amount): string
{
    return number_format((float)$amount, 0, ',', ' ') . ' ' . CURRENCY;
}

function slugify(string $text): string
{
    // Table explicite : iconv//TRANSLIT donne « 'e » sous Windows
    $text = strtr($text, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i', 'ñ' => 'n', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'œ' => 'oe', 'ø' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ý' => 'y', 'ß' => 'ss',
        'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Á' => 'A', 'Æ' => 'AE', 'Ç' => 'C', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Î' => 'I', 'Ï' => 'I',
        'Ô' => 'O', 'Ö' => 'O', 'Œ' => 'OE', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ÿ' => 'Y', '’' => '-', "'" => '-',
    ]);
    $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text));
    return trim($text, '-') ?: 'item';
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string)$t)) {
        http_response_code(419);
        exit('Session expirée. Veuillez recharger la page.');
    }
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function csv_list(?string $s): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string)$s)), 'strlen'));
}

function time_fr(string $datetime, bool $withTime = false): string
{
    $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $t = strtotime($datetime);
    $s = date('j', $t) . ' ' . $months[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
    return $withTime ? $s . ' à ' . date('H:i', $t) : $s;
}

/* ---------- Utilisateurs ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = q('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']])->fetch() ?: null;
        }
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && (int)$u['is_admin'] === 1;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Connectez-vous pour accéder à votre espace.');
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    return $u;
}

function require_admin(): array
{
    $u = current_user();
    if (!$u || !(int)$u['is_admin']) {
        redirect('admin/login.php');
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
}

/* ---------- Couleurs & visuels ---------- */

function color_map(): array
{
    return [
        'Noir' => '#111111', 'Blanc' => '#F4F4F2', 'Gris' => '#8E8E8E', 'Gris chiné' => '#B5B5B5',
        'Beige' => '#D8C7A8', 'Crème' => '#EFE7DA', 'Kaki' => '#5B5E3C', 'Bleu nuit' => '#1D2A44',
        'Rouge' => '#B3261E', 'Marron' => '#5A3D2B', 'Or' => '#C9A646', 'Argent' => '#C0C0C0',
        'Vert' => '#2F5D3A', 'Sable' => '#C8B48C', 'Lime' => '#D4FF3A',
    ];
}

function color_hex(string $name): string
{
    return color_map()[$name] ?? '#8E8E8E';
}

function visual_url(string $visual, ?string $color = null, string $bg = ''): string
{
    $hex = ltrim(color_hex($color ?? 'Noir'), '#');
    return url('img.php?v=' . urlencode($visual) . '&c=' . $hex . ($bg ? '&bg=' . $bg : '') . '&r=2'); // r = version (cache navigateur)
}

/** Liste des images d'un produit : photos uploadées, sinon visuels générés par couleur */
function product_gallery(array $p): array
{
    $imgs = q('SELECT path FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$p['id']])->fetchAll();
    $out = [];
    foreach ($imgs as $i) {
        $out[] = ['src' => UPLOAD_URL . $i['path'], 'color' => null];
    }
    if (!$out) {
        $colors = csv_list($p['colors']) ?: ['Noir'];
        foreach ($colors as $c) {
            $out[] = ['src' => visual_url($p['visual'], $c), 'color' => $c];
        }
        $out[] = ['src' => visual_url($p['visual'], $colors[0], 'l'), 'color' => $colors[0]];
    }
    return $out;
}

function product_thumb(array $p, ?string $color = null): string
{
    static $cache = [];
    if (!$color) {
        if (!array_key_exists($p['id'], $cache)) {
            $cache[$p['id']] = q('SELECT path FROM product_images WHERE product_id = ? ORDER BY sort_order, id LIMIT 1', [$p['id']])->fetchColumn() ?: null;
        }
        if ($cache[$p['id']]) {
            return UPLOAD_URL . $cache[$p['id']];
        }
    }
    $colors = csv_list($p['colors'] ?? '');
    return visual_url($p['visual'] ?? 'tee', $color ?: ($colors[0] ?? 'Noir'));
}

/* ---------- Produits ---------- */

function discount_percent(array $p): int
{
    if (!empty($p['old_price']) && $p['old_price'] > $p['price']) {
        return (int)round(100 - ($p['price'] * 100 / $p['old_price']));
    }
    return 0;
}

function product_rating(int $productId): array
{
    $r = q('SELECT COUNT(*) n, COALESCE(AVG(rating),0) avg FROM reviews WHERE product_id = ? AND approved = 1', [$productId])->fetch();
    return ['count' => (int)$r['n'], 'avg' => round((float)$r['avg'], 1)];
}

function stars(float $rating, string $cls = ''): string
{
    $html = '<span class="stars ' . $cls . '" aria-label="Note ' . $rating . ' sur 5">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $rating >= $i ? 'full' : ($rating >= $i - 0.5 ? 'half' : 'empty');
        $html .= '<i class="star ' . $fill . '"></i>';
    }
    return $html . '</span>';
}

function favorite_ids(): array
{
    static $ids = null;
    if ($ids === null) {
        $u = current_user();
        $ids = $u ? array_map('intval', q('SELECT product_id FROM favorites WHERE user_id = ?', [$u['id']])->fetchAll(PDO::FETCH_COLUMN)) : [];
    }
    return $ids;
}

function product_card(array $p): string
{
    $pct = discount_percent($p);
    $colors = csv_list($p['colors']);
    $sizes = csv_list($p['sizes']);
    $link = url('product.php?slug=' . urlencode($p['slug']));
    $fav = in_array((int)$p['id'], favorite_ids(), true);
    $inStock = (int)$p['stock'] > 0;
    $single = count($sizes) <= 1;
    ob_start(); ?>
    <article class="card">
        <a href="<?= $link ?>" class="card-media">
            <img src="<?= e(product_thumb($p)) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <div class="card-badges">
                <?php if ($p['is_new']): ?><span class="badge badge-new">New</span><?php endif; ?>
                <?php if ($pct): ?><span class="badge badge-sale">-<?= $pct ?>%</span><?php endif; ?>
                <?php if (!$inStock): ?><span class="badge badge-out">Sold out</span><?php endif; ?>
            </div>
        </a>
        <button class="fav-btn <?= $fav ? 'active' : '' ?>" data-fav="<?= (int)$p['id'] ?>" aria-label="Ajouter aux favoris">
            <svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-9.5-9.2C1.1 8.5 3.2 5 6.6 5c2 0 3.4 1.1 4.4 2.5C12 6.1 13.4 5 15.4 5c3.4 0 5.5 3.5 4.1 6.8C19.5 16.4 12 21 12 21z"/></svg>
        </button>
        <?php if ($inStock): ?>
            <form method="post" action="<?= url('cart-action.php') ?>" class="quick-add" data-quick-add>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="color" value="<?= e($colors[0] ?? '') ?>">
                <?php if ($single): ?>
                    <input type="hidden" name="size" value="<?= e($sizes[0] ?? '') ?>">
                <?php else: ?>
                    <div class="quick-sizes" role="group" aria-label="Choisir une taille">
                        <span>Taille</span>
                        <?php foreach ($sizes as $s): ?>
                            <button name="size" value="<?= e($s) ?>"><?= e($s) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
        <div class="card-body">
            <a href="<?= $link ?>" class="card-title"><?= e($p['name']) ?></a>
            <div class="card-row">
                <div class="card-price">
                    <span class="price"><?= price($p['price']) ?></span>
                    <?php if ($pct): ?><s class="old-price"><?= price($p['old_price']) ?></s><?php endif; ?>
                </div>
                <?php if ($inStock): ?>
                    <button type="<?= $single ? 'submit' : 'button' ?>" class="card-plus" <?= $single ? 'form="qa-' . (int)$p['id'] . '"' : 'data-quick-open' ?> aria-label="Ajouter <?= e($p['name']) ?> au panier">[+]</button>
                <?php endif; ?>
            </div>
            <?php if (count($colors) > 1): ?>
                <div class="swatches">
                    <?php foreach (array_slice($colors, 0, 5) as $c): ?>
                        <span class="swatch" style="--sw:<?= color_hex($c) ?>" title="<?= e($c) ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($p['show_sales'])): ?>
                <div class="card-meta"><?= stars((float)$p['rating_avg'], 'sm') ?> <span><?= (int)$p['sales_count'] ?> vendus</span></div>
            <?php endif; ?>
        </div>
    </article>
    <?php $html = ob_get_clean();
    // id du formulaire rapide (pour le bouton [+] placé hors du <form>)
    return str_replace('data-quick-add>', 'data-quick-add id="qa-' . (int)$p['id'] . '">', $html);
}

function products_query(string $where = '1', array $params = [], string $order = 'p.created_at DESC', ?int $limit = null, int $offset = 0): array
{
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id = p.id AND r.approved = 1) AS rating_avg,
                   (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.approved = 1) AS rating_count
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1 AND ($where)
            ORDER BY $order" . ($limit ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '');
    return q($sql, $params)->fetchAll();
}

function categories_tree(): array
{
    $all = q('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll();
    $tree = [];
    foreach ($all as $c) {
        if (!$c['parent_id']) {
            $tree[$c['id']] = $c + ['children' => []];
        }
    }
    foreach ($all as $c) {
        if ($c['parent_id'] && isset($tree[$c['parent_id']])) {
            $tree[$c['parent_id']]['children'][] = $c;
        }
    }
    return $tree;
}

/* ---------- Panier ---------- */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_key(int $pid, string $size, string $color): string
{
    return $pid . '|' . $size . '|' . $color;
}

function cart_add(int $pid, string $size, string $color, int $qty): array
{
    $p = q('SELECT * FROM products WHERE id = ? AND is_active = 1', [$pid])->fetch();
    if (!$p) {
        return [false, 'Produit introuvable.'];
    }
    $sizes = csv_list($p['sizes']);
    $colors = csv_list($p['colors']);
    if ($sizes && !in_array($size, $sizes, true)) {
        return [false, 'Veuillez choisir une taille.'];
    }
    if ($colors && !in_array($color, $colors, true)) {
        $color = $colors[0];
    }
    $key = cart_key($pid, $size, $color);
    $current = $_SESSION['cart'][$key] ?? 0;
    $inCart = 0;
    foreach (cart() as $k => $q) {
        if ((int)explode('|', $k)[0] === $pid) $inCart += $q;
    }
    if ($inCart + $qty > (int)$p['stock']) {
        return [false, $p['stock'] > 0 ? 'Stock insuffisant (' . (int)$p['stock'] . ' disponible(s)).' : 'Produit épuisé.'];
    }
    $_SESSION['cart'][$key] = $current + max(1, $qty);
    return [true, $p['name'] . ' ajouté au panier.'];
}

function cart_set(string $key, int $qty): void
{
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } elseif (isset($_SESSION['cart'][$key])) {
        $pid = (int)explode('|', $key)[0];
        $stock = (int)q('SELECT stock FROM products WHERE id = ?', [$pid])->fetchColumn();
        $_SESSION['cart'][$key] = min($qty, max(1, $stock));
    }
}

function cart_count(): int
{
    return array_sum(cart());
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['promo_code']);
}

/** Lignes du panier enrichies avec les données produit */
function cart_lines(): array
{
    $lines = [];
    foreach (cart() as $key => $qty) {
        [$pid, $size, $color] = array_pad(explode('|', $key), 3, '');
        $p = q('SELECT * FROM products WHERE id = ? AND is_active = 1', [(int)$pid])->fetch();
        if (!$p) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $lines[] = [
            'key' => $key, 'product' => $p, 'size' => $size, 'color' => $color,
            'qty' => (int)$qty, 'unit' => (int)$p['price'], 'total' => (int)$p['price'] * (int)$qty,
        ];
    }
    return $lines;
}

/* ---------- Promotions ---------- */

function find_promo(?string $code): ?array
{
    if (!$code) return null;
    $p = q('SELECT * FROM promo_codes WHERE code = ? AND is_active = 1', [strtoupper(trim($code))])->fetch();
    if (!$p) return null;
    $today = date('Y-m-d');
    if ($p['starts_at'] && $today < $p['starts_at']) return null;
    if ($p['ends_at'] && $today > $p['ends_at']) return null;
    if ($p['usage_limit'] !== null && $p['used_count'] >= $p['usage_limit']) return null;
    return $p;
}

function promo_label(array $p): string
{
    return match ($p['type']) {
        'percent' => '-' . $p['value'] . ' %',
        'fixed' => '-' . price($p['value']),
        'free_shipping' => 'Livraison gratuite',
        'bogo50' => '1 acheté = 1 à -50 %',
        default => '',
    };
}

function featured_promo(): ?array
{
    $p = q("SELECT * FROM promo_codes WHERE is_active = 1 AND is_featured = 1
            AND (starts_at IS NULL OR starts_at <= CURDATE()) AND (ends_at IS NULL OR ends_at >= CURDATE())
            ORDER BY id DESC LIMIT 1")->fetch();
    return $p ?: null;
}

function shipping_fee(string $city, int $subtotal): int
{
    if ($subtotal >= FREE_SHIPPING_THRESHOLD) return 0;
    return (stripos($city, 'abidjan') !== false || $city === '') ? SHIPPING_FEE : SHIPPING_FEE_OTHER;
}

/** Calcule les totaux du panier avec promo éventuelle */
function cart_totals(array $lines, ?string $promoCode = null, string $city = ''): array
{
    $subtotal = array_sum(array_column($lines, 'total'));
    $promo = find_promo($promoCode);
    $discount = 0;
    $promoError = null;

    if ($promoCode && !$promo) {
        $promoError = 'Code promo invalide ou expiré.';
    } elseif ($promo && $subtotal < $promo['min_amount']) {
        $promoError = 'Ce code nécessite un minimum d\'achat de ' . price($promo['min_amount']) . '.';
        $promo = null;
    }

    if ($promo) {
        switch ($promo['type']) {
            case 'percent':
                $discount = (int)round($subtotal * $promo['value'] / 100);
                break;
            case 'fixed':
                $discount = min((int)$promo['value'], $subtotal);
                break;
            case 'bogo50':
                // Tous les 2 articles, le moins cher est à -50 %
                $units = [];
                foreach ($lines as $l) {
                    for ($i = 0; $i < $l['qty']; $i++) $units[] = $l['unit'];
                }
                rsort($units);
                foreach ($units as $i => $u) {
                    if ($i % 2 === 1) $discount += (int)round($u / 2);
                }
                break;
        }
    }

    $shipping = $lines ? shipping_fee($city, $subtotal - $discount) : 0;
    if ($promo && $promo['type'] === 'free_shipping') {
        $shipping = 0;
    }

    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total' => max(0, $subtotal - $discount + $shipping),
        'promo' => $promo,
        'promo_error' => $promoError,
        'free_left' => max(0, FREE_SHIPPING_THRESHOLD - ($subtotal - $discount)),
    ];
}

/* ---------- Commandes ---------- */

function order_statuses(): array
{
    return [
        'confirmed' => 'Commande confirmée',
        'preparing' => 'Préparation',
        'shipped' => 'Expédiée',
        'out_for_delivery' => 'En livraison',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];
}

function status_label(string $s): string
{
    return order_statuses()[$s] ?? $s;
}

function payment_methods(): array
{
    $m = [
        'wave' => ['label' => 'Wave', 'type' => 'mobile', 'color' => '#1DC8FF'],
        'orange_money' => ['label' => 'Orange Money', 'type' => 'mobile', 'color' => '#FF7900'],
        'mtn_momo' => ['label' => 'MTN MoMo', 'type' => 'mobile', 'color' => '#FFCC00'],
        'moov_money' => ['label' => 'Moov Money', 'type' => 'mobile', 'color' => '#3B8BD9'],
        'card' => ['label' => 'Carte bancaire', 'type' => 'card', 'color' => '#FFFFFF'],
        'cod' => ['label' => 'Paiement à la livraison', 'type' => 'cod', 'color' => '#6B6B6B'],
    ];
    // Pas de passerelle carte branchée : on ne propose pas un paiement qui ne débiterait rien
    if (!CARD_PAYMENT_ENABLED) unset($m['card']);
    return $m;
}

/** Libellés connus (y compris moyens désactivés, pour l'historique des commandes) */
function payment_label(string $m): string
{
    return payment_methods()[$m]['label'] ?? (['card' => 'Carte bancaire'][$m] ?? $m);
}

function payment_status_label(string $s): string
{
    return ['pending' => 'En attente', 'paid' => 'Payée', 'failed' => 'Échouée', 'refunded' => 'Remboursée'][$s] ?? $s;
}

function generate_order_number(): string
{
    do {
        $n = 'VY' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    } while (q('SELECT 1 FROM orders WHERE number = ?', [$n])->fetchColumn());
    return $n;
}

function add_status_history(int $orderId, string $status, ?string $note = null): void
{
    q('INSERT INTO order_status_history (order_id, status, note) VALUES (?,?,?)', [$orderId, $status, $note]);
}

/** Commande accessible par le visiteur courant (créée dans sa session ou liée à son compte) */
function find_own_order(string $number): ?array
{
    $o = q('SELECT * FROM orders WHERE number = ?', [$number])->fetch();
    if (!$o) return null;
    $u = current_user();
    $own = in_array($number, $_SESSION['my_orders'] ?? [], true) || ($u && (int)$o['user_id'] === (int)$u['id']) || is_admin();
    return $own ? $o : null;
}

function restore_order_stock(int $orderId): void
{
    foreach (q('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL', [$orderId])->fetchAll() as $it) {
        q('UPDATE products SET stock = stock + ?, sales_count = GREATEST(0, sales_count - ?) WHERE id = ?', [$it['qty'], $it['qty'], $it['product_id']]);
    }
}

/** Email de confirmation (nécessite un serveur SMTP configuré dans php.ini / sendmail en production) */
function send_order_email(array $order, string $subject, string $intro): void
{
    $track = (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . url('track.php?n=' . $order['number']);
    $body = "Bonjour {$order['first_name']},\n\n$intro\n\nCommande : {$order['number']}\nTotal : " . price($order['total'])
        . "\nPaiement : " . payment_label($order['payment_method']) . "\n\nSuivre ma commande : $track\n\nMerci d'avoir choisi VYRO.\nDefine your style.";
    $headers = 'From: VYRO <' . CONTACT_EMAIL . ">\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($order['email'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

function communes_abidjan(): array
{
    return ['Abobo', 'Adjamé', 'Attécoubé', 'Cocody', 'Koumassi', 'Marcory', 'Plateau', 'Port-Bouët',
        'Treichville', 'Yopougon', 'Bingerville', 'Songon', 'Anyama'];
}

function pickup_points(): array
{
    return ['VYRO Store — Cocody Riviera 2', 'Relais — Plateau (Cité Administrative)', 'Relais — Marcory Zone 4', 'Relais — Yopougon Siporex'];
}

/* ---------- Journal (blog) ---------- */

define('POST_UPLOAD_DIR', __DIR__ . '/../uploads/posts/');
define('POST_UPLOAD_URL', BASE_URL . '/uploads/posts/');

function post_categories(): array
{
    return ['Drop', 'Lookbook', 'Guide', 'Culture', 'News'];
}

function post_cover(array $post, string $bg = ''): string
{
    return $post['cover'] ? POST_UPLOAD_URL . $post['cover'] : visual_url($post['visual'], $post['visual_color'], $bg);
}

function reading_time(string $content): int
{
    return max(1, (int)ceil(str_word_count(strip_tags($content)) / 200));
}

/** Formatage inline : **gras**, *italique*, [lien](url) — le texte est échappé avant. */
function md_inline(string $s): string
{
    $s = e($s);
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/', '<em>$1</em>', $s);
    return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
        $href = html_entity_decode($m[2], ENT_QUOTES);
        if (!preg_match('#^(https?://|/|mailto:|tel:|[\w-]+\.php)#i', $href)) return $m[1];
        $ext = preg_match('#^https?://#i', $href);
        return '<a href="' . e($href) . '"' . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
    }, $s);
}

/** Markdown simplifié : ## titres, - listes, 1. listes, > citations, paragraphes. Sûr (pas de HTML brut). */
function render_content(string $text): string
{
    $html = '';
    foreach (preg_split("/\n\s*\n/", str_replace("\r", '', trim($text))) as $block) {
        $lines = explode("\n", trim($block));
        // Titre en début de bloc, éventuellement suivi d'un paragraphe sans ligne vide
        while ($lines && preg_match('/^(#{2,3})\s+(.*)$/', $lines[0], $m)) {
            $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $html .= "<$tag>" . md_inline($m[2]) . "</$tag>";
            array_shift($lines);
        }
        if (!$lines) {
            continue;
        } elseif (preg_match('/^[-*]\s+/', $lines[0])) {
            $html .= '<ul>' . implode('', array_map(fn($l) => '<li>' . md_inline(preg_replace('/^[-*]\s+/', '', $l)) . '</li>', $lines)) . '</ul>';
        } elseif (preg_match('/^\d+[.)]\s+/', $lines[0])) {
            $html .= '<ol>' . implode('', array_map(fn($l) => '<li>' . md_inline(preg_replace('/^\d+[.)]\s+/', '', $l)) . '</li>', $lines)) . '</ol>';
        } elseif (str_starts_with($lines[0], '>')) {
            $html .= '<blockquote>' . implode('<br>', array_map(fn($l) => md_inline(ltrim($l, "> ")), $lines)) . '</blockquote>';
        } else {
            $html .= '<p>' . implode('<br>', array_map('md_inline', $lines)) . '</p>';
        }
    }
    return $html;
}

/** Enregistre une image uploadée (JPG/PNG/WEBP, 5 Mo max). Retourne le nom du fichier ou null. */
function save_uploaded_image(string $tmp, int $size, string $dir, string $prefix): ?string
{
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset($allowed[$mime]) || $size > 5 * 1024 * 1024) return null;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = $prefix . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    return move_uploaded_file($tmp, $dir . $name) ? $name : null;
}

/* ---------- Maintenance ---------- */

/** Annule les commandes payables en ligne restées impayées plus de 2h et libère leur stock. */
function expire_unpaid_orders(): void
{
    $rows = q("SELECT id FROM orders WHERE payment_status = 'pending' AND payment_method <> 'cod'
               AND status = 'confirmed' AND payment_ref IS NULL AND created_at < (NOW() - INTERVAL 2 HOUR)")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $id) {
        q("UPDATE orders SET status = 'cancelled', payment_status = 'failed' WHERE id = ?", [$id]);
        restore_order_stock((int)$id);
        add_status_history((int)$id, 'cancelled', 'Annulée automatiquement : paiement non reçu sous 2h');
    }
}

/** Visuel associé à une catégorie / collection / filtre (en-têtes et tuiles de l'accueil) */
function listing_visual(string $key): ?array
{
    $map = [
        'vetements' => ['hoodie', 'Gris chiné'], 'tshirts' => ['tee', 'Noir'], 'hoodies' => ['hoodie', 'Noir'], 'sweats' => ['sweat', 'Beige'],
        'pantalons' => ['pants', 'Kaki'], 'ensembles' => ['set', 'Bleu nuit'], 'vestes' => ['jacket', 'Noir'],
        'chaussures' => ['runner', 'Noir'], 'sneakers' => ['sneaker', 'Blanc'], 'running' => ['runner', 'Bleu nuit'], 'lifestyle' => ['lifestyle', 'Beige'],
        'accessoires' => ['bag', 'Kaki'], 'sacs' => ['bag', 'Noir'], 'casquettes' => ['cap', 'Beige'], 'bijoux' => ['jewelry', 'Or'], 'lunettes' => ['glasses', 'Marron'],
        'new-drop' => ['hoodie', 'Noir'], 'essentials' => ['tee', 'Blanc'], 'street-collection' => ['pants', 'Kaki'], 'summer-collection' => ['glasses', 'Marron'],
        'new' => ['sneaker', 'Noir'], 'promo' => ['set', 'Rouge'], 'all' => ['tee', 'Noir'],
    ];
    return $map[$key] ?? null;
}
