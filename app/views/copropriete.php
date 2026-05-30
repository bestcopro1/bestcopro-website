<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

if (isset($_POST["id"], $_POST["delete"])) {
    $error_msg = "";

    $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $delete = filter_input(INPUT_POST, "delete", FILTER_SANITIZE_STRING);

    if ($id != "" && $delete == "true") {
        $request = "UPDATE copropriete SET display=0 WHERE id=?";

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
            $action = "a supprimé|copropriete|" . $id;
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
} elseif (
    isset(
        $_POST["id"],
        $_POST["nom"],
        $_POST["ville"],
        $_POST["adresse"],
        $_POST["codePostale"],
        $_POST["rib"],
        $_POST["nbrLot"],
        $_POST["dateExercice"],
        $_POST["id_syndic"],
        $_POST["prefixe"],
    )
) {
    $error_msg = "";

    $nom = filter_input(INPUT_POST, "nom", FILTER_SANITIZE_STRING);
    $ville = filter_input(INPUT_POST, "ville", FILTER_SANITIZE_STRING);
    $adresse = filter_input(INPUT_POST, "adresse", FILTER_SANITIZE_STRING);
    $codePostale = filter_input(
        INPUT_POST,
        "codePostale",
        FILTER_SANITIZE_STRING,
    );
    $rib = filter_input(INPUT_POST, "rib", FILTER_SANITIZE_STRING);
    $nbrLot = filter_input(INPUT_POST, "nbrLot", FILTER_SANITIZE_STRING);
    $dateExercice = filter_input(
        INPUT_POST,
        "dateExercice",
        FILTER_SANITIZE_STRING,
    );
    $id_syndic = filter_input(INPUT_POST, "id_syndic", FILTER_SANITIZE_STRING);
    $prefixe = filter_input(INPUT_POST, "prefixe", FILTER_SANITIZE_STRING);
    if ($nom == "") {
        $error_msg .= "Veuillez entrer le nom";
        echo $error_msg;
        exit();
    }
    if ($adresse == "") {
        $error_msg .= 'Veuillez entrer l\'adresse';
        echo $error_msg;
        exit();
    }
    if ($ville == "") {
        $error_msg .= "Veuillez entrer la ville";
        echo $error_msg;
        exit();
    }
    if ($prefixe == "") {
        $error_msg .= "Veuillez entrer le préfixe";
        echo $error_msg;
        exit();
    }
    if (empty($error_msg) && isset($_POST["id"], $_POST["update"])) {
        $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
        $update = filter_input(INPUT_POST, "update", FILTER_SANITIZE_STRING);
        if ($id != "" && $update == "true") {
            $request =
                "UPDATE copropriete SET nom=?, ville=?, adresse=?, codePostale=?, rib=?, id_syndic=?, prefixe=? WHERE id=?";

            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param(
                    "ssssssss",
                    $nom,
                    $ville,
                    $adresse,
                    $codePostale,
                    $rib,
                    $id_syndic,
                    $prefixe,
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
                $action = "a modifié|copropriete|" . $id;
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
    } else {
        echo $error_msg;
        exit();
    }
}
$disabled = "";
if (
    $_SESSION["id_usertype"] !== "1" &&
    $_SESSION["id_usertype"] !== "2" &&
    $_SESSION["id_usertype"] !== "3"
) {
    $disabled = "disabled";
}
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données de la copropriété</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<a href="./dashboard.php" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="copropriete">Enregistrer</button>
					<?php endif; ?>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<input type="hidden" name="id" value="<?= $GLOBALS["copropriete"][0]["id"] ?>">
													<input type="hidden" name="update" value="true">
													<label class="text-label">Nom*</label>
													<input type="text" name="nom" class="form-control input-rounded input-default mb-3" placeholder="Nom de la copropriété" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["nom"] ?>" required="" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<label class="text-label">Ville*</label>
													<input type="text" name="ville" class="form-control input-rounded input-default mb-3" placeholder="Ville" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["ville"] ?>" required="" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-lg-12 mb-2">
												<div class="form-group">
													<label class="text-label">Adresse*</label>
													<input type="text" name="adresse" class="form-control input-rounded input-default mb-3" placeholder="Adresse de la copropriété" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["adresse"] ?>" required="" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<label class="text-label">Code postale</label>
													<input type="text" name="codePostale" class="form-control input-rounded input-default mb-3" placeholder="Code postale" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["codePostale"] ?>" required="" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<label class="text-label">RIB</label>
													<input type="text" name="rib" class="form-control input-rounded input-default mb-3" placeholder="RIB" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["rib"] ?>" required="" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<label class="text-label">Nombre de lots*</label>
													<input type="number" min="1" name="nbrLot" class="form-control input-rounded input-default mb-3" placeholder="Nombre de lots" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["nbrLot"] ?>" required="" disabled>
												</div>
											</div>
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date d'ouverture de l'exercice*</label>
													<input type="month" name="dateExercice" class="form-control input-rounded input-default mb-3" placeholder="mm/yyyy" value="<?= date(
                 "Y-m",
                 strtotime($GLOBALS["copropriete"][0]["dateExercice"]),
             ) ?>" required="" disabled>
												</div>
											</div>
											<input type="hidden" name="id_syndic" value="<?= $GLOBALS["copropriete"][0][
               "id_syndic"
           ] ?>">
											<div class="col-lg-6 mb-2">
												<div class="form-group">
													<label class="text-label">Préfixe*</label>
													<input type="text" name="prefixe" class="form-control input-rounded" placeholder="Préfixe des références" value="<?= $GLOBALS[
                 "copropriete"
             ][0]["prefixe"] ?>" required="" <?= $disabled ?>>
												</div>
											</div>
										</div>
									</form>
                                </div>
							</div>
							<?php if ($_SESSION["id_usertype"] === "1"): ?>
							<div class="card-footer d-flex flex-wrap justify-content-between">
								<div class="mb-md-2 mb-3"></div>
								<div>
									<a href="javascript:void(0);" class="btn btn-rounded btn-danger btn-md me-2 mb-2" data-bs-toggle="modal" data-bs-target="#deleteCopropriete"><i class="fas fa-trash me-2"></i>Supprimer ce copropriété</a>
									<!-- Modal -->
                                    <div class="modal fade" id="deleteCopropriete">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Supprimer la copropriété</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                                    </button>
                                                </div>
                                                <div class="modal-body">
													<div class="text-center mb-4"><i class="fas fa-exclamation-triangle" style="font-size: 111px;"></i></div>
													<div class="text-center">Êtes-vous sûr de vouloir supprimer cette copropriété ?</div>
												</div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Non</button>
                                                    <button type="button" class="btn btn-rounded btn-danger" id="deleteCoproprieteBtn" data-id="<?= $GLOBALS[
                                                        "copropriete"
                                                    ][0]["id"] ?>">Oui</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
								</div>
							</div>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
			</div>
        </div>
		