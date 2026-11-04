<?php
declare(strict_types=1);

/**
 * Référencement de livres proposés par leurs auteurs.
 * Aucune réception de manuscrit, aucune commande ni aucun paiement.
 * La publication est toujours une action explicite de l'administration.
 */
final class AuthorBookListings
{
    public function __construct(private PDO $pdo) {}

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
        foreach (['rights_confirmed', 'public_contact_confirmed', 'seller_responsibility_confirmed'] as $confirmation) {
            if (($input[$confirmation] ?? null) !== '1') {
                throw new InvalidArgumentException('Veuillez confirmer vos droits, la publication des coordonnées et la vente directe.');
            }
        }
        return [
            'title' => $title, 'subtitle' => $subtitle, 'author_name' => $author,
            'description' => $desc, 'book_format' => $format, 'isbn' => $isbn,
            'price_cents' => $priceCents, 'seller_type' => $sellerType,
            'private_email' => $email, 'public_email' => $publicEmail,
            'seller_url' => $url,
        ];
    }

    public function submit(array $input): int
    {
        $item = self::normalize($input);
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
            (status,title,subtitle,author_name,description,book_format,isbn,price_cents,seller_type,
             private_email,public_email,seller_url,rights_confirmed,public_contact_confirmed,seller_responsibility_confirmed)
             VALUES
            (\'pending\',:title,:subtitle,:author_name,:description,:book_format,:isbn,:price_cents,:seller_type,
             :private_email,:public_email,:seller_url,1,1,1)'
        );
        $stmt->execute([
            ':title'=>$item['title'], ':subtitle'=>$item['subtitle'] ?: null,
            ':author_name'=>$item['author_name'], ':description'=>$item['description'],
            ':book_format'=>$item['book_format'], ':isbn'=>$item['isbn'] ?: null,
            ':price_cents'=>$item['price_cents'], ':seller_type'=>$item['seller_type'],
            ':private_email'=>$item['private_email'],
            ':public_email'=>$item['public_email'] ?: null,
            ':seller_url'=>$item['seller_url'] ?: null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function visible(?int $id = null, string $query = '', int $limit = 24): array
    {
        $where = "status = 'published'";
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
            'SELECT id,title,subtitle,author_name,description,book_format,isbn,price_cents,
               seller_type,public_email,seller_url,reviewed_at
             FROM author_book_listings WHERE ' . $where .
            ' ORDER BY reviewed_at DESC,id DESC LIMIT ' . max(1, min(100, $limit))
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function pendingReview(?string $status = null): array
    {
        $states = ['pending','published','rejected','hidden'];
        $status = in_array($status, $states, true) ? $status : 'pending';
        $stmt = $this->pdo->prepare(
            'SELECT * FROM author_book_listings WHERE status = :status ORDER BY submitted_at DESC,id DESC LIMIT 150'
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
        $note = mb_substr(trim($note), 0, 1000);
        $stmt = $this->pdo->prepare(
            'UPDATE author_book_listings SET status=:status, review_note=:note,
             reviewed_at=CURRENT_TIMESTAMP WHERE id=:id'
        );
        $stmt->execute([':status'=>$states[$action],':note'=>$note ?: null,':id'=>$id]);
        return $stmt->rowCount() === 1;
    }
}
