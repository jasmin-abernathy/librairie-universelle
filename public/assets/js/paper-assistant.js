(() => {
  'use strict';
  const el = id => document.getElementById(id);
  const ids = ['paper-size','paper-title','paper-author','paper-subtitle','paper-bg','paper-ink','paper-description','paper-isbn','paper-front-ready'];
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
    setText('paper-back-isbn',items['paper-isbn'].value.trim() ? 'ISBN : '+items['paper-isbn'].value.trim() : 'Emplacement réservé au code-barres');
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
        return;
      }
      if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > maxBytes) {
        input.value = '';
        if (message) message.textContent = 'Format ou taille non accepté : JPEG, PNG ou WebP, 15 Mo maximum.';
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
    });
  }
  photoInput('paper-front-image','paper-front-visual','paper-front-image-check',true);
  photoInput('paper-back-image','paper-back-visual','paper-back-image-check',true);
  photoInput('paper-barcode-image','paper-barcode-visual',null,false);
  const print = side => {
    update();
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
