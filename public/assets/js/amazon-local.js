/* Chiffres fournis par le visiteur, calcul local sans réseau ni stockage. */
(function(root,factory){
    const api=factory();
    if(typeof module!=='undefined' && module.exports) module.exports=api;
    if(root) root.AmazonLocalCalculator=api;
})(typeof window!=='undefined'?window:null,function(){
    'use strict';
    function numberField(value,label,minimum,maximum){
        if(value===null || value===undefined || String(value).trim()==='')throw new Error('Renseignez '+label+'.');
        const n=Number(value);
        if(!Number.isFinite(n)||n<minimum||n>maximum)throw new Error('Valeur incorrecte pour '+label+'.');
        return n;
    }
    function estimate(values){
        const publicPrice=numberField(values.publicPrice,'le prix public TTC',0.01,250);
        const kdpPrint=numberField(values.kdpPrint,"le coût d'impression KDP",0,250);
        const localPrint=numberField(values.localPrint,'le coût local HT',0,250);
        const discount=numberField(values.discount,'la remise libraire',0,90);
        const beforeTax=publicPrice/1.055;
        const kdpRate=beforeTax>=9.99?0.60:0.50;
        const commission=publicPrice*0.03;
        return {
            beforeTax:beforeTax,
            kdpRate:kdpRate,
            commission:commission,
            kdp:kdpRate*beforeTax-kdpPrint,
            direct:beforeTax-localPrint-commission,
            bookstore:beforeTax*(1-discount/100)-localPrint,
            discount:discount
        };
    }
    return {estimate:estimate};
});
(function(){
    'use strict';
    if(typeof document==='undefined')return;
    const form=document.getElementById('amazon-local-calculator');
    if(!form||!window.AmazonLocalCalculator)return;
    const result=document.getElementById('amazon-comparison-results');
    const status=document.getElementById('amazon-comparison-status');
    const euro=new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR',maximumFractionDigits:2});
    const byId=(name)=>document.getElementById(name);
    function update(){
        try{
            const values=Object.fromEntries(new FormData(form));
            const data=window.AmazonLocalCalculator.estimate(values);
            byId('compare-kdp-result').textContent=euro.format(data.kdp);
            byId('compare-direct-result').textContent=euro.format(data.direct);
            byId('compare-store-result').textContent=euro.format(data.bookstore);
            byId('compare-kdp-rule').textContent='Taux appliqué : '+Math.round(data.kdpRate*100)+' % du prix catalogue HT.';
            byId('compare-direct-rule').textContent='Commission de 3 % du TTC : '+euro.format(data.commission)+'.';
            byId('compare-store-rule').textContent='Remise saisie : '+data.discount+' % du prix HT.';
            result.hidden=false;
            status.textContent='Comparaison indicative mise à jour avec vos montants.';
        }catch(error){
            result.hidden=true;
            status.textContent=error.message||'Veuillez vérifier vos montants.';
        }
    }
    form.addEventListener('input',update);
    form.addEventListener('change',update);
    form.addEventListener('submit',event=>event.preventDefault());
    update();
})();
