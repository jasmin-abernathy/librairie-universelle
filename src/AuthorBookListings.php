<?php
declare(strict_types=1);

/**
 * Référencement de livres proposés par leurs auteurs.
 * Aucune réception de manuscrit, aucune commande ni aucun paiement.
 * La publication est toujours une action explicite de l'administration.
 */
final class AuthorBookListings
{
    public function __construct(private PDO $pdo, private ?OfficialSiretLookup $officialLookup = null) {}

    public static function normalize(array $input): array
    {
        $get = static function (string $key, int $max, bool $multiline = false) use ($input): string {
            $raw = $input[$key] ?? '';
            if (!is_string($raw)) {
                throw new InvalidArgumentException('Champ invalide : ' . $key);
            }
            $value = trim($raw);
            $value = preg_replace($multiline ? '/[ \t]+/u' : '/\s+/u', $multiline ? ' ' : ' ', $value) ?? $value;
            if (mb_strlen($value, 'UTF-8') > $max) {
                throw new InvalidArgumentException('Champ trop long : ' . $key);
            }
            return $value;
        };
        $title = $get('title', 200);
        $author = $get('author_name', 140);
        $desc = $get('description', 1600, true);
        $subtitle = $get('subtitle', 200);
        if (mb_strlen($title, 'UTF-8') < 2 || mb_strlen($author, 'UTF-8') < 2 || mb_strlen($desc, 'UTF-8') < 30) {
            throw new InvalidArgumentException('Indiquez le titre, le nom public de l’auteur et un résumé d’au moins 30 caractères.');
        }
        $format = $get('book_format', 10);
        if (!in_array($format, ['paper', 'ebook', 'both'], true)) {
            throw new InvalidArgumentException('Choisissez un format de livre.');
        }
        $sellerType = $get('seller_type', 20);
        if (!in_array($sellerType, ['individual', 'professional'], true)) {
            throw new InvalidArgumentException('Indiquez si vous vendez comme particulier ou professionnel.');
        }
        $siret = null;
        if ($sellerType === 'professional') {
            $siret = preg_replace('/[\\s.-]+/', '', $get('siret', 32)) ?? '';
            if (!preg_match('/^[0-9]{14}$/D', $siret)) {
                throw new InvalidArgumentException('Le SIRET à 14 chiffres est obligatoire pour les professionnels.');
            }
            $selected = preg_replace('/[\\s.-]+/', '', $get('selected_company_siret', 32)) ?? '';
            if ($selected !== $siret) {
                throw new InvalidArgumentException('Recherchez puis sélectionnez votre entreprise dans le registre officiel avant de continuer.');
            }
        }
        $sellerLegalName = $get('seller_legal_name', 150);
        $sellerPostalAddress = $get('seller_postal_address', 350, true);
        $sellerPhone = $get('seller_phone', 32);
        if (mb_strlen($sellerLegalName) < 3 || mb_strlen($sellerPostalAddress) < 12) {
            throw new InvalidArgumentException('Indiquez votre identité légale et votre adresse postale privée pour préparer la convention de référencement.');
        }
        if ($sellerPhone !== '' && !preg_match('/^[+0-9 ()\.\-]{6,32}$/D', $sellerPhone)) {
            throw new InvalidArgumentException('Numéro de téléphone invalide.');
        }
        $email = $get('private_email', 254);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse e-mail privée invalide.');
        }
        $publicEmail = $get('public_email', 254);
        if ($publicEmail !== '' && !filter_var($publicEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse e-mail publique invalide.');
        }
        $url = $get('seller_url', 500);
        if ($url !== '') {
            $p = parse_url($url);
            if (!is_array($p) || strtolower((string)($p['scheme'] ?? '')) !== 'https'
                || empty($p['host']) || isset($p['user']) || isset($p['pass'])
                || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/iD', (string)$p['host'])) {
                throw new InvalidArgumentException('Le lien vendeur doit être une URL HTTPS publique valide.');
            }
        }
        if ($publicEmail === '' && $url === '') {
            throw new InvalidArgumentException('Ajoutez une adresse de contact publique ou un lien HTTPS vers votre boutique.');
        }
        $isbn = preg_replace('/[\s-]+/', '', $get('isbn', 24)) ?? '';
        if ($isbn !== '' && (!class_exists('Isbn') || !Isbn::isValid($isbn))) {
            throw new InvalidArgumentException('Cet ISBN n’est pas valide. Vous pouvez laisser le champ vide.');
        }
        $priceRaw = str_replace(',', '.', $get('price', 12));
        if (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $priceRaw)) {
            throw new InvalidArgumentException('Indiquez un prix en euros (par exemple 15 ou 15,50).');
        }
        $parts = explode('.', $priceRaw, 2);
        $priceCents = (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
        if ($priceCents > 99999999) {
            throw new InvalidArgumentException('Prix trop élevé.');
        }
        $ebookPriceCents = null;
        if ($format === 'both') {
            $raw = str_replace(',', '.', $get('ebook_price', 12));
            if (!preg_match('/^\\d{1,6}(?:\\.\\d{1,2})?$/D', $raw)) {
                throw new InvalidArgumentException('Indiquez le prix public du livre numérique (0 si gratuit).');
            }
            $parts = explode('.', $raw, 2);
            $ebookPriceCents = (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
            if ($ebookPriceCents > 99999999) throw new InvalidArgumentException('Prix numérique trop élevé.');
        }
        foreach (['rights_confirmed', 'public_contact_confirmed', 'seller_responsibility_confirmed'] as $confirmation) {
            if (($input[$confirmation] ?? null) !== '1') {
                throw new InvalidArgumentException('Veuillez confirmer vos droits, la publication des coordonnées et la vente directe.');
            }
        }
        return [
            'title' => $title, 'subtitle' => $subtitle, 'author_name' => $author,
            'description' => $desc, 'book_format' => $format, 'isbn' => $isbn,
            'price_cents' => $priceCents, 'ebook_price_cents' => $ebookPriceCents, 'seller_type' => $sellerType, 'siret' => $siret,
            'private_email' => $email, 'public_email' => $publicEmail,
            'seller_url' => $url, 'seller_legal_name' => $sellerLegalName,
            'seller_postal_address' => $sellerPostalAddress,
            'seller_phone' => $sellerPhone,
            'seller_key' => self::sellerKey($sellerType, $email, $sellerLegalName, $siret),
        ];
    }

    private static function sellerKey(string $type, string $email, string $name, ?string $siret): string
    {
        if ($type === 'professional') return 'professional:' . (string)$siret;
        return 'individual:' . hash('sha256',
            mb_strtolower(trim($email), 'UTF-8') . '|' . mb_strtolower(trim($name), 'UTF-8'));
    }

    public function contractData(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM author_book_listings WHERE id=:id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Une signature vérifiée couvre toutes les fiches portant la même clé vendeur. */
    public function recordSignature(int $id, string $reference): void
    {
        $book = $this->contractData($id);
        if ($book === null) throw new InvalidArgumentException('Fiche introuvable.');
        if (empty($book['seller_key']) || empty($book['seller_legal_name']) || empty($book['seller_postal_address'])) {
            throw new InvalidArgumentException('Identité ou adresse privée manquante : demandez ces informations au vendeur avant signature.');
        }
        $reference = trim($reference);
        if (mb_strlen($reference, 'UTF-8') < 3 || mb_strlen($reference, 'UTF-8') > 160) {
            throw new InvalidArgumentException('Indiquez une référence vérifiable du document signé (3 à 160 caractères).');
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO seller_agreements(seller_key,signed_at,signature_reference)
             VALUES(:key,CURRENT_TIMESTAMP,:reference)
             ON CONFLICT(seller_key) DO UPDATE SET
                 signed_at=CURRENT_TIMESTAMP,signature_reference=excluded.signature_reference,updated_at=CURRENT_TIMESTAMP'
        );
        $stmt->execute([':key'=>$book['seller_key'],':reference'=>$reference]);
    }

    public function submit(array $input): int
    {
        $item = self::normalize($input);
        $registeredCompanyName = null;
        $siretVerifiedAt = null;
        if ($item['seller_type'] === 'professional') {
            // Re-vérification côté serveur : aucune valeur JS ni champ caché ne fait foi.
            $official = ($this->officialLookup ?? new OfficialSiretLookup())->search((string)$item['siret']);
            if ($official['siret'] !== $item['siret']) {
                throw new RuntimeException('Le SIRET sélectionné ne correspond pas au registre officiel.');
            }
            $registeredCompanyName = $official['name'];
            $siretVerifiedAt = gmdate('Y-m-d H:i:s');
        }
        // Limite conservatrice sans collecte d'IP : 3 demandes / adresse / 24 heures.
        $rate = $this->pdo->prepare(
            "SELECT COUNT(*) FROM author_book_listings WHERE private_email = :email AND submitted_at > datetime('now','-1 day')"
        );
        $rate->execute([':email' => $item['private_email']]);
        if ((int)$rate->fetchColumn() >= 3) {
            throw new RuntimeException('Trois propositions maximum par adresse sur 24 heures.');
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO author_book_listings
            (status,title,subtitle,author_name,description,book_format,isbn,price_cents,ebook_price_cents,seller_type,siret,registered_company_name,siret_verified_at,
             private_email,seller_key,seller_legal_name,seller_postal_address,seller_phone,public_email,seller_url,rights_confirmed,public_contact_confirmed,seller_responsibility_confirmed)
             VALUES
            (\'pending\',:title,:subtitle,:author_name,:description,:book_format,:isbn,:price_cents,:ebook_price_cents,:seller_type,:siret,:registered_company_name,:siret_verified_at,
             :private_email,:seller_key,:seller_legal_name,:seller_postal_address,:seller_phone,:public_email,:seller_url,1,1,1)'
        );
        $stmt->execute([
            ':title'=>$item['title'], ':subtitle'=>$item['subtitle'] ?: null,
            ':author_name'=>$item['author_name'], ':description'=>$item['description'],
            ':book_format'=>$item['book_format'], ':isbn'=>$item['isbn'] ?: null,
            ':price_cents'=>$item['price_cents'], ':ebook_price_cents'=>$item['ebook_price_cents'], ':seller_type'=>$item['seller_type'],
            ':siret'=>$item['siret'], ':registered_company_name'=>$registeredCompanyName,
            ':siret_verified_at'=>$siretVerifiedAt,
            ':private_email'=>$item['private_email'],
            ':seller_key'=>$item['seller_key'],
            ':seller_legal_name'=>$item['seller_legal_name'],
            ':seller_postal_address'=>$item['seller_postal_address'],
            ':seller_phone'=>$item['seller_phone'] ?: null,
            ':public_email'=>$item['public_email'] ?: null,
            ':seller_url'=>$item['seller_url'] ?: null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function visible(?int $id = null, string $query = '', int $limit = 24): array
    {
        $where = "status = 'published' AND EXISTS (
            SELECT 1 FROM seller_agreements sa
            WHERE sa.seller_key=author_book_listings.seller_key AND sa.signed_at IS NOT NULL
        )";
        $params = [];
        if ($id !== null) {
            $where .= ' AND id = :id';
            $params[':id'] = $id;
        }
        if ($query !== '') {
            $where .= ' AND (title LIKE :title_search OR author_name LIKE :author_search)';
            $pattern = '%' . mb_substr($query, 0, 80) . '%';
            $params[':title_search'] = $pattern;
            $params[':author_search'] = $pattern;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id,title,subtitle,author_name,description,book_format,isbn,price_cents,ebook_price_cents,
               seller_type,siret,registered_company_name,siret_verified_at,public_email,seller_url,reviewed_at
             FROM author_book_listings WHERE ' . $where .
            ' ORDER BY reviewed_at DESC,id DESC LIMIT ' . max(1, min(100, $limit))
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Livres numériques proposés directement par des auteurs, sans dépôt EPUB.
     * Ne publier que les fiches déjà approuvées ; le paiement reste externe.
     */
    public function visibleEbooks(string $query = '', string $mode = 'all', int $limit = 24): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT id,title,subtitle,author_name,description,book_format,isbn,price_cents,ebook_price_cents,
                seller_type,public_email,seller_url,reviewed_at,
                CASE WHEN book_format='both' THEN COALESCE(ebook_price_cents,price_cents)
                ELSE price_cents END AS digital_price_cents
                FROM author_book_listings
                WHERE status='published' AND book_format IN ('ebook','both')
                AND EXISTS (SELECT 1 FROM seller_agreements sa
                    WHERE sa.seller_key=author_book_listings.seller_key AND sa.signed_at IS NOT NULL)";
        $params = [];
        $digitalPrice = "CASE WHEN book_format='both' THEN COALESCE(ebook_price_cents,price_cents) ELSE price_cents END";
        if ($mode === 'free') {
            $sql .= " AND ".$digitalPrice." = 0";
        } elseif ($mode === 'paid') {
            $sql .= " AND ".$digitalPrice." > 0";
        }
        $query = trim(mb_substr($query, 0, 80));
        if ($query !== '') {
            $sql .= ' AND (title LIKE :title_query OR author_name LIKE :author_query)';
            $params[':title_query'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
            $params[':author_query'] = $params[':title_query'];
        }
        $sql .= ' ORDER BY reviewed_at DESC,id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function pendingReview(?string $status = null): array
    {
        $states = ['pending','published','rejected','hidden'];
        $status = in_array($status, $states, true) ? $status : 'pending';
        $stmt = $this->pdo->prepare(
            'SELECT b.*,a.signed_at AS agreement_signed_at,a.signature_reference AS agreement_signature_reference
               FROM author_book_listings b LEFT JOIN seller_agreements a ON a.seller_key=b.seller_key
               WHERE b.status = :status ORDER BY b.submitted_at DESC,b.id DESC LIMIT 150'
        );
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll();
    }

    public function counts(): array
    {
        $counts = ['pending'=>0,'published'=>0,'rejected'=>0,'hidden'=>0];
        foreach ($this->pdo->query('SELECT status,COUNT(*) n FROM author_book_listings GROUP BY status') as $row) {
            $counts[(string)$row['status']] = (int)$row['n'];
        }
        return $counts;
    }

    public function moderate(int $id, string $action, string $note = ''): bool
    {
        $states = ['publish'=>'published','reject'=>'rejected','hide'=>'hidden'];
        if ($id < 1 || !isset($states[$action])) {
            throw new InvalidArgumentException('Action de modération inconnue.');
        }
        if ($action === 'publish') {
            // Interdit de publier d'anciennes fiches pro sans vérification officielle.
            $verify = $this->pdo->prepare('SELECT b.seller_type,b.siret,b.registered_company_name,b.siret_verified_at,
                b.seller_key,b.seller_legal_name,b.seller_postal_address,a.signed_at
                FROM author_book_listings b LEFT JOIN seller_agreements a ON a.seller_key=b.seller_key
                WHERE b.id=:id');
            $verify->execute([':id'=>$id]);
            $record = $verify->fetch();
            if (!$record) {
                return false;
            }
            if ($record['seller_type'] === 'professional'
                && (!preg_match('/^[0-9]{14}$/D', (string)($record['siret'] ?? ''))
                    || empty($record['registered_company_name']) || empty($record['siret_verified_at']))) {
                throw new InvalidArgumentException('SIRET non vérifié : la fiche professionnelle ne peut pas être publiée.');
            }
            if (empty($record['seller_key']) || empty($record['seller_legal_name'])
                || empty($record['seller_postal_address']) || empty($record['signed_at'])) {
                throw new InvalidArgumentException('Publication bloquée : convention à 3 % signée et identité du vendeur obligatoires.');
            }
        }
        $note = mb_substr(trim($note), 0, 1000);
        $stmt = $this->pdo->prepare(
            'UPDATE author_book_listings SET status=:status, review_note=:note,
             reviewed_at=CURRENT_TIMESTAMP WHERE id=:id'
        );
        $stmt->execute([':status'=>$states[$action],':note'=>$note ?: null,':id'=>$id]);
        return $stmt->rowCount() === 1;
    }
}
