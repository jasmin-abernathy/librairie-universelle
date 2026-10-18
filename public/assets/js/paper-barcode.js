/* EAN-13 for publisher-supplied ISBN-13. No ISBN is assigned/generated. */
(function (root, factory) {
    const api = factory();
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    if (root) root.PaperBarcode = api;
})(typeof window !== 'undefined' ? window : null, function () {
    'use strict';
    const L = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
    const G = ['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
    const R = ['1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'];
    const P = ['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL'];
    function digits(input) { return String(input || '').replace(/[\s-]/g, ''); }
    function valid(input) {
        const value = digits(input);
        if (!/^97[89][0-9]{10}$/.test(value)) return false;
        const sum = value.slice(0,12).split('').reduce((n,c,i) => n + Number(c)*(i%2 ? 3:1),0);
        return (10-(sum%10))%10===Number(value[12]);
    }
    function bits(input) {
        const n = digits(input);
        if (!valid(n)) throw new Error('ISBN-13 invalide ou chiffre de contrôle erroné.');
        let out = '101', parity=P[Number(n[0])];
        for (let i=1;i<=6;i++) out += (parity[i-1]==='L'?L:G)[Number(n[i])];
        out += '01010';
        for (let i=7;i<=12;i++) out += R[Number(n[i])];
        return out+'101';
    }
    function svg(input) {
        const value=digits(input), code=bits(value);
        let bars='';
        for(let i=0;i<code.length;i++){
            if(code[i]!=='1')continue;
            const guard=(i<3||i>=45&&i<50||i>=92);
            bars += '<rect x="'+(11+i)+'" y="4" width="1" height="'+(guard?65:57)+'"/>';
        }
        // Physical width specified separately by CSS, quiet zones included.
        return '<svg xmlns="http://www.w3.org/2000/svg" width="113" height="82" viewBox="0 0 113 82" role="img" aria-label="Code EAN-13 '+value+'">'
           +'<rect width="113" height="82" fill="#fff"/><g fill="#111">'+bars+'</g>'
           +'<text x="8" y="79" font-family="Arial,sans-serif" font-size="8" text-anchor="middle" fill="#111">'+value[0]+'</text>'
           +'<text x="31" y="79" font-family="Arial,sans-serif" font-size="8" text-anchor="middle" fill="#111">'+value.slice(1,7)+'</text>'
           +'<text x="80" y="79" font-family="Arial,sans-serif" font-size="8" text-anchor="middle" fill="#111">'+value.slice(7)+'</text></svg>';
    }
    return {digits,valid,bits,svg};
});
