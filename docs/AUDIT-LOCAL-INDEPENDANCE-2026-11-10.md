# Audit du positionnement local et de l'indépendance des auteurs — 10 novembre 2026

Périmètre : page d'accueil, « Le projet », parcours papier et EPUB, fiches de vente directe sous convention, annuaire de librairies, parcours de présentation aux libraires, CGV, comparaison avec KDP. Contrôles croisés avec repo-factory avant modification.

## Constats avant amélioration

1. Le site mettait déjà en avant les librairies à Metz et en Moselle et leur recherche par distance, mais le lecteur et l'auteur devaient parcourir des pages distinctes pour comprendre **la valeur concrète du local**.
2. « Le projet » était surtout un état d'avancement du MVP. Il ne présentait pas clairement les droits conservés, ni les trois circuits possibles : vente directe, librairies physiques, impression à la demande.
3. Le parcours \`/diffuser-mon-livre.php\` proposait déjà une **fiche et un message pour les libraires**, réalisés dans le navigateur, sans envoi automatique. Il manquait une articulation explicite avec les coûts, la commission de 3 % et la différence entre vendre soi-même et vendre à une librairie.
4. Les tarifs Corep sont **des devis autorisés et datés**, pas un tarif garanti. La grille en place prévoit un réexamen le 17 novembre 2026. DropCom est un imprimeur potentiel, pas un partenaire confirmé ni une grille autorisée à publier.
5. Une accusation « Amazon prend la propriété du livre » serait fausse : KDP ne devient pas titulaire des droits d'auteur ; KDP Select prévoit une exclusivité numérique temporaire et facultative. **Les livres papier peuvent être vendus ailleurs**, y compris sur des salons, le site de l'auteur ou des librairies. L'ISBN gratuit KDP est réservé aux publications chez KDP ; des exemplaires imprimés chez KDP peuvent être revendus ailleurs. La distribution étendue KDP a ses propres règles de non-conflit d'ISBN.

## Comparaison économique publiée : scénario vérifiable

Même scénario dans toute la page : broché A5, 120 pages, noir et blanc, prix public **15 € TTC**, TVA livre à 5,5 % ; marché France / Amazon.fr.

KDP standard Europe : coût fixe 0,75 € + 120 × 0,012 €/page = **2,19 €** de fabrication par exemplaire. Redevance estimée à 60 % du prix catalogue hors TVA, moins impression : \`0,60 × (15 / 1,055) - 2,19 ≈ 6,34 €\`. Amazon fabrique et vend l'exemplaire, l'auteur ne constitue pas de stock.

Corep Metz, source \`data/corep-metz-approved.json\` : 10 ex. 120,82 € TTC (12,082 € chacun), 50 ex. 347,05 € TTC (6,941 €), 100 ex. 584,84 € TTC (5,8484 €). Après seulement coût du tirage alloué et 3 % de commission sur vente directe apportée (0,45 € sur 15 €), montants restants approximatifs par exemplaire : **2,47 €**, **7,61 €**, **8,70 €**. Les montants ne sont **pas un revenu net** : le tirage est financé à l'avance, les ventes ne sont pas garanties et restent à déduire frais, livraison, retours, stocks invendus, temps, taxes, cotisations. Les finitions peuvent différer de KDP. La grille est cachée automatiquement après son terme de réexamen.

Pour un EPUB à **6 € TTC**, la vente directe via la Librairie laisse 5,82 € **avant** coûts de paiement/gestion/vente et fiscalité du vendeur. KDP peut offrir 35 % ou 70 % du prix HT selon les conditions, le taux 70 % étant soumis notamment aux frais de livraison numérique du fichier. Les montants ne représentent donc pas des revenus nets comparables poste pour poste.

La **librairie physique** est un troisième modèle, distinct de la commission de 3 % : dépôt-vente / achat ferme / remise / invendus sont négociés entre l'auteur et le libraire. Ne pas inventer une « marge locale » universelle pour les libraires.

## Écologie — pas de chiffre CO₂ inventé

Les données disponibles ne suffisent pas pour un bilan carbone comparable par livre : il faudrait au minimum le lieu et le procédé d'impression, le papier réel, l'énergie de l'atelier, le tirage, les invendus, l'emballage, les livraisons, les retours et surtout le déplacement du lecteur. L'ADEME montre que le commerce en ligne et le commerce en magasin ne se classent pas de manière absolue ; selon les trajets et modes de transport, les résultats s'inversent.

Scénarios expliqués sans inventer d'économies :
- livre en stock chez un libraire de proximité, retrait à pied/vélo : favorable sur le déplacement dédié et le colis individuel ;
- petite série locale adaptée à la demande : peut limiter certaines étapes de transport, mais attention aux invendus ;
- impression à la demande : avantage possible sur le stock, mais livraison/emballage restant à mesurer ;
- aller-retour dédié en voiture : peut rendre un retrait local moins favorable qu'une livraison mutualisée.

## Modifications

- Page \`/publier-et-lire-local.php\`, SEO/canonical/OG et sources officielles, avec sections Droits, Circuits, Librairies, Chiffres, Écologie, Limites.
- Accueil : trois cartes de choix placées après l'annuaire de proximité, avant la recherche bibliographique.
- « Le projet » : un passage clair sur les droits, la non-exclusivité et le prix écologique non présumé.
- « Contacter des librairies » : rappel du parcours de proximité, conditions d'achat ferme/dépôt-vente et séparation des commissions.
- Navigation partagée « À propos » : « Vos droits et le local ». Sitemap : ajout de la nouvelle page.
- CSS léger et autonome, sans image ni ressource externe et tableau horizontalement scrollable sur mobile.
- Tests PHP de sincérité des chiffres et des promesses, tests navigation et page projet, puis captures Chromium 320, 375, 430 et 1280 px, à vérifier dans la CI après le commit.

## Sources à maintenir

- Code de la propriété intellectuelle L111-1 : https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000042814694
- KDP droits : https://kdp.amazon.com/fr_FR/help/topic/G200672400
- KDP Select : https://kdp.amazon.com/fr_FR/help/topic/GD9PMU58BV24QFZ7
- KDP ISBN : https://kdp.amazon.com/fr_FR/help/topic/G201834170
- KDP distribution étendue : https://kdp.amazon.com/fr_FR/help/topic/GQTT4W3T5AYK7L45
- KDP impression : https://kdp.amazon.com/fr_FR/help/topic/G201834340
- KDP royalties broché : https://kdp.amazon.com/fr_FR/help/topic/G201834330
- KDP royalties ebook : https://kdp.amazon.com/fr_FR/help/topic/G200644210
- ADEME : https://www.ademe.fr/presse/communique-national/e-commerce-un-outil-pour-evaluer-et-reduire-limpact-environnemental-de-la-logistique-des-transports-et-des-deplacements/
- Corep : \`data/corep-metz-approved.json\`, à ne pas prolonger sans confirmation.

## Limites résiduelles

Pas d'accord de référencement commercial connu avec les librairies locales ; le parcours ne promet aucune mise en rayon. Le prix de la grille Corep doit être réévalué à la date prévue. Aucun chiffre CO₂ comparatif sans données d'activité des ateliers et des transports. Pas de paiement ou de suivi des ventes intégré à la Librairie ; seule une convention signée ouvre les ventes directes. Les mentions légales complètes et la politique de confidentialité restent à finaliser séparément.
