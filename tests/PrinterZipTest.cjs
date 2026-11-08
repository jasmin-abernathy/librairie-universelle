'use strict';
const assert=require('node:assert/strict');
const zip=require('../public/assets/js/printer-zip.js');
(async()=>{
 assert.equal(await zip.crc32(new Blob(['123456789'])),0xcbf43926);
 const entries=[
   {name:'interieur.pdf',blob:new Blob(['%PDF-1.4\nINTERIEUR'])},
   {name:'premiere-couverture.pdf',blob:new Blob(['%PDF-1.4\nPREMIERE'])},
   {name:'quatrieme-couverture.pdf',blob:new Blob(['%PDF-1.4\nQUATRIEME'])},
   {name:'fiche-imprimeur.json',blob:new Blob(['{"ok":true}'])}
 ];
 const b=await zip.create(entries),v=new DataView(await b.arrayBuffer());
 assert.equal(v.getUint32(0,true),0x04034b50);
 assert.equal(v.getUint32(b.size-22,true),0x06054b50);
 assert.equal(v.getUint16(b.size-12,true),4);
 const central=v.getUint32(b.size-6,true);
 assert.equal(v.getUint32(central,true),0x02014b50);
 let pos=0;
 for(const f of entries){
  assert.equal(v.getUint32(pos,true),0x04034b50);
  assert.equal(v.getUint32(pos+14,true),await zip.crc32(f.blob));
  assert.equal(v.getUint32(pos+18,true),f.blob.size);
  pos+=30+f.name.length+f.blob.size;
 }
 assert.equal(pos,central);
 // Le kit Bookelis comporte deux PDF (intérieur + couverture à plat),
 // une fiche JSON et les instructions ; Corep utilise trois PDF + fiche.
 const bookelisEntries=[
   {name:'interieur.pdf',blob:new Blob(['%PDF-1.4\\nLIVRE'])},
   {name:'couverture-complete-bookelis.pdf',blob:new Blob(['%PDF-1.4\\nCOUVERTURE'])},
   {name:'fiche-impression.json',blob:new Blob(['{"provider":"bookelis"}'])},
   {name:'instructions.txt',blob:new Blob(['Gabarit officiel Bookelis requis'])}
 ];
 const bBookelis=await zip.create(bookelisEntries);
 const vBookelis=new DataView(await bBookelis.arrayBuffer());
 assert.equal(vBookelis.getUint16(bBookelis.size-12,true),4);
 assert.equal(vBookelis.getUint32(bBookelis.size-22,true),0x06054b50);
 await assert.rejects(zip.create([entries[0],entries[0],entries[2],entries[3]]));
 console.log('Archive ZIP locale : 4 fichiers et CRC32 OK');
})().catch(e=>{console.error(e);process.exit(1)});
