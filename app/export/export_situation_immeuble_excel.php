<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/situation_immeuble_data.php";
$connection = $GLOBALS["connection"];

function escapeSituationImmeubleExcel($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function getSituationImmeubleExcelImageData($path)
{
    if (!file_exists($path)) {
        return "";
    }

    return "data:image/png;base64," . base64_encode(file_get_contents($path));
}

function renderSituationImmeubleExcelTable($title, $rows, $headers)
{
    $totals = getSituationImmeubleTotals($rows);
    $html = '<tr><td colspan="5" class="section">' .
        escapeSituationImmeubleExcel($title) .
        "</td></tr>";
    $html .= "<tr>";
    $html .= '<th>' . escapeSituationImmeubleExcel($headers[0]) . "</th>";
    $html .= '<th>' . escapeSituationImmeubleExcel($headers[1]) . "</th>";
    $html .= '<th>' . escapeSituationImmeubleExcel($headers[2]) . "</th>";
    $html .= "<th>Montant reste dû</th>";
    $html .= "<th>Taux de recouvrement</th>";
    $html .= "</tr>";

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $html .= "<tr>";
            $html .= "<td>" . escapeSituationImmeubleExcel($row["immeuble"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["baseTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["encaissementTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["resteTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeublePercent($row["recouvrementPercent"]) . "</td>";
            $html .= "</tr>";
        }
    } else {
        $html .= '<tr><td colspan="5" class="empty">Aucune donnée disponible dans le tableau</td></tr>';
    }

    $html .= '<tr class="total">';
    $html .= "<td>TOTAL GENERAL</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["baseTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["encaissementTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["resteTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeublePercent($totals["recouvrementPercent"]) . "</td>";
    $html .= "</tr>";
    $html .= '<tr><td colspan="5" class="spacer"></td></tr>';

    return $html;
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
$data = getSituationImmeubleData(
    $exercice[0]["id_copropriete"],
    $id_exercice,
    $connection
);
$logo = getSituationImmeubleExcelImageData(__DIR__ . "/logo.png");

$htmlContent =
    '<html><head><meta charset="UTF-8"><style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 5px; white-space: nowrap; }
        th { background: #c8c8c8; text-align: center; font-weight: bold; }
        .title td { border: 0; font-weight: bold; font-size: 14px; vertical-align: top; }
        .logo-cell { text-align: left; width: 24%; }
        .logo { width: 120px; }
        .title-main { text-align: center; font-size: 22px; padding-top: 16px; width: 52%; }
        .info-cell { text-align: right; width: 24%; }
        .section { background: #d9eaf7; font-weight: bold; text-align: left; }
        .amount { text-align: right; }
        .empty { text-align: center; }
        .total { background: #00B0F0; font-weight: bold; }
        .spacer { border: 0; height: 12px; }
    </style></head><body><table>';
$htmlContent .= '<tr class="title">';
$htmlContent .=
    '<td class="logo-cell">' .
    ($logo !== "" ? '<img class="logo" src="' . $logo . '" alt="logo">' : "") .
    "</td>";
$htmlContent .= '<td colspan="3" class="title-main">Situation de recouvrement et impayés</td>';
$htmlContent .=
    '<td class="info-cell">' .
    escapeSituationImmeubleExcel($residenceName) .
    "<br>" .
    escapeSituationImmeubleExcel($nameExercice) .
    "<br>Situation arrêtée au " .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "<tr><td colspan=\"5\" class=\"spacer\"></td></tr>";
$htmlContent .= renderSituationImmeubleExcelTable(
    "Situation antérieure",
    $data["anterieur"],
    [
        "Immeuble",
        "Total des impayés antérieurs",
        "Encaissement",
    ]
);
$htmlContent .= renderSituationImmeubleExcelTable(
    "Situation de la période encours",
    $data["actuel"],
    [
        "Immeuble",
        "Base de cotisation",
        "Encaissement",
    ]
);
$htmlContent .= "</table></body></html>";

$filename = getSituationImmeubleFilename(
    "situation_recouvrement_impayes",
    $residenceName,
    $nameExercice,
    "xls"
);
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Content-Length: " . strlen($htmlContent));
echo $htmlContent;
exit();
