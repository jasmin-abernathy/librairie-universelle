<?php
declare(strict_types=1);

/**
 * Référencement multicanal : une édition (ou fiche auteur) peut avoir
 * plusieurs destinations publiques confirmées.
 * Ni les prix ni les stocks ne sont inférés d'une URL.
 */
final class DistributionLinks
{
    public function __construct(private PDO $pdo) {}

    public function forWork(int $workId): array
    {
        $q=$this->pdo->prepare(<<<'SQL'
SELECT dl.*, e.isbn13, e.title AS edition_title, e.medium AS edition_medium
FROM distribution_links dl
JOIN editions e ON e.id=dl.edition_id
WHERE e.work_id=:work AND dl.status='checked'
ORDER BY CASE dl.channel WHEN 'independent_portal' THEN 0 WHEN 'bookseller' THEN 1
    WHEN 'publisher' THEN 2 WHEN 'author' THEN 3 ELSE 4 END,
    dl.platform COLLATE NOCASE, dl.id
SQL);
        $q->execute([':work'=>$workId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forAuthorBook(int $bookId): array
    {
        $q=$this->pdo->prepare(<<<'SQL'
SELECT dl.* FROM distribution_links dl
JOIN author_book_listings ab ON ab.id=dl.author_book_id
WHERE ab.id=:book AND ab.status='published' AND dl.status='checked'
ORDER BY CASE dl.channel WHEN 'independent_portal' THEN 0 WHEN 'bookseller' THEN 1
    WHEN 'publisher' THEN 2 WHEN 'author' THEN 3 ELSE 4 END,
    dl.platform COLLATE NOCASE, dl.id
SQL);
        $q->execute([':book'=>$bookId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recent(int $limit=100): array
    {
        $limit=max(1,min($limit,200));
        return $this->pdo->query(<<<SQL
SELECT dl.*, e.isbn13, e.title AS edition_title, w.title AS work_title,
       ab.title AS author_book_title
FROM distribution_links dl
LEFT JOIN editions e ON e.id=dl.edition_id
LEFT JOIN works w ON w.id=e.work_id
LEFT JOIN author_book_listings ab ON ab.id=dl.author_book_id
ORDER BY dl.id DESC LIMIT $limit
SQL)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(array $input): void
    {
        $scope=(string)($input['scope']??'');
        $id=filter_var($input['book_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!in_array($scope,['edition','author_book'],true)||$id===false||$id===null){
            throw new InvalidArgumentException('Choisir une édition ou une fiche auteur valide.');
        }
        $exists=$this->pdo->prepare($scope==='edition'
            ? 'SELECT 1 FROM editions WHERE id=?'
            : "SELECT 1 FROM author_book_listings WHERE id=? AND status='published'");
        $exists->execute([(int)$id]);
        if(!$exists->fetchColumn())throw new InvalidArgumentException('Livre absent ou fiche auteur non publiée.');

        $platform=trim((string)($input['platform']??''));
        if($platform===''||mb_strlen($platform,'UTF-8')>100||preg_match('/[<>\x00-\x1f]/u',$platform)){
            throw new InvalidArgumentException('Nom de plateforme invalide (100 caractères maximum).');
        }
        $channel=(string)($input['channel']??'');
        if(!in_array($channel,['independent_portal','bookseller','publisher','author','other'],true)){
            throw new InvalidArgumentException('Type de circuit inconnu.');
        }
        $medium=(string)($input['medium']??'');
        if(!in_array($medium,['paper','ebook','audio','other'],true)){
            throw new InvalidArgumentException('Format inconnu.');
        }
        $url=trim((string)($input['url']??''));
        if(strlen($url)>1000||!filter_var($url,FILTER_VALIDATE_URL)){
            throw new InvalidArgumentException('URL invalide.');
        }
        $parts=parse_url($url);
        if(!is_array($parts)||strtolower((string)($parts['scheme']??''))!=='https'
           ||!isset($parts['host'])||isset($parts['user'])||isset($parts['pass'])
           ||isset($parts['fragment'])||filter_var($parts['host'],FILTER_VALIDATE_IP)
           ||!preg_match('/^(?=.{4,253}$)[a-z0-9-]+(?:\.[a-z0-9-]+)*\.[a-z]{2,}$/iD',(string)$parts['host'])
           ||preg_match('/(?:^|\.)localhost$/i',(string)$parts['host'])){
            throw new InvalidArgumentException('Lien HTTPS public requis, sans identifiants ni ancre.');
        }
        if(($input['verified']??null)!=='1'){
            throw new InvalidArgumentException('Ouvrez la fiche réelle du livre, vérifiez le lien, puis cochez la confirmation.');
        }
        $edition=$scope==='edition'?(int)$id:null;
        $author=$scope==='author_book'?(int)$id:null;
        $q=$this->pdo->prepare(<<<'SQL'
INSERT INTO distribution_links
    (edition_id, author_book_id, platform, channel, medium, url, status, checked_at)
VALUES (?,?,?,?,?,?,'checked',CURRENT_TIMESTAMP)
SQL);
        try {
            $q->execute([$edition,$author,$platform,$channel,$medium,$url]);
        }catch(PDOException $e){
            if(str_contains(strtolower($e->getMessage()),'unique')) {
                throw new InvalidArgumentException('Ce lien est déjà rattaché à cette édition ou fiche.');
            }
            throw $e;
        }
    }

    public function hide(int $id): void
    {
        $q=$this->pdo->prepare("UPDATE distribution_links SET status='hidden' WHERE id=?");
        $q->execute([$id]);
        if($q->rowCount()===0)throw new InvalidArgumentException('Lien introuvable ou déjà masqué.');
    }

    public function restore(int $id): void
    {
        // Pas de réactivation sans vérification récente du lien.
        throw new InvalidArgumentException('Ajoutez un nouveau lien contrôlé ; la vérification précédente ne vaut plus.');
    }
}
