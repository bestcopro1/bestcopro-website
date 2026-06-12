<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/situation_immeuble_data.php";
$connection = $GLOBALS["connection"];

use Dompdf\Dompdf;

function getSituationImmeubleImageData($path)
{
    if (!file_exists($path)) {
        return "";
    }

    return "data:image/png;base64," . base64_encode(file_get_contents($path));
}

function renderSituationImmeublePdfTable($title, $rows, $headers)
{
    $totals = getSituationImmeubleTotals($rows);
    $html = '<h2>' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "</h2>";
    $html .= "<table>";
    $html .= "<thead>";
    $html .= "<tr>";
    $html .= '<th>' . htmlspecialchars($headers[0], ENT_QUOTES, "UTF-8") . "</th>";
    $html .= '<th>' . htmlspecialchars($headers[1], ENT_QUOTES, "UTF-8") . "</th>";
    $html .= '<th>' . htmlspecialchars($headers[2], ENT_QUOTES, "UTF-8") . "</th>";
    $html .= "<th>Montant reste dû</th>";
    $html .= "<th>Taux de recouvrement</th>";
    $html .= "</tr>";
    $html .= "</thead><tbody>";

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($row["immeuble"], ENT_QUOTES, "UTF-8") . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["baseTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["encaissementTotal"]) . "</td>";
            $html .= '<td class="amount">' . formatSituationImmeubleAmount($row["resteTotal"]) . "</td>";
            $html .= '<td class="percent">' . formatSituationImmeublePercent($row["recouvrementPercent"]) . "</td>";
            $html .= "</tr>";
        }
    } else {
        $html .= '<tr><td colspan="5" class="empty">Aucune donnée disponible dans le tableau</td></tr>';
    }

    $html .= '<tr class="global-total">';
    $html .= "<td>TOTAL GENERAL</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["baseTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["encaissementTotal"]) . "</td>";
    $html .= '<td class="amount">' . formatSituationImmeubleAmount($totals["resteTotal"]) . "</td>";
    $html .= '<td class="percent">' . formatSituationImmeublePercent($totals["recouvrementPercent"]) . "</td>";
    $html .= "</tr>";
    $html .= "</tbody></table>";

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
$logo = getSituationImmeubleImageData(__DIR__ . "/logo.png");

$htmlContent = "";
$htmlContent .=
    '<style>
        @page { margin: 18px 14px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #c8c8c8; text-align: center; }
        .header td { border: 0; font-size: 10px; vertical-align: top; }
        .logo-cell { text-align: left; width: 24%; }
        .logo { width: 120px; }
        .title { font-size: 26px; font-weight: bold; text-align: center; padding-top: 16px; width: 52%; }
        .info-cell { text-align: right; width: 24%; }
        .amount, .percent { text-align: right; white-space: nowrap; }
        .empty { text-align: center; }
        .global-total { background: #fff3e0; font-weight: bold; }
    </style>';
$htmlContent .= '<table class="header">';
$htmlContent .= "<tr>";
$htmlContent .=
    '<td class="logo-cell">' .
    ($logo !== "" ? '<img class="logo" src="' . $logo . '" alt="logo">' : "") .
    "</td>";
$htmlContent .= '<td class="title">Situation de recouvrement et impayés</td>';
$htmlContent .=
    '<td class="info-cell">' .
    htmlspecialchars($residenceName, ENT_QUOTES, "UTF-8") .
    "<br>" .
    htmlspecialchars($nameExercice, ENT_QUOTES, "UTF-8") .
    "<br>Situation arrêtée au " .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</table>";

$htmlContent .= renderSituationImmeublePdfTable(
    "Situation antérieure",
    $data["anterieur"],
    [
        "Immeuble",
        "Total des impayés antérieurs",
        "Encaissement",
    ]
);
$htmlContent .= renderSituationImmeublePdfTable(
    "Situation de la période encours",
    $data["actuel"],
    [
        "Immeuble",
        "Base de cotisation",
        "Encaissement",
    ]
);

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    getSituationImmeubleFilename(
        "situation_recouvrement_impayes",
        $residenceName,
        $nameExercice,
        "pdf"
    )
);
