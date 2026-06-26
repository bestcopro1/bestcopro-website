<?php
include_once __DIR__ . "/../config/db.php";

$connection = $GLOBALS["connection"];
$messages = [];
$errors = [];
$tableReady = false;
$mappingReady = false;

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


function pcbEnsureMappingTable($connection)
{
    $request = "CREATE TABLE IF NOT EXISTS mapping_poste_budgetaire_legacy (
        id INT(11) NOT NULL AUTO_INCREMENT,
        budget VARCHAR(50) NOT NULL,
        ancien_poste VARCHAR(255) NOT NULL,
        ancienne_rubrique VARCHAR(255) NOT NULL,
        nouveau_poste VARCHAR(255) NOT NULL,
        nouvelle_rubrique VARCHAR(255) NOT NULL,
        score DECIMAL(5,4) NOT NULL DEFAULT 0,
        occurrences INT(11) NOT NULL DEFAULT 0,
        statut ENUM('propose','valide','rejete') NOT NULL DEFAULT 'propose',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_mapping (budget, ancien_poste, ancienne_rubrique),
        KEY idx_mapping_statut (statut)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $connection->query($request) === true;
}


function pcbSeedSuggestedMappings($connection)
{
    $rows = [
        [
            base64_decode("Rm9uY3Rpb25uZW1lbnQ="), base64_decode("R2VzdGlvbiBBZG1pbmlzdHJhdGl2ZQ=="), base64_decode("UHJpbWUgZXhjZXB0aW9ubmVsbGUgcG91ciBsZSBwZXJzb25uZWwgKE5ldHRveWFnZSwgZ2FyZGllbiDigKYp"),
            base64_decode("R0VTVElPTiBBRE1JTklTVFJBVElWRQ=="), base64_decode("UFJJTUUgRVhDRVBUSU9OTkVMTEUgUE9VUiBMRSBQRVJTT05ORUwgKE5FVFRZLCBHQVJESUVOLOKApi4p"), 0.9679, 6
        ],
        [
            base64_decode("Rm9uY3Rpb25uZW1lbnQ="), base64_decode("R0FSRElFTk5BR0U="), base64_decode("U8ODwqljdXJpdMODwqkgam91ci9udWl0"),
            base64_decode("R0FSRElFTk5BR0U="), base64_decode("U0VDVVJUSUUtSk9VUi9OVUlU"), 0.8851, 29
        ],
        [
            base64_decode("Rm9uY3Rpb25uZW1lbnQ="), base64_decode("RlJBSVMgSlVSSURJUVVFUw=="), base64_decode("Q09OVEFOVElFVVgvIEhVSVNTSUVSIERFIEpVU1RJQ0U="),
            base64_decode("SlVSSURJUVVF"), base64_decode("Q09OVEFOVElFVVgvIEhVSVNTSUVSIERFIEpVU1RJQ0U="), 0.8824, 78
        ],
        [
            base64_decode("Rm9uY3Rpb25uZW1lbnQ="), base64_decode("QVNTVVJBTkNFUw=="), base64_decode("QXNzdXJhbmNlIE11bHRpcmlzcXVlICYgUkM="),
            base64_decode("QVNTVVJBTkNFUw=="), base64_decode("QVNTIE1VTFRJUklTUVVFUyBFVCBSQw=="), 0.8814, 77
        ],
        [
            base64_decode("Rm9uY3Rpb25uZW1lbnQ="), base64_decode("R0FSRElFTk5BR0U="), base64_decode("U0VDVVJJVEU="),
            base64_decode("R0FSRElFTk5BR0U="), base64_decode("U0VDVVJUSUUtSk9VUg=="), 0.8357, 3
        ],
        [
            base64_decode("Rm9uY3Rpb25uZW1lbnQ="), base64_decode("R2FyZGllbm5hZ2U="), base64_decode("U0VDVVJUSUU="),
            base64_decode("R0FSRElFTk5BR0U="), base64_decode("U0VDVVJUSUUtSk9VUg=="), 0.8357, 2
        ],
    ];

    $request = "INSERT INTO mapping_poste_budgetaire_legacy (budget, ancien_poste, ancienne_rubrique, nouveau_poste, nouvelle_rubrique, score, occurrences, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'propose')
        ON DUPLICATE KEY UPDATE nouveau_poste = VALUES(nouveau_poste), nouvelle_rubrique = VALUES(nouvelle_rubrique), score = VALUES(score), occurrences = VALUES(occurrences)";

    $imported = 0;
    foreach ($rows as $row) {
        $budget = $row[0];
        $ancienPoste = $row[1];
        $ancienneRubrique = $row[2];
        $nouveauPoste = $row[3];
        $nouvelleRubrique = $row[4];
        $score = $row[5];
        $occurrences = $row[6];
        if ($stmt = $connection->prepare($request)) {
            $stmt->bind_param("sssssdi", $budget, $ancienPoste, $ancienneRubrique, $nouveauPoste, $nouvelleRubrique, $score, $occurrences);
            if (!$stmt->execute()) {
                return $connection->error;
            }
            $imported++;
        } else {
            return $connection->error;
        }
    }

    return $imported;
}

