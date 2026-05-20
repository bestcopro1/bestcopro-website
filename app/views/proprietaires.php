<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
if (isset($_POST['select'], $_POST['id_copropriete'])) {
	$select = filter_input(INPUT_POST, 'select', FILTER_SANITIZE_STRING);
	$id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
	if ($select == "all" && $id_copropriete != "") {
		$proprietaires = getProprietaire(null, $id_copropriete, $connection);
		$codeHtml = '';
		foreach($proprietaires as $proprietaire) {
			$codeHtml .= '<option value="'.$proprietaire["id"].'">'.$proprietaire["civilite"].' '.$proprietaire["prenom"].' '.$proprietaire["nom"].'</option>';
		}
		echo 'done|'.$codeHtml;
		exit();
	}
	exit();
}
if (isset(
		$_POST['civilite'],
		$_POST['prenom'],
        $_POST['nom'],
		$_POST['email'],
        $_POST['telephone'],
		$_POST['mobile'],
        $_POST['adresse'],
		$_POST['ville'],
        $_POST['codePostale']
	)) {
	$error_msg = "";
	
    $civilite = filter_input(INPUT_POST, 'civilite', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $mobile = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING);
    $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);
    $ville = filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_STRING);
    $codePostale = filter_input(INPUT_POST, 'codePostale', FILTER_SANITIZE_STRING);
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
    if ($adresse == "") {
		$error_msg .= 'Veuillez entrer l\'adresse';
		echo $error_msg;
		exit();
	}
    if ($ville == "") {
		$error_msg .= 'Veuillez entrer la ville';
		echo $error_msg;
		exit();
	}
    if (empty($error_msg) && isset($_POST['id'],$_POST['update'])) {
		$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
		$update  = filter_input(INPUT_POST, 'update', FILTER_SANITIZE_STRING);
		if ($id != '' && $update == 'true') {
			
			$request = "UPDATE proprietaire SET civilite=?, prenom=?, nom=?, email=?, telephone=?, mobile=?, adresse=?, ville=?, codePostale=? WHERE id=?";

			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('ssssssssss', $civilite, $prenom, $nom, $email, $telephone, $mobile, $adresse, $ville, $codePostale, $id);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
					echo $connection->error;
					exit();
				}
			}
			if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
				$date = date('Y-m-d H:i:s');
				$action = "a modifié|proprietaire|".$id;
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
		
		$request = "INSERT INTO proprietaire (civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$insert_id = "";
		
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('sssssssss', $civilite, $prenom, $nom, $email, $telephone, $mobile, $adresse, $ville, $codePostale);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|proprietaire|".$insert_id;
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
	$proprietaire = getProprietaire($id, $GLOBALS["id_copropriete"], $connection);
	if (count($proprietaire) == 0 || ($_SESSION['id_usertype'] !== "1" && $_SESSION['id_usertype'] !== "2" && $_SESSION['id_usertype'] !== "3")) goto iomEnd;
	if($action == "update" && $id != ""):
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'un propriétaire</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=proprietaires" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="proprietaires">Enregistrer</button>
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
													<input type="hidden" name="id" value="<?=$proprietaire[0]["id"]?>">
													<input type="hidden" name="update" value="true">
                                                    <label class="text-label">Civilité*</label>
                                                    <select name="civilite" class="default-select form-control input-rounded wide mb-3">
                                                        <option value ="M." <?php if($proprietaire[0]["civilite"] == "M.") echo "selected"; ?>>M.</option>
                                                        <option value="Mme." <?php if($proprietaire[0]["civilite"] == "Mme.") echo "selected"; ?>>Mme.</option>
                                                        <option value="Mlle." <?php if($proprietaire[0]["civilite"] == "Mlle.") echo "selected"; ?>>Mlle.</option>
                                                        <option value="Mme/M." <?php if($proprietaire[0]["civilite"] == "Mlle.") echo "selected"; ?>>Mme/M.</option>
                                                        <option value="Sté." <?php if($proprietaire[0]["civilite"] == "Sté.") echo "selected"; ?>>Société</option>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-5  mb-2">
												<div class="form-group">
													<label class="text-label">Prénom*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="prenom" placeholder="Prénom" value="<?=$proprietaire[0]["prenom"]?>">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Nom*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="nom" placeholder="Nom" value="<?=$proprietaire[0]["nom"]?>">
												</div>
											</div>
											<div class="col-12 mb-2">
												<div class="form-group">
													<label class="text-label">Email</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="email" placeholder="Email" value="<?=$proprietaire[0]["email"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Téléphone</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="telephone" placeholder="Téléphone" value="<?=$proprietaire[0]["telephone"]?>">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Mobile</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="mobile" placeholder="Mobile" value="<?=$proprietaire[0]["mobile"]?>">
												</div>
											</div>
											<div class="col-12 mb-2">
												<div class="form-group">
													<label class="text-label">Adresse*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="adresse" placeholder="Adresse" value="<?=$proprietaire[0]["adresse"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Ville*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="ville" placeholder="Ville" value="<?=$proprietaire[0]["ville"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Code postal</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="codePostale" placeholder="Code postal" value="<?=$proprietaire[0]["codePostale"]?>">
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
elseif (isset($_GET['action'])):
	$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	if ($_SESSION['id_usertype'] !== "1" && $_SESSION['id_usertype'] !== "2" && $_SESSION['id_usertype'] !== "3") goto iomEnd;
	if($action == "add"):
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter un propriétaire</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=proprietaires" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="proprietaires">Enregistrer</button>
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
											<div class="col-12 mb-2">
												<div class="form-group">
													<label class="text-label">Email</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="email" placeholder="Email">
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
											<div class="col-12 mb-2">
												<div class="form-group">
													<label class="text-label">Adresse*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="adresse" placeholder="Adresse">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Ville*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="ville" placeholder="Ville">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Code postal</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="codePostale" placeholder="Code postal">
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
	$proprietaires = getProprietaire(null, $GLOBALS["id_copropriete"], $connection);
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Propriétaires</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<?php
					if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
					?>
					<a href="dashboard.php?page=proprietaires&action=add" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-plus color-primary"></i></span> Ajouter
					</a>
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
                                                <th>Nom complet</th>
                                                <th>Téléphone</th>
                                                <th>Mobile</th>
                                                <th>Email</th>
                                                <?php
												if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
												?>
												<th class="text-center">Actions</th>
												<?php
												endif;
												?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
											foreach($proprietaires as $proprietaire):
											?>
                                            <tr>
                                                <td><?=$proprietaire["civilite"]?> <?=$proprietaire["prenom"]?> <?=$proprietaire["nom"]?></td>
                                                <td><?=$proprietaire["telephone"]?></td>
                                                <td><?=$proprietaire["mobile"]?></td>
                                                <td><?=$proprietaire["email"]?></td>
                                                <?php
												if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
												?>
												<td class="text-center"><a href="./dashboard.php?page=proprietaires&action=update&id=<?=$proprietaire["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a></td>
												<?php
												endif;
												?>
											</tr>
                                            <?php
											endforeach;
											?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Nom complet</th>
                                                <th>Téléphone</th>
                                                <th>Mobile</th>
                                                <th>Email</th>
												<?php
												if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
												?>
                                                <th class="text-center">Actions</th>
												<?php
												endif;
												?>
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