(() => {
    'use strict';
    const tabs = [...document.querySelectorAll('.autoedition-tabs [role="tab"]')];
    const panels = [...document.querySelectorAll('.autoedition-panel[role="tabpanel"]')];
    const form = document.getElementById('autoedition-form');
    const activeInput = document.getElementById('active-panel');
    if (!tabs.length || !panels.length || !form) return;
    const panelNames = ['paper', 'project', 'digital', 'submit'];
    const display = (name, focus = false) => {
        if (!panelNames.includes(name)) name = 'paper';
        tabs.forEach(tab => {
            const on = tab.dataset.panel === name;
            tab.setAttribute('aria-selected', String(on));
            tab.tabIndex = on ? 0 : -1;
            if (on && focus) tab.focus();
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.panelName !== name; });
        activeInput.value = name;
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('partie', name);
            window.history.replaceState(null, '', url);
        }
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => display(tab.dataset.panel));
        tab.addEventListener('keydown', event => {
            let next = index;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = tabs.length - 1;
            else return;
            event.preventDefault();
            display(tabs[next].dataset.panel, true);
        });
    });
    // Le navigateur peut émettre plusieurs événements invalid successifs.
    // N'ouvrir que le panneau du PREMIER champ invalide : sinon le dernier
    // panneau prend le dessus et le navigateur ne peut plus focaliser le premier.
    let switchingForInvalid = false;
    form.addEventListener('invalid', event => {
        if (switchingForInvalid) return;
        switchingForInvalid = true;
        const parent = event.target.closest('.autoedition-panel');
        if (parent) display(parent.dataset.panelName);
        window.requestAnimationFrame(() => { switchingForInvalid = false; });
    }, true);
    form.addEventListener('submit', event => {
        if (event.submitter && event.submitter.hasAttribute('formnovalidate')) return;
        if (!form.checkValidity()) {
            const invalid = form.querySelector(':invalid');
            const parent = invalid?.closest('.autoedition-panel');
            if (parent) display(parent.dataset.panelName);
            event.preventDefault();
            invalid?.focus();
            invalid?.reportValidity?.();
        }
    });
    const requested = new URLSearchParams(window.location.search).get('partie');
    display(panelNames.includes(requested) ? requested :
        (panelNames.includes(activeInput.value) ? activeInput.value : 'paper'));
})();
