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

function renderSituationImmeubleExcelTable($title, $rows, $headers)
{
    $totals = getSituationImmeubleTotals($rows);
    $html = '<tr><td colspan="5" class="section">' .
        escapeSituationImmeubleExcel($title) .
        "</td></tr>";
    $html .= "<tr>";
    $html .= '<th rowspan="2">' . escapeSituationImmeubleExcel($headers[0]) . "</th>";
    $html .= '<th rowspan="2">' . escapeSituationImmeubleExcel($headers[1]) . "</th>";
    $html .= '<th rowspan="2">' . escapeSituationImmeubleExcel($headers[2]) . "</th>";
    $html .= '<th colspan="2">Reste dû</th>';
    $html .= "</tr>";
    $html .= "<tr>";
    $html .= "<th>Montant total en chiffres</th>";
    $html .= "<th>Montant total en pourcentage</th>";
    $html .= "</tr>";

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $html .= "<tr>";
            $html .= "<td>" . escapeSituationImmeubleExcel($row["immeuble"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["baseTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["encaissementTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["resteTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeublePercent($row["restePercent"]) . "</td>";
            $html .= "</tr>";
        }
    } else {
        $html .= '<tr><td colspan="5" class="empty">Aucune donnee disponible dans le tableau</td></tr>';
    }

    $html .= '<tr class="total">';
    $html .= "<td>TOTAL GENERAL</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["baseTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["encaissementTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["resteTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeublePercent($totals["restePercent"]) . "</td>";
    $html .= "</tr>";
    $html .= '<tr><td colspan="5" class="spacer"></td></tr>';

    return $html;
}

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

$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$data = getSituationImmeubleData(
    $exercice[0]["id_copropriete"],
    $id_exercice,
    $connection
);

$htmlContent =
    '<html><head><meta charset="UTF-8"><style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 5px; white-space: nowrap; }
        th { background: #c8c8c8; text-align: center; font-weight: bold; }
        .title td { border: 0; font-weight: bold; font-size: 14px; }
        .section { background: #d9eaf7; font-weight: bold; text-align: left; }
        .amount { text-align: right; }
        .empty { text-align: center; }
        .total { background: #00B0F0; font-weight: bold; }
        .spacer { border: 0; height: 12px; }
    </style></head><body><table>';
$htmlContent .= '<tr class="title">';
$htmlContent .= "<td>BEST COPRO</td>";
$htmlContent .= '<td colspan="3">Situation par immeuble - ' .
    escapeSituationImmeubleExcel($nameExercice) .
    "</td>";
$htmlContent .= "<td>" . escapeSituationImmeubleExcel($residenceName) . "</td>";
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
    "Situation actuelle",
    $data["actuel"],
    [
        "Immeuble",
        "Base de cotisation",
        "Encaissement",
    ]
);
$htmlContent .= "</table></body></html>";

$filename = getSituationImmeubleFilename(
    "situation_par_immeuble",
    $residenceName,
    $nameExercice,
    "xls"
);
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Content-Length: " . strlen($htmlContent));
echo $htmlContent;
exit();
