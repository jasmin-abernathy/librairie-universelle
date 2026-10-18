# Archive imprimable locale ZIP

La page `/dossier-impression.php` peut composer une archive standard contenant trois fichiers PDF renommés de manière prévisible (`interieur.pdf`, `premiere-couverture.pdf`, `quatrieme-couverture.pdf`) et la fiche `fiche-imprimeur.json`.

Le ZIP est construit **dans le navigateur**, sans réseau ni service tiers. Les fichiers ne sont pas recompressés (PDF déjà compressés), mais chaque entrée est protégée par un CRC32 calculé par blocs de 1 Mio. Taille totale maximale volontaire : 250 Mio ; au-delà, transmettre les fichiers séparément.

La présence d'un PDF dans l'archive ne garantit pas la conformité PDF/X, les fonds perdus, les polices, la définition ou le BAT. L'imprimeur doit toujours vérifier le dossier avant fabrication. Certains prestataires peuvent préférer les fichiers séparés.
