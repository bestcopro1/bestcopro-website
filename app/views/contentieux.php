<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
if (isset($_POST["id"], $_POST["delete"])) {
    $error_msg = "";

    $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $delete = filter_input(INPUT_POST, "delete", FILTER_SANITIZE_STRING);

    if ($id != "" && $delete == "true") {
        $request = "DELETE FROM contentieux WHERE id_lot=?";
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
            $action = "a supprimé|contentieux|" . $id;
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
        $_POST["id_lot"],
        $_POST["dateContentieux"],
        $_POST["id_etat"],
        $_POST["remarque"],
    )
) {
    $error_msg = "";

    $id_lot = filter_input(INPUT_POST, "id_lot", FILTER_SANITIZE_STRING);
    $dateContentieux = filter_input(
        INPUT_POST,
        "dateContentieux",
        FILTER_SANITIZE_STRING,
    );
    $id_etat = filter_input(INPUT_POST, "id_etat", FILTER_SANITIZE_STRING);
    $remarque = filter_input(INPUT_POST, "remarque", FILTER_SANITIZE_STRING);
    if ($id_etat == "") {
        $error_msg .= "Veuillez sélectionner une état";
        echo $error_msg;
        exit();
    }
    if ($dateContentieux == "") {
        $error_msg .= "Veuillez entrer la date";
        echo $error_msg;
        exit();
    }
    if ($remarque == "") {
        $error_msg .= "Veuillez entrer une remarque";
        echo $error_msg;
        exit();
    }

    if (empty($error_msg)) {
        $request = "INSERT INTO contentieux (id_lot, date, id_etat, remarque) 
		VALUES (?, ?, ?, ?)";

        $insert_id = "";

        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssss",
                $id_lot,
                $dateContentieux,
                $id_etat,
                $remarque,
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
            $action = "a ajouté|contentieux|" . $insert_id;
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

        if ($id_etat == "1") {
            $request = "INSERT INTO echeance (description, date, jourAlerte, id_copropriete) 
			VALUES (?, ?, ?, ?)";
            $lot = getLot($id_lot, null, null, $connection);
            $description =
                "Rappel : Contentieux pour le lot " . $lot[0]["code"];
            $date = date("Y-m-d H:i:s", strtotime("+32 days"));
            $jourAlerte = 0;
            $id_copropriete = $lot[0]["id_copropriete"];
            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param(
                    "ssss",
                    $description,
                    $date,
                    $jourAlerte,
                    $id_copropriete,
                );
                // Execute the prepared query.
                if (!$insert_stmt->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
        }

        $etat = getEtat($id_etat, $connection);
        if ($id_etat == "1") {
            $badgeColor = "warning";
        } elseif ($id_etat == "2") {
            $badgeColor = "danger";
        } elseif ($id_etat == "3") {
            $badgeColor = "dark";
        } elseif ($id_etat == "4") {
            $badgeColor = "info";
        } elseif ($id_etat == "5") {
            $badgeColor = "success";
        }
        $codeHtml = "<li>";
        $codeHtml .= '<div class="timeline-badge ' . $badgeColor . '"></div>';
        $codeHtml .= '<div class="timeline-panel">';
        $codeHtml .=
            "<span>" . date("d/m/Y", strtotime($dateContentieux)) . "</span>";
        $codeHtml .= '<h6 class="mb-0">' . $etat[0]["libelle"] . "</h6>";
        $codeHtml .= '<p class="mb-0">' . $remarque . "</p>";
        $codeHtml .= "</div>";
        $codeHtml .= "</li>";

        echo "done|" . $insert_id . "|" . $codeHtml;
        exit();
    } else {
        echo $error_msg;
        exit();
    }
}
if (isset($_GET["action"], $_GET["id"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
    $lot = getLot($id, null, null, $connection);
    if (count($lot) == 0) {
        goto iomEnd;
    }
    if ($action == "update" && $id != ""):

        $typeLot = getTypelot($lot[0]["id_typeLot"], $connection);
        $proprietaire = getProprietaire(
            $lot[0]["id_proprietaire"],
            null,
            $connection,
        );
        $typeproprietaire = getTypeproprietaire(
            $lot[0]["id_typeProprietaire"],
            $connection,
        );
        $contentieuxs = getContentieux($lot[0]["id"], null, $connection);
        ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Code du lot : <?= $lot[0][
          "code"
      ] ?></h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=contentieux" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Retour à la liste</a>
				</div>
				<div class="row">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header">
                                <h4 class="card-title me-auto">Informations</h4>
							</div>
							<div class="card-body">
								<div class="row">
									<div class="col-xl-6 col-md-6">
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Type du lot : </span><span class="font-w400"><?= $typeLot[0][
              "libelle"
          ] ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Numéro d'immeuble : </span><span class="font-w400"><?= $lot[0][
              "numeroImm"
          ] ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Numéro d'étage : </span><span class="font-w400"><?= $lot[0][
              "etage"
          ] ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Numéro du lot : </span><span class="font-w400"><?= $lot[0][
              "numero"
          ] ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Titre foncier : </span><span class="font-w400"><?php if (
              $lot[0]["foncier"] != ""
          ) {
              echo $lot[0]["foncier"];
          } else {
              echo "N/A";
          } ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Tantième : </span><span class="font-w400"><?= floatval(
              $lot[0]["tantieme"],
          ) ?></span></p>
									</div>
									<div class="col-xl-6 col-md-6">
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Propriétaire :</span> <span class="font-w400"><?= $proprietaire[0][
              "civilite"
          ] ?> <?= $proprietaire[0]["prenom"] ?> <?= $proprietaire[0][
     "nom"
 ] ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Type du propriétaire :</span> <span class="font-w400"><?= $typeproprietaire[0][
              "libelle"
          ] ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Email : </span><span class="font-w400"><?php if (
              $proprietaire[0]["email"] != ""
          ) {
              echo $proprietaire[0]["email"];
          } else {
              echo "N/A";
          } ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Téléphone :</span> <span class="font-w400"><?php if (
              $proprietaire[0]["telephone"] != ""
          ) {
              echo $proprietaire[0]["telephone"];
          } else {
              echo "N/A";
          } ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Mobile : </span><span class="font-w400"><?php if (
              $proprietaire[0]["mobile"] != ""
          ) {
              echo $proprietaire[0]["mobile"];
          } else {
              echo "N/A";
          } ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Date d'acquisition :</span> <span class="font-w400"><?= date(
              "d/m/Y",
              strtotime($lot[0]["dateAcquisition"]),
          ) ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Date de remise des clés : </span><span class="font-w400"><?= date(
              "d/m/Y",
              strtotime($lot[0]["dateRemiseCle"]),
          ) ?></span></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
    $style = "";
    if (count($contentieuxs) > 0) {
        $style = "";
    } else {
        $style = 'style="display: none;"';
    }
    ?>
				<div class="row" <?= $style ?> id="Historique">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header">
                                <h4 class="card-title me-auto">Historique</h4>
							</div>
							<div class="card-body">
								<div id="DZ_W_TimeLine" class="widget-timeline dlab-scroll px-4">
                                    <ul class="timeline">
                                        <?php foreach (
                                            $contentieuxs
                                            as $contentieux
                                        ):

                                            $etat = getEtat(
                                                $contentieux["id_etat"],
                                                $connection,
                                            );
                                            if (
                                                $contentieux["id_etat"] == "1"
                                            ) {
                                                $badgeColor = "warning";
                                            } elseif (
                                                $contentieux["id_etat"] == "2"
                                            ) {
                                                $badgeColor = "danger";
                                            } elseif (
                                                $contentieux["id_etat"] == "3"
                                            ) {
                                                $badgeColor = "dark";
                                            } elseif (
                                                $contentieux["id_etat"] == "4"
                                            ) {
                                                $badgeColor = "info";
                                            } elseif (
                                                $contentieux["id_etat"] == "5"
                                            ) {
                                                $badgeColor = "success";
                                            }
                                            ?>
										<li>
                                            <div class="timeline-badge <?= $badgeColor ?>"></div>
                                            <div class="timeline-panel">
                                                <span><?= date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $contentieux["date"],
                                                    ),
                                                ) ?></span>
                                                <h6 class="mb-0"><?= $etat[0][
                                                    "libelle"
                                                ] ?></h6>
												<p class="mb-0"><?= $contentieux["remarque"] ?></p>
                                            </div>
                                        </li>
										<?php
                                        endforeach; ?>
                                    </ul>
                                </div>
							</div>
						</div>
					</div>
				</div>
				<?php if (
        $_SESSION["id_usertype"] === "1" ||
        $_SESSION["id_usertype"] === "2" ||
        $_SESSION["id_usertype"] === "3"
    ): ?>
				<div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title me-auto">Modifier l'état du contentieux</h4>
                            </div>
							<div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-6 mb-2">
												<div class="form-group">
													<input type="hidden" name="id_lot" value="<?= $lot[0]["id"] ?>">
													<label class="text-label">Date*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateContentieux" placeholder="Date" value="">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">État*</label>
													<select name="id_etat" class="default-select form-control input-rounded wide mb-3">
														<?php
              $etats = getEtat(null, $connection);
              foreach ($etats as $etat): ?>
														<option value="<?= $etat["id"] ?>"><?= $etat["libelle"] ?></option>
														<?php endforeach;
              ?>
													</select>
												</div>
											</div>
											<div class="col-12 mb-2">
												<div class="form-group">
													<label class="text-label">Remarque*</label>
													<textarea name="remarque" id="remarque" class="form-control input-rounded" placeholder="Votre remarque ..."></textarea>
												</div>
											</div>
											<div class="col-12 mb-2">
												<div class="form-group text-end">
													<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveContentieux" data-url="contentieux">Enregistrer</button>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
                    </div>
                </div>
				<?php endif; ?>
			</div>
        </div>
