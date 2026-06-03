<?php

function getCotisationExportData($id_copropriete, $id_exercice, $connection)
{
    $immeubleData = getCotisationExportImmeublesAndLots(
        $id_copropriete,
        $connection,
    );

    return [
        "immeubles" => $immeubleData["immeubles"],
        "lotsByImmeuble" => $immeubleData["lotsByImmeuble"],
        "previousRelSummaries" => getCotisationExportRelSummaries(
            $id_copropriete,
            null,
            $connection,
        ),
        "currentRelSummaries" => getCotisationExportRelSummaries(
            $id_copropriete,
            $id_exercice,
            $connection,
        ),
        "paymentTotals" => getCotisationExportPaymentTotals(
            $id_copropriete,
            $connection,
        ),
    ];
}

function getCotisationExportImmeublesAndLots($id_copropriete, $connection)
{
    $request =
        "SELECT lot.id, lot.code, lot.numero, lot.numeroImm, proprietaire.prenom, proprietaire.nom " .
        "FROM lot INNER JOIN proprietaire ON lot.id_proprietaire = proprietaire.id " .
        "WHERE lot.id_copropriete = ? ORDER BY lot.numeroImm ASC, lot.code ASC";
    $immeubles = [];
    $lotsByImmeuble = [];

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_copropriete);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $code, $numero, $numeroImm, $prenom, $nom);

        while ($stmt->fetch()) {
            $immeubleKey = (string) $numeroImm;
            if (!isset($immeubles[$immeubleKey])) {
                $immeubles[$immeubleKey] = ["numeroImm" => $numeroImm];
                $lotsByImmeuble[$immeubleKey] = [];
            }

            $lotsByImmeuble[$immeubleKey][] = [
                "id" => $id,
                "code" => $code,
                "numero" => $numero,
                "prenom" => $prenom,
                "nom" => $nom,
            ];
        }
    }

    return [
        "immeubles" => array_values($immeubles),
        "lotsByImmeuble" => $lotsByImmeuble,
    ];
}

function getCotisationExportRelSummaries(
    $id_copropriete,
    $id_exercice,
    $connection
) {
    $summaries = [];
    if ($id_exercice === null) {
        $request =
            "SELECT r.id_lot, SUM(COALESCE(r.cotisation, 0)), " .
            "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END) " .
            "FROM rel_lot_exercice r INNER JOIN lot l ON l.id = r.id_lot " .
            "WHERE l.id_copropriete = ? AND r.id_exercice <= 0 GROUP BY r.id_lot";
    } else {
        $request =
            "SELECT r.id_lot, SUM(COALESCE(r.cotisation, 0)), " .
            "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END) " .
            "FROM rel_lot_exercice r INNER JOIN lot l ON l.id = r.id_lot " .
            "WHERE l.id_copropriete = ? AND r.id_exercice = ? GROUP BY r.id_lot";
    }

    if ($stmt = $connection->prepare($request)) {
        if ($id_exercice === null) {
            $stmt->bind_param("s", $id_copropriete);
        } else {
            $stmt->bind_param("ss", $id_copropriete, $id_exercice);
        }
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id_lot, $totalPaye, $totalImpaye);

        while ($stmt->fetch()) {
            $summaries[(string) $id_lot] = [
                "totalPaye" => (float) $totalPaye,
                "totalImpaye" => (float) $totalImpaye,
            ];
        }
    }

    return $summaries;
}

function getCotisationExportPaymentTotals($id_copropriete, $connection)
{
    $request =
        "SELECT p.id_lot, SUM(COALESCE(p.montant, 0)) " .
        "FROM paiement p INNER JOIN lot l ON l.id = p.id_lot " .
        "WHERE l.id_copropriete = ? GROUP BY p.id_lot";
    $totals = [];

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_copropriete);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id_lot, $totalPaiement);

        while ($stmt->fetch()) {
            $totals[(string) $id_lot] = (float) $totalPaiement;
        }
    }

    return $totals;
}

function getCotisationExportSummary($summaries, $id_lot)
{
    $key = (string) $id_lot;
    if (isset($summaries[$key])) {
        return $summaries[$key];
    }

    return [
        "totalPaye" => 0,
        "totalImpaye" => 0,
    ];
}

function getCotisationExportPaymentTotal($totals, $id_lot)
{
    $key = (string) $id_lot;
    return isset($totals[$key]) ? $totals[$key] : 0;
}

function getCotisationExportPeriodDueFlags($dateDebut, $periods)
{
    $currentYm = intval(date("Ym"));
    $flags = [];

    foreach ($periods as $index => $period) {
        $periodYm = intval(
            date(
                "Ym",
                strtotime(
                    date("Y-m-d", strtotime($dateDebut)) .
                        " + " .
                        $period["startOffset"] .
                        " month",
                ),
            ),
        );
        $flags[$index] = $currentYm >= $periodYm;
    }

    return $flags;
}
