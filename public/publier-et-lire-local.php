<?php
declare(strict_types=1);

[$config] = require dirname(__DIR__) . '/src/bootstrap.php';

$gridFile = dirname(__DIR__) . '/data/corep-metz-approved.json';
$corep = is_file($gridFile) ? json_decode((string)file_get_contents($gridFile), true) : null;
$corepCurrent = is_array($corep) && isset($corep['review_on'], $corep['amounts_cents']['120'])
    && date('Y-m-d') <= (string)$corep['review_on'];
$paperPriceTtc = 15.00;
$ebookPriceTtc = 6.00;
$commissionPaper = $paperPriceTtc * .03;
$commissionEbook = $ebookPriceTtc * .03;
$kdpPrintCost = .75 + 120 * .012;
$kdpPaperbackRoyalty = (.60 * ($paperPriceTtc / 1.055)) - $kdpPrintCost;
$kdpEbookMaximum70 = .70 * ($ebookPriceTtc / 1.055);
$kdpEbook35 = .35 * ($ebookPriceTtc / 1.055);
$money = static fn(float $amount): string => number_format($amount, 2, ',', ' ') . ' €';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="https://librairie.lepotager.org/publier-et-lire-local.php">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#f7f3ea">
    <meta name="description" content="Vos droits d'auteur papier et EPUB, la vente directe à 3 %, le parcours des librairies locales et un comparatif chiffré avec Amazon KDP, sans fausse promesse écologique.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Votre livre reste le vôtre — Librairie universelle">
    <meta property="og:description" content="Droits conservés, librairies locales et chiffres transparents : découvrez les différences entre Amazon KDP, vente directe et circuit local.">
    <title>Votre livre, vos droits, vos librairies — Librairie universelle</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/publier-local.css?v=20261110-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='libre-local'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main" class="local-argument-page">
    <section class="page-hero local-argument-hero" aria-labelledby="local-argument-title">
        <p class="eyebrow">Auteurs indépendants · Lecteurs · Librairies de proximité</p>
        <h1 id="local-argument-title">Votre livre reste le vôtre. Son avenir ne dépend pas d'une seule plateforme.</h1>
        <p class="lede">Publier en indépendant n'oblige pas à abandonner ses droits, sa relation avec les lecteurs ou les librairies de son quartier. La Librairie universelle réunit des outils gratuits pour préparer vos fichiers, un annuaire de librairies proches et un référencement direct sous convention claire.</p>
        <div class="cta-row">
            <a class="button-link" href="/autoedition.php?partie=paper">Préparer mon livre</a>
            <a class="button-link secondary" href="/diffuser-mon-livre.php#fiche-libraire">Le proposer aux librairies</a>
            <a class="text-link" href="#chiffres">Voir les chiffres</a>
        </div>
        <p class="local-argument-note">La Librairie ne vend pas et n'imprime pas elle-même les livres. Les auteurs et libraires restent maîtres de leurs commandes ; aucun stock de librairie n'est annoncé en temps réel.</p>
    </section>

    <section class="content-section" aria-labelledby="droits-title" id="droits">
        <p class="section-kicker">Droits d'auteur · papier et numérique</p>
        <h2 id="droits-title">« À qui appartient mon livre ? » À son auteur, sauf droits déjà accordés par contrat.</h2>
        <p>En France, une œuvre originale appartient à son auteur dès sa création. Le fait de faire imprimer un exemplaire, de publier un EPUB ou de vendre un livre ne transfère pas automatiquement le droit d'auteur à l'imprimeur, au libraire ou à la plateforme. Il faut toutefois tenir compte des éventuels contrats d'édition, licences ou cessions déjà signés. <a href="#sources">Fondement juridique et sources</a>.</p>
        <div class="local-proof-grid">
            <article><h3>Le manuscrit reste chez vous</h3><p>Pour préparer un livre ou proposer une fiche au catalogue, vous n'envoyez pas votre manuscrit à la Librairie. Vous conservez votre DOCX, ODT ou EPUB original et choisissez votre imprimeur.</p></article>
            <article><h3>Pas d'exclusivité de référencement</h3><p>La convention de vente directe à 3 % est non exclusive. Elle ne cède pas vos droits sur l'œuvre et n'empêche pas de chercher d'autres circuits, sous réserve des engagements que vous auriez pris ailleurs.</p></article>
            <article><h3>Vous connaissez votre acheteur</h3><p>En vente directe, l'auteur ou le vendeur gère lui-même la commande, l'encaissement et la livraison. La Librairie ne reçoit pas de copie des messages entre vendeur et lecteur.</p></article>
        </div>
        <div class="local-argument-emphasis">
            <h3>Et Amazon KDP ?</h3>
            <p><strong>Amazon ne devient pas non plus propriétaire de votre texte.</strong> KDP repose sur des droits de publication et de distribution accordés par contrat. Son programme <strong>KDP Select</strong>, facultatif, impose en revanche l'exclusivité de l'édition numérique pendant les périodes d'inscription de 90 jours. <strong>Un livre papier publié avec KDP peut être vendu également sur votre site, dans des salons ou dans des librairies.</strong> Pour imprimer cette édition chez un autre prestataire, attention à l'ISBN : l'ISBN gratuit KDP est réservé à KDP ; l'utilisation d'un ISBN propre facilite la diffusion auprès de plusieurs imprimeurs. Vous pouvez aussi revendre directement les exemplaires papier commandés à KDP. En cas de distribution étendue, le même ISBN ne doit pas faire l'objet d'une demande de distribution via un autre service. La question utile est donc moins « qui vole mes droits ? » que <strong>« quels droits ai-je concédés, à qui, et pour combien de temps ? »</strong></p>
            <p><a href="https://kdp.amazon.com/fr_FR/help/topic/GD9PMU58BV24QFZ7" target="_blank" rel="noopener noreferrer">Consulter la règle KDP Select</a> · <a href="https://kdp.amazon.com/fr_FR/help/topic/G201834170" target="_blank" rel="noopener noreferrer">Comprendre les ISBN KDP</a> · <a href="/conditions-generales.php">Lire nos conditions de référencement</a></p>
        </div>
    </section>

    <section class="content-section" aria-labelledby="trois-voies-title" id="circuits">
        <p class="section-kicker">Des chemins différents, sans obligation d'en choisir un seul</p>
        <h2 id="trois-voies-title">Trois façons de faire circuler un livre</h2>
        <div class="local-channel-grid">
            <article>
                <span class="local-channel-number">01</span>
                <h3>Vendre directement aux lecteurs</h3>
                <p>Une fiche papier ou EPUB présente votre livre. Après signature de la convention, les lecteurs vous contactent pour acheter : <strong>3 % uniquement sur le prix TTC des livres réellement vendus grâce à la mise en relation</strong>, hors livraison et remboursements.</p>
                <p>Le dépôt d'une fiche et les outils de préparation sont gratuits. Pas de caisse ni d'expédition gérées par la Librairie.</p>
                <a href="/proposer-livre.php">Proposer un livre →</a>
            </article>
            <article>
                <span class="local-channel-number">02</span>
                <h3>Passer par une librairie près de chez soi</h3>
                <p>L'auteur prépare une présentation, sélectionne des librairies proches et les contacte personnellement. Commande ferme ou dépôt-vente : <strong>les modalités se discutent avec chaque libraire</strong>, pas avec un intermédiaire imposé.</p>
                <p>La convention de commission de 3 % concerne la vente directe auteur–lecteur sur notre catalogue ; elle ne fixe pas la marge d'une librairie partenaire.</p>
                <a href="/diffuser-mon-livre.php#fiche-libraire">Préparer ma proposition aux libraires →</a>
            </article>
            <article>
                <span class="local-channel-number">03</span>
                <h3>Utiliser une plateforme d'impression à la demande</h3>
                <p>Amazon KDP ou Bookelis peuvent éviter l'avance d'un stock et faciliter l'impression d'une commande. En contrepartie, les règles de diffusion, de prix, les redevances et la relation commerciale passent par leurs services.</p>
                <p>Ce choix peut être pertinent, notamment pour quelques ventes dispersées : notre parcours n'a pas vocation à le cacher.</p>
                <a href="/autoedition.php?partie=paper">Comparer les parcours d'impression →</a>
            </article>
        </div>
    </section>

    <section class="content-section" aria-labelledby="librairie-title" id="librairies">
        <p class="section-kicker">Le circuit court du livre · Moselle pour commencer</p>
        <h2 id="librairie-title">Un parcours dédié aux librairies locales, côté lecteurs comme côté auteurs.</h2>
        <div class="local-bookstore-paths">
            <section aria-labelledby="reader-bookstore-title">
                <h3 id="reader-bookstore-title">Je cherche un livre</h3>
                <ol>
                    <li>Je recherche les librairies proches de mon adresse et choisis un rayon.</li>
                    <li>Je contacte la librairie qui m'intéresse pour vérifier la disponibilité, le prix et la possibilité de commander.</li>
                    <li>Si le livre est disponible, je peux envisager un retrait sur place, à pied, à vélo ou lors d'un trajet déjà prévu.</li>
                </ol>
                <p><strong>Pas de faux stock « disponible » :</strong> l'annuaire repère les librairies, il ne connaît pas encore leurs inventaires en direct.</p>
                <a class="button-link secondary" href="/#librairies">Chercher une librairie proche</a>
            </section>
            <section aria-labelledby="author-bookstore-title">
                <h3 id="author-bookstore-title">Je veux proposer mon livre à une librairie</h3>
                <ol>
                    <li>Je prépare les informations utiles : résumé, lectorat, ISBN s'il existe, prix et approvisionnement.</li>
                    <li>Je repère quelques librairies dont la ligne éditoriale correspond à mon ouvrage.</li>
                    <li>Je génère ma fiche de présentation et un message personnalisable, sans transmettre mes données au site.</li>
                    <li>Je discute directement commandes, remise libraire, éventuel dépôt-vente, retours et paiement.</li>
                </ol>
                <p><strong>La mise en rayon ne se décrète pas :</strong> chaque librairie décide librement. Notre service ne lui impose ni logiciel, ni catalogue, ni contrat.</p>
                <a class="button-link" href="/diffuser-mon-livre.php#fiche-libraire">Créer ma fiche pour les librairies</a>
            </section>
        </div>
        <p class="local-small">Amazon KDP propose également une distribution étendue vers certains réseaux, sans garantir qu'une librairie donnée commandera l'ouvrage. Notre différence est l'accompagnement d'une <strong>démarche de proximité choisie par l'auteur</strong>, pas la promesse d'une diffusion plus vaste. <a href="https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45" target="_blank" rel="noopener noreferrer">Voir les conditions KDP</a>.</p>
    </section>

    <section class="content-section" aria-labelledby="economics-title" id="chiffres">
        <p class="section-kicker">Comparer les coûts, sans mélanger les modèles</p>
        <h2 id="economics-title">Combien reste-t-il sur un livre à 15 € ?</h2>
        <p>Exemple : <strong>broché A5 de 120 pages en noir et blanc, vendu 15 € TTC</strong> en France, TVA du livre à 5,5 %. Chez KDP, pour ce format standard, la fabrication annoncée est de <strong><?= $money($kdpPrintCost) ?></strong> par exemplaire (0,75 € fixe + 120 × 0,012 €). La redevance standard d'un broché vendu à 15 € sur Amazon.fr est de <strong><?= $money($kdpPaperbackRoyalty) ?></strong> par vente (60 % du prix hors TVA, moins impression).</p>
        <div class="local-compare-metrics">
            <div><span>Fabrication KDP à la demande</span><strong><?= $money($kdpPrintCost) ?></strong><small>Coût déduit de la redevance, sans stock à acheter</small></div>
            <div><span>Redevance auteur KDP estimée</span><strong><?= $money($kdpPaperbackRoyalty) ?></strong><small>Par vente standard à 15 € TTC sur Amazon.fr</small></div>
            <div><span>Commission de notre vente directe</span><strong><?= $money($commissionPaper) ?></strong><small>3 % de 15 € TTC, uniquement sur une vente apportée</small></div>
        </div>
        <?php if ($corepCurrent): ?>
        <h3>Impression locale : le nombre d'exemplaires change tout</h3>
        <div class="local-table-scroll" role="region" aria-label="Comparaison de l'impression locale" tabindex="0">
            <table class="local-comparison-table">
                <thead><tr><th scope="col">Tirage Corep Metz</th><th scope="col">Prix du tirage TTC</th><th scope="col">Impression / exemplaire</th><th scope="col">Reste sur vente directe à 15 €*</th></tr></thead>
                <tbody>
                <?php foreach ([10,50,100] as $qty):
                    $total=(float)($corep['amounts_cents']['120'][(string)$qty]??0)/100;
                    if($total<=0)continue;
                    $unit=$total/$qty;
                    $left=$paperPriceTtc-$unit-$commissionPaper;
                ?>
                    <tr><th scope="row"><?= $qty ?> exemplaires</th><td><?= $money($total) ?></td><td><?= $money($unit) ?></td><td><?= $money($left) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="local-small">* « Reste » = prix TTC du livre – part de l'impression TTC – 3 % de commission. <strong>Ce n'est ni un bénéfice ni une rémunération nette :</strong> il manque notamment emballage, transaction, déplacements, cotisations, fiscalité, travail, livraison et invendus. Il faut financer le tirage à l'avance. Grille Corep indicative autorisée, relevée le <?= htmlspecialchars((string)$corep['recorded_on'], ENT_QUOTES, 'UTF-8') ?>, soumise à réexamen au <?= htmlspecialchars((string)$corep['review_on'], ENT_QUOTES, 'UTF-8') ?> et à validation du fichier. Les finitions et circuits de vente KDP et Corep ne sont pas identiques.</p>
        <?php else: ?>
        <p class="local-argument-note"><strong>Prix d'impression locale en cours de réexamen :</strong> la dernière grille Corep connue a dépassé sa date de contrôle. Nous n'affichons pas une ancienne grille comme un devis actuel. Demandez un devis directement à l'imprimeur pour comparer avec KDP.</p>
        <?php endif; ?>
        <p><strong>Et une librairie locale ?</strong> Ce calcul de 3 % et de « reste sur vente directe » ne s'applique pas à une vente réalisée par une librairie. Son taux de remise, les modalités de dépôt-vente ou d'achat ferme, les retours et la facturation sont à convenir avec elle. Sans ces données, annoncer un « revenu auteur » chiffré serait trompeur.</p>

        <h3>Et pour un EPUB à 6 € TTC ?</h3>
        <div class="local-table-scroll" role="region" aria-label="Comparaison numérique" tabindex="0">
            <table class="local-comparison-table">
                <thead><tr><th scope="col">Circuit de vente</th><th scope="col">Montant restant à l'auteur ou vendeur</th><th scope="col">Ce qui reste à payer / prévoir</th></tr></thead>
                <tbody>
                    <tr><th scope="row">Vente directe grâce à la Librairie</th><td><strong><?= $money($ebookPriceTtc-$commissionEbook) ?></strong> après la commission de <?= $money($commissionEbook) ?></td><td>Encaissement, hébergement ou envoi du fichier, TVA éventuelle, fiscalité, assistance et obligations vendeur</td></tr>
                    <tr><th scope="row">Amazon KDP, option 70 % éligible</th><td><strong>au maximum <?= $money($kdpEbookMaximum70) ?></strong> avant frais de livraison numérique KDP</td><td>Frais de livraison numériques selon la taille du fichier, admissibilité au taux de 70 %, fiscalité éventuelle</td></tr>
                    <tr><th scope="row">Amazon KDP, option 35 %</th><td><strong><?= $money($kdpEbook35) ?></strong> estimés</td><td>Conditions commerciales et taxes éventuelles selon la vente et le territoire</td></tr>
                </tbody>
            </table>
        </div>
        <p class="local-small">Simulations en France avec TVA livre à 5,5 %. Le chiffre de 70 % est un plafond <strong>avant</strong> les frais de livraison du fichier, non une promesse de revenu. Le référencement direct à 3 % n'inclut pas la gestion commerciale que KDP assure. Les taux KDP dépendent des conditions et des territoires. <a href="https://kdp.amazon.com/fr_FR/help/topic/G200644210" target="_blank" rel="noopener noreferrer">Méthode KDP</a>.</p>
    </section>

    <section class="content-section" aria-labelledby="ecologie-title" id="ecologie">
        <p class="section-kicker">Écologie · comparer les situations réelles</p>
        <h2 id="ecologie-title">Le local peut réduire des transports. Ce n'est pas une étiquette carbone magique.</h2>
        <p><strong>Aucun chiffre de CO₂ par livre n'est publié ici</strong> : nous n'avons ni les consommations des ateliers, ni l'origine exacte des papiers, ni les trajets de livraison, ni la part d'invendus. Une estimation chiffrée sans ces paramètres donnerait une précision illusoire. L'ADEME rappelle que l'achat en ligne et l'achat en magasin n'ont pas de gagnant écologique systématique.</p>
        <div class="local-eco-grid">
            <article><h3>Un livre déjà en librairie, récupéré à pied ou à vélo</h3><p><strong>Potentiellement favorable.</strong> Pas de nouvelle livraison de colis au domicile pour cet achat et pas de déplacement motorisé dédié. Il faut tout de même tenir compte de la fabrication et de l'acheminement du livre jusqu'au magasin.</p></article>
            <article><h3>Un livre imprimé localement en petite série</h3><p><strong>À étudier selon les ventes prévues.</strong> Un retrait proche et la mutualisation des commandes peuvent limiter certains transports. En revanche, imprimer 100 livres pour n'en vendre que quelques-uns entraîne du stock et des invendus.</p></article>
            <article><h3>Un livre KDP imprimé à la demande</h3><p><strong>Avantage possible sur les invendus.</strong> Pas de stock à constituer pour l'auteur ; les exemplaires sont fabriqués à la commande. Les émissions dépendent ensuite de l'atelier, du papier, des emballages et de l'acheminement réel.</p></article>
            <article><h3>Un retrait local avec aller-retour dédié en voiture</h3><p><strong>Pas forcément meilleur.</strong> Les kilomètres supplémentaires du lecteur peuvent dépasser ceux d'une livraison groupée. Privilégier, quand c'est possible, le retrait pendant un trajet existant ou un déplacement non motorisé.</p></article>
        </div>
        <p class="local-argument-emphasis"><strong>Notre engagement mesurable aujourd'hui :</strong> faciliter la découverte des librairies proches et des imprimeurs locaux, ne pas imposer d'expédition et garder le choix de l'approvisionnement. Pour afficher un vrai bilan carbone, il faudra recueillir les données de fabrication, d'emballage, de transport et d'invendus auprès des prestataires concernés.</p>
    </section>

    <section class="content-section" aria-labelledby="transparence-title">
        <p class="section-kicker">Ce que la Librairie fait, et ne fait pas</p>
        <h2 id="transparence-title">Indépendance ne veut pas dire tout faire soi-même.</h2>
        <p>La Librairie propose un annuaire de librairies, des outils de préparation papier, un parcours de présentation aux libraires et des fiches de vente directe conditionnées à une convention signée. Elle ne promet pas de stock synchronisé, de mise en rayon, de commande intégrée, de distribution garantie, ni de prix d'impression définitif. Ses prestations de relecture ou de vérification avant impression sont facultatives.</p>
        <div class="cta-row">
            <a class="button-link" href="/#librairies">Trouver une librairie</a>
            <a class="button-link secondary" href="/diffuser-mon-livre.php">Présenter mon livre aux libraires</a>
            <a class="text-link" href="/projet.php">Suivre le projet</a>
        </div>
    </section>

    <section class="content-section local-sources" id="sources" aria-labelledby="sources-title">
        <h2 id="sources-title">Sources et méthode de comparaison</h2>
        <p>Vérifications du 10 novembre 2026. Ces exemples utilisent les conditions publiées pour Amazon.fr, les données Corep autorisées lorsqu'elles sont encore dans leur période de réexamen et les conditions actuelles de la Librairie. Le scénario est illustratif, pas un devis ni une garantie de revenu.</p>
        <ul>
            <li><a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000042814694" target="_blank" rel="noopener noreferrer">Code de la propriété intellectuelle, article L111-1</a> : droits de l'auteur dès la création.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G200672400" target="_blank" rel="noopener noreferrer">Amazon KDP — droits d'auteur</a>, <a href="https://kdp.amazon.com/fr_FR/help/topic/GD9PMU58BV24QFZ7" target="_blank" rel="noopener noreferrer">KDP Select</a>, <a href="https://kdp.amazon.com/fr_FR/help/topic/G201834170" target="_blank" rel="noopener noreferrer">ISBN gratuit ou personnel</a> et <a href="https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45" target="_blank" rel="noopener noreferrer">distribution étendue</a>.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G201834340" target="_blank" rel="noopener noreferrer">KDP — coûts d'impression</a>, <a href="https://kdp.amazon.com/fr_FR/help/topic/G201834330" target="_blank" rel="noopener noreferrer">redevances du broché</a> et <a href="https://kdp.amazon.com/fr_FR/help/topic/G200644210" target="_blank" rel="noopener noreferrer">redevances des ebooks</a>.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45" target="_blank" rel="noopener noreferrer">KDP — distribution étendue</a> : absence de garantie de référencement ou de commande en librairie.</li>
            <li><a href="https://www.economie.gouv.fr/particuliers/mes-droits-conso/bien-consommer/affichage-des-prix-ce-que-vous-devez-savoir" target="_blank" rel="noopener noreferrer">Ministère de l'Économie — prix unique du livre neuf</a> : le prix ne doit pas varier librement selon les vendeurs.</li>
            <li><a href="https://www.ademe.fr/presse/communique-national/e-commerce-un-outil-pour-evaluer-et-reduire-limpact-environnemental-de-la-logistique-des-transports-et-des-deplacements/" target="_blank" rel="noopener noreferrer">ADEME — empreinte environnementale des achats et livraisons</a>.</li>
            <li>Corep Metz : grille indicative TTC reçue et autorisée par courriel en octobre 2026, contrôlée jusqu'au 17 novembre 2026. La configuration exacte et le tarif doivent toujours être confirmés directement avec l'imprimeur.</li>
        </ul>
    </section>
</main>
<footer class="local-argument-footer">
    <a href="/">Accueil</a><a href="/autoedition.php">Autoédition</a><a href="/diffuser-mon-livre.php">Librairies locales</a><a href="/conditions-generales.php">Conditions</a>
    <p>Librairie universelle · une initiative du Potager du Web, expérimentée en Moselle.</p>
</footer>
</body>
</html>
