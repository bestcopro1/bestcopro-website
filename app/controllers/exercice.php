<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/functions.php";

$connection = $GLOBALS["connection"];

function exerciceCanClose()
{
    return isset($_SESSION["id_usertype"]) && in_array($_SESSION["id_usertype"], ["1", "2", "3"], true);
}

function exerciceHistory($connection, $action)
{
    if (!isset($_SESSION["id"])) {
        return;
    }
    if ($stmt = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
        $date = date("Y-m-d H:i:s");
        $stmt->bind_param("sss", $date, $action, $_SESSION["id"]);
        $stmt->execute();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "cloturer") {
    if (!exerciceCanClose()) {
        echo "error|Vous n'avez pas le droit de cloturer cet exercice.";
        exit();
    }

    $idExercice = filter_input(INPUT_POST, "id_exercice", FILTER_SANITIZE_STRING);
    if ($idExercice === null || $idExercice === "") {
        echo "error|Exercice invalide.";
        exit();
    }

    $exercices = getExercice($idExercice, null, $connection);
    if (count($exercices) === 0) {
        echo "error|Exercice introuvable.";
        exit();
    }

    $exercice = $exercices[0];
    if (intval($exercice["cloture"] ?? 0) === 1) {
        echo "error|Cet exercice est deja cloture.";
        exit();
    }

    $dateDebut = date("Y-m-d", strtotime($exercice["dateFin"] . " + 1 day"));
    $dateFin = date("Y-m-d", strtotime($dateDebut . " + 1 year - 1 day"));

    $request = "SELECT id FROM exercice WHERE id_copropriete = ? AND dateDebut = ? LIMIT 1";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ss", $exercice["id_copropriete"], $dateDebut);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($existingId);
            $stmt->fetch();
            echo "error|L'exercice suivant existe deja.";
            exit();
        }
    }

    $connection->begin_transaction();
    $zero = 0;
    $request = "INSERT INTO exercice (dateDebut, dateFin, id_periodePaiement, id_repartitionFonct, id_repartitionInvest, montantFonct, montantInvest, id_copropriete) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param(
            "ssssssss",
            $dateDebut,
            $dateFin,
            $exercice["id_periodePaiement"],
            $exercice["id_repartitionFonct"],
            $exercice["id_repartitionInvest"],
            $zero,
            $zero,
            $exercice["id_copropriete"]
        );
        if (!$stmt->execute()) {
            $connection->rollback();
            echo "error|" . $connection->error;
            exit();
        }
        $newExerciceId = $connection->insert_id;
    } else {
        $connection->rollback();
        echo "error|" . $connection->error;
        exit();
    }

    $dateCloture = date("Y-m-d H:i:s");
    $idUser = $_SESSION["id"] ?? null;
    $request = "UPDATE exercice SET cloture = 1, dateCloture = ?, id_cloture_par = ? WHERE id = ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $dateCloture, $idUser, $idExercice);
        if (!$stmt->execute()) {
            $connection->rollback();
            echo "error|" . $connection->error;
            exit();
        }
    } else {
        $connection->rollback();
        echo "error|" . $connection->error;
        exit();
    }

    $connection->commit();

    exerciceHistory($connection, "a cloture|exercice|" . $idExercice);
    exerciceHistory($connection, "a ajoute|exercice|" . $newExerciceId);
    $_SESSION["id_exercice"] = $newExerciceId;

    echo "done|" . $newExerciceId;
    exit();
}

echo "error|Action invalide.";
exit();
