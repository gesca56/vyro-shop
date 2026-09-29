<?php
/**
 * VYRO — Initialisation de la base au démarrage du conteneur (CLI).
 *  - attend que la base réponde ;
 *  - tables absentes → installation (install.php) ; sinon mises à jour (upgrade.php) ;
 *  - applique ADMIN_EMAIL / ADMIN_PASSWORD, ou remplace le mot de passe de démo par un mot de passe aléatoire.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: 3306);
$name = getenv('DB_NAME') ?: 'vyro';
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if (getenv('DB_SSL') === '1') $opts[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';

$pdo = null;
for ($i = 1; $i <= 30; $i++) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', $opts);
        break;
    } catch (PDOException $e) {
        fwrite(STDERR, "[VYRO] Base injoignable ($i/30) : " . $e->getMessage() . "\n");
        sleep(2);
    }
}
if (!$pdo) {
    fwrite(STDERR, "[VYRO] Abandon : impossible de se connecter à la base. Vérifiez DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_SSL.\n");
    exit(1);
}

$installed = false;
try {
    $installed = (bool)$pdo->query("SELECT COUNT(*) FROM `$name`.`users`")->fetchColumn();
} catch (PDOException $e) {
    // tables absentes
}

if (!$installed) {
    echo "[VYRO] Première installation de la base…\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg("$root/install.php"), $code);
    if ($code !== 0) exit($code);
} else {
    passthru(PHP_BINARY . ' ' . escapeshellarg("$root/upgrade.php"));
}

// Identifiants administrateur : jamais « admin123 » en ligne
$pdo->exec("USE `$name`");
$admin = $pdo->query('SELECT id, email, password FROM users WHERE is_admin = 1 ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($admin) {
    $email = trim((string)getenv('ADMIN_EMAIL'));
    $pass = (string)getenv('ADMIN_PASSWORD');
    $username = trim((string)getenv('ADMIN_USERNAME'));
    if ($username !== '') {
        $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$username, $admin['id']]);
        echo "[VYRO] Nom d'utilisateur admin : $username\n";
    }
    if ($pass !== '') {
        $pdo->prepare('UPDATE users SET password = ?, email = COALESCE(NULLIF(?, \'\'), email) WHERE id = ?')
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $email, $admin['id']]);
        echo "[VYRO] Identifiants admin appliqués depuis ADMIN_EMAIL / ADMIN_PASSWORD.\n";
    } elseif (password_verify('admin123', $admin['password'])) {
        $random = bin2hex(random_bytes(6));
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($random, PASSWORD_DEFAULT), $admin['id']]);
        echo "[VYRO] ⚠ Mot de passe admin de démo remplacé. Connexion : {$admin['email']} / $random\n";
        echo "[VYRO]   (définissez ADMIN_PASSWORD dans Render pour choisir le vôtre)\n";
    }
}
echo "[VYRO] Base prête.\n";
