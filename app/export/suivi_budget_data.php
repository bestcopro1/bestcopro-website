<?php

function getSuiviBudgetRows($id_exercice, $connection)
{
    $request =
        "SELECT rubrique.id, rubrique.libelle, poste.id, poste.libelle, poste.montant, " .
        "COALESCE(SUM(depense.montant), 0) AS cout, " .
        "COUNT(DISTINCT DATE_FORMAT(depense.date, '%Y-%m')) AS mois_depense " .
        "FROM rubrique " .
        "INNER JOIN poste ON poste.id_rubrique = rubrique.id " .
        "LEFT JOIN depense ON depense.id_poste = poste.id AND depense.id_exercice = ? " .
        "WHERE rubrique.id_exercice = ? AND rubrique.id_typeRubrique = 2 AND COALESCE(poste.montant, 0) <> 0 " .
        "GROUP BY rubrique.id, rubrique.libelle, poste.id, poste.libelle, poste.montant " .
        "ORDER BY rubrique.id ASC, poste.id ASC";
    $rubriques = [];

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ss", $id_exercice, $id_exercice);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result(
            $id_rubrique,
            $rubrique,
            $id_poste,
            $poste,
            $budget,
            $cout,
            $moisDepense,
        );

        while ($stmt->fetch()) {
            if (!isset($rubriques[$id_rubrique])) {
                $rubriques[$id_rubrique] = [
                    "libelle" => $rubrique,
                    "postes" => [],
                    "totals" => getEmptySuiviBudgetTotals(),
                ];
            }

            $budget = floatval($budget);
            $cout = floatval($cout);
            $moisDepense = intval($moisDepense);
            $annuelRestant = $budget - $cout;
            $annuelPourcentageRestant =
                abs($budget) > 0.00001 ? ($annuelRestant / $budget) * 100 : 0;
            $partielMontant = ($budget / 12) * $moisDepense;
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
                "moisDepense" => $moisDepense,
                "annuelRestant" => $annuelRestant,
                "annuelPourcentageRestant" => $annuelPourcentageRestant,
                "partielMontant" => $partielMontant,
                "partielRestant" => $partielRestant,
                "partielPourcentageRestant" => $partielPourcentageRestant,
            ];

            $rubriques[$id_rubrique]["postes"][] = $row;
            addSuiviBudgetTotals($rubriques[$id_rubrique]["totals"], $row);
        }
    }

    return $rubriques;
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
