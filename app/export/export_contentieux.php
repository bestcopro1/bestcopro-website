<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();
require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

use Dompdf\Dompdf;

$connection = $GLOBALS["connection"];
$id_copropriete = isset($_GET["id_copropriete"]) ? $_GET["id_copropriete"] : null;
if ($id_copropriete === null) {
    http_response_code(400);
    exit("Parametres invalides");
}

function contentieuxPdfEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function contentieuxPdfDate($date)
{
    if ($date === null || $date === "" || $date === "0000-00-00") {
        return "";
    }

    return date("d/m/Y", strtotime($date));
}

function contentieuxPdfOwnerName($proprietaire)
{
    if (count($proprietaire) === 0) {
        return "";
    }

    return trim(
        $proprietaire[0]["civilite"] . " " . $proprietaire[0]["prenom"] . " " . $proprietaire[0]["nom"]
    );
}

function contentieuxPdfFilename($residenceName)
{
    $filename = strtolower("contentieux_" . $residenceName);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : "contentieux") . ".pdf";
}

$copropriete = getCopropriete($id_copropriete, $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$contentieuxs = getContentieux(null, $id_copropriete, $connection);

$htmlContent =
    '<html><head><meta charset="UTF-8"><style>
        @page { margin: 18px 14px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #c8c8c8; text-align: center; }
        .header td { border: 0; font-size: 10px; }
        .title { font-size: 14px; font-weight: bold; text-align: center; }
        .empty { text-align: center; color: #555; }
    </style></head><body>';

$htmlContent .= '<table class="header"><tr>';
$htmlContent .= "<td><strong>BEST COPRO</strong></td>";
$htmlContent .= '<td class="title">Contentieux</td>';
$htmlContent .=
    '<td style="text-align:right;">' .
    contentieuxPdfEscape($residenceName) .
    "<br>" .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr></table>";

$htmlContent .= "<table><thead><tr>";
$htmlContent .= "<th>Code du lot</th>";
$htmlContent .= "<th>Proprietaire</th>";
$htmlContent .= "<th>Etat</th>";
$htmlContent .= "<th>Date d'etat</th>";
$htmlContent .= "</tr></thead><tbody>";
if (count($contentieuxs) === 0) {
    $htmlContent .= '<tr><td colspan="4" class="empty">Aucune donnee disponible dans le tableau</td></tr>';
} else {
    foreach ($contentieuxs as $contentieux) {
        $lot = getLot($contentieux["id_lot"], null, null, $connection);
        if (count($lot) === 0) {
            continue;
        }
        $proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
        $etat = getEtat($contentieux["id_etat"], $connection);

        $htmlContent .= "<tr>";
        $htmlContent .= "<td>" . contentieuxPdfEscape($lot[0]["code"]) . "</td>";
        $htmlContent .= "<td>" . contentieuxPdfEscape(contentieuxPdfOwnerName($proprietaire)) . "</td>";
        $htmlContent .= "<td>" . contentieuxPdfEscape(count($etat) > 0 ? $etat[0]["libelle"] : "") . "</td>";
        $htmlContent .= "<td>" . contentieuxPdfDate($contentieux["date"]) . "</td>";
        $htmlContent .= "</tr>";
    }
}
$htmlContent .= "</tbody></table></body></html>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(contentieuxPdfFilename($residenceName));
