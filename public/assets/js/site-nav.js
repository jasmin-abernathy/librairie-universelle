(() => {
    'use strict';

    const header = document.querySelector('[data-site-navigation]');
    if (!header) return;

    const toggle = header.querySelector('.site-nav-toggle');
    const nav = header.querySelector('#site-primary-navigation');
    if (!toggle || !nav) return;

    const groups = [...nav.querySelectorAll('.site-nav-group')];
    const closeMenu = () => {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Ouvrir le menu');
        groups.forEach(group => { group.open = false; });
    };

    toggle.addEventListener('click', () => {
        const opening = !nav.classList.contains('is-open');
        if (opening) {
            nav.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Fermer le menu');
        } else {
            closeMenu();
        }
    });

    nav.addEventListener('click', event => {
        if (event.target.closest('a')) closeMenu();
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const wasOpen = nav.classList.contains('is-open');
        const hadOpenGroup = groups.some(group => group.open);
        if (wasOpen || hadOpenGroup) {
            closeMenu();
            toggle.focus();
        }
    });

    document.addEventListener('pointerdown', event => {
        if (header.contains(event.target)) return;
        closeMenu();
    });

    // Ne pas conserver un menu mobile ouvert au passage vers le mode bureau.
    const breakpoint = window.matchMedia('(max-width: 1040px)');
    if (typeof breakpoint.addEventListener === 'function') {
        breakpoint.addEventListener('change', closeMenu);
    } else if (typeof breakpoint.addListener === 'function') {
        breakpoint.addListener(closeMenu);
    }

    // Sans JavaScript, le menu reste accessible en liste déroulée sur mobile.
    document.documentElement.classList.add('site-nav-ready');
})();
