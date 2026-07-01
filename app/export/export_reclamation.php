<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=liste_des_reclamations.csv");

$output = fopen("php://output", "w");
fputcsv($output, ["DATE", "PROPRIETAIRE", "OBJET", "STATUT"], ";");

$exercice = getExercice($_GET["id_exercice"], null, $connection);

$request =
    'SELECT reclamation.date, concat(proprietaire.civilite, " ", proprietaire.prenom, " ", proprietaire.nom) as nom_proprietaire, reclamation.objet, statutreclamation.libelle FROM reclamation, proprietaire, lot, statutreclamation WHERE reclamation.id_lot = lot.id AND lot.id_proprietaire = proprietaire.id AND reclamation.id_statutReclamation = statutreclamation.id AND lot.id_copropriete = ' .
    $_GET["id_copropriete"] .
    ' AND reclamation.date BETWEEN "' .
    $exercice[0]["dateDebut"] .
    '" AND "' .
    $exercice[0]["dateFin"] .
    '"';

$rows = $connection->query($request, MYSQLI_USE_RESULT);
while ($row = $rows->fetch_row()) {
    fputcsv($output, $row, ";");
}
