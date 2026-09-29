<?php
require __DIR__ . '/vyro-config.php';

$u = current_user();
$f = ['name' => $u ? $u['first_name'] . ' ' . $u['last_name'] : '', 'email' => $u['email'] ?? '', 'phone' => $u['phone'] ?? '', 'subject' => '', 'message' => ''];
$errors = [];
$sent = false;

if (is_post()) {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    if (!empty($_POST['website'])) exit; // anti-spam (champ piège)
    if ($f['name'] === '') $errors[] = 'Votre nom est requis.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if ($f['subject'] === '') $errors[] = 'Choisissez un sujet.';
    if (mb_strlen($f['message']) < 10) $errors[] = 'Votre message est trop court.';
    if (!$errors) {
        q('INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)', array_values($f));
        $sent = true;
    }
}

$pageTitle = 'Contact';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <h1>Contact</h1>
        <p>Une question sur un produit, une commande, une collab ? On vous répond vite.</p>
    </div>
</section>
<div class="container">
    <div class="contact-layout">
        <div class="contact-cards">
            <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="panel contact-card">
                <b>💬 WhatsApp</b><span><?= CONTACT_PHONE ?></span><small>7j/7 · 9h–21h · réponse rapide</small>
            </a>
            <a href="mailto:<?= CONTACT_EMAIL ?>" class="panel contact-card">
                <b>✉️ Email</b><span><?= CONTACT_EMAIL ?></span><small>Réponse sous 24h</small>
            </a>
            <div class="panel contact-card">
                <b>📍 VYRO Store</b><span>Cocody Riviera 2, Abidjan</span><small>Lun–Sam · 10h–20h</small>
            </div>
            <a href="<?= INSTAGRAM_URL ?>" target="_blank" rel="noopener" class="panel contact-card">
                <b>📸 Réseaux</b><span><?= e(INSTAGRAM_HANDLE) ?> · <?= e(TIKTOK_HANDLE) ?></span><small>Instagram · TikTok</small>
            </a>
        </div>
        <div class="panel">
            <?php if ($sent): ?>
                <div class="confirm-panel">
                    <div class="confirm-icon">✓</div>
                    <h2>Message envoyé !</h2>
                    <p>Merci <?= e($f['name']) ?>, nous revenons vers vous très vite.</p>
                    <a href="<?= url('shop.php') ?>" class="btn btn-dark">Retour à la boutique</a>
                </div>
            <?php else: ?>
                <h2 class="h3">Envoyez-nous un message</h2>
                <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
                <form method="post" class="form">
                    <?= csrf_field() ?>
                    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="grid-2">
                        <label class="field">Nom *<input name="name" value="<?= e($f['name']) ?>" required></label>
                        <label class="field">Email *<input type="email" name="email" value="<?= e($f['email']) ?>" required></label>
                        <label class="field">Téléphone<input type="tel" name="phone" value="<?= e($f['phone']) ?>"></label>
                        <label class="field">Sujet *
                            <select name="subject" required>
                                <option value="">Choisir</option>
                                <?php foreach (['Question produit', 'Ma commande', 'Retour / échange', 'Partenariat / collab', 'Autre'] as $s): ?>
                                    <option <?= $f['subject'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label class="field">Message *<textarea name="message" rows="6" required><?= e($f['message']) ?></textarea></label>
                    <button class="btn btn-dark btn-lg">Envoyer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
