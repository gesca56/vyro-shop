/* VYRO — interactions */
(function () {
    'use strict';
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
    const wait = ms => new Promise(r => setTimeout(r, ms));
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fmt = n => new Intl.NumberFormat('fr-FR').format(n).replace(/ | /g, ' ') + ' FCFA';
    const overlay = $('#overlay');
    const curtain = $('#pageCurtain');

    /* ---------- Toast ---------- */
    function toast(msg, type = 'success') {
        const zone = $('#toastZone');
        if (!zone) return;
        const t = document.createElement('div');
        t.className = 'toast toast-' + type;
        t.setAttribute('role', 'status');
        t.textContent = msg;
        zone.appendChild(t);
        setTimeout(() => t.remove(), 4600);
    }
    window.vyroToast = toast;

    /* ---------- Panneaux (menu, filtres) ---------- */
    let openPanel = null;
    function openP(panel) {
        if (!panel) return;
        closeP();
        panel.classList.add('open');
        overlay?.classList.add('show');
        document.body.classList.add('no-scroll');
        document.body.classList.toggle('menu-open', panel.id === 'mainNav');
        openPanel = panel;
    }
    function closeP() {
        openPanel?.classList.remove('open');
        overlay?.classList.remove('show');
        document.body.classList.remove('no-scroll', 'menu-open');
        openPanel = null;
    }

    document.addEventListener('click', e => {
        if (e.target.closest('#menuToggle')) return openP($('#mainNav'));
        if (e.target.closest('#menuClose') || e.target === overlay || e.target.closest('[data-close-filters]')) return closeP();
        if (e.target.closest('[data-open-filters]')) return openP($('#filters'));

        // Sous-menus en accordéon (mobile)
        const tg = e.target.closest('.sub-toggle');
        if (tg) {
            const item = tg.closest('.nav-item');
            const isOpen = item.classList.toggle('open');
            tg.setAttribute('aria-expanded', isOpen);
            return;
        }
        // Lien du menu mobile : on referme le tiroir
        if (e.target.closest('#mainNav a') && openPanel?.id === 'mainNav') closeP();
    });

    /* ---------- Recherche ---------- */
    const sp = $('#searchPanel');
    $('#searchToggle')?.addEventListener('click', () => {
        sp.classList.toggle('open');
        if (sp.classList.contains('open')) setTimeout(() => $('input', sp).focus(), 50);
    });
    $('#searchClose')?.addEventListener('click', () => sp.classList.remove('open'));

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        closeP();
        sp?.classList.remove('open');
        $$('.modal.open').forEach(m => m.classList.remove('open'));
    });

    /* ---------- En-tête : masqué au défilement vers le bas (mobile) ---------- */
    const header = $('#siteHeader');
    let lastY = window.scrollY;
    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        if (header && window.innerWidth < 1024 && !sp?.classList.contains('open') && !openPanel) {
            header.classList.toggle('hide', y > lastY && y > 160);
        } else {
            header?.classList.remove('hide');
        }
        header?.classList.toggle('scrolled', y > 10);
        lastY = y;
    }, { passive: true });

    /* ---------- Animations d'apparition ---------- */
    const io = 'IntersectionObserver' in window && !reduced
        ? new IntersectionObserver(entries => entries.forEach(en => {
            if (en.isIntersecting) {
                en.target.classList.add('in');
                io.unobserve(en.target);
            }
        }), { rootMargin: '0px 0px -8% 0px', threshold: 0.05 })
        : null;

    function initDynamic(root = document) {
        // Décalage progressif des cartes
        $$('.product-grid, .post-grid, .cat-grid, .collection-grid, .review-grid, .social-grid', root).forEach(g => {
            Array.from(g.children).forEach((c, i) => c.style.setProperty('--i', Math.min(i, 12)));
        });
        $$('.reveal', root).forEach(el => io ? io.observe(el) : el.classList.add('in'));
        initGalleryDots(root);
    }

    /* ---------- Navigation animée entre catégories (sans rechargement) ---------- */
    const progress = document.createElement('div');
    progress.className = 'nav-progress';
    document.body.appendChild(progress);

    function canSwap(u) {
        return !!$('#swapRoot') && u.origin === location.origin && u.pathname === location.pathname;
    }

    let swapCtrl = null;
    async function swapTo(href, push = true) {
        const root = $('#swapRoot');
        closeP();
        sp?.classList.remove('open');
        swapCtrl?.abort();
        const ctrl = swapCtrl = new AbortController();
        progress.className = 'nav-progress run';
        root.classList.add('is-loading');

        let html;
        try {
            const res = await fetch(href, { signal: ctrl.signal, headers: { 'X-Requested-With': 'vyro-swap' } });
            html = await res.text();
        } catch (err) {
            if (err.name !== 'AbortError') location.href = href;
            return;
        }
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const next = doc.querySelector('#swapRoot');
        if (!next) { location.href = href; return; }

        if (push) history.pushState({ swap: true }, '', href);

        const update = () => {
            root.innerHTML = next.innerHTML;
            document.title = doc.title;
            const nav = doc.querySelector('#mainNav');
            if (nav) $('#mainNav').innerHTML = nav.innerHTML;
            const desk = doc.querySelector('#deskNav');
            if (desk && $('#deskNav')) $('#deskNav').innerHTML = desk.innerHTML;
            root.classList.remove('is-loading');
            initDynamic(root);
            $$('.reveal', root).forEach(el => el.classList.add('in'));
        };

        const top = root.getBoundingClientRect().top + window.scrollY - 70;
        if (window.scrollY > top + 40) window.scrollTo({ top: Math.max(0, top), behavior: reduced ? 'auto' : 'smooth' });

        if (document.startViewTransition && !reduced && !document.hidden) {
            document.documentElement.classList.add('swap-vt');
            // Filet de sécurité : si la transition ne démarre pas, on met quand même à jour
            let done = false;
            const run = () => { if (!done) { done = true; update(); } };
            const t = document.startViewTransition(run);
            const guard = setTimeout(() => { if (!done) { t.skipTransition(); run(); } }, 500);
            await Promise.race([t.finished.catch(() => { }), wait(1500)]);
            clearTimeout(guard);
            run();
            document.documentElement.classList.remove('swap-vt');
        } else {
            root.classList.add('is-leaving');
            await wait(reduced ? 0 : 200);
            update();
            root.classList.remove('is-leaving');
        }
        progress.className = 'nav-progress done';
        setTimeout(() => { if (swapCtrl === ctrl) progress.className = 'nav-progress'; }, 400);
    }

    if ($('#swapRoot')) {
        history.replaceState({ swap: true }, '');
        window.addEventListener('popstate', e => { if (e.state?.swap) swapTo(location.href, false); });
    }

    document.addEventListener('click', e => {
        const a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.target === '_blank' || a.hasAttribute('download')) return;
        const u = new URL(a.href, location.href);
        if (canSwap(u)) {
            if (u.search === location.search && u.hash) return;
            e.preventDefault();
            swapTo(u.href);
            return;
        }
        // Navigation vers une autre page du site : rideau de transition, puis chargement
        if (u.origin !== location.origin || a.hasAttribute('data-no-curtain')) return;
        if (u.pathname === location.pathname && u.search === location.search && u.hash) return; // ancre sur la même page
        if (reduced || !curtain) return;
        e.preventDefault();
        closeP();
        curtain.classList.remove('is-out');
        curtain.classList.add('is-in');
        setTimeout(() => { location.href = u.href; }, 420);
    });
    // Retour arrière (cache du navigateur) : on retire le rideau
    window.addEventListener('pageshow', ev => {
        if (ev.persisted && curtain) {
            curtain.classList.remove('is-in');
            curtain.classList.add('is-out');
        }
    });

    document.addEventListener('submit', e => {
        const f = e.target;
        if ((f.getAttribute('method') || 'get').toLowerCase() !== 'get' || e.defaultPrevented) return;
        const u = new URL(f.getAttribute('action') || location.href, location.href);
        if (!canSwap(u)) return;
        e.preventDefault();
        const params = new URLSearchParams();
        for (const [k, v] of new FormData(f)) if (v !== '') params.append(k, v);
        u.search = params.toString();
        swapTo(u.href);
    });

    /* ---------- Ajax helpers ---------- */
    async function post(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': VYRO.csrf },
            body: data
        });
        let json = {};
        try { json = await res.json(); } catch (e) { }
        return { status: res.status, ...json };
    }
    function setCartCount(n) {
        const c = $('#cartCount');
        if (!c) return;
        c.textContent = n;
        c.hidden = n <= 0;
        c.classList.remove('bump'); void c.offsetWidth; c.classList.add('bump');
    }

    /* ---------- Ajout au panier ---------- */
    document.addEventListener('submit', async e => {
        const form = e.target;
        const isQuick = form.classList.contains('quick-add');
        const isBuy = form.id === 'buyForm';
        if (!isQuick && !isBuy) return;
        const submitter = e.submitter;
        if (submitter && submitter.value === 'buy') return; // « Acheter maintenant » : envoi classique
        e.preventDefault();
        const data = new FormData(form);
        data.set('action', 'add');
        if (submitter && submitter.name === 'size') data.set('size', submitter.value);
        const btn = submitter || $('button', form);
        if (btn) btn.disabled = true;
        try {
            const r = await post(form.action, data);
            if (r.ok) {
                setCartCount(r.count);
                toast(r.message + ' 🛒');
                btn?.classList.add('added');
                form.closest('.card')?.classList.remove('show-sizes');
                const plus = form.closest('.card')?.querySelector('.card-plus');
                if (plus) { plus.textContent = '[✓]'; setTimeout(() => plus.textContent = '[+]', 1400); }
                setTimeout(() => btn?.classList.remove('added'), 1200);
            } else {
                toast(r.message || 'Erreur, réessayez.', 'error');
            }
        } catch (err) {
            form.submit();
        } finally {
            if (btn) btn.disabled = false;
        }
    });

    // Barre d'achat collante : vérifier la taille avant l'envoi
    document.addEventListener('click', e => {
        const b = e.target.closest('button[form="buyForm"]');
        if (!b) return;
        const form = $('#buyForm');
        if (!form.checkValidity()) {
            e.preventDefault();
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            $('.size-options', form)?.classList.add('shake');
            setTimeout(() => $('.size-options', form)?.classList.remove('shake'), 600);
            toast('Veuillez choisir une taille.', 'error');
        }
    });

    /* ---------- Favoris ---------- */
    document.addEventListener('click', async e => {
        const b = e.target.closest('[data-fav]');
        if (!b) return;
        e.preventDefault();
        const data = new FormData();
        data.set('product_id', b.dataset.fav);
        data.set('csrf', VYRO.csrf);
        const r = await post(VYRO.base + '/fav-action.php', data);
        if (r.status === 401) {
            toast(r.message, 'info');
            setTimeout(() => location.href = r.login + '?redirect=' + encodeURIComponent(location.pathname + location.search), 900);
            return;
        }
        if (r.ok) {
            $$('[data-fav="' + b.dataset.fav + '"]').forEach(x => {
                x.classList.toggle('active', r.active);
                x.classList.remove('pop'); void x.offsetWidth; x.classList.add('pop');
                const s = $('span', x);
                if (s) s.textContent = r.active ? 'Dans vos favoris' : 'Ajouter aux favoris';
            });
            toast(r.message, 'info');
        }
    });

    /* ---------- Quantité ---------- */
    let qtyTimer;
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-qty]');
        if (!b) return;
        const input = $('input', b.closest('.qty'));
        const min = parseInt(input.min || '1', 10);
        const max = parseInt(input.max || '99', 10);
        input.value = Math.max(min, Math.min(max, (parseInt(input.value, 10) || 0) + parseInt(b.dataset.qty, 10)));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    document.addEventListener('change', e => {
        if (e.target.matches('[data-autosubmit]')) {
            clearTimeout(qtyTimer);
            qtyTimer = setTimeout(() => e.target.form.submit(), 500);
        }
    });

    /* ---------- Galerie produit ---------- */
    function initGalleryDots(root = document) {
        const gm = $('#galleryMain', root);
        if (!gm || gm.dataset.ready) return;
        gm.dataset.ready = '1';
        const slides = Array.from(gm.children);
        const thumbs = $$('.gallery-thumbs button');
        const dots = document.createElement('div');
        dots.className = 'gallery-dots';
        slides.forEach(() => dots.appendChild(document.createElement('i')));
        gm.after(dots);
        const go = i => gm.scrollTo({ left: gm.clientWidth * i, behavior: reduced ? 'auto' : 'smooth' });
        const sync = () => {
            const i = Math.round(gm.scrollLeft / gm.clientWidth);
            thumbs.forEach((t, j) => t.classList.toggle('active', j === i));
            Array.from(dots.children).forEach((d, j) => d.classList.toggle('on', j === i));
        };
        sync();
        thumbs.forEach(t => t.addEventListener('click', () => go(+t.dataset.index)));
        gm.addEventListener('scroll', sync, { passive: true });
        $$('input[name="color"]', $('#buyForm')).forEach(r => r.addEventListener('change', () => {
            $('#colorName').textContent = r.value;
            const idx = slides.findIndex(s => s.dataset && s.dataset.color === r.value);
            if (idx >= 0) go(idx);
        }));
        $$('input[name="size"]', $('#buyForm')).forEach(r => r.addEventListener('change', () => {
            $('#sizeName').textContent = r.value;
        }));
    }

    // Barre d'achat collante : masquée quand les boutons principaux sont visibles
    const sticky = $('#stickyBuy');
    const actions = $('#buyActions');
    if (sticky && actions && 'IntersectionObserver' in window) {
        new IntersectionObserver(([en]) => sticky.classList.toggle('hide', en.isIntersecting || en.boundingClientRect.top > 0))
            .observe(actions);
    }

    /* ---------- Modales ---------- */
    document.addEventListener('click', e => {
        const opener = e.target.closest('[data-modal]');
        if (opener) return $('#' + opener.dataset.modal)?.classList.add('open');
        const m = e.target.closest('.modal');
        if (m && (e.target === m || e.target.closest('[data-close-modal]'))) m.classList.remove('open');
    });

    /* ---------- Copier ---------- */
    document.addEventListener('click', async e => {
        const b = e.target.closest('[data-copy]');
        if (!b) return;
        try {
            await navigator.clipboard.writeText(b.dataset.copy);
            toast('« ' + b.dataset.copy + ' » copié !');
        } catch (err) {
            toast(b.dataset.copy, 'info');
        }
    });

    /* ---------- Compte à rebours promo ---------- */
    $$('.countdown[data-end]').forEach(el => {
        const end = new Date(el.dataset.end.replace(' ', 'T')).getTime();
        const tick = () => {
            let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
            const d = Math.floor(s / 86400); s %= 86400;
            const h = Math.floor(s / 3600); s %= 3600;
            const m = Math.floor(s / 60); s %= 60;
            el.textContent = '⏱ Se termine dans ' + d + 'j ' + String(h).padStart(2, '0') + 'h ' + String(m).padStart(2, '0') + 'm ' + String(s).padStart(2, '0') + 's';
        };
        tick();
        setInterval(tick, 1000);
    });

    /* ---------- Checkout ---------- */
    const co = $('#checkoutForm');
    if (co) {
        const syncDelivery = () => co.classList.toggle('pickup', $('input[name="delivery"]:checked', co)?.value === 'pickup');
        $$('input[name="delivery"]', co).forEach(r => r.addEventListener('change', syncDelivery));
        syncDelivery();

        const ca = $('#createAccount');
        const syncAccount = () => $('.account-only', co)?.classList.toggle('show', ca.checked);
        ca?.addEventListener('change', syncAccount);
        if (ca) syncAccount();

        const t = $('#checkoutTotals');
        const city = $('#citySelect');
        const recalc = () => {
            const sub = +t.dataset.subtotal;
            let ship = 0;
            if (t.dataset.freeship !== '1' && sub < +t.dataset.free) {
                ship = /abidjan/i.test(city.value) ? +t.dataset.fee : +t.dataset.feeOther;
            }
            $('#shipAmount').innerHTML = ship ? fmt(ship) : '<b class="free">Offerte</b>';
            $('#totalAmount').textContent = fmt(sub + ship);
        };
        city?.addEventListener('change', recalc);
        if (city) recalc();

        co.addEventListener('submit', () => {
            const b = $('button.btn-accent', co);
            b.disabled = true;
            b.textContent = 'Traitement…';
        });
    }

    /* ---------- Paiement ---------- */
    $$('[data-card]').forEach(i => i.addEventListener('input', () => {
        i.value = i.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
    }));
    $$('[data-exp]').forEach(i => i.addEventListener('input', () => {
        const v = i.value.replace(/\D/g, '').slice(0, 4);
        i.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
    }));
    document.addEventListener('submit', e => {
        const b = $('[data-loading]', e.target);
        if (b && e.target.checkValidity()) {
            setTimeout(() => { b.disabled = true; b.textContent = b.dataset.loading; }, 0);
        }
    });

    /* ---------- Cartes produit : [+] ouvre le choix de taille ---------- */
    document.addEventListener('click', e => {
        const opener = e.target.closest('[data-quick-open]');
        const openCard = $('.card.show-sizes');
        if (opener) {
            const card = opener.closest('.card');
            if (openCard && openCard !== card) openCard.classList.remove('show-sizes');
            card.classList.toggle('show-sizes');
            return;
        }
        if (openCard && !e.target.closest('.card.show-sizes .quick-add')) openCard.classList.remove('show-sizes');
    });

    /* ---------- Newsletter ---------- */
    document.addEventListener('submit', async e => {
        const f = e.target;
        if (!f.matches('[data-newsletter]')) return;
        e.preventDefault();
        const btn = $('button', f);
        btn.disabled = true;
        try {
            const res = await fetch(f.action, { method: 'POST', body: new FormData(f), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const r = await res.json();
            toast(r.message, r.ok ? 'success' : 'error');
            if (r.ok) { f.reset(); f.classList.add('done'); }
        } catch (err) {
            f.submit();
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------- Bandeau d'annonce : messages en fondu ---------- */
    const rot = $('.promo-rotator');
    if (rot && rot.children.length > 1 && !reduced) {
        let idx = 0;
        setInterval(() => {
            rot.children[idx].classList.remove('on');
            idx = (idx + 1) % rot.children.length;
            rot.children[idx].classList.add('on');
        }, 3800);
    }

    initDynamic();
})();
