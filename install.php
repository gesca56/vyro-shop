<?php
/**
 * VYRO — Installation de la base de données + données de démonstration.
 * Navigateur : http://localhost/VYRO/install.php   |   CLI : php install.php
 */
// En production, les accès viennent de config.local.php
if (is_file(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';
// … ou des variables d'environnement (Render / Docker)
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_PORT') || define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'vyro');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
defined('DB_SSL') || define('DB_SSL', getenv('DB_SSL') === '1');

$cli = PHP_SAPI === 'cli';
$out = [];

try {
    $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (DB_SSL) $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, $pdoOptions);

    $exists = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'")->fetchColumn();
    // Une boutique existe déjà : réinstallation interdite depuis le navigateur (sauf ALLOW_REINSTALL)
    $hasShop = false;
    if ($exists) {
        try {
            $hasShop = (bool)$pdo->query('SELECT COUNT(*) FROM `' . DB_NAME . '`.users')->fetchColumn();
        } catch (PDOException $e) {
        }
    }
    if ($hasShop && !$cli && !(defined('ALLOW_REINSTALL') && ALLOW_REINSTALL)) {
        http_response_code(403);
        exit('<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto"><h1>VYRO est déjà installé</h1><p>Pour protéger vos données, la réinstallation est désactivée ici. Supprimez install.php du serveur.</p><p><a href="index.php">Aller au site</a></p></div>');
    }
    if ($exists && !$cli && !isset($_GET['force'])) {
        echo '<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto">
              <h1>VYRO est déjà installé</h1>
              <p>Réinstaller effacera toutes les données (produits, commandes, clients).</p>
              <p><a href="?force=1" style="background:#111;color:#fff;padding:12px 20px;text-decoration:none">Réinstaller</a>
              &nbsp; <a href="index.php">Aller au site</a></p></div>';
        exit;
    }

    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        // Hébergement mutualisé : la base existe déjà (créée depuis le panneau de l'hébergeur)
    }
    $pdo->exec('USE `' . DB_NAME . '`');
    $pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));
    $out[] = 'Tables créées.';

    /* ---- Utilisateurs ---- */
    $u = $pdo->prepare('INSERT INTO users (first_name,last_name,email,phone,password,is_admin,created_at) VALUES (?,?,?,?,?,?,?)');
    $u->execute(['Admin', 'VYRO', 'admin@vyro.ci', '+225 07 00 00 00 00', password_hash('admin123', PASSWORD_DEFAULT), 1, date('Y-m-d H:i:s', strtotime('-60 days'))]);
    $u->execute(['Aïcha', 'Koné', 'client@vyro.ci', '+225 07 11 22 33 44', password_hash('client123', PASSWORD_DEFAULT), 0, date('Y-m-d H:i:s', strtotime('-30 days'))]);
    $clientId = (int)$pdo->lastInsertId();
    $clients = [['Yann', 'Kouassi', 'yann.k@mail.ci', '+225 05 44 55 66 77'], ['Mariam', 'Traoré', 'mariam.t@mail.ci', '+225 01 23 45 67 89'],
        ['Serge', 'Bamba', 'serge.b@mail.ci', '+225 07 98 76 54 32'], ['Fatou', 'Diallo', 'fatou.d@mail.ci', '+225 05 12 12 12 12']];
    $clientIds = [$clientId];
    foreach ($clients as $i => $c) {
        $u->execute([$c[0], $c[1], $c[2], $c[3], password_hash('client123', PASSWORD_DEFAULT), 0, date('Y-m-d H:i:s', strtotime('-' . (25 - $i * 4) . ' days'))]);
        $clientIds[] = (int)$pdo->lastInsertId();
    }
    $pdo->prepare('INSERT INTO addresses (user_id,label,city,commune,address,phone) VALUES (?,?,?,?,?,?)')
        ->execute([$clientId, 'Domicile', 'Abidjan', 'Cocody', 'Riviera 3, rue des Jardins, villa 12', '+225 07 11 22 33 44']);

    /* ---- Catégories ---- */
    $cats = [
        ['Vêtements', 'vetements', [['T-shirts', 'tshirts'], ['Hoodies', 'hoodies'], ['Sweats', 'sweats'], ['Pantalons', 'pantalons'], ['Ensembles', 'ensembles'], ['Vestes', 'vestes']]],
        ['Chaussures', 'chaussures', [['Sneakers', 'sneakers'], ['Running', 'running'], ['Lifestyle', 'lifestyle']]],
        ['Accessoires', 'accessoires', [['Sacs', 'sacs'], ['Casquettes', 'casquettes'], ['Bijoux', 'bijoux'], ['Lunettes', 'lunettes']]],
    ];
    $catIds = [];
    $ci = $pdo->prepare('INSERT INTO categories (parent_id,name,slug,sort_order) VALUES (?,?,?,?)');
    foreach ($cats as $i => [$name, $slug, $children]) {
        $ci->execute([null, $name, $slug, $i]);
        $pid = (int)$pdo->lastInsertId();
        $catIds[$slug] = $pid;
        foreach ($children as $j => [$cn, $cs]) {
            $ci->execute([$pid, $cn, $cs, $j]);
            $catIds[$cs] = (int)$pdo->lastInsertId();
        }
    }

    /* ---- Collections ---- */
    $cols = [
        ['New Drop', 'new-drop', 'Les dernières pièces, fraîchement débarquées.', 'dark'],
        ['Essentials', 'essentials', 'Les basiques premium à porter tous les jours.', 'light'],
        ['Street Collection', 'street-collection', 'L’attitude de la rue, la qualité en plus.', 'accent'],
        ['Summer Collection', 'summer-collection', 'Léger, frais, prêt pour le soleil d’Abidjan.', 'sand'],
    ];
    $colIds = [];
    $cs = $pdo->prepare('INSERT INTO collections (name,slug,tagline,theme,sort_order) VALUES (?,?,?,?,?)');
    foreach ($cols as $i => $c) {
        $cs->execute([...$c, $i]);
        $colIds[$c[1]] = (int)$pdo->lastInsertId();
    }

    /* ---- Produits ---- */
    $CL = 'S,M,L,XL,XXL';
    $SH = '39,40,41,42,43,44,45';
    // nom, sous-cat, collection, prix, ancien prix, tailles, couleurs, stock, visuel, new, featured, ventes, description
    $products = [
        ['VYRO Oversized Tee', 'tshirts', 'essentials', 39900, 49900, $CL, 'Noir,Blanc,Gris', 42, 'tee', 1, 1, 318, 'Le t-shirt signature VYRO. Coupe oversized, coton épais 240 g/m², col côtelé renforcé et logo brodé poitrine. Une pièce essentielle, pensée pour durer.'],
        ['VYRO Core Logo Tee', 'tshirts', 'essentials', 24900, null, $CL, 'Blanc,Noir,Beige', 60, 'tee', 0, 1, 254, 'T-shirt coupe droite en coton peigné 200 g/m². Logo VYRO sérigraphié. Le basique parfait pour tous les jours.'],
        ['Street Signature Tee', 'tshirts', 'street-collection', 29900, 34900, $CL, 'Noir,Kaki', 25, 'tee', 1, 0, 97, 'Tee graphique issu de la Street Collection. Impression dos grand format, coupe boxy.'],
        ['Summer Linen Tee', 'tshirts', 'summer-collection', 27900, null, 'S,M,L,XL', 'Crème,Sable,Blanc', 30, 'tee', 1, 0, 41, 'T-shirt en mélange lin et coton, respirant et léger. Idéal pour la chaleur.'],
        ['VYRO Heavy Hoodie', 'hoodies', 'new-drop', 59900, 69900, $CL, 'Noir,Gris chiné,Bleu nuit', 18, 'hoodie', 1, 1, 203, 'Hoodie heavyweight 400 g/m², molleton brossé intérieur, capuche doublée et poche kangourou. Coupe légèrement oversized.'],
        ['Essential Zip Hoodie', 'hoodies', 'essentials', 54900, null, $CL, 'Gris chiné,Noir', 14, 'hoodie', 0, 0, 88, 'Hoodie zippé en molleton premium, finitions côtelées. Un must-have de la garde-robe.'],
        ['Define Crewneck Sweat', 'sweats', 'new-drop', 44900, 52900, $CL, 'Beige,Noir,Vert', 22, 'sweat', 1, 0, 76, 'Sweat col rond « DEFINE YOUR STYLE » brodé. Molleton 350 g/m².'],
        ['VYRO Essentials Sweat', 'sweats', 'essentials', 39900, null, $CL, 'Gris chiné,Crème', 35, 'sweat', 0, 0, 132, 'Sweat classique au toucher doux, coupe régulière, poignets et ourlet côtelés.'],
        ['Cargo Street Pants', 'pantalons', 'street-collection', 49900, 59900, '28,30,32,34,36,38', 'Noir,Kaki,Beige', 16, 'pants', 1, 1, 189, 'Pantalon cargo en twill de coton, 6 poches, bas ajustables par cordon. Coupe relaxed.'],
        ['VYRO Jogger', 'pantalons', 'essentials', 34900, null, $CL, 'Noir,Gris chiné', 40, 'pants', 0, 0, 221, 'Jogging molleton coupe fuselée, taille élastiquée avec cordon et poches zippées.'],
        ['Summer Linen Pants', 'pantalons', 'summer-collection', 42900, null, 'S,M,L,XL', 'Crème,Sable', 12, 'pants', 1, 0, 23, 'Pantalon ample en lin, taille élastique. Élégance décontractée pour l’été.'],
        ['VYRO Tracksuit Set', 'ensembles', 'new-drop', 89900, 109900, $CL, 'Noir,Bleu nuit,Gris', 10, 'set', 1, 1, 145, 'Ensemble sweat + jogger assorti. Molleton premium, logo brodé ton sur ton.'],
        ['Summer Set Short', 'ensembles', 'summer-collection', 64900, null, 'S,M,L,XL', 'Sable,Blanc', 9, 'set', 0, 0, 38, 'Ensemble chemise et short en coton léger. Le combo parfait pour le weekend.'],
        ['VYRO Coach Jacket', 'vestes', 'street-collection', 74900, 89900, $CL, 'Noir,Kaki', 8, 'jacket', 1, 0, 64, 'Veste coach en nylon déperlant, boutons pression, doublure légère.'],
        ['Varsity Jacket VYRO', 'vestes', 'new-drop', 99900, null, $CL, 'Noir,Rouge', 6, 'jacket', 1, 1, 52, 'Veste teddy style varsity, corps en laine mélangée, manches en similicuir, patchs brodés.'],
        ['VYRO Court Low', 'sneakers', 'essentials', 69900, 84900, $SH, 'Blanc,Noir', 20, 'sneaker', 0, 1, 276, 'Sneaker basse en cuir premium, semelle cupsole cousue. Le classique VYRO.'],
        ['VYRO Street Runner 01', 'sneakers', 'new-drop', 79900, null, $SH, 'Noir,Gris,Blanc', 15, 'sneaker', 1, 1, 118, 'Sneaker au design affûté, empiècements suédine et mesh, semelle légère.'],
        ['Velocity Run', 'running', 'street-collection', 74900, 89900, $SH, 'Noir,Blanc,Bleu nuit', 18, 'runner', 1, 0, 91, 'Chaussure de running avec semelle amortissante haute densité et tige en mesh respirant.'],
        ['Air Stride', 'running', 'summer-collection', 64900, null, $SH, 'Blanc,Gris', 11, 'runner', 0, 0, 57, 'Running légère et respirante pour vos sessions quotidiennes.'],
        ['VYRO High Classic', 'lifestyle', 'street-collection', 84900, 99900, $SH, 'Noir,Blanc,Beige', 9, 'lifestyle', 0, 1, 133, 'Montante lifestyle en toile épaisse et cuir, semelle vulcanisée.'],
        ['VYRO Crossbody Bag', 'sacs', 'street-collection', 29900, 34900, 'Unique', 'Noir,Kaki', 25, 'bag', 1, 0, 167, 'Sac bandoulière en nylon technique, poche zippée frontale, sangle réglable.'],
        ['VYRO Dad Cap', 'casquettes', 'essentials', 19900, null, 'Unique', 'Noir,Blanc,Beige,Bleu nuit', 50, 'cap', 0, 1, 298, 'Casquette 6 panneaux en coton sergé, logo VYRO brodé, attache réglable.'],
        ['Chain V Pendant', 'bijoux', 'new-drop', 24900, null, 'Unique', 'Argent,Or', 30, 'jewelry', 1, 0, 72, 'Chaîne en acier inoxydable avec pendentif V gravé. Ne noircit pas.'],
        ['VYRO Shades 01', 'lunettes', 'summer-collection', 22900, 27900, 'Unique', 'Noir,Marron', 20, 'glasses', 1, 0, 84, 'Lunettes de soleil rectangulaires, verres UV400, monture acétate.'],
        ['Bucket Hat Summer', 'casquettes', 'summer-collection', 17900, null, 'Unique', 'Sable,Noir', 0, 'cap', 0, 0, 45, 'Bob en coton léger, parfait pour l’été. (Réassort bientôt)'],
    ];
    $pi = $pdo->prepare('INSERT INTO products (category_id,collection_id,name,slug,description,price,old_price,sizes,colors,stock,visual,is_new,is_featured,sales_count,created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $productIds = [];
    foreach ($products as $i => $p) {
        $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $p[0])), '-');
        $pi->execute([$catIds[$p[1]], $colIds[$p[2]], $p[0], $slug, $p[12], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10], $p[11],
            date('Y-m-d H:i:s', strtotime('-' . ($p[9] ? $i : 30 + $i) . ' days'))]);
        $productIds[] = ['id' => (int)$pdo->lastInsertId(), 'name' => $p[0], 'price' => $p[3], 'visual' => $p[8], 'sizes' => explode(',', $p[5]), 'colors' => explode(',', $p[6])];
    }
    $out[] = count($products) . ' produits ajoutés.';

    /* ---- Avis ---- */
    $reviews = [
        [0, 'Aïcha', 'Cocody', 5, 'La qualité du coton est dingue, le tee tombe parfaitement. Je recommande à 100 % !'],
        [0, 'Yann', 'Marcory', 5, 'Livré en 24h à Abidjan, emballage propre. La coupe oversized est parfaite.'],
        [4, 'Mariam', 'Yopougon', 5, 'Le hoodie est super épais et chaud, parfait pour les soirées. Taille normalement.'],
        [4, 'Serge', 'Plateau', 4, 'Très bonne qualité, juste un peu grand. Prenez votre taille habituelle ou une en dessous.'],
        [8, 'Fatou', 'Bingerville', 5, 'Le cargo est trop stylé, beaucoup de compliments. Les poches sont pratiques.'],
        [15, 'Kevin', 'Cocody', 5, 'Les Court Low sont confortables dès le premier jour. Cuir de qualité.'],
        [15, 'Ange', 'Treichville', 4, 'Très belles sneakers, livraison rapide. Je voulais juste plus de coloris.'],
        [11, 'Ibrahim', 'Koumassi', 5, 'L’ensemble est premium, on sent la différence avec les autres marques.'],
        [21, 'Nadia', 'Adjamé', 5, 'Ma casquette préférée, le logo brodé est très propre.'],
        [16, 'Junior', 'Port-Bouët', 5, 'Les Street Runner sont une dinguerie. Légères et stylées.'],
        [1, 'Chloé', 'Grand-Bassam', 4, 'Basique de qualité, le blanc n’est pas transparent. Top.'],
        [19, 'Moussa', 'Bouaké', 5, 'Livrées à Bouaké en 3 jours. Les High Classic sont magnifiques.'],
        [20, 'Estelle', 'Cocody', 5, 'Sac pratique et stylé, parfait pour sortir. Paiement Wave ultra simple.'],
        [23, 'Yao', 'Yamoussoukro', 4, 'Belles lunettes, bonne protection. Livraison un peu longue en région.'],
    ];
    $ri = $pdo->prepare('INSERT INTO reviews (product_id,name,city,rating,comment,verified,created_at) VALUES (?,?,?,?,?,1,?)');
    foreach ($reviews as $i => $r) {
        $ri->execute([$productIds[$r[0]]['id'], $r[1], $r[2], $r[3], $r[4], date('Y-m-d H:i:s', strtotime('-' . ($i * 2 + 1) . ' days'))]);
    }

    /* ---- Codes promo ---- */
    $promo = $pdo->prepare('INSERT INTO promo_codes (code,type,value,min_amount,label,description,is_featured,ends_at) VALUES (?,?,?,?,?,?,?,?)');
    $promo->execute(['VYRO20', 'percent', 20, 0, 'DROP WEEKEND', '-20 % sur toute la collection', 1, date('Y-m-d', strtotime('+30 days'))]);
    $promo->execute(['WELCOME10', 'percent', 10, 0, 'Bienvenue', '-10 % sur votre première commande', 0, null]);
    $promo->execute(['VYRO30', 'percent', 30, 100000, 'Big Drop', '-30 % dès 100 000 FCFA d’achat', 0, null]);
    $promo->execute(['DUO50', 'bogo50', 0, 0, '1 acheté = 1 à -50 %', 'Le 2e article à moitié prix', 0, null]);
    $promo->execute(['FREESHIP', 'free_shipping', 0, 0, 'Livraison offerte', 'Livraison gratuite sans minimum', 0, null]);
    $promo->execute(['VYRO5000', 'fixed', 5000, 40000, 'Remise 5 000', '-5 000 FCFA dès 40 000 FCFA', 0, null]);

    /* ---- Commandes de démonstration ---- */
    $statuses = ['delivered', 'delivered', 'delivered', 'out_for_delivery', 'shipped', 'preparing', 'confirmed', 'delivered', 'delivered', 'shipped', 'confirmed', 'delivered', 'cancelled', 'delivered'];
    $methods = ['orange_money', 'wave', 'mtn_momo', 'card', 'wave', 'cod', 'orange_money', 'moov_money'];
    $communes = ['Cocody', 'Marcory', 'Yopougon', 'Plateau', 'Treichville', 'Koumassi'];
    $flow = ['confirmed', 'preparing', 'shipped', 'out_for_delivery', 'delivered'];
    $oi = $pdo->prepare('INSERT INTO orders (number,user_id,first_name,last_name,phone,email,country,city,commune,address,subtotal,discount,shipping,total,promo_code,payment_method,payment_status,payment_ref,status,tracking_number,created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $ii = $pdo->prepare('INSERT INTO order_items (order_id,product_id,name,visual,price,size,color,qty) VALUES (?,?,?,?,?,?,?,?)');
    $hi = $pdo->prepare('INSERT INTO order_status_history (order_id,status,note,created_at) VALUES (?,?,?,?)');
    $allUsers = $pdo->query('SELECT * FROM users WHERE is_admin = 0')->fetchAll(PDO::FETCH_ASSOC);
    mt_srand(42);
    foreach ($statuses as $n => $status) {
        $user = $allUsers[$n % count($allUsers)];
        $created = strtotime('-' . (20 - $n) . ' days ' . mt_rand(8, 21) . ':' . mt_rand(10, 59));
        $items = [];
        $sub = 0;
        for ($k = 0, $max = mt_rand(1, 3); $k < $max; $k++) {
            $p = $productIds[mt_rand(0, count($productIds) - 2)];
            $qty = mt_rand(1, 2);
            $items[] = [$p, $qty];
            $sub += $p['price'] * $qty;
        }
        $promoCode = $n % 4 === 0 ? 'VYRO20' : null;
        $discount = $promoCode ? (int)round($sub * 0.2) : 0;
        $ship = ($sub - $discount) >= 60000 ? 0 : 2000;
        $total = $sub - $discount + $ship;
        $method = $methods[$n % count($methods)];
        $paid = $status === 'cancelled' ? 'failed' : (($method === 'cod' && $status !== 'delivered') ? 'pending' : 'paid');
        $number = 'VY' . date('ymd', $created) . strtoupper(substr(md5((string)$n), 0, 4));
        $tracking = in_array($status, ['shipped', 'out_for_delivery', 'delivered'], true) ? 'CI' . strtoupper(substr(md5('t' . $n), 0, 10)) : null;
        $oi->execute([$number, $user['id'], $user['first_name'], $user['last_name'], $user['phone'], $user['email'], 'Côte d\'Ivoire', 'Abidjan',
            $communes[$n % count($communes)], 'Rue ' . (10 + $n) . ', Résidence Les Palmiers', $sub, $discount, $ship, $total, $promoCode,
            $method, $paid, $paid === 'paid' ? 'PAY-' . strtoupper(substr(md5('p' . $n), 0, 8)) : null, $status, $tracking, date('Y-m-d H:i:s', $created)]);
        $oid = (int)$pdo->lastInsertId();
        foreach ($items as [$p, $qty]) {
            $ii->execute([$oid, $p['id'], $p['name'], $p['visual'], $p['price'], $p['sizes'][mt_rand(0, count($p['sizes']) - 1)], $p['colors'][0], $qty]);
        }
        $steps = $status === 'cancelled' ? ['confirmed', 'cancelled'] : array_slice($flow, 0, array_search($status, $flow, true) + 1);
        foreach ($steps as $si => $st) {
            $hi->execute([$oid, $st, null, date('Y-m-d H:i:s', $created + $si * 86400 / 2)]);
        }
    }
    $out[] = count($statuses) . ' commandes de démonstration créées.';

    /* ---- Journal ---- */
    require __DIR__ . '/database/seed_posts.php';
    $out[] = seed_posts($pdo) . ' articles de blog ajoutés.';

    // Dossiers uploads
    foreach (['products', 'posts'] as $dir) {
        if (!is_dir(__DIR__ . '/uploads/' . $dir)) {
            mkdir(__DIR__ . '/uploads/' . $dir, 0775, true);
        }
    }

    $out[] = 'Installation terminée.';
    $out[] = 'Admin : admin@vyro.ci / admin123';
    $out[] = 'Client démo : client@vyro.ci / client123';
} catch (Throwable $e) {
    $out[] = 'ERREUR : ' . $e->getMessage();
}

if ($cli) {
    echo implode(PHP_EOL, $out) . PHP_EOL;
} else {
    echo '<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto"><h1>Installation VYRO</h1><ul>';
    foreach ($out as $l) echo '<li>' . htmlspecialchars($l) . '</li>';
    echo '</ul><p><a href="index.php" style="background:#111;color:#fff;padding:12px 20px;text-decoration:none">Voir la boutique</a>
          &nbsp; <a href="admin/">Administration</a></p></div>';
}
