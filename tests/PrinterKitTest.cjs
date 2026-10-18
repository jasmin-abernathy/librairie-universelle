'use strict';
const assert=require('node:assert/strict');
const kit=require('../public/assets/js/printer-kit.js');
assert.equal(kit.pdfSignature(Uint8Array.from([37,80,68,70,45,49,46,55])),true);
assert.equal(kit.pdfSignature(Uint8Array.from([37,80,68,70,88,49,46,55])),false);
assert.equal(kit.pdfSignature(new Uint8Array(0)),false);
const files=[
 {role:'interior',name:'interieur.pdf',size:150000,pdfValid:true},
 {role:'front',name:'premiere.pdf',size:12000,pdfValid:true},
 {role:'back',name:'quatrieme.pdf',size:18000,pdfValid:true}
];
const manifest=kit.manifest({title:'Livre test',author:'A. Test',isbn:'',format:'a5',paper:'80g',pages:'200',quantity:'30'},files);
assert.equal(manifest.schema,'librairie-print-package-v1');
assert.equal(manifest.files.length,3);
assert.equal(manifest.requested_specification.pages,200);
assert.equal(manifest.work.title,'Livre test');
assert.ok(kit.message(manifest).includes('quatrieme.pdf'));
assert.ok(kit.message(manifest).includes('confirmer le prix'));
assert.throws(()=>kit.manifest({title:'A',author:'B',pages:'0',quantity:''},files));
assert.throws(()=>kit.manifest({title:'A',author:'B',pages:'',quantity:''},[files[0],files[0],files[2]]));
assert.throws(()=>kit.manifest({title:'A',author:'B',pages:'',quantity:''},[files[0],files[1]]));
console.log('Printer kit: en-têtes PDF et dossier local OK');
