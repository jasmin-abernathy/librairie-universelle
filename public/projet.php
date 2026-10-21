<?php

declare(strict_types=1);

[$config] = require dirname(__DIR__) . '/src/bootstrap.php';

function e_project(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="description" content="Librairie universelle : trouver un livre près de chez soi, éviter des frais de livraison, favoriser les trajets sobres et compléter les plateformes de librairies indépendantes. Pilote en Moselle.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Le projet — <?= e_project($config['name']) ?>">
    <meta property="og:description" content="Et si le livre le plus rapide à obtenir était déjà près de chez vous ? Un projet local, sobre et complémentaire des réseaux de libraires existants.">
    <title>Le projet — <?= e_project($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
    <link rel="stylesheet" href="/assets/css/projet.css?v=20261021-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'projet'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>

<main id="main" class="project-page">
    <section class="page-hero" aria-labelledby="project-title">
        <p class="eyebrow">Un prototype concret · première étape en Moselle</p>
        <h1 id="project-title">Et si le livre le plus pratique à trouver était celui qui est près de chez vous ?</h1>
        <p class="lede">Librairie universelle veut réunir lecteurs, librairies indépendantes, auteurs et imprimeurs locaux autour d'un parcours simple : trouver un livre, le commander ou le faire imprimer au plus près, sans imposer à chacun une nouvelle usine à gaz.</p>
        <p>Face à Amazon, la proximité peut devenir un avantage très concret : <strong>faire aussi simple et aussi efficace, voire mieux lorsque la proximité le permet</strong>. Un livre déjà en rayon à deux rues peut être récupéré aujourd'hui, sans attendre un colis ni payer son expédition. Et si l'on s'y rend à pied, à vélo ou sur un trajet habituel, l'impact du transport peut aussi diminuer.</p>
        <div class="cta-row">
            <a class="button-link" href="/#librairies">Trouver une librairie proche</a>
            <a class="button-link secondary" href="/autoedition.php">Préparer mon livre</a>
            <a class="text-link" href="#etat-du-projet">Où en est le projet ?</a>
        </div>
    </section>

    <section class="content-section" aria-labelledby="why-local">
        <p class="section-kicker">Pourquoi commencer près de chez soi ?</p>
        <h2 id="why-local">La proximité peut battre la livraison sur son propre terrain.</h2>
        <p>Amazon a popularisé deux arguments : aller vite et limiter les frais de livraison. Mais <strong>un livre déjà disponible chez un libraire voisin n'a pas besoin d'attendre un colis</strong>. Librairie universelle veut rendre cette option plus facile à trouver, sans demander aux libraires de changer leurs outils.</p>
        <div class="feature-grid">
            <article>
                <span aria-hidden="true">1</span>
                <h3>Rapide quand le livre est déjà là</h3>
                <p>Si l'ouvrage est en rayon et que la librairie est ouverte, le retrait peut se faire dans la journée. Le but est d'afficher une disponibilité vérifiée et la date de sa dernière mise à jour, pas de promettre un stock que nous ne connaissons pas encore.</p>
            </article>
            <article>
                <span aria-hidden="true">2</span>
                <h3>Sans frais d'expédition à payer</h3>
                <p>En France, le prix du livre neuf est encadré. Pour une commande expédiée de moins de 35 € en livres neufs, les frais de livraison sont d'au moins 3 €. <strong>Retirer un livre en librairie évite ces frais d'expédition</strong> — sans prétendre que le déplacement du lecteur ne coûte rien.</p>
                <p><a class="text-link" href="https://www.legifrance.gouv.fr/loda/article_lc/LEGIARTI000047419002" target="_blank" rel="noopener noreferrer">Comprendre les règles de livraison des livres →</a></p>
            </article>
            <article>
                <span aria-hidden="true">3</span>
                <h3>Moins de trajets inutiles, quand c'est possible</h3>
                <p>Récupérer à pied, à vélo ou à l'occasion d'un trajet déjà prévu un exemplaire présent sur place peut éviter un colis supplémentaire, son emballage et sa livraison finale. Mais <strong>le local n'est pas automatiquement plus écologique</strong> : un aller-retour dédié en voiture peut annuler cet avantage.</p>
                <p><a class="text-link" href="https://www.ademe.fr/presse/communique-national/e-commerce-un-outil-pour-evaluer-et-reduire-limpact-environnemental-de-la-logistique-des-transports-et-des-deplacements/" target="_blank" rel="noopener noreferrer">Ce que dit l'ADEME sur l'impact des livraisons →</a></p>
            </article>
        </div>
        <p>Nous voulons rendre visibles les <strong>prix, délais, disponibilités et moyens de retrait</strong> pour que chacun puisse choisir en connaissance de cause. Les gains réels de temps, de coût et d'émissions seront à mesurer avec des librairies volontaires, pas annoncés d'avance.</p>
    </section>

    <section class="content-section" aria-labelledby="complementarity-title">
        <p class="section-kicker">Compléter l'existant, pas le remplacer</p>
        <h2 id="complementarity-title">Les plateformes nationales ont leur place. Le lien local aussi.</h2>
        <div class="prose-grid">
            <p>Des réseaux comme <a href="https://www.leslibraires.fr/" target="_blank" rel="noopener noreferrer">leslibraires.fr</a>, <a href="https://www.canalbd.net/" target="_blank" rel="noopener noreferrer">Canal BD</a> et d'autres portails permettent déjà de trouver, réserver et commander des livres, parfois en privilégiant une librairie proche. <strong>Nous ne cherchons ni à copier leurs services ni à détourner les libraires de leurs boutiques actuelles.</strong></p>
            <p>Notre approche commence par une question plus locale : <strong>« Où puis-je obtenir ce livre près de chez moi, dans de bonnes conditions ? »</strong> Si la réponse se trouve sur le site d'un libraire ou d'un réseau existant, le bon service consiste à y orienter le lecteur. Pas à l'obliger à passer par une nouvelle caisse.</p>
        </div>
        <p>Le pilote démarre en Moselle : référencement utile, liens vers les boutiques existantes et, seulement avec l'accord des librairies, connexion progressive aux stocks. Aucune double saisie imposée, aucun partenariat ou flux de stock présenté comme acquis avant de l'être.</p>
    </section>

    <section class="content-section" aria-labelledby="who-title">
        <p class="section-kicker">Pour qui ?</p>
        <h2 id="who-title">Un même réseau, quatre façons d'en profiter.</h2>
        <div class="feature-grid">
            <article><span aria-hidden="true">1</span><h3>Lecteurs et lectrices</h3><p>Chercher une œuvre, découvrir ses éditions, accéder à des ebooks gratuits ou à des offres commerciales identifiées, et repérer les librairies proches.</p></article>
            <article><span aria-hidden="true">2</span><h3>Librairies</h3><p>À terme, rendre leurs stocks visibles sans double saisie, puis recevoir des demandes de réservation ou de commande avec leur accord et dans le respect de leurs outils.</p></article>
            <article><span aria-hidden="true">3</span><h3>Auteurs indépendants</h3><p>Préparer leur livre avec des outils gratuits, proposer un dépôt au catalogue, puis obtenir une fiche et un message pour approcher les librairies locales.</p></article>
            <article><span aria-hidden="true">4</span><h3>Imprimeurs de proximité</h3><p>Recevoir des dossiers mieux préparés et, à terme, participer à un circuit d'impression locale adapté aux besoins des auteurs et des lecteurs.</p></article>
        </div>
    </section>

    <section class="content-section" aria-labelledby="authors-title">
        <p class="section-kicker">Autoédition · déjà à essayer</p>
        <h2 id="authors-title">Un livre ne s'arrête pas à la dernière page du manuscrit.</h2>
        <p>Nous accompagnons les auteurs depuis la préparation de leurs fichiers jusqu'aux premières démarches de diffusion, sans rendre l'accompagnement payant obligatoire.</p>
        <div class="project-journey">
            <article><span>01</span><h3>Créer</h3><p>Composer l'intérieur du livre, préparer les couvertures, générer les PDF et les réunir dans un dossier destiné à l'imprimeur.</p><a href="/autoedition.php?partie=paper">Utiliser les outils gratuits →</a></article>
            <article><span>02</span><h3>Faire imprimer</h3><p>Demander un devis à un imprimeur. Le prototype oriente notamment vers le formulaire officiel de Corep Metz, sans lui envoyer automatiquement de fichiers.</p><a href="/dossier-impression.php">Préparer le dossier →</a></article>
            <article><span>03</span><h3>Faire référencer</h3><p>Déposer une candidature au catalogue : après acceptation humaine et publication par l'équipe, une fiche est créée. <strong>Les dépôts publics sont encore fermés pendant la pré-ouverture.</strong></p><a href="/autoedition.php?partie=submit">Découvrir le dépôt →</a></article>
            <article><span>04</span><h3>Proposer aux librairies</h3><p>Créer une fiche de présentation et un message personnalisé, trouver des librairies proches et discuter avec elles des conditions de commande ou de dépôt-vente.</p><a href="/diffuser-mon-livre.php">Préparer ma diffusion →</a></article>
        </div>
        <p class="project-clarification">Être référencé au catalogue ne signifie pas encore pouvoir vendre directement sur la plateforme. L'impression, les commandes et les accords avec les librairies restent des démarches distinctes à ce stade.</p>
    </section>

    <section class="content-section" aria-labelledby="human-title">
        <p class="section-kicker">Accompagnement facultatif</p>
        <h2 id="human-title">Les outils restent gratuits. Le temps humain, lui, peut être rémunéré.</h2>
        <p>Si un auteur préfère ne pas tout vérifier seul, Le Potager du Web propose deux prestations distinctes, sur devis :</p>
        <div class="project-prices">
            <article><h3>Vérification avant impression</h3><p class="project-price">79 € HT</p><p>Contrôle humain des fichiers et compte rendu des problèmes visibles : pagination, marges, couvertures, lisibilité.</p></article>
            <article><h3>Correction accompagnée</h3><p class="project-price">Dès 179 € HT</p><p>Correction orthographique et grammaticale assistée par logiciel, avec vérification humaine dans un périmètre défini. Il ne s'agit pas d'une relecture intégrale garantie à ce prix.</p></article>
        </div>
        <p><a class="text-link" href="/prestations-auteurs.php">Voir les prestations et demander un devis →</a></p>
    </section>

    <section class="content-section" id="etat-du-projet" aria-labelledby="status-title">
        <p class="section-kicker">Transparence · état actuel du prototype</p>
        <h2 id="status-title">Ce qui fonctionne, ce que nous testons, ce qu'il reste à construire.</h2>
        <div class="project-status-grid">
            <article class="project-status">
                <h3>À essayer aujourd'hui</h3>
                <ul>
                    <li>Recherche par œuvre et fiches regroupant éditions et offres identifiées</li>
                    <li>Rayon d'ebooks gratuits et payants provenant de sources distinctes ; les offres payantes renvoient vers leur vendeur</li>
                    <li>Annuaire de librairies locales à Metz et en Moselle, <strong>sans prétendre connaître leurs stocks en direct</strong></li>
                    <li>Outils gratuits de préparation papier, PDF, couvertures et dossier d'impression</li>
                    <li>Guide de diffusion et création locale d'un message à adresser aux librairies</li>
                    <li>Demandes de devis pour un accompagnement humain facultatif</li>
                </ul>
            </article>
            <article class="project-status">
                <h3>Encore en préparation</h3>
                <ul>
                    <li>Ouverture du dépôt public des auteurs, avec contrôle éditorial humain avant mise au catalogue</li>
                    <li>Premières impressions et commandes testées avec de vrais auteurs et imprimeurs</li>
                    <li>Connexion autorisée aux logiciels et stocks des librairies volontaires</li>
                    <li>Réservation, commande, règlement et retrait local coordonnés depuis la plateforme</li>
                    <li>Vente intégrée des ebooks payants déposés par les auteurs</li>
                    <li>Extension progressive à d'autres territoires, après validation du pilote mosellan</li>
                </ul>
            </article>
        </div>
        <p class="note"><strong>Nous n'annonçons ni stock en temps réel, ni vente intégrée, ni partenariat contractuel qui n'existent pas encore.</strong> Corep Metz est cité aujourd'hui comme option de demande de devis d'impression ; une intégration commerciale ou technique éventuelle sera présentée lorsqu'elle sera réellement conclue.</p>
    </section>

    <section class="content-section callout" aria-labelledby="trust-title">
        <div>
            <p class="section-kicker">Nos principes</p>
            <h2 id="trust-title">Un service ouvert et compréhensible, sans vous observer.</h2>
            <p>Le projet est développé en logiciel libre. Il distingue les œuvres, leurs éditions et les offres, identifie les sources et n'utilise ni profilage publicitaire ni classement opaque. La recherche, les recommandations et la sélection éditoriale ne sont pas déléguées à une IA. Les droits des auteurs sont respectés et les cas incertains restent signalés.</p>
            <p>Le développement privilégie la sobriété, l'accessibilité et des interfaces utilisables sans compte lorsque c'est possible.</p>
        </div>
        <a class="button-link secondary" href="/sans-ia.php">Comprendre notre démarche sans IA</a>
    </section>
</main>

<footer>
    <div class="footer-links"><a href="/">Chercher un livre</a><a href="/ebooks.php">Ebooks</a><a href="/autoedition.php">Autoédition</a><a href="/diffuser-mon-livre.php">Vendre mon livre</a><a href="/prestations-auteurs.php">Accompagnement</a></div>
    <p>Prototype en construction — Librairie universelle est un nom de travail. Premier territoire d'expérimentation : la Moselle.</p>
</footer>
</body>
</html>
