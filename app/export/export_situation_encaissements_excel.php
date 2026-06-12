<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/situation_encaissements_data.php";
$connection = $GLOBALS["connection"];

function escapeSituationEncaissementsExcel($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function getSituationEncaissementsExcelImageData($path)
{
    if (!file_exists($path)) {
        return "";
    }

    return "data:image/png;base64," . base64_encode(file_get_contents($path));
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
$logo = getSituationEncaissementsExcelImageData(__DIR__ . "/logo.png");

$htmlContent =
    '<html><head><meta charset="UTF-8"><style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 5px; white-space: nowrap; }
        th { background: #f2f2f2; text-align: center; font-weight: bold; vertical-align: middle; }
        .title td { border: 0; font-weight: bold; font-size: 14px; vertical-align: top; }
        .logo-cell { text-align: left; width: 24%; }
        .logo { width: 120px; }
        .title-main { text-align: center; font-size: 22px; padding-top: 16px; width: 52%; }
        .info-cell { text-align: right; width: 24%; }
        .amount { text-align: right; }
        .month { font-weight: bold; }
        .total { background: #fff3e0; font-weight: bold; }
        .positive { color: #198754; }
        .negative { color: #dc3545; }
        .spacer { border: 0; height: 12px; }
    </style></head><body><table>';
$htmlContent .= '<tr class="title">';
$htmlContent .=
    '<td class="logo-cell">' .
    ($logo !== "" ? '<img class="logo" src="' . $logo . '" alt="logo">' : "") .
    "</td>";
$htmlContent .= '<td colspan="5" class="title-main">Situation des encaissements</td>';
$htmlContent .=
    '<td class="info-cell">' .
    escapeSituationEncaissementsExcel($residenceName) .
    "<br>" .
    escapeSituationEncaissementsExcel($nameExercice) .
    "<br>Situation arrêtée au " .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "<tr><td colspan=\"7\" class=\"spacer\"></td></tr>";
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

foreach ($data["rows"] as $row) {
    $ecartClass = $row["ecartMensuel"] >= 0 ? "positive" : "negative";
    $htmlContent .= "<tr>";
    $htmlContent .= '<td class="month">' . escapeSituationEncaissementsExcel($row["mois"]) . "</td>";
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
$htmlContent .= "</table></body></html>";

$filename = getSituationEncaissementsFilename(
    "situation_encaissements",
    $residenceName,
    $nameExercice,
    "xls"
);
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Content-Length: " . strlen($htmlContent));
echo $htmlContent;
exit();
