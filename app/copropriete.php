<?php
require_once __DIR__ . '/session.php';
bestcopro_start_session();

// If the user is not logged in redirect to the login page...
if (
    !isset($_SESSION["loggedin"], $_SESSION["id"]) ||
    (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] !== "ImIn") ||
    (isset($_SESSION["id"]) && !is_int(intval($_SESSION["id"])))
) {
    header("Location: ./login.php");
    exit();
}
if (
    $_SESSION["id_usertype"] !== "1" &&
    $_SESSION["id_usertype"] !== "2" &&
    $_SESSION["id_usertype"] !== "3"
) {
    header("Location: ./index.php");
    exit();
}
include_once __DIR__ . "/config/db.php";
include_once __DIR__ . "/controllers/functions.php";

function coproBudgetCleanText($value)
{
    $value = trim((string) $value);
    if ($value === "") {
        return "";
    }

    if (!preg_match("//u", $value)) {
        $converted = @iconv("Windows-1252", "UTF-8//IGNORE", $value);
        if ($converted !== false) {
            return trim($converted);
        }
    }

    return $value;
}

function coproBudgetNormalizeBudget($value)
{
    $value = coproBudgetCleanText($value);
    $ascii = function_exists("iconv") ? @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value) : false;
    $normalized = strtoupper($ascii !== false ? $ascii : $value);

    if (in_array($normalized, ["1", "F", "FONCT", "FONCTIONNEMENT"], true)) {
        return "Fonctionnement";
    }

    if (in_array($normalized, ["2", "I", "INV", "INVESTISSEMENT", "INVESSTISSEMENT"], true)) {
        return "Investissement";
    }

    return $value;
}

function coproBudgetSignature($budget, $poste, $rubrique)
{
    return md5(preg_replace("/\s+/u", " ", trim($budget . "|" . $poste . "|" . $rubrique)));
}

function coproBudgetEnsureReferentielTable($connection)
{
    $request = "CREATE TABLE IF NOT EXISTS referentiel_poste_budgetaire (
        id INT(11) NOT NULL AUTO_INCREMENT,
        code VARCHAR(20) NOT NULL,
        budget ENUM('Fonctionnement', 'Investissement') NOT NULL,
        poste VARCHAR(255) NOT NULL,
        rubrique VARCHAR(255) NOT NULL,
        signature CHAR(32) NOT NULL,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_ref_signature (signature),
        KEY idx_ref_code (code),
        KEY idx_ref_budget_poste (budget, poste(120))
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $connection->query($request) === true;
}

function coproBudgetTypeKey($budget)
{
    return $budget === "Investissement" ? "invest" : "fonct";
}

function coproBudgetSaveReference($connection, $code, $budget, $poste, $rubrique)
{
    $code = coproBudgetCleanText($code);
    $budget = coproBudgetNormalizeBudget($budget);
    $poste = coproBudgetCleanText($poste);
    $rubrique = coproBudgetCleanText($rubrique);

    if ($code === "" || $budget === "" || $poste === "" || $rubrique === "") {
        return ["error" => "Code, type budget, poste et rubrique sont obligatoires."];
    }

    if (!in_array($budget, ["Fonctionnement", "Investissement"], true)) {
        return ["error" => "Type budget invalide."];
    }

    $signature = coproBudgetSignature($budget, $poste, $rubrique);
    $request = "INSERT INTO referentiel_poste_budgetaire (code, budget, poste, rubrique, signature, actif)
        VALUES (?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE code = VALUES(code), actif = 1";

    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sssss", $code, $budget, $poste, $rubrique, $signature);
        if (!$stmt->execute()) {
            return ["error" => $connection->error];
        }
        return [
            "error" => "",
            "reference" => [
                "code" => $code,
                "budget" => $budget,
                "type" => coproBudgetTypeKey($budget),
                "poste" => $poste,
                "rubrique" => $rubrique,
            ],
        ];
    }

    return ["error" => $connection->error];
}

function coproBudgetLoadReferences($connection)
{
    $references = ["fonct" => [], "invest" => []];
    $request = "SELECT code, budget, poste, rubrique FROM referentiel_poste_budgetaire WHERE actif = 1 ORDER BY budget, poste, rubrique";
    if ($stmt = $connection->prepare($request)) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($code, $budget, $poste, $rubrique);
        while ($stmt->fetch()) {
            $references[coproBudgetTypeKey($budget)][] = [
                "code" => $code,
                "budget" => $budget,
                "poste" => $poste,
                "rubrique" => $rubrique,
            ];
        }
    }

    return $references;
}

