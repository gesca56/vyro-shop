<?php
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (DB_SSL) {
            // Certificats racine du système (image Docker Debian) — requis par les MySQL managés
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        }
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $e) {
            error_log('[VYRO] Connexion BDD impossible : ' . $e->getMessage());
            http_response_code(503);
            $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) || PHP_SAPI === 'cli';
            echo '<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto;color:#eee;background:#000">'
                . '<h1>VYRO — Boutique momentanément indisponible</h1>'
                . '<p>Merci de réessayer dans quelques instants.</p>'
                . ($local ? '<p style="color:#888">' . htmlspecialchars($e->getMessage()) . ' — <a style="color:#fff" href="' . BASE_URL . '/install.php">install.php</a></p>' : '')
                . '</div>';
            exit;
        }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
