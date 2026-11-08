<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/SellerAgreementPdf.php';
$assert=static function(bool $ok,string $why):void {
    if (!$ok) {fwrite(STDERR,"[PDF convention] ".$why."\n");exit(1);}
};
$pdfSource=(string)file_get_contents(dirname(__DIR__).'/src/SellerAgreementPdf.php');
$endpoint=(string)file_get_contents(dirname(__DIR__).'/public/admin/contrat-auteur.php');
$assert(!str_contains($pdfSource,'Odoo Sign')&&!str_contains($pdfSource,'Odoo'),
    'Le contrat ne doit pas nommer le logiciel de signature.');
$assert(str_contains($endpoint,'AdminAuth::enforce('),'Accès au PDF vendeur non protégé.');
$assert(str_contains($endpoint,"Content-Disposition: attachment"),'Téléchargement du PDF manquant.');
if (is_executable('/usr/bin/gs') && function_exists('proc_open')) {
    $demo=[
      'seller_legal_name'=>'Camille Durand','seller_postal_address'=>'12 rue des Fleurs, 57000 Metz',
      'private_email'=>'camille@example.org','seller_key'=>'individual:test',
      'author_name'=>'C. Durand','title'=>'Le premier roman','seller_type'=>'individual',
      'seller_phone'=>'+33 6 12 34 56 78',
    ];
    $pdf=SellerAgreementPdf::generate($demo);
    $assert(str_starts_with($pdf,'%PDF-') && strlen($pdf)>2000,'PDF invalide ou vide.');
    echo "PDF convention : génération Ghostscript vérifiée\n";
} else {
    echo "PDF convention : générateur contrôlé, Ghostscript non disponible sur ce runner\n";
}
