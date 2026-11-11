(() => {
'use strict';
const $=id=>document.getElementById(id);
const KEY='librairie-reader-survey-v2-token';
let token='', answers={}, position=0, busy=false;
try { token=localStorage.getItem(KEY)||''; } catch (_) {}
const source=document.body.dataset.source||'direct';
const Q=[
 {id:'age_band',title:'Dans quelle tranche d’âge vous situez-vous ?',type:'one',hint:'Pour comparer le profil des répondants à celui de 2014.',options:[['18_24','18 à 24 ans'],['25_34','25 à 34 ans'],['35_49','35 à 49 ans'],['50_64','50 à 64 ans'],['65_75','65 à 75 ans'],['76_plus','76 ans et plus']]},
 {id:'territory',title:'Vous habitez plutôt…',type:'one',hint:'Aucune adresse précise n’est demandée.',options:[['rural','Dans une commune rurale'],['small','Dans une petite ville'],['medium','Dans une ville moyenne'],['large','Dans une grande ville ou métropole'],['unknown','Je ne sais pas']]},
 {id:'read_frequency',title:'À quelle fréquence lisez-vous des livres ?',type:'one',hint:'Question comparable à celle de 2014.',options:[['daily','Tous les jours ou presque'],['weekly','Plusieurs fois par semaine'],['monthly','Quelques fois par mois'],['rarely','Rarement'],['never','Jamais']]},
 {id:'formats',title:'Quels formats de livres lisez-vous ou écoutez-vous ?',type:'many',hint:'Plusieurs réponses possibles.',options:[['paper','Livres imprimés'],['ebook','Livres numériques / ebooks'],['audio','Livres audio'],['none','Aucun actuellement']]},
 {id:'buy_volume',title:'Combien de livres achetez-vous environ par an ?',type:'one',hint:'Pour vous-même ou pour offrir, tous formats confondus.',options:[['zero','Aucun'],['one_two','1 ou 2'],['three_five','3 à 5'],['six_ten','6 à 10'],['eleven_twenty','11 à 20'],['over_twenty','Plus de 20']]},
 {id:'channels',title:'Où avez-vous acheté des livres au cours des douze derniers mois ?',type:'many',hint:'Plusieurs réponses possibles. Les catégories actualisent celles de 2014.',options:[['indie','Librairie indépendante'],['cultural_chain','Fnac, Cultura ou autre grande enseigne culturelle'],['chain_online','Site Internet d’une grande enseigne culturelle'],['amazon','Amazon'],['supermarket','Supermarché ou hypermarché'],['used_online','Plateforme de livres d’occasion'],['author','Directement auprès d’un auteur ou éditeur'],['other','Autre circuit'],['none','Je n’ai acheté aucun livre']]},
 {id:'main_channel',title:'Où réalisez-vous la majeure partie de vos achats de livres ?',type:'one',hint:'Une seule réponse, contrairement à la question sur tous vos circuits.',options:[['indie','Librairie indépendante'],['cultural_chain','Grande enseigne culturelle'],['chain_online','Site Internet d’une grande enseigne'],['amazon','Amazon'],['supermarket','Grande distribution'],['used_online','Plateforme d’occasion'],['author','Auteur ou éditeur directement'],['other','Autre circuit'],['none','Je n’achète pas de livres']]},
 {id:'ebook_owned',title:'Avez-vous déjà possédé ou acheté un livre numérique ?',type:'one',hint:'Question comparable à celle de 2014.',options:[['yes','Oui'],['no','Non']]},
 {id:'ebook_devices',title:'Sur quels appareils lisez-vous des livres numériques ?',type:'many',hint:'Plusieurs réponses possibles.',when:a=>a.ebook_owned==='yes',options:[['ereader','Liseuse'],['phone','Téléphone'],['tablet','Tablette'],['laptop','Ordinateur portable'],['desktop','Ordinateur fixe'],['none','Je n’en lis plus actuellement']]},
 {id:'ebook_advantages',title:'Quels sont selon vous les avantages du livre numérique ?',type:'many',hint:'Question comparable à celle de 2014.',options:[['portable','Transporter facilement plusieurs livres'],['quick','Obtenir immédiatement un livre'],['cheap','Un prix éventuellement inférieur au papier'],['rare_titles','Accéder à des titres rares ou indisponibles'],['search','Rechercher dans le texte et annoter'],['accessibility','Adapter la présentation du texte'],['none','Aucun avantage particulier']]},
 {id:'ebook_disadvantages',title:'Quels sont selon vous les inconvénients du livre numérique ?',type:'many',hint:'Question comparable à celle de 2014.',options:[['physical','L’absence de contact avec l’objet-livre'],['fatigue','La fatigue liée aux écrans'],['cost','Des prix jugés trop élevés'],['drm','Les restrictions d’utilisation et de partage (DRM)'],['catalog','Une offre de titres parfois limitée'],['none','Aucun inconvénient particulier']]},
 {id:'used_books',title:'Achetez-vous des livres d’occasion ?',type:'one',hint:'La seconde main est un sujet important de l’étude 2026.',options:[['often','Souvent'],['sometimes','De temps en temps'],['never','Jamais']]},
 {id:'bookshop_visits',title:'À quelle fréquence achetez-vous des livres en librairie indépendante ?',type:'one',hint:'La réponse « jamais » est aussi utile que les autres.',options:[['monthly','Au moins une fois par mois'],['some','Quelques fois par an'],['yearly','Environ une fois par an ou moins'],['never','Jamais']]},
 {id:'discovery',title:'Comment découvrez-vous de nouveaux livres ?',type:'many',hint:'Plusieurs réponses possibles.',options:[['bookseller','Conseils de libraires'],['relatives','Famille, amis et collègues'],['social','Réseaux sociaux et communautés de lecture'],['traditional_media','Presse, radio ou télévision'],['algorithm','Recommandations de plateformes'],['author','Auteurs ou éditeurs'],['library','Bibliothèques et médiathèques'],['other','Autrement']]},
 {id:'barriers',title:'Qu’est-ce qui peut limiter vos achats en librairie indépendante ?',type:'many',hint:'Sélectionnez ce qui vous concerne, même si vous fréquentez déjà ces commerces.',options:[['distance','Éloignement ou accès difficile'],['hours','Horaires peu pratiques'],['stock','Disponibilité incertaine des titres'],['price','Impression que cela coûte davantage'],['ordering','Commande ou retrait jugé compliqué'],['unaware','Services proposés mal connus'],['none','Aucun frein particulier'],['other','Autre raison']]},
 {id:'services',title:'Quels services pourraient vous inciter à acheter plus souvent en librairie indépendante ?',type:'many',max:3,hint:'Choisissez trois réponses au maximum.',options:[['stock','Consulter le stock en ligne'],['reservation','Réserver et retirer facilement'],['delivery','Livraison locale ou à domicile'],['advice','Conseils de libraires'],['events','Rencontres et animations'],['used','Livres d’occasion'],['ebooks','Livres numériques'],['accessibility','Meilleure accessibilité physique ou numérique'],['none','Aucun de ces services']]},
 {id:'waiting',title:'Quel délai seriez-vous prêt à attendre pour récupérer un livre chez un libraire ?',type:'one',hint:'Pour un livre qui n’est pas immédiatement en stock.',options:[['same_day','Je préfère le récupérer le jour même'],['two_days','Jusqu’à deux jours'],['one_week','Jusqu’à une semaine'],['depends','Cela dépend du livre'],['never','Je n’attendrais pas']]},
 {id:'idea',title:'Une idée pour faciliter l’achat de livres en librairie indépendante ?',type:'text',optional:true,hint:'Facultatif, 400 caractères maximum. Ne communiquez aucune information personnelle.'}
];
const visible=()=>Q.filter(q=>!q.when||q.when(answers));
const current=()=>visible()[position];
const escapeHtml=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const isValid=q=>q.optional|| (q.type==='many'?Array.isArray(answers[q.id])&&answers[q.id].length>0:q.type==='one'?typeof answers[q.id]==='string'&&answers[q.id]!=='':true);
const show=(which)=>{for(const id of ['survey-intro','survey-questions','survey-complete'])$(id).hidden=(id!==which);};
const error=(id,message)=>{$(id).hidden=!message;$(id).textContent=message||'';};
async function api(action,payload={}) {
 const response=await fetch('/enquete-lecteurs.php?api='+encodeURIComponent(action),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({token,...payload})});
 let data;try{data=await response.json();}catch(_){throw Error('Réponse du serveur impossible à lire.');}
 if(!response.ok)throw Error(data.error||'Une erreur est survenue.');
 return data;
}
function safeStore(value){try{if(value)localStorage.setItem(KEY,value);else localStorage.removeItem(KEY);}catch(_){}}
function render(){
 const questions=visible();if(position>=questions.length)position=questions.length-1;
 if(position<0)position=0;
 const q=current(), progress=Math.round((position/questions.length)*100);
 $('survey-step-text').textContent='Question '+(position+1)+' sur '+questions.length;
 $('survey-progress').value=progress;
 const value=answers[q.id];
 let html='<p class="survey-kicker">'+(position<11?'Lecture et habitudes d’achat':'Librairies et services')+'</p><h2 id="survey-question-title">'+escapeHtml(q.title)+'</h2><p class="survey-help">'+escapeHtml(q.hint||'')+'</p>';
 if(q.type==='text'){
   html+='<label class="survey-free-label" for="survey-answer-text">Votre réponse</label><textarea id="survey-answer-text" maxlength="400" rows="5" placeholder="Votre suggestion…">'+escapeHtml(value||'')+'</textarea><p class="survey-help">Facultatif.</p>';
 }else{
   html+='<fieldset class="survey-options"><legend class="sr-only">'+escapeHtml(q.title)+'</legend>';
   for(const [key,label] of q.options){
      const checked=q.type==='many'?Array.isArray(value)&&value.includes(key):value===key;
      html+='<label class="survey-option"><input type="'+(q.type==='many'?'checkbox':'radio')+'" name="survey-choice" value="'+escapeHtml(key)+'" '+(checked?'checked':'')+'><span>'+escapeHtml(label)+'</span></label>';
   }
   html+='</fieldset>';
 }
 $('survey-question-content').innerHTML=html;
 $('survey-question-content').addEventListener('change',handleChoice,{once:true});
 $('survey-prev').disabled=position===0;
 $('survey-next').textContent=position===questions.length-1?'Terminer et envoyer':'Continuer';
 error('survey-question-error','');
 $('survey-save-status').textContent='Réponses enregistrées progressivement';
}
function handleChoice(event){
 if(event.target.matches('input[name="survey-choice"]')) {
  const q=current(), key=event.target.value;
  if(q.type==='one')answers[q.id]=key;
  else {
   let selected=Array.isArray(answers[q.id])?answers[q.id].slice():[];
   if(event.target.checked)selected.push(key);else selected=selected.filter(v=>v!==key);
   selected=Array.from(new Set(selected));
   if(selected.includes('none')&&key==='none'&&event.target.checked)selected=['none'];
   else if(key!=='none')selected=selected.filter(v=>v!=='none');
   if(q.max&&selected.length>q.max){
      event.target.checked=false;
      error('survey-question-error','Vous pouvez sélectionner '+q.max+' réponses au maximum.');
      return;
   }
   answers[q.id]=selected;
   for(const input of $('survey-question-content').querySelectorAll('input[type="checkbox"]'))input.checked=selected.includes(input.value);
  }
  error('survey-question-error','');
  // Wait until the Next button to persist the step so errors can be shown reliably.
 }
 $('survey-question-content').addEventListener('change',handleChoice,{once:true});
}
function capture(){
 const q=current();
 if(q.type==='text')answers[q.id]=($('survey-answer-text').value||'').trim().slice(0,400);
}
async function move(forward){
 if(busy)return;
 capture();
 const questions=visible(), q=current();
 if(forward&&!isValid(q)){error('survey-question-error','Sélectionnez une réponse pour continuer.');return;}
 if(forward&&q.id==='main_channel'&&Array.isArray(answers.channels)&&answers.channels.includes('none')&&answers.main_channel!=='none'){error('survey-question-error','Vous avez indiqué n’avoir acheté aucun livre : choisissez la même réponse ici.');return;}
 busy=true;$('survey-next').disabled=true;
 $('survey-save-status').textContent='Enregistrement…';
 try{
  if(forward&&position===questions.length-1){
    await api('finish',{answers});
    show('survey-complete');return;
  }
  await api('save',{answers});
  position=forward?Math.min(position+1,visible().length-1):Math.max(0,position-1);
  render();
  $('survey-questions').scrollIntoView({block:'start',behavior:'instant'});
 }catch(e){error('survey-question-error',e.message);$('survey-save-status').textContent='Non enregistré';}
 finally{busy=false;$('survey-next').disabled=false;}
}
async function start(){
 if(busy)return;
 if(!$('survey-adult').checked||!$('survey-consent').checked){error('survey-intro-error','Merci de confirmer votre majorité et votre accord pour participer.');return;}
 busy=true;$('survey-start').disabled=true;
 try{const r=await api('start',{adult:true,consent:true,website:$('survey-website').value,source}); token=r.token;safeStore(token);answers={};position=0;show('survey-questions');render();}
 catch(e){error('survey-intro-error',e.message);}
 finally{busy=false;$('survey-start').disabled=false;}
}
async function resume(){
 if(!token)return;
 try{const r=await api('resume');answers=r.answers||{};if(r.status==='completed'){show('survey-complete');return;}position=0;
   const qs=visible();for(let i=0;i<qs.length;i++){if(!isValid(qs[i])){position=i;break;}position=i;}
   show('survey-questions');render();
 }catch(e){safeStore('');token='';$('survey-resume').hidden=true;error('survey-intro-error','La reprise est indisponible : vous pouvez commencer une nouvelle réponse.');}
}
async function erase(){
 if(!token)return;
 if(!confirm('Supprimer définitivement toutes les réponses associées à ce navigateur ?'))return;
 try{await api('erase');safeStore('');token='';answers={};position=0;show('survey-intro');$('survey-resume').hidden=true;error('survey-intro-error','Votre participation a été supprimée.');}
 catch(e){alert(e.message);}
}
$('survey-start').addEventListener('click',start);
$('survey-resume').addEventListener('click',resume);
$('survey-next').addEventListener('click',()=>move(true));
$('survey-prev').addEventListener('click',()=>move(false));
$('survey-erase').addEventListener('click',erase);
$('survey-delete-completed').addEventListener('click',erase);
if(token){$('survey-resume').hidden=false;}
})();
