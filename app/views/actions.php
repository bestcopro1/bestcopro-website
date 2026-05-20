<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
if (isset(
		$_POST['id_copropriete'],
		$_POST['nom'],
		$_POST['origine'],
        $_POST['id_statutAction'],
		$_POST['dateDebut'],
        $_POST['dateFin'],
		$_POST['dateFinReelle'],
        $_POST['efficacite'],
        $_POST['pilote'],
		$_POST['intervenants'],
        $_POST['avancement'],
		$_POST['observation']
	)) {
	$error_msg = "";
	
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $origine = filter_input(INPUT_POST, 'origine', FILTER_SANITIZE_STRING);
    $id_statutAction = filter_input(INPUT_POST, 'id_statutAction', FILTER_SANITIZE_STRING);
    $dateDebut = filter_input(INPUT_POST, 'dateDebut', FILTER_SANITIZE_STRING);
    $dateFin = filter_input(INPUT_POST, 'dateFin', FILTER_SANITIZE_STRING);
    $dateFinReelle = filter_input(INPUT_POST, 'dateFinReelle', FILTER_SANITIZE_STRING);
    $efficacite = filter_input(INPUT_POST, 'efficacite', FILTER_SANITIZE_STRING);
    $pilote = filter_input(INPUT_POST, 'pilote', FILTER_SANITIZE_STRING);
    $intervenants = filter_input(INPUT_POST, 'intervenants', FILTER_SANITIZE_STRING);
    $avancement = filter_input(INPUT_POST, 'avancement', FILTER_SANITIZE_STRING);
    $observation = filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_STRING);
    $id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
	
	if ($nom == "") {
		$error_msg .= 'Veuillez entrer le nom de l\'action';
		echo $error_msg;
		exit();
	}
    if ($origine == "") {
		$error_msg .= 'Veuillez entrer l\'origine';
		echo $error_msg;
		exit();
	}
    if ($dateDebut == "") {
		$error_msg .= 'Veuillez entrer la la date de début';
		echo $error_msg;
		exit();
	}
    if ($dateFin == "") {
		$error_msg .= 'Veuillez entrer la date fin prévue';
		echo $error_msg;
		exit();
	}
    if ($dateFinReelle == "") {
		$dateFinReelle = null;
	}
    if ($intervenants == "") {
		$error_msg .= 'Veuillez entrer les intervenants';
		echo $error_msg;
		exit();
	}
	if (empty($error_msg) && isset($_POST['id'],$_POST['update'])) {
		$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
		$update  = filter_input(INPUT_POST, 'update', FILTER_SANITIZE_STRING);
		if ($id != '' && $update == 'true') {
			
			$request = "UPDATE action SET nom=?, origine=?, dateDebut=?, dateFin=?, dateFinReelle=?, id_statutAction=?, efficacite=?, pilote=?, intervenants=?, avancement=?, observation=? WHERE id=?";

			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('ssssssssssss', $nom, $origine, $dateDebut, $dateFin, $dateFinReelle, $id_statutAction, $efficacite, $pilote, $intervenants, $avancement, $observation, $id);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
					echo $connection->error;
					exit();
				}
			}
			if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
				$date = date('Y-m-d H:i:s');
				$action = "a modifié|action|".$id;
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
		
		$request = "INSERT INTO action (nom, origine, dateDebut, dateFin, dateFinReelle, id_statutAction, efficacite, pilote, intervenants, avancement, observation, id_copropriete) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$insert_id = "";
		
		if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param('ssssssssssss', $nom, $origine, $dateDebut, $dateFin, $dateFinReelle, $id_statutAction, $efficacite, $pilote, $intervenants, $avancement, $observation, $id_copropriete);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
				echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
		if ($insert_stmt_history = $connection->prepare("INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)")) {
			$date = date('Y-m-d H:i:s');
			$action = "a ajouté|action|".$insert_id;
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
	$planAction = getAction($id, null, $connection);
	if (count($planAction) == 0) goto iomEnd;
	$date = date("d/m/Y", strtotime($planAction[0]["dateDebut"]));
	if($action == "update" && $id != ""):
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'une action</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=actions" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="actions">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-4 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id" value="<?=$planAction[0]["id"]?>">
													<input type="hidden" name="update" value="true">
													<input type="hidden" name="id_copropriete" value="<?=$GLOBALS["id_copropriete"]?>">
													<label class="text-label">Nom de l'action*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="nom" placeholder="Nom de l'action" value="<?=$planAction[0]["nom"]?>">
												</div>
											</div>
                                            <div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Origine*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="origine" placeholder="Origine" value="<?=$planAction[0]["origine"]?>">
												</div>
											</div>
											<div class="col-4 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Statut*</label>
                                                    <select name="id_statutAction" class="default-select form-control input-rounded wide mb-3">
                                                        <?php
                                                        $statutactions = getStatutaction(null, $connection);
                                                        foreach($statutactions as $statutaction):
                                                        ?>
                                                        <option value="<?=$statutaction["id"]?>" <?php if($statutaction["id"] == $planAction[0]["id_statutAction"]) echo "selected";?>><?=$statutaction["libelle"]?></option>
                                                        <?php
                                                        endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Date de début*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateDebut" placeholder="jj/mm/aaaa" value="<?=$planAction[0]["dateDebut"]?>">
												</div>
											</div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Date de fin prévue*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateFin" placeholder="jj/mm/aaaa" value="<?=$planAction[0]["dateFin"]?>">
												</div>
											</div>
                                            <div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Date de fin réelle</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateFinReelle" placeholder="jj/mm/aaaa" value="<?=$planAction[0]["dateFinReelle"]?>">
												</div>
											</div>
                                            <div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Efficacité*</label>
													<select name="efficacite" class="default-select form-control input-rounded wide mb-3">
														<option value="1">Action efficace (OUI)</option>
                                                        <option value="0">Action inefficace (NON)</option>
                                                    </select>
												</div>
											</div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Pilote</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="pilote" placeholder="Pilote" value="<?=$planAction[0]["pilote"]?>">
												</div>
											</div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Intervenants*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="intervenants" placeholder="Intervenants" value="<?=$planAction[0]["intervenants"]?>">
												</div>
											</div>
											<div class="col-12 mb-4">
											    <div class="form-group">
													<label class="text-label">Avancement (<span class="example-val" id="slider-step-value"></span>%)</label>
													<input type="hidden" id="avancement" name="avancement" placeholder="Avancement" value="<?=$planAction[0]["avancement"]?>">
													<div class="stepping-slider mt-2">
														<div id="slider-step"></div>
													</div>
												</div>
											</div>
                                            <div class="col-12 mb-2">
											    <div class="form-group">
													<label class="text-label">Observation</label>
													<textarea class="form-control input-rounded input-default mb-3" name="observation" placeholder="Observation"><?=$planAction[0]["observation"]?></textarea>
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
						<h2 class="text-primary font-w600 mb-0">Ajouter une action</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="./dashboard.php?page=actions" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="actions">Enregistrer</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-4 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id_copropriete" value="<?=$GLOBALS["id_copropriete"]?>">
													<label class="text-label">Nom de l'action*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="nom" placeholder="Nom de l'action">
												</div>
											</div>
                                            <div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Origine*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="origine" placeholder="Origine">
												</div>
											</div>
											<div class="col-4 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Statut*</label>
                                                    <select name="id_statutAction" class="default-select form-control input-rounded wide mb-3">
                                                        <?php
                                                        $statutactions = getStatutaction(null, $connection);
                                                        foreach($statutactions as $statutaction):
                                                        ?>
                                                        <option value="<?=$statutaction["id"]?>"><?=$statutaction["libelle"]?></option>
                                                        <?php
                                                        endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Date de début*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateDebut" placeholder="jj/mm/aaaa">
												</div>
											</div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Date de fin prévue*</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateFin" placeholder="jj/mm/aaaa">
												</div>
											</div>
                                            <div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Date de fin réelle</label>
													<input type="date" class="form-control input-rounded input-default mb-3" name="dateFinReelle" placeholder="jj/mm/aaaa">
												</div>
											</div>
                                            <div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Efficacité*</label>
													<select name="efficacite" class="default-select form-control input-rounded wide mb-3">
														<option value="1">Action efficace (OUI)</option>
                                                        <option value="0">Action inefficace (NON)</option>
                                                    </select>
												</div>
											</div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Pilote</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="pilote" placeholder="Pilote">
												</div>
											</div>
											<div class="col-4 mb-2">
											    <div class="form-group">
													<label class="text-label">Intervenants*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="intervenants" placeholder="Intervenants">
												</div>
											</div>
											<div class="col-12 mb-4">
											    <div class="form-group">
													<label class="text-label">Avancement (<span class="example-val" id="slider-step-value"></span>%)</label>
													<input type="hidden" id="avancement" name="avancement" placeholder="Avancement" value="0">
													<div class="stepping-slider mt-2">
														<div id="slider-step"></div>
													</div>
												</div>
											</div>
                                            <div class="col-12 mb-2">
											    <div class="form-group">
													<label class="text-label">Observation</label>
													<textarea class="form-control input-rounded input-default mb-3" name="observation" placeholder="Observation"></textarea>
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
	$planActions = getAction(null, $GLOBALS["id_copropriete"], $connection);
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Plans d'action</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
					<a href="dashboard.php?page=actions&action=add" type="button" class="btn btn-rounded btn-primary me-2">
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
                                                <th>Date</th>
												<th>Plan d'action</th>
                                                <th>Statut</th>
                                                <th>Avancement</th>
                                                <th>Efficacité</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
											foreach($planActions as $planAction):
												$statutaction = getStatutaction($planAction["id_statutAction"], $connection);
											?>
                                            <tr>
                                                <td><?=date("d/m/Y", strtotime($planAction["dateDebut"]))?></td>
												<td><?=$planAction["nom"]?></td>
                                                <td><?=$statutaction[0]["libelle"]?></td>
                                                <td><?=$planAction["avancement"]?>%</td>
                                                <td><?php if ($planAction["efficacite"] == "1") echo "OUI"; else echo "NON"; ?></td>
                                                <td class="text-center"><a href="./dashboard.php?page=actions&action=update&id=<?=$planAction["id"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a></td>
                                            </tr>
                                            <?php
											endforeach;
											?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Date</th>
												<th>Plan d'action</th>
                                                <th>Statut</th>
                                                <th>Avancement</th>
                                                <th>Efficacité</th>
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