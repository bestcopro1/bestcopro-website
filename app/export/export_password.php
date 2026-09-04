<?php
require_once __DIR__ . "/export_common.php";
bestcopro_export_bootstrap("password_csv");
$connection = $GLOBALS["connection"];

$idCopropriete = filter_input(INPUT_GET, "id_copropriete", FILTER_VALIDATE_INT);
if (!$idCopropriete && isset($_SESSION["id_copropriete"])) {
    $idCopropriete = filter_var(
        $_SESSION["id_copropriete"],
        FILTER_VALIDATE_INT,
    );
}

$idCopropriete = bestcopro_export_require_int($idCopropriete, "copropriete");
bestcopro_export_require_copropriete_access($connection, "lots", $idCopropriete);

$request =
    "SELECT p.prenom, p.nom, p.telephone, p.mobile, l.code, l.password " .
    "FROM lot l " .
    "INNER JOIN proprietaire p ON p.id = l.id_proprietaire " .
    "WHERE l.id_copropriete = ? " .
    "ORDER BY l.code ASC";
$stmt = $connection->prepare($request);
if (!$stmt) {
    throw new RuntimeException("Preparation export mots de passe impossible : " . $connection->error);
}
$stmt->bind_param("i", $idCopropriete);
if (!$stmt->execute()) {
    throw new RuntimeException("Export mots de passe impossible : " . $stmt->error);
}
$stmt->bind_result($prenom, $nom, $telephone, $mobile, $code, $password);

header("Content-Type: text/csv; charset=utf-8");
header(
    'Content-Disposition: attachment; filename="liste_des_mots_de_passe.csv"',
);
header("Cache-Control: no-store, no-cache, must-revalidate");

$output = fopen("php://output", "w");
fwrite($output, "\xEF\xBB\xBF");
fputcsv(
    $output,
    ["PRENOM", "NOM", "TELEPHONE", "MOBILE", "CODE", "MOT DE PASSE"],
    ";",
    '"',
    "\\",
);

while ($stmt->fetch()) {
    fputcsv(
        $output,
        [$prenom, $nom, $telephone, $mobile, $code, $password],
        ";",
        '"',
        "\\",
    );
}

$stmt->close();
fclose($output);
exit();
