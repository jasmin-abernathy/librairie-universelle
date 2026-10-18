<?php
declare(strict_types=1);

/**
 * Optional Ghostscript PDF/X export of a browser-generated PDF.
 * No conversion starts unless the operator configured the approved CMYK ICC
 * profile and explicitly enabled this feature. The output is NOT certified.
 */
final class PdfXExport
{
    public static function profileOk(string $path): bool
    {
        if ($path === '' || !is_file($path) || is_link($path) || !is_readable($path)) return false;
        $size = filesize($path);
        if ($size === false || $size < 128 || $size > 16777216) return false;
        $f = @fopen($path,'rb');
        if ($f === false) return false;
        try { $header=fread($f,128); } finally { fclose($f); }
        return is_string($header) && strlen($header)>=40
          && substr($header,16,4)==='CMYK' && substr($header,36,4)==='acsp';
    }

    public static function reason(array $config): ?string
    {
        if (empty($config['pdfx_enabled'])) return 'Export PDF/X non activé sur cet hébergement.';
        if (!function_exists('proc_open')) return 'L’exécution isolée de Ghostscript est indisponible.';
        foreach (['pdfx_gs_bin','pdfx_timeout_bin'] as $name) {
            $binary=(string)($config[$name]??'');
            if ($binary==='' || !str_starts_with($binary,'/') || !is_file($binary) || !is_executable($binary)) {
                return 'Moteur ou limiteur de durée non configuré par l’hébergeur.';
            }
        }
        // Versions prior to 10.05 cannot generate both target PDF/X formats.
        // Query only the operator-configured binary; never accept a user path.
        $gs=(string)$config['pdfx_gs_bin'];
        $descriptors=[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['file','/dev/null','w']];
        $proc=@proc_open([$gs,'--version'],$descriptors,$pipes,null,null,['bypass_shell'=>true]);
        if(!is_resource($proc))return 'Impossible de lire la version de Ghostscript.';
        $version=isset($pipes[1])&&is_resource($pipes[1])?trim((string)stream_get_contents($pipes[1],64)):'';
        if(isset($pipes[1])&&is_resource($pipes[1]))fclose($pipes[1]);
        if(proc_close($proc)!==0 || !preg_match('/^\d+\.\d+(?:\.\d+)?$/D',$version)
           || version_compare($version,'10.05.0','<')) {
            return 'Ghostscript 10.05.0 ou plus récent est nécessaire pour PDF/X-4 et PDF/X-1a.';
        }
        if (!self::profileOk((string)($config['pdfx_icc_profile']??''))) {
            return 'Profil ICC CMJN approuvé absent ou non valide.';
        }
        return null;
    }

