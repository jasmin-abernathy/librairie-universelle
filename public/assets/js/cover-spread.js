/* Composition d'une couverture à plat dans le navigateur : aucun fichier envoyé au serveur.
 * Les cotes de tranche doivent provenir du papier réellement utilisé, ou de l'imprimeur.
 * Un PDF exporté ici reste à confronter au gabarit officiel avant fabrication. */
(function(root,factory){
  const api=factory();
  if(typeof module!=='undefined' && module.exports)module.exports=api;
  if(root){root.LibrairieCoverSpread=api;api.mount();}
})(typeof window!=='undefined'?window:null,function(){
  'use strict';
  let generatedPdf=null;
  let validatedTemplate=null;
  let currentTemplateDimensions=null;
  const mmpt=72/25.4;
  const fmt=n=>Number(n.toFixed(2));
  const clamp=(n,min,max)=>Math.min(max,Math.max(min,n));
  function dimensions({trimWidth,trimHeight,pages,leafMm,spineMm,bleedMm}){
    const w=Number(trimWidth),h=Number(trimHeight),p=Number(pages),b=Number(bleedMm);
    if(!Number.isFinite(w)||w<95||w>305||!Number.isFinite(h)||h<95||h>305)
      throw Error('Format du livre invalide.');
    if(!Number.isInteger(p)||p<1||p>5000)throw Error('Calculez d’abord le nombre exact de pages.');
    if(!Number.isFinite(b)||b<0||b>15)throw Error('Le fond perdu doit être compris entre 0 et 15 mm.');
    const direct=spineMm!==''&&spineMm!==null&&spineMm!==undefined;
    const leaf=Number(leafMm);
    const spine=direct?Number(spineMm):Math.ceil(p/2)*leaf;
    if(!direct&&(!Number.isFinite(leaf)||leaf<.04||leaf>.4))
      throw Error('Indiquez l’épaisseur réelle d’une feuille fournie par l’imprimeur, ou directement la largeur du dos.');
    if(!Number.isFinite(spine)||spine<.5||spine>100)
      throw Error('Largeur de tranche hors limites : vérifiez le papier, la pagination et le coefficient indiqué.');
    return {trimWidth:w,trimHeight:h,pages:p,bleedMm:b,spineMm:fmt(spine),
      totalWidthMm:fmt(w*2+spine+2*b),totalHeightMm:fmt(h+2*b),
      method:direct?'tranche communiquée par l’imprimeur':'nombre de feuilles × épaisseur de la feuille'};
  }
  const escape=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&apos;');
  const color=s=>/^#[0-9a-fA-F]{6}$/.test(s||'')?s:'#275842';
  function wrapWords(text,max,maxLines){
    const words=String(text||'').trim().replace(/\s+/g,' ').split(' ').filter(Boolean);
    const lines=[];let current='';
    for(const word of words){
      const part=word.slice(0,max);
      if(!current)current=part;
      else if((current+' '+part).length<=max)current+=' '+part;
      else {lines.push(current);current=part;}
    }
    if(current)lines.push(current);
    if(lines.length>maxLines)throw Error('Texte trop long pour la couverture : raccourcissez-le afin de garantir sa lisibilité.');
    return lines;
  }
  function svgMarkup(dim,{title,author,description,background,frontImage,backImage,isbn,barcodeBits,price,showGuides=false}={}){
    const {trimWidth:w,trimHeight:h,spineMm:s,bleedMm:b,totalWidthMm:W,totalHeightMm:H}=dim;
    const frontX=b+w+s,spineX=b+w,frontColor=color(background);
    const backColor='#f3efe4',safe=Math.max(8,Math.min(11,w*.065));
    const parts=[
      '<svg xmlns="http://www.w3.org/2000/svg" width="'+W+'mm" height="'+H+'mm" viewBox="0 0 '+W+' '+H+'">',
      '<rect width="'+W+'" height="'+H+'" fill="'+backColor+'"/>',
      '<rect x="'+spineX+'" width="'+(W-spineX)+'" height="'+H+'" fill="'+frontColor+'"/>'
    ];
    const art=(src,x,width)=>{
      if(!/^data:image\/(?:jpeg|png|webp);base64,[A-Za-z0-9+/=]+$/.test(src||''))return;
      const id='clip'+x.toString().replace(/\./g,'_');
      parts.push('<defs><clipPath id="'+id+'"><rect x="'+x+'" y="0" width="'+width+'" height="'+H+'"/></clipPath></defs>');
      parts.push('<image x="'+x+'" y="0" width="'+width+'" height="'+H+'" preserveAspectRatio="xMidYMid slice" href="'+src+'" clip-path="url(#'+id+')"/>');
    };
    art(backImage,0,b+w);
    art(frontImage,frontX,w+b);
    if(frontImage)parts.push('<rect x="'+frontX+'" y="'+b+'" width="'+w+'" height="'+Math.min(h*.37,82)+'" fill="#111" fill-opacity=".48"/>');
    if(backImage)parts.push('<rect x="'+b+'" y="'+(b+h*.23)+'" width="'+w+'" height="'+Math.min(h*.56,116)+'" fill="#fff" fill-opacity=".88"/>');
    const lines=(text,x,y,size,fill,max,maxLines,spacing=1.35)=>{
      const ls=wrapWords(text,max,maxLines);
      ls.forEach((t,i)=>parts.push('<text x="'+fmt(x)+'" y="'+fmt(y+i*size*spacing)+'" font-size="'+size+'" font-family="DejaVu Sans,Arial,sans-serif" fill="'+fill+'" font-weight="bold">'+escape(t)+'</text>'));
    };
    lines(title||'Titre du livre',frontX+safe,b+safe+15,clamp(w/13,8,13),'#fff',Math.round(w/5.5),3,1.35);
    lines(author||"Nom de l’auteur",frontX+safe,b+h-Math.max(22,safe*2),5,'#fff',Math.round(w/3.5),2,1.3);
    lines(description||'Présentation du livre',b+safe,b+h*.28,3.7,'#1c2f26',Math.round((w-safe*2)/2.1),7,1.6);
    if(s>=8){
      const mx=spineX+s/2,my=b+h/2;
      parts.push('<text transform="translate('+fmt(mx)+' '+fmt(my)+') rotate(-90)" text-anchor="middle" font-family="DejaVu Sans,Arial,sans-serif" font-size="'+fmt(clamp(s-3,3,6))+'" fill="#fff">'+escape((title||'').slice(0,75))+'</text>');
    }
    const bcValid=/^[01]{95}$/.test(barcodeBits||'') && /^97[89]\d{10}$/.test(isbn||'');
    if(bcValid){
      const boxW=42,boxH=31,x=b+w-safe-boxW,y=b+h-safe-boxH,barW=boxW*.83/95,px=x+3.5;
      parts.push('<rect x="'+fmt(x)+'" y="'+fmt(y)+'" width="'+boxW+'" height="'+boxH+'" fill="white"/>');
      for(let i=0;i<95;i++) if(barcodeBits[i]==='1')
        parts.push('<rect x="'+fmt(px+i*barW)+'" y="'+fmt(y+3)+'" width="'+fmt(barW+.01)+'" height="'+(i<3||(i>=45&&i<50)||i>=92?21:19)+'" fill="#000"/>');
      parts.push('<text x="'+fmt(x+boxW/2)+'" y="'+fmt(y+29)+'" text-anchor="middle" fill="#111" font-family="monospace" font-size="2.9">'+isbn+'</text>');
    }
    if(price!==''&&price!==null&&price!==undefined&&Number.isFinite(Number(price))&&Number(price)>=0){
      parts.push('<text x="'+fmt(b+safe)+'" y="'+fmt(b+h-safe)+'" font-size="4" fill="#1c2f26" font-family="Arial,sans-serif">'+escape(new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR'}).format(Number(price)))+'</text>');
    }
    if(showGuides){
      for(const x of [b,b+w,b+w+s,W-b]){
        parts.push('<line x1="'+fmt(x)+'" y1="0" x2="'+fmt(x)+'" y2="'+H+'" stroke="#ef5b4d" stroke-width=".28" stroke-dasharray="2 1"/>');
      }
      for(const y of [b,H-b])parts.push('<line x1="0" y1="'+fmt(y)+'" x2="'+W+'" y2="'+fmt(y)+'" stroke="#ef5b4d" stroke-width=".28" stroke-dasharray="2 1"/>');
    }
    return parts.join('')+'</svg>';
  }
  function pdfFromJpeg(jpg,widthMm,heightMm,pxWidth,pxHeight){
    if(!(jpg instanceof Uint8Array)||jpg.length<4||jpg[0]!==0xff||jpg[1]!==0xd8)
      throw Error('Image JPEG invalide pour l’export PDF.');
    if(!Number.isFinite(widthMm)||!Number.isFinite(heightMm)||widthMm<=0||heightMm<=0||
       !Number.isInteger(pxWidth)||!Number.isInteger(pxHeight)||pxWidth<1||pxHeight<1)
      throw Error('Dimensions PDF incorrectes.');
    const enc=new TextEncoder(),parts=[];let length=0;
    const add=chunk=>{const a=typeof chunk==='string'?enc.encode(chunk):chunk;parts.push(a);length+=a.length;};
    const offsets=[0]; const w=(widthMm*mmpt).toFixed(3),h=(heightMm*mmpt).toFixed(3);
    add('%PDF-1.4\n%PDF Image\n');
    const obj=(index,content)=>{offsets[index]=length;add(index+' 0 obj\n'+content+'\nendobj\n');};
    obj(1,'<< /Type /Catalog /Pages 2 0 R >>');
    obj(2,'<< /Type /Pages /Count 1 /Kids [3 0 R] >>');
    obj(3,'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '+w+' '+h+'] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>');
    offsets[4]=length;
    add('4 0 obj\n<< /Type /XObject /Subtype /Image /Width '+pxWidth+' /Height '+pxHeight+
      ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '+jpg.length+' >>\nstream\n');
    add(jpg);
    add('\nendstream\nendobj\n');
    const draw='q\n'+w+' 0 0 '+h+' 0 0 cm\n/Im0 Do\nQ\n';
    obj(5,'<< /Length '+enc.encode(draw).length+' >>\nstream\n'+draw+'endstream');
    const xref=length;
    add('xref\n0 6\n0000000000 65535 f \n');
    for(let i=1;i<=5;i++)add(String(offsets[i]).padStart(10,'0')+' 00000 n \n');
    add('trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n'+xref+'\n%%EOF\n');
    const data=new Uint8Array(length);let at=0;
    for(const part of parts){data.set(part,at);at+=part.length;}
    return data;
  }
  /**
   * Le PDF officiel apporte la largeur totale, y compris ses fonds perdus.
   * On ne déduit JAMAIS l'épaisseur du dos à partir d'un grammage supposé.
   */
  function dimensionsFromTemplate({totalWidthMm,totalHeightMm,trimWidth,trimHeight,pages,bleedMm=8}){
    const W=Number(totalWidthMm),H=Number(totalHeightMm),w=Number(trimWidth),h=Number(trimHeight);
    const b=Number(bleedMm),p=Number(pages);
    if(!Number.isInteger(p)||p<1||p>5000)throw Error('Le nombre exact de pages du PDF intérieur est nécessaire.');
    if(!Number.isFinite(W)||!Number.isFinite(H)||!Number.isFinite(w)||!Number.isFinite(h)
      ||!Number.isFinite(b)||w<95||h<95||W>1000||H>1000||b!==8)
      throw Error('Dimensions du gabarit PDF incorrectes.');
    if(Math.abs(H-(h+2*b))>0.4)
      throw Error('Hauteur incompatible avec le format intérieur et les 8 mm de fond perdu Bookelis.');
    const s=W-2*w-2*b;
    if(!Number.isFinite(s)||s<0.5||s>100)
      throw Error('Largeur incompatible avec le format intérieur : vérifiez le gabarit téléchargé.');
    return {
      trimWidth:w,trimHeight:h,pages:p,bleedMm:b,spineMm:fmt(s),
      totalWidthMm:fmt(W),totalHeightMm:fmt(H),
      method:'mesures du gabarit PDF importé'
    };
  }
  function mount(){
    const container=document.getElementById('paper-spread-editor');
    if(!container)return;
    const el=id=>document.getElementById(id);
    const form=document.getElementById('autoedition-form')||container.closest('form');
    const provider=()=>form.querySelector('input[name="print_provider"]:checked')?.value||'corep_metz';
    const stock=el('print-paper-stock'),trim=el('print-trim-size'),binding=form.querySelector('[name="print_binding"]');
    const pages=el('print-estimate-pages'),templateInput=el('bookelis-template-pdf');
    const templateStatus=el('bookelis-template-status'),templatePreview=el('bookelis-template-preview');
    const commercial=el('bookelis-commercialisation'),lamination=el('bookelis-lamination'),ink=el('bookelis-ink');
    const status=el('spread-status'),details=el('spread-dimensions'),preview=el('spread-preview'),button=el('spread-download');
    const canvasLimit=24000000;
    const sizes={a5:[148,210],'130x180':[130,180],'156x234':[156,234],
      '170x244':[170,244],'170x170':[170,170],'190x150':[190,150],a4:[210,297]};
    const formats={a5:'14,8 × 21 cm (A5)','130x180':'13 × 18 cm','156x234':'15,6 × 23,4 cm',
      '170x244':'17 × 24,4 cm','170x170':'17 × 17 cm','190x150':'19 × 15 cm, à l’italienne',a4:'21 × 29,7 cm (A4)'};
    let templateData=null,templateSignature='',templateUrl='';
    let generatedSignature='',images={front:'',back:''};
    const signature=()=>[provider(),trim.value,stock.value,pages.value,binding.value,
      commercial.value,lamination.value,ink.value].join('|');
    const pageCount=()=>Number(pages.value);
    const dimensionsNow=()=>{
      if(!templateData||templateSignature!==signature())
        throw Error('Importez le gabarit PDF Bookelis correspondant à ces paramètres avant d’exporter.');
      const d=sizes[trim.value];
      if(!d)throw Error('Format du livre inconnu.');
      return dimensionsFromTemplate({...templateData,trimWidth:d[0],trimHeight:d[1],pages:pageCount()});
    };
    const hasValidTemplate=()=>{
      try{return provider()==='bookelis'&&binding.value==='paperback'&&!!dimensionsNow();}
      catch(_){return false;}
    };
    const guideInfo=()=>{
      const p=pageCount();
      return [
        ['Nombre de pages',Number.isInteger(p)&&p>0?p+' pages':'en attente du PDF intérieur'],
        ['Format',formats[trim.value]||'à vérifier'],
        ['Commercialisation',commercial.value||'à choisir selon le projet'],
        ['Couleur d’impression',ink.value||'à sélectionner'],
        ['Type de papier',stock.selectedOptions[0]?.textContent?.replace(/\s*[—-]\s*Bookelis.*$/,'')||'à vérifier'],
        ['Type de couverture',binding.value==='paperback'?'Souple (broché)':'Cartonnée / reliée — non prise en charge dans cet outil'],
        ['Pelliculage',lamination.value||'à sélectionner'],
        ['Format du gabarit','PDF']
      ];
    };
    const updateGuide=()=>{
      const data=guideInfo();
      const ids=['bookelis-guide-pages','bookelis-guide-format',null,'bookelis-guide-ink',
        'bookelis-guide-paper','bookelis-guide-cover'];
      [0,1,3,4,5].forEach(k=>{
        if(el(ids[k]))el(ids[k]).textContent=data[k][1];
      });
      const warn=el('bookelis-guide-warning');
      if(warn)warn.textContent=commercial.value==='Distribution Hachette'&&stock.value!=='offset80'
        ?'La distribution Hachette impose le papier offset blanc 80 g chez Bookelis : choisissez ce papier ou une autre commercialisation.'
        :'Ces choix doivent correspondre à ceux du formulaire Bookelis. Aucun remplissage de leur site n’est effectué automatiquement.';
      el('bookelis-guide-copy').disabled=!(Number.isInteger(pageCount())&&pageCount()>0);
    };
    const resetPreview=()=>{
      if(templateUrl){URL.revokeObjectURL(templateUrl);templateUrl='';}
      templatePreview.hidden=true;templatePreview.removeAttribute('href');
    };
    const info=()=>{
      const isbn=el('wizard-isbn')?.value.trim().replace(/[\s-]/g,'')||'';
      const valid=window.PaperBarcode?.valid(isbn);
      const mode=form.querySelector('[name="wizard_barcode_mode"]:checked')?.value||'generate';
      return {
        title:el('spread-title').value,author:el('spread-author').value,
        description:el('spread-description').value,background:el('spread-bg').value,
        frontImage:images.front,backImage:images.back,
        isbn:valid&&mode==='generate'?isbn:'',
        barcodeBits:valid&&mode==='generate'?window.PaperBarcode.bits(isbn):'',
        price:form.querySelector('[name="paper_price"]')?.value||''
      };
    };
    const invalidate=()=>{
      generatedPdf=null;generatedSignature='';button.disabled=true;
      updateGuide();
      try{
        if(provider()!=='bookelis'){
          details.textContent='Ce calculateur de couverture complète concerne Bookelis.';
          status.textContent='Pour Corep, conservez la première et la quatrième séparées.';
          preview.replaceChildren();return;
        }
        if(binding.value!=='paperback'){
          details.textContent='La couverture cartonnée exige une autre construction.';
          status.textContent='Pour une couverture cartonnée Bookelis, utilisez le gabarit officiel et un outil adapté aux charnières et rabats.';
          preview.replaceChildren();return;
        }
        if(!commercial.value||!lamination.value||!ink.value)
          throw Error('Sélectionnez la commercialisation, l’impression et le pelliculage avant d’importer le gabarit.');
        if(commercial.value==='Distribution Hachette'&&stock.value!=='offset80')
          throw Error('Distribution Hachette : choisissez d’abord le papier offset blanc 80 g.');
        const d=dimensionsNow(),art=info();
        const pixels=Math.ceil(d.totalWidthMm*300/25.4)*Math.ceil(d.totalHeightMm*300/25.4);
        if(pixels>canvasLimit)throw Error('Ce format dépasse la capacité du générateur mobile. Utilisez un outil graphique adapté.');
        preview.innerHTML=svgMarkup(d,{...art,showGuides:true});
        details.textContent='Gabarit importé : '+d.totalWidthMm.toLocaleString('fr-FR')+
          ' × '+d.totalHeightMm.toLocaleString('fr-FR')+' mm · tranche : '+d.spineMm.toLocaleString('fr-FR')+' mm · 8 mm de fond perdu.';
        status.textContent='Dimensions reprises du gabarit importé. Les traits rouges sont uniquement les guides de l’aperçu ; ils ne constituent pas des repères d’impression certifiés.';
        button.disabled=false;
        templateStatus.textContent='Gabarit accepté : dimensions cohérentes avec le format. Origine officielle et repères à confirmer visuellement.';
      }catch(e){
        preview.replaceChildren();
        details.textContent='En attente d’un gabarit PDF correspondant au format et à la pagination retenus.';
        status.textContent=e.message||'Gabarit Bookelis requis.';
        if(templateData&&templateSignature!==signature())
          templateStatus.textContent='Les paramètres ont changé depuis l’import. Téléchargez un nouveau gabarit Bookelis puis importez-le.';
      }
    };
    const setStock=()=>{
      const isCorep=provider()==='corep_metz';
      stock.querySelectorAll('option[data-bookelis-paper]').forEach(option=>{
        option.disabled=isCorep;option.hidden=isCorep;
      });
      if(isCorep)stock.value='offset80';
      const help=el('paper-stock-help');
      if(help)help.textContent=isCorep
        ?'Corep : grille existante pour l’offset blanc 80 g uniquement.'
        :'Bookelis : choisissez ce papier dans leur formulaire de gabarit. En distribution Hachette, seul l’offset blanc 80 g est disponible.';
      invalidate();
    };
    const resetStockForFormat=()=>{
      if(provider()==='bookelis'){
        const small=['a5','130x180','156x234'].includes(trim.value);
        for(const option of stock.querySelectorAll('option[data-sizes]')){
          option.disabled=option.dataset.sizes===(small?'large':'small');
        }
        if(stock.selectedOptions[0]?.disabled)stock.value='offset80';
      }
      invalidate();
    };
    templateInput.addEventListener('change',async()=>{
      templateData=null;templateSignature='';resetPreview();
      generatedPdf=null;button.disabled=true;
      const file=templateInput.files?.[0];
      if(!file){templateStatus.textContent='Aucun gabarit PDF importé.';invalidate();return;}
      if(!/\.pdf$/i.test(file.name)||file.size<100||file.size>12*1048576){
        templateStatus.textContent='Sélectionnez un fichier PDF de moins de 12 Mo.';invalidate();return;
      }
      if(provider()!=='bookelis'||binding.value!=='paperback'||!commercial.value||!lamination.value||!ink.value){
        templateStatus.textContent='Terminez les choix Bookelis (commercialisation, impression, pelliculage) et utilisez une couverture souple avant l’import.';
        invalidate();return;
      }
      const before=signature();
      templateStatus.textContent='Analyse des dimensions du gabarit en cours…';
      try{
        const body=new FormData();
        body.append('bookelis_template',file,file.name);
        body.append('csrf_token',form.querySelector('input[name="csrf_token"]')?.value||'');
        const result=await fetch('/analyser-gabarit-bookelis.php',{
          method:'POST',body,credentials:'same-origin',cache:'no-store'
        });
        const data=await result.json();
        if(!result.ok||data.error)throw Error(data.error||'Gabarit non analysable.');
        if(before!==signature())throw Error('Les paramètres ont changé pendant l’analyse : réimportez le bon gabarit.');
        const size=sizes[trim.value];
        if(!size)throw Error('Format intérieur inconnu.');
        dimensionsFromTemplate({...data,trimWidth:size[0],trimHeight:size[1],pages:pageCount()});
        templateData={totalWidthMm:data.width_mm,totalHeightMm:data.height_mm};
        templateSignature=before;
        templateUrl=URL.createObjectURL(file);
        templatePreview.href=templateUrl;templatePreview.hidden=false;
        templateStatus.textContent='PDF reconnu : 1 page, '+data.width_mm+' × '+data.height_mm+
          ' mm. L’origine Bookelis n’est pas vérifiable automatiquement.';
        invalidate();
      }catch(e){
        templateData=null;templateSignature='';
        templateStatus.textContent='Import refusé : '+(e.message||'erreur inconnue');
        invalidate();
      }
    });
    for(const name of ['front','back']){
      el('spread-'+name+'-image').addEventListener('change',async event=>{
        images[name]='';
        const file=event.target.files?.[0];
        if(file){
          if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>12*1048576){
            event.target.value='';status.textContent='Illustration refusée : JPEG, PNG ou WebP, 12 Mo maximum.';invalidate();return;
          }
          try{
            images[name]=await new Promise((resolve,reject)=>{
              const reader=new FileReader();
              reader.onload=()=>resolve(String(reader.result));reader.onerror=reject;
              reader.readAsDataURL(file);
            });
          }catch(_){status.textContent='Illustration illisible.';}
        }
        invalidate();
      });
    }
    [pages,stock,trim,binding,commercial,lamination,ink,
      el('spread-title'),el('spread-author'),el('spread-description'),el('spread-bg')].forEach(input=>{
      input?.addEventListener('input',invalidate);
      input?.addEventListener('change',invalidate);
    });
    form.querySelectorAll('input[name="print_provider"]').forEach(input=>input.addEventListener('change',()=>{
      setStock();resetStockForFormat();
    }));
    trim.addEventListener('change',resetStockForFormat);
    el('wizard-isbn')?.addEventListener('input',invalidate);
    form.querySelectorAll('[name="wizard_barcode_mode"]').forEach(input=>input.addEventListener('change',invalidate));
    form.querySelector('[name="paper_price"]')?.addEventListener('input',invalidate);
    el('bookelis-guide-copy').addEventListener('click',async()=>{
      const content=guideInfo().map(entry=>entry[0]+' : '+entry[1]).join('\n');
      try{
        await navigator.clipboard.writeText(content);
        el('bookelis-guide-warning').textContent='Récapitulatif copié. Reportez ces choix dans le générateur Bookelis.';
      }catch(_){
        const field=document.createElement('textarea');
        field.value=content;field.style.position='fixed';field.style.top='-10000px';
        document.body.appendChild(field);field.select();
        const copied=document.execCommand('copy');field.remove();
        el('bookelis-guide-warning').textContent=copied?'Récapitulatif copié.':'Copie impossible dans ce navigateur : reportez les valeurs affichées.';
      }
    });
    button.addEventListener('click',async()=>{
      button.disabled=true;status.textContent='Création locale de la couverture à partir du gabarit validé…';
      try{
        if(!hasValidTemplate())throw Error('L’import valide du gabarit PDF officiel est obligatoire pour Bookelis.');
        const before=signature(),d=dimensionsNow(),art=info(),svg=svgMarkup(d,art);
        const pxW=Math.round(d.totalWidthMm*300/25.4),pxH=Math.round(d.totalHeightMm*300/25.4);
        if(pxW*pxH>canvasLimit)throw Error('Format trop grand pour un PDF local à 300 ppp.');
        const bitmap=await new Promise((resolve,reject)=>{
          const url=URL.createObjectURL(new Blob([svg],{type:'image/svg+xml;charset=utf-8'}));
          const image=new Image();
          image.onload=()=>{URL.revokeObjectURL(url);resolve(image);};
          image.onerror=()=>{URL.revokeObjectURL(url);reject(Error('Composition impossible : réduisez la taille des images.'));};
          image.src=url;
        });
        const canvas=document.createElement('canvas');
        canvas.width=pxW;canvas.height=pxH;
        const ctx=canvas.getContext('2d',{alpha:false});
        if(!ctx)throw Error('Canvas indisponible.');
        ctx.fillStyle='#fff';ctx.fillRect(0,0,pxW,pxH);ctx.drawImage(bitmap,0,0,pxW,pxH);
        const jpg=await new Promise((resolve,reject)=>
          canvas.toBlob(b=>b?resolve(b):reject(Error('Encodage JPEG impossible.')),'image/jpeg',.94));
        canvas.width=1;canvas.height=1;
        if(before!==signature()||!hasValidTemplate())throw Error('Paramètres modifiés pendant la génération : recommencez.');
        const pdf=pdfFromJpeg(new Uint8Array(await jpg.arrayBuffer()),d.totalWidthMm,d.totalHeightMm,pxW,pxH);
        generatedPdf=new File([pdf],'couverture-complete-bookelis.pdf',{type:'application/pdf'});
        generatedSignature=before;
        const url=URL.createObjectURL(generatedPdf),link=document.createElement('a');
        link.href=url;link.download=generatedPdf.name;document.body.appendChild(link);link.click();link.remove();
        setTimeout(()=>URL.revokeObjectURL(url),30000);
        status.textContent='PDF téléchargé selon les dimensions importées. Document RVB de travail : contrôler CMJN, repères, rogne et code-barres avant tout dépôt chez Bookelis.';
      }catch(e){
        generatedPdf=null;generatedSignature='';
        status.textContent='Export non effectué : '+(e.message||'erreur inconnue');
      }finally{invalidateIfNeeded();}
    });
    const invalidateIfNeeded=()=>{
      button.disabled=!hasValidTemplate();
    };
    validatedTemplate=hasValidTemplate;
    currentTemplateDimensions=dimensionsNow;
    setStock();resetStockForFormat();updateGuide();
  }
  return {dimensions,dimensionsFromTemplate,svgMarkup,pdfFromJpeg,
    getGeneratedPdf:()=>generatedPdf&&validatedTemplate?.()?generatedPdf:null,
    hasValidTemplate:()=>Boolean(validatedTemplate?.()),
    getTemplateDimensions:()=>validatedTemplate?.()?currentTemplateDimensions?.():null,
    mount
  };
});
