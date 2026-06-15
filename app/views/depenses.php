<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
ensureDepensePaiementFields($connection);
if (isset($_POST["select"])) {
    $id_rubrique = filter_input(INPUT_POST, "select", FILTER_SANITIZE_STRING);
    if ($id_rubrique != "" || $id_rubrique != null) {
        $postes = getPoste(null, $id_rubrique, null, $connection);
        $codeHtml = "";
        foreach ($postes as $poste) {
            $codeHtml .=
                '<option value="' .
                $poste["id"] .
                '">' .
                $poste["libelle"] .
                "</option>";
        }
        echo "done|" . $codeHtml;
        exit();
    }
    exit();
} elseif (isset($_POST["id"], $_POST["delete"])) {
    $error_msg = "";

    $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $delete = filter_input(INPUT_POST, "delete", FILTER_SANITIZE_STRING);

    if ($id != "" && $delete == "true") {
        $request = "DELETE FROM depense WHERE id=?";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $id);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a supprimé|depense|" . $id;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $_SESSION["id"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }
        echo "done|" . $id;
        exit();
    } else {
        $error_msg .= "Une erreur est survenue";
        exit();
    }
} elseif (isset($_POST["id"], $_POST["regler"])) {
    $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $datePaiement = filter_input(INPUT_POST, "datePaiement", FILTER_SANITIZE_STRING);
    $id_modePaiement = filter_input(INPUT_POST, "id_modePaiement", FILTER_SANITIZE_STRING);
    $montantPaye = filter_input(INPUT_POST, "montantPaye", FILTER_SANITIZE_STRING);

    if ($id == "" || $datePaiement == "" || $id_modePaiement == "" || $montantPaye == "") {
        echo "Veuillez renseigner les informations de paiement";
        exit();
    }
    if (!is_numeric($montantPaye) || floatval($montantPaye) <= 0) {
        echo "Veuillez entrer un montant payé valide";
        exit();
    }

    $request = "UPDATE depense SET situationPaiement='paye', datePaiement=?, id_modePaiement=?, montantPaye=? WHERE id=?";
    if ($insert_stmt = $connection->prepare($request)) {
        $insert_stmt->bind_param("ssss", $datePaiement, $id_modePaiement, $montantPaye, $id);
        if (!$insert_stmt->execute()) {
            echo $connection->error;
            exit();
        }
    }
    echo "done|" . $id;
    exit();
} elseif (
    isset(
        $_POST["id_exercice"],
        $_POST["id_poste"],
        $_POST["date"],
        $_POST["montant"],
        $_POST["id_fournisseur"],
        $_POST["situationPaiement"],
    )
) {
    $error_msg = "";

    $id_exercice = filter_input(
        INPUT_POST,
        "id_exercice",
        FILTER_SANITIZE_STRING,
    );
    $id_poste = filter_input(INPUT_POST, "id_poste", FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, "date", FILTER_SANITIZE_STRING);
    $montant = filter_input(INPUT_POST, "montant", FILTER_SANITIZE_STRING);
    $id_fournisseur = filter_input(
        INPUT_POST,
        "id_fournisseur",
        FILTER_SANITIZE_STRING,
    );
    $situationPaiement = filter_input(INPUT_POST, "situationPaiement", FILTER_SANITIZE_STRING);
    $datePaiement = filter_input(INPUT_POST, "datePaiement", FILTER_SANITIZE_STRING);
    $id_modePaiement = filter_input(INPUT_POST, "id_modePaiement", FILTER_SANITIZE_STRING);
    $montantPaye = filter_input(INPUT_POST, "montantPaye", FILTER_SANITIZE_STRING);
    $commentaire = filter_input(
        INPUT_POST,
        "commentaire",
        FILTER_SANITIZE_STRING,
    );
    $id_syndic = $_SESSION["id"];

    if ($id_poste == "") {
        $error_msg .= "Veuillez sélectionner un poste";
        echo $error_msg;
        exit();
    }
    if ($date == "") {
        $error_msg .= "Veuillez entrer la date";
        echo $error_msg;
        exit();
    }
    if ($montant == "") {
        $error_msg .= "Veuillez entrer le montant";
        echo $error_msg;
        exit();
    }
    if (!is_numeric($montant) && floatval($montant) > 0) {
        $error_msg .= "Veuillez entrer un montant valide";
        echo $error_msg;
        exit();
    }
    if ($id_fournisseur == "") {
        $error_msg .= "Veuillez sélectionner un fournisseurs";
        echo $error_msg;
        exit();
    }
    if ($situationPaiement == "non_paye") {
        $id_modePaiement = "0";
    }
    if ($id_modePaiement == "") {
        $error_msg .= "Veuillez sélectionner un mode de paiment";
        echo $error_msg;
        exit();
    }
    if ($situationPaiement != "paye" && $situationPaiement != "non_paye") {
        $error_msg .= "Veuillez sélectionner la situation de paiement";
        echo $error_msg;
        exit();
    }
    if ($situationPaiement == "paye") {
        if ($datePaiement == "") {
            $error_msg .= "Veuillez entrer la date de paiement";
            echo $error_msg;
            exit();
        }
        if ($montantPaye == "" || !is_numeric($montantPaye) || floatval($montantPaye) <= 0) {
            $error_msg .= "Veuillez entrer un montant payé valide";
            echo $error_msg;
            exit();
        }
    } else {
        $datePaiement = null;
        $id_modePaiement = null;
        $montantPaye = null;
    }
    $poste = getPoste($id_poste, null, null, $connection);
    if (floatval($montant) > floatval($poste[0]["montant"])) {
        $error_msg .= "Vous avez dépassé le budget de ce poste";
        echo $error_msg;
        exit();
    }
    $id_typeRubrique = 0;
    $totalDepenses = 0;
    $exercice = getExercice($id_exercice, null, $connection);
    $request =
        "SELECT id_typeRubrique FROM rubrique WHERE id = (SELECT id_rubrique FROM poste WHERE id = ?)";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_poste);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id_typeRubrique);
        $stmt->fetch();
    }
    $request =
        "select COALESCE(SUM(montant),0) from depense where id_exercice = ? AND id_poste IN (SELECT id FROM poste WHERE id_rubrique IN (SELECT id FROM rubrique WHERE id_typeRubrique = ?))";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ss", $id_exercice, $id_typeRubrique);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($totalDepenses);
        $stmt->fetch();
    }
    if (
        $id_typeRubrique == "1" &&
        floatval($montant) + floatval($totalDepenses) >
            floatval($exercice[0]["montantFonct"])
    ) {
        $error_msg .= "Vous avez dépassé le budget de fonctionnement";
        echo $error_msg;
        exit();
    }
    if (
        $id_typeRubrique == "2" &&
        floatval($montant) + floatval($totalDepenses) >
            floatval($exercice[0]["montantInvest"])
    ) {
        $error_msg .= 'Vous avez dépassé le budget d\'investissement';
        echo $error_msg;
        exit();
    }
    if (empty($error_msg) && isset($_POST["id"], $_POST["update"])) {
        $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
        $update = filter_input(INPUT_POST, "update", FILTER_SANITIZE_STRING);
        if ($id != "" && $update == "true") {
            $request =
                "UPDATE depense SET id_poste=?, date=?, montant=?, id_fournisseur=?, id_modePaiement=?, commentaire=?, situationPaiement=?, datePaiement=?, montantPaye=? WHERE id=?";

            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param(
                    "ssssssssss",
                    $id_poste,
                    $date,
                    $montant,
                    $id_fournisseur,
                    $id_modePaiement,
                    $commentaire,
                    $situationPaiement,
                    $datePaiement,
                    $montantPaye,
                    $id,
                );
                // Execute the prepared query.
                if (!$insert_stmt->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
            if (
                $insert_stmt_history = $connection->prepare(
                    "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
                )
            ) {
                $date = date("Y-m-d H:i:s");
                $action = "a modifié|depense|" . $id;
                $insert_stmt_history->bind_param(
                    "sss",
                    $date,
                    $action,
                    $_SESSION["id"],
                );
                // Execute the prepared query.
                if (!$insert_stmt_history->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
            $linkFile = "";
            if (isset($_FILES["file"]["name"])) {
                if ($_FILES["file"]["name"] != "") {
                    $fileType = pathinfo(
                        $_FILES["file"]["name"],
                        PATHINFO_EXTENSION,
                    );
                    $fileType = strtolower($fileType);
                    $location =
                        __DIR__ .
                        "/../justificatifs/depenses/" .
                        $id .
                        "." .
                        $fileType;
                    $oldFiles = glob("../justificatifs/depenses/" . $id . ".*");
                    foreach ($oldFiles as $oldFile) {
                        if (is_file($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    move_uploaded_file($_FILES["file"]["tmp_name"], $location);
                    $linkFile =
                        getURL() .
                        "/../justificatifs/depenses/" .
                        $id .
                        "." .
                        $fileType;
                }
            }
            echo "done|" . $id . "|" . $linkFile;
            exit();
        } else {
            $error_msg .= "Une erreur est survenue";
            exit();
        }
    } elseif (empty($error_msg)) {
        $request = "INSERT INTO depense (id_poste, date, montant, id_fournisseur, id_modePaiement, commentaire, id_exercice, id_syndic, situationPaiement, datePaiement, montantPaye) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insert_id = "";

        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "sssssssssss",
                $id_poste,
                $date,
                $montant,
                $id_fournisseur,
                $id_modePaiement,
                $commentaire,
                $id_exercice,
                $id_syndic,
                $situationPaiement,
                $datePaiement,
                $montantPaye,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a ajouté|depense|" . $insert_id;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $_SESSION["id"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $linkFile = "";
        if (isset($_FILES["file"]["name"])) {
            if ($_FILES["file"]["name"] != "") {
                $fileType = pathinfo(
                    $_FILES["file"]["name"],
                    PATHINFO_EXTENSION,
                );
                $fileType = strtolower($fileType);
                $location =
                    __DIR__ .
                    "/../justificatifs/depenses/" .
                    $insert_id .
                    "." .
                    $fileType;
                move_uploaded_file($_FILES["file"]["tmp_name"], $location);
                $linkFile =
                    getURL() .
                    "/../justificatifs/depenses/" .
                    $insert_id .
                    "." .
                    $fileType;
            }
        }
        echo "done|" . $insert_id . "|" . $linkFile;
        exit();
    } else {
        echo $error_msg;
        exit();
    }
}
if (isset($_GET["action"], $_GET["id"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
    $depense = getDepense($id, null, $connection);
    if (
        count($depense) == 0 ||
        ($_SESSION["id_usertype"] !== "1" &&
            $_SESSION["id_usertype"] !== "2" &&
            $_SESSION["id_usertype"] !== "3")
    ) {
        goto iomEnd;
    }
    $date = date("d/m/Y", strtotime($depense[0]["date"]));
    if ($action == "update" && $id != ""): ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'une dépense</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=depenses" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="depenses">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                            <div class="row">
											<div class="col-6 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id" value="<?= $depense[0]["id"] ?>">
													<input type="hidden" name="update" value="true">
													<input type="hidden" name="id_exercice" value="<?= $GLOBALS["id_exercice"] ?>">
                                                    <label class="text-label">Rubrique</label>
                                                    <select name="id_rubrique" id="depenses_rubrique" class="single-select2 form-control wide mb-3">
                                                        <?php
                                                        $rubriques = getRubrique(
                                                            null,
                                                            $GLOBALS[
                                                                "id_exercice"
                                                            ],
                                                            null,
                                                            $connection,
                                                        );
                                                        foreach (
                                                            $rubriques
                                                            as $rubrique
                                                        ): ?>
                                                        <option value="<?= $rubrique[
                                                            "id"
                                                        ] ?>"><?= $rubrique[
    "libelle"
] ?></option>
                                                        <?php endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Poste de dépense*</label>
                                                    <select name="id_poste" id="id_poste" class="single-select2 form-control wide mb-3">
                                                        <?php $poste = getPoste(
                                                            $depense[0][
                                                                "id_poste"
                                                            ],
                                                            null,
                                                            null,
                                                            $connection,
                                                        ); ?>
                                                        <option value="<?= $poste[0][
                                                            "id"
                                                        ] ?>"><?= $poste[0][
    "libelle"
] ?></option>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date de Facture*</label>
                                                    <input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="jj/mm/aaaa" value="<?= $depense[0][
                                                        "date"
                                                    ] ?>">
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Montant de facture*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="montant" placeholder="Montant" value="<?= $depense[0][
                 "montant"
             ] ?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Fournisseur*</label>
													<select name="id_fournisseur" class="single-select2 form-control wide mb-3">
														<?php
              $fournisseurs = getFournisseur(null, $connection);
              foreach ($fournisseurs as $fournisseur): ?>
                                                        <option value="<?= $fournisseur[
                                                            "id"
                                                        ] ?>" <?php if (
    $fournisseur["id"] == $depense[0]["id_fournisseur"]
) {
    echo "selected";
} ?>><?= $fournisseur["raisonSocial"] ?></option>
                                                        <?php endforeach;
              ?>
                                                    </select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Justificatif </label>
													<input type="file" class="form-control input-rounded form-file-input" name="justificatif" id="justificatif" placeholder="Justificatif" accept="image/jpeg,image/png,application/pdf">
												</div>
											</div>
                                            <div class="col-6 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Situation de paiment*</label>
                                                    <select name="situationPaiement" class="default-select form-control input-rounded wide mb-3 situationPaiement">
                                                        <option value="paye" <?php if (($depense[0]["situationPaiement"] ?? "paye") == "paye") { echo "selected"; } ?>>Payé</option>
                                                        <option value="non_paye" <?php if (($depense[0]["situationPaiement"] ?? "paye") == "non_paye") { echo "selected"; } ?>>Non payé</option>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2 paiement-fields">
												<div class="form-group">
													<label class="text-label">Date de paiment*</label>
                                                    <input type="date" class="form-control input-rounded input-default mb-3" name="datePaiement" placeholder="jj/mm/aaaa" value="<?= $depense[0]["datePaiement"] ?: $depense[0]["date"] ?>">
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2 paiement-fields">
                                                <div class="form-group">
                                                    <label class="text-label">Mode de paiment*</label>
                                                    <select name="id_modePaiement" class="default-select form-control input-rounded wide mb-3">
														<?php
              $modepaiements = getModepaiement(null, $connection);
              foreach ($modepaiements as $modepaiement): ?>
                                                        <option value="<?= $modepaiement[
                                                            "id"
                                                        ] ?>" <?php if (
    $modepaiement["id"] == $depense[0]["id_modePaiement"]
) {
    echo "selected";
} ?>><?= $modepaiement["libelle"] ?></option>
                                                        <?php endforeach;
              ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2 paiement-fields">
												<div class="form-group">
													<label class="text-label">Montant payé*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="montantPaye" placeholder="Montant payé" value="<?= $depense[0]["montantPaye"] ?: $depense[0]["montant"] ?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Commentaire</label>
                                                    <textarea class="form-control input-rounded input-default mb-3" name="commentaire" placeholder="Commentaire"><?= $depense[0][
                                                        "commentaire"
                                                    ] ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<?php $preuves = glob(
        "./justificatifs/depenses/" . $depense[0]["id"] . ".*",
    ); ?>
				<div class="row" id="blockOFjustificatif" <?php if (count($preuves) == 0) {
        echo 'style="display: none;"';
    } ?>>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Justificatif</h4>
                            </div>
							<div class="card-body">
								<div class="row">
									<div class="col-12">
										<?php if (count($preuves) > 0) {
              echo '<iframe src="' .
                  $preuves[0] .
                  "?" .
                  uniqid() .
                  '" width="100%" height="500px"></iframe>';
          } else {
              echo '<iframe src="#" width="100%" height="500px"></iframe>';
          } ?>
									</div>
								</div>
							</div>
						</div>
                    </div>
                </div>
			</div>
        </div>
