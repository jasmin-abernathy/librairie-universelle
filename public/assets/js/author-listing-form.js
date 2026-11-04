(() => {
  'use strict';
  const selector = document.getElementById('listing-seller-type');
  const fieldset = document.getElementById('professional-siret-field');
  const input = document.getElementById('listing-siret');
  const searchButton = document.getElementById('siret-search-button');
  const hidden = document.getElementById('selected-company-siret');
  const result = document.getElementById('siret-search-results');
  const form = document.querySelector('form.author-book-form');
  if (!selector || !fieldset || !input || !searchButton || !hidden || !result || !form) return;
  const token = form.querySelector('input[name=csrf_token]');
  const format = raw => raw.replace(/[\s.-]/g, '');
  const clearSelection = () => {
    hidden.value = '';
    result.replaceChildren();
  };
  const syncType = () => {
    const professional = selector.value === 'professional';
    fieldset.hidden = !professional;
    input.disabled = !professional;
    input.required = professional;
    searchButton.disabled = !professional;
    if (!professional) { input.value = ''; clearSelection(); }
  };
  selector.addEventListener('change', () => { clearSelection(); syncType(); });
  input.addEventListener('input', clearSelection);
  searchButton.addEventListener('click', async () => {
    clearSelection();
    const siret = format(input.value);
    if (!/^\d{14}$/.test(siret)) {
      result.textContent = 'Saisissez un numéro SIRET de 14 chiffres avant de rechercher.';
      return;
    }
    searchButton.disabled = true;
    result.textContent = 'Recherche dans le registre officiel en cours…';
    try {
      const data = new URLSearchParams({ siret, csrf_token: token?.value || '' });
      const response = await fetch('/api/siret-recherche.php', {
        method: 'POST', headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        credentials: 'same-origin', body: data.toString(),
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !Array.isArray(payload.results)) {
        throw new Error(payload.error || 'Recherche impossible pour le moment.');
      }
      result.replaceChildren();
      const intro = document.createElement('p');
      intro.textContent = 'Entreprise retrouvée. Sélectionnez la fiche qui correspond à votre activité :';
      result.append(intro);
      for (const company of payload.results) {
        if (company.siret !== siret || !company.name) continue;
        const card = document.createElement('div');
        card.className = 'siret-result-card';
        const heading = document.createElement('strong');
        heading.textContent = company.name;
        const meta = document.createElement('p');
        meta.textContent = 'SIRET : ' + company.siret + (company.city ? ' · ' + company.city : '');
        const choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'button-link';
        choose.textContent = 'Sélectionner cette entreprise';
        choose.addEventListener('click', () => {
          hidden.value = company.siret;
          result.querySelectorAll('.siret-result-card').forEach(item => item.classList.remove('selected'));
          card.classList.add('selected');
          choose.textContent = 'Entreprise sélectionnée ✓';
          result.setAttribute('data-selected', 'true');
        });
        card.append(heading, meta, choose);
        result.append(card);
      }
      if (result.querySelectorAll('.siret-result-card').length === 0) {
        result.textContent = 'Aucun résultat correspondant à ce SIRET.';
      }
    } catch (error) {
      result.textContent = error instanceof Error ? error.message : 'Service temporairement indisponible.';
    } finally {
      searchButton.disabled = selector.value !== 'professional';
    }
  });
  form.addEventListener('submit', event => {
    if (selector.value === 'professional' && hidden.value !== format(input.value)) {
      event.preventDefault();
      result.textContent = 'Pour continuer, recherchez puis sélectionnez votre entreprise dans le registre officiel.';
      input.focus();
    }
  });
  syncType();
})();
