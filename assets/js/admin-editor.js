/* VYRO Admin — éditeur d'articles (Markdown simplifié) */
(function () {
    'use strict';
    const area = document.getElementById('postContent');
    if (!area) return;
    const preview = document.getElementById('postPreview');
    const toolbar = document.querySelector('[data-editor-toolbar]');

    // Insère une mise en forme autour de la sélection
    function wrap(before, after = before, placeholder = 'texte') {
        const { selectionStart: s, selectionEnd: e, value } = area;
        const sel = value.slice(s, e) || placeholder;
        area.setRangeText(before + sel + after, s, e, 'end');
        area.focus();
        area.setSelectionRange(s + before.length, s + before.length + sel.length);
    }
    // Préfixe chaque ligne de la sélection (listes, titres, citations)
    function prefixLines(fn, placeholder) {
        const { selectionStart: s, selectionEnd: e, value } = area;
        const start = value.lastIndexOf('\n', s - 1) + 1;
        let end = value.indexOf('\n', e);
        if (end === -1) end = value.length;
        const block = value.slice(start, end) || placeholder;
        const out = block.split('\n').map((l, i) => fn(l, i)).join('\n');
        const needGap = start > 0 && value.slice(0, start).trim() !== '' && !value.slice(0, start).endsWith('\n\n');
        area.setRangeText((needGap ? '\n' : '') + out, start, end, 'end');
        area.focus();
    }

    toolbar.addEventListener('click', e => {
        const b = e.target.closest('[data-md]');
        if (!b) return;
        switch (b.dataset.md) {
            case 'h2': prefixLines(l => '## ' + l.replace(/^#+\s*/, ''), 'Titre de section'); break;
            case 'h3': prefixLines(l => '### ' + l.replace(/^#+\s*/, ''), 'Sous-titre'); break;
            case 'bold': wrap('**'); break;
            case 'italic': wrap('*'); break;
            case 'ul': prefixLines(l => '- ' + l.replace(/^[-*]\s+/, ''), 'Élément'); break;
            case 'ol': prefixLines((l, i) => (i + 1) + '. ' + l.replace(/^\d+[.)]\s+/, ''), 'Élément'); break;
            case 'quote': prefixLines(l => '> ' + l.replace(/^>\s*/, ''), 'Citation'); break;
            case 'link': {
                const href = prompt('Adresse du lien (ex : shop.php?cat=sneakers ou https://…)', 'shop.php');
                if (href) wrap('[', '](' + href.trim() + ')', 'texte du lien');
                break;
            }
        }
    });

    // Raccourcis clavier
    area.addEventListener('keydown', e => {
        if (!(e.ctrlKey || e.metaKey)) return;
        if (e.key === 'b') { e.preventDefault(); wrap('**'); }
        if (e.key === 'i') { e.preventDefault(); wrap('*'); }
    });

    // Onglets Écrire / Aperçu (rendu identique au site, fait côté serveur)
    document.querySelectorAll('[data-editor-tab]').forEach(tab => tab.addEventListener('click', async () => {
        document.querySelectorAll('[data-editor-tab]').forEach(t => t.classList.toggle('active', t === tab));
        const isPreview = tab.dataset.editorTab === 'preview';
        area.hidden = isPreview;
        toolbar.hidden = isPreview;
        preview.hidden = !isPreview;
        if (isPreview) {
            preview.innerHTML = '<p class="muted">Chargement…</p>';
            const data = new FormData();
            data.set('content', area.value);
            data.set('csrf', document.querySelector('input[name="csrf"]').value);
            const res = await fetch('preview.php', { method: 'POST', body: data });
            preview.innerHTML = await res.text();
        }
    }));

    // Hauteur automatique
    const grow = () => { area.style.height = 'auto'; area.style.height = Math.max(360, area.scrollHeight + 4) + 'px'; };
    area.addEventListener('input', grow);
    grow();

    // Avertit en cas de modifications non enregistrées
    const form = area.form;
    let dirty = false;
    form.addEventListener('input', () => dirty = true);
    form.addEventListener('submit', () => dirty = false);
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
