<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
if (
    isset(
        $_POST["id_reclamation"],
        $_POST["id_statutReclamation"],
        $_POST["commentaire"],
    )
) {
    if (
        !isset($_SESSION["id"]) ||
        (isset($_SESSION["id"]) && !is_int(intval($_SESSION["id"])))
    ) {
        echo "Une erreur est survenue !";
        exit();
    }

    $id_reclamation = filter_input(
        INPUT_POST,
        "id_reclamation",
        FILTER_SANITIZE_STRING,
    );
    $id_statutReclamation = filter_input(
        INPUT_POST,
        "id_statutReclamation",
        FILTER_SANITIZE_STRING,
    );
    $commentaire = filter_input(
        INPUT_POST,
        "commentaire",
        FILTER_SANITIZE_STRING,
    );

    if ($commentaire != "") {
        $request = "INSERT INTO message (date, id_syndic, commentaire, id_reclamation) 
					VALUES (?, ?, ?, ?)";
        $date = date("Y-m-d H:i:s");
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssss",
                $date,
                $_SESSION["id"],
                $commentaire,
                $id_reclamation,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_message = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $action = "a ajouté|message2|" . $id_message;
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
    }
    $date = null;
    if ($id_statutReclamation == "2") {
        $date = date("Y-m-d H:i:s");
    }
    $request =
        "UPDATE reclamation SET id_statutReclamation=?, dateFermeture=? WHERE id=?";
    if ($insert_stmt = $connection->prepare($request)) {
        $insert_stmt->bind_param(
            "sss",
            $id_statutReclamation,
            $date,
            $id_reclamation,
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
        $action = "a modifié|reclamation|" . $id_reclamation;
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
    if ($commentaire != "") {
        $syndic = getSyndic($_SESSION["id"], null, $connection);
        echo "done|" .
            date("d/m/Y") .
            "|" .
            $syndic[0]["civilite"] .
            " " .
            $syndic[0]["prenom"] .
            " " .
            $syndic[0]["nom"] .
            "|" .
            $commentaire;
    } else {
        echo "done|X";
    }
    exit();
}
if (isset($_GET["action"], $_GET["id"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
    $reclamation = getReclamation($id, null, null, $connection);
    if (count($reclamation) == 0) {
        goto iomEnd;
    }
    $date = date("d/m/Y", strtotime($reclamation[0]["date"]));
    if ($action == "update" && $id != ""):

        $request =
            "UPDATE notificationsyndic SET seen = 1 WHERE idPage = ? AND nomPage = 'reclamations'";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $id);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Messages</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=reclamations" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="reclamations">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card read-page">
                            <div class="card-body pt-0">
                                <div class="ms-0 ms-sm-0">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="right-box-padding py-4">
                                                <div class="read-content">
                                                    <div class="media pt-3 d-sm-flex d-block justify-content-between">
														<div class="clearfix mb-3 d-flex">
															<?php
               $preuves = glob(
                   "./justificatifs/reclamations/" .
                       $reclamation[0]["id"] .
                       ".*",
               );
               if (count($preuves) == 0): ?>
															<img class="me-3 rounded" height="70" alt="image" src="justificatifs\reclamations\no-image.jpg">
															<?php else: ?>
															<div id="lightgallery">
																<a href="<?= $preuves[0] ?>" data-exthumbimage="<?= $preuves[0] ?>" data-src="<?= $preuves[0] ?>" class="col-lg-3 col-md-6 mb-4">
																	<img class="me-3 rounded" height="70" alt="image" src="<?= $preuves[0] ?>">
																</a>
															</div>
															<?php endif;
               ?>
															<div class="media-body me-2">
																<input type="hidden" name="id_reclamation" value="<?= $reclamation[0]["id"] ?>">
																<h5 class="text-primary mb-0 mt-1">Objet : <?= $reclamation[0]["objet"] ?></h5>
																<p class="mb-0">Crée le : <?= $date ?></p>
															</div>
														</div>
														<div class="clearfix mb-3">
															<style>
															<!--
															.nice-select.form-control {
																line-height: 2.2rem;
																min-width: 150px;
															}
															-->
															</style>
															<select name="id_statutReclamation" class="default-select form-control input-rounded wide mb-3">
																<?php
                $statutReclamations = getStatutreclamation(null, $connection);
                foreach ($statutReclamations as $statutReclamation): ?>
																<option value="<?= $statutReclamation["id"] ?>" <?php if (
    $statutReclamation["id"] == $reclamation[0]["id_statutReclamation"]
) {
    echo "selected";
} ?>><?= $statutReclamation["libelle"] ?></option>
																<?php endforeach;
                ?>
															</select>
														</div>
													</div>
                                                    <hr>
													<?php
             $messages = getMessages($reclamation[0]["id"], $connection);
             foreach ($messages as $message): ?>
                                                    <div class="media mb-2 mt-3">
                                                        <div class="media-body"><span class="pull-end"><?= date(
                                                            "d/m/Y",
                                                            strtotime(
                                                                $message[
                                                                    "date"
                                                                ],
                                                            ),
                                                        ) ?></span>
															<?php if ($message["id_syndic"] != null):
                   $syndic = getSyndic(
                       $message["id_syndic"],
                       null,
                       $connection,
                   ); ?>
                                                            <h5 class="my-1 text-primary"><?= $syndic[0][
                                                                "civilite"
                                                            ] ?> <?= $syndic[0][
     "prenom"
 ] ?> <?= $syndic[0]["nom"] ?></h5>
															<?php
               else:
                   $proprietaire = getProprietaire(
                       $message["id_proprietaire"],
                       null,
                       $connection,
                   ); ?>
															<h5 class="my-1 text-secondary"><?= $proprietaire[0][
                   "civilite"
               ] ?> <?= $proprietaire[0]["prenom"] ?> <?= $proprietaire[0][
     "nom"
 ] ?></h5>
															<?php
               endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="read-content-body">
                                                        <p><?= $message[
                                                            "commentaire"
                                                        ] ?></p>
                                                        <hr>
                                                    </div>
													<?php endforeach;
             ?>
                                                    <span id="newCommentaire" style="display: none;">
														<div class="media mb-2 mt-3">
															<div class="media-body"><span class="pull-end" id="newCommentaireDate"></span>
																<h5 class="my-1 text-primary" id="newCommentaireSyndic"></h5>
															</div>
														</div>
														<div class="read-content-body">
															<p id="newCommentaireMessage"></p>
															<hr>
														</div>
													</span>
													<div class="mb-3 pt-3">
                                                        <textarea name="commentaire" id="commentaire" cols="30" rows="5" class="form-control input-rounded" placeholder="Votre réponse ..." <?php if (
                                                            $reclamation[0][
                                                                "id_statutReclamation"
                                                            ] == "2"
                                                        ) {
                                                            echo 'style="display: none;"';
                                                        } ?>></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    elseif ($action == "view" && $id != ""):
        goto iomEnd; ?>

<?php
    else:
        goto iomEnd;
    endif;
elseif (isset($_GET["action"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    if ($action == "add"):
        goto iomEnd; ?>

<?php
    else:
        goto iomEnd;
    endif;
else:

    iomEnd:
    $reclamations = getReclamation(
        null,
        null,
        $GLOBALS["id_copropriete"],
        $GLOBALS["connection"],
    );
    ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Réclamations</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<a href="export/export_reclamation.php?id_copropriete=<?= $GLOBALS[
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
                                                <th>Date</th>
                                                <th>Propriétaire</th>
                                                <th>Objet</th>
                                                <th>Statut</th>
                                                <th>Date de fermeture</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach (
                                            $reclamations
                                            as $reclamation
                                        ):

                                            $lot = getLot(
                                                $reclamation["id_lot"],
                                                null,
                                                $GLOBALS["id_copropriete"],
                                                $connection,
                                            );
                                            $proprietaire = getProprietaire(
                                                $lot[0]["id_proprietaire"],
                                                null,
                                                $connection,
                                            );
                                            $statutReclamation = getStatutreclamation(
                                                $reclamation[
                                                    "id_statutReclamation"
                                                ],
                                                $connection,
                                            );
                                            ?>
                                            <tr>
                                                <td><?= date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $reclamation["date"],
                                                    ),
                                                ) ?></td>
                                                <td><?= $proprietaire[0][
                                                    "civilite"
                                                ] ?> <?= $proprietaire[0][
     "prenom"
 ] ?> <?= $proprietaire[0]["nom"] ?></td>
                                                <td><?= $reclamation[
                                                    "objet"
                                                ] ?></td>
                                                <td>
													<?php if ($reclamation["id_statutReclamation"] == "1") {
                 echo '<span class="badge badge-rounded badge-danger">' .
                     $statutReclamation[0]["libelle"] .
                     "</span>";
             } elseif ($reclamation["id_statutReclamation"] == "2") {
                 echo '<span class="badge badge-rounded badge-success">' .
                     $statutReclamation[0]["libelle"] .
                     "</span>";
             } ?>
												</td>
                                                <td>
													<?php if ($reclamation["dateFermeture"] != null) {
                 echo date("d/m/Y", strtotime($reclamation["dateFermeture"]));
             } else {
                 echo "N/A";
             } ?>
												</td>
                                                <td class="text-center">
													<a href="./dashboard.php?page=reclamations&action=update&id=<?= $reclamation[
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-eye"></i></a>
												</td>
                                            </tr>
                                            <?php
                                        endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Date</th>
                                                <th>Propriétaire</th>
                                                <th>Objet</th>
                                                <th>Statut</th>
                                                <th>Date de fermeture</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
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