# Decisions Techniques

## 2026-05-20 - GitHub Devient La Source De Verite Du Code

Decision:

Le code sera modifie localement, pousse sur GitHub, puis deploye vers le VPS par GitHub Actions.

Raison:

Le projet n'avait pas d'historique Git fiable. Cette decision permet de tracer les modifications, revenir en arriere et faciliter la passation.

Impact:

- Les developpeurs ne doivent plus modifier le code directement en production sauf urgence.
- Les fichiers de configuration et les uploads restent sur le VPS.
- La base de donnees de production ne doit pas etre remplacee par la base staging.

## 2026-05-20 - Separation Staging Et Production

Decision:

La branche `staging` deploie vers `/var/www/bestcopro-staging`, et la branche `main` deploie vers `/var/www/bestcopro`.

Raison:

Les modifications doivent etre testees en ligne avant d'atteindre les utilisateurs finaux.

Impact:

- Les validations fonctionnelles se font sur staging.
- La production recoit seulement les changements valides.
