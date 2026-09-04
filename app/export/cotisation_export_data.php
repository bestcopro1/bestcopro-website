<?php

require_once __DIR__ . "/export_calculations.php";

function getCotisationExportData(
    $id_copropriete,
    $id_exercice,
    $connection,
    $dateSituation = null
)
{
    $dateLimit = getCotisationExportDateLimit(
        $id_exercice,
        $dateSituation,
        $connection
    );
    $immeubleData = getCotisationExportImmeublesAndLots(
        $id_copropriete,
        $connection,
    );

    return [
        "dateLimit" => $dateLimit,
        "immeubles" => $immeubleData["immeubles"],
        "lotsByImmeuble" => $immeubleData["lotsByImmeuble"],
        "previousRelSummaries" => getCotisationExportRelSummaries(
            $id_copropriete,
            null,
            $connection,
            $dateLimit,
            $id_exercice
        ),
        "periodRowsByLot" => getCotisationExportPeriodRows(
            $id_copropriete,
            $id_exercice,
            $connection,
            $dateLimit
        ),
        "advanceTotals" => getCotisationExportAdvanceTotals(
            $id_copropriete,
            $connection,
            $dateLimit
        ),
    ];
}

function getCotisationExportDateLimit(
    $id_exercice,
    $dateSituation,
    $connection
)
{
    $exerciseEndDate = null;
    $request = "SELECT CAST(dateFin AS DATE) FROM exercice WHERE id = ? LIMIT 1";

    $stmt = $connection->prepare($request);
    if (!$stmt) {
        throw new RuntimeException("Preparation de la date de situation impossible : " . $connection->error);
    }
    $stmt->bind_param("s", $id_exercice);
    if (!$stmt->execute()) {
        throw new RuntimeException("Lecture de la date de situation impossible : " . $stmt->error);
    }
    $stmt->store_result();
    $stmt->bind_result($dateFin);
    if ($stmt->fetch() && $dateFin !== null && $dateFin !== "") {
        $exerciseEndDate = $dateFin;
    }
    $stmt->close();

    if ($exerciseEndDate === null) {
        return $dateSituation;
    }

    $today = date("Y-m-d");
    if ($dateSituation === null || $dateSituation > $today) {
        $dateSituation = $today;
    }

    if ($dateSituation > $exerciseEndDate) {
        return $exerciseEndDate;
    }

    return $dateSituation;
}

function getCotisationExportImmeublesAndLots($id_copropriete, $connection)
{
    $request =
        "SELECT lot.id, lot.code, lot.numero, lot.numeroImm, proprietaire.prenom, proprietaire.nom " .
        "FROM lot LEFT JOIN proprietaire ON lot.id_proprietaire = proprietaire.id " .
        "WHERE lot.id_copropriete = ? ORDER BY lot.numeroImm ASC, lot.code ASC";
    $immeubles = [];
    $lotsByImmeuble = [];

    $stmt = $connection->prepare($request);
    if (!$stmt) {
        throw new RuntimeException("Preparation des lots impossible : " . $connection->error);
    }
    $stmt->bind_param("s", $id_copropriete);
    if (!$stmt->execute()) {
        throw new RuntimeException("Lecture des lots impossible : " . $stmt->error);
    }
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
    $stmt->close();

    return [
        "immeubles" => array_values($immeubles),
        "lotsByImmeuble" => $lotsByImmeuble,
    ];
}

