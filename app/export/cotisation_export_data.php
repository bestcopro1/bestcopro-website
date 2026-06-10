<?php

function getCotisationExportData(
    $id_copropriete,
    $id_exercice,
    $connection,
    $dateSituation = null
)
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
            $dateSituation
        ),
        "currentRelSummaries" => getCotisationExportRelSummaries(
            $id_copropriete,
            $id_exercice,
            $connection,
            $dateSituation
        ),
        "paymentTotals" => getCotisationExportPaymentTotals(
            $id_copropriete,
            $connection,
            $dateSituation
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
    $connection,
    $dateSituation = null
) {
    $summaries = [];
    $paidExpression = "SUM(COALESCE(r.cotisation, 0))";
    $unpaidExpression =
        "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END)";
    $joinPaiements = "";
    if ($dateSituation !== null) {
        $paidExpression = "SUM(COALESCE(rp.montant_paye, 0))";
        $unpaidExpression =
            "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(rp.montant_paye, 0) THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(rp.montant_paye, 0) ELSE 0 END)";
        $joinPaiements =
            "LEFT JOIN (" .
            "SELECT rrp.id_rel, SUM(COALESCE(rrp.montant, 0)) AS montant_paye " .
            "FROM rel_rel_paiement rrp " .
            "INNER JOIN paiement p ON p.id = rrp.id_paiement " .
            "WHERE CAST(p.date AS date) <= ? " .
            "GROUP BY rrp.id_rel" .
            ") rp ON rp.id_rel = r.id_rel ";
    }

    if ($id_exercice === null) {
        $request =
            "SELECT r.id_lot, " .
            $paidExpression .
            ", " .
            $unpaidExpression .
            " " .
            "FROM rel_lot_exercice r INNER JOIN lot l ON l.id = r.id_lot " .
            $joinPaiements .
            "WHERE l.id_copropriete = ? AND r.id_exercice <= 0 GROUP BY r.id_lot";
    } else {
        $request =
            "SELECT r.id_lot, " .
            $paidExpression .
            ", " .
            $unpaidExpression .
            " " .
            "FROM rel_lot_exercice r INNER JOIN lot l ON l.id = r.id_lot " .
            $joinPaiements .
            "WHERE l.id_copropriete = ? AND r.id_exercice = ? GROUP BY r.id_lot";
    }

    if ($stmt = $connection->prepare($request)) {
        if ($id_exercice === null) {
            if ($dateSituation === null) {
                $stmt->bind_param("s", $id_copropriete);
            } else {
                $stmt->bind_param("ss", $dateSituation, $id_copropriete);
            }
        } else {
            if ($dateSituation === null) {
                $stmt->bind_param("ss", $id_copropriete, $id_exercice);
            } else {
                $stmt->bind_param(
                    "sss",
                    $dateSituation,
                    $id_copropriete,
                    $id_exercice
                );
            }
        }
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id_lot, $totalPaye, $totalImpaye);

        while ($stmt->fetch()) {
            $summaries[(string) $id_lot] = [
                "totalPaye" => (float) $totalPaye,
                "totalImpaye" => max(0, (float) $totalImpaye),
            ];
        }
    }

    return $summaries;
}

function getCotisationExportPaymentTotals(
    $id_copropriete,
    $connection,
    $dateSituation = null
)
{
    $request =
        "SELECT p.id_lot, SUM(COALESCE(p.montant, 0)) " .
        "FROM paiement p INNER JOIN lot l ON l.id = p.id_lot " .
        "WHERE l.id_copropriete = ? ";
    if ($dateSituation !== null) {
        $request .= "AND CAST(p.date AS date) <= ? ";
    }
    $request .= "GROUP BY p.id_lot";
    $totals = [];

    if ($stmt = $connection->prepare($request)) {
        if ($dateSituation === null) {
            $stmt->bind_param("s", $id_copropriete);
        } else {
            $stmt->bind_param("ss", $id_copropriete, $dateSituation);
        }
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

function getCotisationExportDisplayAdvance($value)
{
    $value = (float) $value;
    if ($value < 10) {
        return 0;
    }

    return floor($value);
}

function getCotisationExportDisplayResteAPayer($value)
{
    return ceil((float) $value);
}

function getCotisationExportFilename(
    $prefix,
    $residenceName,
    $nameExercice,
    $dateSituation,
    $extension
) {
    $parts = [$prefix, $residenceName, $nameExercice];
    if ($dateSituation !== null) {
        $parts[] = "situation-" . date("Y-m-d", strtotime($dateSituation));
    }

    $filename = strtolower(implode("_", array_filter($parts)));
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    if ($filename == "") {
        $filename = $prefix;
    }

    return $filename . "." . $extension;
}

function getCotisationExportPeriodDueFlags(
    $dateDebut,
    $periods,
    $dateSituation = null
)
{
    $currentYm = intval(
        date(
            "Ym",
            $dateSituation !== null ? strtotime($dateSituation) : time()
        )
    );
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
