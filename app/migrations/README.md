# Migration des exports du 4 septembre 2026

Cette migration remplace les modifications de schéma qui étaient exécutées pendant la consultation et les exports. Elle complète aussi les affectations historiques manquantes dans `rel_rel_paiement`, sans modifier le montant des paiements ni les cotisations enregistrées.

1. Sauvegarder la base de données utilisée par l'environnement ciblé.
2. Lancer la simulation depuis la racine de l'application :

   ```bash
   php app/migrations/20260904_export_refactor.php
   ```

3. Vérifier les lignes `Affectations à créer`, `Montant à réconcilier` et surtout `Montant non réconciliable`.
4. Appliquer seulement après validation de la simulation :

   ```bash
   php app/migrations/20260904_export_refactor.php --apply
   ```

La commande est idempotente : une deuxième simulation après application doit annoncer zéro affectation à créer. Elle ne lit aucun fichier de dump SQL et n'utilise notamment pas `localhost.sql`.
