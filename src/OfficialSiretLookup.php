<?php
declare(strict_types=1);

/**
 * Consultation sans clé de l'API officielle Recherche d'entreprises (DINUM).
 * Vérifie une immatriculation publique ; NE prouve PAS que le demandeur
 * représente effectivement l'entreprise.
 */
final class OfficialSiretLookup
{
    public function __construct(private ?Closure $transport = null) {}

    public function search(string $rawSiret): array
    {
        $siret = preg_replace('/[\s.-]+/', '', $rawSiret) ?? '';
        if (!preg_match('/^\d{14}$/D', $siret)) {
            throw new InvalidArgumentException('Le SIRET doit comporter exactement 14 chiffres.');
        }
        $url = 'https://recherche-entreprises.api.gouv.fr/search?q=' . rawurlencode($siret) . '&per_page=1&minimal=true&include=siege';
        $json = $this->transport !== null ? ($this->transport)($url) : self::request($url);
        if (!is_array($json)) {
            throw new RuntimeException('Le registre officiel est temporairement indisponible.');
        }
        $results = $json['results'] ?? $json['resultats'] ?? [];
        if (!is_array($results)) {
            throw new RuntimeException('Réponse inattendue du registre officiel.');
        }
        foreach ($results as $item) {
            if (!is_array($item) || (string)($item['siren'] ?? '') !== substr($siret, 0, 9)) {
                continue;
            }
            if (($item['etat_administratif'] ?? null) !== 'A') {
                throw new DomainException('L’entreprise n’est pas indiquée comme active dans le registre officiel.');
            }
            $siege = is_array($item['siege'] ?? null) ? $item['siege'] : [];
            if (($siege['siret'] ?? null) === $siret
                && isset($siege['etat_administratif'])
                && $siege['etat_administratif'] !== 'A') {
                throw new DomainException('Cet établissement est indiqué comme fermé.');
            }
            $name = trim((string)($item['nom_complet'] ?? $item['nom_raison_sociale'] ?? $item['denomination'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Le registre ne fournit pas de dénomination exploitable.');
            }
            return [
                'siret' => $siret,
                'siren' => substr($siret, 0, 9),
                'name' => mb_substr($name, 0, 240, 'UTF-8'),
                'city' => ($siege['siret'] ?? null) === $siret
                    ? mb_substr((string)($siege['libelle_commune'] ?? ''), 0, 100, 'UTF-8')
                    : '',
                'source' => 'API Recherche d’entreprises — données publiques DINUM',
            ];
        }
        throw new DomainException('Aucune entreprise diffusible trouvée pour ce SIRET.');
    }

    private static function request(string $url): array
    {
        // Adresse distante fixée par le serveur ; aucune URL extérieure fournie
        // par l'internaute : pas de SSRF ni d'envoi de coordonnées privées.
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('Recherche indisponible.');
            }
            $body = '';
            curl_setopt_array($ch, [
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'LibrairieUniverselle/1.0 (recherche SIRET depuis le formulaire auteur)',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                    if (strlen($body) + strlen($chunk) > 262144) {
                        return 0;
                    }
                    $body .= $chunk;
                    return strlen($chunk);
                },
            ]);
            $ok = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($ok !== true || $status !== 200) {
                throw new RuntimeException($status === 429
                    ? 'Le service officiel est momentanément surchargé. Réessayez dans quelques instants.'
                    : 'Le registre officiel est indisponible. Réessayez ultérieurement.');
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET', 'timeout' => 8, 'follow_location' => 0,
                    'header' => "Accept: application/json\r\nUser-Agent: LibrairieUniverselle/1.0\r\n",
                ],
                'ssl' => ['verify_peer'=>true,'verify_peer_name'=>true],
            ]);
            $body = @file_get_contents($url, false, $context, 0, 262145);
            $httpHeader = $http_response_header[0] ?? '';
            if (!is_string($body) || strlen($body) > 262144 || !preg_match('~^HTTP/\S+\s+200\b~', $httpHeader)) {
                throw new RuntimeException('Le registre officiel est indisponible. Réessayez ultérieurement.');
            }
        }
        $parsed = json_decode($body, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('La réponse du registre officiel est illisible.');
        }
        return $parsed;
    }
}
