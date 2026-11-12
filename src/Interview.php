<?php
declare(strict_types=1);

/**
 * Entretiens écrits privés de la Librairie universelle.
 * Les réponses résident uniquement dans SQLite privé, jamais dans Git.
 */
final class Interview
{
    public const CECILE_ID = 'cecile-bory-saillant-2026';

    public static function questions(): array
    {
        return [
            ['theme' => 'Présentation', 'question' => 'Sous quel nom et avec quelle fonction souhaitez-vous être présentée dans cette nouvelle enquête ?', 'required' => true],
            ['theme' => 'Présentation', 'question' => 'Comment présenteriez-vous aujourd’hui la librairie Georges à une personne qui ne la connaît pas ?', 'required' => true],
            ['theme' => 'Douze ans après', 'question' => 'Nous avions échangé pour mon mémoire de 2014 sur les librairies face au numérique. En relisant cette période, quelles attentes vous semblent s’être confirmées, et lesquelles se sont révélées trompeuses ?', 'required' => false],
            ['theme' => 'Douze ans après', 'question' => 'Parmi les initiatives numériques de la librairie depuis 2014, lesquelles ont réellement changé le quotidien de votre équipe ou les habitudes de vos clients ? Lesquelles avez-vous abandonnées ?', 'required' => false],
            ['theme' => 'Outils numériques et indépendance', 'question' => 'Aujourd’hui, que vous apporte concrètement la vente ou la réservation en ligne ? Quelles charges nouvelles — logiciels, logistique, mise à jour des stocks, service client — entraîne-t-elle ?', 'required' => false],
            ['theme' => 'Outils numériques et indépendance', 'question' => 'Entre un site propre et un portail collectif de libraires, comment arbitrez-vous la visibilité, les coûts et la maîtrise de la relation avec vos lecteurs ?', 'required' => false],
            ['theme' => 'Outils numériques et indépendance', 'question' => 'Quand un service numérique est utilisé par la librairie, quels critères sont désormais essentiels : interopérabilité des stocks, propriété des données, facilité de maintenance, autonomie de l’équipe, autre chose ?', 'required' => false],
            ['theme' => 'Économie du métier', 'question' => 'Quelles sont les contraintes économiques que le public perçoit le moins bien : remises commerciales, retours, trésorerie, assortiment, délais ou temps de travail ?', 'required' => false],
            ['theme' => 'Économie du métier', 'question' => 'En tant que présidente de Librairies indépendantes en Nouvelle-Aquitaine, quelles formes de mutualisation ont produit des effets mesurables pour les libraires, et où restent les principaux blocages ?', 'required' => false],
            ['theme' => 'Livre, lecteurs et territoires', 'question' => 'La librairie Georges est aussi un lieu de rencontres et de débats. Comment évaluez-vous la valeur de cette activité culturelle, et comment la financez-vous sans qu’elle repose sur du travail invisible ?', 'required' => false],
            ['theme' => 'Livre, lecteurs et territoires', 'question' => 'Avez-vous observé depuis 2014 des changements significatifs dans la clientèle, les usages de commande, la place des étudiants ou la découverte de livres moins exposés ?', 'required' => false],
            ['theme' => 'Nouveaux marchés et enjeux', 'question' => 'Quelle place les livres numériques, les livres audio et les abonnements occupent-ils aujourd’hui dans votre réflexion ? Les limitations de format ou de droits d’usage sont-elles un frein ?', 'required' => false],
            ['theme' => 'Nouveaux marchés et enjeux', 'question' => 'Le livre d’occasion, l’autoédition et l’impression à la demande modifient les circuits du livre. Où voyez-vous des occasions de coopération et, au contraire, des risques pour les libraires ?', 'required' => false],
            ['theme' => 'Nouveaux marchés et enjeux', 'question' => 'Sur les questions écologiques — livraisons, retours, pilon, numérique — quels leviers vous semblent vraiment efficaces plutôt que simplement séduisants en communication ?', 'required' => false],
            ['theme' => 'Nouveaux marchés et enjeux', 'question' => 'Les outils d’intelligence artificielle et les moteurs de recommandation changent-ils déjà la visibilité des librairies ou la découverte des livres ? Qu’aimeriez-vous préserver du conseil humain ?', 'required' => false],
            ['theme' => 'Perspectives', 'question' => 'Si vous pouviez modifier une règle ou un rapport de force dans la chaîne du livre d’ici 2030, lequel choisiriez-vous ? Et quel conseil donneriez-vous aux jeunes libraires ?', 'required' => false],
        ];
    }

