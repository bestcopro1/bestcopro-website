<?php

require_once __DIR__ . "/../session.php";
bestcopro_start_session();

bestcopro_export_bootstrap("initialisation");

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

function bestcopro_export_log($message, $context = [])
{
    $name = isset($GLOBALS["bestcopro_export_name"])
        ? $GLOBALS["bestcopro_export_name"]
        : basename(isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : "export");
    $details = "";
    if (!empty($context)) {
        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $details = $encoded !== false ? " " . $encoded : "";
    }
    error_log("[BestCopro export:" . $name . "] " . $message . $details);
}

function bestcopro_export_fail($status, $message, $logMessage = null, $context = [])
{
    if ($logMessage === null && (int) $status >= 400) {
        $logMessage = "Echec HTTP " . (int) $status . ": " . (string) $message;
        $context += [
            "user" => isset($_SESSION["id"]) ? (int) $_SESSION["id"] : null,
            "script" => isset($_SERVER["SCRIPT_NAME"]) ? basename($_SERVER["SCRIPT_NAME"]) : null,
        ];
    }
    if ($logMessage !== null && $logMessage !== "") {
        bestcopro_export_log($logMessage, $context);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code((int) $status);
    if (!headers_sent()) {
        header("Content-Type: text/html; charset=UTF-8");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("X-Content-Type-Options: nosniff");
    }

    echo "<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><title>Export impossible</title></head>";
    echo "<body><h1>Export impossible</h1><p>" .
        htmlspecialchars((string) $message, ENT_QUOTES, "UTF-8") .
        "</p></body></html>";
    exit();
}

function bestcopro_export_bootstrap($name)
{
    static $installed = false;
    $GLOBALS["bestcopro_export_name"] = (string) $name;

    if ($installed) {
        return;
    }
    $installed = true;

    set_exception_handler(function ($exception) {
        bestcopro_export_fail(
            500,
            "Une erreur est survenue pendant la génération du document.",
            get_class($exception) . ": " . $exception->getMessage(),
            ["file" => $exception->getFile(), "line" => $exception->getLine()]
        );
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if (!$error || !in_array($error["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        bestcopro_export_fail(
            500,
            "Une erreur est survenue pendant la génération du document.",
            $error["message"],
            ["file" => $error["file"], "line" => $error["line"]]
        );
    });
}

function bestcopro_export_require_int($value, $label)
{
    $validated = filter_var($value, FILTER_VALIDATE_INT);
    if ($validated === false || $validated <= 0) {
        bestcopro_export_fail(400, "Paramètre invalide : " . $label . ".");
    }

    return (int) $validated;
}

function bestcopro_export_require_authenticated_user()
{
    $valid =
        isset($_SESSION["loggedin"], $_SESSION["id"], $_SESSION["id_usertype"]) &&
        $_SESSION["loggedin"] === "ImIn" &&
        (int) $_SESSION["id"] > 0;

    if (!$valid) {
        bestcopro_export_fail(401, "Votre session a expiré. Veuillez vous reconnecter.");
    }

    return [
        "id" => (int) $_SESSION["id"],
        "role" => (string) $_SESSION["id_usertype"],
    ];
}

function bestcopro_export_require_copropriete_access($connection, $section, $idCopropriete)
{
    $user = bestcopro_export_require_authenticated_user();
    $idCopropriete = bestcopro_export_require_int($idCopropriete, "copropriété");

    if (!hadAccess($section . ".view", $user["role"])) {
        bestcopro_export_fail(403, "Vous n'avez pas le droit de consulter cet export.");
    }

    $stmt = $connection->prepare("SELECT id FROM copropriete WHERE id = ? AND display = 1 LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Validation copropriété impossible : " . $connection->error);
    }
    $stmt->bind_param("i", $idCopropriete);
    if (!$stmt->execute()) {
        throw new RuntimeException("Validation copropriété impossible : " . $stmt->error);
    }
    $stmt->store_result();
    $exists = $stmt->num_rows === 1;
    $stmt->close();
    if (!$exists) {
        bestcopro_export_fail(404, "Copropriété introuvable.");
    }

    if (!bestcopro_is_access_admin($user["role"])) {
        $stmt = $connection->prepare(
            "SELECT 1 FROM rel_copropriete_syndic WHERE id_syndic = ? AND id_copropriete = ? LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Validation des accès impossible : " . $connection->error);
        }
        $stmt->bind_param("ii", $user["id"], $idCopropriete);
        if (!$stmt->execute()) {
            throw new RuntimeException("Validation des accès impossible : " . $stmt->error);
        }
        $stmt->store_result();
        $allowed = $stmt->num_rows === 1;
        $stmt->close();
        if (!$allowed) {
            bestcopro_export_fail(403, "Vous n'avez pas accès à cette copropriété.");
        }
    }

    return $idCopropriete;
}

function bestcopro_export_require_exercise_access($connection, $section, $idExercice)
{
    $idExercice = bestcopro_export_require_int($idExercice, "exercice");
    $stmt = $connection->prepare("SELECT id_copropriete FROM exercice WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Validation exercice impossible : " . $connection->error);
    }
    $stmt->bind_param("i", $idExercice);
    if (!$stmt->execute()) {
        throw new RuntimeException("Validation exercice impossible : " . $stmt->error);
    }
    $stmt->bind_result($idCopropriete);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) {
        bestcopro_export_fail(404, "Exercice introuvable.");
    }

    bestcopro_export_require_copropriete_access($connection, $section, $idCopropriete);
    return ["id_exercice" => $idExercice, "id_copropriete" => (int) $idCopropriete];
}

