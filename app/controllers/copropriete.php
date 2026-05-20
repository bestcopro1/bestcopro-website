<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');

if (!isset($connection)) {
    $connection = $GLOBALS["connection"];
}

if (!$connection) {
    die("Database connection failed.");
}

if (isset(
		$_POST['id_copropriete'],
		$_POST['id_exercice'],
        $_POST['nom'],
		$_POST['adresse'],
        $_POST['ville'],
		$_POST['codePostale'],
		$_POST['rib'],
        $_POST['nbrLot'],
		$_POST['dateExercice'],
        $_POST['id_syndic'],
        $_POST['prefixe']
	)) {
	$id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
    $id_exercice = filter_input(INPUT_POST, 'id_exercice', FILTER_SANITIZE_STRING);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);
    $ville = filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_STRING);
    $codePostale = filter_input(INPUT_POST, 'codePostale', FILTER_SANITIZE_STRING);
    $rib = filter_input(INPUT_POST, 'rib', FILTER_SANITIZE_STRING);
    $nbrLot = filter_input(INPUT_POST, 'nbrLot', FILTER_SANITIZE_STRING);
    $dateExercice = filter_input(INPUT_POST, 'dateExercice', FILTER_SANITIZE_STRING);
    $id_syndic = filter_input(INPUT_POST, 'id_syndic', FILTER_SANITIZE_STRING);
    $prefixe = filter_input(INPUT_POST, 'prefixe', FILTER_SANITIZE_STRING);

    if (empty($id_syndic) && isset($_SESSION['id'])) {
        $id_syndic = $_SESSION['id'];
    }

	if ($id_copropriete != "" && $id_exercice != "") {
		$request = "UPDATE copropriete SET nom = ?, adresse = ?, ville = ?, codePostale = ?, rib = ?, nbrLot = ?, dateExercice = ?, id_syndic = ?, prefixe = ? WHERE id = ?";
		$dateExercice = $dateExercice."-01";
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('ssssssssss', $nom, $adresse, $ville, $codePostale, $rib, $nbrLot, $dateExercice, $id_syndic, $prefixe, $id_copropriete);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a modifié|copropriete|".$id_copropriete;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
		$request = "UPDATE exercice SET dateDebut = ?, dateFin = ? WHERE id = ?";
		$dateDebut = $dateExercice;
		$dateFin =  date("Y-m-d", strtotime(date("Y-m-d", strtotime($dateDebut)) . " + 1 year - 1 day"));
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('sss', $dateDebut, $dateFin, $id_exercice);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a modifié|exercice|".$id_exercice;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
        echo "done|".$id_copropriete."|".$id_exercice;
		exit();
	} else {
		$request = "INSERT INTO copropriete (nom, adresse, ville, codePostale, rib, nbrLot, dateExercice, id_syndic, prefixe) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$dateExercice = $dateExercice."-01";
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('sssssssss', $nom, $adresse, $ville, $codePostale, $rib, $nbrLot, $dateExercice, $id_syndic, $prefixe);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $id_copropriete = $connection->insert_id;
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|copropriete|".$id_copropriete;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
		$request = "INSERT INTO exercice (dateDebut, dateFin, id_periodePaiement, id_repartitionFonct, id_repartitionInvest, montantFonct, montantInvest, id_copropriete) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$dateDebut = $dateExercice;
		$dateFin =  date("Y-m-d", strtotime(date("Y-m-d", strtotime($dateDebut)) . " + 1 year - 1 day"));
		$id_periodePaiement = 1;
		$id_repartitionFonct = 1;
		$id_repartitionInvest = 1;
		$montantFonct = 0;
		$montantInvest = 0;
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('ssssssss', $dateDebut, $dateFin, $id_periodePaiement, $id_repartitionFonct, $id_repartitionInvest, $montantFonct, $montantInvest, $id_copropriete);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $id_exercice = $connection->insert_id;
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|exercice|".$id_exercice;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
        echo "done|".$id_copropriete."|".$id_exercice;
        exit();
	}
}
if (isset($_POST['rubrique_1'], $_POST['rubrique2_1'], $_POST['id_exercice'])) {
	$id_exercice = filter_input(INPUT_POST, 'id_exercice', FILTER_SANITIZE_STRING);
	if ($id_exercice != "") {
		$request = "DELETE FROM poste WHERE id_rubrique IN (SELECT id FROM rubrique WHERE id_exercice = ?)";
		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('s', $id_exercice);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		$request = "DELETE FROM rubrique WHERE id_exercice = ?";
		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('s', $id_exercice);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		$i = 1;
		while (isset($_POST['rubrique_'.$i])) {
			$rubrique = filter_input(INPUT_POST, 'rubrique_'.$i, FILTER_SANITIZE_STRING);
			if ($rubrique != "") {
				$request = "INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique) 
				VALUES (?, ?, ?)";
				$id_typeRubrique = 1;
				if ($insert_stmt = $connection->prepare($request)) {
					$insert_stmt->bind_param('sss', $rubrique, $id_exercice, $id_typeRubrique);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
				$id_rubrique = $connection->insert_id;
				if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
					$date = date('Y-m-d H:i:s');
					$action = "a ajouté|rubrique|".$id_rubrique;
					$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
					// Execute the prepared query.
					if (! $insert_stmt_history->execute()) {
						echo $connection->error;
						exit();
					}
				}
				$j = 1;
				while (isset($_POST['rubrique_'.$i.'_poste_'.$j])) {
					$poste = filter_input(INPUT_POST, 'rubrique_'.$i.'_poste_'.$j, FILTER_SANITIZE_STRING);
					$poste_value = filter_input(INPUT_POST, 'rubrique_'.$i.'_poste_'.$j.'_value', FILTER_SANITIZE_STRING);
					$poste_value = floatval($poste_value);
					if ($poste != "" && $poste_value > 0) {
						$request = "INSERT INTO poste (libelle, montant, id_rubrique) 
						VALUES (?, ?, ?)";
						
						if ($insert_stmt = $connection->prepare($request)) {
							$insert_stmt->bind_param('sss', $poste, $poste_value, $id_rubrique);
							// Execute the prepared query.
							if (! $insert_stmt->execute()) {
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
		while (isset($_POST['rubrique2_'.$i])) {
			$rubrique = filter_input(INPUT_POST, 'rubrique2_'.$i, FILTER_SANITIZE_STRING);
			if ($rubrique != "") {
				$request = "INSERT INTO rubrique (libelle, id_exercice, id_typeRubrique) 
				VALUES (?, ?, ?)";
				$id_typeRubrique = 2;
				if ($insert_stmt = $connection->prepare($request)) {
					$insert_stmt->bind_param('sss', $rubrique, $id_exercice, $id_typeRubrique);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
				$id_rubrique = $connection->insert_id;
				if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
					$date = date('Y-m-d H:i:s');
					$action = "a ajouté|rubrique|".$id_rubrique;
					$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
					// Execute the prepared query.
					if (! $insert_stmt_history->execute()) {
						echo $connection->error;
						exit();
					}
				}
				$j = 1;
				while (isset($_POST['rubrique2_'.$i.'_poste2_'.$j])) {
					$poste = filter_input(INPUT_POST, 'rubrique2_'.$i.'_poste2_'.$j, FILTER_SANITIZE_STRING);
					$poste_value = filter_input(INPUT_POST, 'rubrique2_'.$i.'_poste2_'.$j.'_value', FILTER_SANITIZE_STRING);
					$poste_value = floatval($poste_value);
					if ($poste != "" && $poste_value > 0) {
						$request = "INSERT INTO poste (libelle, montant, id_rubrique) 
						VALUES (?, ?, ?)";
						
						if ($insert_stmt = $connection->prepare($request)) {
							$insert_stmt->bind_param('sss', $poste, $poste_value, $id_rubrique);
							// Execute the prepared query.
							if (! $insert_stmt->execute()) {
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
if (isset($_POST['id_periodePaiement'], $_POST['id_repartitionFonct'], $_POST['id_repartitionInvest'], $_POST['id_exercice'])) {
	$id_periodePaiement = filter_input(INPUT_POST, 'id_periodePaiement', FILTER_SANITIZE_STRING);
	$id_repartitionFonct = filter_input(INPUT_POST, 'id_repartitionFonct', FILTER_SANITIZE_STRING);
	$id_repartitionInvest = filter_input(INPUT_POST, 'id_repartitionInvest', FILTER_SANITIZE_STRING);
	$montantFonct = filter_input(INPUT_POST, 'montantFonct', FILTER_SANITIZE_STRING);
	$montantInvest = filter_input(INPUT_POST, 'montantInvest', FILTER_SANITIZE_STRING);
	$id_exercice = filter_input(INPUT_POST, 'id_exercice', FILTER_SANITIZE_STRING);
	if ($id_exercice != "") {
		$exercice = getExercice($id_exercice, null, $connection);
		$request = "UPDATE exercice SET id_periodePaiement = ?, id_repartitionFonct = ?, id_repartitionInvest = ?, montantFonct = ?, montantInvest = ? WHERE id = ?";
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('ssssss', $id_periodePaiement, $id_repartitionFonct, $id_repartitionInvest, $montantFonct, $montantInvest, $id_exercice);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a modifié|exercice|".$id_exercice;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
		$i = 1;
		while (isset($_POST['id_lot2_'.$i])) {
			$id_lot = filter_input(INPUT_POST, 'id_lot2_'.$i, FILTER_SANITIZE_STRING);
			$partFonct = floatval(filter_input(INPUT_POST, 'partFonct_'.$i, FILTER_SANITIZE_STRING));
			$partInv = floatval(filter_input(INPUT_POST, 'partInv_'.$i, FILTER_SANITIZE_STRING));
			if ($id_lot != "" && $partFonct > 0) {
				$request = "INSERT INTO rel_lot_exercice (id_lot, id_exercice, partFonct, partInv, dateFinPeriode) 
				VALUES (?, ?, ?, ?, ?)";
				$nbrPeriode = 12;
				$nbrMonth = 1;
				$dateFinPeriode = $exercice[0]["dateDebut"];
				$lot = getLot($id_lot, null, null, $connection);
				for ($j = 0 ; $j < 6 ; $j++) {
					if (floatval($lot[0]["impaye".$j]) > 0) {
						$dateFinAE = date("Y-m-d", strtotime(date("Y-m-d", strtotime($dateFinPeriode)) . " - ".$j." year"));
						$impayeF = floatval($lot[0]["impaye".$j]);
						$impayeI = 0;
						if ($j == 0)
							$id_AE = $j;
						else
							$id_AE = "-".$j;
						if ($insert_stmt = $connection->prepare($request)) {
							$insert_stmt->bind_param('sssss', $id_lot, $id_AE, $impayeF, $impayeI, $dateFinAE);
							// Execute the prepared query.
							if (! $insert_stmt->execute()) {
								echo $connection->error;
								exit();
							}
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
				for ($j = 0 ; $j < $nbrPeriode ; $j++) {
					$dateFinPeriode = date("Y-m-d", strtotime(date("Y-m-d", strtotime($dateFinPeriode)) . " + ".$nbrMonth." month"));
					if ($insert_stmt = $connection->prepare($request)) {
						$insert_stmt->bind_param('sssss', $id_lot, $id_exercice, $partFonct, $partInv, $dateFinPeriode);
						// Execute the prepared query.
						if (! $insert_stmt->execute()) {
							echo $connection->error;
							exit();
						}
					}
				}
			}
			$i = $i+ 1;
		}
		
		$request = "UPDATE copropriete SET display=1 WHERE id=?";

		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('s', $exercice[0]["id_copropriete"]);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		
		echo "done|0";
		exit();
	}
}
