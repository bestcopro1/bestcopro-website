<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";

$token = input_value("token");
$lot = mobile_token_lot($connection, $token);

if (!$lot) {
    mobile_error("Session invalide ou copropriété inactive.");
}

$telephone = input_value("Telephone");
$email = input_value("Email");
$adresse = input_value("Adresse");

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    mobile_error("Email invalide.");
}

if ($telephone === "" || $adresse === "") {
    mobile_error("Téléphone et adresse sont obligatoires.");
}

$request = "UPDATE proprietaire SET email = ?, telephone = ?, adresse = ? WHERE id = ?";
$stmt = $connection->prepare($request);
if (!$stmt) {
    mobile_error("Erreur de préparation de la mise à jour.");
}

$stmt->bind_param("ssss", $email, $telephone, $adresse, $lot["id_proprietaire"]);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    mobile_error("Impossible de mettre à jour le profil.");
}

mobile_response(true, ["statut" => "OK"], "Profil mis à jour.");
