<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

$connection = $GLOBALS["connection"];
$messages = [];
$errors = [];

function creationPosteBudgetaireEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function creationPosteBudgetaireAmount($value)
{
    $value = str_replace([" ", "\xc2\xa0"], "", trim((string) $value));
    return str_replace(",", ".", $value);
}

function creationPosteBudgetaireTypeFromCode($code)
{
    $normalized = strtolower(trim(preg_replace("/^\xEF\xBB\xBF/", "", (string) $code)));

    if (in_array($normalized, ["1", "f", "fonct", "fonctionnement"], true)) {
        return "1";
    }

    if (in_array($normalized, ["2", "i", "inv", "investissement"], true)) {
        return "2";
    }

    return "";
}

function creationPosteBudgetaireFindRubrique($libelle, $id_exercice, $id_typeRubrique, $connection)
{
    $request =
        "SELECT id FROM rubrique WHERE libelle = ? AND id_exercice = ? AND id_typeRubrique = ? LIMIT 1";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $libelle, $id_exercice, $id_typeRubrique);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id);
            $stmt->fetch();
            return $id;
        }
    }

    return null;
}

function creationPosteBudgetaireFindPoste($libelle, $id_rubrique, $connection)
{
    $request = "SELECT id FROM poste WHERE libelle = ? AND id_rubrique = ? LIMIT 1";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ss", $libelle, $id_rubrique);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id);
            $stmt->fetch();
            return $id;
        }
    }

    return null;
}

function creationPosteBudgetaireHistory($action, $id, $connection)
{
    if (
        isset($_SESSION["id"]) &&
        ($stmt = $connection->prepare(
            "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)"
        ))
    ) {
        $date = date("Y-m-d H:i:s");
        $action = $action . "|" . $id;
        $stmt->bind_param("sss", $date, $action, $_SESSION["id"]);
        $stmt->execute();
    }
}

