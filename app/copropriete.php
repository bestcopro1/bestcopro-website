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

$rubriquesFonctTemplates = getRubrique(null, null, 1, $connection);
$rubriquesInvestTemplates = getRubrique(null, null, 2, $connection);

$allPostesTemplates = [];
foreach($rubriquesFonctTemplates as $r) {
	$postes = getPoste(null, null, $r['libelle'], $connection);
	$allPostesTemplates[$r['libelle']] = array_column($postes, 'libelle');
}
foreach($rubriquesInvestTemplates as $r) {
	if (!isset($allPostesTemplates[$r['libelle']])) {
		$postes = getPoste(null, null, $r['libelle'], $connection);
		$allPostesTemplates[$r['libelle']] = array_column($postes, 'libelle');
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
    <title>BEST COPRO - Ajout d'une copropriété</title>
    
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images\favicon.png">
	<!-- Form step -->
    <link href="vendor\jquery-smartwizard\dist\css\smart_wizard.min.css" rel="stylesheet">
	<link rel="stylesheet" href="vendor\select2\css\select2.min.css">
	<link href="vendor\jquery-nice-select\css\nice-select.css" rel="stylesheet">
    <link href="css\style.css" rel="stylesheet">

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
	
		<?php include('./header.php'); ?>
		
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
												<input type="hidden" name="id_syndic" value="<?=$_SESSION['id']?>">
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
											<div class="default-tab">
												<ul class="nav nav-tabs" role="tablist">
													<li class="nav-item">
														<a class="nav-link active" data-bs-toggle="tab" href="#fonctionnement">Budget de fonctionnement</a>
													</li>
													<li class="nav-item">
														<a class="nav-link" data-bs-toggle="tab" href="#investissement">Budget d'investissement</a>
													</li>
												</ul>
												<div class="tab-content">
													<div class="tab-pane fade show active" id="fonctionnement" role="tabpanel">
														<div class="pt-4">
															<div class="row mb-4 align-items-end">
																<div class="col-sm-8 col-lg-9 mb-3 mb-sm-0">
																	<label class="form-label">Choisir une rubrique de fonctionnement</label>
																	<select id="select_rubrique_fonct" class="form-control default-select wide">
																		<option value="">-- Choisir une rubrique --</option>
																		<?php foreach($rubriquesFonctTemplates as $r): ?>
																			<option value="<?=htmlspecialchars($r['libelle'])?>"><?=htmlspecialchars($r['libelle'])?></option>
																		<?php endforeach; ?>
																		<option value="NEW">-- Autre (Nouvelle rubrique) --</option>
																	</select>
																</div>
																<div class="col-sm-4 col-lg-3">
																	<button type="button" class="btn btn-primary btn-block" id="btn_add_rubrique_fonct">Ajouter</button>
																</div>
															</div>
															<div id="container_rubriques_fonct">
																<!-- Les rubriques seront ajoutées ici -->
															</div>
															<input type="hidden" id="rubrique_count_fonct" value="1">
														</div>
													</div>
													<div class="tab-pane fade" id="investissement" role="tabpanel">
														<div class="pt-4">
															<div class="row mb-4 align-items-end">
																<div class="col-sm-8 col-lg-9 mb-3 mb-sm-0">
																	<label class="form-label">Choisir une rubrique d'investissement</label>
																	<select id="select_rubrique_invest" class="form-control default-select wide">
																		<option value="">-- Choisir une rubrique --</option>
																		<?php foreach($rubriquesInvestTemplates as $r): ?>
																			<option value="<?=htmlspecialchars($r['libelle'])?>"><?=htmlspecialchars($r['libelle'])?></option>
																		<?php endforeach; ?>
																		<option value="NEW">-- Autre (Nouvelle rubrique) --</option>
																	</select>
																</div>
																<div class="col-sm-4 col-lg-3">
																	<button type="button" class="btn btn-primary btn-block" id="btn_add_rubrique_invest">Ajouter</button>
																</div>
															</div>
															<div id="container_rubriques_invest">
																<!-- Les rubriques seront ajoutées ici -->
															</div>
															<input type="hidden" id="rubrique_count_invest" value="1">
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
																						$typeproprietaires = getTypeproprietaire(null, $GLOBALS["connection"]);
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
																	$periodepaiements = getPeriodepaiement(null, $GLOBALS["connection"]);
																	foreach($periodepaiements as $periodepaiement):
																	?>
																	<option value="<?=$periodepaiement["id"]?>"><?=$periodepaiement["libelle"]?></option>
																	<?php
																	endforeach;
																	?>
																</select>
															</div>
														</div>
														<div class="col-4 mb-2">
															<div class="form-group">
																<label class="text-label">Mode de répartition des charges de fonctionnement*</label>
																<select id="id_repartitionFonct" name="id_repartitionFonct" class="default-select form-control input-rounded wide mb-3">
																	<?php
																	$repartitionfoncts = getRepartitionfonct(null, $GLOBALS["connection"]);
																	foreach($repartitionfoncts as $repartitionfonct):
																	?>
																	<option value="<?=$repartitionfonct["id"]?>"><?=$repartitionfonct["libelle"]?></option>
																	<?php
																	endforeach;
																	?>
																</select>
															</div>
														</div>
														<div class="col-4 mb-2">
															<div class="form-group">
																<label class="text-label">Mode de répartition des charges d'investissement*</label>
																<select id="id_repartitionInvest" name="id_repartitionInvest" class="default-select form-control input-rounded wide mb-3">
																	<?php
																	$repartitioninvests = getRepartitioninvest(null, $GLOBALS["connection"]);
																	foreach($repartitioninvests as $repartitioninvest):
																	?>
																	<option value="<?=$repartitioninvest["id"]?>"><?=$repartitioninvest["libelle"]?></option>
																	<?php
																	endforeach;
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

		<?php include('./footer.php'); ?>


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
	<script>
		$(document).ready(function(){
			var budgetPostesTemplates = <?=json_encode($allPostesTemplates)?>;

			function addRubriqueUI(type, name, templatePostes) {
				var rubrique_count_selector = (type == 1) ? '#rubrique_count_fonct' : '#rubrique_count_invest';
				var container_selector = (type == 1) ? '#container_rubriques_fonct' : '#container_rubriques_invest';
				var prefix = (type == 1) ? 'rubrique_' : 'rubrique2_';
				var poste_prefix = (type == 1) ? '_poste_' : '_poste2_';
				var add_poste_class = (type == 1) ? 'add_poste' : 'add_poste2';
				var del_rubrique_class = (type == 1) ? 'del_rubrique' : 'del_rubrique2';
				
				var rubrique_count = parseInt($(rubrique_count_selector).val());
				
				var codeHtml = '<div class="basic-list-group ' + prefix + rubrique_count + ' mt-4">';
				codeHtml += '<ul class="list-group">';
				codeHtml += '<li class="list-group-item active">';
				codeHtml += '<div class="row align-items-center">';
				codeHtml += '<div class="col-10 col-md-11">';
				codeHtml += '<input type="text" class="form-control input-rounded" name="' + prefix + rubrique_count + '" placeholder="Nouvelle rubrique" value="' + (name || '') + '">';
				codeHtml += '</div>';
				codeHtml += '<div class="col-2 col-md-1 text-end">';
				codeHtml += '<button type="button" class="btn btn-outline-secondary btn-rounded ' + del_rubrique_class + '" data-' + prefix.replace('_', '') + '="' + rubrique_count + '"><i class="fa fa-trash"></i></button>';
				codeHtml += '</div>';
				codeHtml += '</div>';
				codeHtml += '</li>';
				
				var poste_count = 1;
				if (templatePostes && templatePostes.length > 0) {
					templatePostes.forEach(function(pName) {
						codeHtml += renderPosteHtml(type, rubrique_count, poste_count, pName);
						poste_count++;
					});
				} else {
					codeHtml += renderPosteHtml(type, rubrique_count, poste_count, '');
					poste_count++;
				}
				
				codeHtml += '<li class="list-group-item">';
				codeHtml += '<div class="row">';
				codeHtml += '<div class="col-12">';
				codeHtml += '<a href="#" class="btn light btn-primary btn-block ' + add_poste_class + '" data-' + prefix.replace('_', '') + '="' + rubrique_count + '" data-' + (type == 1 ? 'poste' : 'poste2') + '="' + poste_count + '">Ajouter un poste</a>';
				codeHtml += '</div>';
				codeHtml += '</div>';
				codeHtml += '</li>';
				codeHtml += '</ul>';
				codeHtml += '</div>';
				
				$(container_selector).append(codeHtml);
				$(rubrique_count_selector).val(rubrique_count + 1);
				updateTabHeight();
			}

			function renderPosteHtml(type, rubrique_count, poste_count, pName) {
				var prefix = (type == 1) ? 'rubrique_' : 'rubrique2_';
				var poste_prefix = (type == 1) ? '_poste_' : '_poste2_';
				var del_poste_class = (type == 1) ? 'del_poste' : 'del_poste2';
				
				var codeHtml = '<li class="list-group-item ' + prefix + rubrique_count + poste_prefix + poste_count + '">';
				codeHtml += '<div class="row align-items-center">';
				codeHtml += '<div class="col-12 col-md-6 mb-2 mb-md-0">';
				codeHtml += '<input type="text" class="form-control input-rounded" name="' + prefix + rubrique_count + poste_prefix + poste_count + '" placeholder="Nouveau poste" value="' + (pName || '') + '">';
				codeHtml += '</div>';
				codeHtml += '<div class="col-10 col-md-5">';
				codeHtml += '<input type="number" class="form-control input-rounded value" name="' + prefix + rubrique_count + poste_prefix + poste_count + '_value" placeholder="0.00" value="">';
				codeHtml += '</div>';
				codeHtml += '<div class="col-2 col-md-1 text-end">';
				codeHtml += '<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-1 ' + del_poste_class + '" data-' + prefix.replace('_', '') + '="' + rubrique_count + '" data-' + (type == 1 ? 'poste' : 'poste2') + '="' + poste_count + '"></a>';
				codeHtml += '</div>';
				codeHtml += '</div>';
				codeHtml += '</li>';
				return codeHtml;
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

			$(window).on('resize', function() {
				updateTabHeight();
			});

			$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
				updateTabHeight();
			});

			$('#btn_add_rubrique_fonct').on('click', function() {
				var selected = $('#select_rubrique_fonct').val();
				if (!selected) return;
				if (selected === "NEW") {
					addRubriqueUI(1, '', []);
				} else {
					addRubriqueUI(1, selected, budgetPostesTemplates[selected] || []);
				}
				$('#select_rubrique_fonct').val('').niceSelect('update');
			});

			$('#btn_add_rubrique_invest').on('click', function() {
				var selected = $('#select_rubrique_invest').val();
				if (!selected) return;
				if (selected === "NEW") {
					addRubriqueUI(2, '', []);
				} else {
					addRubriqueUI(2, selected, budgetPostesTemplates[selected] || []);
				}
				$('#select_rubrique_invest').val('').niceSelect('update');
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
							form_data.append(input.attr('name'), input.val());
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
			$("body").on("click", '.add_poste', function(event) {
				event.preventDefault();
				var rubrique_count =  parseInt($(this).attr('data-rubrique'));
				var poste_count =  parseInt($(this).attr('data-poste'));
				var codeHtml = renderPosteHtml(1, rubrique_count, poste_count, '');
				$(this).closest('li').before(codeHtml);
				poste_count += 1;
				$(this).attr('data-poste',poste_count);
				updateTabHeight();
				return false;
			});
			$("body").on("click", '.del_rubrique', function(event) {
				event.preventDefault();
				var rubrique_count =  parseInt($(this).attr('data-rubrique'));
				$('.rubrique_'+rubrique_count).remove();
				var totalBudget = 0;
				var totalBudgetFonct = 0;
				var totalBudgetInvest = 0;
				$('#fonctionnement .value').each(function(i) {
					totalBudgetFonct += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#investissement .value').each(function(i) {
					totalBudgetInvest += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#totalBudgetFonct').text(totalBudgetFonct.toFixed(2));
				$('#totalBudgetInvest').text(totalBudgetInvest.toFixed(2));
				totalBudget = totalBudgetFonct + totalBudgetInvest;
				$('#totalBudget').text(totalBudget.toFixed(2));
				updateTabHeight();
				return false;
			});
			$("body").on("click", '.del_poste', function(event) {
				event.preventDefault();
				var rubrique_count =  $(this).attr('data-rubrique');
				var poste_count =  $(this).attr('data-poste');
				$('.rubrique_'+rubrique_count+'_poste_'+poste_count).remove();
				var totalBudget = 0;
				var totalBudgetFonct = 0;
				var totalBudgetInvest = 0;
				$('#fonctionnement .value').each(function(i) {
					totalBudgetFonct += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#investissement .value').each(function(i) {
					totalBudgetInvest += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#totalBudgetFonct').text(totalBudgetFonct.toFixed(2));
				$('#totalBudgetInvest').text(totalBudgetInvest.toFixed(2));
				totalBudget = totalBudgetFonct + totalBudgetInvest;
				$('#totalBudget').text(totalBudget.toFixed(2));
				updateTabHeight();
				return false;
			});
			$("body").on("click", '.add_poste2', function(event) {
				event.preventDefault();
				var rubrique2_count =  parseInt($(this).attr('data-rubrique2'));
				var poste2_count =  parseInt($(this).attr('data-poste2'));
				var codeHtml = renderPosteHtml(2, rubrique2_count, poste2_count, '');
				$(this).closest('li').before(codeHtml);
				poste2_count += 1;
				$(this).attr('data-poste2',poste2_count);
				updateTabHeight();
				return false;
			});
			$("body").on("click", '.del_rubrique2', function(event) {
				event.preventDefault();
				var rubrique2_count =  parseInt($(this).attr('data-rubrique2'));
				$('.rubrique2_'+rubrique2_count).remove();
				var totalBudget = 0;
				var totalBudgetFonct = 0;
				var totalBudgetInvest = 0;
				$('#fonctionnement .value').each(function(i) {
					totalBudgetFonct += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#investissement .value').each(function(i) {
					totalBudgetInvest += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#totalBudgetFonct').text(totalBudgetFonct.toFixed(2));
				$('#totalBudgetInvest').text(totalBudgetInvest.toFixed(2));
				totalBudget = totalBudgetFonct + totalBudgetInvest;
				$('#totalBudget').text(totalBudget.toFixed(2));
				updateTabHeight();
				return false;
			});
			$("body").on("click", '.del_poste2', function(event) {
				event.preventDefault();
				var rubrique2_count =  $(this).attr('data-rubrique2');
				var poste2_count =  $(this).attr('data-poste2');
				$('.rubrique2_'+rubrique2_count+'_poste2_'+poste2_count).remove();
				var totalBudget = 0;
				var totalBudgetFonct = 0;
				var totalBudgetInvest = 0;
				$('#fonctionnement .value').each(function(i) {
					totalBudgetFonct += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#investissement .value').each(function(i) {
					totalBudgetInvest += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#totalBudgetFonct').text(totalBudgetFonct.toFixed(2));
				$('#totalBudgetInvest').text(totalBudgetInvest.toFixed(2));
				totalBudget = totalBudgetFonct + totalBudgetInvest;
				$('#totalBudget').text(totalBudget.toFixed(2));
				updateTabHeight();
				return false;
			});
			$("body").on("change", '.value', function(event) {
				var totalBudget = 0;
				var totalBudgetFonct = 0;
				var totalBudgetInvest = 0;
				$('#fonctionnement .value').each(function(i) {
					totalBudgetFonct += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#investissement .value').each(function(i) {
					totalBudgetInvest += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
				});
				$('#totalBudgetFonct').text(totalBudgetFonct.toFixed(2));
				$('#totalBudgetInvest').text(totalBudgetInvest.toFixed(2));
				totalBudget = totalBudgetFonct + totalBudgetInvest;
				$('#totalBudget').text(totalBudget.toFixed(2));
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
