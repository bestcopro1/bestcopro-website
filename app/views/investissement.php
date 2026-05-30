<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
function getDepenseByRubriqueOrPoste(
    $id_rubrique = null,
    $id_poste = null,
    $connection,
) {
    if ($id_rubrique != null) {
        $request =
            "SELECT id, id_poste, date, montant, id_fournisseur, id_modePaiement, commentaire, id_exercice, id_syndic FROM depense WHERE id_poste IN (SELECT id FROM poste WHERE id_rubrique = ?)";
    } elseif ($id_poste != null) {
        $request =
            "SELECT id, id_poste, date, montant, id_fournisseur, id_modePaiement, commentaire, id_exercice, id_syndic FROM depense WHERE id_poste = ?";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id_rubrique != null) {
            $stmt->bind_param("s", $id_rubrique);
        } elseif ($id_poste != null) {
            $stmt->bind_param("s", $id_poste);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $id_poste,
                $date,
                $montant,
                $id_fournisseur,
                $id_modePaiement,
                $commentaire,
                $id_exercice,
                $id_syndic,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "id_poste" => $id_poste,
                    "date" => $date,
                    "montant" => $montant,
                    "id_fournisseur" => $id_fournisseur,
                    "id_modePaiement" => $id_modePaiement,
                    "commentaire" => $commentaire,
                    "id_exercice" => $id_exercice,
                    "id_syndic" => $id_syndic,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
if (isset($_POST["rubrique2_1"], $_POST["id_exercice"])) {
    $error_msg = "";

    $id_exercice = filter_input(
        INPUT_POST,
        "id_exercice",
        FILTER_SANITIZE_STRING,
    );
    if ($id_exercice != "") {
        $exercice = getExercice($id_exercice, null, $connection);
        $montantTotal = filter_input(
            INPUT_POST,
            "montantTotal",
            FILTER_SANITIZE_STRING,
        );
        if (
            number_format(floatval($montantTotal)) !=
            number_format(floatval($exercice[0]["montantInvest"]))
        ) {
            $error_msg .=
                "Le budget annuel de fonctionnement doit être égal à " .
                $exercice[0]["montantInvest"] .
                " MAD";
            echo $error_msg;
            exit();
        }
        $id_typeRubrique = 2;
        $id_rubrique = "";
        $i = 1;
        while (isset($_POST["rubrique2_" . $i])) {
            $rubrique = filter_input(
                INPUT_POST,
                "rubrique2_" . $i,
                FILTER_SANITIZE_STRING,
            );
            if ($rubrique != "") {
                if (isset($_POST["rubrique2_" . $i . "_id"])) {
                    $id_rubrique = filter_input(
                        INPUT_POST,
                        "rubrique2_" . $i . "_id",
                        FILTER_SANITIZE_STRING,
                    );
                    $request = "UPDATE rubrique SET libelle = ? WHERE id = ?";
                    if ($insert_stmt = $connection->prepare($request)) {
                        $insert_stmt->bind_param("ss", $rubrique, $id_rubrique);
                        // Execute the prepared query.
                        if (!$insert_stmt->execute()) {
                            echo $connection->error;
                            exit();
                        }
                    }
                    $id_rubrique = $connection->insert_id;
                    if (
                        $insert_stmt_history = $connection->prepare(
                            "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
                        )
                    ) {
                        $date = date("Y-m-d H:i:s");
                        $action = "a modifié|rubrique|" . $id_rubrique;
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
                } else {
                    $request = "INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique) 
					VALUES (?, ?, ?)";
                    if ($insert_stmt = $connection->prepare($request)) {
                        $insert_stmt->bind_param(
                            "sss",
                            $rubrique,
                            $id_exercice,
                            $id_typeRubrique,
                        );
                        // Execute the prepared query.
                        if (!$insert_stmt->execute()) {
                            echo $connection->error;
                            exit();
                        }
                    }
                    $id_rubrique = $connection->insert_id;
                    if (
                        $insert_stmt_history = $connection->prepare(
                            "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
                        )
                    ) {
                        $date = date("Y-m-d H:i:s");
                        $action = "a ajouté|rubrique|" . $id_rubrique;
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
                $id_poste = "";
                $j = 1;
                while (isset($_POST["rubrique2_" . $i . "_poste2_" . $j])) {
                    $poste = filter_input(
                        INPUT_POST,
                        "rubrique2_" . $i . "_poste2_" . $j,
                        FILTER_SANITIZE_STRING,
                    );
                    $poste_value = filter_input(
                        INPUT_POST,
                        "rubrique2_" . $i . "_poste2_" . $j . "_value",
                        FILTER_SANITIZE_STRING,
                    );
                    $poste_value = floatval($poste_value);
                    if ($poste != "" && $poste_value > 0) {
                        if (
                            isset(
                                $_POST[
                                    "rubrique2_" . $i . "_poste2_" . $j . "_id"
                                ],
                            )
                        ) {
                            $id_poste = filter_input(
                                INPUT_POST,
                                "rubrique2_" . $i . "_poste2_" . $j . "_id",
                                FILTER_SANITIZE_STRING,
                            );
                            $request =
                                "UPDATE poste SET libelle = ? , montant = ? WHERE id = ?";
                            if ($insert_stmt = $connection->prepare($request)) {
                                $insert_stmt->bind_param(
                                    "sss",
                                    $poste,
                                    $poste_value,
                                    $id_poste,
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
                                $action = "a modifié|poste|" . $id_poste;
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
                        } else {
                            $request = "INSERT INTO poste (libelle, montant, id_rubrique) 
							VALUES (?, ?, ?)";
                            if ($insert_stmt = $connection->prepare($request)) {
                                $insert_stmt->bind_param(
                                    "sss",
                                    $poste,
                                    $poste_value,
                                    $id_rubrique,
                                );
                                // Execute the prepared query.
                                if (!$insert_stmt->execute()) {
                                    echo $connection->error;
                                    exit();
                                }
                            }
                            $id_poste = $connection->insert_id;
                            if (
                                $insert_stmt_history = $connection->prepare(
                                    "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
                                )
                            ) {
                                $date = date("Y-m-d H:i:s");
                                $action = "a ajouté|poste|" . $id_poste;
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
                    } elseif ($poste == "" && $poste_value == 0) {
                        if (
                            isset(
                                $_POST[
                                    "rubrique2_" . $i . "_poste2_" . $j . "_id"
                                ],
                            )
                        ) {
                            $id_poste = filter_input(
                                INPUT_POST,
                                "rubrique2_" . $i . "_poste2_" . $j . "_id",
                                FILTER_SANITIZE_STRING,
                            );
                            $request = "DELETE FROM poste WHERE id = ?";
                            if ($insert_stmt = $connection->prepare($request)) {
                                $insert_stmt->bind_param("s", $id_poste);
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
                                $action = "a supprimé|poste|" . $id_poste;
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
                    }
                    $j = $j + 1;
                }
            } else {
                if (isset($_POST["rubrique2_" . $i . "_id"])) {
                    $id_rubrique = filter_input(
                        INPUT_POST,
                        "rubrique2_" . $i . "_id",
                        FILTER_SANITIZE_STRING,
                    );
                    $request = "DELETE FROM rubrique WHERE id = ?";
                    if ($insert_stmt = $connection->prepare($request)) {
                        $insert_stmt->bind_param("s", $id_rubrique);
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
                        $action = "a supprimé|rubrique|" . $id_rubrique;
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
            }
            $i = $i + 1;
        }
        echo "done|X";
        exit();
    }
}
$disabled = "";
if ($_SESSION["id_usertype"] !== "1") {
    $disabled = "disabled";
}
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Budget d'investissement</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<?php if ($_SESSION["id_usertype"] === "1"): ?>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="investissement">Enregistrer les modifications</button>
					<?php endif; ?>
				</div>
				<?php $exercice = getExercice($GLOBALS["id_exercice"], null, $connection); ?>
				<input type="hidden" name="id_exercice" value="<?= $GLOBALS["id_exercice"] ?>">
				<input type="hidden" name="montantTotal" id="montantTotal" value="<?= $exercice[0][
        "montantInvest"
    ] ?>">
				<div class="alert alert-primary alert-alt fade show p-3 mb-4">
					<div class="row">
						<div class="col-lg-6">
							<strong>Budget annuel d'investissement de l'<?= getNameexercice(
           $exercice[0]["dateDebut"],
       ) ?></strong>
						</div>
						<div class="col-lg-6 text-end">
							<strong>TOTAL = <span id="totalBudget" class="font-w500"><?= $exercice[0][
           "montantInvest"
       ] ?></span> MAD</strong>
						</div>
					</div>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<?php
        $rubriques = getRubrique(null, null, 2, $connection);
        $i = 1;
        if (count($rubriques) > 0):
            foreach ($rubriques as $rubrique):
                $depensesByRubrique = getDepenseByRubriqueOrPoste(
                    $rubrique["id"],
                    null,
                    $connection,
                ); ?>
								<div class="basic-list-group rubrique2_<?= $i ?>">
									<ul class="list-group">
										<li class="list-group-item active">
											<?php if ($_SESSION["id_usertype"] === "1"):
               if (count($depensesByRubrique) > 0): ?>
											<div class="row">
												<div class="col-12">
													<input type="hidden" name="rubrique2_<?= $i ?>_id" value="<?= $rubrique[
    "id"
] ?>">
													<input type="text" class="form-control input-rounded" name="rubrique2_<?= $i ?>" placeholder="Nouvelle rubrique" value="<?= $rubrique[
    "libelle"
] ?>">
												</div>
											</div>
											<?php else: ?>
											<div class="row">
												<div class="col-11">
													<input type="hidden" name="rubrique2_<?= $i ?>_id" value="<?= $rubrique[
    "id"
] ?>">
													<input type="text" class="form-control input-rounded" name="rubrique2_<?= $i ?>" placeholder="Nouvelle rubrique" value="<?= $rubrique[
    "libelle"
] ?>">
												</div>
												<div class="col-1">
													<button type="button" class="btn btn-outline-secondary btn-rounded del_rubrique2" data-rubrique2="<?= $i ?>"><i class="fa fa-trash"></i></button>
												</div>
											</div>
											<?php endif;
           else:
                ?>
											<div class="col-12">
													<input type="hidden" name="rubrique2_<?= $i ?>_id" value="<?= $rubrique[
    "id"
] ?>">
													<input type="text" class="form-control input-rounded" name="rubrique2_<?= $i ?>" placeholder="Nouvelle rubrique" value="<?= $rubrique[
    "libelle"
] ?>" disabled>
												</div>
											<?php
           endif; ?>
										</li>
										<?php
          $postes = getPoste(null, null, $rubrique["libelle"], $connection);
          $j = 1;
          if (count($postes) > 0):
              foreach ($postes as $poste):
                  $depensesByPoste = getDepenseByRubriqueOrPoste(
                      null,
                      $poste["id"],
                      $connection,
                  ); ?>
											<li class="list-group-item rubrique2_<?= $i ?>_poste2_<?= $j ?>">
												<div class="row">
													<div class="col-6">
														<input type="hidden" name="rubrique2_<?= $i ?>_poste2_<?= $j ?>_id" value="<?= $poste[
    "id"
] ?>">
														<input type="text" class="form-control input-rounded" name="rubrique2_<?= $i ?>_poste2_<?= $j ?>" placeholder="Nouveau poste" value="<?= $poste[
    "libelle"
] ?>" <?= $disabled ?>>
													</div>
													<?php if ($_SESSION["id_usertype"] === "1"):
                 if (count($depensesByPoste) > 0): ?>
													<div class="col-6">
														<input type="number" class="form-control input-rounded value" name="rubrique2_<?= $i ?>_poste2_<?= $j ?>_value" placeholder="0.00" value="<?= $poste[
    "montant"
] ?>">
													</div>
													<?php else: ?>
													<div class="col-5">
														<input type="number" class="form-control input-rounded value" name="rubrique2_<?= $i ?>_poste2_<?= $j ?>_value" placeholder="0.00" value="<?= $poste[
    "montant"
] ?>">
													</div>
													<div class="col-1">
														<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste2" data-rubrique2="<?= $i ?>" data-poste2="<?= $j ?>"></a>
													</div>
													<?php endif;
             else:
                  ?>
													<div class="col-6">
														<input type="number" class="form-control input-rounded value" name="rubrique_<?= $i ?>_poste_<?= $j ?>_value" placeholder="0.00" value="<?= $poste[
    "montant"
] ?>" disabled>
													</div>
													<?php
             endif; ?>
												</div>
											</li>
											<?php $j += 1;
              endforeach;
          elseif ($_SESSION["id_usertype"] === "1"): ?>
											<li class="list-group-item rubrique2_<?= $i ?>_poste2_1">
												<div class="row">
													<div class="col-6">
														<input type="text" class="form-control input-rounded" name="rubrique2_<?= $i ?>_poste2_1" placeholder="Nouveau poste" value="">
													</div>
													<div class="col-5">
														<input type="number" class="form-control input-rounded value" name="rubrique2_<?= $i ?>_poste2_1_value" placeholder="0.00" value="">
													</div>
													<div class="col-1">
														<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste2" data-rubrique2="<?= $i ?>" data-poste2="2"></a>
													</div>
												</div>
											</li>
										<?php endif;
          ?>
										<?php if ($_SESSION["id_usertype"] === "1"): ?>
										<li class="list-group-item">
											<div class="row">
												<div class="col-12">
													<a href="#" class="btn light btn-primary btn-block add_poste2" data-rubrique2="<?= $i ?>" data-poste2="<?= $j ?>">Ajouter un poste</a>
												</div>
											</div>
										</li>
										<?php endif; ?>
									</ul>
								</div>
								<?php $i += 1;
            endforeach;
        elseif ($_SESSION["id_usertype"] === "1"):
            $i += 1; ?>
								<div class="basic-list-group rubrique2_1">
									<ul class="list-group">
										<li class="list-group-item active">
											<div class="row">
												<div class="col-11">
													<input type="text" class="form-control input-rounded" name="rubrique2_1" placeholder="Nouvelle rubrique" value="">
												</div>
												<div class="col-1">
													<button type="button" class="btn btn-outline-secondary btn-rounded del_rubrique2" data-rubrique2="1"><i class="fa fa-trash"></i></button>
												</div>
											</div>
										</li>
										<li class="list-group-item rubrique2_1_poste2_1">
											<div class="row">
												<div class="col-6">
													<input type="text" class="form-control input-rounded" name="rubrique2_1_poste2_1" placeholder="Nouveau poste" value="">
												</div>
												<div class="col-5">
													<input type="number" class="form-control input-rounded value" name="rubrique2_1_poste2_1_value" placeholder="0.00" value="">
												</div>
												<div class="col-1">
													<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste2" data-rubrique2="1" data-poste2="1"></a>
												</div>
											</div>
										</li>
										<li class="list-group-item">
											<div class="row">
												<div class="col-12">
													<a href="#" class="btn light btn-primary btn-block add_poste2" data-rubrique2="1" data-poste2="2">Ajouter un poste</a>
												</div>
											</div>
										</li>
									</ul>
								</div>
								<?php
        endif;
        ?>
								<?php if ($_SESSION["id_usertype"] === "1"): ?>
								<div class="row mt-4 ">
									<div class="col-12">
										<a href="#" class="btn btn-outline-primary btn-block add_rubrique2" data-rubrique2="<?= $i ?>">Ajouter une rubrique</a>
									</div>
								</div>
								<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
        </div>