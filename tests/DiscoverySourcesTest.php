<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/discovery-bootstrap.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

foreach (['bnf','gallica','wikisource','elg','bnr','gutenberg','standardebooks','openlibrary','doab'] as $key) {
    $assert(in_array($key, DiscoveryRegistry::keys(), true), 'Source manquante : ' . $key);
}

$bnfXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <srw:records><srw:record><srw:recordData><oai_dc:dc>
    <dc:title>Germinal</dc:title><dc:creator>Émile Zola</dc:creator><dc:date>1885</dc:date>
    <dc:language>fre</dc:language><dc:identifier>ark:/12148/cb123456789</dc:identifier>
  </oai_dc:dc></srw:recordData></srw:record></srw:records>
</srw:searchRetrieveResponse>
XML;

$bnfResults = (new BnfDiscoverySource(new HttpClient()))->parse($bnfXml);
$assert(count($bnfResults) === 1 && $bnfResults[0]['title'] === 'Germinal', 'Parseur découverte BnF invalide.');

$opdsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Catalogue test</title>
  <entry>
    <id>urn:test:germinal</id><title>Germinal</title><author><name>Émile Zola</name></author>
    <link rel="alternate" type="text/html" href="https://example.invalid/germinal"/>
    <link rel="http://opds-spec.org/acquisition/open-access" type="application/epub+zip" href="https://example.invalid/germinal.epub"/>
  </entry>
</feed>
XML;

$opds = new OpdsDiscoverySource('test', 'Catalogue test', 'https://example.invalid/opds/', 'Test', new HttpClient());
$opdsResults = $opds->parseFeed($opdsXml);
$assert(count($opdsResults) === 1 && $opdsResults[0]['download_url'] === 'https://example.invalid/germinal.epub', 'Parseur OPDS invalide.');

$temp = sys_get_temp_dir() . '/librairie-discovery-' . bin2hex(random_bytes(4));
$cache = new DiscoveryCache($temp, 3600);
$calls = 0;
$cache->remember('test', 'Germinal', static function () use (&$calls): array { $calls++; return [['title'=>'Germinal']]; });
$second = $cache->remember('test', 'Germinal', static function () use (&$calls): array { $calls++; return []; });
$assert($calls === 1 && $second['cached'] === true, 'Cache de découverte invalide.');
foreach (glob($temp . '/search-cache/*') ?: [] as $file) @unlink($file);
@rmdir($temp . '/search-cache');
@rmdir($temp);

fwrite(STDOUT, "Recherche fédérée et sources externes: OK\n");
