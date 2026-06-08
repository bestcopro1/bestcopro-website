<?php

function getSuiviBudgetRows($id_exercice, $connection)
{
    $exercice = getExercice($id_exercice, null, $connection);
    if (count($exercice) === 0) {
        return [];
    }

    $dateDebut = $exercice[0]["dateDebut"];
    $dateFin = $exercice[0]["dateFin"];
    $idCopropriete = $exercice[0]["id_copropriete"];
    $moisEcoules = getSuiviBudgetElapsedMonths($dateDebut, $dateFin);

    $request =
        "SELECT rubrique.id, rubrique.libelle, poste.id, poste.libelle, poste.montant, " .
        "COALESCE(SUM(depense.montant), 0) AS cout " .
        "FROM rubrique " .
        "INNER JOIN poste ON poste.id_rubrique = rubrique.id " .
        "LEFT JOIN depense ON depense.id_poste = poste.id AND CAST(depense.date AS date) BETWEEN ? AND ? " .
        "WHERE rubrique.id_exercice = ? AND rubrique.id_typeRubrique = 1 AND COALESCE(poste.montant, 0) <> 0 " .
        "GROUP BY rubrique.id, rubrique.libelle, poste.id, poste.libelle, poste.montant " .
        "ORDER BY rubrique.id ASC, poste.id ASC";
    $rubriques = [];

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $dateDebut, $dateFin, $id_exercice);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result(
            $id_rubrique,
            $rubrique,
            $id_poste,
            $poste,
            $budget,
            $cout,
        );

        while ($stmt->fetch()) {
            addSuiviBudgetRow(
                $rubriques,
                $id_rubrique,
                $rubrique,
                $poste,
                $budget,
                $cout,
                $moisEcoules,
            );
        }
    }

    return count($rubriques) > 0
        ? $rubriques
        : getSuiviBudgetFallbackRows(
            $idCopropriete,
            $dateDebut,
            $dateFin,
            $moisEcoules,
            $connection,
        );
}

function getSuiviBudgetFallbackRows(
    $id_copropriete,
    $dateDebut,
    $dateFin,
    $moisEcoules,
    $connection,
)
{
    $request =
        "SELECT MIN(rubrique.id) AS id_rubrique, rubrique.libelle, poste.libelle, " .
        "MAX(poste.montant) AS budget, " .
        "COALESCE(SUM(depense.montant), 0) AS cout " .
        "FROM rubrique " .
        "INNER JOIN exercice ON exercice.id = rubrique.id_exercice " .
        "INNER JOIN poste ON poste.id_rubrique = rubrique.id " .
        "LEFT JOIN depense ON depense.id_poste = poste.id AND CAST(depense.date AS date) BETWEEN ? AND ? " .
        "WHERE exercice.id_copropriete = ? AND rubrique.id_typeRubrique = 1 AND COALESCE(poste.montant, 0) <> 0 " .
        "GROUP BY rubrique.libelle, poste.libelle " .
        "ORDER BY MIN(rubrique.id) ASC, MIN(poste.id) ASC";
    $rubriques = [];

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $dateDebut, $dateFin, $id_copropriete);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result(
            $id_rubrique,
            $rubrique,
            $poste,
            $budget,
            $cout,
        );

        while ($stmt->fetch()) {
            addSuiviBudgetRow(
                $rubriques,
                $id_rubrique,
                $rubrique,
                $poste,
                $budget,
                $cout,
                $moisEcoules,
            );
        }
    }

    return $rubriques;
}

function addSuiviBudgetRow(
    &$rubriques,
    $id_rubrique,
    $rubrique,
    $poste,
    $budget,
    $cout,
    $moisEcoules,
) {
    if (!isset($rubriques[$id_rubrique])) {
        $rubriques[$id_rubrique] = [
            "libelle" => $rubrique,
            "postes" => [],
            "totals" => getEmptySuiviBudgetTotals(),
        ];
    }

    $budget = floatval($budget);
    $cout = floatval($cout);
    $moisEcoules = intval($moisEcoules);
    $annuelRestant = $budget - $cout;
    $annuelPourcentageRestant =
        abs($budget) > 0.00001 ? ($annuelRestant / $budget) * 100 : 0;
    $partielMontant = ($budget / 12) * $moisEcoules;
    $partielRestant = $partielMontant - $cout;
    $partielPourcentageRestant =
        abs($partielMontant) > 0.00001
            ? ($partielRestant / $partielMontant) * 100
            : 0;

    $row = [
        "rubrique" => $rubrique,
        "poste" => $poste,
        "budget" => $budget,
        "cout" => $cout,
        "moisEcoules" => $moisEcoules,
        "annuelRestant" => $annuelRestant,
        "annuelPourcentageRestant" => $annuelPourcentageRestant,
        "partielMontant" => $partielMontant,
        "partielRestant" => $partielRestant,
        "partielPourcentageRestant" => $partielPourcentageRestant,
    ];

    $rubriques[$id_rubrique]["postes"][] = $row;
    addSuiviBudgetTotals($rubriques[$id_rubrique]["totals"], $row);
}

function getSuiviBudgetElapsedMonths($dateDebut, $dateFin = null)
{
    $startTimestamp = strtotime($dateDebut);
    if ($startTimestamp === false) {
        return 0;
    }

    $todayTimestamp = time();
    if ($todayTimestamp < $startTimestamp) {
        return 0;
    }

    $endTimestamp = $todayTimestamp;
    $dateFinTimestamp = $dateFin !== null ? strtotime($dateFin) : false;
    if ($dateFinTimestamp !== false) {
        $endTimestamp = min($endTimestamp, $dateFinTimestamp);
    }

    $startYear = intval(date("Y", $startTimestamp));
    $startMonth = intval(date("n", $startTimestamp));
    $endYear = intval(date("Y", $endTimestamp));
    $endMonth = intval(date("n", $endTimestamp));
    $months = (($endYear - $startYear) * 12) + ($endMonth - $startMonth) + 1;

    return max(0, min(12, $months));
}

function getEmptySuiviBudgetTotals()
{
    return [
        "budget" => 0,
        "cout" => 0,
        "annuelRestant" => 0,
        "partielMontant" => 0,
        "partielRestant" => 0,
    ];
}

function addSuiviBudgetTotals(&$totals, $row)
{
    $totals["budget"] += $row["budget"];
    $totals["cout"] += $row["cout"];
    $totals["annuelRestant"] += $row["annuelRestant"];
    $totals["partielMontant"] += $row["partielMontant"];
    $totals["partielRestant"] += $row["partielRestant"];
}

function getSuiviBudgetPercent($amount, $base)
{
    return abs($base) > 0.00001 ? ($amount / $base) * 100 : 0;
}

function formatSuiviBudgetAmount($amount)
{
    return number_format($amount, 2) . " MAD";
}

function formatSuiviBudgetPercent($percent)
{
    return number_format($percent, 2) . " %";
}
