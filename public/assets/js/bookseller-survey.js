(() => {
'use strict';
const $=id=>document.getElementById(id);
const KEY='librairie-bookseller-survey-v2-token';
let token='',answers={},position=0,busy=false;
try{token=localStorage.getItem(KEY)||'';}catch(_){}
const source=document.body.dataset.source||'direct';
const Q=[
{id:'role',title:'Quel est votre rôle dans la librairie ?',type:'one',hint:'Il n’est pas nécessaire d’indiquer votre nom ou celui de votre établissement.',options:[['owner','Propriétaire ou gérant·e'],['manager','Responsable de magasin'],['bookseller','Libraire salarié·e'],['other','Autre fonction']]},
{id:'location',title:'Votre librairie se situe plutôt…',type:'one',hint:'Aucune adresse précise n’est demandée.',options:[['rural','Dans une commune rurale'],['small','Dans une petite ville'],['medium','Dans une ville moyenne'],['city','Dans une grande ville'],['unsure','Difficile à classer']]},
{id:'staff',title:'Combien de personnes travaillent habituellement dans cette librairie ?',type:'one',hint:'À titre indicatif, sans chiffre d’affaires ni identité.',options:[['one','Une personne'],['two_four','2 à 4 personnes'],['five_nine','5 à 9 personnes'],['ten_plus','10 personnes ou plus'],['prefer_not','Je préfère ne pas répondre']]},
{id:'audience',title:'Quels publics fréquentent régulièrement votre librairie ?',type:'many',hint:'Plusieurs réponses possibles.',options:[['local','Habitants du quartier ou de la commune'],['families','Familles et enfants'],['students','Étudiants'],['tourists','Touristes et visiteurs'],['schools','Établissements scolaires ou collectivités'],['online','Clients principalement à distance'],['other','Autres publics']]},
{id:'challenges',title:'Quelles sont aujourd’hui vos principales difficultés ?',type:'many',max:3,hint:'Sélectionnez trois réponses au maximum.',options:[['charges','Loyers et charges'],['margin','Marges commerciales'],['cash','Trésorerie'],['stocks','Gestion des stocks et retours'],['workload','Temps de travail disponible'],['visibility','Visibilité de la librairie'],['logistics','Livraisons et logistique'],['competition','Concurrence d’autres circuits de vente'],['other','Autre difficulté']]},
{id:'communication',title:'Comment faites-vous connaître la librairie et ses actualités ?',type:'many',hint:'Plusieurs réponses possibles.',options:[['word_of_mouth','Bouche-à-oreille et recommandations'],['website','Site Internet'],['newsletter','Newsletter'],['social','Réseaux sociaux'],['events','Rencontres, ateliers et événements'],['posters','Affichage local'],['local_press','Presse ou médias locaux'],['none','Pas de communication dédiée']]},
{id:'stock_online',title:'Vos clients peuvent-ils vérifier en ligne la disponibilité d’un livre ?',type:'one',hint:'Les solutions mutualisées comptent aussi.',options:[['live','Oui, avec un stock actualisé'],['partial','Partiellement ou sans mise à jour automatique'],['no','Non'],['unknown','Je ne sais pas']]},
{id:'online_order',title:'Comment vos clients peuvent-ils réserver ou commander un livre à distance ?',type:'one',hint:'Choisissez le principal parcours.',options:[['own','Par notre site ou notre formulaire'],['shared','Par un portail collectif de librairies'],['platform','Par une autre plateforme ou application'],['none','Pas de parcours en ligne ; téléphone ou boutique seulement']]},
{id:'services',title:'Quels services proposez-vous actuellement ?',type:'many',hint:'Plusieurs réponses possibles.',options:[['reservation','Réservation ou retrait de commandes'],['delivery','Livraison'],['advice','Conseil personnalisé et recommandations'],['events','Événements et rencontres'],['used','Livres d’occasion'],['schools','Partenariats scolaires ou associatifs'],['ebooks','Livres numériques'],['none','Aucun de ces services']]},
{id:'client_demands',title:'Quelles demandes reviennent le plus souvent de la part de vos clients ?',type:'many',hint:'Plusieurs réponses possibles.',options:[['availability','Connaître la disponibilité d’un titre'],['quick_pickup','Réserver et récupérer rapidement'],['online_buy','Acheter à distance'],['recommendations','Obtenir des conseils personnalisés'],['used','Acheter des livres d’occasion'],['events','Participer à des rencontres'],['accessibility','Accéder plus facilement aux services'],['other','Autres demandes']]},
{id:'priority',title:'Quelles améliorations seraient les plus utiles à votre librairie ?',type:'many',max:3,hint:'Trois réponses au maximum, en tenant compte de vos moyens.',options:[['stock','Rendre nos stocks plus visibles'],['booking','Simplifier les réservations'],['training','Se former à des outils utiles'],['communication','Améliorer notre communication'],['partnerships','Développer des partenariats locaux'],['culture','Développer les activités culturelles'],['logistics','Mutualiser transport ou logistique'],['access','Améliorer l’accessibilité'],['other','Autre amélioration']]},
{id:'cooperation',title:'Seriez-vous intéressé·e par davantage de mutualisation entre librairies ?',type:'one',hint:'Pour les outils, les services ou la communication.',options:[['active','Oui, nous participons déjà à une initiative'],['interested','Oui, nous aimerions en étudier la possibilité'],['no','Non, ce n’est pas prioritaire pour nous'],['unknown','Je ne sais pas']]},
{id:'ebook_sales',title:'Votre librairie commercialise-t-elle des livres numériques ?',type:'one',hint:'Question volontairement placée vers la fin ; elle reprend le sujet de l’enquête libraires de 2014.',options:[['yes','Oui'],['no','Non'],['testing','Nous y réfléchissons ou l’expérimentons']]},
{id:'ebook_future',title:'Quelle place le livre numérique pourrait-il prendre dans votre activité ?',type:'one',hint:'Il n’y a pas de réponse attendue.',options:[['important','Une activité importante'],['possible','Un service complémentaire'],['limited','Un intérêt limité ou nul'],['unknown','Impossible à dire aujourd’hui']]},
{id:'paper_future',title:'Comment envisagez-vous les rapports entre livres papier et numériques ?',type:'one',hint:'Pour mettre vos réponses en perspective avec celles de 2014.',options:[['paper','Le papier restera largement dominant'],['digital','Le numérique remplacera progressivement le papier'],['complementary','Les deux formats resteront complémentaires'],['unknown','Je ne sais pas']]},
{id:'one_idea',title:'Une solution concrète que vous aimeriez voir se développer ?',type:'text',optional:true,hint:'Facultatif · 400 caractères maximum · Merci de ne pas indiquer de données personnelles.'}
];
const current=()=>Q[position];
function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function show(id){for(const name of ['survey-intro','survey-questions','survey-complete'])$(name).hidden=name!==id;}
function err(id,msg){$(id).hidden=!msg;$(id).textContent=msg||'';}
function safeStore(t){try{if(t)localStorage.setItem(KEY,t);else localStorage.removeItem(KEY);}catch(_){}}
async function api(action,extra={}){
 const response=await fetch('/enquete-libraires.php?api='+encodeURIComponent(action),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({token,...extra})});
 let data;try{data=await response.json();}catch(_){throw Error('Impossible de lire la réponse du serveur.');}
 if(!response.ok)throw Error(data.error||'Une erreur est survenue.');
 return data;
}
function valid(q){if(q.optional)return true;const a=answers[q.id];return q.type==='many'?Array.isArray(a)&&a.length>0:q.type==='one'?typeof a==='string'&&a.length>0:true;}
function render(){
 const q=current();
 $('survey-step-text').textContent='Question '+(position+1)+' sur '+Q.length;
 $('survey-progress').value=Math.round(position/Q.length*100);
 let html='<p class="survey-kicker">'+(position<11?'Votre librairie et vos publics':'Enjeux et perspectives')+'</p><h2 id="survey-question-title">'+esc(q.title)+'</h2><p class="survey-help">'+esc(q.hint||'')+'</p>';
 if(q.type==='text'){
   html+='<label for="survey-answer-text" class="survey-free-label">Votre idée</label><textarea id="survey-answer-text" maxlength="400" rows="5">'+esc(answers[q.id]||'')+'</textarea><p class="survey-help">Cette question est facultative.</p>';
 }else{
   html+='<fieldset class="survey-options"><legend class="sr-only">'+esc(q.title)+'</legend>';
   for(const [k,label] of q.options){
     const yes=q.type==='one'?answers[q.id]===k:Array.isArray(answers[q.id])&&answers[q.id].includes(k);
     html+='<label class="survey-option"><input type="'+(q.type==='one'?'radio':'checkbox')+'" name="survey-choice" value="'+esc(k)+'"'+(yes?' checked':'')+'><span>'+esc(label)+'</span></label>';
   }
   html+='</fieldset>';
 }
 $('survey-question-content').innerHTML=html;
 $('survey-prev').disabled=position===0;
 $('survey-next').textContent=position===Q.length-1?'Terminer et envoyer':'Continuer';
 $('survey-save-status').textContent='Réponses enregistrées progressivement';
 err('survey-question-error','');
}
$('survey-question-content').addEventListener('change',event=>{
 if(!event.target.matches('input[name="survey-choice"]'))return;
 const q=current(),v=event.target.value;
 if(q.type==='one')answers[q.id]=v;
 else{
   let selected=Array.isArray(answers[q.id])?answers[q.id].slice():[];
   if(event.target.checked)selected.push(v);else selected=selected.filter(x=>x!==v);
   selected=[...new Set(selected)];
   if(v==='none'&&event.target.checked)selected=['none'];else if(v!=='none')selected=selected.filter(x=>x!=='none');
   if(q.max&&selected.length>q.max){event.target.checked=false;err('survey-question-error','Trois réponses au maximum.');return;}
   answers[q.id]=selected;
   $('survey-question-content').querySelectorAll('input[type="checkbox"]').forEach(el=>{el.checked=selected.includes(el.value);});
 }
 err('survey-question-error','');
});
function capture(){if(current().type==='text')answers[current().id]=($('survey-answer-text').value||'').trim().slice(0,400);}
async function move(forward){
 if(busy)return;capture();const q=current();
 if(forward&&!valid(q)){err('survey-question-error','Choisissez une réponse pour continuer.');return;}
 busy=true;$('survey-next').disabled=true;$('survey-save-status').textContent='Sauvegarde…';
 try{
   if(forward&&position===Q.length-1){
      await api('finish',{answers});show('survey-complete');return;
   }
   await api('save',{answers});
   position=forward?Math.min(Q.length-1,position+1):Math.max(0,position-1);
   render();$('survey-questions').scrollIntoView({block:'start',behavior:'instant'});
 }catch(e){err('survey-question-error',e.message);$('survey-save-status').textContent='Sauvegarde non confirmée';}
 finally{busy=false;$('survey-next').disabled=false;}
}
async function start(){
 if(busy)return;
 if(!$('survey-consent').checked){err('survey-intro-error','Merci de confirmer votre accord.');return;}
 busy=true;$('survey-start').disabled=true;
 try{
   const data=await api('start',{consent:true,source,website:$('survey-website').value});
   token=data.token;safeStore(token);answers={};position=0;show('survey-questions');render();
 }catch(e){err('survey-intro-error',e.message);}
 finally{busy=false;$('survey-start').disabled=false;}
}
async function resume(){
 if(!token)return;
 try{
   const r=await api('resume');answers=r.answers||{};
   if(r.status==='completed'){show('survey-complete');return;}
   position=0;
   for(let i=0;i<Q.length;i++){if(!valid(Q[i])){position=i;break;}position=i;}
   show('survey-questions');render();
 }catch(e){token='';safeStore('');$('survey-resume').hidden=true;err('survey-intro-error','Votre ancienne réponse n’est plus accessible : vous pouvez en commencer une nouvelle.');}
}
async function erase(){
 if(!token||!confirm('Supprimer définitivement votre participation ?'))return;
 try{await api('erase');token='';safeStore('');answers={};position=0;show('survey-intro');$('survey-resume').hidden=true;err('survey-intro-error','Votre réponse a été supprimée.');}
 catch(e){alert(e.message);}
}
$('survey-start').addEventListener('click',start);
$('survey-resume').addEventListener('click',resume);
$('survey-next').addEventListener('click',()=>move(true));
$('survey-prev').addEventListener('click',()=>move(false));
$('survey-erase').addEventListener('click',erase);
$('survey-delete-completed').addEventListener('click',erase);
if(token)$('survey-resume').hidden=false;
})();
