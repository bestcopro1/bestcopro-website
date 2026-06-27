# Correction postes/rubriques vers referentiel

Source: `localhost(1).sql` et `poste comptable budge.csv`.

## Synthese

- Combinaisons existantes analysees: 172
- Deja alignees avec le referentiel: 32
- Corrections proposees haute confiance: 6
- A revoir manuellement: 134

## Regle de securite

Ne pas appliquer de correction automatique en production. Appliquer d abord sur staging, verifier les exports et les budgets, puis valider les mappings manuels.

## Corrections proposees haute confiance

| Score | Occurrences | Budget | Poste actuel | Rubrique actuelle | Poste referentiel | Rubrique referentiel |
|---:|---:|---|---|---|---|---|
| 0.97 | 6 | Fonctionnement | Gestion Administrative | Prime exceptionnelle pour le personnel (Nettoyage, gardien …) | GESTION ADMINISTRATIVE | PRIME EXCEPTIONNELLE POUR LE PERSONNEL (NETTY, GARDIEN,….) |
| 0.89 | 29 | Fonctionnement | GARDIENNAGE | SÃ©curitÃ© jour/nuit | GARDIENNAGE | SECURTIE-JOUR/NUIT |
| 0.88 | 78 | Fonctionnement | FRAIS JURIDIQUES | CONTANTIEUX/ HUISSIER DE JUSTICE | JURIDIQUE | CONTANTIEUX/ HUISSIER DE JUSTICE |
| 0.88 | 77 | Fonctionnement | ASSURANCES | Assurance Multirisque & RC | ASSURANCES | ASS MULTIRISQUES ET RC |
| 0.84 | 3 | Fonctionnement | GARDIENNAGE | SECURITE | GARDIENNAGE | SECURTIE-JOUR/NUIT |
| 0.84 | 2 | Fonctionnement | Gardiennage | SECURTIE | GARDIENNAGE | SECURTIE-JOUR |

## A revoir manuellement

