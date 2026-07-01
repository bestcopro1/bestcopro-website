<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();
require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

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

function facturesNonPayeesPdfEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function facturesNonPayeesPdfDate($date)
{
    if ($date === null || $date === "" || $date === "0000-00-00") {
        return "";
    }

    return date("d/m/Y", strtotime($date));
}

function facturesNonPayeesPdfSyndicName($id_syndic, $connection)
{
    $syndic = getSyndic($id_syndic, null, $connection);
    if (count($syndic) === 0) {
        return "";
    }

    return trim(
        $syndic[0]["civilite"] . " " . $syndic[0]["prenom"] . " " . $syndic[0]["nom"]
    );
}

function facturesNonPayeesPdfPosteName($id_poste, $connection)
{
    $poste = getPoste($id_poste, null, null, $connection);
    return count($poste) > 0 ? $poste[0]["libelle"] : "";
}

function facturesNonPayeesPdfFournisseurName($id_fournisseur, $connection)
{
    $fournisseur = getFournisseur($id_fournisseur, $connection);
    return count($fournisseur) > 0 ? $fournisseur[0]["raisonSocial"] : "";
}

function facturesNonPayeesPdfFilename($nameExercice)
{
    $filename = strtolower("factures_non_payees_" . $nameExercice);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : "factures_non_payees") . ".pdf";
}

$depenses = getDepense(null, $id_exercice, $connection);
$depensesNonPayees = array_filter($depenses, function ($depense) {
    return ($depense["situationPaiement"] ?? "paye") == "non_paye" && getDepenseResteDu($depense) > 0;
});
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";

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
        .amount { text-align: right; white-space: nowrap; }
        .empty { text-align: center; color: #555; }
    </style></head><body>';

$htmlContent .= '<table class="header"><tr>';
$htmlContent .= "<td><strong>BEST COPRO</strong></td>";
$htmlContent .= '<td class="title">Factures non payees</td>';
$htmlContent .=
    '<td style="text-align:right;">' .
    facturesNonPayeesPdfEscape($residenceName) .
    "<br>" .
    facturesNonPayeesPdfEscape($nameExercice) .
    "<br>" .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr></table>";

$htmlContent .= "<table><thead><tr>";
$htmlContent .= "<th>Date de Facture</th>";
$htmlContent .= "<th>Rubrique</th>";
$htmlContent .= "<th>Montant de facture</th>";
$htmlContent .= "<th>Reste du</th>";
$htmlContent .= "<th>Responsable</th>";
$htmlContent .= "<th>Fournisseur</th>";
$htmlContent .= "</tr></thead><tbody>";
if (count($depensesNonPayees) === 0) {
    $htmlContent .= '<tr><td colspan="6" class="empty">Aucune donnee disponible dans le tableau</td></tr>';
} else {
    foreach ($depensesNonPayees as $depense) {
        $htmlContent .= "<tr>";
        $htmlContent .= "<td>" . facturesNonPayeesPdfDate($depense["date"]) . "</td>";
        $htmlContent .= "<td>" . facturesNonPayeesPdfEscape(facturesNonPayeesPdfPosteName($depense["id_poste"], $connection)) . "</td>";
        $htmlContent .= '<td class="amount">' . number_format(floatval($depense["montant"]), 2, ",", " ") . "</td>";
        $htmlContent .= '<td class="amount">' . number_format(getDepenseResteDu($depense), 2, ",", " ") . "</td>";
        $htmlContent .= "<td>" . facturesNonPayeesPdfEscape(facturesNonPayeesPdfSyndicName($depense["id_syndic"], $connection)) . "</td>";
        $htmlContent .= "<td>" . facturesNonPayeesPdfEscape(facturesNonPayeesPdfFournisseurName($depense["id_fournisseur"], $connection)) . "</td>";
        $htmlContent .= "</tr>";
    }
}
$htmlContent .= "</tbody></table></body></html>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(facturesNonPayeesPdfFilename($nameExercice));
