/* Simulation locale, sans requête réseau ni stockage et sans arrondi intermédiaire. */
(function(root,factory){
    const api=factory();
    if(typeof module!=='undefined'&&module.exports)module.exports=api;
    if(root)root.AmazonLocalCalculator=api;
})(typeof window!=='undefined'?window:null,function(){
    'use strict';
    function parse(value,label,min,max){
        if(value===null||value===undefined||String(value).trim()==='')throw new Error('Renseignez '+label+'.');
        const n=Number(value);
        if(!Number.isFinite(n)||n<min||n>max)throw new Error('Vérifiez la valeur de '+label+'.');
        return n;
    }
    function estimate(values){
        const publicPrice=parse(values.publicPrice,'le prix TTC',0.01,250);
        const kdpPrint=parse(values.kdpPrint,"le coût d'impression KDP",0,250);
        const localPrint=parse(values.localPrint,"le coût d'impression local HT",0,250);
        const discount=parse(values.discount,'la remise libraire',0,90);
        const priceExVat=publicPrice/1.055;
        const kdpRate=priceExVat>=9.99?0.60:0.50;
        const commission=publicPrice*0.03;
        return {
            priceExVat,kdpRate,commission,discount,
            kdp:kdpRate*priceExVat-kdpPrint,
            direct:priceExVat-localPrint-commission,
            bookstore:priceExVat*(1-discount/100)-localPrint
        };
    }
    return {estimate};
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
            byId('compare-kdp-rule').textContent='Taux KDP estimé : '+Math.round(data.kdpRate*100)+' % du prix catalogue HT.';
            byId('compare-direct-rule').textContent='Commission de 3 % du TTC : '+euro.format(data.commission)+'.';
            byId('compare-store-rule').textContent='Remise négociée : '+data.discount+' % du prix HT.';
            result.hidden=false;
            status.textContent='Estimation actualisée avec les montants que vous avez saisis.';
        }catch(error){
            result.hidden=true;
            status.textContent=error.message||'Vérifiez les montants saisis.';
        }
    }
    form.addEventListener('input',update);
    form.addEventListener('change',update);
    form.addEventListener('submit',event=>event.preventDefault());
    update();
})();
