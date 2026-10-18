/* Printer package: local PDF header checks only, no uploads or network. */
(function(root,factory){
  const api=factory();
  if(typeof module!=='undefined'&&module.exports)module.exports=api;
  if(root)root.PrinterKit=api;
})(typeof window!=='undefined'?window:null,function(){
  'use strict';
  const isbnBarcode = typeof module !== 'undefined' && module.exports
    ? require('./paper-barcode.js')
    : (typeof window !== 'undefined' ? window.PaperBarcode : null);
  function validateISBN(input) {
    const value=String(input||'').trim();
    if(!value)return null;
    if(!isbnBarcode||!isbnBarcode.valid(value)){
      throw new Error('ISBN-13 invalide : vérifiez les 13 chiffres et leur chiffre de contrôle.');
    }
    return isbnBarcode.digits(value);
  }
  function pdfSignature(bytes){
    return bytes instanceof Uint8Array && bytes.length>=5 &&
      bytes[0]===37 && bytes[1]===80 && bytes[2]===68 && bytes[3]===70 && bytes[4]===45;
  }
  const safe=(text,n=180)=>String(text||'').trim().replace(/[\u0000-\u001f\u007f]/g,' ').slice(0,n);
  function manifest(data,descriptors){
    if(!Array.isArray(descriptors)||descriptors.length!==3||
       descriptors.some(f=>!f||!['interior','front','back'].includes(f.role)||!f.pdfValid))
       throw new Error('Trois fichiers PDF valides sont nécessaires.');
    if(!safe(data.title)||!safe(data.author))throw new Error('Titre et nom de couverture nécessaires.');
    const pages=data.pages===''?null:Number(data.pages);
    const quantity=data.quantity===''?null:Number(data.quantity);
    if((pages!==null&&(!Number.isInteger(pages)||pages<1||pages>5000))||
       (quantity!==null&&(!Number.isInteger(quantity)||quantity<1||quantity>10000)))
      throw new Error('Nombre de pages ou tirage invalide.');
    const roles=new Set(descriptors.map(f=>f.role));
    if(roles.size!==3||!['interior','front','back'].every(role=>roles.has(role)))
      throw new Error('Fournissez un intérieur, une première et une quatrième distincts.');
    const files=descriptors.map(f=>({role:f.role,name:safe(f.name,250),bytes:f.size}));
    const unique=new Set(files.map(f=>f.name.toLowerCase()+'|'+f.bytes));
    if(unique.size!==3)throw new Error('Des fichiers semblent dupliqués : vérifiez les trois rôles.');
    return {
      schema:'librairie-print-package-v1',generated_at:new Date().toISOString(),
      work:{title:safe(data.title),author:safe(data.author),isbn13:validateISBN(data.isbn)},
      requested_specification:{format:safe(data.format,20),paper:safe(data.paper,20),pages,quantity},
      files,
      notes:['L’intérieur est un PDF à pages simples, sans imposition.','La première et la quatrième sont des PDF distincts.','Épaisseur de tranche à calculer par l’imprimeur.','Tarifs, BAT, couleurs, fonds perdus, polices, conformité des PDF et délais à reconfirmer.'],
      preflight:'Contrôle limité à l’en-tête PDF et à une vérification visuelle déclarée. Aucun devis ni BAT automatique.'
    };
  }
  function message(doc){
    const lines=[
      'Bonjour,',
      '',
      'Je souhaiterais faire examiner un dossier d’impression pour « '+doc.work.title+' » ('+doc.work.author+').',
      'Format : '+doc.requested_specification.format+' ; papier intérieur : '+doc.requested_specification.paper+'.'
    ];
    if(doc.work.isbn13)lines.push('ISBN : '+doc.work.isbn13+'.');
    if(doc.requested_specification.pages)lines.push('Pages intérieures : '+doc.requested_specification.pages+'.');
    if(doc.requested_specification.quantity)lines.push('Tirage envisagé : '+doc.requested_specification.quantity+' exemplaires.');
    lines.push('','Pièces jointes :');
    for(const f of doc.files){
      const label={interior:'Intérieur',front:'Première de couverture',back:'Quatrième de couverture'}[f.role];
      lines.push('- '+label+' : '+f.name);
    }
    lines.push('','Les pages intérieures ne sont pas imposées. Merci de vérifier les fichiers, la tranche, les fonds perdus et les conditions du BAT, puis de confirmer le prix et le délai avant toute commande.','','Merci et bonne journée.');
    return lines.join('\n');
  }
  return{pdfSignature,manifest,message};
});
(function(){
  'use strict';
  if(typeof document==='undefined')return;
  const form=document.getElementById('printer-kit-form');
  if(!form||!window.PrinterKit)return;
  const $=id=>document.getElementById(id);
  const status=$('kit-status'),list=$('kit-file-report'),download=$('kit-download'),zipButton=$('kit-zip'),copy=$('kit-copy');
  const roles=[['interior','kit-interior'],['front','kit-front'],['back','kit-back']];
  let prepared=null;
  const clear=()=>{
    prepared=null;download.disabled=true;zipButton.disabled=true;copy.disabled=true;
    status.textContent='Fichiers et renseignements modifiés : relancez la préparation du dossier.';
  };
  for(const control of form.querySelectorAll('input,select'))control.addEventListener('change',clear);
  const descriptor=async(role,id)=>{
    const file=$(id).files?.[0];
    if(!file)throw new Error('Sélectionnez les trois PDF avant de continuer.');
    if(!/\.pdf$/i.test(file.name)||file.size<5)throw new Error('Fichier non conforme : '+file.name+'. Choisissez un PDF.');
    const bytes=new Uint8Array(await file.slice(0,5).arrayBuffer());
    if(!window.PrinterKit.pdfSignature(bytes))throw new Error('Le fichier '+file.name+' ne commence pas par un en-tête PDF valide.');
    return {role,name:file.name,size:file.size,pdfValid:true};
  };
  form.addEventListener('submit',async event=>{
    event.preventDefault();
    status.textContent='Vérification locale des en-têtes PDF…';
    prepared=null;download.disabled=true;zipButton.disabled=true;copy.disabled=true;
    try{
      if(!['kit-check-pages','kit-check-covers','kit-check-printer'].every(id=>$(id).checked))
        throw new Error('Cochez les trois vérifications avant la préparation du dossier.');
      if(!$('kit-title').value.trim()||!$('kit-author').value.trim())
        throw new Error('Indiquez le titre et le nom figurant sur le livre.');
      const isbn=$('kit-isbn').value.trim().replace(/[\s-]/g,'');
      if(isbn && !window.PaperBarcode?.valid(isbn))
        throw new Error('ISBN-13 invalide : vérifiez les 13 chiffres et le chiffre de contrôle.');
      const descriptors=await Promise.all(roles.map(([role,id])=>descriptor(role,id)));
      const manifest=window.PrinterKit.manifest({
        title:$('kit-title').value,author:$('kit-author').value,isbn,
        format:$('kit-format').value,paper:$('kit-paper').value,
        pages:$('kit-pages').value,quantity:$('kit-quantity').value
      },descriptors);
      prepared=manifest;
      list.replaceChildren(...descriptors.map(d=>{
        const li=document.createElement('li');
        li.textContent=({interior:'Intérieur',front:'Première de couverture',back:'Quatrième de couverture'}[d.role])+
          ' : '+d.name+' ('+(d.size/1048576).toFixed(2)+' Mo) · en-tête PDF reconnu';
        return li;
      }));
      const large=descriptors.filter(d=>d.size>20*1048576);
      if(large.length){const item=document.createElement('li');item.textContent=
        'Attention : '+large.length+' fichier(s) de plus de 20 Mo ; vérifiez les limites des pièces jointes de l’imprimeur.';list.appendChild(item);}
      download.disabled=false;zipButton.disabled=false;copy.disabled=false;
      status.textContent='Dossier préparé localement. Vérifiez maintenant les PDF avec votre imprimeur.';
    }catch(error){
      status.textContent='Dossier incomplet : '+(error?.message||'vérification impossible.');
    }
  });
  download.addEventListener('click',()=>{
    if(!prepared)return;
    const blob=new Blob([JSON.stringify(prepared,null,2)],{type:'application/json'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');a.href=url;a.download='dossier-impression-'+new Date().toISOString().slice(0,10)+'.json';
    document.body.appendChild(a);a.click();a.remove();
    setTimeout(()=>URL.revokeObjectURL(url),15000);
  });
  zipButton.addEventListener('click',async()=>{
    if(!prepared||!window.PrinterZip)return;
    const snapshot=prepared;zipButton.disabled=true;
    status.textContent='Préparation de l’archive sur votre appareil…';
    try{
      const entries=[
        {name:'interieur.pdf',blob:$('kit-interior').files[0]},
        {name:'premiere-couverture.pdf',blob:$('kit-front').files[0]},
        {name:'quatrieme-couverture.pdf',blob:$('kit-back').files[0]},
        {name:'fiche-imprimeur.json',blob:new Blob([JSON.stringify(snapshot,null,2)],{type:'application/json'})}
      ];
      const archive=await window.PrinterZip.create(entries);
      if(prepared!==snapshot)throw Error('Les fichiers ont changé : recommencez la vérification.');
      const url=URL.createObjectURL(archive),a=document.createElement('a');
      a.href=url;a.download='dossier-impression-'+new Date().toISOString().slice(0,10)+'.zip';
      document.body.appendChild(a);a.click();a.remove();
      setTimeout(()=>URL.revokeObjectURL(url),15000);
      status.textContent='Archive ZIP prête : les trois PDF et leur fiche y figurent. Faites confirmer la conformité par l’imprimeur.';
    }catch(e){status.textContent='Archive non créée : '+(e?.message||'erreur');}
    finally{zipButton.disabled=!prepared;}
  });
  copy.addEventListener('click',async()=>{
    if(!prepared)return;
    try{
      await navigator.clipboard.writeText(window.PrinterKit.message(prepared));
      status.textContent='Message copié. Ajoutez vous-même les trois PDF à votre e-mail.';
    }catch(_){status.textContent='Copie non disponible dans ce navigateur. Vous pouvez utiliser la fiche JSON.';}
  });
})();
