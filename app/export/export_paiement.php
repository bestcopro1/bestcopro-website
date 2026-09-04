<?php
require_once __DIR__ . "/export_common.php";
bestcopro_export_bootstrap("paiement_csv");
$connection = $GLOBALS["connection"];

$access = bestcopro_export_require_exercise_access(
    $connection,
    "paiements",
    isset($_GET["id_exercice"]) ? $_GET["id_exercice"] : null
);
$idCopropriete = bestcopro_export_require_int(
    isset($_GET["id_copropriete"]) ? $_GET["id_copropriete"] : null,
    "copropriete"
);
bestcopro_export_assert_same_copropriete($access["id_copropriete"], $idCopropriete);

$exercice = getExercice($access["id_exercice"], null, $connection);
if (count($exercice) === 0) {
    bestcopro_export_fail(404, "Exercice introuvable.");
}

$request =
    "SELECT p.date, l.code, CONCAT_WS(' ', pr.civilite, pr.prenom, pr.nom), " .
    "p.montant, mp.libelle, p.commentaire, " .
    "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', s.civilite, s.prenom, s.nom)), ''), 'Le Systeme') " .
    "FROM paiement p " .
    "INNER JOIN modepaiement mp ON mp.id = p.id_modePaiement " .
    "INNER JOIN lot l ON l.id = p.id_lot " .
    "INNER JOIN proprietaire pr ON pr.id = l.id_proprietaire " .
    "LEFT JOIN syndic s ON s.id = p.id_syndic " .
    "WHERE l.id_copropriete = ? AND CAST(p.date AS date) BETWEEN ? AND ? " .
    "ORDER BY p.date ASC, p.id ASC";
$stmt = $connection->prepare($request);
if (!$stmt) {
    throw new RuntimeException("Preparation export paiements impossible : " . $connection->error);
}
$stmt->bind_param("iss", $idCopropriete, $exercice[0]["dateDebut"], $exercice[0]["dateFin"]);
if (!$stmt->execute()) {
    throw new RuntimeException("Export paiements impossible : " . $stmt->error);
}
$stmt->bind_result($date, $code, $proprietaire, $montant, $mode, $commentaire, $responsable);

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="liste_des_paiements.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");
$output = fopen("php://output", "w");
fwrite($output, "\xEF\xBB\xBF");
fputcsv(
    $output,
    ["DATE", "CODE", "PROPRIETAIRE", "MONTANT", "MODE DE PAIEMENT", "COMMENTAIRE", "RESPONSABLE"],
    ";"
);
while ($stmt->fetch()) {
    fputcsv($output, [$date, $code, $proprietaire, $montant, $mode, $commentaire, $responsable], ";");
}
$stmt->close();
fclose($output);
exit();
