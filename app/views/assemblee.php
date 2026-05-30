<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
if (
    isset(
        $_POST["id_exercice"],
        $_POST["date1"],
        $_POST["date2"],
        $_POST["date3"],
        $_POST["objet"],
        $_POST["ordreDuJour"],
        $_POST["titre1"],
        $_POST["titre2"],
    )
) {
    $error_msg = "";

    $id_exercice = filter_input(
        INPUT_POST,
        "id_exercice",
        FILTER_SANITIZE_STRING,
    );
    $date1 = filter_input(INPUT_POST, "date1", FILTER_SANITIZE_STRING);
    $date2 = filter_input(INPUT_POST, "date2", FILTER_SANITIZE_STRING);
    $date3 = filter_input(INPUT_POST, "date3", FILTER_SANITIZE_STRING);
    $objet = filter_input(INPUT_POST, "objet", FILTER_SANITIZE_STRING);
    $ordreDuJour = filter_input(
        INPUT_POST,
        "ordreDuJour",
        FILTER_SANITIZE_STRING,
    );
    $titre1 = filter_input(INPUT_POST, "titre1", FILTER_SANITIZE_STRING);
    $titre2 = filter_input(INPUT_POST, "titre2", FILTER_SANITIZE_STRING);

    if ($date1 == "") {
        $error_msg .= "Veuillez entrer la date de la 1<sup>ère</sup> séance";
        echo $error_msg;
        exit();
    }
    if ($date2 == "") {
        $error_msg .= "Veuillez entrer la date de la 2<sup>ème</sup> séance";
        echo $error_msg;
        exit();
    }
    if ($date3 == "") {
        $error_msg .= "Veuillez entrer la date de consultation des documents";
        echo $error_msg;
        exit();
    }
    if ($objet == "") {
        $error_msg .= 'Veuillez entrer l\'objet';
        echo $error_msg;
        exit();
    }
    if ($objet == "") {
        $error_msg .= 'Veuillez saisir l\'ordre du jour';
        echo $error_msg;
        exit();
    }

    if (empty($error_msg) && isset($_POST["update"])) {
        $update = filter_input(INPUT_POST, "update", FILTER_SANITIZE_STRING);
        if ($update == "true") {
            $exercice = getExercice($id_exercice, null, $connection);
            $assemblee = getAssemblee(null, $exercice[0]["id"], $connection);
            if (count($assemblee) > 0) {
                $id = $assemblee[0]["id"];
                $request =
                    "UPDATE assemblee SET date1=?, date2=?, date3=?, objet=?, ordreDuJour=?, titre1=?, titre2=? WHERE id=?";

                if ($insert_stmt = $connection->prepare($request)) {
                    $insert_stmt->bind_param(
                        "ssssssss",
                        $date1,
                        $date2,
                        $date3,
                        $objet,
                        $ordreDuJour,
                        $titre1,
                        $titre2,
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
                    $action = "a modifié|assemblee|" . $id;
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
                $request = "INSERT INTO assemblee (date1, date2, date3, objet, ordreDuJour, titre1, titre2, id_exercice) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $insert_id = "";

                if ($insert_stmt = $connection->prepare($request)) {
                    $insert_stmt->bind_param(
                        "ssssssss",
                        $date1,
                        $date2,
                        $date3,
                        $objet,
                        $ordreDuJour,
                        $titre1,
                        $titre2,
                        $id_exercice,
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
                    $action = "a ajouté|assemblee|" . $insert_id;
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

                echo "done|" . $insert_id;
                exit();
            }
        }
    } else {
        $error_msg .= "Une erreur est survenue";
        exit();
    }
}
$exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
$assemblee = getAssemblee(null, $exercice[0]["id"], $connection);
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Assemblée générale (<?= getNameexercice(
          $exercice[0]["dateDebut"],
      ) ?>)</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="assemblee">Enregistrer les modifications</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-4 mb-2">
												<div class="form-group">
													<input type="hidden" name="id_exercice" value="<?= $exercice[0]["id"] ?>">
													<input type="hidden" name="update" value="true">
													<label class="text-label">Date de la 1<sup>ère</sup> séance*</label>
													<input type="datetime-local" class="form-control input-rounded input-default mb-3" name="date1" placeholder="Date 1" value="<?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["date1"]
                 : "" ?>">
												</div>
											</div>
											<div class="col-4 mb-2">
												<div class="form-group">
													<label class="text-label">Date de la 2<sup>ème</sup> séance*</label>
													<input type="datetime-local" class="form-control input-rounded input-default mb-3" name="date2" placeholder="Date 2" value="<?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["date2"]
                 : "" ?>">
												</div>
											</div>
											<div class="col-4 mb-2">
												<div class="form-group">
													<label class="text-label">Date de consultation des documents*</label>
													<input type="datetime-local" class="form-control input-rounded input-default mb-3" name="date3" placeholder="Date 3" value="<?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["date3"]
                 : "" ?>">
												</div>
											</div>
											<div class="col-12 mb-2">
												<div class="form-group">
													<label class="text-label">Objet*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="objet" placeholder="Objet" value="<?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["objet"]
                 : "" ?>">
												</div>
											</div>
											<div class="col-12 mb-4">
												<div class="form-group">
													<label class="text-label">Ordre du jour*</label>
													<textarea name="ordreDuJour" id="ordreDuJour" cols="30" rows="5" class="form-control input-rounded" placeholder="Ordre du jour ..."><?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["ordreDuJour"]
                 : "" ?></textarea>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Titre de la feuille de présence à l'assemblée générale</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="titre1" placeholder="Titre de la feuille" value="<?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["titre1"]
                 : "" ?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Titre de la liste de réception des documents</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="titre2" placeholder="Titre de la liste" value="<?= count(
                 $assemblee,
             )
                 ? $assemblee[0]["titre2"]
                 : "" ?>">
												</div>
											</div>
										</div>
                                    </form>
                                </div>
								<div class="compose-content">
									<h5 class="mb-4"><i class="fa fa-paperclip"></i> Pièces jointes</h5>
									<form action="#" class="dropzone" id="mydz">
										<div class="fallback">
											<input name="file" type="file" multiple="">
										</div>
									</form>
								</div>
							</div>
                        </div>
                    </div>
                </div>
			</div>
        </div>