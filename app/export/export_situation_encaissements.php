<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/situation_encaissements_data.php";
$connection = $GLOBALS["connection"];

use Dompdf\Dompdf;

function getSituationEncaissementsImageData($path)
{
    if (!file_exists($path)) {
        return "";
    }

    $mime = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === "svg" ? "image/svg+xml" : "image/png";
    return "data:" . $mime . ";base64," . base64_encode(file_get_contents($path));
}

$id_exercice = isset($_GET["id_exercice"]) ? $_GET["id_exercice"] : null;
if ($id_exercice === null) {
    http_response_code(400);
    exit("Paramètres invalides");
}

$exercice = getExercice($id_exercice, null, $connection);
if (count($exercice) === 0) {
    http_response_code(404);
    exit("Exercice introuvable");
}

$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$data = getSituationEncaissementsRows(
    $exercice[0]["id_copropriete"],
    $exercice[0],
    $connection
);
$logo = getSituationEncaissementsImageData(__DIR__ . "/../best_copro_logo.svg");

$htmlContent = "";
$htmlContent .=
    '<style>
        @page { margin: 18px 14px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background: #f2f2f2; text-align: center; vertical-align: middle; }
        .header td { border: 0; font-size: 10px; vertical-align: top; }
        .logo-cell { text-align: left; width: 24%; }
        .logo { width: 120px; }
        .title { font-size: 24px; font-weight: bold; text-align: center; padding-top: 16px; width: 52%; }
        .info-cell { text-align: right; width: 24%; }
        .amount { text-align: right; white-space: nowrap; }
        .month { font-weight: bold; }
        .total { background: #fff3e0; font-weight: bold; }
        .positive { color: #198754; }
        .negative { color: #dc3545; }
    </style>';
$htmlContent .= '<table class="header"><tr>';
$htmlContent .=
    '<td class="logo-cell">' .
    ($logo !== "" ? '<img class="logo" src="' . $logo . '" alt="logo">' : "") .
    "</td>";
$htmlContent .= '<td class="title">Situation des encaissements</td>';
$htmlContent .=
    '<td class="info-cell">' .
    htmlspecialchars($residenceName, ENT_QUOTES, "UTF-8") .
    "<br>" .
    htmlspecialchars($nameExercice, ENT_QUOTES, "UTF-8") .
    "<br>Situation arrêtée au " .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr></table>";

$htmlContent .= "<table>";
$htmlContent .= "<thead>";
$htmlContent .= "<tr>";
$htmlContent .= '<th rowspan="2">Mois</th>';
$htmlContent .= '<th rowspan="2">Base théorique</th>';
$htmlContent .= '<th colspan="4">Encaissement</th>';
$htmlContent .= '<th rowspan="2">Écart Mensuel</th>';
$htmlContent .= "</tr>";
$htmlContent .= "<tr>";
$htmlContent .= "<th>Antérieur</th>";
$htmlContent .= "<th>En cours</th>";
$htmlContent .= "<th>Avance</th>";
$htmlContent .= "<th>Total Mensuel</th>";
$htmlContent .= "</tr>";
$htmlContent .= "</thead><tbody>";

foreach ($data["rows"] as $row) {
    $ecartClass = $row["ecartMensuel"] >= 0 ? "positive" : "negative";
    $htmlContent .= "<tr>";
    $htmlContent .= '<td class="month">' . htmlspecialchars($row["mois"], ENT_QUOTES, "UTF-8") . "</td>";
    $htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($row["baseTheorique"]) . "</td>";
    $htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($row["anterieur"]) . "</td>";
    $htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($row["encours"]) . "</td>";
    $htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($row["avance"]) . "</td>";
    $htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($row["totalMensuel"]) . "</td>";
    $htmlContent .= '<td class="amount ' . $ecartClass . '">' . formatSituationEncaissementsAmount($row["ecartMensuel"]) . "</td>";
    $htmlContent .= "</tr>";
}

$totals = $data["totals"];
$totalEcartClass = $totals["ecartMensuel"] >= 0 ? "positive" : "negative";
$htmlContent .= '<tr class="total">';
$htmlContent .= "<td>TOTAL</td>";
$htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($totals["baseTheorique"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($totals["anterieur"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($totals["encours"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($totals["avance"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSituationEncaissementsAmount($totals["totalMensuel"]) . "</td>";
$htmlContent .= '<td class="amount ' . $totalEcartClass . '">' . formatSituationEncaissementsAmount($totals["ecartMensuel"]) . "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</tbody></table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    getSituationEncaissementsFilename(
        "situation_encaissements",
        $residenceName,
        $nameExercice,
        "pdf"
    )
);
