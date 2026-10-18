(() => {
'use strict';
const bar=document.getElementById('work-access-filters');
const wrap=document.getElementById('work-offer-list');
if(!bar||!wrap)return;
const cards=[...wrap.querySelectorAll('.offer-card[data-mode]')];
const sets={all:null,free:['free_download','read_online'],borrow:['borrow'],
digital:['free_download','read_online','ebook'],paper:['new','used']};
const buttons=[...bar.querySelectorAll('button[data-mode]')];
const status=document.getElementById('work-filter-status');
function activate(mode){
let count=0;
for(const card of cards){const show=!sets[mode]||sets[mode].includes(card.dataset.mode);card.hidden=!show;if(show)count++;}
for(const button of buttons)button.setAttribute('aria-pressed',String(button.dataset.mode===mode));
status.textContent=count+' sur '+cards.length+' accès affichés';
}
for(const button of buttons){
if(sets[button.dataset.mode]&&!cards.some(c=>sets[button.dataset.mode].includes(c.dataset.mode)))button.hidden=true;
button.addEventListener('click',()=>activate(button.dataset.mode));
}
bar.hidden=false;activate('all');
})();