    public static function ensureCecileDraft(PDO $pdo): array
    {
        $existing = self::find($pdo, self::CECILE_ID);
        if ($existing !== null) return $existing;
        try {
            $stmt = $pdo->prepare("INSERT INTO editorial_interviews
                (id,title,recipient_name,recipient_email,status,answers_json)
                VALUES (?, ?, ?, ?, 'draft', '{}')");
            $stmt->execute([
                self::CECILE_ID,
                'Douze ans après : Cécile Bory-Saillant et l’avenir de la librairie indépendante',
                'Cécile Bory-Saillant',
                'direction@librairiegeorges.fr',
            ]);
        } catch (PDOException $e) {
            // Deux administrateurs peuvent ouvrir cette page au même instant.
            if (self::find($pdo, self::CECILE_ID) === null) throw $e;
        }
        return self::find($pdo, self::CECILE_ID)
            ?? throw new RuntimeException('Entretien non créé.');
    }

    public static function find(PDO $pdo, string $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM editorial_interviews WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function answers(array $row): array
    {
        $data = json_decode((string)$row['answers_json'], true);
        return is_array($data) ? $data : [];
    }

    public static function link(array $row, array $config): string
    {
        $secret = (string)($config['admin_token'] ?? '');
        if (strlen($secret) < 16) throw new RuntimeException('ADMIN_TOKEN doit être configuré avec un secret long.');
        $token = hash_hmac('sha256', 'librairie-interview-v1:'.$row['id'].':'.$row['token_version'], $secret);
        return 'https://librairie.lepotager.org/interview.php?i='
            .rawurlencode((string)$row['id']).'&k='.$token;
    }

    public static function canOpen(array $row, array $config, string $key): bool
    {
        if (!in_array($row['status'], ['invited', 'in_progress', 'submitted'], true)) return false;
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $key)) return false;
        $expected = (string)parse_url(self::link($row, $config), PHP_URL_QUERY);
        parse_str($expected, $query);
        return isset($query['k']) && hash_equals((string)$query['k'], $key);
    }

    public static function saveAnswer(PDO $pdo, array $row, int $cursor, string $answer, string $direction): void
    {
        $questions = self::questions();
        if (!in_array($row['status'], ['invited','in_progress'], true)
            || $cursor !== (int)$row['cursor']
            || !isset($questions[$cursor])
            || !in_array($direction, ['next', 'back'], true)) {
            throw new RuntimeException('Cette étape ne peut plus être modifiée. Rechargez la page.');
        }
        $answer = trim(str_replace("\r", '', $answer));
        if (strlen($answer) > 18000) throw new RuntimeException('Réponse trop longue : 18 000 octets au maximum.');
        if ($direction === 'next' && $questions[$cursor]['required'] && $answer === '') {
            throw new RuntimeException('Merci de renseigner cette présentation.');
        }
        $answers = self::answers($row);
        $answers[(string)$cursor] = $answer;
        $next = $direction === 'next' ? $cursor+1 : max(0, $cursor-1);
        $stmt = $pdo->prepare("UPDATE editorial_interviews SET answers_json=?,cursor=?,status='in_progress',
            updated_at=CURRENT_TIMESTAMP WHERE id=? AND cursor=? AND status IN ('invited','in_progress')");
        $stmt->execute([json_encode($answers, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$next,$row['id'],$cursor]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Modification concurrente : rechargez la page.');
    }
}
