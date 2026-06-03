<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/suivi_budget_data.php";
$connection = $GLOBALS["connection"];

use Dompdf\Dompdf;

$id_exercice = isset($_GET["id_exercice"]) ? $_GET["id_exercice"] : null;
if ($id_exercice === null) {
    http_response_code(400);
    exit("Parametres invalides");
}

$exercice = getExercice($id_exercice, null, $connection);
if (count($exercice) === 0) {
    http_response_code(404);
    exit("Exercice introuvable");
}

$rubriques = getSuiviBudgetRows($id_exercice, $connection);
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$globalTotals = getEmptySuiviBudgetTotals();

$htmlContent = "";
$htmlContent .=
    '<style>
        @page { margin: 18px 14px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 8px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3px; }
        th { background: #c8c8c8; text-align: center; }
        .header td { border: 0; font-size: 10px; }
        .title { font-size: 14px; font-weight: bold; text-align: center; }
        .amount, .percent { text-align: right; white-space: nowrap; }
        .rubrique-total { background: #edf2f7; font-weight: bold; }
        .global-total { background: #00B0F0; font-weight: bold; }
    </style>';
$htmlContent .= '<table class="header">';
$htmlContent .= "<tr>";
$htmlContent .= "<td><strong>BEST COPRO</strong></td>";
$htmlContent .= '<td class="title">Suivi budget de fonctionnement</td>';
$htmlContent .=
    '<td style="text-align:right;">' .
    htmlspecialchars($nameExercice, ENT_QUOTES, "UTF-8") .
    "<br>" .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</table>";

$htmlContent .= "<table>";
$htmlContent .= "<thead>";
$htmlContent .= "<tr>";
$htmlContent .= '<th rowspan="2">Rubrique</th>';
$htmlContent .= '<th rowspan="2">Poste</th>';
$htmlContent .= '<th rowspan="2">Montant budget</th>';
$htmlContent .= '<th rowspan="2">Coût</th>';
$htmlContent .= '<th colspan="2">Suivi budget annuel</th>';
$htmlContent .= '<th colspan="2">Suivi budget partiel</th>';
$htmlContent .= "</tr>";
$htmlContent .= "<tr>";
$htmlContent .= "<th>Montant restant</th>";
$htmlContent .= "<th>% restant</th>";
$htmlContent .= "<th>Montant total</th>";
$htmlContent .= "<th>% restant</th>";
$htmlContent .= "</tr>";
$htmlContent .= "</thead>";
$htmlContent .= "<tbody>";

foreach ($rubriques as $rubrique) {
    $rubriqueTotals = $rubrique["totals"];
    addSuiviBudgetTotals($globalTotals, $rubriqueTotals);

    foreach ($rubrique["postes"] as $index => $poste) {
        $htmlContent .= "<tr>";
        $htmlContent .=
            "<td>" .
            ($index === 0
                ? htmlspecialchars($rubrique["libelle"], ENT_QUOTES, "UTF-8")
                : "") .
            "</td>";
        $htmlContent .=
            "<td>" .
            htmlspecialchars($poste["poste"], ENT_QUOTES, "UTF-8") .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            formatSuiviBudgetAmount($poste["budget"]) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            formatSuiviBudgetAmount($poste["cout"]) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            formatSuiviBudgetAmount($poste["annuelRestant"]) .
            "</td>";
        $htmlContent .=
            '<td class="percent">' .
            formatSuiviBudgetPercent($poste["annuelPourcentageRestant"]) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            formatSuiviBudgetAmount($poste["partielMontant"]) .
            "</td>";
        $htmlContent .=
            '<td class="percent">' .
            formatSuiviBudgetPercent($poste["partielPourcentageRestant"]) .
            "</td>";
        $htmlContent .= "</tr>";
    }

    $htmlContent .= '<tr class="rubrique-total">';
    $htmlContent .=
        '<td colspan="2">TOTAL ' .
        htmlspecialchars($rubrique["libelle"], ENT_QUOTES, "UTF-8") .
        "</td>";
    $htmlContent .=
        '<td class="amount">' .
        formatSuiviBudgetAmount($rubriqueTotals["budget"]) .
        "</td>";
    $htmlContent .=
        '<td class="amount">' .
        formatSuiviBudgetAmount($rubriqueTotals["cout"]) .
        "</td>";
    $htmlContent .=
        '<td class="amount">' .
        formatSuiviBudgetAmount($rubriqueTotals["annuelRestant"]) .
        "</td>";
    $htmlContent .=
        '<td class="percent">' .
        formatSuiviBudgetPercent(
            getSuiviBudgetPercent(
                $rubriqueTotals["annuelRestant"],
                $rubriqueTotals["budget"],
            ),
        ) .
        "</td>";
    $htmlContent .=
        '<td class="amount">' .
        formatSuiviBudgetAmount($rubriqueTotals["partielMontant"]) .
        "</td>";
    $htmlContent .=
        '<td class="percent">' .
        formatSuiviBudgetPercent(
            getSuiviBudgetPercent(
                $rubriqueTotals["partielRestant"],
                $rubriqueTotals["partielMontant"],
            ),
        ) .
        "</td>";
    $htmlContent .= "</tr>";
}

$htmlContent .= '<tr class="global-total">';
$htmlContent .= '<td colspan="2">TOTAL GENERAL</td>';
$htmlContent .=
    '<td class="amount">' . formatSuiviBudgetAmount($globalTotals["budget"]) . "</td>";
$htmlContent .=
    '<td class="amount">' . formatSuiviBudgetAmount($globalTotals["cout"]) . "</td>";
$htmlContent .=
    '<td class="amount">' .
    formatSuiviBudgetAmount($globalTotals["annuelRestant"]) .
    "</td>";
$htmlContent .=
    '<td class="percent">' .
    formatSuiviBudgetPercent(
        getSuiviBudgetPercent(
            $globalTotals["annuelRestant"],
            $globalTotals["budget"],
        ),
    ) .
    "</td>";
$htmlContent .=
    '<td class="amount">' .
    formatSuiviBudgetAmount($globalTotals["partielMontant"]) .
    "</td>";
$htmlContent .=
    '<td class="percent">' .
    formatSuiviBudgetPercent(
        getSuiviBudgetPercent(
            $globalTotals["partielRestant"],
            $globalTotals["partielMontant"],
        ),
    ) .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</tbody>";
$htmlContent .= "</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    "suivi_budget_fonctionnement_" . str_replace(" ", "_", $nameExercice) . ".pdf",
);
