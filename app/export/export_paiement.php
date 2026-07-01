<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=liste_des_paiements.csv");

$output = fopen("php://output", "w");
fputcsv(
    $output,
    [
        "DATE",
        "CODE",
        "PROPRIETAIRE",
        "MONTANT",
        "MODE DE PAIEMENT",
        "COMMENTAIRE",
        "RESPONSABLE",
    ],
    ";",
);

$exercice = getExercice($_GET["id_exercice"], null, $connection);

$request =
    'SELECT paiement.date, lot.code, concat(proprietaire.civilite, " ", proprietaire.prenom, " ", proprietaire.nom) as nom_proprietaire, paiement.montant, modepaiement.libelle, paiement.commentaire, concat(syndic.civilite, " ", syndic.prenom, " ", syndic.nom) as nom_syndic FROM paiement, modepaiement, lot, proprietaire, syndic WHERE paiement.id_modePaiement = modepaiement.id AND paiement.id_lot = lot.id AND lot.id_proprietaire = proprietaire.id AND paiement.id_syndic = syndic.id AND lot.id_copropriete = ' .
    $_GET["id_copropriete"] .
    ' AND paiement.date BETWEEN "' .
    $exercice[0]["dateDebut"] .
    '" AND "' .
    $exercice[0]["dateFin"] .
    '"';

$rows = $connection->query($request, MYSQLI_USE_RESULT);
while ($row = $rows->fetch_row()) {
    fputcsv($output, $row, ";");
}
