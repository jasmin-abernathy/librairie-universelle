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
    <link rel="canonical" href="https://librairie.lepotager.org/comparer-diffusion-livre.php">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#f7f3ea">
    <meta name="description" content="Amazon KDP, librairies et vente directe : ISBN, droits, coûts papier et EPUB, calcul de marge et impact écologique.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Amazon KDP ou librairies : droits et coûts">
    <meta property="og:description" content="Comprendre ses droits et comparer les revenus estimatifs des différents circuits de vente.">
    <title>Amazon KDP ou librairies : droits et revenus — Librairie universelle</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/publier-local.css?v=20261110-1">
    <link rel="stylesheet" href="/assets/css/amazon-local.css?v=20261110-2">
    <link rel="stylesheet" href="/assets/css/circuits-guide.css?v=20261110-1">
    <script src="/assets/js/amazon-local.js?v=20261110-2" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='libre-local'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main" class="local-argument-page local-detail-page">
    <section class="page-hero local-detail-hero" aria-labelledby="detail-title">
        <a class="local-back-link" href="/publier-et-lire-local.php">Retour aux parcours de la Librairie</a>
        <p class="eyebrow">Guide pratique · droits, prix, impression</p>
        <h1 id="detail-title">Amazon KDP, vente directe ou librairie : quels droits, quels coûts ?</h1>
        <p class="lede"><strong>Un livre papier publié via KDP peut aussi être vendu ailleurs.</strong> Les différences se jouent sur les ISBN, les contrats, les coûts de fabrication et les conditions commerciales. Choisissez à partir de ces informations, sans exclure un circuit par principe.</p>
        <div class="cta-row"><a class="button-link" href="#simulateur-marges">Calculer mes montants</a><a class="button-link secondary" href="#droits-kdp">Comprendre mes droits</a></div>
    </section>
    <nav class="local-guide-jump" aria-label="Rubriques de ce guide">
        <a href="#droits-kdp">Droits et ISBN</a><a href="#chiffres">Prix et revenus</a><a href="#enjeux-sociaux">Impact social</a><a href="#ecologie">Impact écologique</a><a href="#sources">Sources</a>
    </nav>
    <section class="content-section" id="droits-kdp" aria-labelledby="rights-guide-title">
        <p class="section-kicker">Droits d'auteur · contrats · ISBN</p>
        <h2 id="rights-guide-title">Un livre papier KDP peut-il être vendu ailleurs ?</h2>
        <p>Oui. L'œuvre reste celle de son auteur, sous réserve de tout autre contrat. Une édition papier KDP n'impose pas une exclusivité générale ; vous pouvez revendre des exemplaires d'auteur ou proposer votre livre à une librairie.</p>
        <div class="local-argument-emphasis">
            <h3>Et Amazon KDP ?</h3>
            <p><strong>Amazon ne devient pas non plus propriétaire de votre texte.</strong> KDP repose sur des droits de publication et de distribution accordés par contrat. Son programme <strong>KDP Select</strong>, facultatif, impose en revanche l'exclusivité de l'édition numérique pendant les périodes d'inscription de 90 jours. <strong>Un livre papier publié avec KDP peut être vendu également sur votre site, dans des salons ou dans des librairies.</strong> Pour imprimer cette édition chez un autre prestataire, attention à l'ISBN : l'ISBN gratuit KDP est réservé à KDP ; l'utilisation d'un ISBN propre facilite la diffusion auprès de plusieurs imprimeurs. Vous pouvez aussi revendre directement les exemplaires papier commandés à KDP. En cas de distribution étendue, le même ISBN ne doit pas faire l'objet d'une demande de distribution via un autre service. La question utile est donc moins « qui vole mes droits ? » que <strong>« quels droits ai-je concédés, à qui, et pour combien de temps ? »</strong></p>
            <p><a href="https://kdp.amazon.com/fr_FR/help/topic/GD9PMU58BV24QFZ7" target="_blank" rel="noopener noreferrer">Consulter la règle KDP Select</a> · <a href="https://kdp.amazon.com/fr_FR/help/topic/G201834170" target="_blank" rel="noopener noreferrer">Comprendre les ISBN KDP</a> · <a href="/conditions-generales.php">Lire nos conditions de référencement</a></p>
        </div>
        <p class="local-small"><strong>Attention :</strong> les épreuves portant la mention « Non destiné à la vente » ne sont pas commercialisables. Si vous faites imprimer une édition ailleurs, vérifiez la compatibilité de votre ISBN et les accords de distribution déjà conclus.</p>
        <p class="local-small">Amazon KDP propose également une distribution étendue vers certains réseaux, sans garantir qu'une librairie donnée commandera l'ouvrage. Notre différence est l'accompagnement d'une <strong>démarche de proximité choisie par l'auteur</strong>, pas la promesse d'une diffusion plus vaste. <a href="https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45" target="_blank" rel="noopener noreferrer">Voir les conditions KDP</a>.</p>
    </section>

    <section class="content-section" aria-labelledby="economics-title" id="chiffres">
        <p class="section-kicker">Revenus indicatifs · comparer sans mélanger</p>
        <h2 id="economics-title">Combien reste-t-il sur une vente ?</h2>
        <p>Vous connaissez votre prix et vos devis ? Commencez par le calculateur. Il ne prétend pas calculer un bénéfice net ; il indique les montants restant après les postes que vous renseignez.</p>
        <section class="local-margin-tool" aria-labelledby="margin-tool-title" id="simulateur-marges">
            <div class="local-margin-head">
                <p class="section-kicker">Votre propre livre · valeurs modifiables</p>
                <h3 id="margin-tool-title">Calculez avec vos devis, pas avec des prix inventés</h3>
                <p>Le tableau précédent illustre un livre précis. Pour le vôtre, renseignez les prix <strong>réellement proposés</strong> par KDP et votre imprimeur, et la remise éventuellement négociée avec une librairie. Les résultats changent immédiatement, sans compte ni transmission de données.</p>
            </div>
            <form id="amazon-local-calculator" class="local-margin-form" novalidate>
                <div class="local-margin-fields">
                    <label for="compare-public-price">Prix public du livre neuf, TTC (€)
                        <input id="compare-public-price" name="publicPrice" type="number" min="0.01" max="250" step="0.01" inputmode="decimal" value="15" required>
                    </label>
                    <label for="compare-kdp-print">Coût d'impression de votre livre chez KDP (€)
                        <input id="compare-kdp-print" name="kdpPrint" type="number" min="0" max="250" step="0.01" inputmode="decimal" placeholder="Prix réel dans votre compte KDP" required>
                    </label>
                    <label for="compare-local-print">Coût d'impression local par exemplaire, HT (€)
                        <input id="compare-local-print" name="localPrint" type="number" min="0" max="250" step="0.01" inputmode="decimal" placeholder="Votre devis, ramené à l'unité" required>
                    </label>
                    <label for="compare-bookstore-discount">Remise professionnelle accordée à la librairie (%)
                        <input id="compare-bookstore-discount" name="discount" type="number" min="0" max="90" step="0.5" inputmode="decimal" placeholder="À négocier, aucune valeur imposée" required>
                    </label>
                </div>
                <p class="local-small">Le prix public de 15 € est un <strong>exemple modifiable</strong>, pas un tarif conseillé. La remise n'est ni choisie ni prélevée par la Librairie universelle. Ces montants sont recalculés localement par votre navigateur, sans envoi ni conservation.</p>
            </form>
            <p id="amazon-comparison-status" role="status" aria-live="polite">Renseignez le coût KDP, le coût d'impression local et la remise libraire pour voir vos estimations.</p>
            <div id="amazon-comparison-results" class="local-margin-results" hidden>
                <article>
                    <h4>Amazon.fr · vente KDP standard</h4>
                    <strong id="compare-kdp-result" class="local-margin-amount">—</strong>
                    <p id="compare-kdp-rule"></p>
                    <small>Prix catalogue HT × 50 % ou 60 % − coût d'impression KDP.</small>
                </article>
                <article>
                    <h4>Vente directe via notre catalogue</h4>
                    <strong id="compare-direct-result" class="local-margin-amount">—</strong>
                    <p id="compare-direct-rule"></p>
                    <small>Prix HT − fabrication locale HT − 3 % du prix TTC, uniquement si notre convention prévoit une commission pour la vente concernée.</small>
                </article>
                <article>
                    <h4>Vente à une librairie locale</h4>
                    <strong id="compare-store-result" class="local-margin-amount">—</strong>
                    <p id="compare-store-rule"></p>
                    <small>Prix HT × (1 − remise négociée) − fabrication locale HT ; aucune commission de référencement présumée.</small>
                </article>
            </div>
            <p class="local-small"><strong>Lecture des résultats :</strong> il s'agit de montants restant après les seuls coûts saisis, <strong>pas de bénéfices nets</strong>. Hypothèse : vente de livre broché sur Amazon.fr, TVA livre de 5,5 % en France métropolitaine, auteur ou éditeur pouvant déduire la TVA sur ses dépenses et prix local saisi hors taxe. Si vous ne récupérez pas la TVA, vos coûts réels seront différents. Les frais de livraison, de paiement, l'emballage, les retours, les invendus, le temps de travail, les cotisations et les impôts sont exclus. Les exemples ne remplacent pas un devis ni un conseil fiscal. La distribution étendue KDP (40 %) n'est pas simulée.</p>
            <p class="local-small"><a href="https://kdp.amazon.com/fr_FR/help/topic/G201834330" target="_blank" rel="noopener noreferrer">Barème officiel KDP : 50 % ou 60 % et 40 % pour la distribution étendue</a> · <a href="/conditions-generales.php">Notre commission de référencement : conditions exactes</a></p>
        </section>
        <details class="local-guide-details" id="exemple-papier">
            <summary>Voir l'exemple papier à 15 € et les tarifs Corep Metz</summary>
            <div class="local-guide-details-content">

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



            </div>
        </details>
        <details class="local-guide-details" id="exemple-epub">
            <summary>Comparer aussi un EPUB à 6 € TTC</summary>
            <div class="local-guide-details-content">

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

            </div>
        </details>
    </section>
    <section class="content-section local-social-section" id="enjeux-sociaux" aria-labelledby="social-title">
        <p class="section-kicker">Emplois · librairies · conditions de travail</p>
        <h2 id="social-title">Un livre acheté, c'est aussi un modèle social que l'on soutient.</h2>
        <p>Comparer seulement la marge de l'auteur ou le prix de livraison laisse de côté les personnes qui font circuler le livre : libraires, employés d'entrepôt, préparateurs de commandes et livreurs. Cet enjeu ne se résume pas à opposer des salariés « locaux » à ceux d'Amazon. Il s'agit aussi de la répartition des ventes, de la qualité de l'emploi et de la diversité culturelle.</p>
        <div class="local-social-grid">
            <article>
                <h3>Le tissu des librairies est fragile</h3>
                <p>En 2025, le Centre national du livre a recensé <strong>83 ouvertures et 85 fermetures</strong> de librairies, ainsi que 57 reprises. Le ministère de la Culture indiquait en septembre 2026 un réseau d'environ <strong>3 200 librairies et 14 000 emplois</strong>. La concurrence des plateformes compte, mais les loyers, les charges, la baisse des achats et la concentration de l'édition fragilisent aussi le secteur : il serait inexact d'attribuer chaque fermeture à Amazon.</p>
                <a href="https://centrenationaldulivre.fr/actualites/librairies-creations-fermetures-reprises-chiffres-2025" target="_blank" rel="noopener noreferrer">Chiffres 2025 du Centre national du livre</a>
            </article>
            <article>
                <h3>Les conditions de travail de la logistique comptent</h3>
                <p>En décembre 2023, la CNIL a sanctionné <strong>Amazon France Logistique de 32 millions d'euros</strong> pour un dispositif de surveillance des performances des salariés jugé excessivement intrusif. Une enquête publiée en 2025 fait également état de critiques syndicales sur les cadences, l'intérim et l'usure physique en entrepôt. Ce sont des constats documentés ; ils ne signifient pas que tous les postes du groupe connaissent les mêmes conditions.</p>
                <a href="https://www.cnil.fr/sites/default/files/2024-05/cnil_44e_rapport_annuel_2023.pdf" target="_blank" rel="noopener noreferrer">CNIL : sanction et surveillance des salariés</a>
            </article>
            <article>
                <h3>Un libraire, ce n'est pas qu'un point de retrait</h3>
                <p>Conseiller un lecteur, faire découvrir une petite maison d'édition, accueillir un événement, maintenir un commerce culturel accessible : ces tâches ne se mesurent pas au seul coût d'un colis. Une librairie locale n'est pourtant pas automatiquement un employeur exemplaire : rémunérations et conditions de travail doivent aussi pouvoir être discutées dans ce secteur.</p>
                <a href="https://www.syndicat-librairie.fr/ressources/chiffres-cles" target="_blank" rel="noopener noreferrer">Syndicat de la librairie française : rôle et chiffres clés</a>
            </article>
        </div>
        <div class="local-social-evidence">
            <h3>Pourquoi les règles de livraison sont un enjeu collectif</h3>
            <p>Depuis octobre 2023, une commande de livres neufs de moins de 35 € expédiée à domicile entraîne au moins 3 € de frais de livraison en France. Amazon a contesté cette mesure ; le <strong>Conseil d'État a rejeté son recours le 13 mai 2026</strong>. Le rapport gouvernemental d'octobre 2025 observe un effet probablement favorable aux achats en magasin, sans pouvoir réduire toutes les difficultés de la filière à cette seule règle.</p>
            <p>Le modèle en ligne crée également des emplois et peut faciliter l'accès aux livres pour des personnes éloignées d'une librairie ou ayant des difficultés à se déplacer. <strong>La vente locale n'est donc pas une obligation morale ni une solution universelle</strong> : notre rôle est de rendre les alternatives visibles, y compris la commande auprès d'une librairie indépendante sans déplacement inutile.</p>
        </div>
        <p class="local-small"><strong>À notre échelle :</strong> l'annuaire et le parcours auteur permettent de contacter directement des librairies, sans les obliger à rejoindre un nouveau logiciel, sans afficher de stock inventé et sans garantir de mise en rayon. Les retombées économiques ou sociales du service lui-même ne sont pas encore mesurées.</p>
        <div class="local-guide-actions">
            <a class="button-link secondary" href="/#librairies">Trouver une librairie près de moi</a>
            <a class="text-link" href="/diffuser-mon-livre.php#fiche-libraire">Proposer mon livre à une librairie</a>
        </div>
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

    <section class="content-section local-sources" id="sources" aria-labelledby="sources-title">
        <h2 id="sources-title">Sources et méthode de comparaison</h2>
        <p>Vérifications du 10 novembre 2026. Ces exemples utilisent les conditions publiées pour Amazon.fr, les données Corep autorisées lorsqu'elles sont encore dans leur période de réexamen et les conditions actuelles de la Librairie. Le scénario est illustratif, pas un devis ni une garantie de revenu.</p>
        <ul>
            <li><a href="https://centrenationaldulivre.fr/actualites/librairies-creations-fermetures-reprises-chiffres-2025" target="_blank" rel="noopener noreferrer">CNL — créations, fermetures et reprises de librairies en 2025</a> (publié en juin 2026).</li>
            <li><a href="https://questions.assemblee-nationale.fr/q17/17-16988QE.htm" target="_blank" rel="noopener noreferrer">Ministère de la Culture — chiffres de l'emploi en librairie</a> (réponse publiée en septembre 2026).</li>
            <li><a href="https://www.cnil.fr/sites/default/files/2024-05/cnil_44e_rapport_annuel_2023.pdf" target="_blank" rel="noopener noreferrer">CNIL — surveillance des salariés d'Amazon France Logistique</a> (rapport annuel 2023, sanction prononcée fin 2023).</li>
            <li><a href="https://www.lemonde.fr/economie/article/2025/04/30/la-logistique-vivier-d-emplois-mais-a-quel-prix_6601858_3234.html" target="_blank" rel="noopener noreferrer">Le Monde — enquête sur la logistique, les emplois et les conditions de travail</a> (avril 2025).</li>
            <li><a href="https://www.conseil-etat.fr/site/actualites/le-conseil-d-etat-rejette-le-recours-d-amazon-contre-le-montant-minimal-des-frais-de-livraison-des-livres" target="_blank" rel="noopener noreferrer">Conseil d'État — recours d'Amazon contre les frais de livraison</a> (13 mai 2026).</li>
            <li><a href="https://www.culture.gouv.fr/thematiques/livre-et-lecture/documentation/publications/rapport-d-evaluation-sur-la-tarification-minimale-des-frais-de-livraison-de-livres" target="_blank" rel="noopener noreferrer">Ministère de la Culture — évaluation des frais de livraison</a> (rapport remis en octobre 2025).</li>
            <li><a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000042814694" target="_blank" rel="noopener noreferrer">Code de la propriété intellectuelle, article L111-1</a> : droits de l'auteur dès la création.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G7BBN68RYX5UMDZF" target="_blank" rel="noopener noreferrer">Amazon KDP — exemplaires d'auteur revendables et épreuves non destinées à la vente</a>.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G200672400" target="_blank" rel="noopener noreferrer">Amazon KDP — droits d'auteur</a>, <a href="https://kdp.amazon.com/fr_FR/help/topic/GD9PMU58BV24QFZ7" target="_blank" rel="noopener noreferrer">KDP Select</a>, <a href="https://kdp.amazon.com/fr_FR/help/topic/G201834170" target="_blank" rel="noopener noreferrer">ISBN gratuit ou personnel</a> et <a href="https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45" target="_blank" rel="noopener noreferrer">distribution étendue</a>.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G201834340" target="_blank" rel="noopener noreferrer">KDP — coûts d'impression</a>, <a href="https://kdp.amazon.com/fr_FR/help/topic/G201834330" target="_blank" rel="noopener noreferrer">redevances du broché</a> et <a href="https://kdp.amazon.com/fr_FR/help/topic/G200644210" target="_blank" rel="noopener noreferrer">redevances des ebooks</a>.</li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45" target="_blank" rel="noopener noreferrer">KDP — distribution étendue</a> : absence de garantie de référencement ou de commande en librairie.</li>
            <li><a href="https://www.economie.gouv.fr/particuliers/mes-droits-conso/bien-consommer/affichage-des-prix-ce-que-vous-devez-savoir" target="_blank" rel="noopener noreferrer">Ministère de l'Économie — prix unique du livre neuf</a> : le prix ne doit pas varier librement selon les vendeurs.</li>
            <li><a href="https://www.ademe.fr/presse/communique-national/e-commerce-un-outil-pour-evaluer-et-reduire-limpact-environnemental-de-la-logistique-des-transports-et-des-deplacements/" target="_blank" rel="noopener noreferrer">ADEME — empreinte environnementale des achats et livraisons</a>.</li>
            <li>Corep Metz : grille indicative TTC reçue et autorisée par courriel en octobre 2026, contrôlée jusqu'au 17 novembre 2026. La configuration exacte et le tarif doivent toujours être confirmés directement avec l'imprimeur.</li>
        </ul>
    </section>
    <section class="content-section local-guide-finish" aria-labelledby="guide-finish-title">
        <h2 id="guide-finish-title">Et ensuite ?</h2>
        <p>Vous gardez le choix des circuits, dans les limites des droits et engagements signés.</p>
        <div class="local-guide-actions">
            <a class="button-link" href="/autoedition.php?partie=paper">Préparer mon livre</a>
            <a class="button-link secondary" href="/diffuser-mon-livre.php#fiche-libraire">Contacter des librairies</a>
            <a class="text-link" href="/publier-et-lire-local.php">Revenir aux trois parcours</a>
        </div>
    </section>
</main>
<footer class="local-argument-footer">
    <a href="/">Accueil</a><a href="/autoedition.php">Autoédition</a><a href="/diffuser-mon-livre.php">Librairies locales</a><a href="/conditions-generales.php">Conditions</a>
    <p>Librairie universelle · une initiative du Potager du Web, expérimentée en Moselle.</p>
</footer>
</body>
</html>
