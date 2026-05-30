<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
if (isset($_POST["typedocument"], $_POST["update_typedocument"])) {
    $error_msg = "";

    $typedocument = filter_input(
        INPUT_POST,
        "typedocument",
        FILTER_SANITIZE_STRING,
    );
    $update_typedocument = filter_input(
        INPUT_POST,
        "update_typedocument",
        FILTER_SANITIZE_STRING,
    );

    if ($update_typedocument == "true") {
        if ($typedocument == "") {
            $error_msg .= "Veuillez entrer le type du document";
            echo $error_msg;
            exit();
        }
        if (empty($error_msg)) {
            $request = "INSERT INTO typedocument (libelle) VALUES (?)";
            $insert_id = "";
            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param("s", $typedocument);
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
                $action = "a ajouté|typedocument|" . $insert_id;
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
            echo "done|" .
                $insert_id .
                "|<option value='" .
                $insert_id .
                "'>" .
                $typedocument .
                "</option>";
            exit();
        } else {
            echo $error_msg;
            exit();
        }
    }
} elseif (
    isset(
        $_POST["titre"],
        $_POST["date"],
        $_POST["id_typedocument"],
        $_POST["id_copropriete"],
        $_POST["public"],
    )
) {
    $error_msg = "";

    $titre = filter_input(INPUT_POST, "titre", FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, "date", FILTER_SANITIZE_STRING);
    $id_typedocument = filter_input(
        INPUT_POST,
        "id_typedocument",
        FILTER_SANITIZE_STRING,
    );
    $id_copropriete = filter_input(
        INPUT_POST,
        "id_copropriete",
        FILTER_SANITIZE_STRING,
    );
    $public = filter_input(INPUT_POST, "public", FILTER_SANITIZE_STRING);

    if ($titre == "") {
        $error_msg .= "Veuillez entrer le Titre du document";
        echo $error_msg;
        exit();
    }
    if ($date == "") {
        $error_msg .= "Veuillez entrer la Date";
        echo $error_msg;
        exit();
    }

    if (empty($error_msg) && isset($_POST["id"], $_POST["update"])) {
        $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
        $update = filter_input(INPUT_POST, "update", FILTER_SANITIZE_STRING);
        if ($id != "" && $update == "true") {
            $request =
                "UPDATE document SET titre=?, date=?, id_typedocument=?, public=? WHERE id=?";

            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param(
                    "sssss",
                    $titre,
                    $date,
                    $id_typedocument,
                    $public,
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
                $action = "a modifié|document|" . $id;
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
                        "/../justificatifs/documents/" .
                        $id .
                        "." .
                        $fileType;
                    $oldFiles = glob(
                        "../justificatifs/documents/" . $id . ".*",
                    );
                    foreach ($oldFiles as $oldFile) {
                        if (is_file($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    move_uploaded_file($_FILES["file"]["tmp_name"], $location);
                    $linkFile =
                        getURL() .
                        "/../justificatifs/documents/" .
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
        if (!isset($_FILES["file"]["name"])) {
            $error_msg .= "Veuillez choisir un fichier";
            echo $error_msg;
            exit();
        }
        if ($_FILES["file"]["name"] == "") {
            $error_msg .= "Veuillez choisir un fichier";
            echo $error_msg;
            exit();
        }

        $request = "INSERT INTO document (titre, date, id_typedocument, id_copropriete, public) 
		VALUES (?, ?, ?, ?, ?)";

        $insert_id = "";

        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "sssss",
                $titre,
                $date,
                $id_typedocument,
                $id_copropriete,
                $public,
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
            $action = "a ajouté|document|" . $insert_id;
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
                    "/../justificatifs/documents/" .
                    $insert_id .
                    "." .
                    $fileType;
                move_uploaded_file($_FILES["file"]["tmp_name"], $location);
                $linkFile =
                    getURL() .
                    "/../justificatifs/documents/" .
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
    $document = getDocument($id, $GLOBALS["id_copropriete"], null, $connection);
    if (count($document) == 0) {
        goto iomEnd;
    }
    $date = date("d/m/Y", strtotime($document[0]["date"]));
    if ($action == "update" && $id != ""):

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
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'un document</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<a href="./dashboard.php?page=documents" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="documents">Enregistrer</button>
					<?php else: ?>
					<a href="./dashboard.php?page=documents" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Retour à la liste</a>
					<?php endif; ?>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
										<div class="row">
											<div class="col-6 mb-2" >
												<div class="form-group">
													<input type="hidden" name="id" value="<?= $document[0]["id"] ?>">
													<input type="hidden" name="update" value="true">
													<input type="hidden" name="id_copropriete" value="<?= $GLOBALS[
                 "id_copropriete"
             ] ?>">
													<label class="text-label">Titre du document*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="titre" placeholder="Titre du document" value="<?= $document[0][
                 "titre"
             ] ?>" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Date*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="Date" value="<?= $document[0][
                 "date"
             ] ?>" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-6 mb-2" >
												<div class="form-group">
													<label class="text-label">Type du document*</label>
													<div class="row">
														<div class="col-10">
															<span id="id_typedocumentContainer">
																<select name="id_typedocument" id="id_typedocument" class="default-select form-control input-rounded wide mb-3" <?= $disabled ?>>
																	<?php
                 $typedocuments = getTypedocument(null, $connection);
                 foreach ($typedocuments as $typedocument): ?>
																	<option value="<?= $typedocument["id"] ?>" <?php if (
    $typedocument["id"] == $document[0]["id_typedocument"]
) {
    echo "selected";
} ?>><?= $typedocument["libelle"] ?></option>
																	<?php endforeach;
                 ?>
																</select>
															</span>
															<input type="text" class="form-control input-rounded input-default mb-3" name="typedocument" id="typedocument" placeholder="Type du document" style="display: none;">
														</div>
														<div class="col-2">
															<button type="button" id="addTypedocument" class="btn btn-primary btn-rounded" <?= $disabled ?>><i class="fa fa-plus color-primary"></i></button>
															<button type="button" id="saveTypedocument" class="btn btn-secondary btn-rounded" style="display: none;"><i class="fa fa-check color-secondary"></i></button>
														</div>
													</div>
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Téléverser le document</label>
													<input type="file" class="form-control input-rounded form-file-input" name="justificatif" id="justificatif" placeholder="Justificatif" accept="image/jpeg,image/png,application/pdf" <?= $disabled ?>>
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Document à partager</label>
													<div class="mb-0">
														<label class="radio-inline me-3"><input type="radio" name="public" value="1" class="form-check-input" <?php if (
                  $document[0]["public"] == "1"
              ) {
                  echo "checked";
              } ?> <?= $disabled ?>> Oui</label>
														<label class="radio-inline me-3"><input type="radio" name="public" value="0" class="form-check-input" <?php if (
                  $document[0]["public"] == "0"
              ) {
                  echo "checked";
              } ?> <?= $disabled ?>> Non</label>
													</div>
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
        "./justificatifs/documents/" . $document[0]["id"] . ".*",
    ); ?>
				<div class="row" id="blockOFjustificatif" <?php if (count($preuves) == 0) {
        echo 'style="display: none;"';
    } ?>>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Aperçu du document</h4>
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
<?php
    elseif ($action == "view" && $id != ""): ?>

<?php else:goto iomEnd;endif;
elseif (isset($_GET["action"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    if ($action == "add"): ?>
<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter un document</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=documents" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="documents">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
										<div class="row">
											<div class="col-6 mb-2" >
												<div class="form-group">
													<input type="hidden" name="id_copropriete" value="<?= $GLOBALS[
                 "id_copropriete"
             ] ?>">
													<label class="text-label">Titre du document*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="titre" placeholder="Titre du document">
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Date*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="Date">
												</div>
											</div>
											<div class="col-6 mb-2" >
												<div class="form-group">
													<label class="text-label">Type du document*</label>
													<div class="row">
														<div class="col-10">
															<span id="id_typedocumentContainer">
																<select name="id_typedocument" id="id_typedocument" class="single-select2 form-control wide mb-3">
																	<?php
                 $typedocuments = getTypedocument(null, $connection);
                 foreach ($typedocuments as $typedocument): ?>
																	<option value="<?= $typedocument["id"] ?>"><?= $typedocument[
    "libelle"
] ?></option>
																	<?php endforeach;
                 ?>
																</select>
															</span>
															<input type="text" class="form-control input-rounded input-default mb-3" name="typedocument" id="typedocument" placeholder="Type du document" style="display: none;">
														</div>
														<div class="col-2">
															<button type="button" id="addTypedocument" class="btn btn-primary btn-rounded"><i class="fa fa-plus color-primary"></i></button>
															<button type="button" id="saveTypedocument" class="btn btn-secondary btn-rounded" style="display: none;"><i class="fa fa-check color-secondary"></i></button>
														</div>
													</div>
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Téléverser le document*</label>
													<input type="file" class="form-control input-rounded form-file-input" name="justificatif" id="justificatif" placeholder="Justificatif" accept="image/jpeg,image/png,application/pdf">
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Document à partager</label>
													<div class="mb-0">
														<label class="radio-inline me-3"><input type="radio" name="public" value="1" class="form-check-input" checked> Oui</label>
														<label class="radio-inline me-3"><input type="radio" name="public" value="0" class="form-check-input"> Non</label>
													</div>
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
                                <h4 class="card-title">Aperçu du document</h4>
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
    $documents = getDocument(
        null,
        $GLOBALS["id_copropriete"],
        null,
        $connection,
    );
    ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Documents</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="dashboard.php?page=documents&action=add" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-plus color-primary"></i></span> Ajouter
					</a>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Titre du document</th>
                                                <th>Type</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (
                                                $documents
                                                as $document
                                            ):
                                                $typedocument = getTypedocument(
                                                    $document[
                                                        "id_typedocument"
                                                    ],
                                                    $connection,
                                                ); ?>
											<tr>
                                                <td><?= date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $document["date"],
                                                    ),
                                                ) ?></td>
                                                <td><?= $document[
                                                    "titre"
                                                ] ?></td>
                                                <td><?= $typedocument[0][
                                                    "libelle"
                                                ] ?></td>
                                                <td class="text-center">
													<?php if (
                 $_SESSION["id_usertype"] === "1" ||
                 $_SESSION["id_usertype"] === "2" ||
                 $_SESSION["id_usertype"] === "3"
             ): ?>
													<a href="./dashboard.php?page=documents&action=update&id=<?= $document[
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
													<?php else: ?>
													<a href="./dashboard.php?page=documents&action=update&id=<?= $document[
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-eye"></i></a>
													<?php endif; ?>
												</td>
                                            </tr>
											<?php
                                            endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Date</th>
                                                <th>Titre du document</th>
                                                <th>Type</th>
                                                <th class="text-center">Actions</th>
                                        </tfoot>
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
