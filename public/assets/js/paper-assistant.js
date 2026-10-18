(() => {
  'use strict';
  const el = id => document.getElementById(id);
  const ids = ['paper-size','paper-title','paper-author','paper-subtitle','paper-bg','paper-ink','paper-description','paper-isbn','paper-price','paper-front-ready'];
  const items = Object.fromEntries(ids.map(id => [id, el(id)]));
  const front = el('paper-front-preview');
  const back = el('paper-back-preview');
  if (!front || !back) return;
  const formats = {
    '148x210':[148,210], '140x210':[140,210],
    '135x215':[135,215], '152x229':[152,229]
  };
  const fileUrls = new Map();
  const maxBytes = 15 * 1024 * 1024;
  const setText = (id, text) => { el(id).textContent = text; };
  const update = () => {
    const dim = formats[items['paper-size'].value] || formats['148x210'];
    for (const sheet of [front,back]) {
      sheet.style.setProperty('--cover-aspect',dim[0]+' / '+dim[1]);
      sheet.style.setProperty('--cover-width',dim[0]+'mm');
      sheet.style.setProperty('--cover-height',dim[1]+'mm');
      sheet.style.setProperty('--cover-bg',items['paper-bg'].value);
      sheet.style.setProperty('--cover-ink',items['paper-ink'].value);
    }
    setText('paper-front-title',items['paper-title'].value.trim() || 'Titre du livre');
    setText('paper-back-title',items['paper-title'].value.trim() || 'Titre du livre');
    setText('paper-front-author',items['paper-author'].value.trim() || 'Nom de l’auteur');
    setText('paper-front-subtitle',items['paper-subtitle'].value.trim());
    setText('paper-back-description',items['paper-description'].value.trim() || 'Votre résumé apparaîtra ici.');
    const isbn = items['paper-isbn'].value.trim();
    const provided = el('paper-barcode-image').files?.[0];
    const check = el('paper-isbn-check');
    const barcodePreview = el('paper-barcode-visual');
    const previewMode = window.PaperBarcode?.previewMode(isbn, Boolean(provided)) || 'empty';
    if (previewMode === 'uploaded') {
      // Never replace an author-provided barcode with an auto-generated image.
      // In particular, changing the ISBN or cover color must keep the uploaded file.
      barcodePreview.hidden = !barcodePreview.getAttribute('src');
      barcodePreview.alt = 'Image de code-barres fournie par l’auteur';
      check.textContent = isbn && !window.PaperBarcode?.valid(isbn)
        ? 'Image conservée, mais ISBN-13 invalide : corrigez le numéro avant l’export.'
        : 'Image de code-barres fournie et conservée : vérifiez qu’elle correspond exactement à l’ISBN du livre.';
    } else if (previewMode === 'generated') {
      const code = window.PaperBarcode.digits(isbn);
      barcodePreview.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(window.PaperBarcode.svg(code));
      barcodePreview.hidden = false;
      barcodePreview.alt = 'EAN-13 calculé pour l’ISBN ' + code;
      check.textContent = 'ISBN-13 valide : EAN-13 calculé (chiffre de contrôle vérifié). Vérifiez sa lisibilité après export.';
    } else {
      barcodePreview.removeAttribute('src');
      barcodePreview.hidden = true;
      check.textContent = previewMode === 'invalid'
        ? 'Attention : cet ISBN-13 n’est pas valide. Vérifiez les 13 chiffres et le chiffre de contrôle.'
        : 'ISBN absent : le code-barres n’est pas généré. Aucun numéro ne sera inventé.';
    }
    setText('paper-back-isbn',isbn ? 'ISBN : '+isbn : 'Emplacement réservé au code-barres');
    const price = Number(items['paper-price'].value);
    const priceLabel = el('paper-back-price');
    priceLabel.hidden = items['paper-price'].value.trim() === '' || !Number.isFinite(price) || price < 0;
    if (!priceLabel.hidden) priceLabel.textContent = 'Prix public : ' + new Intl.NumberFormat('fr-FR', {style:'currency',currency:'EUR'}).format(price);
    el('paper-front-text').hidden = items['paper-front-ready'].checked;
    const warning = el('paper-print-check');
    const summary = items['paper-description'].value.trim();
    warning.textContent = summary.length > 850 ?
      'La quatrième contient beaucoup de texte : vérifiez que tout reste lisible et non coupé avant l’export.' :
      'Contrôlez les PDF exportés et faites valider les réglages particuliers par l’imprimeur.';
  };
  function photoInput(inputId, imageId, messageId, checkResolution) {
    const input = el(inputId);
    const image = el(imageId);
    const message = messageId ? el(messageId) : null;
    input.addEventListener('change', () => {
      const prev = fileUrls.get(inputId);
      if (prev) URL.revokeObjectURL(prev);
      fileUrls.delete(inputId);
      image.removeAttribute('src');
      image.hidden = true;
      const file = input.files && input.files[0];
      if (!file) {
        if (message) message.textContent = 'Aucune image ajoutée.';
        update();
        return;
      }
      if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > maxBytes) {
        input.value = '';
        if (message) message.textContent = 'Format ou taille non accepté : JPEG, PNG ou WebP, 15 Mo maximum.';
        update();
        return;
      }
      const url = URL.createObjectURL(file);
      fileUrls.set(inputId, url);
      image.addEventListener('load', () => {
        if (!message) return;
        if (!checkResolution) { message.textContent = 'Image prête : vérifiez son rendu avant impression.'; return; }
        const [w,h] = formats[items['paper-size'].value] || formats['148x210'];
        const pxW = Math.ceil(w / 25.4 * 300);
        const pxH = Math.ceil(h / 25.4 * 300);
        message.textContent = image.naturalWidth >= pxW && image.naturalHeight >= pxH
          ? 'Définition suffisante pour environ 300 ppp au format sélectionné (à confirmer avec Corep).'
          : 'Image potentiellement peu définie pour l’impression : environ '+pxW+' × '+pxH+' pixels conseillés à 300 ppp pour ce format.';
      }, {once:true});
      image.src = url;
      image.hidden = false;
      update();
    });
  }
  photoInput('paper-front-image','paper-front-visual','paper-front-image-check',true);
  photoInput('paper-back-image','paper-back-visual','paper-back-image-check',true);
  photoInput('paper-barcode-image','paper-barcode-visual',null,false);
  const draftKey = 'librairie:paper-cover-draft:v1';
  const draftStatus = el('paper-draft-status');
  const draftFields = Object.keys(items);
  el('paper-save-draft').addEventListener('click', () => {
    try {
      const fields = Object.fromEntries(draftFields.map(key => [key,items[key].type === 'checkbox' ? items[key].checked : items[key].value]));
      localStorage.setItem(draftKey,JSON.stringify({version:1,savedAt:Date.now(),fields}));
      draftStatus.textContent = 'Réglages sauvegardés sur cet appareil. Les images devront être rechargées.';
    } catch (_) {draftStatus.textContent='Sauvegarde locale indisponible dans ce navigateur.';}
  });
  el('paper-restore-draft').addEventListener('click', () => {
    try {
      const state=JSON.parse(localStorage.getItem(draftKey) || 'null');
      if(!state || state.version!==1 || Date.now()-state.savedAt>30*86400000 || Date.now()<state.savedAt){
        throw Error('aucun brouillon récent');
      }
      for(const key of draftFields){
        const value=state.fields?.[key];
        if(typeof value!=='string' && typeof value!=='boolean') continue;
        if(String(value).length>1200) continue;
        if(items[key].type==='checkbox')items[key].checked=Boolean(value);
        else items[key].value=String(value);
      }
      update();
      draftStatus.textContent='Réglages restaurés. Rechargez les images si elles étaient utilisées.';
    }catch(_){draftStatus.textContent='Aucun brouillon valide de moins de 30 jours.';}
  });
  el('paper-clear-draft').addEventListener('click', () => {
    try {localStorage.removeItem(draftKey);draftStatus.textContent='Sauvegarde locale supprimée.';}
    catch(_){draftStatus.textContent='Effacement local indisponible.';}
  });
  const print = side => {
    update();
    if (side === 'back' && items['paper-isbn'].value.trim()
      && !window.PaperBarcode?.valid(items['paper-isbn'].value.trim())) {
      el('paper-print-check').textContent = 'Export interrompu : l’ISBN-13 saisi est invalide. Corrigez-le ou retirez-le.';
      items['paper-isbn'].focus();
      return;
    }
    const [w,h] = formats[items['paper-size'].value] || formats['148x210'];
    el('paper-sheet-size').textContent = '@media print{@page{size:'+w+'mm '+h+'mm;margin:0}}';
    document.documentElement.dataset.paperPrint = side;
    window.print();
  };
  el('paper-export-front').addEventListener('click', () => print('front'));
  el('paper-export-back').addEventListener('click', () => print('back'));
  window.addEventListener('afterprint', () => { delete document.documentElement.dataset.paperPrint; });
  for (const item of Object.values(items)) item.addEventListener('input',update);
  update();
})();
