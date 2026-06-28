<?php

function formatSituationImmeubleAmount($value)
{
    return number_format((float) $value, 2, ",", " ");
}

function formatSituationImmeublePercent($value)
{
    return number_format((float) $value, 2, ",", " ") . " %";
}

function getEmptySituationImmeubleTotals()
{
    return [
        "baseTotal" => 0,
        "encaissementTotal" => 0,
        "resteTotal" => 0,
        "recouvrementPercent" => 0,
    ];
}

function addSituationImmeubleTotals(&$totals, $row)
{
    $totals["baseTotal"] += $row["baseTotal"];
    $totals["encaissementTotal"] += $row["encaissementTotal"];
    $totals["resteTotal"] += $row["resteTotal"];
}

function getSituationImmeublePercent($amount, $base)
{
    return abs((float) $base) > 0.00001 ? ((float) $amount * 100) / (float) $base : 0;
}

function getSituationImmeubleRows($id_copropriete, $id_exercice, $isCurrent, $connection)
{
    $rows = [];
    if ($isCurrent) {
        $request =
            "SELECT l.numeroImm, " .
            "SUM(COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0)) AS base_total, " .
            "SUM(COALESCE(r.cotisation, 0)) AS encaissement_total, " .
            "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) " .
            "THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END) AS reste_total " .
            "FROM lot l INNER JOIN rel_lot_exercice r ON r.id_lot = l.id " .
            "WHERE l.id_copropriete = ? AND r.id_exercice = ? " .
            "GROUP BY l.numeroImm ORDER BY l.numeroImm ASC";
    } else {
        $previousCondition = getPreviousExerciseRelConditionSql("curr", "r", "prev");
        $request =
            "SELECT l.numeroImm, " .
            "SUM(COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0)) AS base_total, " .
            "SUM(COALESCE(r.cotisation, 0)) AS encaissement_total, " .
            "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) " .
            "THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END) AS reste_total " .
            "FROM lot l INNER JOIN rel_lot_exercice r ON r.id_lot = l.id " .
            "INNER JOIN exercice curr ON curr.id = ? " .
            "LEFT JOIN exercice prev ON prev.id = r.id_exercice " .
            "WHERE l.id_copropriete = ? AND " .
            $previousCondition .
            " GROUP BY l.numeroImm ORDER BY l.numeroImm ASC";
    }

    if ($stmt = $connection->prepare($request)) {
        if ($isCurrent) {
            $stmt->bind_param("ss", $id_copropriete, $id_exercice);
        } else {
            $stmt->bind_param("ss", $id_exercice, $id_copropriete);
        }

        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($numeroImm, $baseTotal, $encaissementTotal, $resteTotal);

        while ($stmt->fetch()) {
            $baseTotal = (float) $baseTotal;
            $resteTotal = (float) $resteTotal;
            $rows[] = [
                "immeuble" => $numeroImm,
                "baseTotal" => $baseTotal,
                "encaissementTotal" => (float) $encaissementTotal,
                "resteTotal" => $resteTotal,
                "restePercent" => getSituationImmeublePercent($resteTotal, $baseTotal),
                "recouvrementPercent" => getSituationImmeublePercent($encaissementTotal, $baseTotal),
            ];
        }
    }

    return $rows;
}
function getSituationImmeubleData($id_copropriete, $id_exercice, $connection)
{
    return [
        "anterieur" => getSituationImmeubleRows(
            $id_copropriete,
            $id_exercice,
            false,
            $connection
        ),
        "actuel" => getSituationImmeubleRows(
            $id_copropriete,
            $id_exercice,
            true,
            $connection
        ),
    ];
}

function getSituationImmeubleTotals($rows, $baseOverride = null)
{
    $totals = getEmptySituationImmeubleTotals();
    foreach ($rows as $row) {
        addSituationImmeubleTotals($totals, $row);
    }
    if ($baseOverride !== null) {
        $totals["baseTotal"] = (float) $baseOverride;
        $totals["resteTotal"] = max(
            0,
            $totals["baseTotal"] - $totals["encaissementTotal"]
        );
    }
    $totals["restePercent"] = getSituationImmeublePercent(
        $totals["resteTotal"],
        $totals["baseTotal"]
    );
    $totals["recouvrementPercent"] = getSituationImmeublePercent(
        $totals["encaissementTotal"],
        $totals["baseTotal"]
    );

    return $totals;
}

function getSituationImmeubleFilename($prefix, $residenceName, $nameExercice, $extension)
{
    $filename = strtolower($prefix . "_" . $residenceName . "_" . $nameExercice);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : $prefix) . "." . $extension;
}
