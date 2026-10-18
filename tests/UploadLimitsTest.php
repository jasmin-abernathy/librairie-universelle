<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/UploadLimits.php';
$assert=static function(bool $ok,string $reason):void{
 if(!$ok){fwrite(STDERR,$reason."\n");exit(1);}
};
$assert(UploadLimits::iniBytes('2M')===2097152,'2 Mo parsed incorrectly.');
$assert(UploadLimits::iniBytes('1G')===1073741824,'1 Go parsed incorrectly.');
$assert(UploadLimits::iniBytes('512K')===524288,'KiB parsed incorrectly.');
$assert(UploadLimits::iniBytes('8M')===8388608,'POST size parsed incorrectly.');
$assert(UploadLimits::effective(31457280,2097152,8388608)===2097152,'PHP upload 2M not limiting.');
$assert(UploadLimits::effective(31457280,117440512,201326592)===31457280,'30M logical limit not applied.');
$assert(UploadLimits::effective(104857600,117440512,201326592)===104857600,'PDF cap incorrect.');
$assert(UploadLimits::effective(104857600,117440512,8388608)===7340032,'POST overhead not reserved.');
$assert(UploadLimits::effective(31457280,0,0)===31457280,'Unlimited runtime limit incorrect.');
echo "UploadLimits: OK\n";
