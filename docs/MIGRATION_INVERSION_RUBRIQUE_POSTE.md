# Migration SQL - Inversion Rubrique / Poste

Date de reference: 2026-06-16

Objectif:

Inverser les donnees entre `rubrique` et `poste` dans la base applicative `bestcopr_app`, sans renommer les tables et sans casser les depenses.

Structure attendue par l'application:

```text
rubrique
  -> poste.id_rubrique
      -> depense.id_poste
```

Apres inversion, l'application garde la meme structure SQL:

```text
nouvelle rubrique = ancien poste
nouveau poste     = ancienne rubrique
depense.id_poste  = nouveau poste
```

## Point Important

La production est la source de verite pour les donnees.

Pour une migration serveur future, il faut exporter un dump frais de la base de production apres les modifications SQL:

```bash
mysqldump -u bestcopro_user -p bestcopr_app > bestcopr_app_prod_latest.sql
```

Ne pas remplacer la base production par une base staging.

## Backup Obligatoire

Avant toute modification:

```bash
mysqldump -u bestcopro_user -p bestcopr_app > backup_bestcopr_app_avant_inversion.sql
```

Apres validation:

```bash
mysqldump -u bestcopro_user -p bestcopr_app > backup_bestcopr_app_apres_inversion.sql
```

## Verifications Executees

Recherche des contraintes etrangeres declarees:

```sql
SELECT
  TABLE_NAME,
  COLUMN_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IN ('poste', 'rubrique');
```

Resultat observe:

```text
Empty set
```

Conclusion: aucune contrainte etrangere declaree dans MariaDB, mais les relations existent dans le code applicatif.

Structure des tables:

```sql
DESCRIBE rubrique;
DESCRIBE poste;
DESCRIBE depense;
```

Types observes:

```text
rubrique.id              int(11) auto_increment
rubrique.libelle         varchar(300)
rubrique.id_exercice     int(11)
rubrique.id_typeRubrique int(11)

poste.id                 int(11) auto_increment
poste.libelle            varchar(300)
poste.montant            decimal(10,2)
poste.id_rubrique        int(11)

depense.id               int(11) auto_increment
depense.id_poste         int(11)
depense.montant          decimal(10,2)
depense.montantPaye      decimal(12,2)
```

Volumes observes avant nettoyage:

```sql
SELECT COUNT(*) AS nb_rubriques FROM rubrique;
SELECT COUNT(*) AS nb_postes FROM poste;
SELECT COUNT(*) AS nb_depenses FROM depense;
```

Resultats observes:

```text
nb_rubriques = 3536
nb_postes    = 2293
nb_depenses  = 731
```

Verification des postes sans rubrique valide:

```sql
SELECT p.*
FROM poste p
LEFT JOIN rubrique r ON r.id = p.id_rubrique
WHERE r.id IS NULL;
```

Resultat observe:

```text
24 postes orphelins
```

Verification des depenses sans poste valide:

```sql
SELECT d.*
FROM depense d
LEFT JOIN poste p ON p.id = d.id_poste
WHERE p.id IS NULL;
```

Resultat observe:

```text
Empty set
```

Verification des depenses liees aux postes orphelins:

```sql
SELECT 
  p.id,
  p.libelle,
  p.montant,
  p.id_rubrique,
  COUNT(d.id) AS nb_depenses
FROM poste p
LEFT JOIN rubrique r ON r.id = p.id_rubrique
LEFT JOIN depense d ON d.id_poste = p.id
WHERE r.id IS NULL
GROUP BY p.id, p.libelle, p.montant, p.id_rubrique
ORDER BY p.id;
```

Resultat observe:

```text
24 postes orphelins, tous avec nb_depenses = 0
```

Verification des rubriques sans poste:

```sql
SELECT COUNT(*) AS rubriques_sans_poste
FROM rubrique r
LEFT JOIN poste p ON p.id_rubrique = r.id
WHERE p.id IS NULL;
```

Resultat observe:

```text
rubriques_sans_poste = 2325
```

Decision: ne pas supprimer ces rubriques, car elles sont nombreuses et reparties sur plusieurs exercices.

Repartition observee:

```sql
SELECT 
  r.id_exercice,
  r.id_typeRubrique,
  COUNT(*) AS rubriques_sans_poste
FROM rubrique r
LEFT JOIN poste p ON p.id_rubrique = r.id
WHERE p.id IS NULL
GROUP BY r.id_exercice, r.id_typeRubrique
ORDER BY r.id_exercice, r.id_typeRubrique;
```

## Nettoyage Des Postes Orphelins

Les 24 postes orphelins n'avaient aucune depense. Ils pouvaient etre supprimes avant migration:

```sql
START TRANSACTION;

DELETE p
FROM poste p
LEFT JOIN rubrique r ON r.id = p.id_rubrique
LEFT JOIN depense d ON d.id_poste = p.id
WHERE r.id IS NULL
  AND d.id IS NULL;

SELECT p.*
FROM poste p
LEFT JOIN rubrique r ON r.id = p.id_rubrique
WHERE r.id IS NULL;

COMMIT;
```

Le `SELECT` doit retourner:

```text
Empty set
```

## Creation De La Table De Mapping

