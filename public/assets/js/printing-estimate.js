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
  const feedback = document.getElementById('print-pages-feedback');
  const corepQuote = document.getElementById('printer-corep-quote');
  const bookelisQuote = document.getElementById('printer-bookelis-quote');
  const trimInput = document.getElementById('print-trim-size');
  const bindingInput = document.querySelector('select[name="print_binding"]');
  const bleedInput = document.querySelector('select[name="print_bleed_mm"]');
  const priceInput = document.querySelector('input[name="paper_price"]');
  const providerInputs = [...document.querySelectorAll('input[name="print_provider"]')];
  const money = value => new Intl.NumberFormat('fr-FR', {style:'currency',currency:'EUR'}).format(value);
  const storageKey = 'librairie:paper-preview-count:v1';
  let grids = [], appliedStamp = null;
  try { grids = JSON.parse(panel.dataset.grids || '[]'); } catch (_) { grids = []; }
  const provider = () => providerInputs.find(r => r.checked)?.value || 'corep_metz';
  const reset = message => {
    status.textContent = message;
    total.textContent = '—';
    unit.textContent = '—';
    balance.textContent = '—';
    description.textContent = 'Prix à faire confirmer directement par l’imprimeur. Aucun devis n’est créé ici.';
    corepQuote.textContent = message;
  };
  const readPreview = () => {
    try {
      const data = JSON.parse(localStorage.getItem(storageKey) || 'null');
      if (!data || !Number.isInteger(data.pages) || data.pages < 1 || data.pages > 5000 ||
          !Number.isFinite(data.at) || Date.now() - data.at > 7*86400000 || data.at > Date.now()) return null;
      return data;
    } catch (_) { return null; }
  };
  const restorePages = () => {
    // L'assistant travaille sur le manuscrit courant : ne jamais réutiliser le
    // nombre de pages d'un ancien ouvrage enregistré dans ce navigateur.
    if (document.getElementById('paper-wizard')) return;
    const data = readPreview();
    if (!data || data.at === appliedStamp) return;
    if (data.provider === provider() && data.trim === trimInput.value) {
      pagesInput.value = String(data.pages);
      appliedStamp = data.at;
      feedback.textContent = 'Nombre réel récupéré du dernier aperçu : '+data.pages+' pages ('+ (data.provider === 'bookelis' ? 'Bookelis' : 'Corep') +'). Vérifiez ce nombre dans le PDF exporté.';
    } else {
      feedback.textContent = 'Le dernier aperçu concerne un autre imprimeur ou format. Générez un nouvel aperçu pour reprendre son nombre réel de pages.';
    }
  };
  const syncProvider = () => {
    const choice = provider();
    for (const option of trimInput.querySelectorAll('[data-bookelis-size]')) {
      option.disabled = choice !== 'bookelis';
      option.hidden = choice !== 'bookelis';
    }
    if (choice === 'corep_metz' && trimInput.value !== 'a5') trimInput.value = 'a5';
    const hard = bindingInput?.querySelector('option[value="hardcover"]');
    if (hard) {
      hard.disabled = choice === 'corep_metz';
      if (choice === 'corep_metz' && bindingInput.value !== 'paperback') bindingInput.value = 'paperback';
    }
    const bleed = bleedInput?.querySelector('option[value="3"]');
    if (bleed) {
      bleed.disabled = choice === 'bookelis';
      if (choice === 'bookelis' && bleedInput.value === '3') bleedInput.value = '0';
    }
    for (const element of document.querySelectorAll('[data-printer-only]')) {
      element.hidden = element.dataset.printerOnly !== choice;
    }
    const choiceLabel = choice === 'bookelis' ? 'Bookelis' : 'Corep Metz';
    bookelisQuote.textContent = choice === 'bookelis'
      ? 'Devis exact sur le calculateur Bookelis après pagination.'
      : 'Devis Bookelis à recalculer selon ses propres options.';
    const info = document.getElementById('paper-provider-guidance');
    if (info) info.textContent = choice === 'bookelis'
      ? 'Bookelis : choisissez le format, puis générez votre intérieur. Pour la couverture complète avec tranche, utilisez ensuite leur gabarit officiel. Les éléments à fond perdu exigent une préparation particulière.'
      : 'Corep Metz : intérieur A5, brochure à dos carré collé et couvertures recto/verso séparées. Vérifiez le BAT avec l’agence.';
    restorePages();
    render();
  };
  const render = () => {
    const pages = Number(pagesInput.value);
    const quantity = Number(quantityInput.value);
    if (!Number.isInteger(pages) || pages < 1 || pages > 5000) {
      reset('Renseignez une estimation du nombre de pages, ou générez votre aperçu pour reprendre le nombre exact.');
      return;
    }
    if (trimInput.value !== 'a5') {
      reset('Comparaison Corep indisponible : votre composition Bookelis n’est pas en A5. Recomposez en A5 pour obtenir un devis comparable.');
      return;
    }
    if (bindingInput?.value !== 'paperback') {
      reset('La grille Corep concerne uniquement le livre broché (dos carré collé).');
      return;
    }
    const grid = grids.find(g => g && g.format === 'a5' && g.binding === 'paperback'
      && Number.isInteger(g.amounts_cents?.[pages]?.[quantity]) && g.amounts_cents[pages][quantity] > 0);
    if (!grid) {
      reset('Pas de tarif Corep autorisé pour '+pages+' pages et '+quantity+' exemplaires : demandez un devis. Les paliers connus sont 120, 200 et 300 pages.');
      return;
    }
    const cost = grid.amounts_cents[pages][quantity] / 100;
    const perCopy = cost / quantity;
    total.textContent = money(cost);
    unit.textContent = money(perCopy);
    corepQuote.textContent = pages+' pages · '+quantity+' ex. : '+money(cost)+' TTC (indicatif, grille autorisée).';
    status.textContent = 'Montant Corep indicatif pour ce palier exact, sous réserve de confirmation.';
    description.textContent = 'Source : '+grid.printer+' · grille à réexaminer le '+
      grid.valid_until.split('-').reverse().join('/')+
      '. Date de réexamen interne, pas de garantie commerciale. BAT, frais annexes et livraison non compris.';
    const price = Number((priceInput?.value||'').trim().replace(',', '.'));
    balance.textContent = priceInput?.value.trim() && Number.isFinite(price) && price >= 0
      ? money(price*.70-perCopy) : 'Saisissez un prix papier';
  };
  providerInputs.forEach(el => el.addEventListener('change',syncProvider));
  trimInput?.addEventListener('change',()=>{restorePages();render();});
  bindingInput?.addEventListener('change',render);
  [pagesInput,quantityInput,priceInput].forEach(el => {if (!el)return;el.addEventListener('input',render);el.addEventListener('change',render);});
  window.addEventListener('focus',()=>{restorePages();render();});
  window.addEventListener('storage',e=>{if(e.key===storageKey){restorePages();render();}});
  syncProvider();
})();
