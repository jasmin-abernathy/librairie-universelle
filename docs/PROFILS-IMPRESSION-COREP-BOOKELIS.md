# Impression multi-prestataires : Corep Metz et Bookelis

État des sources publiques vérifiées : 2 novembre 2026. Ce document est un relevé de spécifications et un plan technique, **pas** une homologation de fichiers, une intégration commerciale ou une annonce de partenariat.

## Décision d'architecture

Conserver **un moteur commun de recomposition** DOCX/ODT → modèle du livre → pagination recto verso → PDF intérieur → contrôles, mais paramétrer le résultat avec des **profils de fabrication par imprimeur**.

Ne pas dupliquer `OfficePrintSource`, Paged.js, le sommaire et les pages blanches pour chaque imprimeur. Les différences concernent les formats, la reliure, les marges/contraintes, les fonds perdus, les couvertures, les gabarits, les validations et le mode de commande.

Le **verrouillage A5 demandé par Corep** doit demeurer **dans le profil Corep**, et non dans `PrintSettings` pour tous les imprimeurs. À la date du relevé, le code existant impose A5 globalement dans `src/PrintSettings.php`, dans la page `public/autoedition.php` et dans `public/assistant-papier.php`. Il faut les refactoriser ensemble avant de proposer un choix Bookelis.

## Comparaison des spécifications

| Domaine | Corep Metz (échanges directs, pilote local) | Bookelis (documentation publique) |
| --- | --- | --- |
| Intérieur | A5 148 × 210 mm imposé pour notre parcours local ; dos carré collé et N&B 80 g dans la grille indicative existante | 7 formats listés : 130 × 180, 148 × 210, 156 × 234, 170 × 244, 170 × 170, 210 × 297 et 190 × 150 mm (paysage) ; N&B ou couleur |
| Papier | 80 g (90 g sur devis spécifique) | Offset blanc 80 g, crème 80 g, bouffant blanc 80 g, couché demi-mat 115 g ou 150 g selon le format |
| Couverture | Première et quatrième séparées selon les précédentes indications de Corep ; la tranche reste à définir par Corep | Un document de couverture **complet à plat** (4e + tranche + 1re), dont le gabarit dépend du nombre de pages, du format, du papier et de la couverture |
| Couverture/support | Dos carré collé dans le devis pilote | Souple (carte couchée 230 g ou Rives Tradition 250 g) ou cartonnée ; finitions conditionnelles |
| Fond perdu | Demander validation au magasin pour les situations particulières | Documentation : 8 mm **couverture** ; 5 mm **intérieur** seulement si des éléments touchent le bord |
| Technique | BAT et contraintes d'impression à confirmer avec Corep | Couverture 300 dpi, CMJN ; code-barres noir 100 % sur blanc ; recul du texte de tranche ≥ 2 mm ; taux d'encrage max 240 %, selon FAQ |
| Début des chapitres | Page de droite / pages blanches nécessaires demandées par Corep | Recto verso et pages impaires à droite |
| Taille du livre | À confirmer avec Corep | FAQ Bookelis : acceptation à partir de 18 pages, ajout automatique de pages vierges pour atteindre 40 pages lorsque le fichier comporte 18–39 pages |
| Handoff | Contact/impression directement avec l'agence ; aucun transfert automatique | Redirection manuelle vers Bookelis, téléchargement des PDF et paramétrage de la fabrication par l'auteur ; aucune API/préremplissage officiel annoncé dans leur réponse du 2 novembre |
| Prix | Grille Corep autorisée, datée, limitée aux configurations exactes | Calculateur Bookelis accessible en ligne ; **ne pas reprendre des tarifs dynamiques sans accord/source adaptée** |

Bookelis limite **certaines combinaisons** selon sa collection, le papier, le format, le genre et la couverture. La simple présence de 7 formats n'implique donc pas que chaque option soit combinable avec toutes les autres.

Les recommandations de fond perdu portent sur des cas différents ; ne pas appliquer automatiquement 8 mm à l'intérieur, ni 5 mm à la couverture.

## Interfaces proposées

```text
DOCX / ODT
   └─ OfficePrintSource (commun)
       └─ Livre structuré (chapitres, images, sommaire, avertissements)
           ├─ PrintProviderProfile::corep_metz
           │   ├─ Formats : A5 uniquement
           │   ├─ PrintSettings (règles du profil)
           │   ├─ Paged.js -> intérieur A5
           │   ├─ Couverture recto et verso séparées
           │   └─ Checklist BAT + contact Corep
           └─ PrintProviderProfile::bookelis
               ├─ Formats/combinaisons selon la documentation et le produit
               ├─ PrintSettings (règles du profil)
               ├─ Paged.js -> intérieur au format sélectionné
               ├─ Couverture complète : 4e + tranche + 1re
               │   └─ Gabarit officiel Bookelis obligatoire pour valider le dos
               └─ Checklist prépresse + dépôt manuel sur Bookelis
```

