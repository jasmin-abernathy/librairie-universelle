<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/PdfXExport.php';
$assert=static function(bool $condition,string $label):void{
 if(!$condition){fwrite(STDERR,$label."\n");exit(1);}
};
$cfg=[
 'pdfx_enabled'=>false,'pdfx_gs_bin'=>'/usr/bin/gs','pdfx_timeout_bin'=>'/usr/bin/timeout',
 'pdfx_icc_profile'=>'','pdfx_output_condition'=>'Test CMYK','pdfx_max_bytes'=>26214400
];
$assert(str_contains((string)PdfXExport::reason($cfg),'non activé'),'Export is not disabled by default.');
$assert(!PdfXExport::profileOk('/tmp/nonexistent-pdfx-profile.icc'),'Absent ICC accepted.');
$assert(!PdfXExport::validPdf('/tmp/nonexistent-pdfx-source.pdf',100000),'Missing PDF accepted.');
$dir=sys_get_temp_dir().'/lu-pdfx-unit-'.bin2hex(random_bytes(5));mkdir($dir,0700);
try{
  $source=$dir.'/source.pdf';
  file_put_contents($source,"%PDF-1.7\n".str_repeat('x',64)."\n%%EOF\n");
  $assert(PdfXExport::validPdf($source,1000),'PDF boundary validation failed.');
  $assert(!PdfXExport::validPdf($source,32),'Too large PDF accepted.');
  $icc=$dir.'/profile.icc';
  $bytes=str_repeat("\0",132);
  $bytes=substr_replace($bytes,'CMYK',16,4);
  $bytes=substr_replace($bytes,'acsp',36,4);
  file_put_contents($icc,$bytes);
  $assert(PdfXExport::profileOk($icc),'Valid CMYK profile header not accepted.');
  $def=PdfXExport::definition('x4',$icc,'Trusted printing condition');
  $assert(str_contains($def,'PDF/X-4'),'PDF/X-4 declaration absent.');
  $assert(str_contains($def,'/DestOutputProfile'),'Output intent absent.');
  $assert(str_contains(PdfXExport::definition('x1a',$icc,'CMYK'),'PDF/X-1a:2001'),'PDF/X-1a declaration absent.');
  try{PdfXExport::definition('x3',$icc,'CMYK');$assert(false,'Unsupported format accepted.');}
  catch(InvalidArgumentException){}
  $cfg['pdfx_enabled']=true;$cfg['pdfx_icc_profile']=$icc;
  $cmd=PdfXExport::command($cfg,'x4',$dir.'/definition.ps',$source,$dir.'/result.pdf');
  $assert($cmd[3]==='/usr/bin/gs','Unexpected Ghostscript binary.');
  $assert(in_array('-dSAFER',$cmd,true),'SAFER disabled.');
  $assert(!in_array('-dNOSAFER',$cmd,true),'Unsafe mode enabled.');
  $assert(in_array('--permit-file-read='.$icc,$cmd,true),'ICC file permission missing.');
  // Unit tests may execute before Ghostscript is installed on the runner.
  if (is_file('/usr/bin/gs') && is_executable('/usr/bin/gs') && is_file('/usr/bin/timeout')) {
      $assert(PdfXExport::reason($cfg)===null,'Configured PDF/X unexpectedly disabled.');
  }
  echo "PDF/X configuration and security tests passed\n";
}finally{
 foreach(glob($dir.'/*')?:[] as $f)@unlink($f);
 @rmdir($dir);
}
