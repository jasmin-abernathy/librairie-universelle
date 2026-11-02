(() => {
    'use strict';
    const template=document.getElementById('book-template');
    const target=document.getElementById('book-pages');
    const status=document.getElementById('render-status');
    const button=document.getElementById('print-button');
    const reviewed=document.getElementById('preview-reviewed');
    const audit=document.getElementById('preview-audit');
    const reviewStatus=document.getElementById('preview-review-status');
    const bookelisHandoff=document.getElementById('bookelis-cover-handoff');
    const bookelisPages=document.getElementById('bookelis-real-pages');
    const bookelisTransferPages=document.getElementById('bookelis-transfer-page-count');
    const copyPages=document.getElementById('bookelis-copy-pages');
    const copySummary=document.getElementById('bookelis-copy-summary');
    const copyFeedback=document.getElementById('bookelis-copy-feedback');
    const transferFormat=document.getElementById('bookelis-transfer-format');
    const transferBinding=document.getElementById('bookelis-transfer-binding');
    let actualPages=0;
    const copyBookelisText=async (value,success)=>{
        if(!value || !copyFeedback)return;
        try {
            if(!navigator.clipboard || !navigator.clipboard.writeText)throw Error('no clipboard');
            await navigator.clipboard.writeText(value);
            copyFeedback.textContent=success;
        } catch (_) {
            // Les navigateurs mobiles peuvent refuser l'accès au presse-papiers.
            window.prompt('Copiez ces informations :',value);
            copyFeedback.textContent='Sélectionnez puis copiez les informations dans la fenêtre.';
        }
    };
    if(copyPages)copyPages.addEventListener('click',()=>{
        if(!actualPages)return;
        copyBookelisText(String(actualPages),'Nombre de pages copié. Collez-le dans le champ « Nombre de pages » chez Bookelis.');
    });
    if(copySummary)copySummary.addEventListener('click',()=>{
        if(!actualPages)return;
        const summary=[
            'Informations pour le gabarit Bookelis',
            'Nombre de pages : '+actualPages,
            'Format du livre : '+(transferFormat?.textContent.trim()||'à vérifier'),
            'Type de couverture envisagé : '+(transferBinding?.textContent.trim()||'à choisir'),
            'Papier, couleur, pelliculage et commercialisation : à choisir chez Bookelis',
            'Source : Librairie universelle (contrôlez le nombre de pages du PDF enregistré)'
        ].join('\n');
        copyBookelisText(summary,'Récapitulatif copié. Vérifiez ensuite chaque option dans le générateur Bookelis.');
    });
    let rendered=false,critical=false;
    const printReady=()=>{
        button.disabled=!rendered||critical||!reviewed.checked;
        if(bookelisHandoff)bookelisHandoff.hidden=button.disabled;
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
        // Ne conserver que le nombre de pages et le profil, jamais le manuscrit.
        if (!critical && pages > 0 && pages <= 5000) {
            try {
                localStorage.setItem('librairie:paper-preview-count:v1',JSON.stringify({
                    pages, provider:document.body.dataset.printProvider||'corep_metz',
                    trim:document.body.dataset.printTrim||'a5', at:Date.now()
                }));
            } catch (_) { /* Le PDF reste exportable si le stockage est désactivé. */ }
        }
        if(bookelisPages)bookelisPages.textContent=String(pages);
        actualPages=critical?0:pages;
        if(bookelisTransferPages)bookelisTransferPages.textContent=String(pages);
        if(copyPages)copyPages.disabled=critical;
        if(copySummary)copySummary.disabled=critical;
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