Proposition de contrat `PrintProviderProfile` (PHP, sans connexion réseau) :
- `id`, `display_name`, `verified_at`, `documentation_url` ;
- `supported_trim_sizes` en mm, `default_trim_size` et contraintes de combinaisons ;
- `binding_options`, `paper_options`, `colour_options` ;
- `interior_bleed_policy`, `cover_bleed_mm`, `cover_mode` (separate_faces / full_wrap) ;
- `right_hand_chapters`, `page_count_rules`, `gutter_rule`, `min_safe_font_size` si confirmée ;
- `preflight_checks`, `handoff_url`, `status` (draft / tested / published).

Toutes les données provenant du prestataire doivent être distinguées des hypothèses et de celles obtenues par échange direct. Le nombre de pages réelles, pas l'estimation en mots, doit servir au gabarit définitif de la tranche.

## Ordre de réalisation recommandé

1. **Extraire le profil Corep A5** de `PrintSettings::normalize`, garder inchangé le comportement courant (A5, chapitres à droite et couverture séparée).
2. Ajouter un sélecteur « Où souhaitez-vous faire imprimer le livre ? » ; afficher uniquement les contraintes du profil retenu. Ne pas imposer une seconde saisie du DOCX/ODT.
3. Autoriser dans le profil Bookelis uniquement les formats documentés pour le **PDF intérieur** et afficher les limites des combinaisons ; exporter en mode **« PDF à faire vérifier »**, jamais « prêt Bookelis » sans contrôle réel.
4. Pour la couverture Bookelis, préférer **télécharger ou utiliser le gabarit officiel** lors de la première version. Ne pas calculer ou inventer l'épaisseur de tranche sans données fiables pour le papier, le nombre de pages et la reliure.
5. Ajouter un contrôle final : format réel du PDF, police/lisibilité, incorporation de polices, dimensions de pages, images, fond perdu selon le contenu, pages blanches, ordre recto verso, fichier de couverture et cohérence avec le gabarit officiel. Les vérifications non automatisées doivent être signalées.
6. Proposer la redirection manuelle Bookelis vers `https://livres.bookelis.com/imprimer`, sans API, transmission automatique, devis garanti ni partenariat officiel.
7. Tester sur des manuscrits réels (roman essentiellement textuel, ouvrage illustré, petit livre de moins de 40 pages, dos carré collé épais) avec sortie PDF comparée aux exigences du prestataire.

### Tests de non-régression obligatoires

- Profil Corep : refus de tout format non A5, sans régression dans le devis et la couverture.
- Profil Bookelis : validation du format sélectionné et refus d'une dimension inventée ; fond perdu intérieur conditionnel ; couverture en mode `full_wrap`.
- Changement de profil : les paramètres non compatibles sont réinitialisés, mais manuscrit et données auteur préservés.
- Une ancienne configuration enregistrée n'impose jamais de mauvais format.
- Pas d'acceptation automatique du PDF final : les contrôles et le BAT restent explicites.

## Points restant à confirmer auprès de Bookelis

- Fichiers de couverture : leur guide indique une couverture complète et le gabarit officiel ; confirmer les contraintes exactes de CMJN, PDF/X et traits de coupe **pour chaque mode** (souple/cartonné, impression seule/distribution).
- Validation des marges, taille minimale de police, nombre maximal de pages et combinaisons de finition.
- Possibilité d'utiliser une couverture composite créée hors de Bookelis à partir de leur gabarit, et compatibilité avec leur portail pour l'impression seule.
- Aucune API ni raccord technique annoncés : **pas d'intégration automatisée** tant qu'un accord n'existe pas.

## Sources

- Calculateur : https://livres.bookelis.com/imprimer
- Options : https://www.bookelis.com/imprimer-un-livre/options-dimpression/
- Gabarits Bookelis : https://livres.bookelis.com/generation-couverture
- Couverture complète à plat : https://www.bookelis.com/conseil/129-comment-creer-une-couverture-de-livre-sur-mesure/
- Fond perdu intérieur : https://www.bookelis.com/conseil/comment-preparer-fichier-impression-fonds-perdus/
- Fonds perdus différenciés : https://www.bookelis.com/conseil/157-quest-ce-que-les-fonds-perdus/
- FAQ technique : https://livres.bookelis.com/faq?category_id=1
