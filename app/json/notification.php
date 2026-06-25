<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";

$token = input_value("token");
$lot = mobile_token_lot($connection, $token);

if (!$lot) {
    mobile_error("Session invalide ou copropriété inactive.");
}

$proprietaires = getProprietaire($lot["id_proprietaire"], null, $connection);
if (!$proprietaires) {
    mobile_error("Propriétaire introuvable.");
}

$proprietaire = $proprietaires[0];
$notifications = [
    [
        "date" => date("d/m/Y"),
        "text" => "Bienvenue dans l'application mobile BestCopro",
    ],
];

mobile_response(true, [
    "civilite" => $proprietaire["civilite"],
    "nom" => $proprietaire["nom"],
    "prenom" => $proprietaire["prenom"],
    "situation" => $notifications,
]);
