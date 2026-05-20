# BestCopro Website And Application

Depot de passation pour le site WordPress BestCopro et l'application metier PHP situee dans `app/`.

## Architecture

```text
WordPress public site: racine du projet
Application metier:   app/
Production VPS:       /var/www/bestcopro
Staging VPS:          /var/www/bestcopro-staging
```

Bases de donnees connues:

```text
Production WordPress: bestcopr_wp627
Production app:       bestcopr_app
```

Les fichiers de configuration contenant les mots de passe ne sont pas versionnes:

```text
wp-config.php
app/config/db.php
```

Ils doivent rester sur le VPS et etre geres manuellement.

## Workflow De Travail

Developpement normal:

```bash
git checkout staging
git pull origin staging
# modifier le code
git status
git add .
git commit -m "Description courte"
git push origin staging
```

La branche `staging` deploie vers l'environnement de test.

Quand les changements sont valides:

```bash
git checkout main
git pull origin main
git merge staging
git push origin main
```

La branche `main` deploie vers la production.

## Regles Importantes

- Ne jamais committer `wp-config.php`.
- Ne jamais committer `app/config/db.php`.
- Ne jamais committer les exports SQL ou les logs.
- Ne pas versionner `wp-content/uploads/`.
- Ne pas versionner `wp-admin/` et `wp-includes/`; WordPress core reste gere sur le serveur.
- Ne pas ecraser la base de donnees de production avec la base staging.
- Les changements de schema DB doivent etre faits avec des migrations SQL ciblees.

## Documentation

Documents importants:

```text
CHANGELOG.md
docs/DECISIONS.md
GUIDE_PASSATION_GITHUB_DEPLOIEMENT.md
```

Chaque modification importante doit etre ajoutee au `CHANGELOG.md`.

## Deploiement

GitHub Actions deploie les fichiers vers le VPS avec `rsync`.

Secrets requis dans GitHub Actions:

```text
VPS_HOST
VPS_PORT
VPS_USER
VPS_SSH_KEY
```

Recommandation: utiliser un utilisateur `deploy` sur le VPS, pas `root`, pour le long terme.
