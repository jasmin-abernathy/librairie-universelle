# Autoédition — workflow du MVP

## Positionnement

L’objectif n’est pas d’ouvrir un dépôt automatique de masse. Le projet veut accueillir des livres indépendants tout en conservant un storefront lisible et une exigence minimale de qualité éditoriale et technique.

Aucune IA ne valide, refuse ou classe les soumissions.

## Parcours auteur

1. Renseigner auteur/autrice, titre, langue et présentation.
2. Renseigner les ISBN déjà obtenus, si disponibles.
3. Déposer un EPUB et/ou un PDF prêt à imprimer ; une couverture peut être ajoutée.
4. Déclarer le mode de diffusion numérique envisagé : gratuit ou payant.
5. Confirmer disposer des droits nécessaires et soumettre un véritable projet éditorial.
6. Attendre la revue humaine.

Statuts internes :

```text
pending
  -> needs_changes
  -> approved
  -> published

pending/needs_changes/approved
  -> rejected
```

Une publication dans le catalogue n’est possible qu’après `approved`.

## ISBN

Le formulaire renvoie vers la demande officielle AFNIL destinée aux particuliers autoédités :

`https://www.afnil.org/formulaire/demande_isbn_particulier/`

Le MVP accepte un dépôt initial sans ISBN afin de ne pas bloquer la préparation du dossier, mais la publication commerciale doit utiliser les identifiants adaptés aux manifestations réellement diffusées. L’EPUB et la version papier ne doivent pas réutiliser arbitrairement le même ISBN.

L’auteur ou l’autrice reste identifié comme autoéditeur/autrice du projet tant qu’aucun autre contrat d’édition n’a été conclu. La plateforme ne s’attribue pas automatiquement le rôle d’éditeur et ne distribue pas son propre préfixe ISBN aux soumissions.

## Impression papier

Le formulaire propose comme ressources externes, sans partenariat implicite :

- Bookelis — `https://www.bookelis.com/imprimer-un-livre/`
- CoolLibri — `https://www.coollibri.com/auto-edition-livre`
- BoD / Books on Demand — `https://www.bod.fr/livre/publier-un-livre`

Leur présence dans le formulaire ne constitue ni une recommandation exclusive ni un classement sponsorisé. Le projet pourra ajouter ou retirer des ressources selon leurs conditions réelles.

## Contrôles automatiques autorisés

Ils sont déterministes et portent uniquement sur des propriétés objectives :

- taille des fichiers ;
- extension ;
- type MIME ;
- signature PDF ;
- type réel d’une image ;
- structure minimale EPUB lorsque `ZipArchive` est disponible ;
- checksum SHA-256 ;
- format de l’adresse e-mail ;
- validité mathématique ISBN-10 / ISBN-13 ;
- séparation des ISBN ebook/papier lorsqu’ils sont tous deux fournis.

Ces contrôles ne constituent pas une décision éditoriale.

## Validation humaine

Depuis `/admin/submissions.php`, la personne chargée de la revue peut :

- ouvrir/télécharger les fichiers soumis depuis une route protégée ;
- écrire une note éditoriale ;
- demander des corrections ;
- valider ;
- refuser ;
- publier dans le catalogue après validation.

La revue doit porter au minimum sur la cohérence du projet, les métadonnées, la qualité technique exploitable, les déclarations de droits et l’absence de comportement manifeste de spam éditorial.

## EPUB gratuit

Lorsqu’un EPUB est :

- déclaré gratuit par l’auteur ;
- validé ;
- publié ;

une offre `free_download` est créée. Le téléchargement passe par une route contrôlée qui ne sert que les EPUB gratuits de soumissions publiées.

## EPUB payant

Le MVP **ne simule pas une vente**. Après validation, l’édition payante peut être présente dans le catalogue, mais aucun bouton de paiement ou téléchargement payant n’est créé tant qu’un vrai flux de commande, paiement, fiscalité et livraison n’est pas intégré.

## Sécurité des manuscrits

- stockage hors du document root ;
- noms de stockage aléatoires ;
- fichiers ignorés par Git ;
- accès admin protégé ;
- `X-Content-Type-Options: nosniff` lors du téléchargement ;
- pas d’exécution directe depuis le stockage ;
- CSRF sur les écritures web.

Avant l’alpha publique, définir `STORAGE_PATH` vers un dossier privé, un `ADMIN_TOKEN` long et vérifier les sauvegardes du stockage + de la base.

## Ce qui viendra plus tard

- notifications e-mail auteur ;
- historique de révisions/fichiers ;
- contrat et conditions de distribution numérique ;
- paiement d’un ebook indépendant ;
- reversement auteur ;
- génération de fichiers imprimeur / contrôle prépresse plus approfondi ;
- connexion éventuelle à un imprimeur ou POD par API ;
- dépôt légal assisté ;
- tableau de bord auteur et ventes.

Ces fonctions ne doivent pas être présentées comme disponibles avant leur implémentation réelle.
