<?php
require __DIR__ . '/_layout.php';
require_admin();

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        q('DELETE FROM contact_messages WHERE id = ?', [$id]);
    } else {
        q('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?', [$id]);
    }
    redirect('admin/messages.php');
}

$messages = q('SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC')->fetchAll();
admin_header('Messages', 'messages');
?>
<?php if (!$messages): ?><div class="panel center muted">Aucun message pour le moment.</div><?php endif; ?>
<div class="msg-list">
    <?php foreach ($messages as $m): ?>
        <article class="panel msg <?= $m['is_read'] ? '' : 'unread' ?>">
            <div class="msg-head">
                <div><b><?= e($m['name']) ?></b> · <span class="muted small"><?= time_fr($m['created_at'], true) ?></span><br>
                    <a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' · ' . e($m['phone']) : '' ?></div>
                <span class="status-pill"><?= e($m['subject']) ?></span>
            </div>
            <p><?= nl2br(e($m['message'])) ?></p>
            <div class="actions">
                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="link-btn"><?= $m['is_read'] ? 'Marquer non lu' : 'Marquer lu' ?></button></form>
                <form method="post" data-confirm="Supprimer ce message ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="link-btn danger">Supprimer</button></form>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
