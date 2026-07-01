<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/suivi_cotisations_coproprietaires_data.php";

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

function suiviCotisationsExcelEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=" .
        getSuiviCotisationsCoproprietairesFilename(
            "suivi_cotisations_coproprietaires",
            $residenceName,
            $nameExercice,
            "xls"
        )
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
        .amount { text-align: right; }
        .total { background: #fff3e0; font-weight: bold; }
    </style>
</head>
<body>
<table>
    <tr><td colspan="6" class="title">Suivi des cotisations des coproprietaires</td></tr>
    <tr><td colspan="6"><?= suiviCotisationsExcelEscape($residenceName) ?></td></tr>
    <tr><td colspan="6"><?= suiviCotisationsExcelEscape($nameExercice) ?></td></tr>
    <tr>
        <th>NOM &amp; PRENOM des coproprietaires</th>
        <th>Ref de la propriete</th>
        <th>Solde au 01/01/<?= suiviCotisationsExcelEscape($annee) ?></th>
        <th>Montant annuel de la cotisation</th>
        <th>Versements de l'exercice</th>
        <th>Solde restant au 31/12/<?= suiviCotisationsExcelEscape($annee) ?></th>
    </tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= suiviCotisationsExcelEscape($row["nomComplet"]) ?></td>
        <td><?= suiviCotisationsExcelEscape($row["code"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($row["soldeAnterieur"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($row["baseCotisation"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($row["encaissement"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($row["soldeRestant"]) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total">
        <td colspan="2">TOTAL</td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($totals["soldeAnterieur"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($totals["baseCotisation"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($totals["encaissement"]) ?></td>
        <td class="amount"><?= formatSuiviCotisationsCoproprietairesAmount($totals["soldeRestant"]) ?></td>
    </tr>
</table>
</body>
</html>
