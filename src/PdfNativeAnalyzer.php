<?php
declare(strict_types=1);

/**
 * Métadonnées uniquement : aucun rendu raster ni extraction de texte.
 * Ghostscript est exécuté en mode SAFER sous timeout, sur un upload temporaire.
 */
final class PdfNativeAnalyzer
{
    private const SIZES = [
        'a5' => [148,210], '130x180' => [130,180], '156x234' => [156,234],
        '170x244' => [170,244], '170x170' => [170,170],
        '190x150' => [190,150], 'a4' => [210,297]
    ];

    public static function parseGhostscript(string $output): array
    {
        if (!preg_match('/^PAGES:\s*(\d+)\s*$/m', $output, $match)) {
            throw new RuntimeException('Nombre de pages du PDF indéterminé.');
        }
        $pages = (int) $match[1];
        if ($pages < 1 || $pages > 5000) {
            throw new RuntimeException('Pagination PDF hors limites (1 à 5 000 pages).');
        }
        $dimensions = [];
        foreach (['FIRST','MIDDLE','LAST'] as $label) {
            if (!preg_match('/^'. $label .':\s*\[([^\]]+)\]/m', $output, $parts)) {
                throw new RuntimeException('Boîtes de page PDF indéterminées.');
            }
            $numbers = preg_split('/\s+/', trim($parts[1]));
            if (!is_array($numbers) || count($numbers) !== 4) {
                throw new RuntimeException('Boîte de page PDF incorrecte.');
            }
            foreach ($numbers as $number) {
                if (!is_numeric($number) || !is_finite((float) $number)) {
                    throw new RuntimeException('Cotes PDF invalides.');
                }
            }
            $width = (float)$numbers[2] - (float)$numbers[0];
            $height = (float)$numbers[3] - (float)$numbers[1];
            if ($width <= 0 || $height <= 0) {
                throw new RuntimeException('Dimensions PDF incorrectes.');
            }
            $dimensions[$label] = [round($width * 25.4 / 72, 1), round($height * 25.4 / 72, 1)];
        }
        [$w,$h] = $dimensions['FIRST'];
        if ($w < 30 || $h < 30 || $w > 1000 || $h > 1000) {
            throw new RuntimeException('Format PDF hors limites.');
        }
        $mixed = false;
        foreach (['MIDDLE','LAST'] as $label) {
            $mixed = $mixed || abs($dimensions[$label][0]-$w)>1.5 ||
                abs($dimensions[$label][1]-$h)>1.5;
        }
        $trim = null;
        if (!$mixed) {
            foreach (self::SIZES as $key => $value) {
                if (abs($value[0]-$w)<=1.5 && abs($value[1]-$h)<=1.5) {
                    $trim = $key;
                    break;
                }
            }
        }
        $warnings = [
            'Dimensions relevées selon TrimBox, puis CropBox ou MediaBox ; les premières, centrales et dernières pages sont comparées.',
            'Contrôlez les autres pages, les rotations, polices, images et fonds perdus ; ce contrôle ne remplace pas le BAT.',
            'Aucune recomposition ni modification du PDF source.'
        ];
        if ($mixed) {
            array_unshift($warnings, 'Le document présente des dimensions différentes sur les pages échantillonnées : aucun format imprimeur n’est présumé compatible.');
        }
        return [
            'pages'=>$pages, 'width_mm'=>$w, 'height_mm'=>$h,
            'trim'=>$trim, 'mixed_sizes'=>$mixed,
            'corep_eligible'=>($trim==='a5' && $pages<=680 && !$mixed),
            'warnings'=>$warnings
        ];
    }

    public static function inspect(string $path): array
    {
        if (!is_file($path) || !is_readable($path) || !function_exists('proc_open')) {
            throw new RuntimeException('L’analyse du PDF n’est pas accessible.');
        }
        $gs = null;
        foreach (['/usr/bin/gs','/bin/gs'] as $candidate) {
            if (is_executable($candidate)) { $gs = $candidate; break; }
        }
        if ($gs !== null && is_executable('/usr/bin/timeout')) {
            // Aucun chemin venant du nom de fichier : le chemin système d’upload seul.
            // Boîtes non transformées : TrimBox > CropBox > MediaBox.
            $program = 'PDFname (r) file runpdfbegin '.
              '/pages pdfpagecount def (PAGES:) print pages == '.
              '/pagebox { pdfgetpage dup /TrimBox known { /TrimBox get } { '.
              'dup /CropBox known { /CropBox get } { /MediaBox get } ifelse } ifelse } bind def '.
              '(FIRST:) print 1 pagebox == (MIDDLE:) print pages 2 div ceiling cvi pagebox == '.
              '(LAST:) print pages pagebox == quit';
            $command = [
                '/usr/bin/timeout','-k','2s','15s',$gs,
                '-q','-dNODISPLAY','-dBATCH','-dNOPAUSE','-dSAFER',
                '-sPDFname='.$path,'-c',$program
            ];
            $output = self::command($command);
            return self::parseGhostscript($output);
        }

        // Repli Poppler uniquement si l’hébergement n’offre pas Ghostscript.
        $pdfinfo = null;
        foreach (['/usr/bin/pdfinfo','/bin/pdfinfo'] as $candidate) {
            if (is_executable($candidate)) { $pdfinfo=$candidate; break; }
        }
        if ($pdfinfo !== null && is_executable('/usr/bin/timeout')) {
            $output = self::command(['/usr/bin/timeout','-k','2s','15s',$pdfinfo,'-box',$path]);
            if (preg_match('/^Pages:\s*(\d+)/mi',$output,$p) &&
                preg_match('/^Page size:\s*([0-9.]+)\s*x\s*([0-9.]+)\s*pts/mi',$output,$b)) {
                $box='[0 0 '.$b[1].' '.$b[2].']';
                $result=self::parseGhostscript("PAGES:".$p[1]."\nFIRST:".$box."\nMIDDLE:".$box."\nLAST:".$box);
                $result['warnings'][0]='Seule la première page est mesurée avec pdfinfo : vérifier les dimensions des autres pages.';
                return $result;
            }
            throw new RuntimeException('Pagination ou format PDF indéterminé.');
        }
        throw new RuntimeException('Analyse PDF indisponible : Ghostscript ou Poppler est nécessaire sur cet hébergement.');
    }

    private static function command(array $args): string
    {
        $proc=@proc_open($args,
            [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],
            $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($proc)) {
            throw new RuntimeException('Impossible de démarrer le moteur PDF.');
        }
        $output=(string)stream_get_contents($pipes[1],65536);
        fclose($pipes[1]);
        $errors=(string)stream_get_contents($pipes[2],32768);
        fclose($pipes[2]);
        $status=proc_close($proc);
        if ($status!==0) {
            throw new RuntimeException($status===124
              ? 'Analyse trop longue : le PDF dépasse les ressources disponibles.'
              : 'PDF protégé, endommagé ou non analysable. Vérifiez le document.');
        }
        return $output;
    }
}
