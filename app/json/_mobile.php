<?php
declare(strict_types=1);

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

$connection = $GLOBALS["connection"];

if ($connection instanceof mysqli) {
    mysqli_set_charset($connection, "utf8mb4");
    mysqli_query($connection, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}

if (!headers_sent()) {
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
}

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

function input_value(string $key, $default = "")
{
    if (isset($_POST[$key])) {
        return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
    }

    if (isset($_GET[$key])) {
        return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
    }

    return $default;
}

function mobile_response(bool $success, $data = null, string $message = "", int $status = 200): void
{
    http_response_code($status);
    echo json_encode(
        [
            "success" => $success,
            "statut" => $success ? "OK" : "NOTOK",
            "data" => $data,
            "message" => $message,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function mobile_error(string $message = "Erreur", int $status = 200): void
{
    mobile_response(false, null, $message, $status);
}

function mobile_token_lot(mysqli $connection, string $token): ?array
{
    if ($token === "") {
        return null;
    }

    $sql = "SELECT id, id_copropriete, id_proprietaire, password, token, code, numero, tantieme, foncier FROM lot WHERE token = ? AND id_copropriete IN (SELECT id FROM copropriete WHERE display = 1) LIMIT 1";
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows <= 0) {
        $stmt->close();
        return null;
    }

    $stmt->bind_result($id, $id_copropriete, $id_proprietaire, $password, $dbToken, $code, $numero, $tantieme, $foncier);
    $stmt->fetch();
    $stmt->close();

    return [
        "id" => $id,
        "id_copropriete" => $id_copropriete,
        "id_proprietaire" => $id_proprietaire,
        "password" => $password,
        "token" => $dbToken,
        "code" => $code,
        "numero" => $numero,
        "tantieme" => $tantieme,
        "foncier" => $foncier,
    ];
}

function mobile_money(float $value): string
{
    return number_format($value, 2, ",", " ") . " MAD";
}

function mobile_public_url(string $relativePath): string
{
    return "https://bestcopro.ma/app/" . ltrim(str_replace("\\", "/", $relativePath), "/");
}
