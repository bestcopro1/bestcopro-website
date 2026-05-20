<?php
 if(!isset($_SESSION)) {
	session_start();
}
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'], $_SESSION['id']) || (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] !== "ImIn") || (isset($_SESSION['id']) && !is_int(intval($_SESSION['id'])))) {
	header('Location: ./login.php');
	exit;
}
if ($_SESSION['id_usertype'] !== "1" && $_SESSION['id_usertype'] !== "2" && $_SESSION['id_usertype'] !== "3") {
	header('Location: ./index.php');
	exit;
}
include_once(__DIR__.'/config/db.php');
include_once(__DIR__.'/controllers/functions.php');
$connection = $GLOBALS["connection"];
function getSVG($number) {
	$number %= 8;
	if ($number == 0)
		return '2769ee';
	elseif ($number == 1)
		return '47ae3b';
	elseif ($number == 2)
		return '8030d0';
	elseif ($number == 3)
		return 'e1b746';
	elseif ($number == 4)
		return '314c82';
	elseif ($number == 5)
		return '676767';
	elseif ($number == 6)
		return 'f94a4a';
	elseif ($number == 7)
		return 'ee9827';
	
}
if (isset(
		$_POST['id'],
		$_POST['delete']
	)) {
	
	$error_msg = "";
	
	$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
	$delete  = filter_input(INPUT_POST, 'delete', FILTER_SANITIZE_STRING);
	
	if ($id != '' && $delete == 'true') {
		$request = "DELETE FROM syndic WHERE id=?";
		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('s', $id);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a supprimé|syndic|".$id;
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
} elseif (isset(
		$_POST['civilite'],
		$_POST['prenom'],
        $_POST['nom'],
		$_POST['email'],
		$_POST['password'],
        $_POST['telephone'],
		$_POST['mobile'],
        $_POST['id_typeSyndic'],
		$_POST['is_active']
	)) {
	$error_msg = "";
	
    $civilite = filter_input(INPUT_POST, 'civilite', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $mobile = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING);
    $id_typeSyndic = filter_input(INPUT_POST, 'id_typeSyndic', FILTER_SANITIZE_STRING);
    $is_active = filter_input(INPUT_POST, 'is_active', FILTER_SANITIZE_STRING);
	if ($civilite == "") {
		$error_msg .= 'Veuillez sélectionner la civilité';
		echo $error_msg;
		exit();
	}
    if ($prenom == "") {
		$error_msg .= 'Veuillez entrer le prénom';
		echo $error_msg;
		exit();
	}
    if ($nom == "") {
		$error_msg .= 'Veuillez entrer le nom';
		echo $error_msg;
		exit();
	}
    if ($email == "") {
		$error_msg .= 'Veuillez entrer l\'email';
		echo $error_msg;
		exit();
	}
    if (empty($error_msg) && isset($_POST['id'],$_POST['update'])) {
		$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
		$update  = filter_input(INPUT_POST, 'update', FILTER_SANITIZE_STRING);
		if ($id != '' && $update == 'true') {
			if ($password != "") {
				if(!preg_match("/^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{6,20}$/", $password)) {
                    $error_msg .= 'Le mot de passe doit comporter entre 6 et 20 caractères, contenir au moins un caractère spécial, une minuscule, une majuscule et un chiffre.';
					echo $error_msg;
					exit();
                }
				$password_hash = password_hash($password, PASSWORD_BCRYPT);
				$request = "UPDATE syndic SET civilite=?, prenom=?, nom=?, email=?, password=?, telephone=?, mobile=?, id_typeSyndic=?, is_active=? WHERE id=?";
				if ($insert_stmt = $connection->prepare($request)) {
					$insert_stmt->bind_param('ssssssssss', $civilite, $prenom, $nom, $email, $password_hash, $telephone, $mobile, $id_typeSyndic, $is_active, $id);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
			} else {
				$request = "UPDATE syndic SET civilite=?, prenom=?, nom=?, email=?, telephone=?, mobile=?, id_typeSyndic=?, is_active=? WHERE id=?";
				if ($insert_stmt = $connection->prepare($request)) {
					$insert_stmt->bind_param('sssssssss', $civilite, $prenom, $nom, $email, $telephone, $mobile, $id_typeSyndic, $is_active, $id);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
			}
			if(!empty($_POST['coproprietes'])) {
				if ($insert_stmt = $connection->prepare("DELETE FROM rel_copropriete_syndic WHERE id_syndic = ?")) {
					$insert_stmt->bind_param('s', $id);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
				foreach($_POST['coproprietes'] as $copropriete){
					if ($insert_stmt = $connection->prepare("INSERT INTO rel_copropriete_syndic (id_copropriete, id_syndic) VALUES (?, ?)")) {
						$insert_stmt->bind_param('ss', $copropriete, $id);
						// Execute the prepared query.
						if (! $insert_stmt->execute()) {
							echo $connection->error;
							exit();
						}
					}
				}
			}
			if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
				$date = date('Y-m-d H:i:s');
				$action = "a modifié|syndic|".$id;
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
		
		if(!preg_match("/^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{6,20}$/", $password)) {
			$error_msg .= 'Le mot de passe doit comporter entre 6 et 20 caractères, contenir au moins un caractère spécial, une minuscule, une majuscule et un chiffre.';
			echo $error_msg;
			exit();
		}
		
		$password_hash = password_hash($password, PASSWORD_BCRYPT);
		$token = md5(rand().time());
		$date = date('Y-m-d H:i:s');
		$request = "INSERT INTO syndic (civilite, prenom, nom, email, password, token, telephone, mobile, id_typeSyndic, is_active, date_time) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$insert_id = "";
		
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('sssssssssss', $civilite, $prenom, $nom, $email, $password_hash, $token, $telephone, $mobile, $id_typeSyndic, $is_active, $date);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
		if(!empty($_POST['coproprietes'])) {
			foreach($_POST['coproprietes'] as $value){
				if ($insert_stmt = $connection->prepare("INSERT INTO rel_copropriete_syndic (id_copropriete, id_syndic) VALUES (?, ?)")) {
					$insert_stmt->bind_param('ss', $value, $insert_id);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
			}
		}
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|syndic|".$insert_id;
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="copropriété, immeuble, villa, appartement, lot">
	<meta name="author" content="IARoTech">
	<meta name="robots" content="">
	<meta name="description" content="BEST COPRO facilite la vie des copropriétés autonomes">
	
	<!-- PAGE TITLE HERE -->
    <title>BEST COPRO - Collaborateurs</title>
    
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images\favicon.png">
	<!-- Datatable -->
    <link href="vendor\datatables\css\jquery.dataTables.min.css" rel="stylesheet">
	<link rel="stylesheet" href="vendor\select2\css\select2.min.css">
	<link href="vendor\jquery-nice-select\css\nice-select.css" rel="stylesheet">
    <link href="css\style.css" rel="stylesheet">
</head>
<body>

    <!--*******************
        Preloader start
    ********************-->
   <div id="preloader">
		<div class="lds-ripple">
			<div></div>
			<div></div>
		</div>
    </div>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">
	
		<?php include('./header.php'); ?>
		
		<!--**********************************
            Content body start
        ***********************************-->
		<?php
		if (isset($_GET['action'],$_GET['id'])):
			$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
			$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
			$syndic = getSyndic($id, $_SESSION['id_usertype'], $connection);
			if (count($syndic) == 0) goto iomEnd;
			if($action == "update" && $id != "" && intval($_SESSION['id_usertype']) <= intval($syndic[0]["id_typeSyndic"]) && intval($_SESSION['id_usertype']) <= 2):
				$relCoproprieteSyndic = getRel_copropriete_syndic($syndic[0]["id"], $connection);
		?>
        
		<div class="content-body" style="padding-top: 5rem;">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'un collaborateur</h2>
					</div>
					<a href="./syndics.php?page=syndics" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="syndics">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
                                             <div class="col-1 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id" value="<?=$syndic[0]["id"]?>">
													<input type="hidden" name="update" value="true">
                                                    <label class="text-label">Civilité*</label>
                                                    <select name="civilite" class="default-select form-control input-rounded wide mb-3">
                                                        <option value ="M." <?php if($syndic[0]["civilite"] == "M.") echo "selected"; ?>>M.</option>
                                                        <option value="Mme." <?php if($syndic[0]["civilite"] == "Mme.") echo "selected"; ?>>Mme.</option>
                                                        <option value="Mlle." <?php if($syndic[0]["civilite"] == "Mlle.") echo "selected"; ?>>Mlle.</option>
                                                        <option value="Mme/M." <?php if($syndic[0]["civilite"] == "Mlle.") echo "selected"; ?>>Mme/M.</option>
                                                        <option value="Sté." <?php if($syndic[0]["civilite"] == "Sté.") echo "selected"; ?>>Société</option>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-5  mb-2">
												<div class="form-group">
													<label class="text-label">Prénom*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="prenom" placeholder="Prénom" value="<?=$syndic[0]["prenom"]?>">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Nom*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="nom" placeholder="Nom" value="<?=$syndic[0]["nom"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Email*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="email" placeholder="Email" value="<?=$syndic[0]["email"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mot de passe</label>
													<input type="password" class="form-control input-rounded input-default mb-3" name="password" placeholder="Laisser vide pour conserver l'actuel" value="">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Téléphone</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="telephone" placeholder="Téléphone" value="<?=$syndic[0]["telephone"]?>">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mobile</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="mobile" placeholder="Mobile" value="<?=$syndic[0]["mobile"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Type*</label>
													<select name="id_typeSyndic" id="id_typeSyndic" class="default-select form-control input-rounded wide mb-3">
														<?php
														$typesyndics = getTypesyndic(null, $connection);
														foreach ($typesyndics as $typesyndic) :
															if (intval($_SESSION['id_usertype']) > intval($typesyndic["id"])) continue;
														?>
                                                        <option value ="<?=$typesyndic["id"]?>" <?php if($syndic[0]["id_typeSyndic"] == $typesyndic["id"]) echo "selected"; ?>><?=$typesyndic["libelle"]?></option>
														<?php
														endforeach;
														?>
                                                    </select>
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Statut*</label>
													<select name="is_active" class="default-select form-control input-rounded wide mb-3">
                                                        <option value ="1" <?php if($syndic[0]["is_active"] == "1") echo "selected"; ?>>Actif</option>
                                                        <option value="0" <?php if($syndic[0]["is_active"] == "0") echo "selected"; ?>>Inactif</option>
                                                    </select>
												</div>
											</div>
                                        </div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<div class="row" id="habilite" <?php if ($syndic[0]["id_typeSyndic"] == "1" || $syndic[0]["id_typeSyndic"] == "2") echo 'style="display: none;"' ?>>
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header border-0 pb-0">
								<h4 class="fs-20 mb-1">Liste des copropriétés à gérer</h4>
							</div>
							<div class="card-body pt-0"> 
								<div class="row">
									<?php
									$coproprietes = getCopropriete(null, $connection);
									$i = 0;
									foreach($coproprietes as $copropriete):
									?>
									<div class="col-xl-6 col-sm-6 mt-4 ">
										<div class="d-flex">
											<span>
												<svg xmlns="http://www.w3.org/2000/svg" width="71" height="71" viewBox="0 0 71 71">
													  <g transform="translate(-457 -443)">
														<rect width="71" height="71" rx="12" transform="translate(457 443)" fill="#c5c5c5"></rect>
														<g transform="translate(457 443)">
														  <rect data-name="placeholder" width="71" height="71" rx="12" fill="#<?=getSVG($i++)?>"></rect>
														  <circle data-name="Ellipse 12" cx="18" cy="18" r="18" transform="translate(15 20)" fill="#fff"></circle>
														  <circle data-name="Ellipse 11" cx="11" cy="11" r="11" transform="translate(36 15)" fill="#ffe70c" style="mix-blend-mode: multiply;isolation: isolate"></circle>
														</g>
													  </g>
												</svg>
											</span>
											<div class="ms-3 featured">
												<h4 class="fs-20 mb-1"><?=$copropriete["nom"]?></h4>
												<div class="form-check form-check-inline">
													<label class="form-check-label"><input type="checkbox" class="form-check-input" name="coproprietes[]" value="<?=$copropriete["id"]?>" <?php if (in_array($copropriete["id"], $relCoproprieteSyndic)) echo "checked"; ?>>permettre la gestion de cette copropriété</label>
												</div>
											</div>
										</div>
									</div>
									<?php
									endforeach;
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
        </div>
		<?php
			elseif($action == "view" && $id != ""):	
				goto iomEnd;
		?>
		<?php
			else:
				goto iomEnd;
			endif;
		elseif (isset($_GET['action'])):
			$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
			if($action == "add" && intval($_SESSION['id_usertype']) <= 2):
		?>
		<div class="content-body" style="padding-top: 5rem;">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter un collaborateur</h2>
					</div>
					<a href="./syndics.php" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="syndics">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
                                             <div class="col-1 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Civilité*</label>
                                                    <select name="civilite" class="default-select form-control input-rounded wide mb-3">
                                                        <option value ="M.">M.</option>
                                                        <option value="Mme.">Mme.</option>
                                                        <option value="Mlle.">Mlle.</option>
                                                        <option value="Sté.">Société</option>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-5  mb-2">
												<div class="form-group">
													<label class="text-label">Prénom*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="prenom" placeholder="Prénom">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Nom*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="nom" placeholder="Nom">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Email*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="email" placeholder="Email">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mot de passe*</label>
													<input type="password" class="form-control input-rounded input-default mb-3" name="password" placeholder="Mot de passe">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Téléphone</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="telephone" placeholder="Téléphone">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mobile</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="mobile" placeholder="Mobile">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Type*</label>
													<select name="id_typeSyndic" class="default-select form-control input-rounded wide mb-3">
														<?php
														$typesyndics = getTypesyndic(null, $connection);
														foreach ($typesyndics as $typesyndic) :
															if (intval($_SESSION['id_usertype']) > intval($typesyndic["id"])) continue;
														?>
                                                        <option value ="<?=$typesyndic["id"]?>"><?=$typesyndic["libelle"]?></option>
														<?php
														endforeach;
														?>
                                                    </select>
													<input type="hidden" name="is_active" value="1">
												</div>
											</div>
                                        </div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<div class="row" id="habilite">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header border-0 pb-0">
								<h4 class="fs-20 mb-1">Liste des copropriétés à gérer</h4>
							</div>
							<div class="card-body pt-0"> 
								<div class="row">
									<?php
									$coproprietes = getCopropriete(null, $connection);
									$i = 0;
									foreach($coproprietes as $copropriete):
									?>
									<div class="col-xl-6 col-sm-6 mt-4 ">
										<div class="d-flex">
											<span>
												<svg xmlns="http://www.w3.org/2000/svg" width="71" height="71" viewBox="0 0 71 71">
													  <g transform="translate(-457 -443)">
														<rect width="71" height="71" rx="12" transform="translate(457 443)" fill="#c5c5c5"></rect>
														<g transform="translate(457 443)">
														  <rect data-name="placeholder" width="71" height="71" rx="12" fill="#<?=getSVG($i++)?>"></rect>
														  <circle data-name="Ellipse 12" cx="18" cy="18" r="18" transform="translate(15 20)" fill="#fff"></circle>
														  <circle data-name="Ellipse 11" cx="11" cy="11" r="11" transform="translate(36 15)" fill="#ffe70c" style="mix-blend-mode: multiply;isolation: isolate"></circle>
														</g>
													  </g>
												</svg>
											</span>
											<div class="ms-3 featured">
												<h4 class="fs-20 mb-1"><?=$copropriete["nom"]?></h4>
												<div class="form-check form-check-inline">
													<label class="form-check-label"><input type="checkbox" class="form-check-input" name="coproprietes[]" value="<?=$copropriete["id"]?>">permettre la gestion de cette copropriété</label>
												</div>
											</div>
										</div>
									</div>
									<?php
									endforeach;
									?>
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
			$syndics = getSyndic(null, $_SESSION['id_usertype'], $connection);
		?>
		<div class="content-body" style="padding-top: 5rem;">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Collaborateurs</h2>
					</div>
					<a href="syndics.php?action=add" type="button" class="btn btn-rounded btn-primary me-2">
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
                                                <th>Nom complet</th>
                                                <th>Email</th>
												<th>Téléphone</th>
                                                <th>Mobile</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
											foreach($syndics as $syndic):
											?>
                                            <tr class="trUser-<?=$syndic["id"]?>">
                                                <td><?=$syndic["civilite"]?> <?=$syndic["prenom"]?> <?=$syndic["nom"]?></td>
                                                <td><?=$syndic["email"]?></td>
                                                <td><?=$syndic["telephone"]?></td>
                                                <td><?=$syndic["mobile"]?></td>
                                                <td>
													<?php
													$typesyndic = getTypesyndic($syndic["id_typeSyndic"], $connection);
													echo $typesyndic[0]["libelle"];
													?>
												</td>
                                                <td>
													<?php
													if ($syndic["is_active"] == "1") 
														echo '<span class="badge badge-rounded badge-success">Actif</span>';
													else
														echo '<span class="badge badge-rounded badge-danger">Inactif</span>';
													?>
												</td>
                                                <td class="text-center">
													<a href="./syndics.php?action=update&id=<?=$syndic["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
													<?php
													if ($_SESSION['id_usertype'] === "1") :
													?>
													<a href="javascript:void(0);" class="btn btn-secondary shadow btn-xs sharp me-1" data-bs-toggle="modal" data-bs-target=".delUser-<?=$syndic["id"]?>"><i class="fas fa-trash"></i></a>
													<?php
													endif;
													?>
												</td>
                                            </tr>
											<?php
											if ($_SESSION['id_usertype'] === "1") :
											?>
											<!-- Modal -->
											<div class="modal fade delUser-<?=$syndic["id"]?>">
												<div class="modal-dialog modal-dialog-centered" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title">Supprimer le collaborateur</h5>
															<button type="button" class="btn-close" data-bs-dismiss="modal">
															</button>
														</div>
														<div class="modal-body">
															<div class="text-center mb-4"><i class="fas fa-exclamation-triangle" style="font-size: 111px;"></i></div>
															<div class="text-center">Êtes-vous sûr de vouloir supprimer ce collaborateur ?</div>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Non</button>
															<button type="button" class="btn btn-rounded btn-danger delUserBtn" data-id="<?=$syndic["id"]?>">Oui</button>
														</div>
													</div>
												</div>
											</div>
											<?php
											endif;
											?>
                                            <?php
											endforeach;
											?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Nom complet</th>
                                                <th>Email</th>
												<th>Téléphone</th>
                                                <th>Mobile</th>
                                                <th>Type</th>
                                                <th>Statut</th>
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
		<!--**********************************
            Content body end
        ***********************************-->

		<?php include('./footer.php'); ?>


    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->
	
	<!-- Modal -->
	<div class="modal fade" id="SuccessErreurAlert">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content waitModal">
				<div class="modal-header">
					<h5 class="modal-title">Veuillez patienter!</h5>
				</div>
				<div class="modal-body">
					<div class="text-center mb-4"><i class="far fa-clock" style="font-size: 111px;"></i></div>
					<div class="text-center">Traitement en cours...</div>
				</div>
			</div>
			<div class="modal-content successModal" style="display: none;">
				<div class="modal-header">
					<h5 class="modal-title">C'est fait!</h5>
				</div>
				<div class="modal-body">
					<div class="text-center mb-4"><i class="far fa-thumbs-up text-success" style="font-size: 111px;"></i></div>
					<div class="text-center" id="successMessage"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" data-bs-dismiss="modal">Retour</button>
				</div>
			</div>
			<div class="modal-content errorModal" style="display: none;">
				<div class="modal-header">
					<h5 class="modal-title">Erreur!</h5>
				</div>
				<div class="modal-body">
					<div class="text-center mb-4"><i class="fas fa-times text-danger" style="font-size: 111px;"></i></div>
					<div class="text-center" id="erreurMessage"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" data-bs-dismiss="modal">Retour</button>
				</div>
			</div>
		</div>
	</div>

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="vendor\global\global.min.js"></script>
	<!-- Datatable -->
    <script src="vendor\datatables\js\jquery.dataTables.min.js"></script>
	<script src="js\plugins-init\datatables.init.js"></script>
	<script src="vendor\select2\js\select2.full.min.js"></script>
	<script src="vendor\jquery-nice-select\js\jquery.nice-select.min.js"></script>
	
	<script src="js\custom.min.js"></script>
	<script src="js\dlabnav-init.js"></script>
	<script src="js\demo.js"></script>
	
	<script>
		$("#saveORedit").on("click", function(event) {
			$('.waitModal').css('display', 'flex');
			$('.successModal').css('display', 'none');
			$('.errorModal').css('display', 'none');
			$('#SuccessErreurAlert').modal('toggle');
			var form_data = new FormData();
			var file_data;
			var url = $(this).attr("data-url");
			$('.content-body input, .content-body select, .content-body textarea').each(
				function(index){  
					var input = $(this);
					if((input.attr('type') == "checkbox" && input.is(':checked')) || (input.attr('type') == "radio" && input.is(':checked')) || (input.attr('type') != "checkbox" && input.attr('type') != "radio"))
						form_data.append(input.attr('name'), input.val());
				}
			);
			if($('#justificatif').length > 0){
				if($("#justificatif").prop("files")[0]){
					file_data = $("#justificatif").prop("files")[0];
					if (file_data.size > 5206301) {
						$('#erreurMessage').text('La taille du fichier doit être inférieure à 5 Mo');
						$('.waitModal').css('display', 'none');
						$('.successModal').css('display', 'none');
						$('.errorModal').css('display', 'flex');
					}
					form_data.append("file", file_data);
				}
			}
			$.ajax({
				url : './syndics.php',
				type : 'POST',
				dataType: 'html',
				cache: false,
				contentType: false,
				processData: false,
				data: form_data,
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						$('#successMessage').text("Opération effectuée avec succès");
						$('.waitModal').css('display', 'none');
						$('.successModal').css('display', 'flex');
						$('.errorModal').css('display', 'none');
						var words = response.split('|');
						$('#windowPrint').attr('data-id', words[1]);
						$('#windowPrint').show();
						$("#saveORedit").hide();
						$('#back').text('Retour à la liste');
						return false;
					} else {
						if (response.includes('done|0')) $('#erreurMessage').text('Une erreur est survenue');
						else $('#erreurMessage').html(response);
						$('.waitModal').css('display', 'none');
						$('.successModal').css('display', 'none');
						$('.errorModal').css('display', 'flex');
						return false;
					}
				}
			});
		});
		$('.delUserBtn').on('click', function() {
			var id_user = $(this).attr('data-id');
			
			$.ajax({
				url:"./syndics.php",
				method:"POST",
				data: {
					id: id_user,
					delete : "true"
				},
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						var table = $('#example').DataTable();
						table.row('.trUser-'+id_user).remove().draw(false);
						$('.delUser-'+id_user).modal('hide');
						return false;
					} else {
						return false;
					}
				}
			});
		});
		$( document ).ready(function() {
			var id_typeSyndicF = $('#id_typeSyndic').val();
			if (id_typeSyndicF == "1" || id_typeSyndicF == "2") {
				$('#habilite').hide();
			} else {
				$('#habilite').show();
			}
		});
		$('#id_typeSyndic').on('change', function() {
			var id_typeSyndic = $(this).val();
			if (id_typeSyndic == "1" || id_typeSyndic == "2") {
				$('#habilite').hide();
			} else {
				$('#habilite').show();
			}
		});
	</script>
	
</body>
</html>