<?php
    else:
        goto iomEnd;
    endif;
else:

    iomEnd:
    $contentieuxs = getContentieux(
        null,
        $GLOBALS["id_copropriete"],
        $connection,
    );
    ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Contentieux</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Code du lot</th>
                                                <th>Propriétaire</th>
                                                <th>Etat</th>
                                                <th>Date d'état</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
											<?php foreach ($contentieuxs as $contentieux):

               $lot = getLot($contentieux["id_lot"], null, null, $connection);
               $proprietaire = getProprietaire(
                   $lot[0]["id_proprietaire"],
                   null,
                   $connection,
               );
               $etat = getEtat($contentieux["id_etat"], $connection);
               ?>
                                            <tr class="trContentieux-<?= $lot[0][
                                                "id"
                                            ] ?>">
                                                <td><?= $lot[0]["code"] ?></td>
                                                <td><?= $proprietaire[0][
                                                    "civilite"
                                                ] ?> <?= $proprietaire[0][
     "prenom"
 ] ?> <?= $proprietaire[0]["nom"] ?></td>
												<td><?= $etat[0]["libelle"] ?></td>
                                                <td><?= date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $contentieux["date"],
                                                    ),
                                                ) ?></td>
                                                <td class="text-center">
													<?php if (
                 $_SESSION["id_usertype"] === "1" ||
                 $_SESSION["id_usertype"] === "2" ||
                 $_SESSION["id_usertype"] === "3"
             ): ?>
													<a href="./dashboard.php?page=contentieux&action=update&id=<?= $lot[0][
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
													<?php else: ?>
													<a href="./dashboard.php?page=contentieux&action=update&id=<?= $lot[0][
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-eye"></i></a>
													<?php endif; ?>
													<?php if ($_SESSION["id_usertype"] === "1"): ?>
													<a href="javascript:void(0);" class="btn btn-secondary shadow btn-xs sharp me-1" data-bs-toggle="modal" data-bs-target=".delContentieux-<?= $lot[0][
                 "id"
             ] ?>"><i class="fas fa-trash"></i></a>
													<?php endif; ?>
												</td>
                                            </tr>
											<?php if ($_SESSION["id_usertype"] === "1"): ?>
											<!-- Modal -->
											<div class="modal fade delContentieux-<?= $lot[0]["id"] ?>">
												<div class="modal-dialog modal-dialog-centered" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title">Supprimer le contentieux</h5>
															<button type="button" class="btn-close" data-bs-dismiss="modal">
															</button>
														</div>
														<div class="modal-body">
															<div class="text-center mb-4"><i class="fas fa-exclamation-triangle" style="font-size: 111px;"></i></div>
															<div class="text-center">Êtes-vous sûr de vouloir supprimer ce contentieux ?</div>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Non</button>
															<button type="button" class="btn btn-rounded btn-danger delContentieuxBtn" data-id="<?= $lot[0][
                   "id"
               ] ?>">Oui</button>
														</div>
													</div>
												</div>
											</div>
											<?php endif; ?>
                                            <?php
           endforeach; ?>
                                            
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Code du lot</th>
                                                <th>Propriétaire</th>
                                                <th>Etat</th>
                                                <th>Date d'état</th>
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
