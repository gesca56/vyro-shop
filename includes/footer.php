</main>

<!-- Newsletter -->
<section class="drop-alert">
    <div class="container drop-alert-inner">
        <img src="<?= url('assets/img/crown-light.png') ?>" alt="" class="drop-alert-crown" width="256" height="200" loading="lazy">
        <h2>Ne manque pas le prochain drop</h2>
        <p>Accès prioritaire aux nouveautés, restocks et codes privés.</p>
        <form method="post" action="<?= url('newsletter.php') ?>" class="drop-alert-form" data-newsletter>
            <?= csrf_field() ?>
            <input type="email" name="email" placeholder="Ton e-mail" required autocomplete="email" aria-label="Adresse e-mail">
            <button class="btn btn-light">S'abonner</button>
        </form>
    </div>
</section>

<section class="trust-strip">
    <div class="container trust-grid">
        <div><strong>Livraison rapide</strong><span>24–48h à Abidjan · 2–5 jours en région</span></div>
        <div><strong>Paiement sécurisé</strong><span>Paiement par Wave, en quelques secondes</span></div>
        <div><strong>Retours 7 jours</strong><span>Échange ou remboursement</span></div>
        <div><strong>Service client</strong><span>WhatsApp 7j/7</span></div>
    </div>
</section>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= url() ?>" class="footer-logo" aria-label="VYRO — accueil">
                <img src="<?= url('assets/img/logo-light.png') ?>" srcset="<?= url('assets/img/logo-light@2x.png') ?> 2x" alt="VYRO" width="480" height="339" loading="lazy">
            </a>
            <p class="footer-tag">Streetwear <i>×</i> Lifestyle <i>×</i> You</p>
            <p>More than just fashion. Pensé à Abidjan pour ceux qui imposent leur style.</p>
            <div class="socials">
                <a href="<?= INSTAGRAM_URL ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
                <a href="<?= TIKTOK_URL ?>" target="_blank" rel="noopener" aria-label="TikTok"><svg viewBox="0 0 24 24"><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5M14 3c.5 2.5 2.3 4.2 5 4.5"/></svg></a>
                <?php if (SNAPCHAT_URL): ?><a href="<?= SNAPCHAT_URL ?>" target="_blank" rel="noopener" aria-label="Snapchat"><svg viewBox="0 0 24 24"><path d="M12 3c3 0 5 2.2 5 5v3l2 1-2 1c.5 1.8 1.8 3 3.5 3.5-.8.9-2 1-3 1.2L17 20c-1.2-.3-2.5 0-3.5.7-1 .6-2 .6-3 0-1-.7-2.3-1-3.5-.7l-.5-2.3c-1-.2-2.2-.3-3-1.2C5.2 16 6.5 14.8 7 13l-2-1 2-1V8c0-2.8 2-5 5-5z"/></svg></a><?php endif; ?>
                <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24"><path d="M4 20l1.3-4A8 8 0 1 1 8 18.7L4 20z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2L9 9.5z"/></svg></a>
            </div>
        </div>
        <div>
            <h4>Shop</h4>
            <a href="<?= url('shop.php?filter=new') ?>">Nouveautés</a>
            <a href="<?= url('shop.php?cat=vetements') ?>">Vêtements</a>
            <a href="<?= url('shop.php?cat=chaussures') ?>">Chaussures</a>
            <a href="<?= url('shop.php?cat=accessoires') ?>">Accessoires</a>
            <a href="<?= url('shop.php?filter=promo') ?>">Promotions</a>
        </div>
        <div>
            <h4>Aide</h4>
            <a href="<?= url('track.php') ?>">Suivre ma commande</a>
            <a href="<?= url('faq.php') ?>">FAQ</a>
            <a href="<?= url('page.php?p=livraison') ?>">Livraison</a>
            <a href="<?= url('page.php?p=retours') ?>">Retours & échanges</a>
            <a href="<?= url('contact.php') ?>">Contact / SAV</a>
        </div>
        <div>
            <h4>VYRO</h4>
            <a href="<?= url('page.php?p=a-propos') ?>">À propos</a>
            <a href="<?= url('blog.php') ?>">Journal</a>
            <a href="<?= url('collections.php') ?>">Collections</a>
            <a href="<?= url('avis.php') ?>">Avis clients</a>
            <a href="<?= url('communaute.php') ?>">Communauté</a>
            <a href="<?= url('page.php?p=cgv') ?>">CGV</a>
            <a href="<?= url('page.php?p=confidentialite') ?>">Confidentialité</a>
            <a href="<?= url('page.php?p=mentions-legales') ?>">Mentions légales</a>
        </div>
    </div>
    <div class="footer-wordmark" aria-hidden="true">GOOD DRIP · BETTER DAYS · GOOD DRIP · BETTER DAYS ·</div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> VYRO — Clothing · Sneakers · Accessories</span>
        <div class="pay-logos">
            <span style="--pc:#1DC8FF">Wave</span>
            <?php if (CARD_PAYMENT_ENABLED): ?><span style="--pc:#fff">Visa / MC</span><?php endif; ?>
        </div>
    </div>
</footer>

<a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode('Bonjour VYRO 👋') ?>" class="wa-float" target="_blank" rel="noopener" aria-label="Contacter VYRO sur WhatsApp">
    <svg viewBox="0 0 24 24"><path d="M4 20l1.3-4A8 8 0 1 1 8 18.7L4 20z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2L9 9.5z"/></svg>
</a>

<script>window.VYRO = { base: '<?= BASE_URL ?>', csrf: '<?= csrf_token() ?>' };</script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
