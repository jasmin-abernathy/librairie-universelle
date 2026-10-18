# PDF/X professionnel : bêta sécurisée

La route `/export-pdfx.php` propose deux conversions **après** avoir enregistré le PDF de mise en page dans le navigateur : PDF/X-4 et PDF/X-1a:2001. L'original est conservé par l'auteur. Chaque PDF (intérieur et deux faces) se convertit séparément si nécessaire, selon les instructions de l'imprimeur. La création du ZIP reste entièrement locale ; la conversion PDF/X exige temporairement un envoi au serveur.

## Activation opérateur
La fonction est **fermée par défaut**. Sur un serveur qui peut isoler les processus Ghostscript traitant des fichiers non fiables, fournir dans la configuration privée :
```ini
PDFX_ENABLED=true
PDFX_GS_BIN=/chemin/absolu/gs
PDFX_TIMEOUT_BIN=/chemin/absolu/timeout
PDFX_ICC_PROFILE=/chemin/absolu/CMYK-imprimeur-approuve.icc
PDFX_OUTPUT_CONDITION="Condition d'impression à confirmer avec l'imprimeur"
```
Le profil ICC doit correspondre au procédé d'impression. Aucun profil commercial n'est livré ni inventé ; le profil de tests générique n'est pas validé par Corep. Contrôler les droits de lecture, les limites PHP web et les permissions du stockage privé.

## Technique
L'exécutable Ghostscript est lancé sans shell, avec `-dSAFER`, un accès ciblé au profil ICC, une limite de durée de 75 s, une entrée PDF bornée (25 Mio) et un répertoire aléatoire privé temporaire (supprimé après téléchargement). Si `proc_open`, `gs`, `timeout` ou l'ICC ne sont pas conformes, l'atelier ne convertit rien. Ne pas exposer publiquement cette fonction sans isolation système, confinement et maîtrise des quotas CPU/mémoire : Ghostscript traitant un PDF inconnu reste une surface d'attaque.

## Limites techniques et preuve
Le moteur Ghostscript génère une déclaration PDF/X et un OutputIntent. Le contrôle vérifie quelques marqueurs PDF : ce n'est **pas** un validateur ISO PDF/X indépendant. Il faut faire valider le résultat par un outil de prépresse reconnu par l'imprimeur, ainsi que les polices, le fond perdu, les boîtes de page, la résolution, la couleur et le BAT. veraPDF est principalement un validateur PDF/A et PDF/UA, pas un validateur PDF/X.

L'automatisation directe DOCX/ODT → PDF/X sans passer par l'impression PDF du navigateur n'est **pas encore terminée**. Elle requiert un moteur de rendu automatisé et un environnement isolé ; ne pas l'annoncer comme existante.