    public static function definition(string $version, string $icc, string $condition): string
    {
        if (!in_array($version,['x4','x1a'],true))throw new InvalidArgumentException('Version PDF/X inconnue.');
        if (!self::profileOk($icc))throw new InvalidArgumentException('Profil ICC CMJN non valide.');
        // The file path is server-controlled. Escaping is still necessary in PS literal strings.
        $quoted=static fn(string $s):string=>str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)', ' ', ' '],$s);
        $name=$version==='x4'?'PDF/X-4':'PDF/X-1a:2001';
        $condition=trim(preg_replace('/[^\x20-\x7e]/',' ',substr($condition,0,120))??'');
        if ($condition==='') $condition='Custom CMYK';
        $description=$quoted($condition);
        return "%!\n"
          ."[ /GTS_PDFXVersion (".$name.") /Title (Livre) /Trapped /False /DOCINFO pdfmark\n"
          ."[/_objdef {icc_PDFX} /type /stream /OBJ pdfmark\n"
          ."[{icc_PDFX} << /N 4 >> /PUT pdfmark\n"
          ."[{icc_PDFX} (".$quoted($icc).") (r) file /PUT pdfmark\n"
          ."[/_objdef {OutputIntent_PDFX} /type /dict /OBJ pdfmark\n"
          ."[{OutputIntent_PDFX} << /Type /OutputIntent /S /GTS_PDFX "
          ."/OutputCondition (".$description.") /Info (".$description.") "
          ."/OutputConditionIdentifier (Custom-CMYK) "
          ."/DestOutputProfile {icc_PDFX} >> /PUT pdfmark\n"
          ."[{Catalog} << /OutputIntents [ {OutputIntent_PDFX} ] >> /PUT pdfmark\n";
    }

    public static function validPdf(string $path, int $limit): bool
    {
        if (!is_file($path) || !is_readable($path) || is_link($path)) return false;
        $size=filesize($path);
        if (!is_int($size) || $size<64 || $size>$limit) return false;
        $f=@fopen($path,'rb');
        if ($f===false)return false;
        try {
            $head=fread($f,5);
            fseek($f,max(0,$size-4096));
            $tail=fread($f,4096);
            return $head==='%PDF-' && is_string($tail)
                && preg_match('/%%EOF[\s\x00]*$/D',$tail)===1;
        } finally { fclose($f); }
    }

    public static function command(array $config, string $version, string $definition, string $source, string $output): array
    {
        if (!in_array($version,['x4','x1a'],true))throw new InvalidArgumentException('Version inconnue.');
        $icc=(string)$config['pdfx_icc_profile'];
        $flag=$version==='x4'?'4':'1';
        return [
            (string)$config['pdfx_timeout_bin'],'--signal=TERM','75s',
            (string)$config['pdfx_gs_bin'],'-q','-dBATCH','-dNOPAUSE','-dSAFER',
            '--permit-file-read='.$icc,
            '-dPDFX='.$flag,
            '-dCompatibilityLevel='.($version==='x4'?'1.6':'1.3'),
            '-dPDFACompatibilityPolicy=2',
            '-dEmbedAllFonts=true',
            '-sColorConversionStrategy=CMYK',
            '-sDEVICE=pdfwrite',
            '-sOutputFile='.$output,
            $definition,$source
        ];
    }

    public static function convert(string $input, string $output, string $version, array $config): void
    {
        $why=self::reason($config);
        if ($why!==null)throw new RuntimeException($why);
        $max=(int)$config['pdfx_max_bytes'];
        if (!self::validPdf($input,$max))throw new RuntimeException('PDF source absent, trop lourd ou incomplet.');
        if (!in_array($version,['x4','x1a'],true))throw new RuntimeException('Format PDF/X non reconnu.');
        $definition=dirname($output).'/definition.ps';
        if (file_put_contents($definition,self::definition($version,(string)$config['pdfx_icc_profile'],(string)$config['pdfx_output_condition']),LOCK_EX)===false) {
            throw new RuntimeException('Préparation du profil colorimétrique impossible.');
        }
        chmod($definition,0600);
        $cmd=self::command($config,$version,$definition,$input,$output);
        $descriptors=[0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file',dirname($output).'/ghostscript-errors.log','w']];
        $proc=@proc_open($cmd,$descriptors,$pipes,dirname($output),null,['bypass_shell'=>true]);
        if (!is_resource($proc))throw new RuntimeException('Conversion PDF/X indisponible.');
        // Standard output is discarded directly; closing a pipe early could SIGPIPE Ghostscript.
        $code=proc_close($proc);
        if ($code!==0 || !self::validPdf($output,$max*3)) {
            throw new RuntimeException('Ghostscript n’a pas produit un PDF/X exploitable. Réexportez le PDF standard et réessayez.');
        }
        // Necessary indicators, NOT an independent ISO PDF/X preflight.
        $sample=file_get_contents($output,false,null,0,min(1048576,(int)filesize($output)));
        $flag=$version==='x4'?'PDF/X-4':'PDF/X-1a:2001';
        if (!is_string($sample) || !str_contains($sample,'/OutputIntents')
            || !str_contains($sample,$flag)) {
            throw new RuntimeException('Les marqueurs PDF/X ou l’intention de sortie sont absents du document produit.');
        }
    }
}