function bestcopro_export_require_lot_access($connection, $section, $idLot)
{
    $idLot = bestcopro_export_require_int($idLot, "lot");
    $stmt = $connection->prepare("SELECT id_copropriete FROM lot WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Validation lot impossible : " . $connection->error);
    }
    $stmt->bind_param("i", $idLot);
    if (!$stmt->execute()) {
        throw new RuntimeException("Validation lot impossible : " . $stmt->error);
    }
    $stmt->bind_result($idCopropriete);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) {
        bestcopro_export_fail(404, "Lot introuvable.");
    }

    bestcopro_export_require_copropriete_access($connection, $section, $idCopropriete);
    return ["id_lot" => $idLot, "id_copropriete" => (int) $idCopropriete];
}

function bestcopro_export_require_payment_access($connection, $section, $idPaiement)
{
    $idPaiement = bestcopro_export_require_int($idPaiement, "paiement");
    $stmt = $connection->prepare(
        "SELECT l.id_copropriete FROM paiement p INNER JOIN lot l ON l.id = p.id_lot WHERE p.id = ? LIMIT 1"
    );
    if (!$stmt) {
        throw new RuntimeException("Validation paiement impossible : " . $connection->error);
    }
    $stmt->bind_param("i", $idPaiement);
    if (!$stmt->execute()) {
        throw new RuntimeException("Validation paiement impossible : " . $stmt->error);
    }
    $stmt->bind_result($idCopropriete);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) {
        bestcopro_export_fail(404, "Paiement introuvable.");
    }

    bestcopro_export_require_copropriete_access($connection, $section, $idCopropriete);
    return ["id_paiement" => $idPaiement, "id_copropriete" => (int) $idCopropriete];
}

function bestcopro_export_assert_same_copropriete($expected, $actual)
{
    if ((int) $expected !== (int) $actual) {
        bestcopro_export_fail(400, "Les paramètres de l'export ne correspondent pas à la même copropriété.");
    }
}

function bestcopro_export_safe_filename($filename, $fallback = "export.pdf")
{
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", (string) $filename);
    $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename !== false ? $filename : "");
    $filename = trim((string) $filename, "._-");
    return $filename !== "" ? $filename : $fallback;
}

function bestcopro_export_create_dompdf()
{
    $runtimeDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "bestcopro_dompdf";
    if (!is_dir($runtimeDir) && !mkdir($runtimeDir, 0770, true) && !is_dir($runtimeDir)) {
        throw new RuntimeException("Creation du dossier temporaire Dompdf impossible.");
    }

    $options = new \Dompdf\Options();
    $options->setTempDir($runtimeDir);
    $options->setFontCache($runtimeDir);
    return new \Dompdf\Dompdf($options);
}
