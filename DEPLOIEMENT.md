# Mettre VYRO en ligne

## 1. Le nom de domaine

`vyro.com` n'est **pas disponible** : il est enregistré depuis juillet 2025 (registraire NameBright), jusqu'en 2027.
NameBright revend souvent des domaines : on peut leur faire une offre, mais c'est en général cher.

Alternatives vérifiées le 29/09/2026 :

| Domaine | Statut |
|---|---|
| **vyroshop225.com** | ✅ libre — cohérent avec l'Instagram @vyroshop225 |
| vyro.shop | probablement libre — à confirmer chez le registraire |
| vyro.ci | à vérifier sur nic.ci (extension ivoirienne) |
| vyroshop.com, vyrostore.com, wearvyro.com, vyro-shop.com | ❌ déjà pris |

Achat possible chez Hostinger, OVH, Namecheap… (≈ 10 à 15 € / an pour un .com).

## 2. L'hébergement

Il faut un hébergement **PHP 8.1+ et MySQL / MariaDB** avec HTTPS (certificat SSL gratuit).
Exemples : Hostinger « Premium », o2switch, OVH « Perso », LWS. Un hébergement mutualisé suffit largement.

## 3. Installation (15 minutes)

1. **Créer la base de données** dans le panneau de l'hébergeur (noter : nom, utilisateur, mot de passe, hôte).
2. **Envoyer les fichiers** : décompresser `vyro-en-ligne.zip` et envoyer tout le contenu dans le dossier
   racine du site (`public_html`, `www`…) via le gestionnaire de fichiers ou FTP (FileZilla).
3. **Configurer** : copier `config.local.example.php` en `config.local.php` et remplir
   la base de données, `SITE_URL` (`https://votre-domaine`), `APP_SECRET` (longue chaîne aléatoire).
4. **Données** — au choix :
   - importer `database/vyro-export.sql` dans phpMyAdmin (reprend la boutique actuelle : produits, articles, promos…), **ou**
   - ouvrir `https://votre-domaine/install.php` pour une installation neuve avec les données de démonstration.
5. **Supprimer `install.php`** du serveur (et `database/vyro-export.sql`).
6. **Sécuriser l'admin** : se connecter sur `/admin/` avec `admin@vyro.ci` / `admin123`, puis
   **changer immédiatement l'email et le mot de passe** (Mon compte › Informations).
   Supprimer aussi les clients / commandes de démonstration si vous avez importé l'export.
7. Activer le **certificat SSL** (HTTPS) dans le panneau de l'hébergeur.

L'adresse du site est détectée automatiquement : rien d'autre à modifier dans le code.

## 4. Paiement Wave (API)

Tant qu'aucune clé n'est configurée, le site fonctionne déjà : le client **envoie le montant par Wave /
Orange / MTN / Moov au 05 56 77 40 58**, déclare l'ID de transaction, et vous confirmez le paiement dans
l'admin (bouton « Paiement reçu ✓ » sur la commande).

Pour le **paiement Wave automatique** (le client paie dans l'app Wave, la commande est confirmée toute seule) :

1. Ouvrir un compte **Wave Business** (entreprise) : https://www.wave.com/fr/business/
2. Portail Wave Business › **Développeurs** › créer une **clé API** avec l'accès *Checkout*.
3. Créer un **webhook** :
   - URL : `https://votre-domaine/wave-webhook.php`
   - Événement : `checkout.session.completed`
   - Copier le **secret de signature**.
4. Dans `config.local.php` : renseigner `WAVE_API_KEY` et `WAVE_WEBHOOK_SECRET`.

Le bouton « Payer avec Wave » apparaît alors automatiquement au paiement.
Le site doit être en **HTTPS** (Wave refuse les adresses de retour non sécurisées).

## 5. Après la mise en ligne

- Remplacer les visuels générés par vos **vraies photos** (Admin › Produits › Photos).
- Vérifier les **mentions légales** (RCCM, NCC, hébergeur) dans `page.php`.
- Emails : configurer l'envoi SMTP de l'hébergeur si les emails de confirmation n'arrivent pas.