function creationPosteBudgetaireSaveRow($row, $id_exercice, $connection)
{
    $code = trim((string) ($row["code"] ?? ""));
    $rubrique = trim((string) ($row["rubrique"] ?? ""));
    $poste = trim((string) ($row["poste"] ?? ""));
    $budget = creationPosteBudgetaireAmount($row["budget"] ?? "");
    $id_typeRubrique = creationPosteBudgetaireTypeFromCode($code);

    if ($id_typeRubrique === "") {
        return "Code invalide: utilisez 1/F/fonctionnement ou 2/I/investissement.";
    }

    if ($rubrique === "" || $poste === "") {
        return "Rubrique et poste sont obligatoires.";
    }

    if ($budget === "" || !is_numeric($budget) || floatval($budget) < 0) {
        return "Budget invalide pour le poste " . $poste . ".";
    }

    $id_rubrique = creationPosteBudgetaireFindRubrique(
        $rubrique,
        $id_exercice,
        $id_typeRubrique,
        $connection
    );

    if ($id_rubrique === null) {
        $request =
            "INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique) VALUES (?, ?, ?)";
        if ($stmt = $connection->prepare($request)) {
            $stmt->bind_param("sss", $rubrique, $id_exercice, $id_typeRubrique);
            if (!$stmt->execute()) {
                return $connection->error;
            }
            $id_rubrique = $connection->insert_id;
            creationPosteBudgetaireHistory("a ajouté|rubrique", $id_rubrique, $connection);
        }
    }

    $id_poste = creationPosteBudgetaireFindPoste($poste, $id_rubrique, $connection);
    if ($id_poste === null) {
        $request = "INSERT INTO poste (libelle, montant, id_rubrique) VALUES (?, ?, ?)";
        if ($stmt = $connection->prepare($request)) {
            $stmt->bind_param("sss", $poste, $budget, $id_rubrique);
            if (!$stmt->execute()) {
                return $connection->error;
            }
            creationPosteBudgetaireHistory("a ajouté|poste", $connection->insert_id, $connection);
        }
    } else {
        $request = "UPDATE poste SET montant = ? WHERE id = ?";
        if ($stmt = $connection->prepare($request)) {
            $stmt->bind_param("ss", $budget, $id_poste);
            if (!$stmt->execute()) {
                return $connection->error;
            }
            creationPosteBudgetaireHistory("a modifié|poste", $id_poste, $connection);
        }
    }

    return "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_exercice = $GLOBALS["id_exercice"];

    if (isset($_POST["save_manual"])) {
        $error = creationPosteBudgetaireSaveRow(
            [
                "code" => $_POST["code"] ?? "",
                "budget" => $_POST["budget"] ?? "",
                "poste" => $_POST["poste"] ?? "",
                "rubrique" => $_POST["rubrique"] ?? "",
            ],
            $id_exercice,
            $connection
        );

        if ($error === "") {
            $messages[] = "Poste budgétaire enregistré.";
        } else {
            $errors[] = $error;
        }
    }

    if (isset($_POST["import_csv"])) {
        if (
            !isset($_FILES["csv_file"]) ||
            $_FILES["csv_file"]["error"] !== UPLOAD_ERR_OK
        ) {
            $errors[] = "Veuillez sélectionner un fichier CSV valide.";
        } else {
            $handle = fopen($_FILES["csv_file"]["tmp_name"], "r");
            if ($handle === false) {
                $errors[] = "Impossible de lire le fichier CSV.";
            } else {
                $line = 0;
                $imported = 0;
                while (($data = fgetcsv($handle, 0, ";")) !== false) {
                    $line++;
                    if (count($data) === 1) {
                        $data = str_getcsv($data[0], ",");
                    }

                    if ($line === 1) {
                        $data[0] = preg_replace("/^\xEF\xBB\xBF/", "", (string) $data[0]);
                        $headers = array_map("strtolower", array_map("trim", $data));
                        if ($headers === ["code", "budget", "poste", "rubrique"]) {
                            continue;
                        }
                    }

                    if (count($data) < 4) {
                        $errors[] = "Ligne " . $line . ": colonnes manquantes.";
                        continue;
                    }

                    $error = creationPosteBudgetaireSaveRow(
                        [
                            "code" => $data[0],
                            "budget" => $data[1],
                            "poste" => $data[2],
                            "rubrique" => $data[3],
                        ],
                        $id_exercice,
                        $connection
                    );

                    if ($error === "") {
                        $imported++;
                    } else {
                        $errors[] = "Ligne " . $line . ": " . $error;
                    }
                }
                fclose($handle);
                if ($imported > 0) {
                    $messages[] = $imported . " poste(s) budgétaire(s) importé(s).";
                }
            }
        }
    }
}

$rubriques = getRubrique(null, $GLOBALS["id_exercice"], null, $connection);
?>
		<div class="content-body">
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Creation poste budgétaire</h2>
						<p class="mb-0"><?= creationPosteBudgetaireEscape($GLOBALS["copropriete"][0]["nom"]) ?></p>
					</div>
				</div>
                <?php foreach ($messages as $message): ?>
                <div class="alert alert-success"><?= creationPosteBudgetaireEscape($message) ?></div>
                <?php endforeach; ?>
                <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= creationPosteBudgetaireEscape($error) ?></div>
                <?php endforeach; ?>
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Importer un fichier CSV</h4>
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
                                <h4 class="card-title">Ajouter sur le site</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="text-label">Code</label>
                                            <input type="text" class="form-control input-rounded" name="code" placeholder="1/F ou 2/I" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="text-label">Budget</label>
                                            <input type="number" step="0.01" min="0" class="form-control input-rounded" name="budget" placeholder="0.00" required>
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
                                <h4 class="card-title">Postes budgétaires de l'exercice</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Budget</th>
                                                <th>Poste</th>
                                                <th>Rubrique</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rubriques as $rubrique): ?>
                                                <?php $postes = getPoste(null, $rubrique["id"], null, $connection); ?>
                                                <?php foreach ($postes as $poste): ?>
                                            <tr>
                                                <td><?= $rubrique["id_typeRubrique"] === "1" ? "1" : "2" ?></td>
                                                <td><?= number_format((float) $poste["montant"], 2, ",", " ") ?></td>
                                                <td><?= creationPosteBudgetaireEscape($poste["libelle"]) ?></td>
                                                <td><?= creationPosteBudgetaireEscape($rubrique["libelle"]) ?></td>
                                            </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
		</div>
