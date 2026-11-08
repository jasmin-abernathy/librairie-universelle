<?php
declare(strict_types=1);

/**
 * PDF de convention pour usage administratif uniquement.
 * Utilise Ghostscript déjà disponible sur l'hébergement ; aucun contenu
 * nominatif n'est écrit dans le document root ou dans une URL publique.
 */
final class SellerAgreementPdf
{
    public static function generate(array $seller): string
    {
        foreach (['seller_legal_name', 'seller_postal_address', 'private_email', 'seller_key'] as $key) {
            if (trim((string)($seller[$key] ?? '')) === '') {
                throw new InvalidArgumentException('Identité du vendeur incomplète pour générer le PDF.');
            }
        }
        if (!function_exists('proc_open') || !is_executable('/usr/bin/gs')) {
            throw new RuntimeException('Le moteur de génération PDF est indisponible.');
        }

        $ps = self::postScript($seller);
        $psFile = tempnam(sys_get_temp_dir(), 'lu-contract-ps-');
        $pdfFile = tempnam(sys_get_temp_dir(), 'lu-contract-pdf-');
        if ($psFile === false || $pdfFile === false) {
            throw new RuntimeException('Impossible de préparer le PDF temporaire.');
        }
        try {
            chmod($psFile, 0600);
            chmod($pdfFile, 0600);
            if (file_put_contents($psFile, $ps) === false) {
                throw new RuntimeException('Impossible de préparer le document PDF.');
            }
            $command = ['/usr/bin/timeout', '15', '/usr/bin/gs', '-dSAFER', '-dBATCH', '-dNOPAUSE',
                '-q', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4',
                '-sOutputFile=' . $pdfFile, $psFile];
            $pipes = [];
            $proc = proc_open($command, [
                0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w'],
            ], $pipes);
            if (!is_resource($proc)) {
                throw new RuntimeException('Conversion PDF impossible.');
            }
            fclose($pipes[0]);
            stream_get_contents($pipes[1]); fclose($pipes[1]);
            $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $status = proc_close($proc);
            $pdf = file_get_contents($pdfFile);
            if ($status !== 0 || $pdf === false || !str_starts_with($pdf, '%PDF-')) {
                error_log('seller_contract_pdf: Ghostscript failed (status ' . $status . ', stderr bytes ' . strlen($errors) . ')');
                throw new RuntimeException('La génération du PDF a échoué.');
            }
            return $pdf;
        } finally {
            @unlink($psFile);
            @unlink($pdfFile);
        }
    }

