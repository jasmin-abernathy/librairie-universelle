# Feuille de route

## Phase 0 — socle ✅

- [x] dépôt privé et conventions de base
- [x] PHP sans framework
- [x] SQLite prototype
- [x] recherche déterministe
- [x] modèle œuvre / édition / source / offre
- [x] principes sans IA et sans profilage
- [x] CI minimale

## Phase 1 — MVP alpha ✅ côté code

- [x] landing qui explique la promesse sans survendre
- [x] corpus réel de 21 œuvres
- [x] cas domaine public / protégé / à vérifier
- [x] fiche œuvre
- [x] plusieurs éditions réelles pour une même œuvre
- [x] traducteurs et contributeurs d’édition distincts des auteurs
- [x] ISBN/EAN et provenance visibles
- [x] BnF SRU : première source bibliographique automatisée
- [x] import idempotent ciblé
- [x] premières offres commerciales réelles et datées
- [x] prix et DRM modélisés au niveau de l’offre vendeur
- [x] storefront ebook gratuit + payant
- [x] politique de fraîcheur des offres
- [x] Gallica OPDS préparé pour des EPUB gratuits du domaine public
- [x] canal simple de retour alpha, fermé par défaut jusqu’au déploiement
- [x] autoédition : dépôt EPUB / PDF / couverture
- [x] validation ISBN-10 / ISBN-13
- [x] lien AFNIL et ressources d’impression/POD
- [x] stockage privé des manuscrits hors webroot
- [x] validation éditoriale humaine : corrections / validation / refus / publication
- [x] back-office alpha protégé
- [x] journalisation des synchronisations et retries limités
- [x] corrections clavier / focus / responsive / réduction des animations au niveau du code
- [x] préflight et configuration o2switch prudente
- [ ] test réel mobile / clavier / lecteur d’écran sur le déploiement
- [ ] alpha privée avec 5 à 10 personnes

## Phase 2 — sources et libraires

- [ ] stabiliser les imports BnF et Gallica sur davantage d’œuvres après test serveur réel
- [ ] déterminer les conditions actuelles d’intégration de Place des Libraires / leslibraires.fr
- [ ] déterminer les conditions d’intégration ePagine pour l’ebook
- [ ] obtenir, si nécessaire, identifiant affilié / contrat / accès partenaire
- [x] interface de connecteur d’offres séparée des sources bibliographiques (`OfferSource`)
- [x] importeur d’offres par ISBN, idempotent (`OfferImporter`)
- [ ] écrire le premier adaptateur commercial réel après obtention d’un accès autorisé
- [ ] afficher une librairie bénéficiaire ou choisie quand le partenaire le permet
- [ ] ajouter plusieurs vendeurs sans favoriser artificiellement celui qui rémunère le plus

## Phase 3 — autoédition commerciale

Le dépôt et la validation humaine sont déjà codés. Il reste les briques qui impliquent réellement une transaction ou une relation contractuelle.

- [ ] conditions de distribution à accepter par l’auteur/autrice
- [ ] notifications et demandes de corrections par e-mail
- [ ] historique des versions de fichiers
- [ ] paiement d’un ebook autoédité
- [ ] livraison sécurisée après paiement
- [ ] calcul et reversement des revenus auteur
- [ ] facture / suivi de commande
- [ ] éventuelle connexion imprimeur / POD
- [ ] aide au dépôt légal
- [ ] tableau de bord auteur / ventes

Le MVP ne doit jamais simuler ces fonctions tant qu’elles ne sont pas réellement disponibles.

## Phase 4 — disponibilité locale

- [ ] données fiables de librairies et stocks
- [ ] recherche autour d’un lieu uniquement sur demande de l’utilisateur
- [ ] retrait en librairie
- [ ] distance calculée explicitement, sans profilage
- [ ] occasion lorsque les sources le permettent
- [ ] bibliothèques et prêt local lorsque la donnée existe

## Phase 5 — numérique étendu

- [ ] plusieurs distributeurs / libraires numériques
- [ ] compatibilité liseuse déclarative
- [x] DRM et restrictions affichables au niveau de l’offre
- [ ] accessibilité des ebooks : métadonnées et filtres plus complets
- [ ] audio
- [x] domaine public : source Gallica OPDS préparée et Wikisource déjà utilisé
- [ ] Standard Ebooks ou autre source qualitative après vérification juridique France œuvre par œuvre
- [ ] éventuel lecteur web non captif

## Phase 6 — transaction générale

À ne commencer qu’après validation du parcours œuvre → édition → offre.

- [ ] panier mono-source propre
- [ ] puis panier multi-source si les contrats et flux le permettent
- [ ] paiement et répartition
- [ ] gestion des commandes et retours
- [ ] carte cadeau réseau

## Phase 7 — comptes facultatifs

- [ ] compte lecteur optionnel
- [ ] librairie préférée
- [ ] bibliothèque personnelle exportable
- [ ] listes de lecture
- [ ] préférences explicites de formats / liseuse
- [ ] aucune personnalisation comportementale implicite

## Phase 8 — éditorial et communauté

- [ ] sélections de libraires
- [ ] listes thématiques humaines
- [ ] recommandations explicables : même auteur, thème choisi, sélection humaine, etc.
- [ ] pages libraires
- [ ] événements

Toujours sans moteur de recommandation par IA ni profilage caché.

## Infrastructure

### Prototype / alpha

SQLite + PHP suffit.

### Première production o2switch

- [x] architecture cible documentée pour une Lune Cloud dédiée
- [x] document root `public/`
- [x] stockage privé hors webroot
- [x] headers de sécurité Apache prudents
- [x] préflight PHP
- [x] cron BnF préparé
- [x] cron Gallica préparé
- [ ] créer réellement la Lune / domaine / dépôt serveur
- [ ] exécuter le préflight sur o2switch
- [ ] installer les crons réels
- [ ] vérifier sauvegardes SQLite + manuscrits
- [ ] ouvrir `SELF_PUBLISHING_ENABLED` et `FEEDBACK_ENABLED` seulement après ces tests

MariaDB devient utile lorsque les imports/écritures concurrentes le justifient. Aucun worker permanent n’est requis au stade de l’alpha.

## Règle de progression

Une nouvelle source n’est pas considérée comme « intégrée » tant que sont documentés :

1. ses conditions de réutilisation ;
2. sa provenance ;
3. sa fraîcheur ;
4. le comportement quand elle ne répond pas ;
5. ce qu’elle permet réellement d’affirmer à l’utilisateur.
