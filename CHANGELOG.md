# Changelog

## 2026-06-16

### Added

- Documentation de la procedure SQL d'inversion des donnees `rubrique` / `poste` et des controles associes.

## 2026-06-03

### Changed

- Ajout d'un choix de periodicite (mensuel, trimestriel, semestriel, annuel) pour les exports de cotisations PDF et Excel depuis la page des lots.

## 2026-05-20

### Added

- Initialisation du depot GitHub pour la passation BestCopro.
- Ajout du `.gitignore` pour exclure secrets, bases SQL, logs, archives, uploads et caches.
- Ajout de la documentation de passation et des workflows de deploiement staging/production.

### Notes

- Le code devient versionne dans GitHub.
- Les donnees de production restent dans MySQL sur le VPS.
- Les fichiers `wp-config.php` et `app/config/db.php` restent uniquement sur le serveur.
