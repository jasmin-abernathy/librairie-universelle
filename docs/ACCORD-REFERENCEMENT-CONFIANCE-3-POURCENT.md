# Accord de référencement sur la confiance — 3 %

Statut : modèle de travail (non activé dans le formulaire public ; à valider avant signature).

## Principe

- Un seul accord **par vendeur**, couvrant l'ensemble des livres dont il demande le référencement sur Librairie Universelle.
- Chaque fiche conserve son prix public TTC déclaré, son identité de vendeur et ses coordonnées professionnelles publiques lorsque nécessaires.
- La Librairie transmet les demandes de lecteurs via un formulaire unique. Le destinataire du formulaire et l'acheteur savent, **avant l'envoi**, que Le Potager du Web reçoit également une copie. Aucune CCI dissimulée.
- Le paiement et la livraison restent entièrement gérés par le vendeur.
- Le vendeur déclare spontanément **une fois par trimestre seulement s'il a réalisé des ventes** le total TTC des livres effectivement vendus grâce à ces mises en relation. Pas de liste de commandes, pas d'identité d'acheteur, pas d'accès aux comptes, pas de vérification automatisée des paiements.
- Commission : **3 % du montant TTC réellement encaissé pour ces livres**, hors frais de livraison, commandes annulées ou remboursées. Aucune commission sur les simples contacts, ventes sans rapport avec le site ou paiements non reçus. Facturation de la prestation par Le Potager du Web suivant le régime de TVA applicable.
- Aucun surcoût de commission pour l'acheteur. Le vendeur fixe un prix public par édition et respecte les lois sur le prix des livres neufs et numériques. Les frais de livraison restent présentés séparément et soumis aux règles applicables.
- Référencement non exclusif, résiliable par e-mail sans pénalité ; les commissions déjà dues sur les ventes conclues subsistent.

## Convention simplifiée — modèle destiné à Odoo Sign

**Entre les soussignés** : (A) l'entreprise individuelle de Jasmin Lévêque, exploitant la marque Le Potager du Web, informations professionnelles à compléter, et (B) le vendeur/éditeur, nom, identité/qualité et coordonnées à compléter.

**1. Objet** — Le Potager du Web publie sur Librairie Universelle les fiches de livres acceptées et facilite la mise en relation avec des lecteurs. Il ne vend pas, n'encaisse pas et ne livre pas les livres. Le vendeur conserve la responsabilité de ses prix, offres, livraisons, droits d'auteur, réclamations et obligations légales.

**2. Prix** — Le vendeur déclare le prix public TTC applicable à chaque édition, le tient à jour et respecte la réglementation du prix unique du livre concerné. Il ne majore pas le prix du livre pour récupérer la commission. Les frais d'expédition sont distingués et respectent la réglementation applicable.

**3. Commission** — Pour chaque vente réellement conclue et encaissée auprès d'un lecteur présenté par la Librairie, le vendeur verse à l'entreprise une commission égale à 3 % du montant TTC effectivement encaissé pour les livres concernés, hors livraison et après déduction des annulations et remboursements. Rien n'est dû pour un simple contact, une vente non payée ou une vente étrangère à la mise en relation. Le Potager du Web facture la commission au vendeur dans le régime de TVA qui lui est applicable.

**4. Déclaration de bonne foi** — Si des ventes sont réalisées grâce aux mises en relation, le vendeur communique une fois par trimestre leur montant TTC total, hors livraison, annulations et remboursements, par simple e-mail. L'entreprise établit la facture correspondante. En l'absence de ventes concernées, aucune déclaration ni commission n'est requise. Aucune preuve systématique, liste de clients ou accès aux paiements n'est demandé.

**5. Confidentialité et fin de l'accord** — Le lecteur est informé avant l'envoi de sa demande que le site en reçoit une copie pour transmettre et suivre la mise en relation ; les données ne servent pas au démarchage. Chaque partie peut mettre fin au référencement à tout moment par e-mail, sans frais, en conservant les obligations nées des ventes déjà conclues. Les désaccords sont d'abord traités à l'amiable.

Le vendeur confirme disposer des droits nécessaires à la commercialisation. L'accord prend effet à la date de sa signature par les deux parties.

**Date et signature du Potager du Web** : [champ Odoo Sign]

**Date et signature du vendeur** : [champ Odoo Sign]

## Mise en œuvre prévue — sans sur-ingénierie

1. L'auteur ou le vendeur soumet sa fiche comme actuellement. Aucune vente ni commission tant qu'il n'y a pas d'accord signé.
2. Pour un nouveau vendeur, l'administrateur envoie un **unique modèle PDF réutilisable** via Odoo Sign.
3. Après signature des deux parties, le référencement peut être validé et le formulaire de contact activé, avec mention de la copie destinée au Potager.
4. Si une vente est conclue grâce au site, l'auteur déclare **un total trimestriel par e-mail** (aucune saisie par transaction). Une facture de 3 % est ensuite émise.

**Odoo Sign** : l'instance Odoo utilisée jusqu'ici est sur un abonnement gratuit à une seule application ; son API externe n'est pas accessible, contrairement à l'offre Custom. Ne pas déclencher automatiquement de demandes de signature tant qu'un accès API compatible ou une autre voie autorisée n'est pas configuré. Le modèle PDF à charger manuellement dans Odoo Sign constitue la solution retenue, sans surcoût.

**Protection des données** : prévoir sur le formulaire de contact des informations concises sur les finalités, les destinataires, la durée de conservation et l'exercice des droits. Le suivi des contacts n'est pas nécessaire pour enregistrer une liste d'acheteurs ou le montant des achats.

**À ne pas faire** : ne pas activer de commission rétroactivement sur des fiches déjà publiées ; ne pas présenter une simple acceptation de conditions comme une signature Odoo ; ne pas bloquer l'ancienne publication avant que le processus de signature puisse fonctionner.

## Références à vérifier pour une version contractuelle définitive

- Prix unique du livre imprimé : https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000049006050
- Prix du livre numérique : https://www.legifrance.gouv.fr/loda/id/JORFTEXT000024079563/
- Odoo Sign : https://www.odoo.com/documentation/19.0/fr/applications/productivity/sign.html
- Odoo API JSON-2 (plan Custom) : https://www.odoo.com/documentation/19.0/fr/developer/reference/external_api.html
- RGPD, information des personnes : https://www.cnil.fr/fr/passer-laction/rgpd-les-premieres-etapes
