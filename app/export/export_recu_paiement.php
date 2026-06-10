<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

use Dompdf\Dompdf;

$connection = $GLOBALS["connection"];
$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);

if ($id == "") {
    exit("Paiement introuvable");
}

$paiement = getPaiement($id, null, null, $connection);
if (count($paiement) == 0) {
    exit("Paiement introuvable");
}

$lot = getLot($paiement[0]["id_lot"], null, null, $connection);
$proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
$copropriete = getCopropriete($lot[0]["id_copropriete"], $connection);
$modepaiement = getModepaiement($paiement[0]["id_modePaiement"], $connection);
$typeLot = getTypelot($lot[0]["id_typeLot"], $connection);
$relRelPaiements = getRel_rel_paiement($paiement[0]["id"], $connection);

function recuPaiementImageData($path)
{
    if (!file_exists($path)) {
        return "";
    }

    return "data:image/png;base64," . base64_encode(file_get_contents($path));
}

function recuPaiementRenderPeriods($relRelPaiements, $connection)
{
    $html = "";
    $totalRelPaiement = 0;
    foreach ($relRelPaiements as $relRelPaiement) {
        $totalRelPaiement += floatval($relRelPaiement["montant"]);
    }

    $chunks = array_chunk($relRelPaiements, 6);
    foreach ($chunks as $chunk) {
        $html .= '<table class="period-table"><tbody><tr>';
        foreach ($chunk as $relRelPaiement) {
            $periodeInfo = periodeInfo($relRelPaiement["id_rel"], $connection);
            $html .=
                '<td><strong>' .
                htmlspecialchars($periodeInfo[0]["nomPeriode"], ENT_QUOTES, "UTF-8") .
                "</strong></td>";
        }
        $html .= "</tr><tr>";
        foreach ($chunk as $relRelPaiement) {
            $html .=
                "<td>" .
                number_format(floatval($relRelPaiement["montant"]), 2, ",", " ") .
                " MAD</td>";
        }
        $html .= "</tr></tbody></table>";
    }

    return [
        "html" => $html,
        "totalRelPaiement" => $totalRelPaiement,
    ];
}

$periods = recuPaiementRenderPeriods($relRelPaiements, $connection);
$avance = floatval($paiement[0]["montant"]) - $periods["totalRelPaiement"];
$logo = recuPaiementImageData(__DIR__ . "/../images/logo.png");
$logoText = recuPaiementImageData(__DIR__ . "/../images/logo-text.png");

$htmlContent = "";
$htmlContent .=
    '<html><head><meta charset="UTF-8"><style>' .
    '@page { margin: 18px; } * { font-family: DejaVu Sans, sans-serif; } ' .
    'body { font-size: 12px; color: #222; } .card { border: 1px solid #ddd; } ' .
    '.header { padding: 12px; border-bottom: 1px solid #ddd; font-size: 14px; } ' .
    '.right { float: right; } .body { padding: 14px; } .row { width: 100%; } ' .
    '.col { display: inline-block; width: 49%; vertical-align: top; } ' .
    '.logo img { vertical-align: middle; } hr { border: 0; border-top: 1px solid #ddd; margin: 14px 0; } ' .
    '.line { margin-bottom: 8px; } .label { display: inline-block; min-width: 125px; font-weight: normal; } ' .
    '.value { font-weight: bold; } .period-title { margin: 10px 0 6px; } ' .
    '.period-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; } ' .
    '.period-table td { border: 1px solid #aaa; padding: 7px; text-align: center; font-size: 10px; } ' .
    '.signature { width: 30%; margin-left: auto; padding-top: 18px; padding-bottom: 40px; }' .
    '</style></head><body>';
$htmlContent .= '<div class="card">';
$htmlContent .= '<div class="header">';
$htmlContent .=
    "<strong>Reçu de paiement N° : " .
    date("y") .
    sprintf("%'.05d", $paiement[0]["id"]) .
    "</strong>";
$htmlContent .=
    '<span class="right">' .
    htmlspecialchars($copropriete[0]["ville"], ENT_QUOTES, "UTF-8") .
    " le " .
    date("d/m/Y") .
    "</span>";
$htmlContent .= "</div>";
$htmlContent .= '<div class="body">';
$htmlContent .= '<div class="row">';
$htmlContent .= '<div class="col logo">';
if ($logo != "") {
    $htmlContent .= '<img width="50" src="' . $logo . '" alt="">';
}
if ($logoText != "") {
    $htmlContent .= '<img width="110" src="' . $logoText . '" alt="">';
}
$htmlContent .= "</div>";
$htmlContent .= '<div class="col">';
$htmlContent .=
    "<div><strong>" .
    htmlspecialchars(
        $proprietaire[0]["civilite"] .
            " " .
            $proprietaire[0]["prenom"] .
            " " .
            $proprietaire[0]["nom"],
        ENT_QUOTES,
        "UTF-8"
    ) .
    "</strong></div>";
$htmlContent .=
    "<div>Copropriété : " .
    htmlspecialchars($copropriete[0]["nom"], ENT_QUOTES, "UTF-8") .
    "</div>";
$htmlContent .=
    "<div>" .
    htmlspecialchars($typeLot[0]["libelle"], ENT_QUOTES, "UTF-8") .
    " N° : " .
    htmlspecialchars($lot[0]["numero"], ENT_QUOTES, "UTF-8") .
    "</div>";
$htmlContent .=
    "<div>Immeuble N° : " .
    htmlspecialchars($lot[0]["numeroImm"], ENT_QUOTES, "UTF-8") .
    "</div>";
$htmlContent .= "</div></div><hr>";
$htmlContent .=
    '<div class="line"><span class="label">Montant total : </span><span class="value">' .
    number_format(floatval($paiement[0]["montant"]), 2, ",", " ") .
    " MAD</span></div>";
$htmlContent .=
    '<div class="line"><span class="label">Mode de paiement : </span><span class="value">' .
    htmlspecialchars($modepaiement[0]["libelle"], ENT_QUOTES, "UTF-8") .
    "</span></div>";
$htmlContent .=
    '<div class="line"><span class="label">Date de paiement : </span><span class="value">' .
    date("d/m/Y", strtotime($paiement[0]["date"])) .
    "</span></div>";
$htmlContent .= '<div class="period-title">Périodes associées :</div>';
$htmlContent .= $periods["html"];
if ($avance > 0) {
    $htmlContent .= '<table class="period-table"><tbody><tr><td><strong>Avance</strong></td></tr><tr><td>';
    $htmlContent .= number_format($avance, 2, ",", " ") . " MAD";
    $htmlContent .= "</td></tr></tbody></table>";
}
$htmlContent .= '<div class="signature">Signature</div>';
$htmlContent .= "</div></div></body></html>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "portrait");
$dompdf->render();
$dompdf->stream(
    "recu_paiement_" . date("y") . sprintf("%'.05d", $paiement[0]["id"]) . ".pdf",
    ["Attachment" => true]
);
exit();
