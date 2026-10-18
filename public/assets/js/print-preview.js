(() => {
    'use strict';
    const template=document.getElementById('book-template');
    const target=document.getElementById('book-pages');
    const status=document.getElementById('render-status');
    const button=document.getElementById('print-button');
    const reviewed=document.getElementById('preview-reviewed');
    const audit=document.getElementById('preview-audit');
    const reviewStatus=document.getElementById('preview-review-status');
    let rendered=false,critical=false;
    const printReady=()=>{
        button.disabled=!rendered||critical||!reviewed.checked;
        reviewStatus.textContent=critical
          ? 'Certaines images ou pages ne sont pas fiables. Corrigez le manuscrit avant de produire le PDF.'
          :!rendered?'Pagination en cours…'
          :!reviewed.checked?'Examinez les pages puis cochez la confirmation pour exporter.'
          :'Vous pouvez utiliser « Enregistrer au format PDF ». Recontrôlez le fichier exporté.';
    };
    if(!template||!target||!status||!button||!reviewed||!audit||!window.Paged){
        if(status)status.textContent='Moteur de pagination indisponible : aucune impression ne peut être validée.';
        return;
    }
    const previewer=new window.Paged.Previewer();
    previewer.preview(template.content.cloneNode(true),[],target).then(flow=>{
        const pages=Number(flow.total||0);
        const pageEls=[...target.querySelectorAll('.pagedjs_page')];
        const missingImages=[...target.querySelectorAll('img')].filter(i=>!i.complete||i.naturalWidth===0);
        // Les débordements graphiques complexes nécessitent toujours une lecture humaine.
        const emptyPageCount=pageEls.filter(p=>!p.textContent.trim()&&!p.querySelector('img,svg,canvas')).length;
        critical=pages<1||pageEls.length!==pages||missingImages.length>0;
        rendered=true;
        const sheets=Math.ceil(pages/2);
        status.textContent=pages+' page'+(pages>1?'s':'')+' · '+sheets+
            ' feuille'+(sheets>1?'s':'')+' recto-verso';
        const points=[
            pages+' pages paginées par le générateur.',
            missingImages.length?missingImages.length+' image(s) manquante(s) ou non décodée(s).':'Images du manuscrit chargées.',
            emptyPageCount+' page(s) sans contenu détecté : vérifiez les sauts de chapitre et le recto verso.',
            'Vérifiez le sommaire, les dernières lignes des pages et les ruptures de chapitre.',
            'Polices incorporées, PDF/X, fond perdu, dimensions définitives et BAT : à confirmer auprès de l’imprimeur.'
        ];
        audit.replaceChildren(...points.map(s=>{const li=document.createElement('li');li.textContent=s;return li;}));
        printReady();
    }).catch(()=>{
        status.textContent='La pagination a échoué : vérifiez le DOCX/ODT et ses illustrations.';
        audit.textContent='Impossible de vérifier les pages générées.';
        critical=true;
        printReady();
    });
    reviewed.addEventListener('change',printReady);
    button.addEventListener('click',()=>{if(!button.disabled)window.print();});
})();
