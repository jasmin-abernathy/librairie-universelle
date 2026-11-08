(() => {
  'use strict';
  const panel = document.getElementById('panel-paper');
  const form = document.getElementById('autoedition-form');
  const nav = panel?.querySelector('.paper-step-nav');
  const layout = document.getElementById('print-layout-settings');
  const source = form?.querySelector('input[name="office_manuscript"]');
  const readyPdf = form?.querySelector('input[name="print_pdf"]');
  const pagesField = document.getElementById('print-estimate-pages');
  if (!panel || !form || !nav || !layout || !source || !readyPdf || !pagesField) return;
  const fieldset = nav.parentElement;
  if (!fieldset) return;
  const get = id => document.getElementById(id);
  const sourceCard = get('paper-interior-step');
  const provider = get('paper-printer-step');
  const intro = sourceCard?.previousElementSibling;
  const printRequest = get('print-preparation-requested')?.closest('label');
  const pdfHeading = layout.nextElementSibling;
  const pdfHelp = pdfHeading?.nextElementSibling;
  const pdfGrid = pdfHelp?.nextElementSibling;
  const pdfLabel = readyPdf.closest('label');
  const paperPriceLabel = form.querySelector('input[name="paper_price"]')?.closest('label');
  const coverCorep = get('paper-covers-step')?.parentElement;
  const bookelisCover = get('paper-bookelis-cover-step');
  const coverInputs = form.querySelector('input[name="paper_cover_front"]')?.closest('.form-grid');
  const corepHint = coverInputs?.nextElementSibling;
  const pricePanel = get('print-cost-estimator');
  const pdfx = get('paper-pdfx-step');
  const kit = get('paper-dossier-step');
  const corepTransfer = get('paper-corep-step');
  const bookelisTransfer = fieldset.querySelector('.resource-card.paper-step-card[data-printer-only="bookelis"]:not(#paper-bookelis-cover-step)');
  const resources = fieldset.querySelector('.resource-section');
  const create = (tag,cls,text) => {
    const el = document.createElement(tag);
    if (cls) el.className = cls;
    if (text) el.textContent = text;
    return el;
  };
  const root = create('div','paper-wizard');
  root.id = 'paper-wizard';
  root.setAttribute('aria-label','Assistant de préparation du livre');
  const progress = create('nav','paper-wizard-progress');
  progress.setAttribute('aria-label','Étapes de préparation');
  root.appendChild(progress);
  const names = ['Importer le livre','Mise en page','Couverture et ISBN','Estimation et prix','Kit et transmission'];
  const steps = names.map((name,i) => {
    const section = create('section','paper-wizard-screen');
    section.id = 'paper-wizard-step-'+i;
    section.setAttribute('aria-labelledby','paper-wizard-title-'+i);
    section.hidden = i!==0;
    const title = create('h2','paper-wizard-heading',name);
    title.id = 'paper-wizard-title-'+i;
    section.appendChild(title);
    root.appendChild(section);
    const item = create('button','paper-wizard-progress-button',(i+1)+'. '+name);
    item.type='button';
    item.dataset.step=String(i);
    progress.appendChild(item);
    return {section,title,item};
  });
  const footer = create('div','paper-wizard-footer');
  const back = create('button','paper-wizard-secondary','← Retour');
  back.type='button';
  const next = create('button','paper-wizard-primary','Continuer →');
  next.type='button';
  const state = create('p','paper-wizard-state');
  state.setAttribute('aria-live','polite');
  footer.append(back,next,state);
  root.append(footer);
  nav.after(root);
  nav.hidden = true;
  const add=(index,...items)=>items.filter(Boolean).forEach(node=>steps[index].section.appendChild(node));
  // Le texte et le choix sont unifiés avant tout réglage technique.
  // Les deux champs historiques restent actifs pour les routes existantes,
  // mais un seul sélecteur les alimente dans les navigateurs compatibles.
  if (intro) intro.hidden=true;
  add(0,sourceCard,pdfLabel,printRequest);
  if (printRequest) {printRequest.hidden=true;const control=get('print-preparation-requested');control.checked=true;control.dispatchEvent(new Event('change',{bubbles:true}));}
  let unifiedUpload=null;
  let unifiedFeedback=null;
  const legacySourceLabel=source.closest('label');
  if(typeof DataTransfer==='function' && legacySourceLabel && pdfLabel && sourceCard){
    const unifiedWrapper=create('div','paper-wizard-unified-upload');
    const unifiedLabel=create('label','paper-wizard-upload-label');
    unifiedLabel.appendChild(create('strong','','Importer mon livre'));
    unifiedUpload=create('input');
    unifiedUpload.id='paper-book-upload';
    unifiedUpload.type='file';
    unifiedUpload.accept='.pdf,.docx,.odt,.epub,application/pdf,application/epub+zip,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text';
    unifiedUpload.setAttribute('aria-describedby','paper-book-upload-hint paper-book-upload-feedback');
    unifiedLabel.appendChild(unifiedUpload);
    const hint=create('p','paper-wizard-help','Un seul fichier à sélectionner. PDF : conserver la mise en page existante. DOCX, ODT ou EPUB : adapter les pages au format du livre.');
    hint.id='paper-book-upload-hint';
    unifiedFeedback=create('p','paper-wizard-upload-feedback','Aucun fichier sélectionné. Votre document ne sera pas envoyé à un imprimeur.');
    unifiedFeedback.id='paper-book-upload-feedback';
    unifiedFeedback.setAttribute('role','status');
    unifiedFeedback.setAttribute('aria-live','polite');
    unifiedWrapper.append(unifiedLabel,hint,unifiedFeedback);
    legacySourceLabel.before(unifiedWrapper);
    legacySourceLabel.hidden=true;
    pdfLabel.hidden=true;
  }
  const results = create('p','paper-wizard-status');
  results.id='paper-wizard-analysis';
  results.setAttribute('role','status');
  sourceCard?.after(results);
  add(1,layout);
  // La reliure se choisit avec la couverture, et non au milieu des marges.
  const binding = form.querySelector('select[name="print_binding"]')?.closest('label');
  if(binding) {binding.firstChild.textContent='Type de couverture / reliure';add(2,binding);}
  if(pdfGrid && !pdfGrid.children.length) pdfGrid.remove();
  if(pdfHeading) pdfHeading.remove();
  if(pdfHelp) pdfHelp.remove();
  const oldGenerate = get('paper-generate-button')?.closest('.print-preview-row');
  add(2,coverCorep,bookelisCover,coverInputs,corepHint);
  const isbnSection = create('section','paper-wizard-isbn');
  isbnSection.innerHTML =
    '<h3>ISBN et code-barres de votre livre</h3>'+
    '<p>Un code-barres ne donne pas un ISBN : il représente graphiquement le numéro officiel du livre. Cette étape peut être ignorée pour un tirage strictement privé.</p>'+
    '<label>ISBN papier déjà attribué (facultatif pour un tirage privé)<input id="wizard-isbn" inputmode="numeric" autocomplete="off" placeholder="978… ou 979…" maxlength="24"></label>'+
    '<p id="wizard-isbn-feedback" role="status" aria-live="polite"></p>'+
    '<div class="paper-wizard-barcode-choices"><label><input type="radio" name="wizard_barcode_mode" value="generate" checked> Générer gratuitement le code-barres</label>'+
    '<label><input type="radio" name="wizard_barcode_mode" value="existing"> J’ai déjà son image</label>'+
    '<label><input type="radio" name="wizard_barcode_mode" value="bookelis"> Laisser Bookelis l’ajouter (si ISBN Bookelis)</label></div>'+
    '<div id="wizard-barcode-generated" hidden><img id="wizard-barcode-preview" alt="Aperçu du code-barres ISBN" width="226" height="164"><p><a id="wizard-barcode-download" class="button-link secondary" download="code-barres-isbn.svg">Télécharger le code-barres SVG</a></p><small>Contrôlez la lisibilité et le contraste en noir sur fond blanc avant impression.</small></div>'+
    '<div id="wizard-barcode-existing" hidden><label>Image fournie par votre éditeur ou l’AFNIL<input id="wizard-barcode-file" type="file" accept=".svg,.png,.jpg,.jpeg,image/svg+xml,image/png,image/jpeg"></label><p>Réutilisez cette image sur votre quatrième de couverture. Elle ne sera pas remplacée automatiquement.</p></div>'+
    '<p id="wizard-barcode-bookelis" hidden>Si Bookelis attribue l’ISBN, son outil peut placer automatiquement l’ISBN, le code-barres et le prix. Pour votre propre ISBN, prévoyez une image et vérifiez les consignes Bookelis. Ne dupliquez pas ces éléments.</p>'+
    '<details><summary>Je n’ai pas d’ISBN : comment en obtenir un ?</summary><ol>'+
    '<li>Pour un livre destiné uniquement à vos proches, aucun ISBN n’est nécessaire.</li>'+
    '<li>Pour une édition destinée à la vente, <a href="https://www.afnil.org/formulaire/demande_isbn_particulier/" target="_blank" rel="noopener noreferrer">remplissez la demande officielle auprès de l’AFNIL</a> (si vous publiez comme particulier).</li>'+
    '<li>Le tarif normal actuellement publié par l’AFNIL est de 37 € HT, avec un délai annoncé de trois semaines. La demande doit porter sur le nom du véritable éditeur.</li>'+
    '<li>Après réception, attribuez un ISBN distinct à l’édition papier et à l’édition EPUB commercialisée séparément. Saisissez ici le numéro du papier : nous produirons son code EAN-13 sans inventer d’identifiant.</li>'+
    '</ol><p><a href="https://www.afnil.org/nos-services/" target="_blank" rel="noopener noreferrer">Tarifs et consignes officiels de l’AFNIL</a>.</p></details>';
  add(2,isbnSection);
  add(3,pricePanel,paperPriceLabel);
  const priceAdvice = create('section','paper-wizard-advice');
  priceAdvice.innerHTML =
    '<h3>Quel prix de vente choisir ?</h3>'+
    '<p>Repères de prix calculés à partir du coût de fabrication connu et de votre rémunération brute souhaitée, sans prix de vente imposé.</p>'+
    '<div class="paper-wizard-price-inputs"><label>Coût d’impression unitaire si vous avez un devis (€)<input type="number" id="wizard-manual-cost" min="0" step=".01" inputmode="decimal" placeholder="À renseigner si nécessaire"></label>'+
    '<label>Rémunération brute souhaitée par livre (€)<input type="number" id="wizard-target-earnings" min="0" step=".5" value="4" inputmode="decimal"></label></div>'+
    '<label class="check-row"><input id="wizard-plan-bookstores" type="checkbox" checked> Prévoir aussi les ventes en librairie (remise professionnelle hypothétique de 30 %)</label>'+ 
    '<div class="paper-wizard-price-cards"><div><span>Prix public unique TTC suggéré</span><strong id="wizard-price-public">—</strong></div><div><span>Reste brut par exemplaire — vente directe</span><strong id="wizard-price-direct">—</strong></div><div><span>Reste brut par exemplaire — vente en librairie (30 %)</span><strong id="wizard-price-shop">—</strong></div></div>'+
    '<p id="wizard-price-origin" role="status">Le tarif de Bookelis est à obtenir sur son calculateur officiel. Aucun montant n’est inventé.</p>'+ 
    '<button type="button" id="wizard-apply-price" class="paper-wizard-secondary" disabled>Retenir ce prix public pour ma couverture</button><p id="wizard-price-apply-status" role="status" aria-live="polite"></p>'+
    '<p class="paper-wizard-help"><strong>Prix unique du livre :</strong> en France, un livre neuf a un seul prix public TTC fixé par l’éditeur pour une même édition, quel que soit le vendeur (hors rabais légaux). Ces montants sont des restes bruts simulés, pas des bénéfices nets. Les frais de livraison, commissions réelles, fiscalité et retours restent à déduire. Le prix TTC final devra figurer sur la couverture.</p>';
  add(3,priceAdvice);
  add(4,oldGenerate,pdfx,kit,corepTransfer,bookelisTransfer,resources);
  const finalHelp = create('p','paper-wizard-help','Le kit comprend les PDF préparés et les consignes propres à l’imprimeur choisi. Aucun achat ni transfert n’est déclenché : la confirmation du devis, le contrôle des fichiers et le BAT restent chez le professionnel.');
  steps[4].section.insertBefore(finalHelp, steps[4].section.firstElementChild?.nextSibling||null);

  // Kit d'impression local. La composition DOCX/ODT/EPUB reste effectuée par
  // Paged.js : l'utilisateur sauvegarde son intérieur PDF après relecture.
  const kitBuilder=create('section','paper-wizard-isbn');
  kitBuilder.innerHTML =
    '<h3>Assembler et télécharger mon kit d’impression</h3>'+
    '<p>Pour un PDF déjà maquetté, réutilisez le fichier d’origine. Pour un manuscrit recomposé, ouvrez l’aperçu, enregistrez le PDF intérieur relu, puis ajoutez-le ici. Le kit est créé dans votre navigateur sans transmission à Corep ni Bookelis.</p>'+
    '<div class="paper-wizard-price-inputs"><label>Titre du livre<input id="wizard-kit-title" maxlength="180" autocomplete="off" required placeholder="Titre figurant en couverture"></label>'+
    '<label>Nom de l’auteur ou de l’autrice<input id="wizard-kit-author" maxlength="180" autocomplete="off" required placeholder="Nom figurant en couverture"></label></div>'+
    '<label id="wizard-final-interior-label">Intérieur PDF final (après mise en page)<input type="file" id="wizard-final-interior" accept=".pdf,application/pdf"></label>'+
    '<label id="wizard-bookelis-cover-label">Couverture Bookelis complète à plat (4e + tranche + 1re) en PDF<input type="file" id="wizard-bookelis-full-cover" accept=".pdf,application/pdf"></label>'+
    '<p class="paper-wizard-help">Corep utilise la première et la quatrième séparées, à joindre dans l’étape couverture. Bookelis exige sa couverture complète à plat selon son gabarit officiel : ce fichier ne peut pas être fabriqué sans le gabarit correspondant.</p>'+
    '<button type="button" id="wizard-build-kit" class="paper-wizard-primary">Télécharger le kit ZIP</button>'+
    '<p id="wizard-kit-feedback" aria-live="polite" role="status">Les PDF devront être validés par l’imprimeur. Un ZIP n’est pas un BAT ni une commande.</p>';
  add(4,kitBuilder);
  const kitFeedback=get('wizard-kit-feedback');
  const validatePdf=async file=>{
    if(!file||!file.name.toLowerCase().endsWith('.pdf')||file.size<20)throw Error('Un fichier PDF manque ou n’est pas valide.');
    const header=new Uint8Array(await file.slice(0,5).arrayBuffer());
    const tail=new Uint8Array(await file.slice(Math.max(0,file.size-4096)).arrayBuffer());
    if(!window.PrinterKit?.pdfSignature(header)||!window.PrinterKit?.pdfTrailer(tail))
      throw Error('Le PDF '+file.name+' ne présente pas les marqueurs de début et de fin attendus.');
    return file;
  };
  get('wizard-build-kit').addEventListener('click',async()=>{
    const button=get('wizard-build-kit');
    button.disabled=true;kitFeedback.textContent='Vérification des fichiers et préparation locale de l’archive…';
    try{
      if(!window.PrinterZip||!window.PrinterKit)throw Error('Le module de création ZIP est indisponible.');
      const pr=selection(),count=Number(pagesField.value),qty=Number(get('print-estimate-quantity').value);
      if(!Number.isInteger(count)||count<1||count>5000)throw Error('Le nombre de pages doit être confirmé avant le kit.');
      if(pr==='corep_metz'&&(count>680||size()!=='a5'))
        throw Error('Corep Metz accepte ici uniquement l’A5 jusqu’à 680 pages recto verso : choisissez Bookelis ou consultez Corep pour un devis spécial.');
      if(isPdf()&&(!pdfData||!pdfData.trim))throw Error('Format PDF natif inconnu : demandez une vérification manuelle avant fabrication.');
      const title=get('wizard-kit-title').value.trim(),author=get('wizard-kit-author').value.trim();
      if(!title||!author)throw Error('Renseignez le titre et le nom figurant en couverture.');
      const isbnVal=isbn.value.trim();
      if(isbnVal&&!window.PaperBarcode?.valid(isbnVal))throw Error('ISBN-13 invalide ; corrigez-le ou laissez le champ vide.');
      const interior=await validatePdf(isPdf()?readyPdf.files?.[0]:get('wizard-final-interior').files?.[0]);
      const files=[{name:'interieur.pdf',blob:interior}];
      const manifest={
        schema:'librairie-print-kit-v2',created_at:new Date().toISOString(),
        title,author,isbn13:isbnVal?window.PaperBarcode.digits(isbnVal):null,
        provider:pr,format:size(),pages:count,quantity:qty,
        cover_type:form.querySelector('select[name="print_binding"]')?.value||'paperback',
        public_price_ttc:form.querySelector('input[name="paper_price"]')?.value||null,
        barcode_mode:form.querySelector('input[name="wizard_barcode_mode"]:checked')?.value||'generate',
        guarantee:'Prix indicatif ou devis à confirmer : le ZIP n’est pas un contrôle prépresse ni un BAT.',
        original_pdf_preserved:isPdf()
      };
      let instructions='';
      if(pr==='corep_metz'){
        const front=await validatePdf(form.querySelector('input[name="paper_cover_front"]')?.files?.[0]);
        const back=await validatePdf(form.querySelector('input[name="paper_cover_back"]')?.files?.[0]);
        files.push({name:'premiere-couverture.pdf',blob:front},{name:'quatrieme-couverture.pdf',blob:back});
        instructions=[
          'COREP METZ — DEMANDE DE DEVIS ET CONTROLE PREPRESSE','',
          '1. Ouvrez https://www.corep.fr/fr/devis-sur-mesure/',
          '2. Choisissez l’agence de Metz, puis indiquez le format A5 (148 x 210 mm).',
          '3. Spécifiez intérieur noir et blanc recto verso 80 g, couverture couleur, dos carré collé.',
          '4. Ajoutez interieur.pdf, premiere-couverture.pdf et quatrieme-couverture.pdf séparément.',
          '5. Indiquez '+count+' pages et '+qty+' exemplaires ; demandez un devis ferme et un BAT.',
          '6. Demandez la validation des marges, des polices, des éventuels fonds perdus et de la tranche.',
          'Les couvertures séparées demandées par Corep Metz ne nécessitent pas de traits de coupe ni fonds perdus.',
          'Aucun accord national ou prix garanti. Les documents sont à vérifier par l’atelier.'
        ].join('\n');
      }else{
        const cover=await validatePdf(get('wizard-bookelis-full-cover').files?.[0]);
        files.push({name:'couverture-complete-bookelis.pdf',blob:cover});
        instructions=[
          'BOOKELIS — IMPRESSION ET CONTROLE','',
          '1. Ouvrez https://livres.bookelis.com/imprimer et choisissez le même format.',
          '2. Renseignez '+count+' pages et '+qty+' exemplaires et choisissez le papier, la couleur et la couverture.',
          '3. Chargez interieur.pdf puis couverture-complete-bookelis.pdf.',
          '4. La couverture fournie doit suivre le gabarit officiel généré pour CES paramètres : https://livres.bookelis.com/generation-couverture',
          '5. Vérifiez les fonds perdus (8 mm sur couverture ; 5 mm sur intérieur avec visuels jusqu’au bord), les polices, le code-barres et le prix.',
          '6. Comparez le tarif officiel, contrôlez l’épreuve et confirmez la commande chez Bookelis.',
          'Aucun tarif Bookelis n’a été inventé ni transféré automatiquement depuis la Librairie.'
        ].join('\n');
      }
      files.push({name:'fiche-impression.json',blob:new Blob([JSON.stringify(manifest,null,2)],{type:'application/json'})});
      if(pr==='bookelis'){
        files.push({name:'instructions.txt',blob:new Blob([instructions],{type:'text/plain'})});
      }else{
        // PrinterZip v1 regroupe exactement trois PDF et une fiche.
        manifest.instructions=instructions;
        files[3].blob=new Blob([JSON.stringify(manifest,null,2)],{type:'application/json'});
      }
      const zip=await window.PrinterZip.create(files);
      const url=URL.createObjectURL(zip),link=document.createElement('a');
      link.href=url;link.download='kit-impression-'+pr+'-'+new Date().toISOString().slice(0,10)+'.zip';
      document.body.appendChild(link);link.click();link.remove();
      setTimeout(()=>URL.revokeObjectURL(url),15000);
      kitFeedback.textContent='Kit ZIP téléchargé : fichiers à faire contrôler, devis et BAT à confirmer chez '+(pr==='bookelis'?'Bookelis':'Corep Metz')+'.';
    }catch(e){kitFeedback.textContent='Kit non généré : '+(e.message||'vérification impossible.');}
    finally{button.disabled=false;}
  });
  const syncKitFields=()=>{
    get('wizard-final-interior-label').hidden=isPdf();
    get('wizard-bookelis-cover-label').hidden=selection()!=='bookelis';
    if(isPdf()&&pdfData&&get('wizard-kit-title').value.trim()==='')get('wizard-kit-title').value=readyPdf.files[0].name.replace(/\.pdf$/i,'').replace(/[_-]+/g,' ').slice(0,180);
  };
  readyPdf.addEventListener('change',syncKitFields);
  source.addEventListener('change',syncKitFields);
  form.querySelectorAll('input[name="print_provider"]').forEach(el=>el.addEventListener('change',syncKitFields));
  const finalFrame=create('iframe','paper-wizard-final-frame');
  finalFrame.id='paper-wizard-final-frame';
  finalFrame.name='paper_wizard_final_iframe';
  finalFrame.title='Aperçu du PDF intérieur et commandes d’export';
  finalFrame.hidden=true;
  steps[4].section.insertBefore(finalFrame,kitBuilder);
  const finalFrameStatus=create('p','paper-wizard-help','L’aperçu final s’affichera ici sans recharger votre page.');
  steps[4].section.insertBefore(finalFrameStatus,finalFrame);
  const finalPreviewFlag=create('input');
  finalPreviewFlag.type='hidden';finalPreviewFlag.name='wizard_preview';finalPreviewFlag.value='0';
  form.appendChild(finalPreviewFlag);
  const pdfSourceButton=get('paper-generate-button');
  let originalObjectUrl=null;
  const originalLabel=pdfSourceButton?.closest('.print-preview-row');
  const previewWithinWizard=()=>{
    if(!hasSource()||isPdf()){
      finalFrameStatus.textContent='Le PDF importé est conservé à l’identique : vous pouvez le vérifier directement et l’utiliser dans le kit.';
      return;
    }
    const target=form.target,action=form.action,estimate=estimateOnly.value;
    form.target=finalFrame.name;form.action='/print-preview.php';
    estimateOnly.value='0';finalPreviewFlag.value='1';
    finalFrame.hidden=false;
    finalFrameStatus.textContent='Composition de l’aperçu en cours dans cet espace. Après vérification, utilisez « Enregistrer l’intérieur en PDF » dans la fenêtre intégrée.';
    try{HTMLFormElement.prototype.submit.call(form);}
    catch(_){finalFrameStatus.textContent='Aperçu impossible. Essayez le bouton d’export classique.';}
    finally{
      form.target=target;form.action=action;estimateOnly.value=estimate;finalPreviewFlag.value='0';
    }
  };
  if(pdfSourceButton){
    pdfSourceButton.textContent='Composer et prévisualiser le PDF dans cette étape';
    pdfSourceButton.addEventListener('click',event=>{event.preventDefault();previewWithinWizard();});
  }
  const syncFinalFrame=()=>{
    if(originalObjectUrl){URL.revokeObjectURL(originalObjectUrl);originalObjectUrl=null;}
    finalFrame.hidden=true;
    if(originalLabel) originalLabel.hidden=isPdf();
    if(isPdf()&&readyPdf.files?.[0]){
      originalObjectUrl=URL.createObjectURL(readyPdf.files[0]);
      finalFrame.src=originalObjectUrl;
      finalFrame.hidden=false;
      finalFrameStatus.textContent='PDF d’origine affiché sans recomposition. Certaines visionneuses mobiles ouvrent le document dans un lecteur dédié.';
    } else {
      finalFrame.removeAttribute('src');
      finalFrameStatus.textContent='Pour un DOCX/ODT/EPUB, composez et vérifiez le PDF intérieur directement ici.';
    }
  };
  readyPdf.addEventListener('change',syncFinalFrame);
  source.addEventListener('change',syncFinalFrame);

  // Réduire la dernière étape : le kit et l’aperçu sont d’abord, les anciens
  // utilitaires restent disponibles dans un panneau secondaire facultatif.
  const finalScreen=steps[4].section;
  const extras=create('details','paper-wizard-extras');
  extras.appendChild(create('summary','','Autres outils et imprimeurs'));
  [pdfx,kit,resources].filter(Boolean).forEach(el=>extras.appendChild(el));
  [oldGenerate,finalFrameStatus,finalFrame,kitBuilder,corepTransfer,bookelisTransfer,extras]
    .filter(Boolean).forEach(el=>finalScreen.appendChild(el));

  // Les étapes d'une seule page évitent les rechargements HTML et conservent tous les champs du formulaire.
  let active=0,seen=0,pdfData=null,analysisPending=false,estimateTimer=null,nonce=0,estimateRequest='',pdfCheckSerial=0;
  const isPdf=()=>Boolean(readyPdf.files?.length);
  const hasSource=()=>Boolean(source.files?.length);
  const selection=()=>form.querySelector('input[name="print_provider"]:checked')?.value||'corep_metz';
  const size=()=>get('print-trim-size')?.value||'a5';
  const setStatus = message => {results.textContent=message;state.textContent=message;};
  const validSteps=()=>isPdf()?[0,2,3,4]:[0,1,2,3,4];
  pagesField.readOnly=true;
  pagesField.setAttribute('aria-label','Pages calculées automatiquement');
  const pagesOk=()=>pagesField.value.trim()!=='' && Number.isInteger(Number(pagesField.value)) && Number(pagesField.value)>0;
  const show=(index,initial=false)=>{
    if(!validSteps().includes(index))index=validSteps()[0];
    active=index;seen=Math.max(seen,index);
    steps.forEach((item,i)=>{
      item.section.hidden=i!==active;
      item.item.hidden=!validSteps().includes(i);
      item.item.disabled=i>seen;
      if(i===active){item.item.setAttribute('aria-current','step');item.item.classList.add('is-current');}
      else {item.item.removeAttribute('aria-current');item.item.classList.remove('is-current');}
    });
    back.hidden=active===0;
    next.textContent=active===4?'Revoir les étapes':'Continuer →';
    if(active===4)next.hidden=true;else next.hidden=false;
    if(!initial && !panel.hidden){
      root.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
      steps[active].title.setAttribute('tabindex','-1');steps[active].title.focus({preventScroll:true});
    }
    if(active===1 && hasSource() && !pagesOk())scheduleEstimate();
    if(active===3)refreshAdvice();
  };
  const invalidate=message=>{
    if(!isPdf())pdfData=null;
    estimateRequest='';
    pagesField.value='';
    pagesField.dispatchEvent(new Event('input',{bubbles:true}));
    setStatus(message);
    refreshAdvice();
  };
  const providerMirror=create('label','paper-wizard-provider-mirror');
  providerMirror.innerHTML='<strong>Choisir le prestataire pour le devis</strong><select id="wizard-provider"><option value="corep_metz">Corep Metz</option><option value="bookelis">Bookelis</option></select>';
  pricePanel.insertBefore(providerMirror,pricePanel.firstElementChild?.nextSibling||null);
  const mirrorSelect=get('wizard-provider');
  const syncProvider=()=>{
    mirrorSelect.value=selection();
    const radio=form.querySelector('input[name="print_provider"]:checked');
    const corep=form.querySelector('input[name="print_provider"][value="corep_metz"]');
    if(isPdf() && pdfData && !pdfData.corep_eligible && corep) {
      corep.disabled=true;
      if(radio===corep) {
        const bookelis=form.querySelector('input[name="print_provider"][value="bookelis"]');
        if(bookelis){bookelis.checked=true;bookelis.dispatchEvent(new Event('change',{bubbles:true}));}
      }
      mirrorSelect.querySelector('option[value="corep_metz"]').disabled=true;
    } else {
      if(corep)corep.disabled=false;
      mirrorSelect.querySelector('option[value="corep_metz"]').disabled=false;
    }
    mirrorSelect.value=selection();
    const chapterPolicy=form.querySelector('input[name="print_chapter_start"]');
    if(chapterPolicy) chapterPolicy.value='right';
    const bleedPolicy=form.querySelector('input[name="print_bleed_mm"]');
    if(bleedPolicy)bleedPolicy.value=selection()==='bookelis'?'0':'3';
    if(isPdf() && pdfData && pdfData.trim){
      const trim=get('print-trim-size');
      trim.value=pdfData.trim;
      trim.dispatchEvent(new Event('change',{bubbles:true}));
    }
    refreshAdvice();
  };
  mirrorSelect.addEventListener('change',()=>{
    const radio=form.querySelector('input[name="print_provider"][value="'+mirrorSelect.value+'"]');
    if(radio && !radio.disabled){radio.checked=true;radio.dispatchEvent(new Event('change',{bubbles:true}));}
  });
  form.querySelectorAll('input[name="print_provider"]').forEach(r=>r.addEventListener('change',syncProvider));
  const analyzePdf=async()=>{
    if(!isPdf())return;
    const ticket=++pdfCheckSerial,selectedFile=readyPdf.files[0];
    pdfData=null;analysisPending=true;
    setStatus('Lecture du format et du nombre de pages du PDF…');
    const body=new FormData();
    body.set('csrf_token',form.querySelector('[name="csrf_token"]')?.value||'');
    body.set('print_pdf',readyPdf.files[0]);
    try {
      const response=await fetch('/analyser-pdf.php',{method:'POST',body,credentials:'same-origin',headers:{'Accept':'application/json'}});
      const data=await response.json();
      if(!response.ok||data.error)throw Error(data.error||'Analyse impossible.');
      if(!isPdf()||ticket!==pdfCheckSerial||readyPdf.files[0]!==selectedFile)return;
      pdfData=data;
      pagesField.value=String(data.pages);
      pagesField.dispatchEvent(new Event('input',{bubbles:true}));
      const fmt=(data.trim||'non reconnu')+' · '+data.width_mm+' × '+data.height_mm+' mm';
      setStatus('PDF : '+data.pages+' pages, '+fmt+'. Mise en page conservée ; vérifiez les éventuelles pages de formats différents.');
      syncProvider();
    }catch(e){
      if(ticket===pdfCheckSerial){
        pdfData=null;
        invalidate('PDF non analysé : '+(e.message||'erreur')+'. Le fichier reste intact. Un devis fiable nécessite une vérification du PDF.');
      }
    }finally{if(ticket===pdfCheckSerial)analysisPending=false;}
  };
  const frame=document.createElement('iframe');
  frame.id='paper-wizard-pagination-frame';
  frame.name='paper_wizard_preview_iframe';
  frame.title='Calcul temporaire de pagination';
  frame.setAttribute('aria-hidden','true');
  frame.tabIndex=-1;
  root.appendChild(frame);
  frame.addEventListener('load',()=>{
    try{
      const error=frame.contentDocument?.querySelector('.error-box');
      if(error && hasSource())setStatus('Pagination impossible : '+error.textContent.trim());
    }catch(_){}
  });
  const estimateOnly=create('input');estimateOnly.type='hidden';estimateOnly.name='estimate_only';estimateOnly.value='1';form.appendChild(estimateOnly);
  const estimateNonce=create('input');estimateNonce.type='hidden';estimateNonce.name='estimate_nonce';form.appendChild(estimateNonce);
  const runEstimate=()=>{
    if(!hasSource()||isPdf())return;
    nonce+=1;
    estimateRequest=String(nonce);
    estimateNonce.value=estimateRequest;
    const target=form.target,action=form.action;
    form.target=frame.name;
    form.action='/print-preview.php';
    setStatus('Pré-calcul de la pagination en cours… aucun PDF final n’est créé.');
    try {HTMLFormElement.prototype.submit.call(form);}
    catch(_) {setStatus('Le calcul n’a pas pu être lancé. Vous pouvez utiliser l’aperçu final.');}
    finally{form.target=target;form.action=action;}
  };
  const scheduleEstimate=()=>{
    if(!hasSource()||isPdf())return;
    if(estimateTimer)clearTimeout(estimateTimer);
    invalidate('Réglages modifiés : le nombre de pages va être recalculé.');
    estimateTimer=setTimeout(runEstimate,1050);
  };
  window.addEventListener('message',event=>{
    if(event.origin!==location.origin||event.source!==frame.contentWindow)return;
    const data=event.data;
    if(!data||data.type!=='librairie:paper-count'||data.request!==estimateRequest)return;
    if(data.error){setStatus('Pagination non disponible : '+data.error);return;}
    if(data.critical && !data.overLimit){
      invalidate('Une image est manquante ou la pagination est incomplète. Corrigez le manuscrit avant de calculer un prix.');
      return;
    }
    if(!Number.isInteger(data.pages)||data.pages<1)return;
    pagesField.value=String(data.pages);
    pagesField.dispatchEvent(new Event('input',{bubbles:true}));
    setStatus(data.pages+' pages calculées pour '+(data.provider==='bookelis'?'Bookelis':'Corep')+
      ' au format '+data.trim+'. '+(data.overLimit?'Corep dépasse sa limite de 680 pages : choisissez Bookelis.':'Ce n’est pas encore un PDF définitif.'));
    if(data.overLimit){
      const bookelis=form.querySelector('input[name="print_provider"][value="bookelis"]');
      if(bookelis&&selection()==='corep_metz'){bookelis.checked=true;bookelis.dispatchEvent(new Event('change',{bubbles:true}));scheduleEstimate();}
    }
    refreshAdvice();
  });
  if(unifiedUpload){
    unifiedUpload.addEventListener('change',()=>{
      const file=unifiedUpload.files?.[0];
      if(!file){
        source.value=''; readyPdf.value='';
        source.dispatchEvent(new Event('change',{bubbles:true}));
        unifiedFeedback.textContent='Aucun fichier sélectionné. Sélectionnez un PDF, DOCX, ODT ou EPUB.';
        return;
      }
      const name=file.name||'';
      const ext=name.split('.').pop()?.toLowerCase()||'';
      const format=ext==='pdf'||file.type==='application/pdf'?'pdf'
        :['docx','odt','epub'].includes(ext)?ext:null;
      if(!format){
        unifiedUpload.value='';
        source.value=''; readyPdf.value='';
        source.dispatchEvent(new Event('change',{bubbles:true}));
        unifiedFeedback.textContent='Format non pris en charge : choisissez un PDF, DOCX, ODT ou EPUB sans DRM.';
        return;
      }
      try{
        const data=new DataTransfer();
        data.items.add(file);
        // Les noms de champs de la soumission existante sont conservés.
        // On transfère le fichier vers une seule cible, et vide l'autre.
        source.value='';readyPdf.value='';
        const dest=format==='pdf'?readyPdf:source;
        dest.files=data.files;
        if(!dest.files?.length)throw Error('Transfert non disponible');
        dest.dispatchEvent(new Event('change',{bubbles:true}));
        unifiedFeedback.textContent=format==='pdf'
          ?'PDF détecté : sa mise en page est conservée. Nous analysons son format et son nombre de pages ; les réglages de recomposition sont ignorés.'
          :format.toUpperCase()+' détecté : nous adapterons la pagination au format choisi. Le fichier d’origine reste inchangé.';
      }catch(_){
        unifiedWrapperFailure();
      }
    });
    const unifiedWrapperFailure=()=>{
      // En cas de navigateur ne permettant pas de réaffecter FileList,
      // préserver les sélecteurs originaux, pas une fausse sélection.
      unifiedUpload.closest('.paper-wizard-unified-upload').hidden=true;
      legacySourceLabel.hidden=false;
      pdfLabel.hidden=false;
      source.value='';readyPdf.value='';
      unifiedFeedback.textContent='Votre navigateur nécessite les sélecteurs de fichiers classiques.';
      setStatus('Sélectionnez votre fichier dans le champ correspondant ci-dessous.');
    };
  }

  const resetPerBook=()=>{
    const manual=get('wizard-manual-cost');
    if(manual)manual.value='';
    const publicPrice=form.querySelector('input[name="paper_price"]');
    if(publicPrice)publicPrice.value='';
    try {sessionStorage.removeItem('librairie:paper-price-for-cover');sessionStorage.removeItem('librairie:paper-isbn-for-cover');} catch(_){}
    get('wizard-price-apply-status').textContent='';
    seen=0;
  };
  source.addEventListener('change',()=>{
    resetPerBook();
    if(source.files?.length){pdfCheckSerial+=1;readyPdf.value='';pdfData=null;analysisPending=false;}
    invalidate(source.files?.length?'Manuscrit chargé : choisissez vos réglages pour calculer les pages.':'Choisissez un manuscrit ou un PDF.');
    syncProvider();
    if(hasSource()&&active===1)scheduleEstimate();
  });
  readyPdf.addEventListener('change',()=>{
    resetPerBook();
    if(readyPdf.files?.length){source.value='';if(estimateTimer)clearTimeout(estimateTimer);}
    invalidate(readyPdf.files?.length?'PDF chargé : analyse des pages…':'Choisissez un manuscrit ou un PDF.');
    syncProvider();
    if(isPdf())analyzePdf();
  });
  form.querySelectorAll('#print-layout-settings select,#print-layout-settings input:not([type="file"])').forEach(control=>{
    control.addEventListener('change',()=>{
      if(hasSource())scheduleEstimate();
      if(control.name==='print_provider')syncProvider();
    });
  });
  const isbn=get('wizard-isbn'),isbnFeedback=get('wizard-isbn-feedback'),barcode=get('wizard-barcode-preview'),
    barcodeGenerated=get('wizard-barcode-generated'),barcodeDownload=get('wizard-barcode-download');
  const updateBarcode=()=>{
    const mode=form.querySelector('input[name="wizard_barcode_mode"]:checked')?.value||'generate';
    get('wizard-barcode-existing').hidden=mode!=='existing';
    get('wizard-barcode-bookelis').hidden=mode!=='bookelis';
    barcodeGenerated.hidden=true;
    const digits=window.PaperBarcode?.digits(isbn.value)||'';
    if(!digits){try{sessionStorage.removeItem('librairie:paper-isbn-for-cover');}catch(_){} isbnFeedback.textContent='Sans ISBN : possible pour une impression strictement privée.';return;}
    if(!window.PaperBarcode?.valid(digits)){try{sessionStorage.removeItem('librairie:paper-isbn-for-cover');}catch(_){} isbnFeedback.textContent='ISBN-13 invalide : vérifiez les 13 chiffres et la clé de contrôle. Aucun code-barres inventé.';return;}
    isbnFeedback.textContent='ISBN-13 valide sur le plan du chiffre de contrôle. Son attribution officielle reste à vérifier auprès de l’éditeur.';
    try{sessionStorage.setItem('librairie:paper-isbn-for-cover',digits);}catch(_){}
    if(mode!=='generate')return;
    const svg=window.PaperBarcode.svg(digits);
    const src='data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg);
    barcode.src=src;barcodeDownload.href=src;barcodeDownload.download='isbn-'+digits+'-ean13.svg';
    barcodeGenerated.hidden=false;
  };
  isbn.addEventListener('input',updateBarcode);
  form.querySelectorAll('input[name="wizard_barcode_mode"]').forEach(e=>e.addEventListener('change',updateBarcode));
  const pageMoney=new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR'});
  const toMoney=value=>pageMoney.format(value);
  const roundPrice=v=>Math.ceil(v);
  const grids=(()=>{try{return JSON.parse(pricePanel.dataset.grids||'[]');}catch(_){return [];}})();
  const refreshAdvice=()=>{
    if(!pricePanel)return;
    let unit=null,origin='Aucun prix fictif : calculez le tarif Bookelis sur leur site officiel ou renseignez le devis obtenu.';
    const pageCount=Number(pagesField.value);
    const quantity=Number(get('print-estimate-quantity')?.value);
    const price=get('wizard-manual-cost');
    const manual=price?.value.trim()||'';
    if(manual!=='' && Number(manual)>=0 && Number.isFinite(Number(manual))){
      unit=Number(manual);origin='Coût unitaire saisi manuellement : à vérifier sur le devis reçu.';
    }else if(selection()==='corep_metz' && Number.isInteger(pageCount) && Number.isInteger(quantity) && size()==='a5'){
      const g=grids.find(grid=>grid?.amounts_cents?.[pageCount]?.[quantity]>0);
      if(g){unit=g.amounts_cents[pageCount][quantity]/100/quantity;origin='Coût unitaire issu du palier Corep autorisé (indicatif, hors coûts supplémentaires).';}
      else origin='Pas de grille Corep autorisée pour ce nombre exact de pages et ce tirage. Un devis est nécessaire.';
    }
    const earned=Number(get('wizard-target-earnings')?.value);
    if(unit===null||!Number.isFinite(earned)||earned<0){
      get('wizard-price-public').textContent='—';
      get('wizard-apply-price').disabled=true;
      get('wizard-apply-price').dataset.price='';
      get('wizard-price-direct').textContent='—';
      get('wizard-price-shop').textContent='—';
    }else{
      const includeBookshops=get('wizard-plan-bookstores').checked;
      const publicPrice=roundPrice((unit+earned)/(includeBookshops?0.7:1));
      get('wizard-price-public').textContent=toMoney(publicPrice);
      get('wizard-apply-price').disabled=false;
      get('wizard-apply-price').dataset.price=String(publicPrice);
      get('wizard-price-direct').textContent=toMoney(publicPrice-unit);
      get('wizard-price-shop').textContent=toMoney(publicPrice*.7-unit);
    }
    get('wizard-price-origin').textContent=origin;
  };
  get('wizard-apply-price').addEventListener('click',()=>{
    const price=Number(get('wizard-apply-price').dataset.price);
    if(!Number.isFinite(price)||price<=0)return;
    const field=form.querySelector('input[name="paper_price"]');
    if(!field)return;
    field.value=price.toFixed(2);
    field.dispatchEvent(new Event('input',{bubbles:true}));
    try{sessionStorage.setItem('librairie:paper-price-for-cover',String(price));}catch(_){}
    get('wizard-price-apply-status').textContent='Prix public TTC retenu : '+toMoney(price)+'. Reprenez-le sur la quatrième de couverture avant d’envoyer le kit à l’imprimeur.';
  });
  get('wizard-manual-cost').addEventListener('input',refreshAdvice);
  get('wizard-target-earnings').addEventListener('input',refreshAdvice);
  get('wizard-plan-bookstores').addEventListener('change',refreshAdvice);
  pagesField.addEventListener('input',refreshAdvice);
  get('print-estimate-quantity')?.addEventListener('change',refreshAdvice);
  get('print-trim-size')?.addEventListener('change',refreshAdvice);
  const chooseNext=()=>{
    if(active===0){
      if(isPdf()){
        if(analysisPending){setStatus('Analyse du PDF en cours…');return;}
        if(!pdfData){setStatus('Le PDF doit être analysé pour obtenir un nombre de pages fiable.');return;}
        show(2);
      }else if(hasSource()){show(1);}
      else setStatus('Ajoutez un manuscrit DOCX/ODT/EPUB ou un PDF déjà mis en page.');
      return;
    }
    if(active===1){if(!pagesOk()){setStatus('Le pré-calcul est en cours ou indisponible : vérifiez le manuscrit ou relancez le calcul.');return;}show(2);return;}
    if(active===2){show(3);return;}
    if(active===3){show(4);return;}
  };
  next.addEventListener('click',chooseNext);
  back.addEventListener('click',()=>{const sequence=validSteps(),position=sequence.indexOf(active);if(position>0)show(sequence[position-1]);});
  steps.forEach((step,i)=>step.item.addEventListener('click',()=>{if(i<=seen&&validSteps().includes(i))show(i);}));
  const oldLink=nav.querySelectorAll('a');
  oldLink.forEach(a=>a.removeAttribute('href'));
  pagesField.value='';
  updateBarcode();
  syncProvider();
  syncKitFields();
  syncFinalFrame();
  show(0,true);
})();