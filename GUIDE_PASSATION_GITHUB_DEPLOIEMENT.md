# Guide De Passation - GitHub, Staging Et Production BestCopro

Date: 2026-05-20

## Objectif

Mettre en place une base de travail durable pour BestCopro:

- code versionne dans un depot GitHub appartenant a BestCopro;
- workflow staging puis production;
- deploiement par GitHub Actions;
- secrets et bases de donnees hors GitHub;
- documentation maintenue pour les futurs developpeurs.

## Architecture Cible

```text
PC developpeur -> GitHub -> GitHub Actions -> VPS -> nginx/PHP/MySQL
```

GitHub contient le code et l'historique. Le VPS execute le site et garde les donnees.

## Environnements

Production:

```text
Chemin: /var/www/bestcopro
WordPress DB: bestcopr_wp627
Application DB: bestcopr_app
```

Staging:

```text
Chemin: /var/www/bestcopro-staging
WordPress DB: bestcopr_wp627_staging
Application DB: bestcopr_app_staging
```

## Regle Critique

Le code va de local vers GitHub puis VPS.

Les donnees de production restent en production. Ne jamais importer toute la base staging vers production, car cela peut supprimer les nouvelles donnees creees par les utilisateurs.

Si une feature demande un changement DB, creer une migration SQL ciblee:

```sql
ALTER TABLE ...
CREATE TABLE ...
```

## Branches

```text
staging -> deploiement staging
main    -> deploiement production
```

Workflow:

```bash
git checkout staging
git pull origin staging
# modifier
git add .
git commit -m "Description"
git push origin staging
```

Apres validation:

```bash
git checkout main
git pull origin main
git merge staging
git push origin main
```

## Fichiers Exclus De GitHub

```text
wp-config.php
app/config/db.php
*.sql
*.log
error_log
wp-content/uploads/
wp-content/cache/
old/
dev/
demo/
gestionnaires.bestcopro.ma/
archives .zip/.tar.gz/.rar
```

## Secrets GitHub Actions

Configurer dans GitHub:

```text
Settings -> Secrets and variables -> Actions
```

Secrets requis:

```text
VPS_HOST
VPS_PORT
VPS_USER
VPS_SSH_KEY
```

## Sauvegardes Avant Production

Avant un gros deploiement:

```bash
mkdir -p /root/backups
mysqldump -u root -p bestcopr_wp627 > /root/backups/bestcopr_wp627_$(date +%F_%H-%M).sql
mysqldump -u root -p bestcopr_app > /root/backups/bestcopr_app_$(date +%F_%H-%M).sql
tar -czf /root/backups/bestcopro_files_$(date +%F_%H-%M).tar.gz /var/www/bestcopro
```

Ne pas stocker les sauvegardes dans GitHub.

## Rollback

Rollback code:

```bash
git log --oneline
git revert COMMIT_ID
git push origin main
```

Rollback base seulement si necessaire:

```bash
mysql -u root -p bestcopr_app < /root/backups/backup_app.sql
mysql -u root -p bestcopr_wp627 < /root/backups/backup_wp.sql
```

Attention: restaurer une base peut supprimer des donnees creees apres le backup.

## Checklist Avant Depart

- Depot GitHub prive cree dans un compte/organisation BestCopro.
- Deux personnes BestCopro ont acces administrateur.
- Branches `main` et `staging` creees.
- GitHub Actions configure.
- Secrets GitHub Actions ajoutes.
- `README.md`, `CHANGELOG.md` et `docs/DECISIONS.md` presents.
- Fichiers sensibles exclus de GitHub.
- Procedure de sauvegarde connue.
- Procedure de rollback connue.
