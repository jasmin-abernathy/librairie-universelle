'use strict';
const assert=require('node:assert/strict');
const cover=require('../public/assets/js/cover-spread.js');
const isbn=require('../public/assets/js/paper-barcode.js');

const d=cover.dimensions({trimWidth:148,trimHeight:210,pages:200,leafMm:'0.100',spineMm:'',bleedMm:8});
assert.equal(d.spineMm,10,'200 pages = 100 feuilles ; à 0,1 mm chacune, dos de 10 mm');
assert.equal(d.totalWidthMm,322,'2 × 148 mm + dos 10 mm + 2 × 8 mm');
assert.equal(d.totalHeightMm,226,'210 mm + 2 × 8 mm');
assert.equal(d.method,'nombre de feuilles × épaisseur de la feuille');
const odd=cover.dimensions({trimWidth:148,trimHeight:210,pages:201,leafMm:.1,spineMm:'',bleedMm:8});
assert.equal(odd.spineMm,10.1,'201 pages comptent 101 feuilles');
const known=cover.dimensions({trimWidth:130,trimHeight:180,pages:201,leafMm:'',spineMm:12.45,bleedMm:8});
assert.equal(known.spineMm,12.45,'cote donnée par Bookelis prioritaire');
assert.equal(known.totalWidthMm,288.45);
assert.equal(known.method,'tranche communiquée par l’imprimeur');
for(const bad of [
  {trimWidth:148,trimHeight:210,pages:200,leafMm:'',spineMm:'',bleedMm:8},
  {trimWidth:148,trimHeight:210,pages:200,leafMm:'0.000',spineMm:'',bleedMm:8},
  {trimWidth:148,trimHeight:210,pages:0,leafMm:.1,spineMm:'',bleedMm:8},
  {trimWidth:148,trimHeight:210,pages:200,leafMm:.1,spineMm:'',bleedMm:-1},
  {trimWidth:148,trimHeight:210,pages:200,leafMm:.1,spineMm:125,bleedMm:8},
]) assert.throws(()=>cover.dimensions(bad));

const img=cover.svgMarkup(d,{title:'Livre <pas du HTML>',author:'Auteur & autrice',
  description:'Résumé de test',isbn:'9780306406157',barcodeBits:isbn.bits('9780306406157'),
  price:20,showGuides:true});
assert.match(img,/viewBox="0 0 322 226"/);
assert.match(img,/9780306406157/);
assert.match(img,/Livre &lt;pas du HTML&gt;/);
assert.doesNotMatch(img,/<pas du HTML>/);
assert.match(img,/stroke="#ef5b4d"/,'guides uniquement dans l’aperçu');
const printable=cover.svgMarkup(d,{title:'Livre',author:'Auteur',showGuides:false});
assert.doesNotMatch(printable,/stroke="#ef5b4d"/,'pas de repères dans l’export');
assert.throws(()=>cover.svgMarkup(d,{title:'Le livre',description:'texte '.repeat(150)}),/long/);
const jpeg=Uint8Array.from([0xff,0xd8,0xff,0xe0,0x00,0x10,0xff,0xd9]);
const pdf=cover.pdfFromJpeg(jpeg,d.totalWidthMm,d.totalHeightMm,3804,2670);
const header=Buffer.from(pdf).subarray(0,8).toString('latin1');
assert.ok(header.startsWith('%PDF-1.4'));
const asText=Buffer.from(pdf).toString('latin1');
assert.ok(asText.includes('/MediaBox [0 0 912.756 640.630]'), 'dimensions PDF physiques');
assert.ok(asText.includes('/Width 3804 /Height 2670'));
assert.ok(asText.endsWith('%%EOF\n'),'PDF terminé normalement');
assert.throws(()=>cover.pdfFromJpeg(Uint8Array.of(1,2,3),320,220,200,200));

const official=cover.dimensionsFromTemplate({
  totalWidthMm:318.5,totalHeightMm:226,trimWidth:148,trimHeight:210,pages:120
});
assert.equal(official.spineMm,6.5,'Gabarit A5 120 pages : tranche de 6,5 mm');
assert.equal(official.totalWidthMm,318.5);
assert.equal(official.totalHeightMm,226);
assert.equal(official.method,'mesures du gabarit PDF importé');
assert.throws(()=>cover.dimensionsFromTemplate({
  totalWidthMm:318.5,totalHeightMm:226,trimWidth:130,trimHeight:180,pages:120
}),/Hauteur incompatible/);
assert.throws(()=>cover.dimensionsFromTemplate({
  totalWidthMm:318.5,totalHeightMm:226,trimWidth:148,trimHeight:210,pages:0
}),/pages/);
assert.throws(()=>cover.dimensionsFromTemplate({
  totalWidthMm:304,totalHeightMm:226,trimWidth:148,trimHeight:210,pages:120
}),/Largeur incompatible/);

console.log('Couverture à plat : gabarit Bookelis requis, géométrie et PDF contrôlés.');
