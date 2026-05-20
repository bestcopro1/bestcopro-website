# BestCopro Website And Application

Depot de passation pour l'application metier PHP BestCopro situee dans `app/`.

Le site WordPress historique reste present sur le VPS, mais il n'est pas le perimetre principal de ce depot. Le depot est volontairement limite a l'application metier, a la documentation et aux workflows de deploiement.

## Architecture

```text
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
- Ne pas versionner WordPress dans ce depot; WordPress reste gere separement sur le VPS.
- Versionner seulement le code de l'application metier `app/` et la documentation.
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
