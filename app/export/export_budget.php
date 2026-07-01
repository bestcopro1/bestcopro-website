<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

use Dompdf\Dompdf;

function getBudgetExportRows($id_exercice, $id_typeRubrique, $connection)
{
    $request =
        "SELECT rubrique.id, rubrique.libelle, poste.id, poste.libelle, poste.montant " .
        "FROM rubrique INNER JOIN poste ON poste.id_rubrique = rubrique.id " .
        "WHERE rubrique.id_exercice = ? AND rubrique.id_typeRubrique = ? " .
        "AND COALESCE(poste.montant, 0) <> 0 " .
        "ORDER BY rubrique.id ASC, poste.id ASC";
    $rubriques = [];

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ss", $id_exercice, $id_typeRubrique);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result(
            $id_rubrique,
            $rubrique,
            $id_poste,
            $poste,
            $montant,
        );

        while ($stmt->fetch()) {
            if (!isset($rubriques[$id_rubrique])) {
                $rubriques[$id_rubrique] = [
                    "libelle" => $rubrique,
                    "postes" => [],
                    "total" => 0,
                ];
            }

            $montant = floatval($montant);
            $rubriques[$id_rubrique]["postes"][] = [
                "libelle" => $poste,
                "montant" => $montant,
            ];
            $rubriques[$id_rubrique]["total"] += $montant;
        }
    }

    return $rubriques;
}

$id_exercice = isset($_GET["id_exercice"]) ? $_GET["id_exercice"] : null;
$type = isset($_GET["type"]) ? $_GET["type"] : "fonctionnement";
$budgetTypes = [
    "fonctionnement" => [
        "id_typeRubrique" => "1",
        "title" => "Budget de fonctionnement",
        "budgetField" => "montantFonct",
    ],
    "investissement" => [
        "id_typeRubrique" => "2",
        "title" => "Budget d'investissement",
        "budgetField" => "montantInvest",
    ],
];

if ($id_exercice === null || !isset($budgetTypes[$type])) {
    http_response_code(400);
    exit("Parametres invalides");
}

$exercice = getExercice($id_exercice, null, $connection);
if (count($exercice) === 0) {
    http_response_code(404);
    exit("Exercice introuvable");
}

$budgetType = $budgetTypes[$type];
$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$rubriques = getBudgetExportRows(
    $id_exercice,
    $budgetType["id_typeRubrique"],
    $connection,
);
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$globalTotal = 0;

$htmlContent = "";
$htmlContent .=
    '<style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background: #c8c8c8; text-align: center; }
        .header td { border: 0; }
        .title { font-size: 16px; font-weight: bold; }
        .rubrique { background: #edf2f7; font-weight: bold; }
        .amount { text-align: right; white-space: nowrap; }
        .total { background: #00B0F0; font-weight: bold; }
    </style>';
$htmlContent .= '<table class="header">';
$htmlContent .= "<tr>";
$htmlContent .= '<td><strong>BEST COPRO</strong></td>';
$htmlContent .=
    '<td class="title" style="text-align:center;">' .
    htmlspecialchars($budgetType["title"], ENT_QUOTES, "UTF-8") .
    "</td>";
$htmlContent .=
    '<td style="text-align:right;">' .
    htmlspecialchars($residenceName, ENT_QUOTES, "UTF-8") .
    "<br>" .
    htmlspecialchars($nameExercice, ENT_QUOTES, "UTF-8") .
    "<br>" .
    date("d/m/Y") .
    "</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</table>";

$htmlContent .= '<p><strong>Budget annuel declare : </strong>' .
    number_format(floatval($exercice[0][$budgetType["budgetField"]]), 2) .
    " MAD</p>";

$htmlContent .= "<table>";
$htmlContent .= "<tr>";
$htmlContent .= "<th>Poste</th>";
$htmlContent .= "<th>Rubrique</th>";
$htmlContent .= "<th>Budget</th>";
$htmlContent .= "</tr>";

foreach ($rubriques as $rubrique) {
    $htmlContent .= '<tr class="rubrique">';
    $htmlContent .=
        '<td colspan="2">' .
        htmlspecialchars($rubrique["libelle"], ENT_QUOTES, "UTF-8") .
        "</td>";
    $htmlContent .=
        '<td class="amount">' . number_format($rubrique["total"], 2) . " MAD</td>";
    $htmlContent .= "</tr>";

    foreach ($rubrique["postes"] as $poste) {
        $htmlContent .= "<tr>";
        $htmlContent .= "<td></td>";
        $htmlContent .=
            "<td>" .
            htmlspecialchars($poste["libelle"], ENT_QUOTES, "UTF-8") .
            "</td>";
        $htmlContent .=
            '<td class="amount">' .
            number_format($poste["montant"], 2) .
            " MAD</td>";
        $htmlContent .= "</tr>";
    }

    $globalTotal += $rubrique["total"];
}

$htmlContent .= '<tr class="total">';
$htmlContent .= '<td colspan="2">TOTAL</td>';
$htmlContent .=
    '<td class="amount">' . number_format($globalTotal, 2) . " MAD</td>";
$htmlContent .= "</tr>";
$htmlContent .= "</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper("A4", "portrait");
$dompdf->render();
$dompdf->stream(
    "budget_" . $type . "_" . str_replace(" ", "_", $nameExercice) . ".pdf",
);
