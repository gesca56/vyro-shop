/* VYRO Admin — interactions communes */
(function () {
    'use strict';
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    // Menu latéral (mobile) avec fond cliquable
    const side = $('#adminSide');
    const backdrop = $('#adminBackdrop');
    const toggleSide = open => {
        side.classList.toggle('open', open);
        backdrop.classList.toggle('show', open);
        document.body.classList.toggle('no-scroll', open);
    };
    $('#adminMenu')?.addEventListener('click', () => toggleSide(!side.classList.contains('open')));
    backdrop?.addEventListener('click', () => toggleSide(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') toggleSide(false); });

    // Confirmations
    $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-confirm-click]');
        if (b && !confirm(b.dataset.confirmClick)) e.preventDefault();
    });

    // Sélection multiple + barre d'actions groupées
    $$('form[data-bulk]').forEach(form => {
        const bar = $('.bulk-bar', form);
        // Inclut les cases reliées par l'attribut form="…" (hors du <form>)
        const boxes = () => Array.from(form.elements).filter(el => el.name === 'ids[]');
        const all = $('[data-check-all]', form) || (form.id && $('[data-check-all][form="' + form.id + '"]'));
        const sync = () => {
            const n = boxes().filter(b => b.checked).length;
            bar.hidden = n === 0;
            $('[data-bulk-count]', bar).textContent = n;
            if (all) {
                all.checked = n > 0 && n === boxes().length;
                all.indeterminate = n > 0 && n < boxes().length;
            }
            boxes().forEach(b => b.closest('tr')?.classList.toggle('selected', b.checked));
        };
        all?.addEventListener('change', () => { boxes().forEach(b => b.checked = all.checked); sync(); });
        document.addEventListener('change', e => { if (e.target.name === 'ids[]' && e.target.form === form) sync(); });
        sync();
    });

    // Stock modifiable directement dans la liste (enregistrement automatique)
    document.addEventListener('change', async e => {
        const form = e.target.closest('form[data-inline-stock]');
        if (!form) return;
        const input = e.target;
        input.classList.add('saving');
        try {
            const res = await fetch(form.action || location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error();
            const v = +input.value;
            input.classList.toggle('is-out', v <= 0);
            input.classList.toggle('is-low', v > 0 && v <= 5);
            input.classList.add('saved');
            setTimeout(() => input.classList.remove('saved'), 1200);
        } catch (err) {
            form.submit();
        } finally {
            input.classList.remove('saving');
        }
    });
    document.addEventListener('submit', e => {
        if (e.target.matches('form[data-inline-stock]')) {
            e.preventDefault();
            $('input[name="stock"]', e.target).dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // Aperçu d'image avant envoi
    $$('input[type="file"][data-preview]').forEach(input => input.addEventListener('change', () => {
        const img = $(input.dataset.preview);
        const file = input.files[0];
        if (img && file) img.src = URL.createObjectURL(file);
    }));
    $$('input[type="file"][data-preview-list]').forEach(input => input.addEventListener('change', () => {
        const box = $(input.dataset.previewList);
        box.innerHTML = '';
        Array.from(input.files).forEach(file => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            box.appendChild(img);
        });
    }));

    // Filtre instantané d'une liste
    $$('[data-filter-list]').forEach(input => input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        $$(input.dataset.filterList + ' > *').forEach(el => el.hidden = q && !el.textContent.toLowerCase().includes(q));
    }));

    // Lignes cliquables
    document.addEventListener('click', e => {
        const tr = e.target.closest('tr[data-href]');
        if (tr && !e.target.closest('a, button, input, select, label, form')) location.href = tr.dataset.href;
    });
})();
