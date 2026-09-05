<?php

require_once __DIR__ . "/session.php";
bestcopro_start_session();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["loggedin"], $_SESSION["id"], $_SESSION["id_usertype"]) || $_SESSION["loggedin"] !== "ImIn") {
    header("Location: ./login.php");
    exit();
}

include_once __DIR__ . "/config/db.php";
include_once __DIR__ . "/controllers/functions.php";

if (!bestcopro_is_access_admin()) {
    http_response_code(403);
    exit("Accès refusé.");
}

list(, $exportRefactorCookiePath) = bestcopro_session_context();
$exportRefactorCookieName = "BESTCOPRO_EXPORT_REFACTOR_CSRF";
$exportRefactorToken = isset($_COOKIE[$exportRefactorCookieName]) ? (string) $_COOKIE[$exportRefactorCookieName] : "";
if (!preg_match('/^[a-f0-9]{64}$/', $exportRefactorToken)) {
    $exportRefactorToken = bin2hex(random_bytes(32));
    setcookie(
        $exportRefactorCookieName,
        $exportRefactorToken,
        0,
        $exportRefactorCookiePath,
        "",
        bestcopro_is_https(),
        true
    );
}

function exportRefactorEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$output = "";
$error = "";
$mode = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfValid = isset($_POST["csrf_token"]) && hash_equals($exportRefactorToken, (string) $_POST["csrf_token"]);
    $mode = isset($_POST["mode"]) ? (string) $_POST["mode"] : "";
    if (!in_array($mode, ["simulate", "apply"], true)) {
        $error = "Action de maintenance invalide. Rechargez cette page puis réessayez.";
    } elseif (!$csrfValid) {
        $error = "Votre session a changé. Rechargez cette page puis relancez la simulation.";
    } elseif ($mode === "apply" && (!isset($_POST["confirm_apply"]) || $_POST["confirm_apply"] !== "1")) {
        $error = "Cochez la confirmation après avoir sauvegardé la base de données.";
    } else {
        try {
            @set_time_limit(120);
            define("BESTCOPRO_WEB_MIGRATION", true);
            $argv = [__FILE__];
            if ($mode === "apply") {
                $argv[] = "--apply";
            }
            ob_start();
            require __DIR__ . "/migrations/20260904_export_refactor.php";
            $output = trim(ob_get_clean());
            error_log("[BestCopro maintenance] export refactor " . $mode . " by syndic " . (int) $_SESSION["id"]);
        } catch (Throwable $exception) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            error_log("[BestCopro maintenance] export refactor failed: " . $exception->getMessage());
            $error = "La migration a échoué : " . $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance des exports — Best Copro</title>
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<main class="container-fluid py-4" style="max-width: 980px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Maintenance des exports PDF</h2>
                    <p class="text-muted mb-0">Outil temporaire réservé aux administrateurs.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">Retour</a>
            </div>
            <div class="alert alert-warning" role="alert">
                Sauvegardez la base dans phpMyAdmin avant l’application. La simulation ne modifie aucune donnée.
            </div>
            <?php if ($error !== ""): ?>
                <div class="alert alert-danger" role="alert"><?= exportRefactorEscape($error) ?></div>
            <?php endif; ?>
            <?php if ($output !== ""): ?>
                <div class="alert alert-<?= $mode === "apply" ? "success" : "info" ?>" role="status">
                    <?= $mode === "apply" ? "Migration appliquée." : "Simulation terminée : aucune donnée n’a été modifiée." ?>
                </div>
                <pre class="bg-light border rounded p-3 mb-4" style="white-space: pre-wrap;"><?= exportRefactorEscape($output) ?></pre>
            <?php endif; ?>
            <form method="post" class="border-top pt-4">
                <input type="hidden" name="csrf_token" value="<?= exportRefactorEscape($exportRefactorToken) ?>">
                <input type="hidden" name="mode" value="simulate">
                <button type="submit" class="btn btn-primary">Lancer la simulation</button>
            </form>
            <?php if ($output !== "" && $mode === "simulate" && $error === ""): ?>
                <form method="post" class="border-top mt-4 pt-4">
                    <input type="hidden" name="csrf_token" value="<?= exportRefactorEscape($exportRefactorToken) ?>">
                    <input type="hidden" name="mode" value="apply">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="confirm_apply" name="confirm_apply">
                        <label class="form-check-label" for="confirm_apply">J’ai sauvegardé la base de données et je confirme l’application.</label>
                    </div>
                    <button type="submit" class="btn btn-danger">Appliquer la migration</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
