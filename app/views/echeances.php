<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
if (isset(
		$_POST['id_copropriete'],
		$_POST['description'],
		$_POST['date'],
        $_POST['jourAlerte']
	)) {
	$error_msg = "";
	
    $id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $jourAlerte = filter_input(INPUT_POST, 'jourAlerte', FILTER_SANITIZE_STRING);
   
	
	if ($description == "") {
		$error_msg .= 'Veuillez entrer une description';
		echo $error_msg;
		exit();
	}
    if ($date == "") {
		$error_msg .= 'Veuillez entrer la date d\'écheance';
		echo $error_msg;
		exit();
	}
    if ($jourAlerte == "") {
		$error_msg .= 'Veuillez entrer le nombre de jours pour l\'alerte';
		echo $error_msg;
		exit();
	}
	if (! is_numeric($jourAlerte) && intval($jourAlerte) >= 0) {
		$error_msg .= 'Veuillez entrer un nombre de jours valide';
		echo $error_msg;
		exit();
	}
	if (empty($error_msg) && isset($_POST['id'],$_POST['update'])) {
		$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
		$update  = filter_input(INPUT_POST, 'update', FILTER_SANITIZE_STRING);
		if ($id != '' && $update == 'true') {
			
			$request = "UPDATE echeance SET description=?, date=?, jourAlerte=? WHERE id=?";

			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('ssss', $description, $date, $jourAlerte, $id);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
					echo $connection->error;
					exit();
				}
			}
			if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
				$date = date('Y-m-d H:i:s');
				$action = "a modifié|echeance|".$id;
				$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
				// Execute the prepared query.
				if (! $insert_stmt_history->execute()) {
					echo $connection->error;
					exit();
				}
			}
			$request = "DELETE FROM notificationsyndic WHERE idPage = ? AND nomPage = 'echeances'";
			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('s', $id);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
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
		
		$request = "INSERT INTO echeance (description, date, jourAlerte, id_copropriete) 
		VALUES (?, ?, ?, ?)";
		
		$insert_id = "";
		
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('ssss', $description, $date, $jourAlerte, $id_copropriete);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|echeance|".$insert_id;
			$insert_stmt_history->bind_param('sss', $date, $action, $_SESSION['id']);
			// Execute the prepared query.
			if (! $insert_stmt_history->execute()) {
				echo $connection->error;
				exit();
			}
		}
        echo "done|0";
        exit();
    } else {
        echo $error_msg;
		exit();
    }
}
if (isset($_GET['action'],$_GET['id'])):
	$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
	$echeance = getEcheance($id, null, null, $connection);
	if (count($echeance) == 0) goto iomEnd;
	$date = date("d/m/Y", strtotime($echeance[0]["date"]));
	if($action == "update" && $id != ""):
		$request = "UPDATE notificationsyndic SET seen = 1 WHERE idPage = ? AND nomPage = 'echeances'"; 
		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('s', $id);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		$disabled = "";
		if ($_SESSION['id_usertype'] !== "1" && $_SESSION['id_usertype'] !== "2" && $_SESSION['id_usertype'] !== "3")
			$disabled = "disabled";
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'une écheance</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<?php
					if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
					?>
					<a href="./dashboard.php?page=echeances" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="echeances">Enregistrer</button>
					<?php
					else:
					?>
					<a href="./dashboard.php?page=echeances" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Retour à la liste</a>
					<?php
					endif;
					?>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-12 mb-2" >
												<div class="form-group">
													<input type="hidden" name="id" value="<?=$echeance[0]["id"]?>">
													<input type="hidden" name="update" value="true">
													<input type="hidden" name="id_copropriete" value="<?=$GLOBALS["id_copropriete"]?>">
													<label class="text-label">Description*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="description" placeholder="Description" value="<?=$echeance[0]["description"]?>" <?=$disabled?>>
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Date d'écheance*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="jj/mm/aaaa" value="<?=$echeance[0]["date"]?>" <?=$disabled?>>
												</div>
											</div>
                                            <div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Alerte*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="jourAlerte" placeholder=" M'avertir X jours avant la date d'écheance" value="<?=$echeance[0]["jourAlerte"]?>" <?=$disabled?>>
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
		goto iomEnd;
?>

<?php
	else:
		goto iomEnd;
	endif;
elseif (isset($_GET['action'])):
	$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	if($action == "add" && (($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3"))):
?>
<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter une écheance</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=echeances" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="echeances">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-12 mb-2" >
												<div class="form-group">
													<input type="hidden" name="id_copropriete" value="<?=$GLOBALS["id_copropriete"]?>">
													<label class="text-label">Description*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="description" placeholder="Description">
												</div>
											</div>
											<div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Date d'écheance*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="jj/mm/aaaa">
												</div>
											</div>
                                            <div class="col-6 mb-2" >
                                                <div class="form-group">
													<label class="text-label">Alerte*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="jourAlerte" placeholder=" M'avertir X jours avant la date d'écheance">
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
	$echeances = getEcheance(null, null, $GLOBALS["id_copropriete"], $connection);
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Échéances</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<?php
					if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
					?>
					<a href="dashboard.php?page=echeances&action=add" type="button" class="btn btn-rounded btn-primary me-2">
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
                                                <th>Description</th>
												<th>Date de notification</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
											foreach($echeances as $echeance):
											?>
                                            <tr>
                                                <td><?=$echeance["description"]?></td>
												<td><?=$echeance["jourAlerte"]?> jour(s) avant le <?=date("d/m/Y", strtotime($echeance["date"]))?></td>
                                                <td class="text-center">
													<?php
													if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
													?>
													<a href="./dashboard.php?page=echeances&action=update&id=<?=$echeance["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
													<?php
													else :
													?>
													<a href="./dashboard.php?page=echeances&action=update&id=<?=$echeance["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-eye"></i></a>
													<?php
													endif;
													?>
												</td>
                                            </tr>
                                            <?php
											endforeach;
											?>
                                        </tbody>
                                        <tfoot>
											<tr>
                                                <th>Description</th>
												<th>Date de notification</th>
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