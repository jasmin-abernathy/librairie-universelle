(() => {
    'use strict';
    const panel = document.getElementById('print-cost-estimator');
    if (!panel) return;
    const pagesInput = document.getElementById('print-estimate-pages');
    const quantityInput = document.getElementById('print-estimate-quantity');
    const status = document.getElementById('print-estimate-status');
    const total = document.getElementById('print-estimate-total');
    const unit = document.getElementById('print-estimate-unit');
    const balance = document.getElementById('print-estimate-balance');
    const description = document.getElementById('print-estimate-source');
    const priceInput = document.querySelector('input[name="paper_price"]');
    const trimInput = document.querySelector('select[name="print_trim_size"]');
    const bindingInput = document.querySelector('select[name="print_binding"]');
    let grids = [];
    try { grids = JSON.parse(panel.dataset.grids || '[]'); } catch (_) { grids = []; }
    const money = value => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    const reset = message => {
        status.textContent = message;
        total.textContent = '—'; unit.textContent = '—'; balance.textContent = '—';
        description.textContent = 'Aucun prix contractuel affichable pour cette configuration.';
    };
    const render = () => {
        if (!grids.length) {
            reset('Aucune grille d’impression autorisée pour le moment. Vous pouvez continuer votre dépôt.');
            return;
        }
        if (trimInput && trimInput.value !== 'a5') {
            reset('Pour ce devis, choisissez le format A5 dans la préparation papier.');
            return;
        }
        if (bindingInput && bindingInput.value !== 'paperback') {
            reset('Ce devis concerne uniquement un livre broché (dos carré collé).');
            return;
        }
        const pages = pagesInput.value;
        const quantity = quantityInput.value;
        const grid = grids.find(g => g && g.format === 'a5' && g.binding === 'paperback'
            && g.amounts_cents && g.amounts_cents[pages]
            && Number.isInteger(g.amounts_cents[pages][quantity])
            && g.amounts_cents[pages][quantity] > 0);
        if (!grid) {
            reset('Tarif non disponible : seules les grilles autorisées et les paliers exacts peuvent être affichés.');
            return;
        }
        const cost = grid.amounts_cents[pages][quantity] / 100;
        const perCopy = cost / Number(quantity);
        status.textContent = 'Estimation indicative, sous réserve de confirmation avant commande.';
        total.textContent = money(cost);
        unit.textContent = money(perCopy);
        description.textContent = 'Source : ' + grid.printer + ' · grille valable jusqu’au ' +
            grid.valid_until.split('-').reverse().join('/') +
            '. Prochain réexamen de la grille (date interne, pas une garantie commerciale). BAT papier et frais annexes non compris. Prix et délais à reconfirmer ; aucune commande automatique.';
        const raw = (priceInput?.value || '').trim().replace(',', '.');
        const publicPrice = raw === '' ? NaN : Number(raw);
        balance.textContent = Number.isFinite(publicPrice) && publicPrice >= 0
            ? money(publicPrice * 0.70 - perCopy) : 'Saisissez un prix papier';
    };
    for (const input of [pagesInput, quantityInput, priceInput, trimInput, bindingInput]) {
        if (!input) continue;
        input.addEventListener('change', render);
        input.addEventListener('input', render);
    }
    render();
})();