| Score | Occurrences | Budget | Poste actuel | Rubrique actuelle | Meilleure proposition |
|---:|---:|---|---|---|---|
| 0.60 | 82 | Fonctionnement | JARDINAGE | ENTRETIEN | JARDINAGE / EMBILLISSEMENT & PLANTATION |
| 0.34 | 78 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | REDAL | ANIMATION ESTIVALE / ANIMATION ESTIVALE |
| 0.73 | 70 | Fonctionnement | Gestion Administrative | Frais de gestion | REMUNERATIONS DU SYNDIC / FRAIS DE GESTION |
| 0.74 | 65 | Fonctionnement | Travaux divers | Recharge des extincteurs | DIVERS ENTRETIENS / RECHARGE DES EXTINCTEURS |
| 0.46 | 65 | Fonctionnement | Travaux divers | Travaux d'Ã©lectricitÃ©s - Fournitures + MOD | DIVERS ENTRETIENS / TRAVAUX DE PLOMBERIE - MAT + MOD |
| 0.74 | 65 | Fonctionnement | Travaux divers | Travaux de plomberie - MAT + MOD | DIVERS ENTRETIENS / TRAVAUX DE PLOMBERIE - MAT + MOD |
| 0.74 | 60 | Fonctionnement | Travaux divers | Travaux de forgeron | DIVERS ENTRETIENS / TRAVAUX DE FORGERON |
| 0.70 | 59 | Fonctionnement | Travaux divers | Travaux de menuiserie | DIVERS ENTRETIENS / TRAVAUX DE MENUISERIES |
| 0.74 | 59 | Fonctionnement | Travaux divers | Travaux de peinture | DIVERS ENTRETIENS / TRAVAUX DE PEINTURE |
| 0.74 | 57 | Fonctionnement | Travaux divers | Travaux Aluminium | DIVERS ENTRETIENS / TRAVAUX ALUMINIUM |
| 0.38 | 54 | Fonctionnement | Travaux divers | Divers dÃ©penses | DIVERS ENTRETIENS / TRAVAUX DE PEINTURE |
| 0.74 | 54 | Fonctionnement | Travaux divers | Travaux de Construction | DIVERS ENTRETIENS / TRAVAUX DE CONSTRUCTION |
| 0.40 | 47 | Fonctionnement | Budget de fonctionnement | Budget de fonctionnement | DIVERS ENTRETIENS / TRAVAUX DE CONSTRUCTION |
| 0.38 | 46 | Fonctionnement | Travaux divers | Réparation portes de garage | DIVERS ENTRETIENS / TRAVAUX DE FORGERON |
| 0.73 | 45 | Fonctionnement | Travaux divers | Organisation AG | JURIDIQUE / ORGANISATION AG |
| 0.71 | 32 | Fonctionnement | JARDINAGE | Plantation | JARDINAGE / EMBILLISSEMENT & PLANTATION |
| 0.74 | 32 | Fonctionnement | NETTOYAGE | MENAGE | NETTOYAGE / PRODUITS DE MENAGE |
| 0.73 | 28 | Fonctionnement | Gestion Administrative | Honoraire de syndic | REMUNERATIONS DU SYNDIC / HONORAIRE DE SYNDIC |
| 0.49 | 28 | Fonctionnement | Travaux divers | Dératisation | HYGIENE-3D / DERATISATION ET 3D |
| 0.75 | 26 | Fonctionnement | MAITRE NAGEUR | Maitre Nageur | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.62 | 25 | Fonctionnement | MAINTENANCE | Travaux divers | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.67 | 22 | Fonctionnement | HYGIÃ‰NE - 3D | 0pÃ©ration 3D | HYGIENE-3D / DERATISATION ET 3D |
| 0.57 | 21 | Fonctionnement | Travaux divers | Pièce de rechange ascenseur | MAINTENANCE / PIECES DE RECHANGE POUR ASCENSEURS |
| 0.49 | 19 | Fonctionnement | Budget de fonctionnement | SECURTIE | GARDIENNAGE / SECURTIE-JOUR |
| 0.77 | 19 | Fonctionnement | JARDINAGE | EMBILLISSEMENT | JARDINAGE / EMBILLISSEMENT & PLANTATION |
| 0.39 | 16 | Fonctionnement | INVESSTISSEMENT | Invesstissement | ANIMATION ESTIVALE / ANIMATION ESTIVALE |
| 0.40 | 15 | Fonctionnement | Piscines | Hiver | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.61 | 14 | Fonctionnement | GARDIENNAGE | SÃ©curitÃ© porte principale et barriÃ¨re | GARDIENNAGE / SECURTIE-JOUR/NUIT |
| 0.66 | 14 | Fonctionnement | GARDIENNAGE | Sécurité porte principale et barriere | GARDIENNAGE / SECURITE-RENFORT |
| 0.61 | 13 | Fonctionnement | GARDIENNAGE | Supplément été | GARDIENNAGE / SECURITE-RENFORT |
| 0.58 | 13 | Fonctionnement | NETTOYAGE | Supplément été | NETTOYAGE / PRODUITS DE MENAGE |
| 0.77 | 13 | Fonctionnement | Piscines | Produits d'entretien | ENTRETIEN PISCINE / PRODUIT D'ENTRETIEN |
| 0.56 | 12 | Fonctionnement | Agent de service | Prestation | AGENT DE SERVICE / SUPERVISEUR |
| 0.82 | 11 | Fonctionnement | Piscines | Impression & bracelets | ENTRETIEN PISCINE / IMPRESSION & BRACELETS |
| 0.33 | 11 | Fonctionnement | Travaux divers | Divers | DIVERS ENTRETIENS / TRAVAUX DE MENUISERIES |
| 0.56 | 10 | Fonctionnement | JARDINAGE | Consommables jardin | JARDINAGE / BALAYEUR |
| 0.47 | 10 | Fonctionnement | PARTICIPATION CONSEIL SYNDICAL | TRAVAUX CONSEIL SYNDICAL | REMUNERATIONS DU SYNDIC / HONORAIRE DE SYNDIC |
| 0.37 | 10 | Fonctionnement | Piscines | Été | ENTRETIEN PISCINE / EQUIPEMENT PISCINE |
| 0.82 | 10 | Fonctionnement | Travaux de federal | Travaux de federal | FEDERAL / TRAVAUX DE FEDERAL |
| 0.73 | 9 | Fonctionnement | MAINTENANCE | Travaux de plomberie | DIVERS ENTRETIENS / TRAVAUX DE PLOMBERIE |
| 0.64 | 9 | Fonctionnement | Piscines | Agents d'entretien | ENTRETIEN PISCINE / PRODUIT D'ENTRETIEN |
| 0.31 | 8 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | SRM | ELECTRICITE / CONSOMMATION ELECTRICITE |
| 0.57 | 7 | Fonctionnement | GESTION | CONTRAT DE GESTION | REMUNERATIONS DU SYNDIC / FRAIS DE GESTION |
| 0.61 | 7 | Fonctionnement | MAINTENANCE | Ã‰lectricitÃ© - Fourniture et MOD | MAINTENANCE / ACHATS MATERIELS & ACCESSOIRES |
| 0.59 | 7 | Fonctionnement | MAINTENANCE | RÃ©paration (Ã©lÃ©ctr/plomb....) | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.72 | 7 | Fonctionnement | NETTOYAGE | Produits | NETTOYAGE / PRODUITS DE MENAGE |
| 0.51 | 7 | Fonctionnement | Piscines | Maintenance piscines | ENTRETIEN PISCINE / EQUIPEMENT PISCINE |
| 0.65 | 6 | Fonctionnement | Budget de fonctionnement | QUOTE PART CS | GARDIENNAGE / QUOTE PART CS |
| 0.59 | 6 | Fonctionnement | JARDINAGE | Agent de service | JARDINAGE / ESSENCE |
| 0.73 | 6 | Fonctionnement | MAINTENANCE | Portes automatiques | DIVERS ENTRETIENS / PORTES AUTOMATIQUES |
| 0.73 | 6 | Fonctionnement | MAINTENANCE | Puit | DIVERS ENTRETIENS / PUIT |
| 0.67 | 6 | Fonctionnement | MAINTENANCE | Travaux d'aluminium | DIVERS ENTRETIENS / TRAVAUX ALUMINIUM |
| 0.82 | 6 | Fonctionnement | Piscines | Equipement piscine | ENTRETIEN PISCINE / EQUIPEMENT PISCINE |
| 0.60 | 5 | Fonctionnement | GARDIENNAGE | SupplÃ©ment Ã©tÃ© | GARDIENNAGE / SECURITE-RENFORT |
| 0.38 | 5 | Fonctionnement | INVESSTISSEMENT | CamÃ©ra | ANIMATION ESTIVALE / ANIMATION ESTIVALE |
| 0.45 | 5 | Fonctionnement | INVESSTISSEMENT | Grillage forgé | DIVERS ENTRETIENS / TRAVAUX DE FORGERON |
| 0.55 | 5 | Fonctionnement | NETTOYAGE | SupplÃ©ment Ã©tÃ© | NETTOYAGE / RENFORT |
| 0.82 | 5 | Fonctionnement | Piscines | Analyse des eaux de piscine | ENTRETIEN PISCINE / ANALYSE DES EAUX DE PISCINE |
| 0.59 | 5 | Fonctionnement | Travaux divers | Puits | DIVERS ENTRETIENS / PUIT |
| 0.39 | 4 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | ONEE | ANIMATION ESTIVALE / ANIMATION ESTIVALE |
| 0.29 | 4 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | RAK | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.36 | 4 | Fonctionnement | Piscines | EtÃ© | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.60 | 3 | Fonctionnement | Agent de service | Charges Social | GARDIENNAGE / CHARGES SOCIALES |
| 0.30 | 3 | Investissement | Budget d'investissement | Budget d'investissement | BORDAGE / BORDAGE DES ENTRES DES IMMEUBLES |
| 0.37 | 3 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | Amendis | ENTRETIEN PISCINE / MOD+ PDS |
| 0.44 | 3 | Fonctionnement | Divers | Outillages et petits Ã©quipements | DIVERS ENTRETIENS / PORTES AUTOMATIQUES |
| 0.41 | 3 | Fonctionnement | INVESSTISSEMENT | Changement de la porte garage | DIVERS ENTRETIENS / ANTENNE PARABOLIQUE |
| 0.77 | 3 | Fonctionnement | INVESSTISSEMENT | La colle pour les tois | DIVERS ENTRETIENS / LA COLLE POUR LES TOIS |
| 0.41 | 3 | Fonctionnement | INVESSTISSEMENT | SÃ¨che linge | DIVERS ENTRETIENS / TRAVAUX DE PEINTURE |
| 0.72 | 3 | Fonctionnement | MAINTENANCE | ContrÃ´le d'accÃ¨s pour ascenseur | MAINTENANCE / PIECES DE RECHANGE POUR ASCENSEURS |
| 0.60 | 3 | Fonctionnement | MAINTENANCE | Travaux d'alpinisme | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.49 | 3 | Fonctionnement | Piscines | Produits et piÃ¨ce de rechange | ENTRETIEN PISCINE / PRODUIT D'ENTRETIEN |
| 0.38 | 3 | Fonctionnement | Travaux divers | Achat guérite | DIVERS ENTRETIENS / TRAVAUX DE MENUISERIES |
| 0.43 | 2 | Fonctionnement | Budget de fonctionnement | ASSURANCE MULTIRISQUE | ASSURANCES / ASS MULTIRISQUES ET RC |
| 0.41 | 2 | Fonctionnement | Budget de fonctionnement | ENTRETIEN PISCINE | ENTRETIEN PISCINE / EQUIPEMENT PISCINE |
| 0.41 | 2 | Fonctionnement | Budget de fonctionnement | FRAIS JURDIQUE | REMUNERATIONS DU SYNDIC / FRAIS DE GESTION |
| 0.35 | 2 | Investissement | ElectricitÃ© | DÃ©tecteurs de mouvements | EXTINCTEURS / ACHAT DES EXTINCTEURS + BAC A SABLE INCENDIE |
| 0.68 | 2 | Fonctionnement | GARDIENNAGE | SÃ©curitÃ© | GARDIENNAGE / SECURTIE-JOUR/NUIT |
| 0.55 | 2 | Fonctionnement | Gestion Administrative | Copropriétaires douteux (taux 10%) | GESTION ADMINISTRATIVE / PRIME DE RENDEMENT POUR LA STE DE GESTION |
| 0.61 | 2 | Fonctionnement | Gestion Administrative | Organisation AG | JURIDIQUE / ORGANISATION AG |
| 0.45 | 2 | Fonctionnement | INVESSTISSEMENT | Achat Parasols | DIVERS ENTRETIENS / ANTENNE PARABOLIQUE |
| 0.40 | 2 | Fonctionnement | INVESSTISSEMENT | Achat Transates | MAINTENANCE / ASCENSEURS |
| 0.47 | 2 | Fonctionnement | INVESSTISSEMENT | Bordage des entrées des immeubles | DIVERS ENTRETIENS / RECHARGE DES EXTINCTEURS |
| 0.42 | 2 | Fonctionnement | INVESSTISSEMENT | DÃ©tecteurs de mouvements | DIVERS ENTRETIENS / TRAVAUX DE MENUISERIES |
| 0.57 | 2 | Fonctionnement | MAINTENANCE | Entretien Télédistribution | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.74 | 2 | Fonctionnement | MAINTENANCE | Maintenance Multi technique | MAINTENANCE / MAINTENANCE SURPRESSEURS |
| 0.63 | 2 | Fonctionnement | MAINTENANCE | Securite incendie | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.61 | 2 | Fonctionnement | Piscines | Analyses Piscine | ENTRETIEN PISCINE / ANALYSE DES EAUX DE PISCINE |
| 0.42 | 2 | Fonctionnement | t | SECURITE | GARDIENNAGE / SECURTIE-JOUR |
| 0.47 | 2 | Fonctionnement | Travaux divers | Achat des extincteurs + Bac a sable incendie | DIVERS ENTRETIENS / RECHARGE DES EXTINCTEURS |
| 0.34 | 2 | Fonctionnement | Travaux divers | Achat ecran caméra | ELECTRICITE / CONSOMMATION ELECTRICITE |
| 0.27 | 1 | Fonctionnement | Budget de fonctionnement | 3D | JARDINAGE / MOD |
| 0.32 | 1 | Fonctionnement | Budget de fonctionnement | ASCENSEUR CONTRAT COMPLET PIÈCE ET MAIN D'OEUVRE | MAINTENANCE / ACHATS MATERIELS & ACCESSOIRES |
| 0.35 | 1 | Fonctionnement | Budget de fonctionnement | ENERGIE ET FLUIDES | AGENT DE SERVICE / SUPERVISEUR |
| 0.36 | 1 | Fonctionnement | Budget de fonctionnement | ENTRETIEN ESPACES VERT | ANIMATION ESTIVALE / ANIMATION ESTIVALE |
| 0.41 | 1 | Fonctionnement | Budget de fonctionnement | FRAIS FINANCIER | REMUNERATIONS DU SYNDIC / FRAIS DE GESTION |
| 0.34 | 1 | Fonctionnement | Budget de fonctionnement | GARDIENNAGE | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.51 | 1 | Fonctionnement | Budget de fonctionnement | HONORAIRES SYNDIC + GESTIONNAIRE | REMUNERATIONS DU SYNDIC / HONORAIRE DE SYNDIC |
| 0.37 | 1 | Fonctionnement | Budget de fonctionnement | INVESTISSEMENT ET DIVERS | JARDINAGE / EMBILLISSEMENT & PLANTATION |
| 0.49 | 1 | Fonctionnement | Budget de fonctionnement | MAINTENANCE DIVERS | MAINTENANCE / MAINTENANCE SURPRESSEURS |
| 0.49 | 1 | Fonctionnement | Budget de fonctionnement | SECURITE | GARDIENNAGE / SECURTIE-JOUR |
| 0.34 | 1 | Fonctionnement | Budget de fonctionnement | TECHNICIEN PERMANANT SUR SITE | GESTION ADMINISTRATIVE / PRIME DE RENDEMENT POUR LE GESTIONNAIRE |
| 0.45 | 1 | Fonctionnement | CHARGES FIXES | Budget de fonctionnement | DIVERS ENTRETIENS / TRAVAUX DE CONSTRUCTION |
| 0.36 | 1 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | Radema | REMUNERATIONS DU SYNDIC / FRAIS DE GESTION |
| 0.39 | 1 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | REDAL-EAU | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.41 | 1 | Fonctionnement | CONSOMMATION EAU ELECTRICITE | REDAL-ELEC | ELECTRICITE / CONSOMMATION ELECTRICITE |
| 0.50 | 1 | Fonctionnement | ENTRETIEN ESPACE VERTS | JARDINAGE | ENTRETIEN PISCINE / MAITRE NAGEUR |
| 0.61 | 1 | Fonctionnement | ENTRETIEN ESPACE VERTS | PRODUIT ET ACCESSOIRES | JARDINAGE / PRODUIT ET ACCESSOIRES |
| 0.64 | 1 | Fonctionnement | ENTRETIEN PISCINE | ANALYSES | ENTRETIEN PISCINE / ANALYSE DES EAUX DE PISCINE |
| 0.72 | 1 | Fonctionnement | ENTRETIEN PISCINE | BRACELETS | ENTRETIEN PISCINE / IMPRESSION & BRACELETS |
| 0.57 | 1 | Fonctionnement | Expertise comptable | Honoraire d'audit | REMUNERATIONS DU SYNDIC / HONORAIRE DE SYNDIC |
| 0.58 | 1 | Fonctionnement | Gestion Administrative | Frais administratifs et juridiques | GESTION ADMINISTRATIVE / PRIME EXCEPTIONNELLE POUR LE PERSONNEL (NETTY, GARDIEN,….) |
| 0.55 | 1 | Fonctionnement | Gestion Administrative | Gestion des risques | GESTION ADMINISTRATIVE / PRIME DE RENDEMENT POUR LE GESTIONNAIRE |
| 0.45 | 1 | Fonctionnement | INVESSTISSEMENT | Air de jeux | DIVERS ENTRETIENS / TRAVAUX DE PEINTURE |
| 0.77 | 1 | Fonctionnement | INVESSTISSEMENT | Antenne parabolique | DIVERS ENTRETIENS / ANTENNE PARABOLIQUE |
| 0.41 | 1 | Fonctionnement | INVESSTISSEMENT | Changement porte de garage | GESTION ADMINISTRATIVE / PRIME DE RENDEMENT POUR LA STE DE GESTION |
| 0.44 | 1 | Fonctionnement | INVESSTISSEMENT | Extracteurs | DIVERS ENTRETIENS / RECHARGE DES EXTINCTEURS |
| 0.72 | 1 | Fonctionnement | INVESSTISSEMENT | Installation panneau signalétique | DIVERS ENTRETIENS / INSTALLATION PANNEAU SIGNALITIQUE |
| 0.39 | 1 | Fonctionnement | INVESSTISSEMENT | Irrigation | ANIMATION ESTIVALE / ANIMATION ESTIVALE |
| 0.51 | 1 | Fonctionnement | INVESSTISSEMENT | Peinture | DIVERS ENTRETIENS / TRAVAUX DE PEINTURE |
| 0.77 | 1 | Fonctionnement | INVESSTISSEMENT | Polissage du marbre | DIVERS ENTRETIENS / POLISSAGE DU MARBRE |
| 0.33 | 1 | Fonctionnement | INVESSTISSEMENT | Ria | DIVERS ENTRETIENS / PUIT |
| 0.77 | 1 | Fonctionnement | INVESSTISSEMENT | Travaux de menuiseries | DIVERS ENTRETIENS / TRAVAUX DE MENUISERIES |
| 0.60 | 1 | Fonctionnement | JARDINAGE | Entretien | JARDINAGE / EMBILLISSEMENT & PLANTATION |
| 0.61 | 1 | Fonctionnement | MAINTENANCE | Electricité - Fourniture | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.61 | 1 | Fonctionnement | MAINTENANCE | Electricité : MOD - Agent polyvalent | MAINTENANCE / PIECES DE RECHANGE POUR ASCENSEURS |
| 0.55 | 1 | Fonctionnement | MAINTENANCE | Opération 3D | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.65 | 1 | Fonctionnement | MAINTENANCE | REPARATION DIVERS | MAINTENANCE / RECHARGE DES EXTINCTEURS |
| 0.73 | 1 | Fonctionnement | MAINTENANCE | Travaux de construction | DIVERS ENTRETIENS / TRAVAUX DE CONSTRUCTION |
| 0.73 | 1 | Fonctionnement | MAINTENANCE | Travaux de forgeron | DIVERS ENTRETIENS / TRAVAUX DE FORGERON |
| 0.54 | 1 | Fonctionnement | NETTOYAGE | HYGIENE-3D | NETTOYAGE / PRODUITS DE MENAGE |
| 0.41 | 1 | Fonctionnement | Produits de menage | Prd | NETTOYAGE / FDM |
| 0.58 | 1 | Fonctionnement | t | FOND DE RESERVE | FOND DE RESERVE / FOND DE RESERVE |
| 0.58 | 1 | Fonctionnement | t | QUOTE PART CS | GARDIENNAGE / QUOTE PART CS |