function pcbUpdateMappingStatus($connection, $id, $statut)
{
    if (!in_array($statut, ["propose", "valide", "rejete"], true)) {
        return "Statut invalide.";
    }

    $request = "UPDATE mapping_poste_budgetaire_legacy SET statut = ? WHERE id = ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("si", $statut, $id);
        if ($stmt->execute()) {
            return "";
        }
    }

    return $connection->error;
}

function pcbApplyValidatedMappings($connection)
{
    $suffix = date("Ymd_His");
    $backupRubrique = "backup_rubrique_before_ref_mapping_" . $suffix;
    $backupPoste = "backup_poste_before_ref_mapping_" . $suffix;

    $count = 0;
    $request = "SELECT COUNT(*) FROM mapping_poste_budgetaire_legacy WHERE statut = 'valide'";
    if ($stmt = $connection->prepare($request)) {
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    }

    if ((int) $count === 0) {
        return "Aucun mapping valide a appliquer.";
    }

    if (!$connection->query("CREATE TABLE `" . $backupRubrique . "` AS SELECT * FROM rubrique")) {
        return $connection->error;
    }

    if (!$connection->query("CREATE TABLE `" . $backupPoste . "` AS SELECT * FROM poste")) {
        return $connection->error;
    }

    $connection->query("DROP TEMPORARY TABLE IF EXISTS tmp_mapping_poste_budgetaire_matches");
    $request = "CREATE TEMPORARY TABLE tmp_mapping_poste_budgetaire_matches AS
        SELECT r.id AS rubrique_id,
               p.id AS poste_id,
               m.nouveau_poste,
               m.nouvelle_rubrique
        FROM rubrique r
        JOIN poste p ON p.id_rubrique = r.id
        JOIN mapping_poste_budgetaire_legacy m
          ON m.ancien_poste = r.libelle
         AND m.ancienne_rubrique = p.libelle
         AND m.budget = CASE WHEN r.id_typeRubrique = 1 THEN 'Fonctionnement' ELSE 'Investissement' END
         AND m.statut = 'valide'";

    if (!$connection->query($request)) {
        return $connection->error;
    }

    $request = "UPDATE poste p
        JOIN tmp_mapping_poste_budgetaire_matches m ON m.poste_id = p.id
        SET p.libelle = m.nouvelle_rubrique";
    if (!$connection->query($request)) {
        return $connection->error;
    }
    $affectedPostes = $connection->affected_rows;

    $request = "UPDATE rubrique r
        JOIN (
            SELECT rubrique_id, MIN(nouveau_poste) AS nouveau_poste
            FROM tmp_mapping_poste_budgetaire_matches
            GROUP BY rubrique_id
            HAVING COUNT(DISTINCT nouveau_poste) = 1
        ) m ON m.rubrique_id = r.id
        SET r.libelle = m.nouveau_poste";
    if (!$connection->query($request)) {
        return $connection->error;
    }
    $affectedRubriques = $connection->affected_rows;

    return [
        "affected_postes" => $affectedPostes,
        "affected_rubriques" => $affectedRubriques,
        "backup_rubrique" => $backupRubrique,
        "backup_poste" => $backupPoste,
    ];
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
$mappingReady = pcbEnsureMappingTable($connection);

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


if ($mappingReady && $_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["seed_mapping_suggestions"])) {
        $result = pcbSeedSuggestedMappings($connection);
        if (is_int($result)) {
            $messages[] = $result . " proposition(s) IA chargee(s).";
        } else {
            $errors[] = $result;
        }
    }

    if (isset($_POST["update_mapping_status"])) {
        $error = pcbUpdateMappingStatus(
            $connection,
            (int) ($_POST["mapping_id"] ?? 0),
            $_POST["mapping_status"] ?? ""
        );

        if ($error === "") {
            $messages[] = "Statut du mapping mis a jour.";
        } else {
            $errors[] = $error;
        }
    }

    if (isset($_POST["apply_validated_mappings"])) {
        if (trim((string) ($_POST["apply_confirmation"] ?? "")) !== "APPLIQUER") {
            $errors[] = "Tapez APPLIQUER pour confirmer l'application des mappings valides.";
        } else {
            $result = pcbApplyValidatedMappings($connection);
            if (is_array($result)) {
                $messages[] = $result["affected_postes"] . " rubrique(s) enfant et " . $result["affected_rubriques"] . " poste(s) parent mis a jour. Backups: " . $result["backup_rubrique"] . ", " . $result["backup_poste"] . ".";
            } else {
                $errors[] = $result;
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

$mappingRows = [];
$mappingStats = [
    "propose" => 0,
    "valide" => 0,
    "rejete" => 0,
];
if ($mappingReady) {
    $request = "SELECT statut, COUNT(*) FROM mapping_poste_budgetaire_legacy GROUP BY statut";
    if ($stmt = $connection->prepare($request)) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($statut, $count);
        while ($stmt->fetch()) {
            $mappingStats[$statut] = (int) $count;
        }
        $stmt->close();
    }

    $request = "SELECT id, budget, ancien_poste, ancienne_rubrique, nouveau_poste, nouvelle_rubrique, score, occurrences, statut
        FROM mapping_poste_budgetaire_legacy
        ORDER BY FIELD(statut, 'propose', 'valide', 'rejete'), occurrences DESC, score DESC, id DESC";
    if ($stmt = $connection->prepare($request)) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $budget, $ancienPoste, $ancienneRubrique, $nouveauPoste, $nouvelleRubrique, $score, $occurrences, $statut);
        while ($stmt->fetch()) {
            $mappingRows[] = [
                "id" => $id,
                "budget" => $budget,
                "ancien_poste" => $ancienPoste,
                "ancienne_rubrique" => $ancienneRubrique,
                "nouveau_poste" => $nouveauPoste,
                "nouvelle_rubrique" => $nouvelleRubrique,
                "score" => $score,
                "occurrences" => $occurrences,
                "statut" => $statut,
            ];
        }
        $stmt->close();
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

        <?php if (!$mappingReady): ?>
        <div class="alert alert-warning">
            La table <strong>mapping_poste_budgetaire_legacy</strong> n'a pas pu etre creee automatiquement. Verifiez les droits MySQL avant de corriger l'existant.
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


        <?php if ($mappingReady): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <div class="me-auto">
                            <h4 class="card-title mb-0">Corrections de l'existant</h4>
                            <small>Validation des anciens postes/rubriques avant application.</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="alert alert-info mb-2">Proposes: <strong><?= (int) $mappingStats["propose"] ?></strong></div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-success mb-2">Valides: <strong><?= (int) $mappingStats["valide"] ?></strong></div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-secondary mb-2">Rejetes: <strong><?= (int) $mappingStats["rejete"] ?></strong></div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            L'application modifie les tables <strong>rubrique</strong> et <strong>poste</strong>. Elle cree automatiquement deux tables de backup avant l'UPDATE. A utiliser sur staging avant toute production.
                        </div>

                        <form method="POST" class="mb-3">
                            <button type="submit" name="seed_mapping_suggestions" class="btn btn-rounded btn-outline-primary">Charger les propositions IA</button>
                        </form>


                        <form method="POST" class="row g-2 align-items-end mb-4">
                            <div class="col-md-4">
                                <label class="text-label">Confirmation</label>
                                <input type="text" class="form-control input-rounded" name="apply_confirmation" placeholder="Tapez APPLIQUER">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="apply_validated_mappings" class="btn btn-rounded btn-danger">Appliquer les mappings valides</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Statut</th>
                                        <th>Score</th>
                                        <th>Occurrences</th>
                                        <th>Budget</th>
                                        <th>Actuel</th>
                                        <th>Referentiel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mappingRows as $row): ?>
                                    <tr>
                                        <td><?= pcbEscape($row["statut"]) ?></td>
                                        <td><?= pcbEscape($row["score"]) ?></td>
                                        <td><?= (int) $row["occurrences"] ?></td>
                                        <td><?= pcbEscape($row["budget"]) ?></td>
                                        <td>
                                            <strong><?= pcbEscape($row["ancien_poste"]) ?></strong><br>
                                            <?= pcbEscape($row["ancienne_rubrique"]) ?>
                                        </td>
                                        <td>
                                            <strong><?= pcbEscape($row["nouveau_poste"]) ?></strong><br>
                                            <?= pcbEscape($row["nouvelle_rubrique"]) ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex gap-2 flex-wrap">
                                                <input type="hidden" name="mapping_id" value="<?= (int) $row["id"] ?>">
                                                <input type="hidden" name="mapping_status" value="">
                                                <button type="submit" name="update_mapping_status" value="1" class="btn btn-sm btn-success" onclick="this.form.mapping_status.value='valide'">Valider</button>
                                                <button type="submit" name="update_mapping_status" value="1" class="btn btn-sm btn-outline-secondary" onclick="this.form.mapping_status.value='propose'">Proposer</button>
                                                <button type="submit" name="update_mapping_status" value="1" class="btn btn-sm btn-outline-danger" onclick="this.form.mapping_status.value='rejete'">Rejeter</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($mappingRows) === 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun mapping charge. Importez la migration de mapping generee pour voir les propositions IA.</td>
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
        <?php endif; ?>
    </div>
</div>
