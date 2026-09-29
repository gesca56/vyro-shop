<?php
require __DIR__ . '/vyro-config.php';

$faq = [
    'Les produits sont-ils disponibles immédiatement ?' =>
        'Oui. Tous les articles affichés « En stock » sont disponibles dans notre entrepôt à Abidjan et expédiés sous 24h ouvrées. Le stock est mis à jour en temps réel : si un produit est épuisé, vous pouvez demander à être prévenu de son retour via WhatsApp.',
    'Quels sont les délais de livraison ?' =>
        'Abidjan : 24 à 48h (' . price(SHIPPING_FEE) . '). Intérieur de la Côte d\'Ivoire : 2 à 5 jours ouvrés (' . price(SHIPPING_FEE_OTHER) . '). La livraison est offerte dès ' . price(FREE_SHIPPING_THRESHOLD) . ' d\'achat. Vous pouvez aussi retirer votre commande en point relais.',
    'Quels moyens de paiement acceptez-vous ?' =>
        'Le paiement se fait uniquement par Wave : directement dans l’application Wave, ou par transfert Wave au ' . PAYMENT_PHONE . '. Ta commande est confirmée dès réception du paiement.',
    'Comment choisir ma taille ?' =>
        'Chaque fiche produit dispose d\'un guide des tailles détaillé. Nos coupes oversized taillent large : pour un rendu plus ajusté, prenez une taille en dessous. En cas de doute, écrivez-nous sur WhatsApp avec votre taille et votre poids, nous vous conseillons.',
    'Puis-je retourner un article ?' =>
        'Oui, vous disposez de 7 jours après réception pour échanger ou retourner un article non porté, non lavé et avec ses étiquettes. Contactez-nous sur WhatsApp ou via le formulaire de contact avec votre numéro de commande.',
    'Comment suivre ma commande ?' =>
        'Rendez-vous sur la page « Suivre ma commande » et indiquez votre numéro de commande (reçu à la confirmation) et votre téléphone ou email. Si vous avez un compte, toutes vos commandes sont visibles dans votre espace personnel.',
    'Comment contacter VYRO ?' =>
        'Par WhatsApp au ' . CONTACT_PHONE . ' (7j/7, 9h–21h), par email à ' . CONTACT_EMAIL . ', via le formulaire de contact, ou en message privé sur Instagram (' . INSTAGRAM_HANDLE . ') et TikTok (' . TIKTOK_HANDLE . ').',
    'Livrez-vous hors de Côte d\'Ivoire ?' =>
        'Nous livrons dans la sous-région (Sénégal, Burkina Faso, Mali, Togo, Bénin, Ghana) sur demande. Contactez-nous pour un devis de livraison.',
];

$pageTitle = 'FAQ';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Questions fréquentes</h1>
        <p>Tout ce qu'il faut savoir avant de commander.</p>
    </div>
</section>
<div class="container narrow">
    <div class="accordion faq">
        <?php $i = 0; foreach ($faq as $qn => $a): ?>
            <details <?= $i++ === 0 ? 'open' : '' ?>>
                <summary><?= e($qn) ?></summary>
                <div><p><?= e($a) ?></p></div>
            </details>
        <?php endforeach; ?>
    </div>
    <div class="panel center">
        <h3>Vous n'avez pas trouvé votre réponse ?</h3>
        <p class="muted">Notre équipe répond en quelques minutes sur WhatsApp.</p>
        <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="btn btn-dark">Écrire sur WhatsApp</a>
        <a href="<?= url('contact.php') ?>" class="btn btn-outline">Formulaire de contact</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
