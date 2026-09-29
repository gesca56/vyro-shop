<?php
require __DIR__ . '/vyro-config.php';

$fee = price(SHIPPING_FEE);
$feeOther = price(SHIPPING_FEE_OTHER);
$free = price(FREE_SHIPPING_THRESHOLD);
$mail = CONTACT_EMAIL;
$tel = CONTACT_PHONE;

// Contenus à personnaliser (raison sociale, RCCM, adresse…) avant la mise en ligne.
$pages = [
    'a-propos' => ['À propos', <<<HTML
<p class="lead">VYRO est née à Abidjan d'une conviction simple : le streetwear peut être premium, accessible et fièrement ivoirien.</p>
<h2>Define your style</h2>
<p>Chaque pièce VYRO est pensée pour ceux qui imposent leur style : coupes affûtées, matières lourdes, finitions soignées. Nous sélectionnons nos cotons et molletons pour leur tenue dans le temps et leur confort sous le climat d'Afrique de l'Ouest.</p>
<h2>Nos engagements</h2>
<ul>
<li><b>Qualité premium</b> — des grammages élevés et des coutures renforcées.</li>
<li><b>Livraison rapide</b> — 24–48h à Abidjan, partout en Côte d'Ivoire.</li>
<li><b>Paiement local</b> — Wave, Orange Money, MTN, Moov ou à la livraison.</li>
<li><b>Communauté</b> — le VYRO movement, c'est vous. Partagez vos looks avec #VYROSTYLE.</li>
</ul>
HTML],
    'cgv' => ['Conditions générales de vente', <<<HTML
<p><em>Dernière mise à jour : {DATE}</em></p>
<h2>1. Objet</h2>
<p>Les présentes conditions régissent les ventes réalisées sur le site VYRO entre VYRO et toute personne effectuant un achat (le « Client »).</p>
<h2>2. Produits et prix</h2>
<p>Les produits sont décrits avec la plus grande exactitude possible. Les prix sont indiqués en francs CFA (FCFA), toutes taxes comprises, hors frais de livraison. VYRO se réserve le droit de modifier ses prix à tout moment ; le prix appliqué est celui en vigueur au moment de la commande.</p>
<h2>3. Commande</h2>
<p>La commande est validée après confirmation du paiement ou, pour le paiement à la livraison, après validation du formulaire de commande. Un numéro de commande est attribué et communiqué au Client.</p>
<h2>4. Paiement</h2>
<p>Moyens acceptés : Wave, Orange Money, MTN MoMo, Moov Money et paiement à la livraison. Le paiement Wave en ligne est traité par Wave ; VYRO ne conserve aucune donnée bancaire.</p>
<h2>5. Livraison</h2>
<p>Voir la <a href="page.php?p=livraison">politique de livraison</a>.</p>
<h2>6. Retours</h2>
<p>Voir la <a href="page.php?p=retours">politique de retour</a>.</p>
<h2>7. Codes promo</h2>
<p>Les codes promotionnels ne sont pas cumulables, sauf mention contraire, et sont valables dans la limite de leurs conditions (durée, montant minimum, nombre d'utilisations).</p>
<h2>8. Litiges</h2>
<p>Les présentes CGV sont soumises au droit ivoirien. En cas de litige, une solution amiable sera recherchée avant toute action auprès des juridictions compétentes d'Abidjan.</p>
HTML],
    'confidentialite' => ['Politique de confidentialité', <<<HTML
<p><em>Dernière mise à jour : {DATE}</em></p>
<p>VYRO respecte la vie privée de ses clients conformément à la loi ivoirienne n° 2013-450 relative à la protection des données à caractère personnel.</p>
<h2>Données collectées</h2>
<p>Nom, prénom, téléphone, email, adresse de livraison, historique de commandes et favoris. Les données de paiement sont traitées exclusivement par nos prestataires de paiement.</p>
<h2>Utilisation</h2>
<ul><li>Traitement et livraison des commandes</li><li>Service client et suivi</li><li>Communication sur nos offres (avec votre accord)</li></ul>
<h2>Conservation et partage</h2>
<p>Vos données ne sont jamais vendues. Elles sont partagées uniquement avec nos livreurs et prestataires de paiement, dans la limite nécessaire à l'exécution de votre commande.</p>
<h2>Vos droits</h2>
<p>Vous pouvez accéder, rectifier ou supprimer vos données à tout moment en écrivant à <a href="mailto:$mail">$mail</a>.</p>
<h2>Cookies</h2>
<p>Le site utilise uniquement des cookies techniques nécessaires au fonctionnement du panier et de la connexion.</p>
HTML],
    'retours' => ['Politique de retour', <<<HTML
<h2>7 jours pour changer d'avis</h2>
<p>Vous disposez de <b>7 jours</b> à compter de la réception pour demander un échange ou un retour.</p>
<h2>Conditions</h2>
<ul><li>Article non porté, non lavé, dans son état d'origine</li><li>Étiquettes et emballage d'origine</li><li>Les bijoux et articles soldés à plus de 30 % ne sont ni repris ni échangés, sauf défaut</li></ul>
<h2>Procédure</h2>
<ol><li>Contactez-nous sur WhatsApp ($tel) ou à $mail avec votre numéro de commande.</li><li>Nous organisons la récupération à Abidjan ou vous indiquons le point de dépôt.</li><li>Après vérification, échange ou remboursement sous 5 jours ouvrés, sur le moyen de paiement initial.</li></ol>
<p>Les frais de retour sont offerts pour un échange de taille. En cas de produit défectueux, tous les frais sont à notre charge.</p>
HTML],
    'livraison' => ['Politique de livraison', <<<HTML
<h2>Zones et délais</h2>
<table class="table"><thead><tr><th>Zone</th><th>Délai</th><th>Frais</th></tr></thead><tbody>
<tr><td>Abidjan (toutes communes)</td><td>24–48h</td><td>$fee</td></tr>
<tr><td>Intérieur de la Côte d'Ivoire</td><td>2–5 jours ouvrés</td><td>$feeOther</td></tr>
<tr><td>Point relais / VYRO Store</td><td>24–48h</td><td>$fee</td></tr>
<tr><td>Sous-région</td><td>Sur devis</td><td>Nous contacter</td></tr>
</tbody></table>
<p><b>Livraison offerte dès $free d'achat.</b></p>
<h2>Déroulement</h2>
<p>Votre commande est préparée sous 24h ouvrées. Le livreur vous appelle avant son passage. Vous pouvez suivre chaque étape sur la page <a href="track.php">Suivre ma commande</a>.</p>
<h2>Absence</h2>
<p>En cas d'absence, une seconde tentative est programmée. Après deux échecs, la commande est mise à disposition au VYRO Store.</p>
HTML],
    'mentions-legales' => ['Mentions légales', <<<HTML
<h2>Éditeur du site</h2>
<p><b>VYRO</b> — [Forme juridique], capital de [montant] FCFA<br>RCCM : [numéro] — NCC : [numéro]<br>Siège : Cocody Riviera 2, Abidjan, Côte d'Ivoire<br>Téléphone : $tel — Email : $mail</p>
<p>Directeur de la publication : [Nom du responsable]</p>
<h2>Hébergement</h2>
<p>[Nom de l'hébergeur] — [adresse de l'hébergeur]</p>
<h2>Propriété intellectuelle</h2>
<p>La marque VYRO, le logo, les visuels et contenus du site sont la propriété exclusive de VYRO. Toute reproduction sans autorisation est interdite.</p>
HTML],
];

$key = $_GET['p'] ?? '';
if (!isset($pages[$key])) {
    http_response_code(404);
    $key = 'a-propos';
}
[$title, $content] = $pages[$key];
$content = str_replace('{DATE}', time_fr(date('Y-m-d')), $content);

$pageTitle = $title;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container"><h1><?= e($title) ?></h1></div>
</section>
<div class="container page-layout">
    <aside class="page-nav">
        <?php foreach ($pages as $k => [$t]): ?>
            <a href="<?= url('page.php?p=' . $k) ?>" class="<?= $k === $key ? 'active' : '' ?>"><?= e($t) ?></a>
        <?php endforeach; ?>
        <a href="<?= url('faq.php') ?>">FAQ</a>
        <a href="<?= url('contact.php') ?>">Contact</a>
    </aside>
    <article class="prose"><?= $content ?></article>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
