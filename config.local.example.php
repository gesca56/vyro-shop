<?php
/**
 * VYRO — Configuration de PRODUCTION
 * 1. Copiez ce fichier en « config.local.php » sur l'hébergeur (jamais publié ailleurs).
 * 2. Remplissez les valeurs ci-dessous.
 */

// Base de données fournie par l'hébergeur
define('DB_HOST', 'localhost');
define('DB_NAME', 'nom_de_la_base');
define('DB_USER', 'utilisateur');
define('DB_PASS', 'mot_de_passe');

// Adresse publique du site (https obligatoire pour Wave)
define('SITE_URL', 'https://vyroshop225.com');

// Clé secrète : mettez une longue chaîne aléatoire (ex. 64 caractères)
define('APP_SECRET', 'CHANGEZ-MOI-avec-une-longue-chaine-aleatoire');

// Wave Business → Portail Wave Business > Développeurs
//   - Clé API (avec la permission « Checkout API ») :
define('WAVE_API_KEY', '');
//   - Webhook : URL https://vyroshop225.com/wave-webhook.php, événement checkout.session.completed
//     puis collez ici le secret de signature fourni par Wave :
define('WAVE_WEBHOOK_SECRET', '');

// Email de contact affiché sur le site
define('CONTACT_EMAIL', 'contact@vyroshop225.com');
