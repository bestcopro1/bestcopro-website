<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
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

function etatFacturesPdfEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function etatFacturesPdfDate($date)
{
    if ($date === null || $date === "" || $date === "0000-00-00") {
        return "";
    }

    return date("d/m/Y", strtotime($date));
}

function etatFacturesPdfSyndicName($id_syndic, $connection)
{
    $syndic = getSyndic($id_syndic, null, $connection);
    if (count($syndic) === 0) {
        return "";
    }

    return trim(
        $syndic[0]["civilite"] . " " . $syndic[0]["prenom"] . " " . $syndic[0]["nom"]
    );
}

function etatFacturesPdfPosteName($id_poste, $connection)
{
    $poste = getPoste($id_poste, null, null, $connection);
    return count($poste) > 0 ? $poste[0]["libelle"] : "";
}

function etatFacturesPdfFournisseurName($id_fournisseur, $connection)
{
    $fournisseur = getFournisseur($id_fournisseur, $connection);
    return count($fournisseur) > 0 ? $fournisseur[0]["raisonSocial"] : "";
}

function etatFacturesPdfModeName($id_modePaiement, $connection)
{
    if ($id_modePaiement === null || $id_modePaiement === "") {
        return "";
    }

    $modepaiement = getModepaiement($id_modePaiement, $connection);
    return count($modepaiement) > 0 ? $modepaiement[0]["libelle"] : "";
}

function renderEtatFacturesPdfEmptyRow($colspan)
{
    return '<tr><td colspan="' .
        $colspan .
        '" class="empty">Aucune donnée disponible dans le tableau</td></tr>';
}

$depenses = getDepense(null, $id_exercice, $connection);
$depensesPayees = array_filter($depenses, function ($depense) {
    return ($depense["situationPaiement"] ?? "paye") == "paye" || getDepenseResteDu($depense) <= 0;
});
$depensesNonPayees = array_filter($depenses, function ($depense) {
    return ($depense["situationPaiement"] ?? "paye") == "non_paye" && getDepenseResteDu($depense) > 0;
});
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";

$htmlContent = "";
$htmlContent .=
    '<html><head><meta charset="UTF-8"><style>
        @page { margin: 18px 14px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 8px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #000; padding: 3px; }
        th { background: #c8c8c8; text-align: center; }
        .header td { border: 0; font-size: 10px; }
        .title { font-size: 14px; font-weight: bold; text-align: center; }
        .section { background: #edf2f7; font-weight: bold; font-size: 10px; }
        .amount { text-align: right; white-space: nowrap; }
        .empty { text-align: center; color: #555; }
    </style></head><body>';

$htmlContent .= '<table class="header">';
$htmlContent .= "<tr>";
$htmlContent .= "<td><strong>BEST COPRO</strong></td>";
$htmlContent .= '<td class="title">Etat des factures</td>';
$htmlContent .=
    '<td style="text-align:right;">' .
    etatFacturesPdfEscape($residenceName) .
    "<br>" .
    etatFacturesPdfEscape($nameExercice) .
    "<br>" .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</table>";

$htmlContent .= "<table>";
$htmlContent .= '<tr><td colspan="9" class="section">Factures payees</td></tr>';
$htmlContent .=
    "<tr>
        <th>Date de Facture</th>
        <th>Rubrique</th>
        <th>Montant de facture</th>
        <th>Date de paiement</th>
        <th>Montant payé</th>
        <th>Reste du</th>
        <th>Mode de paiement</th>
        <th>Fournisseur</th>
        <th>Responsable</th>
    </tr>";

if (count($depensesPayees) > 0) {
    foreach ($depensesPayees as $depense) {
        $htmlContent .= "<tr>";
        $htmlContent .= "<td>" . etatFacturesPdfDate($depense["date"]) . "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfPosteName($depense["id_poste"], $connection)
            ) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            number_format(floatval($depense["montant"]), 2, ",", " ") .
            "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfDate($depense["datePaiement"] ?: $depense["date"]) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            number_format(
                getDepenseMontantPaye($depense),
                2,
                ",",
                " "
            ) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            number_format(
                getDepenseResteDu($depense),
                2,
                ",",
                " "
            ) .
            "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfModeName($depense["id_modePaiement"], $connection)
            ) .
            "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfFournisseurName(
                    $depense["id_fournisseur"],
                    $connection
                )
            ) .
            "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfSyndicName($depense["id_syndic"], $connection)
            ) .
            "</td>";
        $htmlContent .= "</tr>";
    }
} else {
    $htmlContent .= renderEtatFacturesPdfEmptyRow(9);
}
$htmlContent .= "</table>";

$htmlContent .= "<table>";
$htmlContent .= '<tr><td colspan="6" class="section">Factures non payees</td></tr>';
$htmlContent .=
    "<tr>
        <th>Date de Facture</th>
        <th>Rubrique</th>
        <th>Montant de facture</th>
        <th>Reste du</th>
        <th>Responsable</th>
        <th>Fournisseur</th>
    </tr>";

if (count($depensesNonPayees) > 0) {
    foreach ($depensesNonPayees as $depense) {
        $htmlContent .= "<tr>";
        $htmlContent .= "<td>" . etatFacturesPdfDate($depense["date"]) . "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfPosteName($depense["id_poste"], $connection)
            ) .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            number_format(floatval($depense["montant"]), 2, ",", " ") .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            number_format(getDepenseResteDu($depense), 2, ",", " ") .
            "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfSyndicName($depense["id_syndic"], $connection)
            ) .
            "</td>";
        $htmlContent .=
            "<td>" .
            etatFacturesPdfEscape(
                etatFacturesPdfFournisseurName(
                    $depense["id_fournisseur"],
                    $connection
                )
            ) .
            "</td>";
        $htmlContent .= "</tr>";
    }
} else {
    $htmlContent .= renderEtatFacturesPdfEmptyRow(6);
}
$htmlContent .= "</table>";
$htmlContent .= "</body></html>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    "etat_factures_" . str_replace(" ", "_", $nameExercice) . ".pdf"
);
