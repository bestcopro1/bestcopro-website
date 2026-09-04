<?php
require_once __DIR__ . "/export_common.php";
bestcopro_export_bootstrap("reclamation_csv");
$connection = $GLOBALS["connection"];

$access = bestcopro_export_require_exercise_access(
    $connection,
    "reclamations",
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
    "SELECT r.date, CONCAT_WS(' ', p.civilite, p.prenom, p.nom), r.objet, sr.libelle " .
    "FROM reclamation r " .
    "INNER JOIN lot l ON l.id = r.id_lot " .
    "INNER JOIN proprietaire p ON p.id = l.id_proprietaire " .
    "INNER JOIN statutreclamation sr ON sr.id = r.id_statutReclamation " .
    "WHERE l.id_copropriete = ? AND CAST(r.date AS date) BETWEEN ? AND ? " .
    "ORDER BY r.date ASC, r.id ASC";
$stmt = $connection->prepare($request);
if (!$stmt) {
    throw new RuntimeException("Preparation export reclamations impossible : " . $connection->error);
}
$stmt->bind_param("iss", $idCopropriete, $exercice[0]["dateDebut"], $exercice[0]["dateFin"]);
if (!$stmt->execute()) {
    throw new RuntimeException("Export reclamations impossible : " . $stmt->error);
}
$stmt->bind_result($date, $proprietaire, $objet, $statut);

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="liste_des_reclamations.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");
$output = fopen("php://output", "w");
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ["DATE", "PROPRIETAIRE", "OBJET", "STATUT"], ";");
while ($stmt->fetch()) {
    fputcsv($output, [$date, $proprietaire, $objet, $statut], ";");
}
$stmt->close();
fclose($output);
exit();
