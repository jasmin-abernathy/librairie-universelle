<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/PdfXExport.php';
$profiles=array_merge(
 glob('/usr/share/color/icc/ghostscript/*cmyk*.icc')?:[],
 glob('/usr/share/ghostscript/*/iccprofiles/*cmyk*.icc')?:[]
);
$icc='';
foreach($profiles as $candidate)if(PdfXExport::profileOk($candidate)){$icc=$candidate;break;}
if($icc===''){fwrite(STDERR,"No CI CMYK ICC profile found.\n");exit(1);}
$cfg=[
 'pdfx_enabled'=>true,'pdfx_gs_bin'=>'/usr/bin/gs','pdfx_timeout_bin'=>'/usr/bin/timeout',
 'pdfx_icc_profile'=>$icc,'pdfx_output_condition'=>'CI test CMYK (not for production)',
 'pdfx_max_bytes'=>26214400
];
$dir=sys_get_temp_dir().'/lu-pdfx-integ-'.bin2hex(random_bytes(6));mkdir($dir,0700);
try {
 $ps=$dir.'/source.ps';$pdf=$dir.'/source.pdf';
 file_put_contents($ps,"%!PS\n/Helvetica findfont 16 scalefont setfont\n70 500 moveto (PDF X integration test) show\nshowpage\n");
 $cmd=['/usr/bin/gs','-q','-dBATCH','-dNOPAUSE','-dSAFER','-sDEVICE=pdfwrite','-sOutputFile='.$pdf,$ps];
 $descriptors=[0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file',$dir.'/source-errors.log','w']];
 $proc=proc_open($cmd,$descriptors,$pipes);
 if(!is_resource($proc)||proc_close($proc)!==0)throw new RuntimeException('Failed to generate test PDF');
 foreach(['x4'=>'PDF/X-4','x1a'=>'PDF/X-1a:2001'] as $version=>$claim) {
   $out=$dir.'/'.$version.'.pdf';
   PdfXExport::convert($pdf,$out,$version,$cfg);
   if(!PdfXExport::validPdf($out,26214400))throw new RuntimeException($version.' output invalid');
   $bytes=file_get_contents($out);
   if(!str_contains($bytes,$claim)||!str_contains($bytes,'/OutputIntents'))throw new RuntimeException('Missing PDF/X metadata');
   echo "Ghostscript ".$version." PDF/X output: ".filesize($out)." bytes\n";
 }
}finally{
 foreach(glob($dir.'/*')?:[] as $f)@unlink($f);
 @rmdir($dir);
}
