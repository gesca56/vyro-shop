<?php
/** Aperçu du contenu d'un article (éditeur admin) */
require __DIR__ . '/../vyro-config.php';
require_admin();
if (!is_post()) exit;
csrf_check();
header('Content-Type: text/html; charset=utf-8');
echo render_content((string)($_POST['content'] ?? '')) ?: '<p class="muted">Rien à afficher.</p>';
