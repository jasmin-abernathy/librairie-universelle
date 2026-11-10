# Assistant papier — étapes dynamiques

Version du 8 novembre 2026. L’outil principal reste `/autoedition.php?partie=paper` : il ne délègue ni la vente ni les manuscrits aux imprimeurs. Les fichiers restent sur l’appareil lors de la préparation du kit ; les uploads nécessaires à l’analyse PDF et à la pagination sont temporaires, côté hébergement, sans transmission à Corep ni à Bookelis.

## Parcours

Le parcours est un assistant en cinq écrans dans **le même conteneur** (`public/assets/js/paper-wizard.js`, `paper-wizard.css`) :

1. Importer un DOCX/ODT/EPUB à recomposer **ou** un PDF déjà maquetté. Le fichier original n’est pas modifié.
2. Pour DOCX/ODT/EPUB seulement : sélectionner le prestataire et le format, régler marges, pagination et sommaire. Le début des chapitres n’est plus réglable : il reste positionné à droite. Aucun choix manuel de fonds perdus ; le profil Corep utilise 3 mm pour la recomposition, Bookelis reste à 0 dans ce moteur pour ne pas faussement certifier 5 mm sur des pages bord à bord.
3. Choisir la couverture et, si besoin, saisir un ISBN papier officiel et générer son EAN-13 en SVG, ou utiliser une image existante, ou laisser Bookelis le placer. Un tutoriel dirige vers le formulaire AFNIL. Le code à barres local ne constitue pas une attribution d’ISBN ni une certification d’impression.
4. Comparer les coûts et simuler un prix de vente libre : Corep uniquement sur ses paliers de grille datée, Bookelis via son calculateur officiel ou un coût unitaire réel saisi par l’utilisateur. La recommandation est arrondie à l’euro, sur une hypothèse de rémunération brute définie par l’auteur et de remise libraire de 30 % lorsqu’elle s’applique.
5. Revoir le PDF, puis constituer localement un ZIP propre au prestataire : **Corep** (intérieur + deux couvertures PDF + fiche) ou **Bookelis** (intérieur + couverture complète à plat depuis le gabarit officiel + fiche + consignes). Les conversions PDF/X éventuelles et le BAT restent des actions séparées.

La navigation entre écrans ne recharge pas l’HTML principal. Les contrôles et messages sont accessibles au clavier et visibles sur mobile ; le manuscrit et les autres champs ne sont pas effacés au changement de réglage.

## Pagination et PDF

- **DOCX/ODT/EPUB :** un POST dans un iframe *même origine* permet à Paged.js de produire la pagination réelle **sans créer de PDF final** (`estimate_only` dans `print-preview.php`). Le résultat retourne exclusivement le nombre de pages, l’imprimeur et le format via `postMessage` contrôlé sur la même origine. Le calcul est relancé avec temporisation quand les paramètres changent. L’aperçu définitif se fait dans un iframe visible dans la dernière étape, puis le navigateur permet d’enregistrer le PDF. Le contrôle visuel reste obligatoire.
- **PDF déjà maquetté :** `analyser-pdf.php` lit le nombre de pages et le format via Ghostscript (`-dSAFER` et timeout), avec repli `pdfinfo` si ce dernier est disponible sur un upload éphémère, sans reproportionner le document. Le format reporté provient de la première page ; le reste du document et les fonds perdus nécessitent un contrôle. Si `pdfinfo` n’est pas disponible, le message le dit explicitement : aucune estimation présentée comme vérifiée.
- La limite Corep connue est de **680 pages** en A5 sur papier 80 g ; le profil Corep est exclu en cas de dépassement ou de format PDF incompatible. Les autres conditions sont à confirmer par l’agence.
- Le calculateur Bookelis ne dispose pas d’API ou de grille autorisée dans le dépôt. Aucun prix Bookelis en direct n’est inventé. Le coût de fabrication, l’impression, le transport, la commission de librairie et la fiscalité ne se confondent pas.

## Consignes Bookelis et Corep

