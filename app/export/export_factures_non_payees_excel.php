<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

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

function facturesNonPayeesExcelEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function facturesNonPayeesExcelDate($date)
{
    if ($date === null || $date === "" || $date === "0000-00-00") {
        return "";
    }

    return date("d/m/Y", strtotime($date));
}

function facturesNonPayeesExcelSyndicName($id_syndic, $connection)
{
    $syndic = getSyndic($id_syndic, null, $connection);
    if (count($syndic) === 0) {
        return "";
    }

    return trim(
        $syndic[0]["civilite"] . " " . $syndic[0]["prenom"] . " " . $syndic[0]["nom"]
    );
}

function facturesNonPayeesExcelPosteName($id_poste, $connection)
{
    $poste = getPoste($id_poste, null, null, $connection);
    return count($poste) > 0 ? $poste[0]["libelle"] : "";
}

function facturesNonPayeesExcelFournisseurName($id_fournisseur, $connection)
{
    $fournisseur = getFournisseur($id_fournisseur, $connection);
    return count($fournisseur) > 0 ? $fournisseur[0]["raisonSocial"] : "";
}

function facturesNonPayeesExcelFilename($nameExercice)
{
    $filename = strtolower("factures_non_payees_" . $nameExercice);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : "factures_non_payees") . ".xls";
}

$depenses = getDepense(null, $id_exercice, $connection);
$depensesNonPayees = array_filter($depenses, function ($depense) {
    return ($depense["situationPaiement"] ?? "paye") == "non_paye" && getDepenseResteDu($depense) > 0;
});
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=" .
        facturesNonPayeesExcelFilename($nameExercice)
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
    </style>
</head>
<body>
<table>
    <tr><td colspan="6" class="title">Factures non payees</td></tr>
    <tr><td colspan="6"><?= facturesNonPayeesExcelEscape($residenceName) ?></td></tr>
    <tr><td colspan="6"><?= facturesNonPayeesExcelEscape($nameExercice) ?></td></tr>
    <tr>
        <th>Date de Facture</th>
        <th>Rubrique</th>
        <th>Montant de facture</th>
        <th>Reste du</th>
        <th>Responsable</th>
        <th>Fournisseur</th>
    </tr>
    <?php foreach ($depensesNonPayees as $depense): ?>
    <tr>
        <td><?= facturesNonPayeesExcelDate($depense["date"]) ?></td>
        <td><?= facturesNonPayeesExcelEscape(facturesNonPayeesExcelPosteName($depense["id_poste"], $connection)) ?></td>
        <td class="amount"><?= number_format(floatval($depense["montant"]), 2, ",", " ") ?></td>
        <td class="amount"><?= number_format(getDepenseResteDu($depense), 2, ",", " ") ?></td>
        <td><?= facturesNonPayeesExcelEscape(facturesNonPayeesExcelSyndicName($depense["id_syndic"], $connection)) ?></td>
        <td><?= facturesNonPayeesExcelEscape(facturesNonPayeesExcelFournisseurName($depense["id_fournisseur"], $connection)) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
