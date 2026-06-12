<?php

function formatSituationEncaissementsAmount($value)
{
    return number_format((float) $value, 2, ",", " ");
}

function getSituationEncaissementsPaymentStats($id_copropriete, $id_exercice, $from, $to, $connection)
{
    $stats = [
        "anterieur" => 0,
        "encours" => 0,
        "total" => 0,
    ];
    $request =
        "SELECT p.id, p.montant, " .
        "COALESCE(SUM(CASE WHEN r.id_exercice = ? THEN rrp.montant ELSE 0 END), 0) AS montant_encours, " .
        "COALESCE(SUM(CASE WHEN r.id_exercice <= 0 THEN rrp.montant ELSE 0 END), 0) AS montant_anterieur " .
        "FROM paiement p " .
        "INNER JOIN lot l ON l.id = p.id_lot " .
        "LEFT JOIN rel_rel_paiement rrp ON rrp.id_paiement = p.id " .
        "LEFT JOIN rel_lot_exercice r ON r.id_rel = rrp.id_rel " .
        "WHERE l.id_copropriete = ? AND CAST(p.date as date) BETWEEN ? AND ? " .
        "GROUP BY p.id, p.montant";

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ssss", $id_exercice, $id_copropriete, $from, $to);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $montant, $montantEncours, $montantAnterieur);
            while ($stmt->fetch()) {
                $stats["total"] += (float) $montant;
                $stats["encours"] += (float) $montantEncours;
                $stats["anterieur"] += (float) $montantAnterieur;
            }
        }
    }

    return $stats;
}

function getSituationEncaissementsRows($id_copropriete, $exercice, $connection)
{
    $rows = [];
    $totals = [
        "baseTheorique" => 0,
        "anterieur" => 0,
        "encours" => 0,
        "avance" => 0,
        "totalMensuel" => 0,
        "ecartMensuel" => 0,
    ];
    $baseTheorique = (float) $exercice["montantFonct"] / 12;

    for ($i = 0; $i < 12; $i++) {
        $from = date(
            "Y-m-d",
            strtotime(date("Y-m-d", strtotime($exercice["dateDebut"])) . " + " . $i . " month")
        );
        $to = date(
            "Y-m-d",
            strtotime(date("Y-m-d", strtotime($exercice["dateDebut"])) . " + " . ($i + 1) . " month -1 day")
        );
        $stats = getSituationEncaissementsPaymentStats(
            $id_copropriete,
            $exercice["id"],
            $from,
            $to,
            $connection
        );
        $avance = max(0, $stats["total"] - $stats["anterieur"] - $stats["encours"]);
        $ecartMensuel = $stats["encours"] - $baseTheorique;

        $row = [
            "mois" => date("m/Y", strtotime($from)),
            "baseTheorique" => $baseTheorique,
            "anterieur" => $stats["anterieur"],
            "encours" => $stats["encours"],
            "avance" => $avance,
            "totalMensuel" => $stats["total"],
            "ecartMensuel" => $ecartMensuel,
        ];
        $rows[] = $row;

        foreach ($totals as $key => $value) {
            $totals[$key] += $row[$key];
        }
    }

    return [
        "rows" => $rows,
        "totals" => $totals,
    ];
}

function getSituationEncaissementsFilename($prefix, $residenceName, $nameExercice, $extension)
{
    $filename = strtolower($prefix . "_" . $residenceName . "_" . $nameExercice);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : $prefix) . "." . $extension;
}
