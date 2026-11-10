'use strict';
/**
 * Capture et vérification géométrique du parcours papier en navigateur local.
 * Aucun manuscrit envoyé et aucune commande passée.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const {spawn,spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'..');
const out=path.join(root,'test-artifacts','paper-provider');
const sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms));

function browserExecutable(){
  for(const candidate of [process.env.CHROME_BIN,'google-chrome','google-chrome-stable','chromium','chromium-browser'].filter(Boolean)){
    const resolved=candidate.includes('/')?candidate:spawnSync('which',[candidate],{encoding:'utf8'}).stdout.trim();
    if(resolved&&fs.existsSync(resolved))return resolved;
  }
  throw Error('Chromium/Chrome introuvable : contrôle navigateur non réalisé.');
}
async function waitFor(fn,label,limit=12000){
  const start=Date.now();
  while(Date.now()-start<limit){
    const result=await Promise.resolve().then(fn).catch(()=>null);
    if(result)return result;
    await sleep(150);
  }
  throw Error('Délai dépassé : '+label);
}
let server,chrome,ws,profile,work,sequence=0;
const pending=new Map();
function send(method,params={}){
  const id=++sequence;
  return new Promise((resolve,reject)=>{
    const timer=setTimeout(()=>{pending.delete(id);reject(Error('Commande Chrome expirée : '+method));},9000);
    pending.set(id,{resolve:v=>{clearTimeout(timer);resolve(v);},reject:e=>{clearTimeout(timer);reject(e);}});
    ws.send(JSON.stringify({id,method,params}));
  });
}
async function evaluate(expression){
  const data=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});
  if(data.exceptionDetails)throw Error('Erreur JavaScript : '+data.exceptionDetails.text);
  return data.result?.value;
}
async function run(){
  assert.equal(typeof WebSocket,'function','Node.js avec WebSocket requis.');
  fs.mkdirSync(out,{recursive:true});
  const binary=browserExecutable();
  work=fs.mkdtempSync(path.join(os.tmpdir(),'librairie-test-'));
  profile=fs.mkdtempSync(path.join(os.tmpdir(),'librairie-chrome-'));
  const site='http://127.0.0.1:8239/autoedition.php?partie=paper';
  server=spawn('php',['-S','127.0.0.1:8239','-t','public'],{
    cwd:root,stdio:'ignore',
    env:{...process.env,APP_ENV:'testing',SELF_PUBLISHING_ENABLED:'1',
      DATABASE_PATH:path.join(work,'app.sqlite'),STORAGE_PATH:path.join(work,'storage')}
  });
  await waitFor(async()=>{const r=await fetch(site);return r.ok;},'serveur PHP');
  chrome=spawn(binary,['--headless=new','--no-sandbox','--disable-gpu',
    '--disable-dev-shm-usage','--remote-allow-origins=*','--remote-debugging-port=0',
    '--user-data-dir='+profile,'about:blank'],{stdio:'ignore'});
  const file=path.join(profile,'DevToolsActivePort');
  await waitFor(()=>fs.existsSync(file),'lancement de Chromium');
  const port=Number(fs.readFileSync(file,'utf8').split('\n')[0]);
  const targets=await (await fetch('http://127.0.0.1:'+port+'/json/list')).json();
  const page=targets.find(item=>item.type==='page');
  assert(page,'Onglet Chrome indisponible');
  ws=new WebSocket(page.webSocketDebuggerUrl);
  ws.addEventListener('message',event=>{
    let data;try{data=JSON.parse(String(event.data));}catch{return;}
    const task=pending.get(data.id);
    if(!task)return;
    pending.delete(data.id);
    if(data.error)task.reject(Error(data.error.message||'Erreur CDP'));
    else task.resolve(data.result);
  });
  await new Promise((resolve,reject)=>{
    ws.addEventListener('open',resolve,{once:true});
    ws.addEventListener('error',reject,{once:true});
  });
  await send('Page.enable');
  await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride',{width:375,height:900,deviceScaleFactor:1,mobile:true});
  await send('Page.navigate',{url:site});
  await waitFor(async()=>evaluate(
    "Boolean(document.querySelector('#paper-wizard') && document.querySelector('#local-printers-title') && document.querySelector('#bookelis-path-title') && document.querySelector('#print-paper-stock'))"
  ),'initialisation de l’assistant',18000);
  const describe="(()=>{"+
    "const a=document.querySelector('.paper-provider-group-local');"+
    "const b=document.querySelector('.paper-provider-group-pod');"+
    "const box=e=>{const r=e.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width}};"+
    "const visible=e=>!!e&&getComputedStyle(e).display!=='none'&&getComputedStyle(e).visibility!=='hidden'&&e.getClientRects().length>0;"+
    "const radios=[...document.querySelectorAll('input[name=\"print_provider\"]')];"+
    "return {viewport:document.documentElement.clientWidth,hscroll:document.documentElement.scrollWidth-document.documentElement.clientWidth,"+
    "wizard:visible(document.querySelector('#paper-wizard')),local:visible(a),pod:visible(b),"+
    "localBox:box(a),podBox:box(b),cards:radios.length,duplicateSelector:!!document.querySelector('#wizard-provider'),"+
    "offenders:[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();return r.right>document.documentElement.clientWidth+6&&r.left>=0&&getComputedStyle(e).display!=='none'&&getComputedStyle(e).visibility!=='hidden'}).sort((x,y)=>y.getBoundingClientRect().right-x.getBoundingClientRect().right).slice(0,15).map(e=>({tag:e.tagName.toLowerCase(),id:e.id,cls:String(e.className).slice(0,90),left:Math.round(e.getBoundingClientRect().left),right:Math.round(e.getBoundingClientRect().right),width:Math.round(e.getBoundingClientRect().width)}))};"+
  "})()";
  for(const width of [320,375,430,1280]){
    await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width<800});
    await sleep(200);
    const state=await evaluate(describe);
    if(width===320)console.log('DIAGNOSTIC_MOBILE='+JSON.stringify(state));
    await evaluate("document.querySelector('.paper-provider-paths').scrollIntoView({block:'start',behavior:'instant'})");
    await sleep(80);
    if([320,430,1280].includes(width)){
      const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
      fs.writeFileSync(path.join(out,'selection-'+width+'.png'),Buffer.from(shot.data,'base64'));
    }
    assert(state.wizard&&state.local&&state.pod,'Les deux parcours doivent être visibles à '+width+' px');
    assert.equal(state.cards,2,'Un choix radio unique par prestataire est attendu.');
    assert.equal(state.duplicateSelector,false,'Sélecteur redondant détecté.');
    assert(state.hscroll<=3,'Débordement horizontal global à '+width+' px : '+state.hscroll);
    assert(state.localBox.left>=-3&&state.podBox.left>=-3&&
      state.localBox.right<=width+3&&state.podBox.right<=width+3,
      'Cartes hors largeur à '+width+' px : '+JSON.stringify(state));
    if(width<800)assert(state.localBox.bottom<=state.podBox.top+4,'Groupes mobiles non empilés');
    else assert(Math.abs(state.localBox.top-state.podBox.top)<5,'Groupes desktop non alignés');
    console.log('UI '+width+' px : cartes visibles et sans débordement');
  }
  const bookelis=await evaluate("(()=>{const radio=document.querySelector('input[name=\"print_provider\"][value=\"bookelis\"]');"+
    "radio.click();const options=[...document.querySelectorAll('#print-paper-stock option[data-bookelis-paper]')];"+
    "return {checked:radio.checked,options:options.length,enabled:options.some(x=>!x.disabled),"+
    "corepEnabled:!document.querySelector('input[name=\"print_provider\"][value=\"corep_metz\"]').disabled}})()");
  assert(bookelis.checked&&bookelis.options>=3&&bookelis.enabled&&bookelis.corepEnabled,
    'Sélection Bookelis ou options papier incorrectes.');
  console.log('Sélection Bookelis : options papier accessibles, Corep préservé');

  // Cette vérification se limite à présenter l'étape Couverture après injection
  // d'un total de pages de test ; elle ne simule ni un PDF interior ni un upload réel.
  const shown=await evaluate("(()=>{"+
    "const count=document.querySelector('#print-estimate-pages');"+
    "count.value='120';count.dispatchEvent(new Event('input',{bubbles:true}));"+
    "document.querySelectorAll('.paper-wizard-screen').forEach(e=>e.hidden=true);"+
    "const screen=document.querySelector('#paper-wizard-screen-2');if(screen)screen.hidden=false;"+
    "const section=document.querySelector('#paper-bookelis-cover-step');if(section)section.hidden=false;"+
    "return {screen:!!screen,section:!!section,pages:document.querySelector('#bookelis-guide-pages')?.textContent,"+
    "template:!!document.querySelector('#bookelis-template-pdf'),"+
    "exportDisabled:document.querySelector('#spread-download')?.disabled}})()");
  assert(shown.screen&&shown.section&&shown.template&&shown.exportDisabled,
    'Couverture Bookelis : import du gabarit et verrouillage de l’export requis.');
  assert.equal(shown.pages,'120 pages','Aide Bookelis doit afficher la pagination calculée.');
  for(const width of [320,430,1280]){
    await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width<800});
    await sleep(150);
    const state=await evaluate("(()=>{const box=document.querySelector('.bookelis-guide');"+
      "const file=document.querySelector('.bookelis-template-import');"+
      "const visible=e=>e&&getComputedStyle(e).display!=='none'&&e.getClientRects().length>0;"+
      "const a=box.getBoundingClientRect(),b=file.getBoundingClientRect();"+
      "return {hscroll:document.documentElement.scrollWidth-document.documentElement.clientWidth,"+
      "guide:visible(box),upload:visible(file),guideLeft:a.left,guideRight:a.right,"+
      "uploadLeft:b.left,uploadRight:b.right,disabled:document.querySelector('#spread-download').disabled}})()");
    await evaluate("document.querySelector('.bookelis-guide').scrollIntoView({block:'start',behavior:'instant'})");
    await sleep(70);
    const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
    fs.writeFileSync(path.join(out,'bookelis-guide-'+width+'.png'),Buffer.from(shot.data,'base64'));
    assert(state.guide&&state.upload&&state.disabled,
      'Guide et import obligatoire visibles, export verrouillé à '+width+' px.');
    assert(state.hscroll<=3&&state.guideLeft>=-3&&state.guideRight<=width+3&&
      state.uploadLeft>=-3&&state.uploadRight<=width+3,
      'Débordement du guide Bookelis à '+width+' px : '+JSON.stringify(state));
    console.log('Bookelis '+width+' px : guide et import accessibles, export verrouillé, sans débordement.');
  }
}
async function main(){
  try{await run();}finally{
    try{ws?.close();}catch{}
    for(const task of pending.values())task.reject(Error('Fermeture Chrome'));
    pending.clear();
    try{chrome?.kill('SIGTERM');}catch{}
    try{server?.kill('SIGTERM');}catch{}
    if(profile)try{fs.rmSync(profile,{recursive:true,force:true});}catch{}
    if(work)try{fs.rmSync(work,{recursive:true,force:true});}catch{}
  }
}
main().catch(error=>{console.error('[UI Chromium]',error.stack||error.message);process.exitCode=1;});