<?php elseif ($action == "view" && $id != ""): ?>

<?php else:goto iomEnd;endif;
elseif (isset($_GET["action"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    if ($action == "add"): ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter une dépense</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=depenses" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="depenses">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-6 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id_exercice" value="<?= $GLOBALS["id_exercice"] ?>">
                                                    <label class="text-label">Rubrique</label>
                                                    <select name="id_rubrique" id="depenses_rubrique" class="single-select2 form-control wide mb-3">
                                                        <?php
                                                        $rubriques = getRubrique(
                                                            null,
                                                            $GLOBALS[
                                                                "id_exercice"
                                                            ],
                                                            null,
                                                            $connection,
                                                        );
                                                        foreach (
                                                            $rubriques
                                                            as $rubrique
                                                        ): ?>
                                                        <option value="<?= $rubrique[
                                                            "id"
                                                        ] ?>"><?= $rubrique[
    "libelle"
] ?></option>
                                                        <?php endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Poste de dépense*</label>
                                                    <select name="id_poste" id="id_poste" class="single-select2 form-control wide mb-3">
                                                        <?php
                                                        $postes = getPoste(
                                                            null,
                                                            $rubriques[0]["id"],
                                                            null,
                                                            $connection,
                                                        );
                                                        foreach (
                                                            $postes
                                                            as $poste
                                                        ): ?>
                                                        <option value="<?= $poste[
                                                            "id"
                                                        ] ?>"><?= $poste[
    "libelle"
] ?></option>
                                                        <?php endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Fournisseur*</label>
													<select name="id_fournisseur" class="single-select2 form-control wide mb-3">
														<?php
              $fournisseurs = getFournisseur(null, $connection);
              foreach ($fournisseurs as $fournisseur): ?>
                                                        <option value="<?= $fournisseur[
                                                            "id"
                                                        ] ?>"><?= $fournisseur[
    "raisonSocial"
] ?></option>
                                                        <?php endforeach;
              ?>
                                                    </select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date de Facture*</label>
                                                    <input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="jj/mm/aaaa">
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Montant de facture*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="montant" placeholder="Montant">
												</div>
											</div>
                                            <div class="col-6 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Situation de paiment*</label>
                                                    <select name="situationPaiement" class="default-select form-control input-rounded wide mb-3 situationPaiement">
                                                        <option value="paye">Payé</option>
                                                        <option value="non_paye">Non payé</option>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2 paiement-fields">
												<div class="form-group">
													<label class="text-label">Date de paiment*</label>
                                                    <input type="date" class="form-control input-rounded input-default mb-3" name="datePaiement" placeholder="jj/mm/aaaa">
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2 paiement-fields">
                                                <div class="form-group">
                                                    <label class="text-label">Mode de paiment*</label>
                                                    <select name="id_modePaiement" class="default-select form-control input-rounded wide mb-3">
														<?php
              $modepaiements = getModepaiement(null, $connection);
              foreach ($modepaiements as $modepaiement): ?>
                                                        <option value="<?= $modepaiement[
                                                            "id"
                                                        ] ?>"><?= $modepaiement[
    "libelle"
] ?></option>
                                                        <?php endforeach;
              ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2 paiement-fields">
												<div class="form-group">
													<label class="text-label">Montant payé*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="montantPaye" placeholder="Montant payé">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Justificatif</label>
													<input type="file" class="form-control input-rounded form-file-input" name="justificatif" id="justificatif" placeholder="Justificatif" accept="image/jpeg,image/png,application/pdf">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Commentaire</label>
                                                    <textarea class="form-control input-rounded input-default mb-3" name="commentaire" placeholder="Commentaire"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<div class="row" id="blockOFjustificatif" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Justificatif</h4>
                            </div>
							<div class="card-body">
								<div class="row">
									<div class="col-12">
										<iframe src="#" width="100%" height="500px"></iframe>
									</div>
								</div>
							</div>
						</div>
                    </div>
                </div>
			</div>
        </div>
