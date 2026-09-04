<?php
$sessionFile = __DIR__ . "/../session.php";
if (is_file($sessionFile)) {
    require_once $sessionFile;
    bestcopro_start_session();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    !isset($_SESSION["loggedin"], $_SESSION["id"], $_SESSION["id_usertype"]) ||
    $_SESSION["loggedin"] !== "ImIn"
) {
    header("Location: ../login.php");
    exit();
}

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

$idCopropriete = filter_input(INPUT_GET, "id_copropriete", FILTER_VALIDATE_INT);
if (!$idCopropriete && isset($_SESSION["id_copropriete"])) {
    $idCopropriete = filter_var(
        $_SESSION["id_copropriete"],
        FILTER_VALIDATE_INT,
    );
}

if (!$idCopropriete || !hadAccess("lots", $_SESSION["id_usertype"])) {
    http_response_code(403);
    exit("Export non autorisé.");
}

if (in_array((string) $_SESSION["id_usertype"], ["3", "4"], true)) {
    $coproprietesAutorisees = getRel_copropriete_syndic(
        $_SESSION["id"],
        $connection,
    );
    if (
        !in_array(
            (string) $idCopropriete,
            array_map("strval", $coproprietesAutorisees),
            true,
        )
    ) {
        http_response_code(403);
        exit("Vous n'avez pas accès à cette copropriété.");
    }
}

$request =
    "SELECT p.prenom, p.nom, p.telephone, p.mobile, l.code, l.password " .
    "FROM lot l " .
    "INNER JOIN proprietaire p ON p.id = l.id_proprietaire " .
    "WHERE l.id_copropriete = ? " .
    "ORDER BY l.code ASC";
$stmt = $connection->prepare($request);
if (!$stmt) {
    http_response_code(500);
    exit("Impossible de préparer l'export.");
}
$stmt->bind_param("i", $idCopropriete);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("Impossible de générer l'export.");
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
