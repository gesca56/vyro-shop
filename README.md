# VYRO — Boutique e-commerce streetwear (PHP / MySQL)

Site e-commerce mobile-first développé en PHP 8 natif (sans framework) + MySQL, conforme au cahier des charges VYRO.

## Installation (XAMPP / Laragon)

1. Placer le dossier dans `htdocs/VYRO` (XAMPP) — ou `www/VYRO` (Laragon).
2. Démarrer Apache et MySQL.
3. Ouvrir `http://localhost/VYRO/install.php` (ou `php install.php` en ligne de commande).
   Base déjà installée ? `upgrade.php` ajoute les nouveautés (Journal) **sans effacer** les données.
4. Boutique : `http://localhost/VYRO/` — Admin : `http://localhost/VYRO/admin/`

Comptes de démonstration :
- Admin : `admin@vyro.ci` / `admin123`
- Client : `client@vyro.ci` / `client123`

Codes promo de démo : `VYRO20` (-20 %, mis en avant), `WELCOME10`, `VYRO30` (dès 100 000), `DUO50` (2e article -50 %), `FREESHIP`, `VYRO5000`.

Configuration (BDD, URL de base, frais de livraison, WhatsApp, réseaux sociaux) : `vyro-config.php`.

## Structure

| Fichier | Rôle |
|---|---|
| `index.php` | Accueil : barre promo, hero, bannière promo, catégories, nouveautés, collections, best sellers, incontournables, avis, réseaux |
| `shop.php` | Catalogue : catégories, collections, nouveautés, promos, recherche, filtres taille/couleur/prix, tri, pagination |
| `product.php` | Fiche produit : galerie, vidéo, couleurs, tailles, guide des tailles, stock, avis, livraison/retours |
| `cart.php`, `cart-action.php` | Panier, quantités, codes promo, frais de livraison |
| `checkout.php`, `payment.php`, `order-confirmation.php` | Commande, paiement, confirmation |
| `track.php` | Suivi de commande (n° + téléphone/email) |
| `login.php`, `register.php`, `account.php` | Compte client : commandes, favoris, adresses, infos |
| `faq.php`, `contact.php`, `page.php` | FAQ, contact, pages légales |
| `blog.php`, `article.php` | Journal VYRO : drops, lookbooks, guides (Markdown simplifié, produits associés) |
| `admin/` | Tableau de bord, produits (dupliquer, actions groupées, ordre des photos), catégories & collections, Journal (CRUD, brouillons, programmation), commandes, clients, promotions, avis, messages |
| `assets/css/style.css` | Thème sombre (inspiration drop / 667 EKIP) — l’admin réutilise le fichier avec des variables claires |
| `database/make_logo.php` | Régénère les logos transparents, la couronne et les favicons depuis `assets/img/vyro-poster.png` |
| `newsletter.php`, `admin/newsletter.php` | Newsletter « Ne manque pas le prochain drop » + export CSV |
| `assets/css/motion.css` | Transitions de page (View Transitions API), animations, styles du Journal, correctifs mobiles |
| `img.php` | Visuels produits générés (SVG) tant qu'aucune photo n'est uploadée |

## À faire avant la mise en production

- **Paiement** : `payment.php` est en **mode démonstration** (paiement simulé). Brancher un agrégateur ivoirien
  (CinetPay, PayDunya, Wave Business API…) et valider les paiements par webhook côté serveur.
- **Emails** : `send_order_email()` utilise `mail()` ; configurer un SMTP (ou PHPMailer) pour l'envoi réel.
- **Photos** : uploader les vraies photos produits depuis l'admin (elles remplacent automatiquement les visuels générés).
- **Réseaux sociaux** : la section « Follow the VYRO movement » affiche des tuiles qui renvoient vers les comptes ;
  l'affichage des vraies publications nécessite l'API Instagram/TikTok ou un widget.
- **Mentions légales** : compléter RCCM, NCC, hébergeur, etc. dans `page.php`.
- Changer les mots de passe de démo, bloquer `install.php` (voir `.htaccess`), activer HTTPS.