## Deja alignes

| Occurrences | Budget | Poste | Rubrique |
|---:|---|---|---|
| 86 | Fonctionnement | MAINTENANCE | ASCENSEURS |
| 81 | Fonctionnement | NETTOYAGE | FDM |
| 74 | Fonctionnement | COMMISSION BANCAIRE | Frais de tenue de compte |
| 47 | Fonctionnement | FOND DE RESERVE | FOND DE RESERVE |
| 40 | Fonctionnement | GARDIENNAGE | Sécurité jour/nuit |
| 17 | Fonctionnement | MAINTENANCE | Pièces de rechange pour ascenseurs |
| 15 | Fonctionnement | GARDIENNAGE | SECURTIE-JOUR/NUIT |
| 12 | Fonctionnement | MAINTENANCE | Recharge des extincteurs |
| 10 | Fonctionnement | ASSURANCES | ASS MULTIRISQUES ET RC |
| 10 | Fonctionnement | GARDIENNAGE | Charges sociales |
| 10 | Fonctionnement | NETTOYAGE | Charge social - CNSS |
| 7 | Fonctionnement | GARDIENNAGE | SECURTIE-JOUR |
| 7 | Fonctionnement | GARDIENNAGE | SECURTIE-NUIT |
| 7 | Fonctionnement | NETTOYAGE | CANTONNIER |
| 5 | Fonctionnement | Agent de service | Superviseur |
| 5 | Fonctionnement | GARDIENNAGE | Quote part CS |
| 4 | Fonctionnement | Gestion Administrative | Prime de rendement pour la ste de gestion |
| 3 | Fonctionnement | ANIMATION ESTIVALE | Animation estivale |
| 3 | Fonctionnement | Gestion Administrative | Prime de rendement pour le gestionnaire |
| 3 | Fonctionnement | MAINTENANCE | Maintenance surpresseurs |
| 2 | Fonctionnement | GARDIENNAGE | Concierge |
| 2 | Fonctionnement | Gardiennage | QUOTE PART CS |
| 1 | Fonctionnement | Commission bancaire | Frais de tenue de compte |
| 1 | Fonctionnement | ENTRETIEN PISCINE | MAITRE NAGEUR |
| 1 | Fonctionnement | ENTRETIEN PISCINE | PRODUIT D'ENTRETIEN |
| 1 | Fonctionnement | Gardiennage | Charges sociales |
| 1 | Fonctionnement | Gardiennage | SECURTIE-JOUR |
| 1 | Fonctionnement | Gardiennage | SECURTIE-NUIT |
| 1 | Fonctionnement | Gardiennage | SECURITE-RENFORT |
| 1 | Fonctionnement | JARDINAGE | Balayeur |
| 1 | Fonctionnement | MAINTENANCE | Ascenseurs |
| 1 | Fonctionnement | NETTOYAGE | RENFORT |
