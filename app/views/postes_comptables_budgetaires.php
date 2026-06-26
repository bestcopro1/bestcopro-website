<?php
include_once __DIR__ . "/../config/db.php";

$connection = $GLOBALS["connection"];
$messages = [];
$errors = [];
$tableReady = false;

function pcbEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function pcbCleanText($value)
{
    $value = trim((string) $value);
    if ($value === "") {
        return "";
    }

    if (!preg_match("//u", $value)) {
        $converted = @iconv("Windows-1252", "UTF-8//IGNORE", $value);
        if ($converted !== false) {
            return trim($converted);
        }
    }

    return $value;
}

function pcbNormalizeBudget($value)
{
    $value = pcbCleanText($value);
    $ascii = function_exists("iconv") ? @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value) : false;
    $normalized = strtoupper($ascii !== false ? $ascii : $value);

    if (in_array($normalized, ["F", "FONCT", "FONCTIONNEMENT"], true)) {
        return "Fonctionnement";
    }

    if (in_array($normalized, ["I", "INV", "INVESTISSEMENT", "INVESSTISSEMENT"], true)) {
        return "Investissement";
    }

    return $value;
}

function pcbSignature($budget, $poste, $rubrique)
{
    $value = $budget . "|" . $poste . "|" . $rubrique;
    $value = preg_replace("/\s+/u", " ", trim($value));
    return md5($value);
}

function pcbEnsureTable($connection)
{
    $request = "CREATE TABLE IF NOT EXISTS referentiel_poste_budgetaire (
        id INT(11) NOT NULL AUTO_INCREMENT,
        code VARCHAR(20) NOT NULL,
        budget ENUM('Fonctionnement', 'Investissement') NOT NULL,
        poste VARCHAR(255) NOT NULL,
        rubrique VARCHAR(255) NOT NULL,
        signature CHAR(32) NOT NULL,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_ref_signature (signature),
        KEY idx_ref_code (code),
        KEY idx_ref_budget_poste (budget, poste(120))
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $connection->query($request) === true;
}

function pcbSaveRow($connection, $code, $budget, $poste, $rubrique)
{
    $code = pcbCleanText($code);
    $budget = pcbNormalizeBudget($budget);
    $poste = pcbCleanText($poste);
    $rubrique = pcbCleanText($rubrique);
    $signature = pcbSignature($budget, $poste, $rubrique);

    if ($code === "" || $budget === "" || $poste === "" || $rubrique === "") {
        return "Code, budget, poste et rubrique sont obligatoires.";
    }

    if (!in_array($budget, ["Fonctionnement", "Investissement"], true)) {
        return "Budget invalide: utilisez Fonctionnement ou Investissement.";
    }

    $request = "INSERT INTO referentiel_poste_budgetaire (code, budget, poste, rubrique, signature, actif)
        VALUES (?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE code = VALUES(code), actif = 1";

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sssss", $code, $budget, $poste, $rubrique, $signature);
        if (!$stmt->execute()) {
            return $connection->error;
        }
        return "";
    }

    return $connection->error;
}

$tableReady = pcbEnsureTable($connection);

if ($tableReady && $_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["save_manual"])) {
        $error = pcbSaveRow(
            $connection,
            $_POST["code"] ?? "",
            $_POST["budget"] ?? "",
            $_POST["poste"] ?? "",
            $_POST["rubrique"] ?? ""
        );

        if ($error === "") {
            $messages[] = "Poste comptable budgetaire enregistre.";
        } else {
            $errors[] = $error;
        }
    }

    if (isset($_POST["import_csv"])) {
        if (!isset($_FILES["csv_file"]) || $_FILES["csv_file"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Veuillez selectionner un fichier CSV valide.";
        } else {
            $handle = fopen($_FILES["csv_file"]["tmp_name"], "r");
            if ($handle === false) {
                $errors[] = "Impossible de lire le fichier CSV.";
            } else {
                $line = 0;
                $imported = 0;
                while (($data = fgetcsv($handle, 0, ";")) !== false) {
                    $line++;
                    if (count($data) < 4) {
                        continue;
                    }

                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
                    $headers = array_map("strtolower", array_map("trim", $data));
                    if ($line === 1 && $headers[0] === "code" && $headers[1] === "budget" && $headers[2] === "poste" && $headers[3] === "rubrique") {
                        continue;
                    }

                    $error = pcbSaveRow($connection, $data[0], $data[1], $data[2], $data[3]);
                    if ($error === "") {
                        $imported++;
                    } else {
                        $errors[] = "Ligne " . $line . ": " . $error;
                    }
                }
                fclose($handle);

                if ($imported > 0) {
                    $messages[] = $imported . " ligne(s) importee(s).";
                }
            }
        }
    }
}

$referentiel = [];
if ($tableReady) {
    $request = "SELECT id, code, budget, poste, rubrique, actif FROM referentiel_poste_budgetaire ORDER BY budget, poste, rubrique";
    if ($stmt = $connection->prepare($request)) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $code, $budget, $poste, $rubrique, $actif);
        while ($stmt->fetch()) {
            $referentiel[] = [
                "id" => $id,
                "code" => $code,
                "budget" => $budget,
                "poste" => $poste,
                "rubrique" => $rubrique,
                "actif" => $actif,
            ];
        }
    }
}
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="form-head d-flex mb-3 align-items-start">
            <div class="me-auto d-none d-lg-block">
                <h2 class="text-primary font-w600 mb-0">Postes comptables budg&eacute;taires</h2>
                <p class="mb-0">R&eacute;f&eacute;rentiel global des codes, postes et rubriques.</p>
            </div>
        </div>

        <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            La table <strong>referentiel_poste_budgetaire</strong> n'a pas pu etre creee automatiquement. Verifiez les droits MySQL de l'utilisateur staging ou executez la migration <strong>docs/migrations/2026_06_26_referentiel_poste_budgetaire.sql</strong>.
        </div>
        <?php endif; ?>

        <?php foreach ($messages as $message): ?>
        <div class="alert alert-success"><?= pcbEscape($message) ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= pcbEscape($error) ?></div>
        <?php endforeach; ?>

        <?php if ($tableReady): ?>
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Importer un CSV</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="text-label">Fichier CSV</label>
                                <input type="file" class="form-control input-rounded" name="csv_file" accept=".csv,text/csv" required>
                            </div>
                            <p class="mb-3">Colonnes attendues : code, budget, poste, rubrique</p>
                            <button type="submit" name="import_csv" class="btn btn-rounded btn-primary">Importer</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Ajouter manuellement</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-label">Code</label>
                                    <input type="text" class="form-control input-rounded" name="code" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-label">Budget</label>
                                    <select class="form-control input-rounded" name="budget" required>
                                        <option value="Fonctionnement">Fonctionnement</option>
                                        <option value="Investissement">Investissement</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-label">Poste</label>
                                    <input type="text" class="form-control input-rounded" name="poste" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-label">Rubrique</label>
                                    <input type="text" class="form-control input-rounded" name="rubrique" required>
                                </div>
                            </div>
                            <button type="submit" name="save_manual" class="btn btn-rounded btn-primary">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">R&eacute;f&eacute;rentiel</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Budget</th>
                                        <th>Poste</th>
                                        <th>Rubrique</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referentiel as $row): ?>
                                    <tr>
                                        <td><?= pcbEscape($row["code"]) ?></td>
                                        <td><?= pcbEscape($row["budget"]) ?></td>
                                        <td><?= pcbEscape($row["poste"]) ?></td>
                                        <td><?= pcbEscape($row["rubrique"]) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($referentiel) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Aucun poste comptable budgetaire enregistre.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>