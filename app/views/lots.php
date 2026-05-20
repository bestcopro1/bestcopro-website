<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
function getNewProprietaire($connection){
    $request = "SELECT id, civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale FROM proprietaire WHERE id NOT IN (SELECT id_proprietaire FROM lot)";
	if ($stmt = $connection->prepare($request)) {
		$stmt->execute();
		$stmt->store_result();

		if ($stmt->num_rows > 0) {
			$stmt->bind_result(
				$id, 
				$civilite, 
				$prenom, 
				$nom, 
				$email, 
				$telephone, 
				$mobile, 
				$adresse, 
				$ville, 
				$codePostale
			);
			while($stmt->fetch()){
				$result[] = 
					array(
						"id" => $id, 
						"civilite" => $civilite, 
						"prenom" => $prenom, 
						"nom" => $nom, 
						"email" => $email, 
						"telephone" => $telephone, 
						"mobile" => $mobile, 
						"adresse" => $adresse, 
						"ville" => $ville, 
						"codePostale" => $codePostale
					);
			}
			return $result;
		} else {
			return [];
		}
	}
}
function manageRelLotExercice($id_lot, $id_exercice, $partFonct, $connection){
    $request = "SELECT id_rel FROM rel_lot_exercice WHERE id_lot = ? AND id_exercice = ?";
	if ($stmt = $connection->prepare($request)) {
		$stmt->bind_param('ss', $id_lot, $id_exercice);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows > 0) {
			$stmt->bind_result(
				$id_rel
			);
			while($stmt->fetch()){
				if (floatval($partFonct) == 0)
					$request = "DELETE FROM rel_lot_exercice WHERE id_rel = ?";
				else
					$request = "UPDATE rel_lot_exercice SET partFonct = ? WHERE id_rel = ?";
				if ($insert_stmt = $connection->prepare($request)) {
					if (floatval($partFonct) == 0)
						$insert_stmt->bind_param('s', $id_rel);
					else
						$insert_stmt->bind_param('ss', $partFonct, $id_rel);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
			}
		} else {
			if (floatval($partFonct) > 0) {
				$request = "INSERT INTO rel_lot_exercice (id_lot, id_exercice, partFonct, partInv, dateFinPeriode) VALUES (?, ?, ?, ?, ?)";
				if ($insert_stmt = $connection->prepare($request)) {
					$impayeF = $partFonct;
					$impayeI = 0;
					$lot = getLot($id_lot, null, null, $connection);
					$copropriete = getCopropriete($lot[0]["id_copropriete"], $connection);
					$dateFinAE = date("Y-m-d", strtotime(date("Y-m-d", strtotime($copropriete[0]["dateExercice"])) . " ".$id_exercice." year"));
					$insert_stmt->bind_param('sssss', $id_lot, $id_exercice, $impayeF, $impayeI, $dateFinAE);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
			}
		}
	}
}

if (isset(
		$_POST['id_copropriete'],
		$_POST['code'],
		$_POST['id_typeLot'],
		$_POST['numeroImm'],
        $_POST['etage'],
		$_POST['numero'],
        $_POST['foncier'],
		$_POST['tantieme'],
        $_POST['dateAcquisition'],
		$_POST['dateRemiseCle'],
        $_POST['id_proprietaire'],
        $_POST['id_typeProprietaire'],
		$_POST['impaye0'],
		$_POST['impaye1'],
		$_POST['impaye2'],
		$_POST['impaye3'],
		$_POST['impaye4'],
		$_POST['impaye5']
	)) {
	$error_msg = "";
	
    $id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $id_typeLot = filter_input(INPUT_POST, 'id_typeLot', FILTER_SANITIZE_STRING);
    $numeroImm = filter_input(INPUT_POST, 'numeroImm', FILTER_SANITIZE_STRING);
    $etage = filter_input(INPUT_POST, 'etage', FILTER_SANITIZE_STRING);
    $numero = filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING);
    $foncier = filter_input(INPUT_POST, 'foncier', FILTER_SANITIZE_STRING);
    $tantieme = filter_input(INPUT_POST, 'tantieme', FILTER_SANITIZE_STRING);
    $dateAcquisition = filter_input(INPUT_POST, 'dateAcquisition', FILTER_SANITIZE_STRING);
    $dateRemiseCle = filter_input(INPUT_POST, 'dateRemiseCle', FILTER_SANITIZE_STRING);
    $id_proprietaire = filter_input(INPUT_POST, 'id_proprietaire', FILTER_SANITIZE_STRING);
    $id_typeProprietaire = filter_input(INPUT_POST, 'id_typeProprietaire', FILTER_SANITIZE_STRING);
    $impaye0 = filter_input(INPUT_POST, 'impaye0', FILTER_SANITIZE_STRING);	
    $impaye1 = filter_input(INPUT_POST, 'impaye1', FILTER_SANITIZE_STRING);	
    $impaye2 = filter_input(INPUT_POST, 'impaye2', FILTER_SANITIZE_STRING);	
    $impaye3 = filter_input(INPUT_POST, 'impaye3', FILTER_SANITIZE_STRING);	
    $impaye4 = filter_input(INPUT_POST, 'impaye4', FILTER_SANITIZE_STRING);	
    $impaye5 = filter_input(INPUT_POST, 'impaye5', FILTER_SANITIZE_STRING);	
	if ($id_typeLot == "") {
		$error_msg .= 'Veuillez sélectionner un type';
		echo $error_msg;
		exit();
	}
    if ($numeroImm == "") {
		$error_msg .= 'Veuillez entrer le numéro d\'immeuble';
		echo $error_msg;
		exit();
	}
    if ($etage == "") {
		$error_msg .= 'Veuillez entrer le numéro d\'étage';
		echo $error_msg;
		exit();
	}
    if (! is_numeric($etage) && intval($etage) >= 0) {
		$error_msg .= 'Veuillez entrer un numéro d\'étage valide';
		echo $error_msg;
		exit();
	}
    if ($numero == "") {
		$error_msg .= 'Veuillez entrer le numéro du lot';
		echo $error_msg;
		exit();
	}
    if ($tantieme == "") {
		$error_msg .= 'Veuillez entrer le tantième';
		echo $error_msg;
		exit();
	}
    if (! is_numeric($tantieme) && floatval($tantieme) >= 0) {
		$error_msg .= 'Veuillez entrer un tantième valide';
		echo $error_msg;
		exit();
	}
    if ($dateAcquisition == "") {
		$dateAcquisition = null;
	}
    if ($dateRemiseCle == "") {
		$dateRemiseCle = $dateAcquisition;
	}
    if ($id_proprietaire == "") {
		$error_msg .= 'Veuillez sélectionner un propriétaire';
		echo $error_msg;
		exit();
	}
    if ($impaye0 != "" && floatval($impaye0) < 0) {
		$error_msg .= 'Impayé promoteur est invalide';
		echo $error_msg;
		exit();
	} elseif ($impaye0 == "") {
		$impaye0 = 0;
	}
	if ($impaye1 != "" && floatval($impaye1) < 0) {
		$error_msg .= 'Cumul des impayés N-1 est invalide';
		echo $error_msg;
		exit();
	} elseif ($impaye1 == "") {
		$impaye1 = 0;
	}
	if ($impaye2 != "" && floatval($impaye2) < 0) {
		$error_msg .= 'Cumul des impayés N-2 est invalide';
		echo $error_msg;
		exit();
	} elseif ($impaye2 == "") {
		$impaye2 = 0;
	}
	if ($impaye3 != "" && floatval($impaye3) < 0) {
		$error_msg .= 'Cumul des impayés N-3 est invalide';
		echo $error_msg;
		exit();
	} elseif ($impaye3 == "") {
		$impaye3 = 0;
	}
	if ($impaye4 != "" && floatval($impaye4) < 0) {
		$error_msg .= 'Cumul des impayés N-4 est invalide';
		echo $error_msg;
		exit();
	} elseif ($impaye4 == "") {
		$impaye4 = 0;
	}
	if ($impaye5 != "" && floatval($impaye5) < 0) {
		$error_msg .= 'Cumul des impayés N-5 est invalide';
		echo $error_msg;
		exit();
	} elseif ($impaye5 == "") {
		$impaye5 = 0;
	}
	if (empty($error_msg) && isset($_POST['id'],$_POST['update'])) {
		$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
		$update  = filter_input(INPUT_POST, 'update', FILTER_SANITIZE_STRING);
		if ($id != '' && $update == 'true') {
			$password  = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
			
			$request = "UPDATE lot SET code=?, password=?, id_typeLot=?, numeroImm=?, etage=?, numero=?, foncier=?, tantieme=?, dateAcquisition=?, dateRemiseCle=?, id_proprietaire=?, id_typeProprietaire=?, impaye0=?, impaye1=?, impaye2=?, impaye3=?, impaye4=?, impaye5=? WHERE id=?";

			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('sssssssssssssssssss', $code, $password, $id_typeLot, $numeroImm, $etage, $numero, $foncier, $tantieme, $dateAcquisition, $dateRemiseCle, $id_proprietaire, $id_typeProprietaire, $impaye0, $impaye1, $impaye2, $impaye3, $impaye4, $impaye5, $id);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
					echo $connection->error;
					exit();
				}
			}
			manageRelLotExercice($id, "0", $impaye0, $connection);
			manageRelLotExercice($id, "-1", $impaye1, $connection);
			manageRelLotExercice($id, "-2", $impaye2, $connection);
			manageRelLotExercice($id, "-3", $impaye3, $connection);
			manageRelLotExercice($id, "-4", $impaye4, $connection);
			manageRelLotExercice($id, "-5", $impaye5, $connection);
			if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
				$date = date('Y-m-d H:i:s');
				$action = "a modifié|lot|".$id;
				$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
				// Execute the prepared query.
				if (! $insert_stmt_history->execute()) {
					echo $connection->error;
					exit();
				}
			}
			echo "done|".$id;
			exit();
		} else {
			$error_msg .= 'Une erreur est survenue';
			exit();
		}
	} elseif (empty($error_msg)) {
		$request = "INSERT INTO lot (code, id_typeLot, numeroImm, etage, numero, foncier, tantieme, dateAcquisition, dateRemiseCle, id_proprietaire, id_typeProprietaire, impaye0, impaye1, impaye2, impaye3, impaye4, impaye5, id_copropriete) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$insert_id = "";
		
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('ssssssssssssssssss', $code, $id_typeLot, $numeroImm, $etage, $numero, $foncier, $tantieme, $dateAcquisition, $dateRemiseCle, $id_proprietaire, $id_typeProprietaire, $impaye0, $impaye1, $impaye2, $impaye3, $impaye4, $impaye5, $id_copropriete);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
		
		$password = getPassword();
		$token = md5(uniqid($insert_id, true));
		
		$request = "UPDATE lot SET password=?, token=? WHERE id=?";

		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('sss', $password, $token, $insert_id);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|lot|".$insert_id;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
        echo "done|".$insert_id;
        exit();
    } else {
        echo $error_msg;
		exit();
    }
}
if (isset($_GET['action'],$_GET['id'])):
	$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
	$lot = getLot($id, null, null, $connection);
	if (count($lot) == 0) goto iomEnd;
	if($action == "update" && $id != ""):
		$disabled = "";
		if ($_SESSION['id_usertype'] !== "1" && $_SESSION['id_usertype'] !== "2" && $_SESSION['id_usertype'] !== "3")
			$disabled = "disabled";
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'un lot</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=lots" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="lots">Enregistrer</button>
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
													<input type="hidden" name="id" value="<?=$lot[0]["id"]?>">
													<input type="hidden" name="update" value="true">
													<input type="hidden" name="id_copropriete" value="<?=$GLOBALS["id_copropriete"]?>">
													<input type="hidden" name="code" value="<?=$lot[0]["code"]?>">
													<label class="text-label">Type du lot*</label>
													<select name="id_typeLot" class="default-select form-control input-rounded wide mb-3" <?=$disabled?>>
														<?php
														$typelots = getTypelot(null, $connection);
														foreach($typelots as $typelot):
														?>
														<option value="<?=$typelot["id"]?>" <?php if($typelot["id"] == $lot[0]["id_typeLot"]) echo "selected"; ?>><?=$typelot["libelle"]?></option>
														<?php
														endforeach;
														?>
													</select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Numéro d'immeuble*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="numeroImm" placeholder="Numéro d'immeuble" value="<?=$lot[0]["numeroImm"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Numéro d'étage*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="etage" placeholder="Numéro d'étage" value="<?=$lot[0]["etage"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Numéro du lot*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="numero" placeholder="Numéro du lot" value="<?=$lot[0]["numero"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Titre foncier</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="foncier" placeholder="Titre foncier" value="<?=$lot[0]["foncier"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Tantième*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="tantieme" placeholder="Tantième" value="<?=floatval($lot[0]["tantieme"])?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date d'acquisition</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="dateAcquisition" placeholder="Date d'acquisition"  value="<?=$lot[0]["dateAcquisition"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date de remise des clés</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="dateRemiseCle" placeholder="Date de remise des clés" value="<?=$lot[0]["dateRemiseCle"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Propriétaire*</label>
													<select name="id_proprietaire" class="single-select2 form-control wide mb-3" <?=$disabled?>>
														<?php
														$proprietaires = getProprietaire(null, $GLOBALS["id_copropriete"], $connection);
														$newProprietaires = getNewProprietaire($connection);
														foreach($proprietaires as $proprietaire):
														?>
														<option value="<?=$proprietaire["id"]?>" <?php if($proprietaire["id"] == $lot[0]["id_proprietaire"]) echo "selected"; ?>><?=$proprietaire["prenom"]?> <?=$proprietaire["nom"]?></option>
														<?php
														endforeach;
														foreach($newProprietaires as $proprietaire):
														?>
														<option value="<?=$proprietaire["id"]?>" <?php if($proprietaire["id"] == $lot[0]["id_proprietaire"]) echo "selected"; ?>><?=$proprietaire["prenom"]?> <?=$proprietaire["nom"]?></option>
														<?php
														endforeach;
														?>
													</select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Type*</label>
													<select name="id_typeProprietaire" class="default-select form-control input-rounded wide mb-3" <?=$disabled?>>
														<?php
														$typeproprietaires = getTypeproprietaire(null, $connection);
														foreach($typeproprietaires as $typeproprietaire):
														?>
														<option value="<?=$typeproprietaire["id"]?>" <?php if($typeproprietaire["id"] == $lot[0]["id_typeProprietaire"]) echo "selected"; ?>><?=$typeproprietaire["libelle"]?></option>
														<?php
														endforeach;
														?>
													</select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-1</label>
													<?php
													$impaye = getRel_lot_exercice($lot[0]["id"], "-1", $connection);
													$tempdisabled = $disabled;
													if (count($impaye))
														if (floatval($impaye[0]["cotisation"]) > 0)
															$disabled = "disabled";
													?>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye1" placeholder="0.00" value="<?=$lot[0]["impaye1"]?>" <?=$disabled?>>
													<?php
													$disabled = $tempdisabled;
													?>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-2</label>
													<?php
													$impaye = getRel_lot_exercice($lot[0]["id"], "-2", $connection);
													$tempdisabled = $disabled;
													if (count($impaye))
														if (floatval($impaye[0]["cotisation"]) > 0)
															$disabled = "disabled";
													?>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye2" placeholder="0.00" value="<?=$lot[0]["impaye2"]?>" <?=$disabled?>>
													<?php
													$disabled = $tempdisabled;
													?>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-3</label>
													<?php
													$impaye = getRel_lot_exercice($lot[0]["id"], "-3", $connection);
													$tempdisabled = $disabled;
													if (count($impaye))
														if (floatval($impaye[0]["cotisation"]) > 0)
															$disabled = "disabled";
													?>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye3" placeholder="0.00" value="<?=$lot[0]["impaye3"]?>" <?=$disabled?>>
													<?php
													$disabled = $tempdisabled;
													?>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-4</label>
													<?php
													$impaye = getRel_lot_exercice($lot[0]["id"], "-4", $connection);
													$tempdisabled = $disabled;
													if (count($impaye))
														if (floatval($impaye[0]["cotisation"]) > 0)
															$disabled = "disabled";
													?>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye4" placeholder="0.00" value="<?=$lot[0]["impaye4"]?>" <?=$disabled?>>
													<?php
													$disabled = $tempdisabled;
													?>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-5</label>
													<?php
													$impaye = getRel_lot_exercice($lot[0]["id"], "-5", $connection);
													$tempdisabled = $disabled;
													if (count($impaye))
														if (floatval($impaye[0]["cotisation"]) > 0)
															$disabled = "disabled";
													?>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye5" placeholder="0.00" value="<?=$lot[0]["impaye5"]?>" <?=$disabled?>>
													<?php
													$disabled = $tempdisabled;
													?>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Impayé promoteur</label>
													<?php
													$impaye = getRel_lot_exercice(null, "0", $connection);
													$tempdisabled = $disabled;
													if (count($impaye))
														if (floatval($impaye[0]["cotisation"]) > 0)
															$disabled = "disabled";
													?>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye0" placeholder="0.00" value="<?=$lot[0]["impaye0"]?>" <?=$disabled?>>
													<?php
													$disabled = $tempdisabled;
													?>
												</div>
											</div>
										</div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title me-auto">Accès pour l'application mobile</h4>
								<a href="#" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="showPass">Afficher le mot de passe</a>
                            </div>
							<div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Identifiant</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="identifiant" placeholder="Identifiant" value="<?=$lot[0]["code"]?>" disabled>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mot de passe</label>
													<input type="password" class="form-control input-rounded input-default mb-3" name="password" id="passInput" placeholder="Mot de passe" value="<?=$lot[0]["password"]?>">
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
                    </div>
                </div>
			</div>
        </div>
<?php
	elseif($action == "view" && $id != ""):	
		$typeLot = getTypelot($lot[0]["id_typeLot"], $connection);
		$proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
		$typeproprietaire = getTypeproprietaire($lot[0]["id_typeProprietaire"], $connection);
		$exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
		$relLotExercice = getRel_lot_exercice($lot[0]["id"], $GLOBALS["id_exercice"], $connection);
		$impayes = getRel_lot_exercice($lot[0]["id"], null, $connection);
		$paiements = getPaiement(null, null, $lot[0]["id"], $connection);
		
?>
		<style>
		<!--
		@media print {
			@page {
				size: 8.5in 11in; 
			}
			.card {
				box-shadow: none;
			}
		}
		-->
		</style>
		<div class="content-body d-print-none">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Code du lot : <?=$lot[0]["code"]?></h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="export/export_proprietaire.php?id=<?=$lot[0]["id"]?>&id_exercice=<?=$GLOBALS["id_exercice"]?>"" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter la situation du propriétaire
					</a>
					<a href="./dashboard.php?page=lots" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Retour à la liste</a>
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
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Type du lot : </span><span class="font-w400"><?=$typeLot[0]["libelle"]?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Numéro d'immeuble : </span><span class="font-w400"><?=$lot[0]["numeroImm"]?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Numéro d'étage : </span><span class="font-w400"><?=$lot[0]["etage"]?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Numéro du lot : </span><span class="font-w400"><?=$lot[0]["numero"]?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Titre foncier : </span><span class="font-w400"><?php if ($lot[0]["foncier"] != "") echo $lot[0]["foncier"]; else echo "N/A"; ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Tantième : </span><span class="font-w400"><?=floatval($lot[0]["tantieme"])?></span></p>
									</div>
									<div class="col-xl-6 col-md-6">
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Propriétaire :</span> <span class="font-w400"><?=$proprietaire[0]["civilite"]?> <?=$proprietaire[0]["prenom"]?> <?=$proprietaire[0]["nom"]?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Type du propriétaire :</span> <span class="font-w400"><?=$typeproprietaire[0]["libelle"]?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Email : </span><span class="font-w400"><?php if ($proprietaire[0]["email"] != "") echo $proprietaire[0]["email"]; else echo "N/A"; ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Téléphone :</span> <span class="font-w400"><?php if ($proprietaire[0]["telephone"] != "") echo $proprietaire[0]["telephone"]; else echo "N/A"; ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Mobile : </span><span class="font-w400"><?php if ($proprietaire[0]["mobile"] != "") echo $proprietaire[0]["mobile"]; else echo "N/A"; ?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Date d'acquisition :</span> <span class="font-w400"><?php if ($lot[0]["dateAcquisition"] != null && $lot[0]["dateAcquisition"] != "") echo date("d/m/Y", strtotime($lot[0]["dateAcquisition"]))?></span></p>
										<p class="font-w600 mb-2 d-flex"><span class="custom-label-2">Date de remise des clés : </span><span class="font-w400"><?php if ($lot[0]["dateRemiseCle"] != null && $lot[0]["dateRemiseCle"] != "") echo date("d/m/Y", strtotime($lot[0]["dateRemiseCle"]))?></span></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header">
                                <h4 class="card-title me-auto">Situation financière</h4>
								<div class="mb-md-2 mb-3">
									<?php
									$totalPayeChecker = 0;
									$totalImpayeChecker = 0;
									if ($exercice[0]["id_periodePaiement"] == "1") {
										$nbrMonth = 1;
									} elseif ($exercice[0]["id_periodePaiement"] == "2") {
										$nbrMonth = 3;
									} elseif ($exercice[0]["id_periodePaiement"] == "3") {
										$nbrMonth = 6;
									} elseif ($exercice[0]["id_periodePaiement"] == "4") {
										$nbrMonth = 12;
									}
									foreach ($relLotExercice as $periode) {
										if (strtotime(date('Y-m-d')) <= strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - ".$nbrMonth." month"))
											break;
										$totalImpayeChecker += floatval($periode["partFonct"]) + floatval($periode["partInv"]);
										$totalPayeChecker += floatval($periode["cotisation"]);
									}
									if ($totalPayeChecker >= $totalImpayeChecker) echo '<span class="d-block mb-1"><i class="fas fa-circle text-success me-2"></i>Ce propriétaire est <strong>créditeur</strong></span>';
									else echo '<span class="d-block mb-1"><i class="fas fa-circle text-danger me-2"></i>Ce propriétaire est <strong>débiteur</strong></span>';
									?>
								</div>
							</div>
							<div class="card-body">
								<div class="table-responsive">
                                    <table class="table table-responsive-md">
                                        <thead>
                                            <tr>
                                                <th></th>
												<?php
												$trimestre = 1;
												$semestre = 1;
												foreach ($relLotExercice as $periode) : 
													if ($exercice[0]["id_periodePaiement"] == "1") :
														$month = date("M",strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 month"));
												?>
													<th class="text-center"><strong><?=$month?></strong></th>
												<?php
													elseif($exercice[0]["id_periodePaiement"] == "2") :
												?>
													<th class="text-center"><strong>T<?=$trimestre++?></strong></th>
												<?php
													elseif($exercice[0]["id_periodePaiement"] == "3") :
												?>
													<th class="text-center"><strong>S<?=$semestre++?></strong></th>
												<?php
													elseif($exercice[0]["id_periodePaiement"] == "4") :
												?>
													<th class="text-center"><strong><?=date("Y",strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 year"))?></strong></th>
												<?php
													endif;
												?>
												<?php endforeach; ?>
                                                <th>Solde</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td rowspan="2"><strong><?=getNameexercice($exercice[0]["dateDebut"])?></strong></td>
												<?php
												$totalPaye = 0;
												$totalImpaye = 0;
												foreach ($relLotExercice as $periode) : 
													$totalPaye += floatval($periode["cotisation"]);
													if (floatval($periode["cotisation"]) < (floatval($periode["partFonct"]) + floatval($periode["partInv"])))
														$totalImpaye += floatval($periode["partFonct"]) + floatval($periode["partInv"]) - floatval($periode["cotisation"]);
												?>
                                                <td class="text-center">
													<?php 
													if(number_format(floatval($periode["cotisation"]),2) == number_format(floatval($periode["partFonct"]) + floatval($periode["partInv"]),2)) 
														echo '<span class="badge badge-rounded badge-success">PAYÉ</span>';
													else
														echo '<span class="badge badge-rounded badge-danger">IMPAYÉ</span>';
													?>
												</td>
												<?php endforeach; ?>
                                                <td rowspan="2"><strong><?=number_format($totalImpaye,2)?></strong></td>
                                            </tr>
											<tr>
                                                <?php
												foreach ($relLotExercice as $periode) : 
												?>
                                                <td class="text-center">
													<?=number_format(floatval($periode["partFonct"]) + floatval($periode["partInv"]) - floatval($periode["cotisation"]),2)?>
												</td>
												<?php endforeach; ?>
                                            </tr>
										</tbody>
                                    </table>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				$style = 'style="display: none;"';
				foreach ($impayes as $periode)
					if (intval($periode["id_exercice"]) <= 0) {
						$style = '';
						break;
					}
				?>
				<div class="row" <?=$style?>>
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header">
                                <h4 class="card-title me-auto">Liste des impayés</h4>
							</div>
							<div class="card-body">
								<div class="row text-center">
									<?php
									$totalPaye = 0;
									$totalImpaye = 0;
									foreach ($impayes as $periode) : 
										if (intval($periode["id_exercice"]) <= 0) :
											$totalPaye += floatval($periode["cotisation"]);
											if (floatval($periode["cotisation"]) < (floatval($periode["partFonct"]) + floatval($periode["partInv"])))
												$totalImpaye += floatval($periode["partFonct"]) + floatval($periode["partInv"]) - floatval($periode["cotisation"]);
											if(number_format(floatval($periode["cotisation"]),2) == number_format(floatval($periode["partFonct"]) + floatval($periode["partInv"]),2)) 
												$bgl = "success";
											else
												$bgl = "danger";
									?>
									<div class="col-2">
										<div class="bgl-<?=$bgl?> rounded p-3">
											<h6 class="mb-2">
												<?php
												if (intval($periode["id_exercice"]) < 0) :
												?>
												Cumul des impayés N<?=$periode["id_exercice"]?>
												<?php
												elseif (intval($periode["id_exercice"]) == 0) :
												?>
												Impayé promoteur
												<?php
												endif;
												?>
											</h6>
											<h5 class="d-block mb-2">
												<?=number_format(floatval($periode["partFonct"]) + floatval($periode["partInv"]) - floatval($periode["cotisation"]),2)?>
											</h5>
											<?php 
											if(number_format(floatval($periode["cotisation"]),2) == number_format(floatval($periode["partFonct"]) + floatval($periode["partInv"]),2)) 
												echo '<span class="badge badge-rounded badge-success">PAYÉ</span>';
											else
												echo '<span class="badge badge-rounded badge-danger">IMPAYÉ</span>';
											?>
										</div>
									</div>
									<?php 
										endif;
									endforeach;
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title me-auto">Accès pour l'application mobile</h4>
								<a href="#" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="showPass">Afficher le mot de passe</a>
                            </div>
							<div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Identifiant</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="identifiant" placeholder="Identifiant" value="<?=$lot[0]["code"]?>" disabled>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mot de passe</label>
													<input type="password" class="form-control input-rounded input-default mb-3" name="password" id="passInput" placeholder="Mot de passe" value="<?=$lot[0]["password"]?>" disabled>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
                    </div>
                </div>
				<div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title me-auto">Liste des paiements</h4>
							</div>
							<div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Propriétaire</th>
                                                <th>Montant</th>
                                                <th>Commentaire</th>
                                                <th>Responsable</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
											foreach($paiements as $paiement):
												$lot = getLot($paiement["id_lot"], null, null, $connection);
												$proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
												$syndic = getSyndic($paiement["id_syndic"], null, $connection);
											?>
                                            <tr>
                                                <td><?=date("d/m/Y", strtotime($paiement["date"]))?></td>
                                                <td><?=$proprietaire[0]["civilite"]?> <?=$proprietaire[0]["prenom"]?> <?=$proprietaire[0]["nom"]?></td>
                                                <td><?=$paiement["montant"]?></td>
                                                <td><?=$paiement["commentaire"]?></td>
                                                <td><?=$syndic[0]["civilite"]." ".$syndic[0]["prenom"]." ".$syndic[0]["nom"]?></td>
												<td class="text-center">
													<button class="btn btn-primary shadow btn-xs sharp me-1"  data-bs-toggle="modal" data-bs-target="#paiement-<?=$paiement["id"]?>"><i class="fas fa-eye"></i></button>
													<div class="modal fade" id="paiement-<?=$paiement["id"]?>" tabindex="-1" style="display: none;" aria-hidden="true">
														<div class="modal-dialog modal-lg">
															<div class="modal-content">
																<div class="modal-header">
																	<h5 class="modal-title"><strong>Reçu de paiement N° : <?=date("y").sprintf("%'.05d", $paiement["id"])?></strong></h5>
																	<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
																</div>
																<div class="modal-body">
																	<div class="row mb-0">
																		<div class="mt-2 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6">
																			<div class="row">
																				<div class="col-sm-12">
																					<div class="mb-3 text-start">
																						<img class="logo-abbr me-2" width="50" src="images\logo.png" alt="">
																						<img class="brand-title d-inline" width="110" src="images\logo-text.png" alt="">
																					</div>
																				</div>
																			</div>
																		</div>
																		<div class="mt-2 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6 text-start">
																			<div><strong><?=$proprietaire[0]["civilite"]?> <?=$proprietaire[0]["prenom"]?> <?=$proprietaire[0]["nom"]?></strong></div>
																			<div>Copropriété : <?=$GLOBALS["copropriete"][0]["nom"]?></div>
																			<div>N° du lot : <?=$lot[0]["code"]?></div>
																			<div>N° d'Immeuble : <?=$lot[0]["numeroImm"]?></div>
																		</div>
																	</div>
																	<hr>
																	<div class="row">
																		<div class="col-xl-12 col-md-12">
																			<?php
																			$modepaiement = getModepaiement($paiement["id_modePaiement"], $connection);
																			?>
																			<p class="font-w400 mb-2 text-start"><span class="custom-label-2">Montant total : </span><span class="font-w600"> <?=number_format(floatval($paiement["montant"]), 2, ",", " ")?> MAD</span></p>
																			<p class="font-w400 mb-2 text-start"><span class="custom-label-2">Mode de paiement : </span><span class="font-w600"> <?=$modepaiement[0]["libelle"]?></span></p>
																			<p class="font-w400 mb-2 text-start"><span class="custom-label-2">Périodes associées : </span></p>
																			</p>
																			<div class="table-responsive">
																				<table class="table table-bordered table-responsive-sm">
																					<tbody>
																						<tr>
																							<?php
																							$relRelPaiements = getRel_rel_paiement($paiement["id"], $connection);
																							$TotalRelPaiement = 0;
																							foreach ($relRelPaiements as $relRelPaiement) {
																								$periodeInfo = periodeInfo($relRelPaiement["id_rel"], $connection);
																								echo '<td class="text-center font-w400 p-2"><strong>'.$periodeInfo[0]["nomPeriode"].'</strong></td>';
																								$TotalRelPaiement += floatval($relRelPaiement["montant"]);
																							}
																							if (floatval($paiement["montant"]) > $TotalRelPaiement) 
																								echo '<td class="text-center font-w400 p-2"><strong>Avance</strong></td>';
																							?>
																						</tr>
																						<tr>
																							<?php
																							foreach ($relRelPaiements as $relRelPaiement) {
																								echo '<td class="text-center font-w400 p-2">'.number_format(floatval($relRelPaiement["montant"]), 2, ",", " ").' MAD</td>';
																							}
																							if (floatval($paiement["montant"]) > $TotalRelPaiement) 
																								echo '<td class="text-center font-w400 p-2">'.number_format(floatval($paiement["montant"]) - $TotalRelPaiement, 2, ",", " ").' MAD</td>';
																							?>
																						</tr>
																					</tbody>
																				</table>
																			</div>
																		</div>
																	</div>
																</div>
																<div class="modal-footer">
																	<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2 windowPrint" data-id="<?=$paiement["id"]?>">Imprimer</button>
																</div>
															</div>
														</div>
													</div>
												</td>
                                            </tr>
                                            <?php
											endforeach;
											?>
                                        <tfoot>
                                             <tr>
                                                <th>Date</th>
                                                <th>Propriétaire</th>
                                                <th>Montant</th>
                                                <th>Commentaire</th>
                                                <th>Responsable</th>
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
		<div class="row d-none d-print-block" id="printZone"></div>
<?php
	else:
		goto iomEnd;
	endif;
elseif (isset($_GET['action'])):
	$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	if($action == "add"):
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter un lot</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=lots" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="lots">Enregistrer</button>
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
													<input type="hidden" name="id_copropriete" value="<?=$GLOBALS["id_copropriete"]?>">
													<input type="hidden" name="code" value="">
													<label class="text-label">Type du lot*</label>
													<select name="id_typeLot" class="default-select form-control input-rounded wide mb-3">
														<?php
														$typelots = getTypelot(null, $connection);
														foreach($typelots as $typelot):
														?>
														<option value="<?=$typelot["id"]?>"><?=$typelot["libelle"]?></option>
														<?php
														endforeach;
														?>
													</select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Numéro d'immeuble*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="numeroImm" placeholder="Numéro d'immeuble">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Numéro d'étage*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="etage" placeholder="Numéro d'étage">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Numéro du lot*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="numero" placeholder="Numéro du lot">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Titre foncier</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="foncier" placeholder="Titre foncier">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Tantième*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="tantieme" placeholder="Tantième">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date d'acquisition</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="dateAcquisition" placeholder="Date d'acquisition">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date de remise des clés</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="dateRemiseCle" placeholder="Date de remise des clés">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Propriétaire*</label>
													<select name="id_proprietaire" class="single-select2 form-control wide mb-3">
														<?php
														$proprietaires = getProprietaire(null, $GLOBALS["id_copropriete"], $connection);
														foreach($proprietaires as $proprietaire):
														?>
														<option value="<?=$proprietaire["id"]?>"><?=$proprietaire["prenom"]?> <?=$proprietaire["nom"]?></option>
														<?php
														endforeach;
														?>
													</select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Type*</label>
													<select name="id_typeProprietaire" class="default-select form-control input-rounded wide mb-3">
														<?php
														$typeproprietaires = getTypeproprietaire(null, $connection);
														foreach($typeproprietaires as $typeproprietaire):
														?>
														<option value="<?=$typeproprietaire["id"]?>"><?=$typeproprietaire["libelle"]?></option>
														<?php
														endforeach;
														?>
													</select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-1</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye1" placeholder="0.00">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-2</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye2" placeholder="0.00">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-3</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye3" placeholder="0.00">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-4</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye4" placeholder="0.00">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Cumul des impayés N-5</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye5" placeholder="0.00">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Impayé promoteur</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="impaye0" placeholder="0.00">
												</div>
											</div>
										</div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
			</div>
        </div>
<?php
	else:
		goto iomEnd;
	endif;
else:
	iomEnd:
	$lots = getLot(null, null, $GLOBALS["id_copropriete"], $connection);
?>
		<style>
		<!--
		@media print {
			@page {
				size: A4 landscape;
			}
			body {
				font: 9pt Georgia, "Times New Roman", Times, serif;
				background: #fff !important;
				color: #000;
			}
			.card {
				box-shadow: none;
			}
		}
		-->
		</style>
		<div class="content-body d-print-none">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Lots</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<?php
					if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
					?>
					<a href="export/export_password.php" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter la liste des mots de passe
					</a>
					<a href="export/export_cotisation.php?id_copropriete=<?=$GLOBALS["id_copropriete"]?>&id_exercice=<?=$GLOBALS["id_exercice"]?>" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter le tableau des cotisations
					</a>
					<!--a href="export/export_impaye.php?id_copropriete=<?=$GLOBALS["id_copropriete"]?>&id_exercice=<?=$GLOBALS["id_exercice"]?>" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter le tableau des impayés
					</a-->
					<?php
					endif;
					?>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Titre foncier</th>
                                                <th>Propriétaire</th>
                                                <th>Date d'acquisition</th>
                                                <th class="text-center">Contentieux</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
											<?php
											foreach($lots as $lot):
												$proprietaire = getProprietaire($lot["id_proprietaire"], null, $connection);
											?>
                                            <tr>
                                                <td><?=$lot["code"]?></td>
                                                <td><?=$lot["foncier"]?></td>
                                                <td><?=$proprietaire[0]["civilite"]?> <?=$proprietaire[0]["prenom"]?> <?=$proprietaire[0]["nom"]?></td>
												<td><?=date("d/m/Y", strtotime($lot["dateAcquisition"]))?></td>
                                                <td class="text-center">
													<a href="./dashboard.php?page=contentieux&action=update&id=<?=$lot["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-balance-scale"></i></a>
												</td>
                                                <td class="text-center">
													<a href="./dashboard.php?page=lots&action=view&id=<?=$lot["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-eye"></i></a>
													<a href="./dashboard.php?page=lots&action=update&id=<?=$lot["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
												</td>
                                            </tr>
                                            <?php
											endforeach;
											?>
                                            
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Code</th>
                                                <th>Titre foncier</th>
                                                <th>Propriétaire</th>
                                                <th>Date d'acquisition</th>
                                                <th>Contentieux</th>
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