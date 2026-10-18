/* Store-only ZIP, fully client-side. Original files are not transmitted. */
(function(root,factory){
 const api=factory();
 if(typeof module!=='undefined'&&module.exports)module.exports=api;
 if(root)root.PrinterZip=api;
})(typeof window!=='undefined'?window:null,function(){
 'use strict';
 const MAX=250*1048576, CHUNK=1048576;
 const table=new Uint32Array(256);
 for(let i=0;i<256;i++){let c=i;for(let j=0;j<8;j++)c=c&1?0xedb88320^(c>>>1):c>>>1;table[i]=c>>>0;}
 async function crc32(blob){
   let crc=0xffffffff;
   for(let off=0;off<blob.size;off+=CHUNK){
     const data=new Uint8Array(await blob.slice(off,Math.min(off+CHUNK,blob.size)).arrayBuffer());
     for(const v of data)crc=table[(crc^v)&255]^(crc>>>8);
   }
   return(crc^0xffffffff)>>>0;
 }
 const put=(view,off,val,wide)=>wide?view.setUint32(off,val,true):view.setUint16(off,val,true);
 async function create(entries){
   if(!Array.isArray(entries)||entries.length!==4)throw Error('Trois PDF et une fiche sont nécessaires.');
   const names=new Set();let total=0;
   for(const e of entries){
     if(!e||!e.blob||typeof e.blob.slice!=='function'||!Number.isSafeInteger(e.blob.size)||
       !/^[a-zA-Z0-9._-]{1,80}$/.test(e.name)||names.has(e.name))
       throw Error('Nom de fichier invalide ou dupliqué.');
     names.add(e.name);total+=e.blob.size;
   }
   if(total>MAX)throw Error('Archive supérieure à 250 Mo : transmettez les PDF séparément.');
   const enc=new TextEncoder(),payload=[],central=[];
   let offset=0,centralSize=0;
   for(const e of entries){
     const name=enc.encode(e.name),size=e.blob.size,crc=await crc32(e.blob);
     const local=new Uint8Array(30+name.length),l=new DataView(local.buffer);
     put(l,0,0x04034b50,true);put(l,4,20,false);put(l,6,0x800,false);
     put(l,14,crc,true);put(l,18,size,true);put(l,22,size,true);put(l,26,name.length,false);
     local.set(name,30);payload.push(local,e.blob);
     const index=new Uint8Array(46+name.length),c=new DataView(index.buffer);
     put(c,0,0x02014b50,true);put(c,4,20,false);put(c,6,20,false);put(c,8,0x800,false);
     put(c,16,crc,true);put(c,20,size,true);put(c,24,size,true);put(c,28,name.length,false);
     put(c,42,offset,true);index.set(name,46);central.push(index);centralSize+=index.length;
     offset+=local.length+size;
   }
   const end=new Uint8Array(22),d=new DataView(end.buffer);
   put(d,0,0x06054b50,true);put(d,8,entries.length,false);put(d,10,entries.length,false);
   put(d,12,centralSize,true);put(d,16,offset,true);
   return new Blob([...payload,...central,end],{type:'application/zip'});
 }
 return{create,crc32,MAX};
});