<?php else:goto iomEnd;endif;
else:

    iomEnd:
    $depenses = getDepense(null, $GLOBALS["id_exercice"], $connection);
    $depensesPayees = array_filter($depenses, function ($depense) {
        return ($depense["situationPaiement"] ?? "paye") == "paye";
    });
    $depensesNonPayees = array_filter($depenses, function ($depense) {
        return ($depense["situationPaiement"] ?? "paye") == "non_paye";
    });
    ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Dépenses</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="dashboard.php?page=depenses&action=add" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-plus color-primary"></i></span> Ajouter
					</a>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<a href="export/export_depense.php?id_copropriete=<?= $GLOBALS[
         "id_copropriete"
     ] ?>&id_exercice=<?= $GLOBALS[
    "id_exercice"
] ?>" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter
					</a>
					<?php endif; ?>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date de Facture</th>
                                                <th>Poste</th>
                                                <th>Montant de facture</th>
                                                <th>Date de paiment</th>
                                                <th>Montant payé</th>
                                                <th>Mode de paiment</th>
                                                <th>Fournisseur</th>
                                                <th>Responsable</th>
                                                <?php if (
                                                    $_SESSION["id_usertype"] ===
                                                        "1" ||
                                                    $_SESSION["id_usertype"] ===
                                                        "2" ||
                                                    $_SESSION["id_usertype"] ===
                                                        "3"
                                                ): ?>
												<th class="text-center">Actions</th>
												<?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($depensesPayees as $depense):

                                            $poste = getPoste(
                                                $depense["id_poste"],
                                                null,
                                                null,
                                                $connection,
                                            );
                                            $syndic = getSyndic(
                                                $depense["id_syndic"],
                                                null,
                                                $connection,
                                            );
                                            $fournisseur = getFournisseur(
                                                $depense["id_fournisseur"],
                                                $connection,
                                            );
                                            $modepaiement = [];
                                            if ($depense["id_modePaiement"] != "") {
                                                $modepaiement = getModepaiement(
                                                    $depense["id_modePaiement"],
                                                    $connection,
                                                );
                                            }
                                            ?>
                                            <tr class="trDepense-<?= $depense[
                                                "id"
                                            ] ?>">
                                                <td><?= date(
                                                    "d/m/Y",
                                                    strtotime($depense["date"]),
                                                ) ?></td>
                                                <td><?= $poste[0][
                                                    "libelle"
                                                ] ?></td>
                                                <td><?= $depense["montant"] ?></td>
                                                <td><?= date(
                                                    "d/m/Y",
                                                    strtotime($depense["datePaiement"] ?: $depense["date"]),
                                                ) ?></td>
                                                <td><?= $depense["montantPaye"] ?: $depense["montant"] ?></td>
                                                <td><?= count($modepaiement) > 0 ? $modepaiement[0]["libelle"] : "" ?></td>
                                                <td><?= $fournisseur[0][
                                                    "raisonSocial"
                                                ] ?></td>
                                                <td><?= $syndic[0]["civilite"] .
                                                    " " .
                                                    $syndic[0]["prenom"] .
                                                    " " .
                                                    $syndic[0]["nom"] ?></td>
												<?php if (
                $_SESSION["id_usertype"] === "1" ||
                $_SESSION["id_usertype"] === "2" ||
                $_SESSION["id_usertype"] === "3"
            ): ?>
												<td class="text-center">
													<a href="./dashboard.php?page=depenses&action=update&id=<?= $depense[
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
													<a href="javascript:void(0);" class="btn btn-secondary shadow btn-xs sharp me-1" data-bs-toggle="modal" data-bs-target=".delDepense-<?= $depense[
                 "id"
             ] ?>"><i class="fas fa-trash"></i></a>
												</td>
												<?php endif; ?>
                                            </tr>
											<?php if (
               $_SESSION["id_usertype"] === "1" ||
               $_SESSION["id_usertype"] === "2" ||
               $_SESSION["id_usertype"] === "3"
           ): ?>
											<!-- Modal -->
											<div class="modal fade delDepense-<?= $depense["id"] ?>">
												<div class="modal-dialog modal-dialog-centered" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title">Supprimer la dépense</h5>
															<button type="button" class="btn-close" data-bs-dismiss="modal">
															</button>
														</div>
														<div class="modal-body">
															<div class="text-center mb-4"><i class="fas fa-exclamation-triangle" style="font-size: 111px;"></i></div>
															<div class="text-center">Êtes-vous sûr de vouloir supprimer cette dépense ?</div>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Non</button>
															<button type="button" class="btn btn-rounded btn-danger delDepenseBtn" data-id="<?= $depense[
                   "id"
               ] ?>">Oui</button>
														</div>
													</div>
												</div>
											</div>
											<?php endif; ?>
                                            <?php
                                        endforeach; ?>
                                        <tfoot>
                                             <tr>
                                                <th>Date de Facture</th>
                                                <th>Post</th>
                                                <th>Montant de facture</th>
                                                <th>Date de paiment</th>
                                                <th>Montant payé</th>
                                                <th>Mode de paiment</th>
                                                <th>Fournisseur</th>
                                                <th>Responsable</th>
                                                <?php if (
                                                    $_SESSION["id_usertype"] ===
                                                        "1" ||
                                                    $_SESSION["id_usertype"] ===
                                                        "2" ||
                                                    $_SESSION["id_usertype"] ===
                                                        "3"
                                                ): ?>
												<th class="text-center">Actions</th>
												<?php endif; ?>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Factures non payées</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="facturesNonPayees" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date de Facture</th>
                                                <th>Poste</th>
                                                <th>Montant de facture</th>
                                                <th>Responsable</th>
                                                <th>Fournisseur</th>
                                                <?php if (
                                                    $_SESSION["id_usertype"] === "1" ||
                                                    $_SESSION["id_usertype"] === "2" ||
                                                    $_SESSION["id_usertype"] === "3"
                                                ): ?>
                                                <th class="text-center">Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($depensesNonPayees as $depense):
                                                $poste = getPoste($depense["id_poste"], null, null, $connection);
                                                $syndic = getSyndic($depense["id_syndic"], null, $connection);
                                                $fournisseur = getFournisseur($depense["id_fournisseur"], $connection);
                                                ?>
                                            <tr class="trDepense-<?= $depense["id"] ?>">
                                                <td><?= date("d/m/Y", strtotime($depense["date"])) ?></td>
                                                <td><?= $poste[0]["libelle"] ?></td>
                                                <td><?= $depense["montant"] ?></td>
                                                <td><?= $syndic[0]["civilite"] . " " . $syndic[0]["prenom"] . " " . $syndic[0]["nom"] ?></td>
                                                <td><?= $fournisseur[0]["raisonSocial"] ?></td>
                                                <?php if (
                                                    $_SESSION["id_usertype"] === "1" ||
                                                    $_SESSION["id_usertype"] === "2" ||
                                                    $_SESSION["id_usertype"] === "3"
                                                ): ?>
                                                <td class="text-center">
                                                    <a href="javascript:void(0);" class="btn btn-primary shadow btn-xs me-1" data-bs-toggle="modal" data-bs-target=".reglerDepense-<?= $depense["id"] ?>">Régler</a>
                                                    <a href="javascript:void(0);" class="btn btn-secondary shadow btn-xs me-1" data-bs-toggle="modal" data-bs-target=".delDepense-<?= $depense["id"] ?>">Supprimer</a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php if (
                                                $_SESSION["id_usertype"] === "1" ||
                                                $_SESSION["id_usertype"] === "2" ||
                                                $_SESSION["id_usertype"] === "3"
                                            ): ?>
                                            <div class="modal fade reglerDepense-<?= $depense["id"] ?>">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Régler la facture</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label class="text-label">Date de paiment</label>
                                                                <input type="date" class="form-control input-rounded mb-3 regler-date" value="<?= date("Y-m-d") ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="text-label">Mode de paiment</label>
                                                                <select class="default-select form-control input-rounded wide mb-3 regler-mode">
                                                                    <?php $modepaiements = getModepaiement(null, $connection);
                                                                    foreach ($modepaiements as $modepaiement): ?>
                                                                    <option value="<?= $modepaiement["id"] ?>"><?= $modepaiement["libelle"] ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="text-label">Montant payé</label>
                                                                <input type="text" class="form-control input-rounded mb-3 regler-montant" value="<?= $depense["montant"] ?>">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Annuler</button>
                                                            <button type="button" class="btn btn-rounded btn-primary reglerDepenseBtn" data-id="<?= $depense["id"] ?>">Enregistrer</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade delDepense-<?= $depense["id"] ?>">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Supprimer la dépense</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="text-center mb-4"><i class="fas fa-exclamation-triangle" style="font-size: 111px;"></i></div>
                                                            <div class="text-center">Êtes-vous sûr de vouloir supprimer cette dépense ?</div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Non</button>
                                                            <button type="button" class="btn btn-rounded btn-danger delDepenseBtn" data-id="<?= $depense["id"] ?>">Oui</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
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
<?php
endif;
?>
