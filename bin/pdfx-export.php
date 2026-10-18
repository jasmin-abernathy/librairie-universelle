<?php
declare(strict_types=1);

/** Convert a trusted, local PDF without HTTP uploads or a public Ghostscript endpoint. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/PdfXExport.php';

$usage=<<<'USAGE'
Librairie universelle — conversion PDF/X en local

php bin/pdfx-export.php --input=livre.pdf --output=livre-pdfx4.pdf \
  --format=x4 --icc=/chemin/profil-CMYK-de-l-imprimeur.icc \
  --condition="Condition d'impression validée par l'imprimeur"

--format=x4|x1a : PDF/X-4 ou PDF/X-1a:2001
--gs=/usr/bin/gs : Ghostscript 10.05+ (optionnel)
--timeout=/usr/bin/timeout : limiteur GNU (optionnel)
--help : affiche cette aide

Aucun upload réseau. Le PDF original n'est jamais écrasé.
Faire valider le PDF/X généré par un contrôle prépresse indépendant.

USAGE;
$args=getopt('', ['input:','output:','format:','icc:','condition:','gs:','timeout:','help']);
if(isset($args['help'])){echo $usage;exit(0);}
foreach(['input','output','format','icc','condition'] as $key){
    if(!isset($args[$key]) || !is_string($args[$key]) || trim($args[$key])===''){
        fwrite(STDERR,"Argument obligatoire absent : --{$key}\n\n".$usage);
        exit(2);
    }
}
try {
    $input=realpath($args['input']);
    $icc=realpath($args['icc']);
    if($input===false || $icc===false)throw new RuntimeException('Fichier PDF ou profil ICC introuvable.');
    $directory=realpath(dirname($args['output']));
    $basename=basename($args['output']);
    if($directory===false || !is_writable($directory) || !preg_match('/\.pdf$/iD',$basename)){
        throw new RuntimeException('Le dossier de destination doit exister et le fichier doit se terminer par .pdf.');
    }
    $output=$directory.DIRECTORY_SEPARATOR.$basename;
    if(file_exists($output) || is_link($output) || $input===$output){
        throw new RuntimeException('Le PDF de sortie existe déjà : aucun fichier ne sera écrasé.');
    }
    $cfg=[
        'pdfx_enabled'=>true,
        'pdfx_gs_bin'=>$args['gs']??'/usr/bin/gs',
        'pdfx_timeout_bin'=>$args['timeout']??'/usr/bin/timeout',
        'pdfx_icc_profile'=>$icc,
        'pdfx_output_condition'=>$args['condition'],
        'pdfx_max_bytes'=>26214400
    ];
    PdfXExport::convert($input,$output,$args['format'],$cfg);
    echo "Conversion réussie : {$output}\n";
    echo "Fichier NON certifié : validez couleurs, polices, boîtes de pages et BAT avec l'imprimeur.\n";
}catch(Throwable $e){
    fwrite(STDERR,"Conversion impossible : ".$e->getMessage()."\n");
    exit(1);
}