    /** PDF texte sélectionnable en A4, avec fontes PDF standard et caractères Latin 1. */
    private static function postScript(array $seller): string
    {
        $cmd = [
            '%!PS-Adobe-3.0',
            '<< /PageSize [595 842] >> setpagedevice',
            '/Helvetica findfont dup length dict begin',
            '{1 index /FID ne {def} {pop pop} ifelse} forall',
            '/Encoding ISOLatin1Encoding def currentdict end',
            '/LatinRegular exch definefont pop',
            '/Helvetica-Bold findfont dup length dict begin',
            '{1 index /FID ne {def} {pop pop} ifelse} forall',
            '/Encoding ISOLatin1Encoding def currentdict end',
            '/LatinBold exch definefont pop',
        ];
        $y = 0.0;
        $page = 0;
        $finishPage = static function () use (&$cmd, &$page): void {
            $cmd[] = '0.35 0.42 0.38 setrgbcolor /LatinRegular 8 selectfont';
            $cmd[] = '48 43 moveto (Librairie universelle - convention de referencement) show';
            $cmd[] = '505 43 moveto (' . $page . ') show';
            $cmd[] = '0 setgray showpage';
        };
        $startPage = static function () use (&$cmd, &$y, &$page): void {
            $page++;
            $y = 781.0;
            $cmd[] = '0.17 0.36 0.27 setrgbcolor 0 814 595 28 rectfill 0 setgray';
        };
        $startPage();
        $addLine = static function (string $line, bool $bold = false, float $size = 9.6, float $leading = 14.5)
            use (&$cmd, &$y, $finishPage, $startPage): void {
            if ($y < 76) {
                $finishPage();
                $startPage();
            }
            $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $line);
            if (!is_string($encoded)) $encoded = preg_replace('/[^\x20-\x7e]/', '?', $line) ?? '?';
            $encoded = preg_replace('/[\x00-\x1f\x7f]/', ' ', $encoded) ?? $encoded;
            $encoded = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
            $cmd[] = '0 setgray /' . ($bold ? 'LatinBold' : 'LatinRegular') . ' ' .
                number_format($size, 1, '.', '') . ' selectfont';
            $cmd[] = '48 ' . number_format($y, 1, '.', '') . ' moveto (' . $encoded . ') show';
            $y -= $leading;
        };
        $wrap = static function (string $value, int $max = 93): array {
            $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
            if ($value === '') return [''];
            $result = []; $current = '';
            foreach (preg_split('/\s+/u', $value) ?: [] as $word) {
                if (mb_strlen($word, 'UTF-8') > $max) {
                    if ($current !== '') { $result[] = $current; $current = ''; }
                    while (mb_strlen($word, 'UTF-8') > $max) {
                        $result[] = mb_substr($word, 0, $max, 'UTF-8');
                        $word = mb_substr($word, $max, null, 'UTF-8');
                    }
                }
                $trial = $current === '' ? $word : $current . ' ' . $word;
                if (mb_strlen($trial, 'UTF-8') <= $max) {
                    $current = $trial;
                } else {
                    if ($current !== '') $result[] = $current;
                    $current = $word;
                }
            }
            if ($current !== '') $result[] = $current;
            return $result;
        };
        $paragraph = static function (string $copy) use ($wrap, $addLine, &$y): void {
            foreach ($wrap($copy) as $line) $addLine($line);
            $y -= 7;
        };
        $section = static function (string $heading, string $copy) use ($addLine, $paragraph, &$y): void {
            $y -= 5;
            $addLine($heading, true, 10.4, 19);
            $paragraph($copy);
        };
        $addLine('CONVENTION DE REFERENCEMENT ET DE MISE EN RELATION', true, 14.0, 22);
        $addLine('Livres papier et EPUB autoedites - Commission de 3 % - Accord non exclusif', false, 9.5, 25);
        $addLine('EXPLOITANT', true, 10.4, 17);
        $paragraph('Le Potager du Web, entreprise individuelle (SIRET 808 087 399 00032), 3 rue Brunehaut, 57000 Metz. Interlocuteur pour le contrat : Jasmin Leveque. Courriel : contact@lepotager.org.');
        $addLine('VENDEUR / SIGNATAIRE - COORDONNEES FOURNIES AU DEPOT', true, 10.4, 18);
        $sellerName = trim((string)$seller['seller_legal_name']);
        $paragraph('Nom legal et qualite : ' . $sellerName . '.');
        if (($seller['seller_type'] ?? '') === 'professional') {
            $paragraph('Structure : ' . (string)($seller['registered_company_name'] ?? $sellerName) . '; SIRET : ' . (string)($seller['siret'] ?? 'a confirmer') . '.');
        }
        $paragraph('Adresse postale : ' . (string)$seller['seller_postal_address'] . '.');
        $paragraph('Courriel de contact pour signature : ' . (string)$seller['private_email'] . '.');
        if (trim((string)($seller['seller_phone'] ?? '')) !== '') {
            $paragraph('Telephone : ' . (string)$seller['seller_phone'] . '.');
        }
        $paragraph('Auteur public : ' . (string)($seller['author_name'] ?? '') .
            '; premier livre propose : ' . (string)($seller['title'] ?? '') . '.');
        $section('1. OBJET ET CONDITION PREALABLE',
            'Le Potager du Web reference les livres papier et/ou EPUB autoedites proposes par le vendeur et accepte les mises en relation directes avec les lecteurs. La publication de toute fiche de vente directe est subordonnee a la signature prealable de cette convention par les deux parties. Une seule convention couvre les livres du meme vendeur. Le site n encaisse ni ne livre les ouvrages.');
        $section('2. PRIX ET OBLIGATIONS DU VENDEUR',
            'Le vendeur fixe et tient a jour le prix public TTC de chaque edition et respecte les regles francaises du prix unique du livre et du livre numerique quand elles sont applicables. Il ne majore pas le prix du livre pour financer la commission. Les frais de livraison sont separes et conformes a la reglementation. Le vendeur conserve la responsabilite des droits, ventes, paiements, livraisons, acces numeriques, garanties, retractations et reclamations.');
        $section('3. COMMISSION DE 3 %',
            'Pour les ventes de livres papier ou numeriques effectivement conclues et encaissees grace a une mise en relation issue de la Librairie, le vendeur doit une commission de 3 % du montant TTC des livres encaisses, hors livraison, annulations et remboursements. Aucun montant n est du pour un simple contact, une vente non payee ou une vente sans lien avec la plateforme. La commission est a la charge du vendeur et ne s ajoute pas au prix du lecteur.');
        $section('4. DECLARATION TRIMESTRIELLE DE BONNE FOI',
            'A chaque fin de trimestre civil, le vendeur communique un bilan simple : nombre de ventes attribuables a la Librairie et total TTC des livres encaisses hors livraison et remboursements, ou zero vente. Aucune identite d acheteur, facture individuelle, preuve de paiement systematique ou surveillance bancaire ne sont exigees. Un compteur anonyme de demandes ne constitue jamais une preuve de vente.');
        $section('5. FACTURATION',
            'Le Potager du Web facture la commission selon son regime de TVA applicable, payable a trente jours de la facture sauf accord conforme different. Aucun paiement du lecteur n est intercepte. Aucune commission n est due retroactivement sur une vente conclue avant l entree en vigueur de la convention.');
        $section('6. MESSAGES ET CONFIDENTIALITE',
            'Les messages des lecteurs sont destines exclusivement au vendeur : le Potager du Web n en recoit aucune copie, ni visible ni cachee. La plateforme peut conserver des compteurs agreges de mises en relation, sans contenu de message ni liste nominative de lecteurs. Les traitements de donnees suivent les conditions et notices publiees.');
        $section('7. REFERENCEMENT, DUREE ET FIN DE L ACCORD',
            'La Librairie examine les fiches avant publication et peut les refuser ou les suspendre pour motif legitime. La commission ne procure aucun meilleur classement. Cet accord est non exclusif, de duree indeterminee, et peut etre resilie par courriel avec un preavis de quinze jours, sans penalite. Les commissions deja acquises restent dues. Les futures librairies partenaires relevent de conventions differentes.');
        $section('8. DOCUMENTS ET DIFFERENDS',
            'Les conditions generales du site sont consultables sur librairie.lepotager.org/conditions-generales.php. Les clauses particulieres de la presente convention priment pour la commission. Aucune modification future unilaterale ne change le taux signe. Les parties recherchent d abord une issue amiable sans renoncer aux droits imperatifs applicables.');
        $y -= 12;
        $addLine('Pour Le Potager du Web :', true, 10.0, 20);
        $addLine('Date : ____________________    Signature : _______________________________', false, 9.5, 30);
        $addLine('Pour le vendeur / signataire : ' . $sellerName, true, 10.0, 20);
        $addLine('Date : ____________________    Signature : _______________________________', false, 9.5, 20);
        $finishPage();
        $cmd[] = '%%EOF';
        return implode("\n", $cmd) . "\n";
    }
}
