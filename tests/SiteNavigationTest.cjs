'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

class FakeClassList {
    constructor() { this.values = new Set(); }
    add(value) { this.values.add(value); }
    remove(value) { this.values.delete(value); }
    contains(value) { return this.values.has(value); }
}
class FakeElement {
    constructor(name) { this.name=name; this.events={}; this.attributes={}; this.classList=new FakeClassList(); this.open=false; this.focused=false; }
    addEventListener(type, fn) { this.events[type]=fn; }
    dispatch(type, event={}) { this.events[type]?.(event); }
    setAttribute(name, value) { this.attributes[name]=value; }
    focus() { this.focused=true; }
    closest(selector) { return selector==='a' && this.name==='a' ? this : null; }
}
const header=new FakeElement('header');
const toggle=new FakeElement('button');
const nav=new FakeElement('nav');
const author=new FakeElement('details');
const about=new FakeElement('details');
const authorSummary=new FakeElement('summary');
author.querySelector=selector=>selector==='summary'?authorSummary:null;
nav.querySelectorAll=selector=>selector==='.site-nav-group'?[author,about]:[];
header.querySelector=selector=>selector==='.site-nav-toggle'?toggle:selector==='#site-primary-navigation'?nav:null;
header.contains=target=>[header,toggle,nav,author,about].includes(target);
const documentEvents={};
const doc={
    querySelector:selector=>selector==='[data-site-navigation]'?header:null,
    documentElement:{classList:new FakeClassList()},
    addEventListener:(type,fn)=>{documentEvents[type]=fn;}
};
let onChange;
const context={
    document:doc,
    window:{matchMedia:()=>({addEventListener:(type,fn)=>{if(type==='change')onChange=fn;}})}
};
const source=fs.readFileSync(path.join(__dirname,'../public/assets/js/site-nav.js'),'utf8');
vm.runInNewContext(source,context,{filename:'site-nav.js'});
assert(doc.documentElement.classList.contains('site-nav-ready'),'progressive enhancement absent');
toggle.dispatch('click');
assert(nav.classList.contains('is-open'),'burger does not open menu');
assert.equal(toggle.attributes['aria-expanded'],'true');
assert.equal(toggle.attributes['aria-label'],'Fermer le menu');
author.open=true;
documentEvents.keydown({key:'Escape'});
assert(!nav.classList.contains('is-open'),'Escape does not close menu');
assert.equal(toggle.attributes['aria-expanded'],'false');
assert.equal(toggle.focused,true,'keyboard focus not returned');
assert.equal(author.open,false,'submenu left open');
toggle.dispatch('click');
nav.dispatch('click',{target:new FakeElement('a')});
assert.equal(nav.classList.contains('is-open'),false,'following a link does not close menu');
toggle.dispatch('click');
documentEvents.pointerdown({target:new FakeElement('outside')});
assert(!nav.classList.contains('is-open'),'outside click does not close menu');
toggle.dispatch('click');
onChange();
assert(!nav.classList.contains('is-open'),'resize does not reset mobile menu');
author.open=true;
documentEvents.keydown({key:'Escape'});
assert.equal(author.open,false,'desktop submenu not closed on Escape');
assert.equal(authorSummary.focused,true,'focus not restored to desktop submenu summary');
console.log('Navigation JavaScript : burger, aria-expanded, fermeture, Escape, focus et responsive OK');
