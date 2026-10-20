/* Aide à la diffusion : uniquement dans le navigateur, aucun envoi réseau. */
(function(root,factory){
    const api=factory();
    if(typeof module!=='undefined' && module.exports) module.exports=api;
    if(root) root.AuthorDistribution=api;
})(typeof window!=='undefined' ? window : null,function(){
    'use strict';
    const clean=(value,max)=>String(value||'').trim().replace(/[\u0000-\u001f\u007f]/g,' ').slice(0,max);
    function normalize(values){
        const v={};
        for(const [field,length] of Object.entries({bookstore:120,title:200,author:140,isbn:24,price:35,format:70,contact:180,summary:850,url:400})){
            v[field]=clean(values[field],length);
        }
        if(!v.title||!v.author||!v.summary) throw new Error('Indiquez le titre, le nom d’auteur et une courte présentation.');
        if(v.url && !/^https:\/\/[a-z0-9.-]+(?:[/:?#]|$)/i.test(v.url)) throw new Error('Pour la fiche publique, utilisez une adresse HTTPS valide.');
        return v;
    }
    function sheet(v){
        const lines=['FICHE DU LIVRE',v.title,'Auteur ou autrice : '+v.author];
        if(v.isbn)lines.push('ISBN papier : '+v.isbn);
        if(v.format)lines.push('Format : '+v.format);
        if(v.price)lines.push('Prix public : '+v.price);
        lines.push('','Présentation :',v.summary);
        if(v.url)lines.push('','Fiche publique : '+v.url);
        if(v.contact)lines.push('','Contact : '+v.contact);
        return lines.join('\n');
    }
    function message(v){
        const salutation=v.bookstore ? 'Bonjour à l’équipe de '+v.bookstore+',' : 'Bonjour,';
        const lines=[salutation,'',
          'Je suis l’auteur ou l’autrice de « '+v.title+' » et je souhaiterais vous présenter cet ouvrage afin de savoir s’il pourrait intéresser votre librairie.',
          '',v.summary,''];
        if(v.isbn)lines.push('ISBN papier : '+v.isbn+'.');
        if(v.format)lines.push('Format : '+v.format+'.');
        if(v.price)lines.push('Prix public : '+v.price+'.');
        if(v.url)lines.push('Vous pouvez consulter sa fiche : '+v.url);
        lines.push('','Seriez-vous disposés à examiner sa présentation ? Je peux vous transmettre les informations complémentaires utiles, et nous pourrions discuter de vos modalités habituelles (commande, dépôt-vente éventuel, remise, retours). Je ne présume pas de vos conditions.','','Merci pour votre temps et bonne journée,',v.author);
        if(v.contact)lines.push(v.contact);
        return lines.join('\n');
    }
    function build(values){const data=normalize(values);return {message:message(data),sheet:sheet(data)};}
    return {build,normalize};
});
(function(){
    'use strict';
    if(typeof document==='undefined')return;
    const form=document.getElementById('sales-pitch-form');
    if(!form||!window.AuthorDistribution)return;
    const $=id=>document.getElementById(id);
    const status=$('sales-output-status');
    const output=$('sales-output-result');
    let prepared=null;
    const inputChanged=()=>{
        prepared=null;
        output.hidden=true;
        status.textContent='Les informations ont changé : recréez la fiche avant de la transmettre.';
    };
    form.addEventListener('input',inputChanged);
    form.addEventListener('submit',event=>{
        event.preventDefault();
        try{
            prepared=window.AuthorDistribution.build(Object.fromEntries(new FormData(form)));
            $('sales-message').textContent=prepared.message;
            $('sales-sheet').textContent=prepared.sheet;
            output.hidden=false;
            status.textContent='Votre message et votre fiche sont prêts. Personnalisez-les pour chaque librairie avant l’envoi.';
        }catch(error){
            prepared=null;output.hidden=true;
            status.textContent=error?.message||'Impossible de préparer votre présentation.';
        }
    });
    async function copy(value){
        if(!prepared)return;
        try{
            await navigator.clipboard.writeText(value);
            status.textContent='Copié. Vous pouvez maintenant l’adapter et l’envoyer vous-même.';
        }catch(_){status.textContent='Copie indisponible ici : sélectionnez le texte affiché et copiez-le manuellement.';}
    }
    $('sales-copy-message').addEventListener('click',()=>copy(prepared?.message));
    $('sales-copy-sheet').addEventListener('click',()=>copy(prepared?.sheet));
    $('sales-download-sheet').addEventListener('click',()=>{
        if(!prepared)return;
        const blob=new Blob([prepared.sheet],{type:'text/plain;charset=utf-8'});
        const url=URL.createObjectURL(blob);
        const link=document.createElement('a');
        link.href=url;link.download='fiche-livre-librairie.txt';
        document.body.appendChild(link);link.click();link.remove();
        setTimeout(()=>URL.revokeObjectURL(url),12000);
        status.textContent='Votre fiche est téléchargée. Envoyez-la uniquement aux librairies choisies.';
    });
})();
