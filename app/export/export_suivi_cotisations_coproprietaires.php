<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/suivi_cotisations_coproprietaires_data.php";

use Dompdf\Dompdf;

$connection = $GLOBALS["connection"];
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
$annee = date("Y", strtotime($exercice[0]["dateDebut"]));
$rows = getSuiviCotisationsCoproprietairesRows(
    $exercice[0]["id_copropriete"],
    $id_exercice,
    $connection
);
$totals = getSuiviCotisationsCoproprietairesTotals($rows);

function suiviCotisationsPdfEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$htmlContent =
    '<html><head><meta charset="UTF-8"><style>
        @page { margin: 18px 14px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #c8c8c8; text-align: center; }
        .header td { border: 0; font-size: 10px; }
        .title { font-size: 13px; font-weight: bold; text-align: center; }
        .amount { text-align: right; white-space: nowrap; }
        .total { background: #fff3e0; font-weight: bold; }
        .empty { text-align: center; color: #555; }
    </style></head><body>';

$htmlContent .= '<table class="header"><tr>';
$htmlContent .= "<td><strong>BEST COPRO</strong></td>";
$htmlContent .= '<td class="title">Suivi des cotisations des coproprietaires</td>';
$htmlContent .=
    '<td style="text-align:right;">' .
    suiviCotisationsPdfEscape($residenceName) .
    "<br>" .
    suiviCotisationsPdfEscape($nameExercice) .
    "<br>" .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr></table>";

$htmlContent .= "<table><thead><tr>";
$htmlContent .= "<th>NOM &amp; PRENOM des coproprietaires</th>";
$htmlContent .= "<th>Ref de la propriete</th>";
$htmlContent .= "<th>Solde au 01/01/" . suiviCotisationsPdfEscape($annee) . "</th>";
$htmlContent .= "<th>Montant annuel de la cotisation</th>";
$htmlContent .= "<th>Versements de l'exercice</th>";
$htmlContent .= "<th>Solde restant au 31/12/" . suiviCotisationsPdfEscape($annee) . "</th>";
$htmlContent .= "</tr></thead><tbody>";
if (count($rows) === 0) {
    $htmlContent .= '<tr><td colspan="6" class="empty">Aucune donnee disponible dans le tableau</td></tr>';
} else {
    foreach ($rows as $row) {
        $htmlContent .= "<tr>";
        $htmlContent .= "<td>" . suiviCotisationsPdfEscape($row["nomComplet"]) . "</td>";
        $htmlContent .= "<td>" . suiviCotisationsPdfEscape($row["code"]) . "</td>";
        $htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($row["soldeAnterieur"]) . "</td>";
        $htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($row["baseCotisation"]) . "</td>";
        $htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($row["encaissement"]) . "</td>";
        $htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($row["soldeRestant"]) . "</td>";
        $htmlContent .= "</tr>";
    }
}
$htmlContent .= '<tr class="total"><td colspan="2">TOTAL</td>';
$htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($totals["soldeAnterieur"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($totals["baseCotisation"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($totals["encaissement"]) . "</td>";
$htmlContent .= '<td class="amount">' . formatSuiviCotisationsCoproprietairesAmount($totals["soldeRestant"]) . "</td></tr>";
$htmlContent .= "</tbody></table></body></html>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    getSuiviCotisationsCoproprietairesFilename(
        "suivi_cotisations_coproprietaires",
        $residenceName,
        $nameExercice,
        "pdf"
    )
);
