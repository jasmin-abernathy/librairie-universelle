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
    <meta name="description" content="Librairie universelle : trouver des livres et EPUB indépendants, préparer un manuscrit DOCX, ODT ou EPUB pour Corep ou Bookelis et découvrir les librairies proches. Pilote en Moselle.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Le projet — <?= e_project($config['name']) ?>">
    <meta property="og:description" content="Un projet autour des livres et des EPUB : recherche, autoédition gratuite, librairies de proximité et vente directe par les auteurs. Premier pilote en Moselle.">
    <title>Le projet — <?= e_project($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/projet.css?v=20261021-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'projet'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>

<main id="main" class="project-page">
    <section class="page-hero" aria-labelledby="project-title">
        <p class="eyebrow">Un prototype concret · première étape en Moselle</p>
        <h1 id="project-title">Et si le livre le plus pratique à trouver était celui qui est près de chez vous ?</h1>
        <p class="lede">Librairie universelle veut relier toute la chaîne du livre : aider les auteurs indépendants à préparer leur ouvrage, faciliter sa rencontre avec les librairies et permettre aux lecteurs de le trouver ou de le faire imprimer près de chez eux, sans imposer à chacun une nouvelle usine à gaz.</p>
        <p>Un livre déjà en rayon à deux rues peut être récupéré sans attendre un colis ni payer son expédition. Nous voulons rendre cette possibilité aussi simple à trouver que les autres formes d'accès au livre, <strong>lorsque la proximité le permet</strong>. Pour les auteurs, le même projet propose déjà de préparer un livre papier à partir d'un DOCX, d'un ODT ou d'un EPUB et de le faire connaître sans abandonner leur autonomie commerciale.</p>
        <div class="cta-row">
            <a class="button-link" href="/#librairies">Trouver une librairie proche</a>
            <a class="button-link secondary" href="/autoedition.php">Préparer mon livre</a>
            <a class="text-link" href="#etat-du-projet">Où en est le projet ?</a>
        </div>
    </section>

    <section class="content-section" aria-labelledby="why-local">
        <p class="section-kicker">Pourquoi commencer près de chez soi ?</p>
        <h2 id="why-local">Trouver un livre près de chez soi, quand c'est possible.</h2>
        <p>La rapidité et le coût comptent autant que le choix du livre. <strong>Un exemplaire déjà disponible chez un libraire voisin n'a pas besoin d'attendre un colis.</strong> Librairie universelle veut rendre cette possibilité plus visible, sans demander aux libraires de remplacer leurs outils ni annoncer des stocks que nous ne connaissons pas.</p>
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
        <h2 id="complementarity-title">Des réseaux pour acheter les livres. Un projet qui aide aussi à les créer.</h2>
        <div class="prose-grid">
            <p>Des réseaux comme <a href="https://www.leslibraires.fr/" target="_blank" rel="noopener noreferrer">leslibraires.fr</a>, <a href="https://www.canalbd.net/" target="_blank" rel="noopener noreferrer">Canal BD</a> et d'autres portails permettent déjà de trouver, réserver et commander des livres, parfois en privilégiant une librairie proche. <strong>Nous ne cherchons ni à copier leurs services ni à détourner les libraires de leurs boutiques actuelles.</strong></p>
            <p><strong>Notre différence est aussi en amont de la vente :</strong> offrir aux auteurs indépendants des outils gratuits pour composer l'intérieur de leur livre à partir d'un DOCX, d'un ODT ou d'un EPUB sans DRM, préparer leur dossier d'impression et prendre contact avec des librairies proches. Le lecteur peut ensuite rechercher un ouvrage et comparer les moyens de le lire ou de l'obtenir. Si une librairie vend déjà un livre via son propre site ou un réseau existant, nous voulons l'y orienter, pas l'obliger à passer par une nouvelle caisse.</p>
        </div>
        <p>Le pilote démarre en Moselle : outils d'autoédition utilisables, fiches d'auteurs proposées pour vérification humaine, recherche de librairies et préparation de la diffusion locale. Les EPUB indépendants et les œuvres proposées en lecture libre disposent de parcours distincts. La connexion aux stocks ne se fera qu'avec l'accord des librairies. Aucun circuit automatique d'impression ou de vente, aucune double saisie imposée, aucun partenariat présenté comme acquis avant de l'être.</p>
    </section>

    <section class="content-section" aria-labelledby="who-title">
        <p class="section-kicker">Pour qui ?</p>
        <h2 id="who-title">Un même réseau, quatre façons d'en profiter.</h2>
        <div class="feature-grid">
            <article><span aria-hidden="true">1</span><h3>Lecteurs et lectrices</h3><p>Chercher une œuvre et ses éditions, trouver des EPUB gratuits de sources identifiées ou des livres numériques proposés par leurs auteurs, et repérer des librairies proches.</p></article>
            <article><span aria-hidden="true">2</span><h3>Librairies</h3><p>À terme, rendre leurs stocks visibles sans double saisie, puis recevoir des demandes de réservation ou de commande avec leur accord et dans le respect de leurs outils.</p></article>
            <article><span aria-hidden="true">3</span><h3>Auteurs indépendants</h3><p>Préparer leur livre papier ou EPUB, proposer une fiche descriptive sans transmettre de manuscrit, signer une convention de référencement avant publication pour la vente directe, puis préparer leur démarche auprès des librairies.</p></article>
            <article><span aria-hidden="true">4</span><h3>Imprimeurs de proximité</h3><p>Recevoir des dossiers mieux préparés et, à terme, participer à un circuit d'impression locale adapté aux besoins des auteurs et des lecteurs.</p></article>
        </div>
    </section>

    <section class="content-section" aria-labelledby="authors-title">
        <p class="section-kicker">Autoédition · déjà à essayer</p>
        <h2 id="authors-title">Un livre ne s'arrête pas à la dernière page du manuscrit.</h2>
        <p>Nous accompagnons les auteurs depuis la préparation de leurs fichiers jusqu'aux premières démarches de diffusion, sans rendre l'accompagnement payant obligatoire.</p>
        <div class="project-journey">
            <article><span>01</span><h3>Préparer les fichiers</h3><p>Importer un DOCX, un ODT ou un EPUB sans DRM, recomposer l'intérieur, régler la pagination et enregistrer le PDF. Le fichier original reste inchangé ; les mises en page complexes doivent être contrôlées.</p><a href="/autoedition.php?partie=paper">Utiliser le générateur papier →</a></article>
            <article><span>02</span><h3>Choisir un imprimeur</h3><p><strong>Corep Metz :</strong> A5, deux faces de couverture séparées et grille de prix indicative autorisée. <strong>Bookelis :</strong> sept formats d'intérieur et gabarit officiel pour la couverture complète avec tranche. Le devis, le BAT et l'envoi des fichiers restent à valider directement avec chaque prestataire.</p><a href="/autoedition.php?partie=paper">Comparer les parcours Corep et Bookelis →</a><p><a href="/dossier-impression.php">Préparer le dossier d'impression →</a></p></article>
            <article><span>03</span><h3>Faire référencer</h3><p>Proposer une fiche descriptive de livre papier ou EPUB, examinée humainement. <strong>Les manuscrits ne sont jamais envoyés à la Librairie.</strong> Pour la vente directe auteur–acheteur, la publication exige une convention signée de commission à 3 % ; une simple proposition n'est ni publiée ni facturée.</p><a href="/proposer-livre.php">Proposer ma fiche →</a></article>
            <article><span>04</span><h3>Proposer aux librairies</h3><p>Créer une fiche de présentation et un message personnalisé, trouver des librairies proches et discuter avec elles des conditions de commande ou de dépôt-vente.</p><a href="/diffuser-mon-livre.php">Préparer ma diffusion →</a></article>
        </div>
        <p class="project-clarification"><strong>Référencement et vente restent distincts :</strong> la proposition d'une fiche est gratuite, mais une fiche de vente directe auteur–acheteur, papier ou EPUB, ne peut être publiée sans convention signée. La commission de <strong>3 %</strong> porte uniquement sur le prix TTC des livres effectivement vendus grâce à la mise en relation, hors livraison, annulations et remboursements ; aucun frais n'est dû pour un simple contact. Le vendeur gère la commande, le paiement et la livraison : la Librairie n'encaisse pas le prix du livre. <a href="/conditions-generales.php">Consulter les conditions de référencement →</a></p>
    </section>

    <section class="content-section" aria-labelledby="human-title">
        <p class="section-kicker">Accompagnement facultatif</p>
        <h2 id="human-title">Des outils gratuits, et un accompagnement si vous en avez besoin.</h2>
        <p>Si un auteur préfère ne pas tout vérifier seul, Le Potager du Web propose deux prestations distinctes, sur devis :</p>
        <div class="project-prices">
            <article><h3>Vérification avant impression</h3><p class="project-price">79 € HT</p><p>Contrôle des fichiers et compte rendu des problèmes visibles : pagination, marges, couvertures, lisibilité.</p></article>
            <article><h3>Correction accompagnée</h3><p class="project-price">Dès 179 € HT</p><p>Correction orthographique et grammaticale assistée par logiciel, avec relecture des corrections dans un périmètre défini. Il ne s'agit pas d'une relecture intégrale garantie à ce prix.</p></article>
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
                    <li>Générateur papier à partir de DOCX, ODT ou EPUB sans DRM : aperçu paginé et export du PDF intérieur pour Corep Metz (A5) ou Bookelis (sept formats)</li>
                    <li>Assistant de couvertures séparées pour Corep, dossier imprimeur et guidage vers le gabarit officiel de couverture complète Bookelis</li>
                    <li>Proposition gratuite de fiches d'auteurs papier ou EPUB, examen humain et préparation d'une convention de 3 % à signer avant toute publication de vente directe</li>
                    <li>Guide de diffusion et création locale d'un message à adresser aux librairies</li>
                    <li>Demandes de devis pour un accompagnement facultatif</li>
                </ul>
            </article>
            <article class="project-status">
                <h3>Encore en préparation</h3>
                <ul>
                    <li>Validation de bout en bout de fiches d'auteurs réelles, de leur convention signée et de leur publication dans le catalogue</li>
                    <li>Premières impressions et commandes testées avec de vrais auteurs et imprimeurs</li>
                    <li>Connexion autorisée aux logiciels et stocks des librairies volontaires</li>
                    <li>Développer les mises en relation entre auteurs, libraires et lecteurs, sans encaissement du prix des livres par la plateforme</li>
                    <li>Vérifier sur des cas réels les informations vendeur, le suivi de la convention et les conditions de vente de chaque édition</li>
                    <li>Extension progressive à d'autres territoires, après validation du pilote mosellan</li>
                </ul>
            </article>
        </div>
        <p class="note"><strong>Nous n'annonçons ni stock en temps réel, ni vente intégrée, ni partenariat contractuel qui n'existent pas encore.</strong> Corep Metz a transmis des indications techniques et autorisé l'affichage d'une grille indicative pour des configurations précises. Bookelis dispose de son propre parcours de préparation, mais le gabarit complet de couverture et la commande passent encore par son service officiel. Aucun des deux n'est présenté comme un partenaire commercial signé. Les fichiers doivent être vérifiés avant impression.</p>
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
