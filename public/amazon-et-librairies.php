<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/discovery-bootstrap.php';
header('Referrer-Policy: no-referrer');
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Vendre son livre sur Amazon et en librairie — Librairie universelle</title>
    <meta name="description" content="Livre papier sur Amazon KDP et en librairie : droits d'auteur, ISBN, KDP Select, revente d'exemplaires et comparaison des marges selon vos vrais coûts.">
    <link rel="canonical" href="https://librairie.lepotager.org/amazon-et-librairies.php">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/diffusion-auteur.css?v=20261020-1">
    <link rel="stylesheet" href="/assets/css/amazon-local.css?v=20261110-1">
    <script src="/assets/js/amazon-local.js?v=20261110-1" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'amazon'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main" class="author-sales-page amazon-local-page">
    <section class="page-hero compact">
        <p class="eyebrow">Autoédition · choisir sans s'enfermer</p>
        <h1>Déjà sur Amazon ? Vous pouvez aussi vendre votre livre ailleurs.</h1>
        <p class="lede"><strong>La publication papier sur KDP n'est pas exclusive.</strong> Vous pouvez proposer vos exemplaires à des lecteurs, sur votre propre site ou auprès d'une librairie, sous réserve de détenir les droits nécessaires et de respecter vos autres contrats. Le point de vigilance principal n'est pas la propriété de votre texte : c'est souvent l'ISBN et l'organisation de la distribution.</p>
        <div class="cta-row">
            <a class="button-link" href="#choisir">Trouver mon parcours</a>
            <a class="button-link secondary" href="#comparer">Comparer les montants en €</a>
        </div>
    </section>

    <section class="sales-steps" aria-labelledby="rights-title">
        <h2 id="rights-title">Qu'est-ce qui reste à vous ?</h2>
        <div class="sales-step-grid">
            <article class="sales-step">
                <h3>Vos droits d'auteur</h3>
                <p>Publier une création originale sur KDP ne transfère pas automatiquement vos droits d'auteur à Amazon. Vérifiez néanmoins tout contrat de cession antérieur : vous devez disposer des droits de publier le format concerné sur chaque territoire.</p>
            </article>
            <article class="sales-step">
                <h3>Votre édition et son ISBN</h3>
                <p>Un ISBN gratuit attribué par KDP sert <strong>uniquement sur KDP</strong>. Il ne peut pas être repris pour une édition publiée chez un autre imprimeur-éditeur. Si vous souhaitez choisir plusieurs circuits, l'AFNIL recommande un ISBN enregistré à votre nom ou à celui de votre structure éditrice. Un ISBN identifie une édition, il ne crée pas de droit d'auteur.</p>
            </article>
            <article class="sales-step">
                <h3>Les exemplaires déjà imprimés</h3>
                <p>Vous pouvez <strong>revendre les exemplaires d'auteur commandés chez KDP</strong>, y compris en dehors d'Amazon. En revanche, les épreuves portant « Non destiné à la vente » ne sont pas commercialisables.</p>
            </article>
            <article class="sales-step">
                <h3>Le cas particulier de l'ebook</h3>
                <p>Le programme <strong>KDP Select</strong> exige l'exclusivité de la version numérique pendant chaque période d'inscription de 90 jours. Ce n'est pas une exclusivité papier. Sans KDP Select, vérifiez les autres conditions contractuelles et les droits dont vous disposez.</p>
            </article>
        </div>
        <p class="sales-truth"><strong>Attention :</strong> publier chez KDP et faire imprimer chez un autre prestataire ne sont pas la même opération que revendre vos exemplaires d'auteur. Pour une nouvelle fabrication, vérifiez l'ISBN, l'éditeur déclaré, l'identité de l'édition et les éventuelles exclusivités de diffusion. Évitez d'activer plusieurs réseaux de distribution pour la même édition sans vérifier leurs conditions.</p>
    </section>

    <section id="choisir" class="sales-after" aria-labelledby="choose-title">
        <h2 id="choose-title">Quel parcours correspond à votre situation ?</h2>
        <div class="sales-step-grid">
            <article class="sales-step">
                <h3>J'ai déjà un livre papier sur Amazon</h3>
                <p>Commencez par commander des exemplaires d'auteur, puis présentez-les à des librairies. Si vous voulez faire imprimer ailleurs, vérifiez d'abord l'ISBN : celui fourni gratuitement par KDP ne s'utilise pas chez un autre prestataire.</p>
                <p><a href="/diffuser-mon-livre.php#fiche-libraire">Préparer ma fiche pour les librairies</a></p>
            </article>
            <article class="sales-step">
                <h3>Je veux imprimer près de chez moi</h3>
                <p>Préparez un PDF d'intérieur, vos couvertures et un dossier de devis. Vous pouvez examiner le parcours Corep Metz et l'option Bookelis, sans obligation de travailler avec eux ni partenariat commercial sous-entendu.</p>
                <p><a href="/autoedition.php?partie=paper">Préparer mes fichiers et comparer les parcours</a></p>
            </article>
            <article class="sales-step">
                <h3>Je veux être trouvé hors Amazon</h3>
                <p>Proposez une fiche descriptive au catalogue ; la publication est conditionnée à l'examen du dossier et à la convention de référencement. L'auteur garde ses fichiers. La Librairie ne traite pas encore les paiements ni les expéditions.</p>
                <p><a href="/proposer-livre.php">Proposer mon livre</a></p>
            </article>
            <article class="sales-step">
                <h3>Je veux travailler avec une vraie librairie</h3>
                <p>Repérez quelques établissements proches et discutez prix public, remise professionnelle, facturation, dépôt-vente éventuel, disponibilité et retours. Un référencement n'est pas un accord de distribution.</p>
                <p><a href="/#librairies">Trouver des librairies proches</a></p>
            </article>
        </div>
    </section>

    <section id="comparer" class="sales-tool" aria-labelledby="compare-title">
        <div class="sales-tool-intro">
            <p class="eyebrow">Comparaison transparente · estimation, pas promesse</p>
            <h2 id="compare-title">Combien me reste-t-il par exemplaire ?</h2>
            <p>Les tarifs d'impression varient selon le livre. Renseignez <strong>vos devis réels</strong> : nous ne substituons pas des prix fictifs aux tarifs de KDP, de Corep, de Bookelis ou d'une librairie. Les calculs se font exclusivement dans votre navigateur, sans transmission des montants.</p>
        </div>
        <form id="amazon-local-calculator" class="amazon-calculator sales-pitch-form" novalidate>
            <div class="amazon-form-grid">
                <label for="compare-public-price">Prix public TTC du livre neuf, en € <span class="optional">(France métropolitaine)</span>
                    <input type="number" id="compare-public-price" name="publicPrice" min="0.01" max="250" step="0.01" inputmode="decimal" value="18" required>
                </label>
                <label for="compare-kdp-print">Coût d'impression KDP, en € <span class="optional">(valeur affichée par KDP)</span>
                    <input type="number" id="compare-kdp-print" name="kdpPrint" min="0" max="250" step="0.01" inputmode="decimal" placeholder="À relever dans KDP" required>
                </label>
                <label for="compare-local-print">Coût de fabrication local par exemplaire, en € HT
                    <input type="number" id="compare-local-print" name="localPrint" min="0" max="250" step="0.01" inputmode="decimal" placeholder="Votre devis à l'unité" required>
                </label>
                <label for="compare-bookstore-discount">Remise accordée à la librairie, en %
                    <input type="number" id="compare-bookstore-discount" name="discount" min="0" max="90" step="0.5" inputmode="decimal" placeholder="À négocier avec elle" required>
                </label>
            </div>
            <p class="sales-field-help">Hypothèses : livre broché sur Amazon.fr, même prix public TTC, TVA du livre à 5,5 %, frais d'impression locale renseignés hors taxe. Le simulateur indique un <strong>reste estimatif avant autres frais et impôts</strong>, pas un bénéfice net ni un devis. Le taux KDP dépend de son prix catalogue hors taxe : 50 % jusqu'à 9,98 € ; 60 % à partir de 9,99 €. Sur KDP, la redevance est calculée avant déduction du coût d'impression.</p>
        </form>
        <p id="amazon-comparison-status" role="status" aria-live="polite">Complétez les trois coûts et la remise pour afficher la comparaison.</p>
        <div id="amazon-comparison-results" class="amazon-result-grid" hidden>
            <article class="sales-step">
                <h3>KDP · vente sur Amazon.fr</h3>
                <p class="amazon-result-amount" id="compare-kdp-result">—</p>
                <p id="compare-kdp-rule"></p>
                <p>Redevance estimée = prix catalogue HT × taux KDP − coût d'impression KDP.</p>
            </article>
            <article class="sales-step">
                <h3>Vente directe via notre catalogue</h3>
                <p class="amazon-result-amount" id="compare-direct-result">—</p>
                <p id="compare-direct-rule"></p>
                <p>Prix hors TVA − coût d'impression local − commission de référencement de 3 % du TTC, uniquement en cas de vente donnant lieu à cette commission selon la convention.</p>
            </article>
            <article class="sales-step">
                <h3>Vente négociée avec une librairie</h3>
                <p class="amazon-result-amount" id="compare-store-result">—</p>
                <p id="compare-store-rule"></p>
                <p>Prix hors TVA − remise négociée − fabrication locale. Ici, pas de commission de référencement supposée ; vérifiez votre accord et l'origine de la vente.</p>
            </article>
        </div>
        <p class="sales-field-help">Non inclus : livraison, stockage, emballages, frais de paiement, retours, invendus, cotisations, impôts, spécificités de votre régime de TVA et éventuels autres frais. Un montant négatif signifie que les coûts saisis dépassent la recette estimée. La TVA réelle et le coût de revient peuvent changer selon votre situation.</p>
        <div class="cta-row">
            <a class="button-link secondary" href="https://kdp.amazon.com/fr_FR/help/topic/G201834330" target="_blank" rel="noopener noreferrer">Règles de redevance KDP</a>
            <a class="text-link" href="/conditions-generales.php">Notre convention de référencement</a>
        </div>
    </section>

    <section class="sales-after" aria-labelledby="eco-title">
        <h2 id="eco-title">Et le coût écologique ?</h2>
        <div class="sales-after-grid">
            <div><h3>Éviter les exemplaires inutiles</h3><p>L'impression à la demande peut éviter les invendus ; un petit tirage local vendu réellement peut aussi être pertinent. Comparez le nombre d'exemplaires produits et finalement lus.</p></div>
            <div><h3>Regarder les trajets réels</h3><p>Un retrait à pied, à vélo ou pendant un trajet habituel peut éviter un colis et son emballage. Un aller-retour dédié en voiture peut au contraire effacer l'avantage local.</p></div>
            <div><h3>Ne pas fabriquer des « grammes de CO₂ »</h3><p>Sans données sur l'imprimerie, le papier, le tirage, les transports, les retours et le trajet du lecteur, nous n'affichons pas de bilan carbone chiffré. Le choix local n'est pas automatiquement le moins émetteur.</p></div>
        </div>
    </section>

    <section class="sales-after amazon-sources" aria-labelledby="sources-title">
        <h2 id="sources-title">Vérifier les règles à la source</h2>
        <p>Sources officielles consultées le 10 novembre 2026. Elles peuvent évoluer ; les contrats particuliers prévalent pour les droits effectivement accordés.</p>
        <ul>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/GHKDSCW2KQ3K4UU4" target="_blank" rel="noopener noreferrer">Amazon KDP : publier aussi sur d'autres plateformes</a></li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G201834170" target="_blank" rel="noopener noreferrer">Amazon KDP : ISBN gratuit et ISBN personnel</a></li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/G7BBN68RYX5UMDZF" target="_blank" rel="noopener noreferrer">Amazon KDP : revente des exemplaires destinés à l'auteur</a></li>
            <li><a href="https://kdp.amazon.com/fr_FR/help/topic/GD9PMU58BV24QFZ7" target="_blank" rel="noopener noreferrer">Amazon KDP : exclusivité ebook KDP Select</a></li>
            <li><a href="https://www.afnil.org/foire-aux-questions/" target="_blank" rel="noopener noreferrer">AFNIL : ISBN et identité de l'éditeur</a></li>
            <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F22713" target="_blank" rel="noopener noreferrer">Service public : TVA et prix du livre en France</a></li>
        </ul>
        <div class="cta-row">
            <a class="button-link" href="/diffuser-mon-livre.php">Préparer mes contacts libraires</a>
            <a class="button-link secondary" href="/autoedition.php?partie=paper">Préparer mon édition papier</a>
        </div>
    </section>
</main>
<footer>
    <div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/diffuser-mon-livre.php">Vendre mon livre</a><a href="/projet.php">Le projet</a></div>
    <p>Les droits, les coûts et les possibilités de diffusion doivent rester lisibles.</p>
</footer>
</body>
</html>
