<?php

function formatSuiviCotisationsCoproprietairesAmount($value)
{
    return number_format((float) $value, 2, ",", " ");
}

function getSuiviCotisationsCoproprietairesRows($id_copropriete, $id_exercice, $connection)
{
    $rows = [];
    $previousCondition = getPreviousExerciseRelConditionSql("curr", "r", "prev");
    $request =
        "SELECT l.id, l.code, p.civilite, p.prenom, p.nom, " .
        "COALESCE(prev.solde_anterieur, 0) AS solde_anterieur, " .
        "COALESCE(curr.base_cotisation, 0) AS base_cotisation, " .
        "COALESCE(curr.encaissement, 0) AS encaissement " .
        "FROM lot l " .
        "INNER JOIN proprietaire p ON p.id = l.id_proprietaire " .
        "LEFT JOIN (" .
        "SELECT id_lot, SUM(CASE WHEN COALESCE(partFonct, 0) + COALESCE(partInv, 0) > COALESCE(cotisation, 0) " .
        "THEN COALESCE(partFonct, 0) + COALESCE(partInv, 0) - COALESCE(cotisation, 0) ELSE 0 END) AS solde_anterieur " .
        "FROM rel_lot_exercice r INNER JOIN exercice curr ON curr.id = ? LEFT JOIN exercice prev ON prev.id = r.id_exercice WHERE " .
        $previousCondition .
        " GROUP BY r.id_lot" .
        ") prev ON prev.id_lot = l.id " .
        "LEFT JOIN (" .
        "SELECT id_lot, SUM(COALESCE(partFonct, 0) + COALESCE(partInv, 0)) AS base_cotisation, " .
        "SUM(COALESCE(cotisation, 0)) AS encaissement " .
        "FROM rel_lot_exercice WHERE id_exercice = ? GROUP BY id_lot" .
        ") curr ON curr.id_lot = l.id " .
        "WHERE l.id_copropriete = ? ORDER BY p.nom ASC, p.prenom ASC, l.code ASC";

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $id_exercice, $id_exercice, $id_copropriete);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result(
            $idLot,
            $code,
            $civilite,
            $prenom,
            $nom,
            $soldeAnterieur,
            $baseCotisation,
            $encaissement,
        );

        while ($stmt->fetch()) {
            $soldeAnterieur = (float) $soldeAnterieur;
            $baseCotisation = (float) $baseCotisation;
            $encaissement = (float) $encaissement;
            $rows[] = [
                "id_lot" => $idLot,
                "nomComplet" => trim($civilite . " " . $prenom . " " . $nom),
                "code" => $code,
                "soldeAnterieur" => $soldeAnterieur,
                "baseCotisation" => $baseCotisation,
                "encaissement" => $encaissement,
                "soldeRestant" => max(
                    0,
                    $soldeAnterieur + $baseCotisation - $encaissement
                ),
            ];
        }
    }

    return $rows;
}

function getSuiviCotisationsCoproprietairesTotals($rows)
{
    $totals = [
        "soldeAnterieur" => 0,
        "baseCotisation" => 0,
        "encaissement" => 0,
        "soldeRestant" => 0,
    ];

    foreach ($rows as $row) {
        $totals["soldeAnterieur"] += $row["soldeAnterieur"];
        $totals["baseCotisation"] += $row["baseCotisation"];
        $totals["encaissement"] += $row["encaissement"];
        $totals["soldeRestant"] += $row["soldeRestant"];
    }

    return $totals;
}

function getSuiviCotisationsCoproprietairesFilename($prefix, $residenceName, $nameExercice, $extension)
{
    $filename = strtolower($prefix . "_" . $residenceName . "_" . $nameExercice);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : $prefix) . "." . $extension;
}
