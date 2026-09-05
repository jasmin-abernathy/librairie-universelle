# Feuille de route

## Phase 0 — socle ✅

- [x] dépôt privé et conventions de base
- [x] PHP sans framework
- [x] SQLite prototype
- [x] recherche déterministe
- [x] modèle œuvre / édition / source / offre
- [x] principes sans IA et sans profilage
- [x] CI minimale

## Phase 1 — MVP démontrable ✅ en grande partie

- [x] landing qui explique la promesse sans survendre
- [x] corpus réel de 21 œuvres
- [x] cas domaine public / protégé / à vérifier
- [x] fiche œuvre
- [x] plusieurs éditions réelles pour une même œuvre
- [x] traducteurs et contributeurs d’édition distincts des auteurs
- [x] ISBN/EAN et provenance visibles
- [x] première source bibliographique automatisée : BnF SRU
- [x] import idempotent ciblé
- [x] premières offres commerciales réelles et datées
- [x] prix et DRM modélisés au niveau de l’offre vendeur
- [ ] audit clavier / responsive / contrastes
- [ ] canal simple de retour alpha
- [ ] alpha privée

## Phase 2 — sources et libraires

- [ ] stabiliser les imports BnF sur davantage d’œuvres
- [ ] déterminer les conditions actuelles d’intégration de Place des Libraires / leslibraires.fr
- [ ] déterminer les conditions d’intégration ePagine pour l’ebook
- [ ] obtenir, si nécessaire, identifiant affilié / contrat / accès partenaire
- [ ] créer une interface de connecteur d’offres séparée des sources bibliographiques
- [ ] afficher une librairie bénéficiaire ou choisie quand le partenaire le permet
- [ ] ajouter plusieurs vendeurs sans favoriser artificiellement celui qui rémunère le plus
- [ ] politique explicite de fraîcheur des prix et disponibilités

## Phase 3 — disponibilité locale

- [ ] données fiables de librairies et stocks
- [ ] recherche autour d’un lieu uniquement sur demande de l’utilisateur
- [ ] retrait en librairie
- [ ] distance calculée explicitement, sans profilage
- [ ] occasion lorsque les sources le permettent
- [ ] bibliothèques et prêt local lorsque la donnée existe

## Phase 4 — numérique

- [ ] plusieurs distributeurs / libraires numériques
- [ ] compatibilité liseuse déclarative
- [ ] DRM et restrictions compréhensibles
- [ ] accessibilité des ebooks
- [ ] audio
- [ ] domaine public : meilleurs fichiers légitimement réutilisables
- [ ] éventuel lecteur web non captif

## Phase 5 — transaction

À ne commencer qu’après validation du parcours œuvre → édition → offre.

- [ ] panier mono-source propre
- [ ] puis panier multi-source si les contrats et flux le permettent
- [ ] paiement et répartition
- [ ] gestion des commandes et retours
- [ ] carte cadeau réseau

## Phase 6 — comptes facultatifs

- [ ] compte lecteur optionnel
- [ ] librairie préférée
- [ ] bibliothèque personnelle exportable
- [ ] listes de lecture
- [ ] préférences explicites de formats / liseuse
- [ ] aucune personnalisation comportementale implicite

## Phase 7 — éditorial et communauté

- [ ] sélections de libraires
- [ ] listes thématiques humaines
- [ ] recommandations explicables : même auteur, thème choisi, sélection humaine, etc.
- [ ] pages libraires
- [ ] événements

Toujours sans moteur de recommandation par IA ni profilage caché.

## Infrastructure

### Prototype

SQLite + PHP suffit.

### Première production o2switch

- une Lune Cloud dédiée ;
- PHP ;
- MariaDB lorsque les imports/écritures le justifient ;
- cron à faible fréquence pour les sources bibliographiques ;
- secrets hors dépôt ;
- cache si nécessaire ;
- aucun worker permanent requis au stade du MVP.

## Règle de progression

Une nouvelle source n’est pas considérée comme « intégrée » tant que sont documentés :

1. ses conditions de réutilisation ;
2. sa provenance ;
3. sa fraîcheur ;
4. le comportement quand elle ne répond pas ;
5. ce qu’elle permet réellement d’affirmer à l’utilisateur.