function getCotisationExportRelSummaries(
    $id_copropriete,
    $id_exercice,
    $connection,
    $dateSituation = null,
    $current_id_exercice = null
) {
    $summaries = [];
    $paidExpression = "SUM(COALESCE(r.cotisation, 0))";
    $unpaidExpression =
        "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END)";
    $joinPaiements = "";
    $usePaymentHistory = $dateSituation !== null && $dateSituation < date("Y-m-d");
    if ($usePaymentHistory) {
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
        $previousCondition = getPreviousExerciseRelConditionSql(
            "curr",
            "r",
            "prev",
            false
        );
        $request =
            "SELECT r.id_lot, " .
            $paidExpression .
            ", " .
            $unpaidExpression .
            " " .
            "FROM rel_lot_exercice r INNER JOIN lot l ON l.id = r.id_lot " .
            "INNER JOIN exercice curr ON curr.id = ? " .
            "LEFT JOIN exercice prev ON prev.id = r.id_exercice " .
            $joinPaiements .
            "WHERE l.id_copropriete = ? AND " .
            $previousCondition .
            " GROUP BY r.id_lot";
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

    $stmt = $connection->prepare($request);
    if (!$stmt) {
        throw new RuntimeException("Preparation du resume des cotisations impossible : " . $connection->error);
    }
    if ($stmt) {
        if ($id_exercice === null) {
            if (!$usePaymentHistory) {
                $stmt->bind_param("ss", $current_id_exercice, $id_copropriete);
            } else {
                $stmt->bind_param("sss", $dateSituation, $current_id_exercice, $id_copropriete);
            }
        } else {
            if (!$usePaymentHistory) {
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
        if (!$stmt->execute()) {
            throw new RuntimeException("Lecture du resume des cotisations impossible : " . $stmt->error);
        }
        $stmt->store_result();
        $stmt->bind_result($id_lot, $totalPaye, $totalImpaye);

        while ($stmt->fetch()) {
            $summaries[(string) $id_lot] = [
                "totalPaye" => (float) $totalPaye,
                "totalImpaye" => max(0, (float) $totalImpaye),
            ];
        }
        $stmt->close();
    }

    return $summaries;
}

function getCotisationExportPeriodRows(
    $id_copropriete,
    $id_exercice,
    $connection,
    $dateSituation
) {
    $rowsByLot = [];
    $request =
        "SELECT r.id_lot, r.id_rel, r.dateFinPeriode, " .
        "COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) AS montant_du, " .
        "COALESCE(r.cotisation, 0) AS montant_stocke, " .
        "COALESCE(SUM(rrp.montant), 0) AS montant_lie, " .
        "COALESCE(SUM(CASE WHEN CAST(p.date AS date) <= ? THEN rrp.montant ELSE 0 END), 0) AS montant_lie_date " .
        "FROM rel_lot_exercice r " .
        "INNER JOIN lot l ON l.id = r.id_lot " .
        "LEFT JOIN rel_rel_paiement rrp ON rrp.id_rel = r.id_rel " .
        "LEFT JOIN paiement p ON p.id = rrp.id_paiement " .
        "WHERE l.id_copropriete = ? AND r.id_exercice = ? " .
        "GROUP BY r.id_lot, r.id_rel, r.dateFinPeriode, r.partFonct, r.partInv, r.cotisation " .
        "ORDER BY r.id_lot ASC, r.dateFinPeriode ASC, r.id_rel ASC";

    $stmt = $connection->prepare($request);
    if (!$stmt) {
        throw new RuntimeException("Preparation des periodes de cotisation impossible : " . $connection->error);
    }
    $stmt->bind_param("sii", $dateSituation, $id_copropriete, $id_exercice);
    if (!$stmt->execute()) {
        throw new RuntimeException("Lecture des periodes de cotisation impossible : " . $stmt->error);
    }
    $stmt->bind_result(
        $idLot,
        $idRel,
        $dateFin,
        $montantDu,
        $montantStocke,
        $montantLie,
        $montantLieDate
    );

    while ($stmt->fetch()) {
        $montantDu = max(0, (float) $montantDu);
        $montantStocke = max(0, (float) $montantStocke);
        $montantLie = max(0, (float) $montantLie);
        $montantLieDate = max(0, (float) $montantLieDate);
        $liensComplets = abs($montantLie - $montantStocke) < 0.01;
        $montantPaye = $liensComplets ? $montantLieDate : $montantStocke;

        if (!$liensComplets && function_exists("bestcopro_export_log")) {
            bestcopro_export_log("Allocation de paiement incomplete; utilisation du montant de la periode.", [
                "id_rel" => (int) $idRel,
                "stored" => $montantStocke,
                "linked" => $montantLie,
            ]);
        }

        $key = (string) $idLot;
        if (!isset($rowsByLot[$key])) {
            $rowsByLot[$key] = [];
        }
        $rowsByLot[$key][] = [
            "id_rel" => (int) $idRel,
            "dateFinPeriode" => $dateFin,
            "montantDu" => $montantDu,
            "montantPaye" => min($montantDu, $montantPaye),
        ];
    }
    $stmt->close();

    return $rowsByLot;
}

function getCotisationExportPeriodRowsForLot($rowsByLot, $idLot)
{
    $key = (string) $idLot;
    return isset($rowsByLot[$key]) ? $rowsByLot[$key] : [];
}

function getCotisationExportAdvanceTotals(
    $id_copropriete,
    $connection,
    $dateSituation = null
)
{
    $request =
        "SELECT l.id, GREATEST(" .
        "COALESCE(payments.total_paiements, 0) - GREATEST(" .
        "COALESCE(allocations.total_affecte, 0), COALESCE(stored.total_cotisation, 0)" .
        "), 0) AS total_avance " .
        "FROM lot l " .
        "LEFT JOIN (SELECT id_lot, SUM(COALESCE(montant, 0)) AS total_paiements " .
        "FROM paiement WHERE CAST(date AS date) <= ? GROUP BY id_lot) payments ON payments.id_lot = l.id " .
        "LEFT JOIN (SELECT p.id_lot, SUM(COALESCE(rrp.montant, 0)) AS total_affecte " .
        "FROM rel_rel_paiement rrp INNER JOIN paiement p ON p.id = rrp.id_paiement " .
        "WHERE CAST(p.date AS date) <= ? GROUP BY p.id_lot) allocations ON allocations.id_lot = l.id " .
        "LEFT JOIN (SELECT id_lot, SUM(COALESCE(cotisation, 0)) AS total_cotisation " .
        "FROM rel_lot_exercice GROUP BY id_lot) stored ON stored.id_lot = l.id " .
        "WHERE l.id_copropriete = ?";
    $totals = [];

    $stmt = $connection->prepare($request);
    if (!$stmt) {
        throw new RuntimeException("Preparation des avances impossible : " . $connection->error);
    }
    if ($stmt) {
        $dateLimit = $dateSituation !== null ? $dateSituation : date("Y-m-d");
        $stmt->bind_param("ssi", $dateLimit, $dateLimit, $id_copropriete);
        if (!$stmt->execute()) {
            throw new RuntimeException("Lecture des avances impossible : " . $stmt->error);
        }
        $stmt->store_result();
        $stmt->bind_result($id_lot, $totalAvance);

        while ($stmt->fetch()) {
            $totals[(string) $id_lot] = (float) $totalAvance;
        }
        $stmt->close();
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

function getCotisationExportAdvanceTotal($totals, $id_lot)
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

function formatCotisationExportAmount($value, $decimals = 2)
{
    return number_format((float) $value, $decimals, ",", " ");
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
