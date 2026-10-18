/* Explicit local-only project drafts. Uploaded files are never persisted. */
(function (root,factory) {
    const api=factory();
    if (typeof module !== 'undefined' && module.exports) module.exports=api;
    if (root) root.AuthorDraft=api;
})(typeof window !== 'undefined' ? window : null,function() {
    'use strict';
    const VERSION=1, DAYS=30, MAX_SIZE=180000;
    function collect(form) {
        const fields=[...form.querySelectorAll('input[name],select[name],textarea[name]')]
            .filter(el=>el.name && !['file','hidden','submit','button','password'].includes(el.type) && !/(^|_)(confirmed|consent|agree|accept|attest)/i.test(el.name));
        return {version:VERSION, savedAt:new Date().toISOString(), fields:fields.map((el,i)=>({
            index:i,name:el.name,type:el.type,
            value:el.type==='checkbox'||el.type==='radio'?String(el.value):String(el.value||''),
            checked:el.type==='checkbox'||el.type==='radio'?Boolean(el.checked):null,
        }))};
    }
    function valid(data) {
        if(!data||data.version!==VERSION||!Array.isArray(data.fields)
            ||data.fields.length>250||typeof data.savedAt!=='string')return false;
        const stamp=Date.parse(data.savedAt);
        if(!Number.isFinite(stamp)||stamp>Date.now()+3600000||Date.now()-stamp>DAYS*86400000)return false;
        if(JSON.stringify(data).length>MAX_SIZE)return false;
        return data.fields.every(f=>Number.isInteger(f.index)&&f.index>=0&&f.index<250
          &&typeof f.name==='string'&&f.name.length<120&&typeof f.value==='string'
          &&f.value.length<20000&&['text','email','number','url','textarea','select-one',
            'select-multiple','checkbox','radio','search','date','tel'].includes(f.type));
    }
    function apply(form,data) {
        if(!valid(data))throw new Error('Brouillon absent, expiré ou incompatible.');
        const fields=[...form.querySelectorAll('input[name],select[name],textarea[name]')]
            .filter(el=>el.name&&!['file','hidden','submit','button','password'].includes(el.type) && !/(^|_)(confirmed|consent|agree|accept|attest)/i.test(el.name));
        let count=0;
        for(const entry of data.fields) {
            const el=fields[entry.index];
            if(!el||el.name!==entry.name||el.type!==entry.type
               ||/(^|_)(confirmed|consent|agree|accept|attest)/i.test(entry.name))continue;
            if(el.type==='checkbox'||el.type==='radio')el.checked=Boolean(entry.checked);
            else el.value=entry.value;
            el.dispatchEvent(new Event('input',{bubbles:true}));
            el.dispatchEvent(new Event('change',{bubbles:true}));
            count++;
        }
        return count;
    }
    return {collect,valid,apply,MAX_SIZE};
});
(function(){
    'use strict';
    if (typeof document === 'undefined') return; // pure helpers stay testable under Node
    const form=document.getElementById('autoedition-form');
    const status=document.getElementById('draft-status');
    if(!form||!status||!window.AuthorDraft)return;
    const KEY='librairie-universelle:manuscrit-draft:v1';
    const tell=message=>{status.textContent=message;};
    const read=()=>{const s=localStorage.getItem(KEY);return s?JSON.parse(s):null;};
    const restore=data=>{
        const n=window.AuthorDraft.apply(form,data);
        tell(n+' champs restaurés. Pour continuer, rechoisissez les fichiers (manuscrit, images, PDF).');
        return n;
    };
    document.getElementById('draft-save')?.addEventListener('click',()=>{
        try {
            const data=window.AuthorDraft.collect(form);
            if(JSON.stringify(data).length>window.AuthorDraft.MAX_SIZE)throw Error('Brouillon trop volumineux.');
            localStorage.setItem(KEY,JSON.stringify(data));
            tell('Brouillon sauvegardé localement le '+new Date().toLocaleString('fr-FR')+'. Fichiers non conservés.');
        }catch(e){tell('Enregistrement impossible sur ce navigateur : '+e.message);}
    });
    document.getElementById('draft-restore')?.addEventListener('click',()=>{
        try{restore(read());}catch(e){tell('Reprise impossible : '+e.message);}
    });
    document.getElementById('draft-export')?.addEventListener('click',()=>{
        const data=window.AuthorDraft.collect(form);
        const text=JSON.stringify(data,null,2);
        if(text.length>window.AuthorDraft.MAX_SIZE){tell('Brouillon trop volumineux.');return;}
        const blob=new Blob([text],{type:'application/json'});
        const url=URL.createObjectURL(blob);
        const link=document.createElement('a');link.href=url;link.download='librairie-brouillon-'+new Date().toISOString().slice(0,10)+'.json';
        document.body.appendChild(link);link.click();link.remove();
        setTimeout(()=>URL.revokeObjectURL(url),15000);
        tell('Brouillon exporté sans manuscrit ni images. Conservez le JSON dans un endroit privé.');
    });
    document.getElementById('draft-import')?.addEventListener('change',async event=>{
        const f=event.target.files?.[0];
        if(!f)return;
        try {
            if(f.size>window.AuthorDraft.MAX_SIZE)throw Error('Fichier JSON trop volumineux.');
            restore(JSON.parse(await f.text()));
        }catch(e){tell('Import refusé : '+e.message);}
        event.target.value='';
    });
    document.getElementById('draft-clear')?.addEventListener('click',()=>{
        try{localStorage.removeItem(KEY);tell('Brouillon local effacé. Les champs visibles restent inchangés.');}
        catch(e){tell('Effacement indisponible : '+e.message);}
    });
})();
