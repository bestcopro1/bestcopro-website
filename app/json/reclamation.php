<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";

$token = input_value("token");
$lot = mobile_token_lot($connection, $token);

if (!$lot) {
    mobile_error("Session invalide ou copropriété inactive.");
}

function mobile_reclamation_image(string $id): string
{
    $preuves = glob(__DIR__ . "/../justificatifs/reclamations/" . $id . ".*");
    if (count($preuves) === 0) {
        return mobile_public_url("justificatifs/reclamations/no-image.jpg");
    }

    return mobile_public_url("justificatifs/reclamations/" . basename($preuves[0]));
}

function mobile_reclamation_list(mysqli $connection, array $lot): array
{
    $reclamations = getReclamation(null, $lot["id"], null, $connection);
    $data = [];

    foreach ($reclamations as $reclamation) {
        $statutReclamation = getStatutreclamation($reclamation["id_statutReclamation"], $connection);
        $messages = getMessages($reclamation["id"], $connection);
        $messagesData = [];

        foreach ($messages as $message) {
            if ($message["id_syndic"] !== null) {
                $source = "Syndic";
            } else {
                $proprietaires = getProprietaire($message["id_proprietaire"], null, $connection);
                $proprietaire = $proprietaires[0] ?? ["civilite" => "", "prenom" => "", "nom" => ""];
                $source = trim($proprietaire["civilite"] . " " . $proprietaire["prenom"] . " " . $proprietaire["nom"]);
            }

            $messagesData[] = [
                "id" => $message["id"],
                "source" => $source,
                "date" => date("d/m/Y", strtotime($message["date"])),
                "text" => trim(str_replace(["<br>", "<br />", "\n", "\r"], ["", "", " ", ""], $message["commentaire"])),
            ];
        }

        $data[] = [
            "id" => $reclamation["id"],
            "Object" => $reclamation["objet"],
            "image" => mobile_reclamation_image((string) $reclamation["id"]),
            "dateD" => date("d/m/Y", strtotime($reclamation["date"])),
            "statut" => $statutReclamation[0]["libelle"] ?? "",
            "Message" => $messagesData,
        ];
    }

    return $data;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $objet = input_value("object");
    $commentaire = input_value("message");

    if ($objet === "" || $commentaire === "") {
        mobile_error("Objet et message sont obligatoires.");
    }

    $date = date("Y-m-d H:i:s");
    $stmt = $connection->prepare("INSERT INTO reclamation (date, objet, id_lot) VALUES (?, ?, ?)");
    if (!$stmt) {
        mobile_error("Erreur de préparation de la réclamation.");
    }

    $stmt->bind_param("sss", $date, $objet, $lot["id"]);
    if (!$stmt->execute()) {
        mobile_error("Impossible de créer la réclamation.");
    }
    $idReclamation = (string) $connection->insert_id;
    $stmt->close();

    if ($stmt = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
        $action = "a ajouté|reclamation|" . $idReclamation;
        $stmt->bind_param("sss", $date, $action, $lot["id_proprietaire"]);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $connection->prepare("INSERT INTO message (date, id_proprietaire, commentaire, id_reclamation) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        mobile_error("Erreur de préparation du message.");
    }

    $stmt->bind_param("ssss", $date, $lot["id_proprietaire"], $commentaire, $idReclamation);
    if (!$stmt->execute()) {
        mobile_error("Impossible d'ajouter le message.");
    }
    $idMessage = (string) $connection->insert_id;
    $stmt->close();

    if ($stmt = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
        $action = "a ajouté|message|" . $idMessage;
        $stmt->bind_param("sss", $date, $action, $lot["id_proprietaire"]);
        $stmt->execute();
        $stmt->close();
    }

    $reponseAuto = "Bonjour, votre réclamation est bien enregistrée, elle sera traitée dans les plus brefs délais.";
    $idSyndic = "1";
    if ($stmt = $connection->prepare("INSERT INTO message (date, id_syndic, commentaire, id_reclamation) VALUES (?, ?, ?, ?)")) {
        $stmt->bind_param("ssss", $date, $idSyndic, $reponseAuto, $idReclamation);
        $stmt->execute();
        $stmt->close();
    }

    $description = "Une nouvelle réclamation a été enregistrée.";
    $nomPage = "reclamations";
    if ($stmt = $connection->prepare("INSERT INTO notificationsyndic (description, date, nomPage, idPage, id_copropriete) VALUES (?, ?, ?, ?, ?)")) {
        $stmt->bind_param("sssss", $description, $date, $nomPage, $idReclamation, $lot["id_copropriete"]);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_FILES["file"]) && is_uploaded_file($_FILES["file"]["tmp_name"])) {
        $allowed = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
        ];
        $mime = mime_content_type($_FILES["file"]["tmp_name"]);
        if (isset($allowed[$mime]) && (int) $_FILES["file"]["size"] <= 5 * 1024 * 1024) {
            $location = __DIR__ . "/../justificatifs/reclamations/" . $idReclamation . "." . $allowed[$mime];
            move_uploaded_file($_FILES["file"]["tmp_name"], $location);
        }
    }

    mobile_response(true, mobile_reclamation_list($connection, $lot), "Réclamation créée.");
}

if (input_value("update") === "yes") {
    $idReclamation = input_value("id");
    $commentaire = input_value("message");

    if ($idReclamation === "" || $commentaire === "") {
        mobile_error("Message invalide.");
    }

    $stmt = $connection->prepare("SELECT id FROM reclamation WHERE id = ? AND id_lot = ? LIMIT 1");
    if (!$stmt) {
        mobile_error("Erreur de préparation de la vérification.");
    }
    $stmt->bind_param("ss", $idReclamation, $lot["id"]);
    $stmt->execute();
    $stmt->store_result();
    $allowed = $stmt->num_rows > 0;
    $stmt->close();

    if (!$allowed) {
        mobile_error("Réclamation introuvable.");
    }

    $date = date("Y-m-d H:i:s");
    $stmt = $connection->prepare("INSERT INTO message (date, id_proprietaire, commentaire, id_reclamation) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        mobile_error("Erreur de préparation du message.");
    }
    $stmt->bind_param("ssss", $date, $lot["id_proprietaire"], $commentaire, $idReclamation);
    if (!$stmt->execute()) {
        mobile_error("Impossible d'ajouter le message.");
    }
    $idMessage = (string) $connection->insert_id;
    $stmt->close();

    if ($stmt = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
        $action = "a ajouté|message2|" . $idMessage;
        $stmt->bind_param("sss", $date, $action, $lot["id_proprietaire"]);
        $stmt->execute();
        $stmt->close();
    }

    mobile_response(true, mobile_reclamation_list($connection, $lot), "Message ajouté.");
}

mobile_response(true, mobile_reclamation_list($connection, $lot));
