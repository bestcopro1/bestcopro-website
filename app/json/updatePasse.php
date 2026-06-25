<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";

$token = input_value("token");
$oldPassword = input_value("anMoPasse");
$newPassword = input_value("nouMoPasse");
$confirmPassword = input_value("conMoPasse");
$lot = mobile_token_lot($connection, $token);

if (!$lot) {
    mobile_error("Session invalide ou copropriété inactive.");
}

if ($oldPassword === "" || $newPassword === "" || $confirmPassword === "") {
    mobile_error("Tous les champs sont obligatoires.");
}

if ($newPassword !== $confirmPassword) {
    mobile_error("La confirmation ne correspond pas au nouveau mot de passe.");
}

if ($lot["password"] !== $oldPassword) {
    mobile_error("L'ancien mot de passe saisi est incorrect.");
}

$stmt = $connection->prepare("UPDATE lot SET password = ? WHERE id = ?");
if (!$stmt) {
    mobile_error("Erreur de préparation de la mise à jour.");
}

$stmt->bind_param("ss", $newPassword, $lot["id"]);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    mobile_error("Impossible de modifier le mot de passe.");
}

mobile_response(true, ["statut" => "OK"], "Mot de passe modifié.");
