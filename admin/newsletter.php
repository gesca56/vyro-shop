<?php
require __DIR__ . '/_layout.php';
require_admin();

// Export CSV (compatible Excel)
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vyro-newsletter-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Email', 'Inscrit le'], ';');
    foreach (q('SELECT email, created_at FROM newsletter ORDER BY created_at DESC')->fetchAll() as $r) {
        fputcsv($out, [$r['email'], $r['created_at']], ';');
    }
    exit;
}

if (is_post()) {
    csrf_check();
    q('DELETE FROM newsletter WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Abonné retiré.');
    redirect('admin/newsletter.php');
}

$subs = q('SELECT * FROM newsletter ORDER BY created_at DESC')->fetchAll();
admin_header('Newsletter (' . count($subs) . ')', 'newsletter');
?>
<div class="toolbar">
    <p class="muted" style="margin:0">Inscrits via « Ne manque pas le prochain drop » en bas de chaque page.</p>
    <?php if ($subs): ?><a href="?export=1" class="btn btn-accent">Exporter en CSV</a><?php endif; ?>
</div>
<div class="panel table-wrap">
    <table class="table responsive">
        <thead><tr><th>Email</th><th>Inscrit le</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($subs as $s): ?>
            <tr>
                <td data-label="Email"><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></td>
                <td data-label="Inscrit le"><?= time_fr($s['created_at'], true) ?></td>
                <td class="actions">
                    <form method="post" data-confirm="Retirer <?= e($s['email']) ?> de la newsletter ?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="link-btn danger">Retirer</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$subs): ?><tr><td colspan="3" class="center muted">Aucun abonné pour le moment.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
