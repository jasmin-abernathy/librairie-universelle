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
            ['theme' => "Présentation", 'question' => "Sous quel nom, avec quelle fonction et quels engagements professionnels souhaitez-vous être présentée dans cette nouvelle enquête ?", 'required' => true],
            ['theme' => "Présentation", 'question' => "Pour une personne qui ne connaît pas la librairie Georges aujourd’hui, quels aspects de son identité et de son activité souhaiteriez-vous mettre en avant ?", 'required' => true],
            ['theme' => "2014, les projets et leurs suites", 'question' => "En 2014, vous reveniez sur l’échec de 1001libraires et le manque d’information dont disposaient les libraires associés. Avec le recul, quelle leçon en tirez-vous sur la gouvernance des projets collectifs ?", 'required' => false],
            ['theme' => "2014, les projets et leurs suites", 'question' => "Vous racontiez une seconde tentative de site marchand avec Electre, interrompue après plusieurs mois de travail et de dépenses. Quels critères vous sembleraient aujourd’hui indispensables avant de choisir un prestataire numérique ?", 'required' => false],
            ['theme' => "2014, les projets et leurs suites", 'question' => "Vous vous tourniez alors vers Tite-Live pour la boutique en ligne et vers ePagine pour les ebooks. Que sont devenus ces dispositifs chez Georges ? Qu’avez-vous conservé, remplacé ou abandonné, et pourquoi ?", 'required' => false],
            ['theme' => "2014, les projets et leurs suites", 'question' => "Vous décriviez Place des Libraires comme un moyen de réserver un ouvrage dans la librairie la plus proche, puis de choisir entre retrait et expédition. Cette promesse s’est-elle réalisée dans les usages et les résultats économiques ?", 'required' => false],
            ['theme' => "Autonomie et visibilité", 'question' => "En 2014, vous voyiez un avantage important à déléguer la maintenance technique, les mises à jour éditoriales et le service après-vente numérique. Aujourd’hui, quelles tâches sont encore externalisées et à quel prix en temps, en argent ou en autonomie ?", 'required' => false],
            ['theme' => "Autonomie et visibilité", 'question' => "Vous expliquiez qu’un ancien blog de la librairie remontait parfois devant Amazon dans les moteurs de recherche. En 2026, comment les lecteurs découvrent-ils Georges en ligne et quels efforts de communication produisent réellement des visites ou des ventes ?", 'required' => false],
            ['theme' => "Le quotidien d’une librairie", 'question' => "Vous comptiez notamment sur les étudiants et les enseignants de Talence pour développer les commandes en ligne. Cette clientèle a-t-elle changé ses pratiques en douze ans ? Qu’en est-il des autres générations de lecteurs ?", 'required' => false],
            ['theme' => "Le quotidien d’une librairie", 'question' => "Vous souligniez déjà le paradoxe du temps passé derrière les écrans et les journées très longues des petites équipes. La numérisation vous a-t-elle finalement fait gagner du temps ou déplacé la charge de travail ?", 'required' => false],
            ['theme' => "Le quotidien d’une librairie", 'question' => "Vous compariez les facilités d’approvisionnement de Georges aux difficultés des librairies livrées seulement quelques fois par semaine. Les stocks, les délais, les frais d’expédition et les retours restent-ils les principales limites face aux plateformes ?", 'required' => false],
            ['theme' => "Mutualisation et économie", 'question' => "En 2014, vous souhaitiez, avec les Librairies Atlantiques, aider d’autres libraires à disposer d’une présence en ligne et de formations. Qu’est devenue cette ambition et quelles actions collectives se sont révélées les plus utiles ?", 'required' => false],
            ['theme' => "Mutualisation et économie", 'question' => "Vous citiez à l’époque des aides régionales et nationales pouvant soutenir la modernisation des commerces. Ces dispositifs répondent-ils mieux aujourd’hui aux besoins des librairies, notamment les plus petites, ou certains coûts restent-ils mal pris en compte ?", 'required' => false],
            ['theme' => "Livres et responsabilités nouvelles", 'question' => "Quelle place occupent aujourd’hui les livres numériques, les livres audio, l’occasion et l’impression à la demande ? Ces modèles renforcent-ils la liberté de choix des lecteurs et l’indépendance des libraires ?", 'required' => false],
            ['theme' => "Livres et responsabilités nouvelles", 'question' => "Du transport des ouvrages aux retours et au pilon, quels changements ont réellement réduit l’empreinte environnementale de votre activité ? Et comment voyez-vous les nouveaux intermédiaires de découverte, notamment les recommandations par IA ?", 'required' => false],
            ['theme' => "Douze ans après", 'question' => "Vous insistiez en 2014 sur le besoin humain de rencontrer, discuter et débattre en librairie. Ce rôle du Café de Georges vous paraît-il encore plus important aujourd’hui ? Si vous vous adressiez à la professionnelle que vous étiez en 2014, quel conseil lui donneriez-vous pour les douze années suivantes ?", 'required' => false],
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