- Corep Metz : dos carré collé A5, intérieur N&B 80 g, couverture couleur en faces séparées sans fond perdu ni traits de coupe d’après les échanges pilotes ; validation finale par l’atelier.
- Bookelis : couverture à plat (quatrième + tranche + première) selon son gabarit officiel. Fond perdu public documenté de 8 mm en couverture et 5 mm en intérieur **si les éléments vont au bord** ; ces exigences ne doivent pas être remplacées par un fond perdu universel de 3 mm. Un texte sans élément au bord peut ne pas nécessiter de fond perdu.
- Les kits vérifient les signatures de début et de fin PDF mais ne garantissent pas la conformité prépresse, les polices incorporées, les couleurs, les cotes de tranche ou un BAT accepté. L’utilisateur soumet lui-même les fichiers au prestataire.

## Tests et publication

CI : syntaxe PHP/JS et tests du parcours historique. La production doit être vérifiée séparément via la procédure Git → o2switch ; une CI verte n’est pas une preuve de déploiement ni de bon fonctionnement en situation réelle.

## Import unique (8 novembre, correctif UX)

Dans le navigateur moderne, un seul champ « Importer mon livre » accepte PDF, DOCX, ODT et EPUB. Le format est reconnu après sélection, avec explication contextuelle : le PDF existant conserve sa pagination et passe à la couverture ; les manuscrits passent par le moteur de mise en page et le pré-calcul. Les noms internes de téléversement `print_pdf` et `office_manuscript` ne changent pas afin de conserver toutes les routes existantes. Si le navigateur n’autorise pas le transfert de `FileList`, les deux champs historiques réapparaissent comme solution de compatibilité ; sans JavaScript le formulaire historique est disponible.

## Choix du papier avant la pagination et calcul de couverture (10 novembre 2026)

Le choix de l’imprimeur et du **papier intérieur** se fait désormais sur le premier écran d’import, avant les réglages et le calcul de pages (y compris pour les PDF déjà maquettés). Corep conserve son seul papier documenté pour la grille A5 : offset 80 g. Bookelis affiche les papiers documentés par sa FAQ : offset blanc 80 g, Olin blanc 90 g, bouffant blanc 80 g, Cyclus Offset recyclé 90 g et couché 115/150 g selon les formats. Un papier non disponible pour le prestataire/format est rejeté dans `PrintSettings::normalize`.

Pour le papier Bookelis, le troisième écran propose un **calculateur de couverture à plat** (quatrième + tranche + première) avec illustrations, titre, auteur, résumé, prix et EAN-13 dérivé uniquement d’un ISBN valide. Le nombre de pages provient de la pagination calculée / du PDF analysé. **Le grammage seul ne donne jamais une largeur de dos fiable** : l’auteur doit saisir l’épaisseur réelle d’une feuille obtenue de l’imprimeur ou la largeur du dos directement communiquée par celui-ci. La formule est `tranche = ceil(pages / 2) × épaisseur_feuille_mm`; une cote de tranche directe prime. Le fond perdu est configurable, avec 8 mm par défaut pour Bookelis, à confronter au gabarit officiel. La reliure rigide n’est pas exportable par cet outil, car elle demande charnières et rabats.

L’aperçu montre les plis et coupes par des guides rouges **non imprimés** ; le PDF local exporté ne comporte pas ces guides. Il contient une image de couverture à 300 ppp placée sur une page de dimensions physiques calculées, mais ce n’est **pas un PDF/X ni un BAT** : contrôle du gabarit officiel, CMJN, code-barres, visuels et marges par Bookelis obligatoire. Les images et manuscrits ne quittent pas le navigateur pour cet export. Une fois généré, le PDF de couverture peut être repris directement dans le ZIP Bookelis, ou remplacé par un PDF de couverture importé.

Corep conserve le parcours déjà validé : première et quatrième séparées, sans tranche, papier offset 80 g. Le module n’invente aucun coefficient pour les ateliers. Les coefficients cités à titre d’exemple par Bookelis sont explicitement fictifs et ne sont pas utilisés.

Sources techniques : https://www.bookelis.com/conseil/12-faire-belle-couverture-de-livre-qualite-professionnelle/ et https://livres.bookelis.com/faq?category_id=1 . Vérifications : `node tests/CoverSpreadTest.cjs` et `php tests/AutoeditionPaperFlowTest.php`.
