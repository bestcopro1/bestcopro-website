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

function etatFacturesExcelEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function etatFacturesDate($date)
{
    if ($date === null || $date === "" || $date === "0000-00-00") {
        return "";
    }

    return date("d/m/Y", strtotime($date));
}

function etatFacturesSyndicName($id_syndic, $connection)
{
    $syndic = getSyndic($id_syndic, null, $connection);
    if (count($syndic) === 0) {
        return "";
    }

    return trim(
        $syndic[0]["civilite"] . " " . $syndic[0]["prenom"] . " " . $syndic[0]["nom"]
    );
}

function etatFacturesPosteName($id_poste, $connection)
{
    $poste = getPoste($id_poste, null, null, $connection);
    return count($poste) > 0 ? $poste[0]["libelle"] : "";
}

function etatFacturesFournisseurName($id_fournisseur, $connection)
{
    $fournisseur = getFournisseur($id_fournisseur, $connection);
    return count($fournisseur) > 0 ? $fournisseur[0]["raisonSocial"] : "";
}

function etatFacturesModeName($id_modePaiement, $connection)
{
    if ($id_modePaiement === null || $id_modePaiement === "") {
        return "";
    }

    $modepaiement = getModepaiement($id_modePaiement, $connection);
    return count($modepaiement) > 0 ? $modepaiement[0]["libelle"] : "";
}

$depenses = getDepense(null, $id_exercice, $connection);
$depensesPayees = array_filter($depenses, function ($depense) {
    return ($depense["situationPaiement"] ?? "paye") == "paye";
});
$depensesNonPayees = array_filter($depenses, function ($depense) {
    return ($depense["situationPaiement"] ?? "paye") == "non_paye";
});
$nameExercice = getNameexercice($exercice[0]["dateDebut"]);
$copropriete = getCopropriete($exercice[0]["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=etat_factures_" .
        str_replace(" ", "_", $nameExercice) .
        ".xls"
);
echo "\xEF\xBB\xBF";
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #c8c8c8; font-weight: bold; text-align: center; }
        .title { font-weight: bold; font-size: 16px; }
        .amount { text-align: right; }
        .section { background: #edf2f7; font-weight: bold; }
    </style>
</head>
<body>
<table>
    <tr><td colspan="8" class="title">Etat des factures</td></tr>
    <tr><td colspan="8"><?= etatFacturesExcelEscape($residenceName) ?></td></tr>
    <tr><td colspan="8"><?= etatFacturesExcelEscape($nameExercice) ?></td></tr>
</table>

<table>
    <tr><td colspan="8" class="section">Factures payees</td></tr>
    <tr>
        <th>Date de Facture</th>
        <th>Poste</th>
        <th>Montant de facture</th>
        <th>Date de paiement</th>
        <th>Montant paye</th>
        <th>Mode de paiement</th>
        <th>Fournisseur</th>
        <th>Responsable</th>
    </tr>
    <?php foreach ($depensesPayees as $depense): ?>
    <tr>
        <td><?= etatFacturesDate($depense["date"]) ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesPosteName($depense["id_poste"], $connection)) ?></td>
        <td class="amount"><?= number_format(floatval($depense["montant"]), 2, ",", " ") ?></td>
        <td><?= etatFacturesDate($depense["datePaiement"] ?: $depense["date"]) ?></td>
        <td class="amount"><?= number_format(floatval($depense["montantPaye"] ?: $depense["montant"]), 2, ",", " ") ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesModeName($depense["id_modePaiement"], $connection)) ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesFournisseurName($depense["id_fournisseur"], $connection)) ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesSyndicName($depense["id_syndic"], $connection)) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<table>
    <tr><td colspan="5" class="section">Factures non payees</td></tr>
    <tr>
        <th>Date de Facture</th>
        <th>Poste</th>
        <th>Montant de facture</th>
        <th>Responsable</th>
        <th>Fournisseur</th>
    </tr>
    <?php foreach ($depensesNonPayees as $depense): ?>
    <tr>
        <td><?= etatFacturesDate($depense["date"]) ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesPosteName($depense["id_poste"], $connection)) ?></td>
        <td class="amount"><?= number_format(floatval($depense["montant"]), 2, ",", " ") ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesSyndicName($depense["id_syndic"], $connection)) ?></td>
        <td><?= etatFacturesExcelEscape(etatFacturesFournisseurName($depense["id_fournisseur"], $connection)) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
