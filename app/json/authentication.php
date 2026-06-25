<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";

$username = input_value("username");
$password = input_value("password");

if ($username === "" || $password === "") {
    mobile_response(false, [
        "statut" => "OK",
        "token" => "",
        "message" => "Identifiant et/ou mot de passe incorrect!",
    ], "Identifiant et/ou mot de passe incorrect!");
}

$sql = "SELECT token FROM lot WHERE code = ? AND password = ? AND id_copropriete IN (SELECT id FROM copropriete WHERE display = 1) LIMIT 1";
$stmt = $connection->prepare($sql);

if (!$stmt) {
    mobile_error("Erreur de préparation de la requête.");
}

$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows <= 0) {
    $stmt->close();
    mobile_response(false, [
        "statut" => "OK",
        "token" => "",
        "message" => "Identifiant et/ou mot de passe incorrect!",
    ], "Identifiant et/ou mot de passe incorrect!");
}

$stmt->bind_result($token);
$stmt->fetch();
$stmt->close();

if (empty($token)) {
    mobile_response(false, [
        "statut" => "OK",
        "token" => "",
        "message" => "Identifiant et/ou mot de passe incorrect!",
    ], "Identifiant et/ou mot de passe incorrect!");
}

mobile_response(true, [
    "statut" => "OK",
    "token" => $token,
    "message" => "",
]);
