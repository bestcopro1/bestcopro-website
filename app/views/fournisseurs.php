<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
if (isset(
		$_POST['raisonSocial'],
		$_POST['ice'],
        $_POST['email'],
		$_POST['telephone'],
		$_POST['adresse'],
        $_POST['ville'],
		$_POST['codePostale']
	)) {
	$error_msg = "";
	
    $raisonSocial = filter_input(INPUT_POST, 'raisonSocial', FILTER_SANITIZE_STRING);
    $ice = filter_input(INPUT_POST, 'ice', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
	$telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);
    $ville = filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_STRING);
	$codePostale = filter_input(INPUT_POST, 'codePostale', FILTER_SANITIZE_STRING);
   
	
	if ($raisonSocial == "") {
		$error_msg .= 'Veuillez entrer la raison sociale';
		echo $error_msg;
		exit();
	}
    if ($ice == "") {
		$error_msg .= 'Veuillez entrer l\'ICE';
		echo $error_msg;
		exit();
	}
    if ($telephone == "") {
		$error_msg .= 'Veuillez entrer un numéro de téléphone';
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
			
			$request = "UPDATE fournisseur SET raisonSocial=?, ice=?, email=?, telephone=?, adresse=?, ville=?, codePostale=? WHERE id=?";

			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('ssssssss', $raisonSocial, $ice, $email, $telephone, $adresse, $ville, $codePostale, $id);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
					echo $connection->error;
					exit();
				}
			}
			if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
				$date = date('Y-m-d H:i:s');
				$action = "a modifié|fournisseur|".$id;
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
		
		$request = "INSERT INTO fournisseur (raisonSocial, ice, email, telephone, adresse, ville, codePostale) 
		VALUES (?, ?, ?, ?, ?, ?, ?)";
		
		$insert_id = "";
		
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('sssssss', $raisonSocial, $ice, $email, $telephone, $adresse, $ville, $codePostale);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|fournisseur|".$insert_id;
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
	$fournisseur = getFournisseur($id, $connection);
	if (count($fournisseur) == 0) goto iomEnd;
	if($action == "update" && $id != ""):
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'un fournisseur</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=fournisseurs" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="fournisseurs">Enregistrer</button>
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
													<input type="hidden" name="id" value="<?=$fournisseur[0]["id"]?>">
													<input type="hidden" name="update" value="true">
                                                    <label class="text-label">Raison sociale*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="raisonSocial" placeholder="Raison sociale" value="<?=$fournisseur[0]["raisonSocial"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">ICE*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="ice" placeholder="ICE" value="<?=$fournisseur[0]["ice"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Email</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="email" placeholder="Email" value="<?=$fournisseur[0]["email"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Téléphone*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="telephone" placeholder="Téléphone" value="<?=$fournisseur[0]["telephone"]?>">
												</div>
											</div>
											<div class="col-12 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Adresse*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="adresse" placeholder="Adresse" value="<?=$fournisseur[0]["adresse"]?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Ville*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="ville" placeholder="Ville" value="<?=$fournisseur[0]["ville"]?>">
												</div>
											</div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Code postal</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="codePostale" placeholder="Code postal" value="<?=$fournisseur[0]["codePostale"]?>">
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
?>

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
						<h2 class="text-primary font-w600 mb-0">Ajouter un fournisseur</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=fournisseurs" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="fournisseurs">Enregistrer</button>
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
                                                    <label class="text-label">Raison sociale*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="raisonSocial" placeholder="Raison sociale">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">ICE*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="ice" placeholder="ICE">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Email</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="email" placeholder="Email">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Téléphone*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="telephone" placeholder="Téléphone">
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
	$fournisseurs = getFournisseur(null, $connection);
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Fournisseurs</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<!-- smiyet la page a ssaymek -->
					<a href="dashboard.php?page=fournisseurs&action=add" type="button" class="btn btn-rounded btn-primary me-2">
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
                                                <th>Raison sociale</th>
                                                <th>ICE</th>
                                                <th>Téléphone</th>
                                                <th>Email</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
											foreach($fournisseurs as $fournisseur):
											?>
                                            <tr>
                                                <td><?=$fournisseur["raisonSocial"]?></td>
                                                <td><?=$fournisseur["ice"]?></td>
                                                <td><?=$fournisseur["telephone"]?></td>
                                                <td><?=$fournisseur["email"]?></td>
                                                <td class="text-center"><a href="./dashboard.php?page=fournisseurs&action=update&id=<?=$fournisseur["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a></td>
                                            </tr>
                                            <?php
											endforeach;
											?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Raison sociale</th>
                                                <th>ICE</th>
                                                <th>Téléphone</th>
                                                <th>Email</th>
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