```sql
DROP TABLE IF EXISTS migration_rubrique_poste_map;

CREATE TABLE migration_rubrique_poste_map (
  old_rubrique_id INT(11) NOT NULL,
  old_poste_id INT(11) NOT NULL,
  old_rubrique_libelle VARCHAR(300) NOT NULL,
  old_poste_libelle VARCHAR(300) NOT NULL,
  old_montant DECIMAL(10,2) NOT NULL,
  id_exercice INT(11) NOT NULL,
  id_typeRubrique INT(11) NOT NULL,
  new_rubrique_id INT(11) NULL,
  new_poste_id INT(11) NULL,
  PRIMARY KEY (old_poste_id)
);

INSERT INTO migration_rubrique_poste_map (
  old_rubrique_id,
  old_poste_id,
  old_rubrique_libelle,
  old_poste_libelle,
  old_montant,
  id_exercice,
  id_typeRubrique
)
SELECT 
  r.id,
  p.id,
  r.libelle,
  p.libelle,
  p.montant,
  r.id_exercice,
  r.id_typeRubrique
FROM rubrique r
JOIN poste p ON p.id_rubrique = r.id;
```

Controle:

```sql
SELECT COUNT(*) AS lignes_a_migrer
FROM migration_rubrique_poste_map;
```

Resultat observe:

```text
lignes_a_migrer = 2269
```

## Migration D'Inversion

```sql
START TRANSACTION;

INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique)
SELECT 
  CONCAT('__INV_RP_', old_poste_id, '__'),
  id_exercice,
  id_typeRubrique
FROM migration_rubrique_poste_map;

UPDATE migration_rubrique_poste_map m
JOIN rubrique r 
  ON r.libelle = CONCAT('__INV_RP_', m.old_poste_id, '__')
SET m.new_rubrique_id = r.id;

INSERT INTO poste (libelle, montant, id_rubrique)
SELECT
  old_rubrique_libelle,
  old_montant,
  new_rubrique_id
FROM migration_rubrique_poste_map;

UPDATE migration_rubrique_poste_map m
JOIN poste p ON p.id_rubrique = m.new_rubrique_id
SET m.new_poste_id = p.id;

UPDATE rubrique r
JOIN migration_rubrique_poste_map m ON m.new_rubrique_id = r.id
SET r.libelle = m.old_poste_libelle;

UPDATE depense d
JOIN migration_rubrique_poste_map m ON d.id_poste = m.old_poste_id
SET d.id_poste = m.new_poste_id;
```

Controle avant suppression:

```sql
SELECT *
FROM migration_rubrique_poste_map
WHERE new_rubrique_id IS NULL OR new_poste_id IS NULL;
```

Si le resultat n'est pas vide:

```sql
ROLLBACK;
```

Si le resultat est `Empty set`, continuer:

```sql
DELETE p
FROM poste p
JOIN migration_rubrique_poste_map m ON m.old_poste_id = p.id;

DELETE r
FROM rubrique r
JOIN (
  SELECT DISTINCT old_rubrique_id
  FROM migration_rubrique_poste_map
) m ON m.old_rubrique_id = r.id
LEFT JOIN poste p ON p.id_rubrique = r.id
WHERE p.id IS NULL;

COMMIT;
```

## Controles Apres Migration

Depenses sans poste:

```sql
SELECT 
  COUNT(*) AS depenses_sans_poste
FROM depense d
LEFT JOIN poste p ON p.id = d.id_poste
WHERE p.id IS NULL;
```

Resultat observe:

```text
depenses_sans_poste = 0
```

Postes sans rubrique:

```sql
SELECT COUNT(*) AS postes_sans_rubrique
FROM poste p
LEFT JOIN rubrique r ON r.id = p.id_rubrique
WHERE r.id IS NULL;
```

Apercu des donnees finales:

```sql
SELECT 
  r.id AS rubrique_id,
  r.libelle AS rubrique,
  p.id AS poste_id,
  p.libelle AS poste,
  p.montant,
  COUNT(d.id) AS depenses
FROM rubrique r
JOIN poste p ON p.id_rubrique = r.id
LEFT JOIN depense d ON d.id_poste = p.id
GROUP BY r.id, r.libelle, p.id, p.libelle, p.montant
ORDER BY r.id DESC, p.id DESC
LIMIT 30;
```

Verification des lignes reellement inversees:

```sql
SELECT
  m.old_rubrique_libelle AS ancienne_rubrique,
  m.old_poste_libelle AS ancien_poste,
  r.libelle AS nouvelle_rubrique,
  p.libelle AS nouveau_poste,
  p.montant
FROM migration_rubrique_poste_map m
JOIN rubrique r ON r.id = m.new_rubrique_id
JOIN poste p ON p.id = m.new_poste_id
WHERE m.old_rubrique_libelle <> m.old_poste_libelle
LIMIT 30;
```

Resultat attendu:

```text
nouvelle_rubrique = ancien_poste
nouveau_poste     = ancienne_rubrique
```

## Table De Mapping

Garder temporairement la table:

```text
migration_rubrique_poste_map
```

Elle sert d'historique et permet de verifier l'inversion.

Une fois l'application validee:

```sql
DROP TABLE migration_rubrique_poste_map;
```

## Staging Et Production

Regle retenue:

```text
Production = source de verite des donnees
Staging    = copie de test, jamais source pour remplacer la production
Git        = source de verite du code, pas des donnees SQL
```

Pour continuer a developper en staging apres la modification de production:

1. exporter un dump frais de production;
2. importer ce dump dans une base staging;
3. pointer `app/config/db.php` de staging vers la base staging;
4. tester les changements de code sur staging;
5. deployer seulement le code valide vers production.

Ne jamais faire:

```text
staging DB -> production DB
```

sauf procedure exceptionnelle et controlee.
