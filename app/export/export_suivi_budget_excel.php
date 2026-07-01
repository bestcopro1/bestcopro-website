<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/suivi_budget_data.php";
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

function suiviBudgetExcelEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$rubriques = getSuiviBudgetRows($id_exercice, $connection);
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$globalTotals = getEmptySuiviBudgetTotals();

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=suivi_budget_fonctionnement_" .
        str_replace(" ", "_", $nameExercice) .
        ".xls"
);
echo "\xEF\xBB\xBF";
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #c8c8c8; font-weight: bold; text-align: center; }
        .title { font-weight: bold; font-size: 16px; }
        .amount, .percent { text-align: right; }
        .rubrique-total { background: #edf2f7; font-weight: bold; }
        .global-total { background: #00B0F0; font-weight: bold; }
    </style>
</head>
<body>
<table>
    <tr>
        <td colspan="6" class="title">Suivi budget de fonctionnement</td>
    </tr>
    <tr>
        <td colspan="6"><?= suiviBudgetExcelEscape($residenceName) ?></td>
    </tr>
    <tr>
        <td colspan="6"><?= suiviBudgetExcelEscape($nameExercice) ?></td>
    </tr>
    <tr><td colspan="6"></td></tr>
    <tr>
        <th rowspan="2">Poste</th>
        <th rowspan="2">Rubrique</th>
        <th rowspan="2">Montant budget</th>
        <th rowspan="2">Consommation</th>
        <th colspan="2">Suivi budget annuel</th>
    </tr>
    <tr>
        <th>Montant restant</th>
        <th>% restant</th>
    </tr>
    <?php foreach ($rubriques as $rubrique):
        $rubriqueTotals = $rubrique["totals"];
        addSuiviBudgetTotals($globalTotals, $rubriqueTotals);
        foreach ($rubrique["postes"] as $index => $poste): ?>
    <tr>
        <td><?= $index === 0 ? suiviBudgetExcelEscape($rubrique["libelle"]) : "" ?></td>
        <td><?= suiviBudgetExcelEscape($poste["poste"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($poste["budget"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($poste["cout"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($poste["annuelRestant"]) ?></td>
        <td class="percent"><?= formatSuiviBudgetPercent($poste["annuelPourcentageRestant"]) ?></td>
    </tr>
        <?php endforeach; ?>
    <tr class="rubrique-total">
        <td colspan="2">TOTAL <?= suiviBudgetExcelEscape($rubrique["libelle"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($rubriqueTotals["budget"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($rubriqueTotals["cout"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($rubriqueTotals["annuelRestant"]) ?></td>
        <td class="percent"><?= formatSuiviBudgetPercent(getSuiviBudgetPercent($rubriqueTotals["annuelRestant"], $rubriqueTotals["budget"])) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="global-total">
        <td colspan="2">TOTAL GENERAL</td>
        <td class="amount"><?= formatSuiviBudgetAmount($globalTotals["budget"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($globalTotals["cout"]) ?></td>
        <td class="amount"><?= formatSuiviBudgetAmount($globalTotals["annuelRestant"]) ?></td>
        <td class="percent"><?= formatSuiviBudgetPercent(getSuiviBudgetPercent($globalTotals["annuelRestant"], $globalTotals["budget"])) ?></td>
    </tr>
</table>
</body>
</html>