coproBudgetEnsureReferentielTable($connection);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax_add_budget_reference"])) {
    header("Content-Type: application/json; charset=utf-8");
    $result = coproBudgetSaveReference(
        $connection,
        $_POST["code"] ?? "",
        $_POST["budget"] ?? "",
        $_POST["poste"] ?? "",
        $_POST["rubrique"] ?? ""
    );

    if (($result["error"] ?? "") !== "") {
        echo json_encode(["success" => false, "message" => $result["error"]]);
    } else {
        echo json_encode(["success" => true, "reference" => $result["reference"]], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

$budgetReferences = coproBudgetLoadReferences($connection);
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
    <title>BEST COPRO - Ajout d'une copropriété</title>
    
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images\favicon.png">
	<!-- Form step -->
    <link href="vendor\jquery-smartwizard\dist\css\smart_wizard.min.css" rel="stylesheet">
	<link rel="stylesheet" href="vendor\select2\css\select2.min.css">
	<link href="vendor\jquery-nice-select\css\nice-select.css" rel="stylesheet">
    <link href="css\style.css" rel="stylesheet">
    <link href="css\bestcopro-refresh.css" rel="stylesheet">

	<style>
	<!--
	.file_drag_area {  
		width:100%;  
		height:200px;  
		border:2px dashed #ccc;  
		line-height:200px;  
		text-align:center;  
		font-size:24px;  
	}  
	.file_drag_over {  
		color:#000;  
		border-color:#000;  
	}
	.lds-dual-ring {
	  display: inline-block;
	  width: 80px;
	  height: 80px;
	}
	.lds-dual-ring:after {
	  content: " ";
	  display: block;
	  width: 64px;
	  height: 64px;
	  margin: 8px;
	  border-radius: 50%;
	  border: 6px solid #234F68;
	  border-color: #234F68 transparent #234F68 transparent;
	  animation: lds-dual-ring 1.2s linear infinite;
	}
	@keyframes lds-dual-ring {
	  0% {
		transform: rotate(0deg);
	  }
	  100% {
		transform: rotate(360deg);
	  }
	}
	.nice-select .list {
		max-height: 250px;
		overflow-y: auto !important;
	}
	-->
	</style>

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
	
		<?php include "./header.php"; ?>
		
		<!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body" style="padding-top: 5rem;">
            <!-- row -->
			<div class="container-fluid">
				
				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="mb-4">
						<h2 class="text-primary font-w600 mb-0">Ajouter une copropriété</h2>
						<span>Configuration des paramètres de la copropriété</span>
					</div>
				</div>
				
                <!-- row -->
                <div class="row">
                    <div class="col-xl-12 col-xxl-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title" id="stepName">Informations de la copropriété</h4>
								<input type="hidden" name="id_copropriete" id="id_copropriete" value="">
								<input type="hidden" name="id_exercice" id="id_exercice" value="">
                            </div>
                            <div class="card-body">
								<div id="smartwizard" class="form-wizard">
									<ul class="nav nav-wizard">
										<li><a class="nav-link" href="#copropriete_Info"> 
											<span>1</span> 
										</a></li>
										<li><a class="nav-link" href="#copropriete_Budget">
											<span>2</span>
										</a></li>
										<li><a class="nav-link" href="#copropriete_Lots">
											<span>3</span>
										</a></li>
										<li><a class="nav-link" href="#copropriete_Charges">
											<span>4</span>
										</a></li>
									</ul>
									<div class="tab-content" id="tab-content">
										<div id="copropriete_Info" class="tab-pane" role="tabpanel">
											<div class="row">
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Nom*</label>
														<input type="text" name="nom" class="form-control input-rounded input-default mb-3" placeholder="Nom de la copropriété" required="">
													</div>
												</div>
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Ville*</label>
														<input type="text" name="ville" class="form-control input-rounded input-default mb-3" placeholder="Ville" required="">
													</div>
												</div>
												<div class="col-lg-12 mb-2">
													<div class="form-group">
														<label class="text-label">Adresse*</label>
														<input type="text" name="adresse" class="form-control input-rounded input-default mb-3" placeholder="Adresse de la copropriété" required="">
													</div>
												</div>
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Code postale</label>
														<input type="text" name="codePostale" class="form-control input-rounded input-default mb-3" placeholder="Code postale" required="">
													</div>
												</div>
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">RIB</label>
														<input type="text" name="rib" class="form-control input-rounded input-default mb-3" placeholder="RIB" required="">
													</div>
												</div>
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Nombre de lots*</label>
														<input type="number" min="1" name="nbrLot" class="form-control input-rounded input-default mb-3" placeholder="Nombre de lots" required="">
													</div>
												</div>
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Date d'ouverture de l'exercice*</label>
														<input type="month" name="dateExercice" class="form-control input-rounded input-default mb-3" placeholder="mm/yyyy" required="">
													</div>
												</div>
												<input type="hidden" name="id_syndic" value="<?= $_SESSION["id"] ?>">
												<?php
/*
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Syndic*</label>
														<select name="id_syndic" class="single-select2 form-control wide mb-3">
															<?php
															$syndics = getSyndic(null, null, $connection);
															foreach($syndics as $syndic):
															?>
															<option value="<?=$syndic["id"]?>"><?=$syndic["civilite"]?> <?=$syndic["prenom"]?> <?=$syndic["nom"]?></option>
															<?php
															endforeach;
															?>
														</select>
													</div>
												</div>
												*/
?>
												<div class="col-lg-6 mb-2">
													<div class="form-group">
														<label class="text-label">Préfixe*</label>
														<input type="text" name="prefixe" class="form-control input-rounded" placeholder="Préfixe des références" required="">
													</div>
												</div>
											</div>
										</div>
										<div id="copropriete_Budget" class="tab-pane" role="tabpanel">
											<div class="alert alert-primary alert-alt fade show p-3 mb-4">
												<div class="row">
													<div class="col-lg-6">
														<strong>Les postes budgétaires.</strong>
													</div>
													<div class="col-lg-6 text-end">
														<strong>TOTAL = <span id="totalBudget" class="font-w500">0.00</span> MAD</strong>
													</div>
												</div>
											</div>
											<!-- Nav tabs -->
											<div class="default-tab budget-builder">
												<ul class="nav nav-tabs" role="tablist">
													<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#fonctionnement">Budget de fonctionnement</a></li>
													<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#investissement">Budget d'investissement</a></li>
												</ul>
												<div class="tab-content">
													<div class="tab-pane fade show active" id="fonctionnement" role="tabpanel">
														<div class="pt-4 budget-entry" data-type="fonct" data-budget="Fonctionnement">
															<div class="row align-items-end">
																<div class="col-lg-4 mb-3"><label class="form-label">Poste</label><select class="form-control default-select wide budget-poste-select"><option value="">Choisir un poste</option></select></div>
																<div class="col-lg-4 mb-3"><label class="form-label">Rubrique</label><select class="form-control default-select wide budget-rubrique-select" disabled><option value="">Choisir une rubrique</option></select></div>
																<div class="col-lg-2 mb-3"><label class="form-label">Budget</label><input type="number" min="0" step="0.01" class="form-control input-rounded budget-amount-input" placeholder="0.00"></div>
																<div class="col-lg-2 mb-3"><button type="button" class="btn btn-primary btn-block budget-add-row">Rajouter</button></div>
															</div>
															<button type="button" class="btn btn-outline-primary btn-sm mb-3 budget-open-reference-modal" data-budget="Fonctionnement">Ajouter un nouveau poste budg&eacute;taire</button>
															<div class="table-responsive"><table class="table table-responsive-sm budget-lines-table"><thead><tr><th>Poste</th><th>Rubrique</th><th>Budget</th><th class="text-center">Actions</th></tr></thead><tbody id="budget_rows_fonct"><tr class="budget-empty"><td colspan="4"><p class="text-center mt-3">Aucun poste ajout&eacute;</p></td></tr></tbody><tfoot><tr><th colspan="2">Budget de fonctionnement</th><th><span id="budget_total_fonct">0.00</span> MAD</th><th></th></tr></tfoot></table></div>
															<div id="budget_hidden_fonct"></div>
														</div>
													</div>
													<div class="tab-pane fade" id="investissement" role="tabpanel">
														<div class="pt-4 budget-entry" data-type="invest" data-budget="Investissement">
															<div class="row align-items-end">
																<div class="col-lg-4 mb-3"><label class="form-label">Poste</label><select class="form-control default-select wide budget-poste-select"><option value="">Choisir un poste</option></select></div>
																<div class="col-lg-4 mb-3"><label class="form-label">Rubrique</label><select class="form-control default-select wide budget-rubrique-select" disabled><option value="">Choisir une rubrique</option></select></div>
																<div class="col-lg-2 mb-3"><label class="form-label">Budget</label><input type="number" min="0" step="0.01" class="form-control input-rounded budget-amount-input" placeholder="0.00"></div>
																<div class="col-lg-2 mb-3"><button type="button" class="btn btn-primary btn-block budget-add-row">Rajouter</button></div>
															</div>
															<button type="button" class="btn btn-outline-primary btn-sm mb-3 budget-open-reference-modal" data-budget="Investissement">Ajouter un nouveau poste budg&eacute;taire</button>
															<div class="table-responsive"><table class="table table-responsive-sm budget-lines-table"><thead><tr><th>Poste</th><th>Rubrique</th><th>Budget</th><th class="text-center">Actions</th></tr></thead><tbody id="budget_rows_invest"><tr class="budget-empty"><td colspan="4"><p class="text-center mt-3">Aucun poste ajout&eacute;</p></td></tr></tbody><tfoot><tr><th colspan="2">Budget d'investissement</th><th><span id="budget_total_invest">0.00</span> MAD</th><th></th></tr></tfoot></table></div>
															<div id="budget_hidden_invest"></div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div id="copropriete_Lots" class="tab-pane" role="tabpanel">
											<div class="d-flex flex-wrap p-3 align-items-center mb-4">
												<h4 class="text-primary mb-0">La liste des lots (<span id="lotCounter">0</span>/<span id="nbrLot">0</span>)</h4>
												<button type="button" class="btn btn-rounded btn-primary ms-auto me-1" data-bs-toggle="modal" data-bs-target="#import_lot">
													<span class="btn-icon-start text-primary"><i class="fa fa-upload" aria-hidden="true"></i></span>
													Importer les lots depuis un fichier csv
												</button>
												<div class="modal fade" id="import_lot" tabindex="-1" style="display: none;" aria-hidden="true">
													<div class="modal-dialog modal-lg">
														<div class="modal-content">
															<div class="modal-header">
																<h5 class="modal-title">Importer les lots</h5>
																<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
															</div>
															<div class="modal-body">
																<div class="basic-form">
																	<form>
																		<div class="form-group mb-4">
																			<h5>Faites glisser et déposez le fichier</h5>
																			<div class="file_drag_area">  
																				Glisser et Déposer ici 
																			</div>
																			<div id="uploaded_file" class="text-center mt-3"></div>
																			<input type="file" id="foo" name="file1" style="display: none;" />
																		</div>
																	</form>
																</div>
															</div>
															<div class="modal-footer">
																<button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
																<button type="button" class="btn btn-outline-primary" id="import_csv">Importer</button>
															</div>
														</div>
													</div>
												</div>
												<button type="button" class="btn btn-rounded btn-primary me-1" data-bs-toggle="modal" data-bs-target="#add_lot">
													<span class="btn-icon-start text-primary"><i class="fa fa-plus color-info"></i></span>
													Ajouter un lot
												</button>
												<div class="modal fade" id="add_lot" tabindex="-1" style="display: none;" aria-hidden="true">
													<div class="modal-dialog modal-lg">
														<div class="modal-content">
															<div class="modal-header">
																<h5 class="modal-title">Ajouter un lot</h5>
																<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
															</div>
															<div class="modal-body">
																<div class="basic-form">
																	<form>
																		<input type="hidden" name="update_lot" id="update_lot" data-lot-line="">
																		<input type="hidden" name="id_lot" id="id_lot">
																		<div class="row">
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Type du lot*</label>
																					<select name="id_typeLot" class="default-select form-control input-rounded wide mb-3">
																						<?php
                      $typelots = getTypelot(null, $GLOBALS["connection"]);
                      foreach ($typelots as $typelot): ?>
																						<option value="<?= $typelot["id"] ?>"><?= $typelot["libelle"] ?></option>
																						<?php endforeach;
                      ?>
																					</select>
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Numéro d'immeuble*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="numeroImm" placeholder="Numéro d'immeuble">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Numéro d'étage*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="etage" placeholder="Numéro d'étage">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Numéro du lot*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="numero" placeholder="Numéro du lot">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Titre foncier</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="foncier" placeholder="Titre foncier">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Tantième*</label>
																					<input type="number" class="form-control input-rounded input-defaultmb-3" name="tantieme" placeholder="Tantième">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Date d'acquisition*</label>
																					<input type="date" class="form-control input-rounded input-defaultmb-3" name="dateAcquisition" placeholder="Date d'acquisition">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Date de remise des clés</label>
																					<input type="date" class="form-control input-rounded input-defaultmb-3" name="dateRemiseCle" placeholder="Date de remise des clés">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Propriétaire*</label>
																					<select id="id_proprietaire" name="id_proprietaire" class="single-select2 form-control wide mb-3"></select>
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Type*</label>
																					<select name="id_typeProprietaire" class="default-select form-control input-rounded wide mb-3">
																						<?php
                      $typeproprietaires = getTypeproprietaire(
                          null,
                          $GLOBALS["connection"],
                      );
                      foreach ($typeproprietaires as $typeproprietaire): ?>
																						<option value="<?= $typeproprietaire["id"] ?>"><?= $typeproprietaire[
    "libelle"
] ?></option>
																						<?php endforeach;
                      ?>
																					</select>
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Cumul des impayés N-1</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="impaye1" placeholder="0.00">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Cumul des impayés N-2</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="impaye2" placeholder="0.00">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Cumul des impayés N-3</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="impaye3" placeholder="0.00">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Cumul des impayés N-4</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="impaye4" placeholder="0.00">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Cumul des impayés N-5</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="impaye5" placeholder="0.00">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Impayé promoteur</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="impaye0" placeholder="0.00">
																				</div>
																			</div>
																		</div>
																	</form>
																</div>
															</div>
															<div class="modal-footer">
																<button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
																<button type="button" class="btn btn-outline-primary" id="save_lot">Enregistrer</button>
															</div>
														</div>
													</div>
												</div>
												<button type="button" class="btn btn btn-rounded btn-primary" data-bs-toggle="modal" data-bs-target="#add_proprietaire">
													<span class="btn-icon-start text-primary"><i class="fa fa-plus color-info"></i></span>
													Ajouter un propriétaire
												</button>
												<div class="modal fade" id="add_proprietaire" tabindex="-1" style="display: none;" aria-hidden="true">
													<div class="modal-dialog modal-lg">
														<div class="modal-content">
															<div class="modal-header">
																<h5 class="modal-title">Ajouter un propriétaire</h5>
																<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
															</div>
															<div class="modal-body">
																<div class="basic-form">
																	<form>
																		<div class="row">
																			 <div class="col-2 mb-2">
																				<div class="form-group">
																					<label class="text-label">Civilité*</label>
																					<select name="civiliteProprietaire" class="default-select form-control input-rounded wide mb-3">
																						<option value ="M.">M.</option>
																						<option value="Mme.">Mme.</option>
																						<option value="Mlle.">Mlle.</option>
																						<option value="Mme/M.">Mme/M.</option>
																						<option value="Sté.">Société</option>
																					</select>
																				</div>
																			</div>
																			<div class="col-4  mb-2">
																				<div class="form-group">
																					<label class="text-label">Prénom*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="prenomProprietaire" placeholder="Prénom">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Nom*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="nomProprietaire" placeholder="Nom">
																				</div>
																			</div>
																			<div class="col-12 mb-2">
																				<div class="form-group">
																					<label class="text-label">Email</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="emailProprietaire" placeholder="Email">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Téléphone</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="telephoneProprietaire" placeholder="Téléphone">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Mobile</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="mobileProprietaire" placeholder="Mobile">
																				</div>
																			</div>
																			<div class="col-12 mb-2">
																				<div class="form-group">
																					<label class="text-label">Adresse*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="adresseProprietaire" placeholder="Adresse">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Ville*</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="villeProprietaire" placeholder="Ville">
																				</div>
																			</div>
																			<div class="col-6 mb-2">
																				<div class="form-group">
																					<label class="text-label">Code postal</label>
																					<input type="text" class="form-control input-rounded input-defaultmb-3" name="codePostaleProprietaire" placeholder="Code postal">
																				</div>
																			</div>
																		</div>
																	</form>
																</div>
															</div>
															<div class="modal-footer">
																<button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
																<button type="button" class="btn btn-outline-primary" id="save_proprietaire">Enregistrer</button>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="progress mb-4">
												<div class="progress-bar bg-primary" style="width: 0%; height:6px;" role="progressbar">
												</div>
											</div>
											<div class="table-responsive">
												<table class="table table-responsive-sm">
													<thead>
														<tr>
															<th>Code</th>
															<th>Type</th>
															<th>Titre foncier</th>
															<th>Propriétaire</th>
															<th>Tantième</th>
															<th>Date d'acquisition</th>
															<th>Date de remise des clés</th>
															<th class="text-center">Actions</th>
														</tr>
													</thead>
													<tbody id="list_lots">
														<tr class="list_lots_empty">
															<td colspan="8">
																<p class="text-center mt-3">Aucune donnée disponible dans le tableau</p>
															</td>
														</tr>
													</tbody>
												</table>
											</div>
										</div>
										<div id="copropriete_Charges" class="tab-pane" role="tabpanel">
											<div class="d-flex flex-wrap order-manage p-3 align-items-center mb-4">
												<h4 class="text-primary mb-0">Budget prévisionnel de fonctionnement : <span id="totalBudgetFonct">0.00</span> MAD</h4>
												<h4 class="text-primary mb-0 ms-3">|</h4>
												<h4 class="text-primary mb-0 ms-3">Budget prévisionnel d'investissement : <span id="totalBudgetInvest">0.00</span> MAD</h4>
												<h4 class="text-primary mb-0 ms-auto">Nombre de lots : <span id="nbrLot_bis">0</span></h4>
											</div>
											<div class="basic-form mt-5">
												<form>
													<div class="row">
														<div class="col-4 mb-2">
															<div class="form-group">
																<label class="text-label">Période de paiement*</label>
																<select name="id_periodePaiement" class="default-select form-control input-rounded wide mb-3">
																	<?php
                 $periodepaiements = getPeriodepaiement(
                     null,
                     $GLOBALS["connection"],
                 );
                 foreach ($periodepaiements as $periodepaiement): ?>
																	<option value="<?= $periodepaiement["id"] ?>"><?= $periodepaiement[
    "libelle"
] ?></option>
																	<?php endforeach;
                 ?>
																</select>
															</div>
														</div>
														<div class="col-4 mb-2">
															<div class="form-group">
																<label class="text-label">Mode de répartition des charges de fonctionnement*</label>
																<select id="id_repartitionFonct" name="id_repartitionFonct" class="default-select form-control input-rounded wide mb-3">
																	<?php
                 $repartitionfoncts = getRepartitionfonct(
                     null,
                     $GLOBALS["connection"],
                 );
                 foreach ($repartitionfoncts as $repartitionfonct): ?>
																	<option value="<?= $repartitionfonct["id"] ?>"><?= $repartitionfonct[
    "libelle"
] ?></option>
																	<?php endforeach;
                 ?>
																</select>
															</div>
														</div>
														<div class="col-4 mb-2">
															<div class="form-group">
																<label class="text-label">Mode de répartition des charges d'investissement*</label>
																<select id="id_repartitionInvest" name="id_repartitionInvest" class="default-select form-control input-rounded wide mb-3">
																	<?php
                 $repartitioninvests = getRepartitioninvest(
                     null,
                     $GLOBALS["connection"],
                 );
                 foreach ($repartitioninvests as $repartitioninvest): ?>
																	<option value="<?= $repartitioninvest["id"] ?>"><?= $repartitioninvest[
    "libelle"
] ?></option>
																	<?php endforeach;
                 ?>
																</select>
															</div>
														</div>
													</div>
												</form>
											</div>
											<div class="table-responsive">
												<table class="table table-responsive-sm">
													<thead>
														<tr>
															<th>Code</th>
															<th>Type</th>
															<th>Propriétaire</th>
															<th>Tantième</th>
															<th>Part de fonctionnement</th>
															<th>Part d'investissement</th>
														</tr>
													</thead>
													<tbody id="list_lots_bis">
														<tr class="list_lots_empty">
															<td colspan="8">
																<p class="text-center mt-3">Aucune donnée disponible dans le tableau</p>
															</td>
														</tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
                            </div>
                        </div>
                    </div>
                </div>
				
            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->

		<?php include "./footer.php"; ?>


    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->
	
	<!-- Modal -->
	<div class="modal fade" id="SuccessErreurAlert">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content errorModal">
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


	<div class="modal fade" id="budgetReferenceModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ajouter un poste budg&eacute;taire au r&eacute;f&eacute;rentiel</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<form id="budgetReferenceForm">
						<div class="row">
							<div class="col-md-6 mb-3"><label class="text-label">Code*</label><input type="text" class="form-control input-rounded" name="code" id="budget_ref_code" placeholder="Ex. 61382"></div>
							<div class="col-md-6 mb-3"><label class="text-label">Type budget*</label><select class="default-select form-control input-rounded wide" name="budget" id="budget_ref_budget"><option value="Fonctionnement">Fonctionnement</option><option value="Investissement">Investissement</option></select></div>
							<div class="col-md-6 mb-3"><label class="text-label">Poste*</label><input type="text" class="form-control input-rounded" name="poste" id="budget_ref_poste" placeholder="Ex. GARDIENNAGE"></div>
							<div class="col-md-6 mb-3"><label class="text-label">Rubrique*</label><input type="text" class="form-control input-rounded" name="rubrique" id="budget_ref_rubrique" placeholder="Ex. SECURTIE-JOUR/NUIT"></div>
						</div>
					</form>
					<div class="alert alert-danger d-none" id="budgetReferenceError"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
					<button type="button" class="btn btn-outline-primary" id="saveBudgetReference">Ajouter au r&eacute;f&eacute;rentiel</button>
				</div>
			</div>
		</div>
	</div>

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="vendor\global\global.min.js"></script>
	<!-- Form Steps -->
	<script src="vendor\jquery-smartwizard\dist\js\jquery.smartWizard.js"></script>
	<script src="vendor\select2\js\select2.full.min.js"></script>
	<script src="vendor\jquery-nice-select\js\jquery.nice-select.min.js"></script>
	
	<script src="js\custom.min.js"></script>
	<script src="js\dlabnav-init.js"></script>
	<script src="js\demo.js"></script>
	<script src="js\bestcopro-refresh.js"></script>
	<script>
		$(document).ready(function(){
			var budgetReferences = <?= json_encode($budgetReferences, JSON_UNESCAPED_UNICODE) ?>;
			var budgetLines = { fonct: [], invest: [] };

			function escapeHtml(value) {
				return String(value || '').replace(/[&<>"']/g, function(char) {
					return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
				});
			}

			function updateTabHeight() {
				setTimeout(function() {
					$('#tab-content').css('height', 'auto');
					var currentTabId = $('.tab-pane.active').attr('id');
					if (currentTabId === 'fonctionnement' || currentTabId === 'investissement') {
						$('#tab-content').height($('#copropriete_Budget').outerHeight());
					}
				}, 100);
			}

			function refreshNiceSelect($select) {
				if ($select.next('.nice-select').length) {
					$select.niceSelect('update');
				}
			}

			function getUniquePostes(type) {
				var postes = [];
				(budgetReferences[type] || []).forEach(function(reference) {
					if (postes.indexOf(reference.poste) === -1) {
						postes.push(reference.poste);
					}
				});
				return postes.sort(function(a, b) { return a.localeCompare(b); });
			}

			function populatePosteSelect(type) {
				var $entry = $('.budget-entry[data-type="' + type + '"]');
				var $select = $entry.find('.budget-poste-select');
				var current = $select.val();
				$select.empty().append('<option value="">Choisir un poste</option>');
				getUniquePostes(type).forEach(function(poste) {
					$select.append('<option value="' + escapeHtml(poste) + '">' + escapeHtml(poste) + '</option>');
				});
				$select.val(current && getUniquePostes(type).indexOf(current) !== -1 ? current : '');
				refreshNiceSelect($select);
				populateRubriqueSelect($entry);
			}

			function populateAllPosteSelects() {
				populatePosteSelect('fonct');
				populatePosteSelect('invest');
			}

			function populateRubriqueSelect($entry) {
				var type = $entry.data('type');
				var poste = $entry.find('.budget-poste-select').val();
				var $rubrique = $entry.find('.budget-rubrique-select');
				$rubrique.empty().append('<option value="">Choisir une rubrique</option>');
				if (!poste) {
					$rubrique.prop('disabled', true);
					refreshNiceSelect($rubrique);
					return;
				}
				(budgetReferences[type] || []).forEach(function(reference) {
					if (reference.poste === poste) {
						$rubrique.append('<option value="' + escapeHtml(reference.rubrique) + '">' + escapeHtml(reference.rubrique) + '</option>');
					}
				});
				$rubrique.prop('disabled', false);
				refreshNiceSelect($rubrique);
			}

			function hasReference(type, poste, rubrique) {
				return (budgetReferences[type] || []).some(function(reference) {
					return reference.poste === poste && reference.rubrique === rubrique;
				});
			}

			function addReferenceToUi(reference) {
				var type = reference.type || (reference.budget === 'Investissement' ? 'invest' : 'fonct');
				budgetReferences[type] = budgetReferences[type] || [];
				if (!hasReference(type, reference.poste, reference.rubrique)) {
					budgetReferences[type].push(reference);
				}
				populatePosteSelect(type);
				var $entry = $('.budget-entry[data-type="' + type + '"]');
				$entry.find('.budget-poste-select').val(reference.poste);
				refreshNiceSelect($entry.find('.budget-poste-select'));
				populateRubriqueSelect($entry);
				$entry.find('.budget-rubrique-select').val(reference.rubrique);
				refreshNiceSelect($entry.find('.budget-rubrique-select'));
			}

			function renderBudgetTable(type) {
				var $tbody = $('#budget_rows_' + type);
				$tbody.empty();
				if (budgetLines[type].length === 0) {
					$tbody.append('<tr class="budget-empty"><td colspan="4"><p class="text-center mt-3">Aucun poste ajout&eacute;</p></td></tr>');
				} else {
					budgetLines[type].forEach(function(line, index) {
						$tbody.append('<tr data-index="' + index + '"><td>' + escapeHtml(line.poste) + '</td><td>' + escapeHtml(line.rubrique) + '</td><td><input type="number" min="0" step="0.01" class="form-control input-rounded budget-line-amount" value="' + escapeHtml(line.amount) + '"></td><td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm budget-delete-row"><i class="fa fa-trash"></i></button></td></tr>');
					});
				}
				rebuildBudgetHiddenFields(type);
				recalcBudgetTotals();
				updateTabHeight();
			}

			function rebuildBudgetHiddenFields(type) {
				var prefix = type === 'fonct' ? 'rubrique_' : 'rubrique2_';
				var postePrefix = type === 'fonct' ? '_poste_' : '_poste2_';
				var $container = $('#budget_hidden_' + type);
				$container.empty();
				if (budgetLines[type].length === 0) {
					$container.append('<input type="hidden" name="' + prefix + '1" value="">');
					return;
				}
				budgetLines[type].forEach(function(line, index) {
					var i = index + 1;
					$container.append('<input type="hidden" name="' + prefix + i + '" value="' + escapeHtml(line.poste) + '">');
					$container.append('<input type="hidden" name="' + prefix + i + postePrefix + '1" value="' + escapeHtml(line.rubrique) + '">');
					$container.append('<input type="hidden" class="value" name="' + prefix + i + postePrefix + '1_value" value="' + escapeHtml(line.amount) + '">');
				});
			}

			function recalcBudgetTotals() {
				var totalFonct = 0;
				var totalInvest = 0;
				budgetLines.fonct.forEach(function(line) { totalFonct += parseAmount(line.amount); });
				budgetLines.invest.forEach(function(line) { totalInvest += parseAmount(line.amount); });
				$('#budget_total_fonct').text(totalFonct.toFixed(2));
				$('#budget_total_invest').text(totalInvest.toFixed(2));
				$('#totalBudgetFonct').text(totalFonct.toFixed(2));
				$('#totalBudgetInvest').text(totalInvest.toFixed(2));
				$('#totalBudget').text((totalFonct + totalInvest).toFixed(2));
			}

			function addBudgetLine($entry) {
				var type = $entry.data('type');
				var poste = $entry.find('.budget-poste-select').val();
				var rubrique = $entry.find('.budget-rubrique-select').val();
				var amount = parseAmount($entry.find('.budget-amount-input').val());
				if (poste === '' || rubrique === '' || amount <= 0 || !hasReference(type, poste, rubrique)) {
					$('#erreurMessage').text('Veuillez choisir un poste et une rubrique du referentiel, puis renseigner un budget valide.');
					$('#SuccessErreurAlert').modal('toggle');
					return;
				}
				budgetLines[type].push({ poste: poste, rubrique: rubrique, amount: amount.toFixed(2) });
				$entry.find('.budget-poste-select').val('');
				$entry.find('.budget-amount-input').val('');
				refreshNiceSelect($entry.find('.budget-poste-select'));
				populateRubriqueSelect($entry);
				renderBudgetTable(type);
			}

			rebuildBudgetHiddenFields('fonct');
			rebuildBudgetHiddenFields('invest');
			populateAllPosteSelects();

			$(window).on('resize', function() { updateTabHeight(); });
			$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () { updateTabHeight(); });
			$('body').on('change', '.budget-poste-select', function() { populateRubriqueSelect($(this).closest('.budget-entry')); });
			$('body').on('click', '.budget-add-row', function() { addBudgetLine($(this).closest('.budget-entry')); });
			$('body').on('click', '.budget-delete-row', function() {
				var $row = $(this).closest('tr');
				var type = $row.closest('tbody').attr('id') === 'budget_rows_fonct' ? 'fonct' : 'invest';
				budgetLines[type].splice(parseInt($row.data('index')), 1);
				renderBudgetTable(type);
			});
			$('body').on('change keyup', '.budget-line-amount', function() {
				var $row = $(this).closest('tr');
				var type = $row.closest('tbody').attr('id') === 'budget_rows_fonct' ? 'fonct' : 'invest';
				var index = parseInt($row.data('index'));
				budgetLines[type][index].amount = parseAmount($(this).val()).toFixed(2);
				rebuildBudgetHiddenFields(type);
				recalcBudgetTotals();
			});

			$('body').on('click', '.budget-open-reference-modal', function() {
				var budget = $(this).data('budget') || 'Fonctionnement';
				$('#budgetReferenceForm')[0].reset();
				$('#budget_ref_budget').val(budget);
				refreshNiceSelect($('#budget_ref_budget'));
				$('#budgetReferenceError').addClass('d-none').text('');
				$('#budgetReferenceModal').modal('show');
			});

			$('#saveBudgetReference').on('click', function() {
				var payload = $('#budgetReferenceForm').serializeArray();
				payload.push({ name: 'ajax_add_budget_reference', value: '1' });
				$('#budgetReferenceError').addClass('d-none').text('');
				$.ajax({
					url: './copropriete.php',
					method: 'POST',
					dataType: 'json',
					data: payload,
					success: function(response) {
						if (response && response.success) {
							addReferenceToUi(response.reference);
							$('#budgetReferenceModal').modal('hide');
						} else {
							$('#budgetReferenceError').removeClass('d-none').text((response && response.message) ? response.message : "Impossible d'ajouter la reference.");
						}
					},
					error: function() {
						$('#budgetReferenceError').removeClass('d-none').text("Impossible d'ajouter la reference.");
					}
				});
			});

			function parseAmount(value) {
				value = String(value || '').replace(/\s/g, '').replace(',', '.');
				var amount = parseFloat(value);
				return isNaN(amount) ? 0 : amount;
			}

			function getRowTantieme($input) {
				return parseAmount($input.closest('tr').find('td:eq(3)').text());
			}

			function getLotLine($input, prefix) {
				return String($input.attr('name') || '').replace(prefix, '');
			}

			function getInputTantieme($input, prefix) {
				var lotLine = getLotLine($input, prefix);
				var tantieme = parseAmount($('input[name="tantieme_'+lotLine+'"]').val());
				return tantieme > 0 ? tantieme : getRowTantieme($input);
			}

			function getRepartitionMode($select) {
				var value = String($select.val() || '');
				var label = String($select.find('option:selected').text() || '');
				var niceSelectLabel = String($select.next('.nice-select').find('.current').text() || '');
				var groupNiceSelectLabel = String($select.closest('.form-group').find('.nice-select .current').text() || '');
				label = (label + ' ' + niceSelectLabel).toLowerCase();
				label = (label + ' ' + groupNiceSelectLabel).toLowerCase();
				if (label.normalize) {
					label = label.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
				}
				if (value == '3' || label.indexOf('manuel') !== -1) {
					return 'manual';
				}
				if (value == '2' || label.indexOf('egal') !== -1) {
					return 'equal';
				}
				return 'tantieme';
			}

			function applyRepartitionFonct() {
				var nbrLot = parseInt($('#nbrLot_bis').text()) || $('.partFonct').length;
				var totalBudgetFonct = parseAmount($('#totalBudgetFonct').text());
				var mode = getRepartitionMode($('#id_repartitionFonct'));

				if (mode == 'tantieme') {
					var sommeTantieme = 0;
					$('.tantieme').each(function() {
						sommeTantieme += parseAmount($(this).val());
					});
					$('.partFonct').each(function() {
						var lotLine = getLotLine($(this), 'partFonct_');
						var tantieme = parseAmount($('input[name="tantieme_'+lotLine+'"]').val());
						$(this).val(sommeTantieme > 0 ? (totalBudgetFonct * tantieme / sommeTantieme).toFixed(2) : '0.00');
					});
					$('.partFonct').attr('readonly', true);
				} else if (mode == 'equal' && nbrLot != 0) {
					$('.partFonct').val((totalBudgetFonct / nbrLot).toFixed(2));
					$('.partFonct').attr('readonly', true);
				} else if (mode == 'manual') {
					copyTantiemeToFonct();
				}
			}

			function applyRepartitionInvest() {
				var nbrLot = parseInt($('#nbrLot_bis').text()) || $('.partInv').length;
				var totalBudgetInvest = parseAmount($('#totalBudgetInvest').text());
				var mode = getRepartitionMode($('#id_repartitionInvest'));

				if (mode == 'tantieme') {
					var sommeTantieme = 0;
					$('.tantieme').each(function() {
						sommeTantieme += parseAmount($(this).val());
					});
					$('.partInv').each(function() {
						var lotLine = getLotLine($(this), 'partInv_');
						var tantieme = parseAmount($('input[name="tantieme_'+lotLine+'"]').val());
						$(this).val(sommeTantieme > 0 ? (totalBudgetInvest * tantieme / sommeTantieme).toFixed(2) : '0.00');
					});
					$('.partInv').attr('readonly', true);
				} else if (mode == 'equal' && nbrLot != 0) {
					$('.partInv').val((totalBudgetInvest / nbrLot).toFixed(2));
					$('.partInv').attr('readonly', true);
				} else if (mode == 'manual') {
					copyTantiemeToInvest();
				}
			}

			function applyRepartitions() {
				applyRepartitionFonct();
				applyRepartitionInvest();
			}

			function syncManualVisibleRepartitions() {
				if (getRepartitionMode($('#id_repartitionFonct')) == 'manual') {
					copyTantiemeToFonct();
				}
				if (getRepartitionMode($('#id_repartitionInvest')) == 'manual') {
					copyTantiemeToInvest();
				}
			}

			function copyTantiemeToFonct() {
				$('.partFonct').each(function() {
					$(this).val(getInputTantieme($(this), 'partFonct_').toFixed(2));
				});
				$('.partFonct').attr('readonly', false);
			}

			function copyTantiemeToInvest() {
				$('.partInv').each(function() {
					$(this).val(getInputTantieme($(this), 'partInv_').toFixed(2));
				});
				$('.partInv').attr('readonly', false);
			}

			// SmartWizard initialize
			$("#smartwizard").smartWizard({
				lang: { // Language variables for button
					next: 'Suivant',
					previous: 'Précédent'
				}
			}); 
			$("#smartwizard").on("leaveStep", function(e, anchorObject, currentStepIndex, nextStepIndex, stepDirection) {
				var isStepValid = true;
				if(currentStepIndex == 0){
					if($('input[name="nom"]').val() == ""){
						$('#erreurMessage').text("Le champs \"Nom de la copropriété\" est obligatoire");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if($('input[name="ville"]').val() == ""){
						$('#erreurMessage').text("Le champs \"Ville\" est obligatoire");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if($('input[name="adresse"]').val() == ""){
						$('#erreurMessage').text("Le champs \"Adresse de la copropriété\" est obligatoire");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if($('input[name="nbrLot"]').val() == ""){
						$('#erreurMessage').text("Le champs \"Nombre de lots\" est obligatoire");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if(parseInt($('input[name="nbrLot"]').val()) <= 0){
						$('#erreurMessage').text("Le champs \"Nombre de lots\" doit être un nombre valide");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if($('input[name="dateExercice"]').val() == ""){
						$('#erreurMessage').text("Le champs \"Date d'ouverture de l'exercice\" est obligatoire");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if($('input[name="prefixe"]').val() == ""){
						$('#erreurMessage').text("Le champs \"Préfixe\" est obligatoire");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else {
						$.ajax({
							url: './controllers/copropriete.php',
							method: "POST",
							async: false,
							data: {
								id_copropriete: $('#id_copropriete').val(),
								id_exercice: $('#id_exercice').val(),
								nom: $('input[name="nom"]').val(),
								adresse: $('input[name="adresse"]').val(),
								ville: $('input[name="ville"]').val(),
								codePostale: $('input[name="codePostale"]').val(),
								rib: $('input[name="rib"]').val(),
								nbrLot: $('input[name="nbrLot"]').val(),
								dateExercice: $('input[name="dateExercice"]').val(),
								id_syndic: $('input[name="id_syndic"]').val(),
								prefixe: $('input[name="prefixe"]').val()
							},
							success:function(response) {
								if (response.includes('done|')) {
									var id_copropriete = response.split('|')[1];
									var id_exercice = response.split('|')[2];
									$('#id_copropriete').val(id_copropriete);
									$('#id_exercice').val(id_exercice);
								} else {
									$('#erreurMessage').html(response.includes('|') ? response.split('|')[1] : response);
									$('#SuccessErreurAlert').modal('toggle');
									isStepValid = false;
								}
							},
							error:function() {
								$('#erreurMessage').text("Impossible d'enregistrer la copropriété. Veuillez réessayer.");
								$('#SuccessErreurAlert').modal('toggle');
								isStepValid = false;
							}
						});
					}
				} else if(currentStepIndex == 1){
					if($('#id_exercice').val() == ""){
						$('#erreurMessage').text("Veuillez d'abord enregistrer les informations de la copropriété.");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					}
					var totalBudget = $('#totalBudget').text();
					if (parseFloat(totalBudget) <= 0) {
						$('#erreurMessage').text("Budget est invalide");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					} else if (parseFloat($('#totalBudgetFonct').text()) <= 0) {
						$('#erreurMessage').text("Budget de fonctionnement est invalide");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					}
					var form_data = new FormData();
					$('#copropriete_Budget input').each(
						function(index){
							var input = $(this);
							var name = input.attr('name');
							if (name) {
								form_data.append(name, input.val());
							}
						}
					);
					form_data.append('id_exercice', $('#id_exercice').val());
					$.ajax({
						url : './controllers/copropriete.php',
						type : 'POST',
						async: false,
						dataType: 'HTML',
						cache: false,
						contentType: false,
						processData: false,
						data: form_data,
						success:function(response) {
							if (response.includes('done|')) {
								isStepValid = true;
							} else {
								$('#erreurMessage').html(response.includes('|') ? response.split('|')[1] : response);
								$('#SuccessErreurAlert').modal('toggle');
								isStepValid = false;
							}
						},
						error:function() {
							$('#erreurMessage').text("Impossible d'enregistrer le budget. Veuillez réessayer.");
							$('#SuccessErreurAlert').modal('toggle');
							isStepValid = false;
						}
					});
				} else if(currentStepIndex == 2){
					if(parseInt($('#lotCounter').text()) < parseInt($('#nbrLot').text())){
						$('#erreurMessage').text("Veuillez compléter la liste des lots");
						$('#SuccessErreurAlert').modal('toggle');
						return false;
					}
				}
				if(!isStepValid)
					return false;
				if(nextStepIndex == 3)
					$('.sw .toolbar').append('<button id="finish" class="btn btn-primary" type="button">Terminer</button>');
				else
					$('#finish').remove();
				
				if(nextStepIndex == 0){
					$('#stepName').text('Informations de la copropriété');
				} else if(nextStepIndex == 1){
					$('#stepName').text('Budget prévisionnel');
				} else if(nextStepIndex == 2){
					$('#stepName').text('Informations relatives aux lots');
				} else if(nextStepIndex == 3){ 
					$('#stepName').text('Répartition des charges');
					applyRepartitions();
				}
			});
			$('input[name="nbrLot"]').on("change", function(event) {
				$("#nbrLot").text($(this).val());
				$("#nbrLot_bis").text($(this).val());
			});
			$('.file_drag_area').on('dragover', function(){
				$(this).addClass('file_drag_over');  
				return false;  
			});  
			$('.file_drag_area').on('dragleave', function(){
				$(this).removeClass('file_drag_over');  
				return false;  
			});
			var formDataImport = new FormData();
			$('.file_drag_area').on('drop', function(e){
				e.preventDefault();  
				$(this).removeClass('file_drag_over');  
				var files_list = e.originalEvent.dataTransfer.files;
				formDataImport.append('file', files_list[0]);
				$('#uploaded_file').text(files_list[0].name);
			});
			$('.file_drag_area').on('click', function(){
				$('#foo').click();
				return false;  
			});  
			$('#foo').on('change', function() {
				formDataImport.append('file', $(this).get(0).files[0]);
				$('#uploaded_file').text($(this).get(0).files[0].name);
				return false;
			});
			$("#save_proprietaire").on("click", function(event) {
				if($('input[name="prenomProprietaire"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Prénom\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="nomProprietaire"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Nom\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="adresseProprietaire"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Adresse\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="villeProprietaire"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Ville\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				}
				$.ajax({
					url:"./views/proprietaires.php",
					method:"POST",
					data: {
						civilite: $('select[name="civiliteProprietaire"]').val(),
						prenom: $('input[name="prenomProprietaire"]').val(),
						nom: $('input[name="nomProprietaire"]').val(),
						id_typeProprietaire: $('select[name="id_typeProprietaire"]').val(),
						email: $('input[name="emailProprietaire"]').val(),
						telephone: $('input[name="telephoneProprietaire"]').val(),
						mobile: $('input[name="mobileProprietaire"]').val(),
						adresse: $('input[name="adresseProprietaire"]').val(),
						ville: $('input[name="villeProprietaire"]').val(),
						codePostale: $('input[name="codePostaleProprietaire"]').val()
					},
					success:function(response) {
						if (response.includes('done|')) {
							var newID = response.split('|')[1];
							$('#id_proprietaire').append('<option value="'+newID+'">'+$('select[name="civiliteProprietaire"]').val()+' '+$('input[name="prenomProprietaire"]').val()+' '+$('input[name="nomProprietaire"]').val()+'</option>');
							$('#add_proprietaire').modal('toggle');
						} else {
							$('#erreurMessage').html(response);
							$('#SuccessErreurAlert').modal('toggle');
							return false;
						}
					}
				});
			});
			$("#import_csv").on("click", function(event) {
				if($('#id_copropriete').val() == ""){
					$('#erreurMessage').text("Veuillez d'abord enregistrer les informations de la copropriété avant d'importer les lots.");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				}
				$('#list_lots .list_lots_empty').html('<td colspan="8" class="text-center"><div class="lds-dual-ring"></div></td>');
				var form_data = formDataImport;
				form_data.append('id_copropriete', $('#id_copropriete').val());
				$.ajax({
					url : './controllers/import.php',
					type : 'POST',
					dataType: 'HTML',
					cache: false,
					contentType: false,
					processData: false,
					data: form_data,
					success : function(response){
						var words = response.split('|');
						if (words[0] == 'done') {
							$('#list_lots').html(words[1]);
							$('#list_lots_bis').html(words[2]);
							$('#tab-content').height($('#copropriete_Lots').height());
							$.ajax({
								url:"./views/proprietaires.php",
								method:"POST",
								data: {
									select: 'all',
									id_copropriete: $('#id_copropriete').val()
								},
								success:function(options) {
									if (options.includes('done|')) {
										var newOptions = options.split('|')[1];
										$('#id_proprietaire').html(newOptions);
									}
								}
							});
							$('#lotCounter').text(words[3]);
							var widthProgress = (parseInt($('#lotCounter').text()) * 100)/parseInt($('#nbrLot').text());
							$('.progress-bar').css('width', widthProgress+'%');
							applyRepartitions();
							syncManualVisibleRepartitions();
						} else {
							$('#list_lots .list_lots_empty').html('<td colspan="8" class="text-center"><p class="mt-3">Aucune donnée disponible dans le tableau</p></td>');
							$('#erreurMessage').html(response);
							$('#SuccessErreurAlert').modal('toggle');
						}
					},
					complete : function (data) {
						formDataImport = new FormData();
						$("#foo").val(null);
						$('#uploaded_file').text("");
					}
				});
				$('#import_lot').modal('toggle');
				return false;
			});
			$("#save_lot").on("click", function(event) {
				if($('input[name="numeroImm"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Numéro d'immeuble\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="etage"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Numéro d'étage\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="numero"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Numéro d'étage\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="tantieme"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Tantième\" est obligatoire");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if(parseFloat($('input[name="tantieme"]').val()) <= 0){
					$('#erreurMessage').text("Le champs \"Tantième\" doit être un nombre valide");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				} else if($('input[name="dateAcquisition"]').val() == ""){
					$('#erreurMessage').text("Le champs \"Date d'acquisition\" doit être un nombre valide");
					$('#SuccessErreurAlert').modal('toggle');
					return false;
				}
				if($('#update_lot').val() == "update" && $('#update_lot').attr("data-lot-line") != ""){
					$.ajax({
						url:"./views/lots.php",
						method:"POST",
						data: {
							id_copropriete: $('#id_copropriete').val(),
							code: $('input[name="prefixe"]').val()+$('input[name="numeroImm"]').val()+$('input[name="numero"]').val(),
							id_typeLot: $('select[name="id_typeLot"]').val(),
							numeroImm: $('input[name="numeroImm"]').val(),
							etage: $('input[name="etage"]').val(),
							numero: $('input[name="numero"]').val(),
							foncier: $('input[name="foncier"]').val(),
							tantieme: $('input[name="tantieme"]').val(),
							dateAcquisition: $('input[name="dateAcquisition"]').val(),
							dateRemiseCle: $('input[name="dateRemiseCle"]').val(),
							id_proprietaire: $('select[name="id_proprietaire"]').val(),
							id_typeProprietaire: $('select[name="id_typeProprietaire"]').val(),
							impaye0: $('input[name="impaye0"]').val(),
							impaye1: $('input[name="impaye1"]').val(),
							impaye2: $('input[name="impaye2"]').val(),
							impaye3: $('input[name="impaye3"]').val(),
							impaye4: $('input[name="impaye4"]').val(),
							impaye5: $('input[name="impaye5"]').val(),
							id: $('input[name="id_lot"]').val(),
							update: "true"
						},
						success:function(response) {
							if (response.includes('done|')) {
								var currentLot = $('#update_lot').attr("data-lot-line");
								$('#tdCode_'+currentLot).text($('input[name="prefixe"]').val()+$('input[name="numeroImm"]').val()+$('input[name="numero"]').val());
								$('#tdCode2_'+currentLot).text($('input[name="prefixe"]').val()+$('input[name="numeroImm"]').val()+$('input[name="numero"]').val());
								$('input[name="id_lot_'+currentLot+'"]').val($('input[name="id_lot"]').val());
								$('input[name="id_typeLot_'+currentLot+'"]').val($('select[name="id_typeLot"]').val());
								$('input[name="numeroImm_'+currentLot+'"]').val($('input[name="numeroImm"]').val());
								$('input[name="etage_'+currentLot+'"]').val($('input[name="etage"]').val());
								$('input[name="numero_'+currentLot+'"]').val($('input[name="numero"]').val());
								$('input[name="foncier_'+currentLot+'"]').val($('input[name="foncier"]').val());
								$('input[name="tantieme_'+currentLot+'"]').val($('input[name="tantieme"]').val());
								$('input[name="dateAcquisition_'+currentLot+'"]').val($('input[name="dateAcquisition"]').val());
								$('input[name="dateRemiseCle_'+currentLot+'"]').val($('input[name="dateRemiseCle"]').val());
								$('input[name="id_proprietaire_'+currentLot+'"]').val($('select[name="id_proprietaire"]').val());
								$('input[name="id_typeProprietaire_'+currentLot+'"]').val($('select[name="id_typeProprietaire"]').val());
								$('input[name="impaye0_'+currentLot+'"]').val($('input[name="impaye0"]').val());
								$('input[name="impaye1_'+currentLot+'"]').val($('input[name="impaye1"]').val());
								$('input[name="impaye2_'+currentLot+'"]').val($('input[name="impaye2"]').val());
								$('input[name="impaye3_'+currentLot+'"]').val($('input[name="impaye3"]').val());
								$('input[name="impaye4_'+currentLot+'"]').val($('input[name="impaye4"]').val());
								$('input[name="impaye5_'+currentLot+'"]').val($('input[name="impaye5"]').val());
								$('#tdType_'+currentLot).text($('select[name="id_typeLot"]').find(":selected").text());
								$('#tdType2_'+currentLot).text($('select[name="id_typeLot"]').find(":selected").text());
								$('#tdTitre_'+currentLot).text($('input[name="foncier"]').val());
								$('#tdTitre2_'+currentLot).text($('input[name="foncier"]').val());
								$('#tdProprio_'+currentLot).text($('select[name="id_proprietaire"]').find(":selected").text());
								$('#tdProprio2_'+currentLot).text($('select[name="id_proprietaire"]').find(":selected").text());
								$('#tdTantieme_'+currentLot).text($('input[name="tantieme"]').val());
								$('#tdTantieme2_'+currentLot).text($('input[name="tantieme"]').val());
								$('input[name="partFonct_'+currentLot+'"]').val($('input[name="tantieme"]').val());
								$('input[name="partInv_'+currentLot+'"]').val($('input[name="tantieme"]').val());
								$('#tdAcqui_'+currentLot).text($('input[name="dateAcquisition"]').val());
								$('#tdRemise_'+currentLot).text($('input[name="dateRemiseCle"]').val());
								applyRepartitions();
								syncManualVisibleRepartitions();
								
								$('#update_lot').val("");
								$('#update_lot').attr("data-lot-line", "");
								
								$('#add_lot').modal('toggle');
							} else {
								$('#erreurMessage').html(response);
								$('#SuccessErreurAlert').modal('toggle');
								return false;
							}
						}
					});
				} else if (parseInt($('#lotCounter').text()) < parseInt($('#nbrLot').text())) {
					$.ajax({
						url:"./views/lots.php",
						method:"POST",
						data: {
							id_copropriete: $('#id_copropriete').val(),
							code: $('input[name="prefixe"]').val()+$('input[name="numeroImm"]').val()+$('input[name="numero"]').val(),
							id_typeLot: $('select[name="id_typeLot"]').val(),
							numeroImm: $('input[name="numeroImm"]').val(),
							etage: $('input[name="etage"]').val(),
							numero: $('input[name="numero"]').val(),
							foncier: $('input[name="foncier"]').val(),
							tantieme: $('input[name="tantieme"]').val(),
							dateAcquisition: $('input[name="dateAcquisition"]').val(),
							dateRemiseCle: $('input[name="dateRemiseCle"]').val(),
							id_proprietaire: $('select[name="id_proprietaire"]').val(),
							id_typeProprietaire: $('select[name="id_typeProprietaire"]').val(),
							impaye0: $('select[name="impaye0"]').val(),
							impaye1: $('select[name="impaye1"]').val(),
							impaye2: $('select[name="impaye2"]').val(),
							impaye3: $('select[name="impaye3"]').val(),
							impaye4: $('select[name="impaye4"]').val(),
							impaye5: $('select[name="impaye5"]').val()
						},
						success:function(response) {
							if (response.includes('done|')) {
								var newID = response.split('|')[1];
								var currentLot = parseInt($('#lotCounter').text()) + 1;
								var codeHtml = '';
								codeHtml += '<tr>';
								codeHtml += '<td>';
								codeHtml += '<span id="tdCode_'+currentLot+'">'+$('input[name="prefixe"]').val()+$('input[name="numeroImm"]').val()+$('input[name="numero"]').val()+'</span>';
								codeHtml += '<input type="hidden" name="id_lot_'+currentLot+'" value="'+newID+'">';
								codeHtml += '<input type="hidden" name="id_typeLot_'+currentLot+'" value="'+$('select[name="id_typeLot"]').val()+'">';
								codeHtml += '<input type="hidden" name="numeroImm_'+currentLot+'" value="'+$('input[name="numeroImm"]').val()+'">';
								codeHtml += '<input type="hidden" name="etage_'+currentLot+'" value="'+$('input[name="etage"]').val()+'">';
								codeHtml += '<input type="hidden" name="numero_'+currentLot+'" value="'+$('input[name="numero"]').val()+'">';
								codeHtml += '<input type="hidden" name="foncier_'+currentLot+'" value="'+$('input[name="foncier"]').val()+'">';
								codeHtml += '<input type="hidden" name="tantieme_'+currentLot+'" value="'+$('input[name="tantieme"]').val()+'" class="tantieme">';
								codeHtml += '<input type="hidden" name="dateAcquisition_'+currentLot+'" value="'+$('input[name="dateAcquisition"]').val()+'">';
								codeHtml += '<input type="hidden" name="dateRemiseCle_'+currentLot+'" value="'+$('input[name="dateRemiseCle"]').val()+'">';
								codeHtml += '<input type="hidden" name="id_proprietaire_'+currentLot+'" value="'+$('select[name="id_proprietaire"]').val()+'">';
								codeHtml += '<input type="hidden" name="id_typeProprietaire_'+currentLot+'" value="'+$('select[name="id_typeProprietaire"]').val()+'">';
								codeHtml += '<input type="hidden" name="impaye0_'+currentLot+'" value="'+$('input[name="impaye0"]').val()+'">';
								codeHtml += '<input type="hidden" name="impaye1_'+currentLot+'" value="'+$('input[name="impaye1"]').val()+'">';
								codeHtml += '<input type="hidden" name="impaye2_'+currentLot+'" value="'+$('input[name="impaye2"]').val()+'">';
								codeHtml += '<input type="hidden" name="impaye3_'+currentLot+'" value="'+$('input[name="impaye3"]').val()+'">';
								codeHtml += '<input type="hidden" name="impaye4_'+currentLot+'" value="'+$('input[name="impaye4"]').val()+'">';
								codeHtml += '<input type="hidden" name="impaye5_'+currentLot+'" value="'+$('input[name="impaye5"]').val()+'">';
								codeHtml += '</td>';
								codeHtml += '<td><span id="tdType_'+currentLot+'">'+$('select[name="id_typeLot"]').find(":selected").text()+'</span></td>';
								codeHtml += '<td><span id="tdTitre_'+currentLot+'">'+$('input[name="foncier"]').val()+'</span></td>';
								codeHtml += '<td><span id="tdProprio_'+currentLot+'">'+$('select[name="id_proprietaire"]').find(":selected").text()+'</span></td>';
								codeHtml += '<td><span id="tdTantieme_'+currentLot+'">'+$('input[name="tantieme"]').val()+'</span></td>';
								codeHtml += '<td><span id="tdAcqui_'+currentLot+'">'+$('input[name="dateAcquisition"]').val()+'</span></td>';
								codeHtml += '<td><span id="tdRemise_'+currentLot+'">'+$('input[name="dateRemiseCle"]').val()+'</span></td>';
								codeHtml += '<td><a href="#" class="btn btn-primary shadow btn-xs sharp me-1 edit_lot" data-lot-line="'+currentLot+'"><i class="fas fa-pencil-alt"></i></a></td>';
								codeHtml += '</tr>';
								$('.list_lots_empty').hide();
								$('#list_lots').append(codeHtml);
								$('#lotCounter').text(currentLot);
								var widthProgress = (parseInt($('#lotCounter').text()) * 100)/parseInt($('#nbrLot').text());
								$('.progress-bar').css('width', widthProgress+'%');
								codeHtml = '<tr>';
								codeHtml += '<td><span id="tdCode2_'+currentLot+'">'+$('input[name="prefixe"]').val()+$('input[name="numeroImm"]').val()+$('input[name="numero"]').val()+'</span>';
								codeHtml += '<input type="hidden" name="id_lot2_'+currentLot+'" value="'+newID+'"></td>';
								codeHtml += '<td><span id="tdType2_'+currentLot+'">'+$('select[name="id_typeLot"]').find(":selected").text()+'</span></td>';
								codeHtml += '<td><span id="tdProprio2_'+currentLot+'">'+$('select[name="id_proprietaire"]').find(":selected").text()+'</span></td>';
								codeHtml += '<td><span id="tdTantieme2_'+currentLot+'">'+$('input[name="tantieme"]').val()+'</span></td>';
								codeHtml += '<td><input type="number" class="form-control input-rounded input-defaultmb-3 partFonct" name="partFonct_'+currentLot+'" value="'+$('input[name="tantieme"]').val()+'" placeholder="0.00" readonly></td>';
								codeHtml += '<td><input type="number" class="form-control input-rounded input-defaultmb-3 partInv" name="partInv_'+currentLot+'" value="'+$('input[name="tantieme"]').val()+'" placeholder="0.00" readonly></td>';
								codeHtml += '</tr>';
								$('#list_lots_bis').append(codeHtml);
								$('#tab-content').height($('#copropriete_Lots').height());
								applyRepartitions();
								syncManualVisibleRepartitions();
								
								$('#add_lot').modal('toggle');
							} else {
								$('#erreurMessage').html(response);
								$('#SuccessErreurAlert').modal('toggle');
								return false;
							}
						}
					});
				} else {
					$('#erreurMessage').text("Vous avez atteint la limite du nombre des lots");
					$('#SuccessErreurAlert').modal('toggle');
					
					$('#add_lot').modal('toggle');
				}
			});
			$("body").on("click", '.edit_lot', function(event) {
				var lot_line =  $(this).attr('data-lot-line');
				$('#id_lot').val($('input[name="id_lot_'+lot_line+'"]').val());
				$('#id_typeLot').val($('input[name="id_typeLot_'+lot_line+'"]').val()).niceSelect('update');
				//$('#id_typeLot').niceSelect('update');
				$('input[name="numeroImm"]').val($('input[name="numeroImm_'+lot_line+'"]').val());
				$('input[name="etage"]').val($('input[name="etage_'+lot_line+'"]').val());
				$('input[name="numero"]').val($('input[name="numero_'+lot_line+'"]').val());
				$('input[name="foncier"]').val($('input[name="foncier_'+lot_line+'"]').val());
				$('input[name="tantieme"]').val($('input[name="tantieme_'+lot_line+'"]').val());
				$('input[name="dateAcquisition"]').val($('input[name="dateAcquisition_'+lot_line+'"]').val());
				$('input[name="dateRemiseCle"]').val($('input[name="dateRemiseCle_'+lot_line+'"]').val());
				$('#id_proprietaire').val($('input[name="id_proprietaire_'+lot_line+'"]').val());
				$('#id_typeProprietaire').val($('input[name="id_typeProprietaire_'+lot_line+'"]').val()).niceSelect('update');
				//$('#id_typeProprietaire').niceSelect('update');
				$('input[name="impaye0"]').val($('input[name="impaye0_'+lot_line+'"]').val());
				$('input[name="impaye1"]').val($('input[name="impaye0_'+lot_line+'"]').val());
				$('input[name="impaye2"]').val($('input[name="impaye0_'+lot_line+'"]').val());
				$('input[name="impaye3"]').val($('input[name="impaye0_'+lot_line+'"]').val());
				$('input[name="impaye4"]').val($('input[name="impaye4_'+lot_line+'"]').val());
				$('input[name="impaye5"]').val($('input[name="impaye5_'+lot_line+'"]').val());
				$('#update_lot').val("update");
				$('#update_lot').attr("data-lot-line", lot_line);
				$('#add_lot').modal('toggle');
			});
			$('#id_repartitionFonct').on('change', function() {
				applyRepartitionFonct();
			});
			$('#id_repartitionInvest').on('change', function() {
				applyRepartitionInvest();
			});
			$(document).on('click', '.nice-select .option', function() {
				var selectId = $(this).closest('.nice-select').prev('select').attr('id');
				var fieldLabel = String($(this).closest('.form-group').find('label').text() || '').toLowerCase();
				if (fieldLabel.normalize) {
					fieldLabel = fieldLabel.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
				}
				if (selectId != 'id_repartitionFonct' && fieldLabel.indexOf('fonctionnement') === -1) {
					return;
				}
				var label = String($(this).text() || '').toLowerCase();
				if (label.normalize) {
					label = label.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
				}
				if (label.indexOf('manuel') !== -1) {
					copyTantiemeToFonct();
				} else {
					setTimeout(applyRepartitionFonct, 0);
				}
			});
			$(document).on('click', '.nice-select .option', function() {
				var selectId = $(this).closest('.nice-select').prev('select').attr('id');
				var fieldLabel = String($(this).closest('.form-group').find('label').text() || '').toLowerCase();
				if (fieldLabel.normalize) {
					fieldLabel = fieldLabel.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
				}
				if (selectId != 'id_repartitionInvest' && fieldLabel.indexOf('investissement') === -1) {
					return;
				}
				var label = String($(this).text() || '').toLowerCase();
				if (label.normalize) {
					label = label.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
				}
				if (label.indexOf('manuel') !== -1) {
					copyTantiemeToInvest();
				} else {
					setTimeout(applyRepartitionInvest, 0);
				}
			});
			$("body").on("click", '#finish', function(event) {
				syncManualVisibleRepartitions();
				var form_data = new FormData();
				$('#copropriete_Charges input, #copropriete_Charges select').each(
					function(index){  
						var input = $(this);
						form_data.append(input.attr('name'), input.val());
					}
				);
				form_data.append('montantFonct', $('#totalBudgetFonct').text());
				form_data.append('montantInvest', $('#totalBudgetInvest').text());
				form_data.append('id_exercice', $('#id_exercice').val());
				$.ajax({
					url : './controllers/copropriete.php',
					type : 'POST',
					dataType: 'HTML',
					cache: false,
					contentType: false,
					processData: false,
					data: form_data,
					success:function(response) {
						if (response.includes('done|')) {
							window.location.replace("./index.php");
						}
					}
				});
			});
		});
	</script>
</body>
</html>
