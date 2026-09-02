<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

if (!$connection) {
    http_response_code(500);
    echo "error|Connexion a la base de donnees indisponible.";
    exit();
}

if (!isset($_SESSION["id"])) {
    http_response_code(401);
    echo "error|Votre session a expire. Veuillez vous reconnecter.";
    exit();
}

function parseAllocationAmount($value, &$valid)
{
    $normalized = str_replace([" ", "\xc2\xa0", "\xa0"], "", trim((string) $value));
    $normalized = str_replace(",", ".", $normalized);
    $valid = $normalized !== "" && is_numeric($normalized);
    return $valid ? (float) $normalized : 0.0;
}

if (
    isset(
        $_POST["id_copropriete"],
        $_POST["id_exercice"],
        $_POST["nom"],
        $_POST["adresse"],
        $_POST["ville"],
        $_POST["codePostale"],
        $_POST["rib"],
        $_POST["nbrLot"],
        $_POST["dateExercice"],
        $_POST["id_syndic"],
        $_POST["prefixe"],
    )
) {
    $id_copropriete = $_POST["id_copropriete"] ?? "";
    $id_exercice = $_POST["id_exercice"] ?? "";
    $nom = $_POST["nom"] ?? "";
    $adresse = $_POST["adresse"] ?? "";
    $ville = $_POST["ville"] ?? "";
    $codePostale = $_POST["codePostale"] ?? "";
    $rib = $_POST["rib"] ?? "";
    $nbrLot = $_POST["nbrLot"] ?? "";
    $dateExercice = $_POST["dateExercice"] ?? "";
    $id_syndic = $_POST["id_syndic"] ?? "";
    $prefixe = $_POST["prefixe"] ?? "";

    if (empty($id_syndic) && isset($_SESSION["id"])) {
        $id_syndic = $_SESSION["id"];
    }

    if ($id_copropriete != "" && $id_exercice != "") {
        $request =
            "UPDATE copropriete SET nom = ?, adresse = ?, ville = ?, codePostale = ?, rib = ?, nbrLot = ?, dateExercice = ?, id_syndic = ?, prefixe = ? WHERE id = ?";
        $dateExercice = $dateExercice . "-01";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssssssssss",
                $nom,
                $adresse,
                $ville,
                $codePostale,
                $rib,
                $nbrLot,
                $dateExercice,
                $id_syndic,
                $prefixe,
                $id_copropriete,
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
            $action = "a modifié|copropriete|" . $id_copropriete;
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
        $request =
            "UPDATE exercice SET dateDebut = ?, dateFin = ? WHERE id = ?";
        $dateDebut = $dateExercice;
        $dateFin = date(
            "Y-m-d",
            strtotime(
                date("Y-m-d", strtotime($dateDebut)) . " + 1 year - 1 day",
            ),
        );
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("sss", $dateDebut, $dateFin, $id_exercice);
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
            $action = "a modifié|exercice|" . $id_exercice;
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
        echo "done|" . $id_copropriete . "|" . $id_exercice;
        exit();
    } else {
        $request = "INSERT INTO copropriete (nom, adresse, ville, codePostale, rib, nbrLot, dateExercice, id_syndic, prefixe) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $dateExercice = $dateExercice . "-01";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "sssssssss",
                $nom,
                $adresse,
                $ville,
                $codePostale,
                $rib,
                $nbrLot,
                $dateExercice,
                $id_syndic,
                $prefixe,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_copropriete = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a ajouté|copropriete|" . $id_copropriete;
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
        $request = "INSERT INTO exercice (dateDebut, dateFin, id_periodePaiement, id_repartitionFonct, id_repartitionInvest, montantFonct, montantInvest, id_copropriete) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $dateDebut = $dateExercice;
        $dateFin = date(
            "Y-m-d",
            strtotime(
                date("Y-m-d", strtotime($dateDebut)) . " + 1 year - 1 day",
            ),
        );
        $id_periodePaiement = 1;
        $id_repartitionFonct = 1;
        $id_repartitionInvest = 1;
        $montantFonct = 0;
        $montantInvest = 0;
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssssssss",
                $dateDebut,
                $dateFin,
                $id_periodePaiement,
                $id_repartitionFonct,
                $id_repartitionInvest,
                $montantFonct,
                $montantInvest,
                $id_copropriete,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_exercice = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a ajouté|exercice|" . $id_exercice;
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
        echo "done|" . $id_copropriete . "|" . $id_exercice;
        exit();
    }
}
if (isset($_POST["rubrique_1"], $_POST["rubrique2_1"], $_POST["id_exercice"])) {
    $id_exercice = filter_input(
        INPUT_POST,
        "id_exercice",
        FILTER_SANITIZE_STRING,
    );
    if ($id_exercice != "") {
        $request =
            "DELETE FROM poste WHERE id_rubrique IN (SELECT id FROM rubrique WHERE id_exercice = ?)";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $id_exercice);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $request = "DELETE FROM rubrique WHERE id_exercice = ?";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $id_exercice);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $i = 1;
        while (isset($_POST["rubrique_" . $i])) {
            $rubrique = filter_input(
                INPUT_POST,
                "rubrique_" . $i,
                FILTER_SANITIZE_STRING,
            );
            if ($rubrique != "") {
                $request = "INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique) 
				VALUES (?, ?, ?)";
                $id_typeRubrique = 1;
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
                $j = 1;
                while (isset($_POST["rubrique_" . $i . "_poste_" . $j])) {
                    $poste = filter_input(
                        INPUT_POST,
                        "rubrique_" . $i . "_poste_" . $j,
                        FILTER_SANITIZE_STRING,
                    );
                    $poste_value = filter_input(
                        INPUT_POST,
                        "rubrique_" . $i . "_poste_" . $j . "_value",
                        FILTER_SANITIZE_STRING,
                    );
                    $poste_value = floatval($poste_value);
                    if ($poste != "" && $poste_value > 0) {
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
                    }
                    $j = $j + 1;
                }
            }
            $i = $i + 1;
        }
        $i = 1;
        while (isset($_POST["rubrique2_" . $i])) {
            $rubrique = filter_input(
                INPUT_POST,
                "rubrique2_" . $i,
                FILTER_SANITIZE_STRING,
            );
            if ($rubrique != "") {
                $request = "INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique) 
				VALUES (?, ?, ?)";
                $id_typeRubrique = 2;
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
                    }
                    $j = $j + 1;
                }
            }
            $i = $i + 1;
        }
        echo "done|0";
        exit();
    }
}
if (
    isset(
        $_POST["id_periodePaiement"],
        $_POST["id_repartitionFonct"],
        $_POST["id_repartitionInvest"],
        $_POST["id_exercice"],
    )
) {
    $id_periodePaiement = filter_input(
        INPUT_POST,
        "id_periodePaiement",
        FILTER_SANITIZE_STRING,
    );
    $id_repartitionFonct = filter_input(
        INPUT_POST,
        "id_repartitionFonct",
        FILTER_SANITIZE_STRING,
    );
    $id_repartitionInvest = filter_input(
        INPUT_POST,
        "id_repartitionInvest",
        FILTER_SANITIZE_STRING,
    );
    $montantFonct = filter_input(
        INPUT_POST,
        "montantFonct",
        FILTER_SANITIZE_STRING,
    );
    $montantInvest = filter_input(
        INPUT_POST,
        "montantInvest",
        FILTER_SANITIZE_STRING,
    );
    $id_exercice = filter_input(
        INPUT_POST,
        "id_exercice",
        FILTER_SANITIZE_STRING,
    );
    if ($id_exercice != "") {
        $exercice = getExercice($id_exercice, null, $connection);
        if (empty($exercice)) {
            http_response_code(404);
            echo "error|L'exercice est introuvable.";
            exit();
        }
        if (!in_array((string) $id_periodePaiement, ["1", "2", "3", "4"], true)) {
            http_response_code(400);
            echo "error|La période de paiement est invalide.";
            exit();
        }

        $montantFonct = (float) $exercice[0]["montantFonct"];
        $montantInvest = (float) $exercice[0]["montantInvest"];
        $id_repartitionFonct = 3;
        $id_repartitionInvest = 3;
        $validatedAllocations = [];
        $totalPartFonct = 0.0;
        $totalPartInv = 0.0;
        $allocationIndex = 1;

        while (isset($_POST["id_lot2_" . $allocationIndex])) {
            $id_lot = trim((string) $_POST["id_lot2_" . $allocationIndex]);
            $partFonct = parseAllocationAmount(
                $_POST["partFonct_" . $allocationIndex] ?? "",
                $validPartFonct,
            );
            $partInv = parseAllocationAmount(
                $_POST["partInv_" . $allocationIndex] ?? "",
                $validPartInv,
            );
            if (
                $id_lot === "" ||
                !$validPartFonct ||
                !$validPartInv ||
                $partFonct < 0 ||
                $partInv < 0
            ) {
                http_response_code(400);
                echo "error|Montant invalide pour le lot numéro " . $allocationIndex . ".";
                exit();
            }
            $validatedAllocations[$allocationIndex] = [
                "id_lot" => $id_lot,
                "partFonct" => $partFonct,
                "partInv" => $partInv,
            ];
            $totalPartFonct += $partFonct;
            $totalPartInv += $partInv;
            ++$allocationIndex;
        }

        if (empty($validatedAllocations)) {
            http_response_code(400);
            echo "error|Aucun lot n'a été reçu pour la répartition.";
            exit();
        }

        $lotCount = 0;
        $lotCountStmt = $connection->prepare(
            "SELECT COUNT(*) FROM lot WHERE id_copropriete = ?",
        );
        if (!$lotCountStmt) {
            http_response_code(500);
            echo "error|Impossible de vérifier les lots de la copropriété.";
            exit();
        }
        $lotCountStmt->bind_param("s", $exercice[0]["id_copropriete"]);
        if (!$lotCountStmt->execute()) {
            http_response_code(500);
            echo "error|Impossible de vérifier les lots de la copropriété.";
            exit();
        }
        $lotCountStmt->bind_result($lotCount);
        $lotCountStmt->fetch();
        $lotCountStmt->close();
        if ((int) $lotCount !== count($validatedAllocations)) {
            http_response_code(400);
            echo "error|La répartition doit contenir tous les lots de la copropriété.";
            exit();
        }

	        if (
	            abs(round($totalPartFonct * 100) - round($montantFonct * 100)) > 1 ||
	            abs(round($totalPartInv * 100) - round($montantInvest * 100)) > 1
	        ) {
            http_response_code(400);
            echo "error|Les totaux des lots ne correspondent pas aux budgets. Fonctionnement : " .
                number_format($totalPartFonct, 2, ".", " ") . " / " .
                number_format($montantFonct, 2, ".", " ") .
                " MAD. Investissement : " .
                number_format($totalPartInv, 2, ".", " ") . " / " .
                number_format($montantInvest, 2, ".", " ") . " MAD.";
            exit();
        }

        $connection->begin_transaction();
        $request =
            "UPDATE exercice SET id_periodePaiement = ?, id_repartitionFonct = ?, id_repartitionInvest = ?, montantFonct = ?, montantInvest = ? WHERE id = ?";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssssss",
                $id_periodePaiement,
                $id_repartitionFonct,
                $id_repartitionInvest,
                $montantFonct,
                $montantInvest,
                $id_exercice,
            );
            // Execute the prepared query.
	            if (!$insert_stmt->execute()) {
	                $connection->rollback();
	                echo $connection->error;
	                exit();
	            }
	        } else {
	            $connection->rollback();
	            echo "error|Impossible de mettre à jour l'exercice.";
	            exit();
	        }
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a modifié|exercice|" . $id_exercice;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $_SESSION["id"],
            );
            // Execute the prepared query.
	            if (!$insert_stmt_history->execute()) {
	                $connection->rollback();
	                echo $connection->error;
	                exit();
	            }
	        } else {
	            $connection->rollback();
	            echo "error|Impossible d'enregistrer l'historique.";
	            exit();
	        }
	        $request = "INSERT INTO rel_lot_exercice (id_lot, id_exercice, partFonct, partInv, dateFinPeriode) VALUES (?, ?, ?, ?, ?)";
	        $relLotStmt = $connection->prepare($request);
	        if (!$relLotStmt) {
	            $connection->rollback();
	            echo "error|Impossible de préparer les échéances des lots.";
	            exit();
	        }
	        $i = 1;
        while (isset($validatedAllocations[$i])) {
            $id_lot = $validatedAllocations[$i]["id_lot"];
            $partFonct = $validatedAllocations[$i]["partFonct"];
            $partInv = $validatedAllocations[$i]["partInv"];
            if ($id_lot != "" && $partFonct >= 0 && $partInv >= 0) {
	                $nbrPeriode = 12;
                $nbrMonth = 1;
                $dateFinPeriode = $exercice[0]["dateDebut"];
                $lot = getLot($id_lot, null, null, $connection);
                if (
                    empty($lot) ||
                    (string) $lot[0]["id_copropriete"] !==
                        (string) $exercice[0]["id_copropriete"]
                ) {
                    $connection->rollback();
                    http_response_code(400);
                    echo "error|Un lot de la répartition n'appartient pas à cette copropriété.";
                    exit();
                }
                for ($j = 0; $j < 6; $j++) {
                    if (floatval($lot[0]["impaye" . $j]) > 0) {
                        $dateFinAE = date(
                            "Y-m-d",
                            strtotime(
                                date("Y-m-d", strtotime($dateFinPeriode)) .
                                    " - " .
                                    $j .
                                    " year",
                            ),
                        );
                        $impayeF = floatval($lot[0]["impaye" . $j]);
                        $impayeI = 0;
                        if ($j == 0) {
                            $id_AE = $j;
                        } else {
                            $id_AE = "-" . $j;
                        }
	                        $relLotStmt->bind_param(
	                            "sssss",
	                            $id_lot,
	                            $id_AE,
	                            $impayeF,
	                            $impayeI,
	                            $dateFinAE,
	                        );
	                        if (!$relLotStmt->execute()) {
	                            $connection->rollback();
	                            echo $connection->error;
	                            exit();
	                        }
                    }
                }
                if ($id_periodePaiement == "1") {
                    $nbrPeriode = 12;
                    $nbrMonth = 1;
                } elseif ($id_periodePaiement == "2") {
                    $nbrPeriode = 4;
                    $nbrMonth = 3;
                } elseif ($id_periodePaiement == "3") {
                    $nbrPeriode = 2;
                    $nbrMonth = 6;
                } elseif ($id_periodePaiement == "4") {
                    $nbrPeriode = 1;
                    $nbrMonth = 12;
                }
                $partFonct = $partFonct / $nbrPeriode;
                $partInv = $partInv / $nbrPeriode;
                for ($j = 0; $j < $nbrPeriode; $j++) {
                    $dateFinPeriode = date(
                        "Y-m-d",
                        strtotime(
                            date("Y-m-d", strtotime($dateFinPeriode)) .
                                " + " .
                                $nbrMonth .
                                " month",
                        ),
                    );
	                    $relLotStmt->bind_param(
	                        "sssss",
	                        $id_lot,
	                        $id_exercice,
	                        $partFonct,
	                        $partInv,
	                        $dateFinPeriode,
	                    );
	                    if (!$relLotStmt->execute()) {
	                        $connection->rollback();
	                        echo $connection->error;
	                        exit();
	                    }
                }
            }
            $i = $i + 1;
        }

        $request = "UPDATE copropriete SET display=1 WHERE id=?";

        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $exercice[0]["id_copropriete"]);
            // Execute the prepared query.
	            if (!$insert_stmt->execute()) {
	                $connection->rollback();
	                echo $connection->error;
	                exit();
	            }
	        } else {
	            $connection->rollback();
	            echo "error|Impossible d'activer la copropriété.";
	            exit();
	        }

        $connection->commit();
        echo "done|0";
        exit();
    }
}
