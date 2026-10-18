'use strict';
const assert=require('node:assert/strict');
const d=require('../public/assets/js/author-draft.js');
const fake=(nodes)=>({querySelectorAll:()=>nodes});
const nodes=[{name:'author_name',type:'text',value:'Marie',dispatchEvent(){}},
 {name:'email',type:'email',value:'marie@example.org',dispatchEvent(){}},
 {name:'ebook',type:'file',value:'C:\\fiction.docx',dispatchEvent(){}},
 {name:'rights_confirmed',type:'checkbox',value:'1',checked:true,dispatchEvent(){}},
 {name:'csrf_token',type:'hidden',value:'secret',dispatchEvent(){}}];
const state=d.collect(fake(nodes));
assert.equal(state.fields.length,3);
assert.equal(JSON.stringify(state).includes('fiction.docx'),false);
assert.equal(JSON.stringify(state).includes('secret'),false);
assert.equal(d.valid(state),true);
nodes[0].value='';
d.apply(fake(nodes),state);
assert.equal(nodes[0].value,'Marie');
assert.equal(nodes[3].checked,true);
assert.equal(d.valid({...state, savedAt:'2000-01-01T00:00:00Z'}),false);
console.log('Brouillon local sans fichiers: OK');
