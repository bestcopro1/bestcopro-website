<?php
require_once __DIR__ . "/../config/session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=liste_des_mots_de_passe.csv");

$output = fopen("php://output", "w");
fputcsv(
    $output,
    ["PRENOM", "NOM", "TELEPHONE", "MOBILE", "CODE", "MOT DE PASSE"],
    ";",
);

$rows = $connection->query(
    "SELECT prenom,nom,telephone,mobile,code,password FROM proprietaire,lot WHERE proprietaire.id = id_proprietaire AND id_copropriete = " .
        $_SESSION["id_copropriete"],
    MYSQLI_USE_RESULT,
);
while ($row = $rows->fetch_row()) {
    fputcsv($output, $row, ";");
}
