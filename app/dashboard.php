<?php
if (!isset($_SESSION)) {
    session_start();
}
// If the user is not logged in redirect to the login page...
if (
    !isset($_SESSION["loggedin"], $_SESSION["id"]) ||
    (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] !== "ImIn") ||
    (isset($_SESSION["id"]) && !is_int(intval($_SESSION["id"])))
) {
    header("Location: ./login.php");
    exit();
}
include_once "config/db.php";
include_once "controllers/functions.php";
$connection = $GLOBALS["connection"];
$page = null;
if (isset($_GET["page"])) {
    $page = filter_input(INPUT_GET, "page", FILTER_SANITIZE_STRING);
}
$id_copropriete = null;
if (isset($_GET["copropriete"])) {
    $id_copropriete = filter_input(
        INPUT_GET,
        "copropriete",
        FILTER_SANITIZE_STRING,
    );
    $copropriete = getCopropriete($id_copropriete, $connection);
    if (count($copropriete) > 0) {
        $_SESSION["id_copropriete"] = $copropriete[0]["id"];
        $id_exercice = null;
        $_SESSION["id_exercice"] = "";
    } else {
        header("Location: ./index.php");
        exit();
    }
}
if (
    !isset($_SESSION["id_copropriete"]) ||
    (isset($_SESSION["id_copropriete"]) && $_SESSION["id_copropriete"] === "")
) {
    header("Location: ./index.php");
    exit();
} else {
    $id_copropriete = $_SESSION["id_copropriete"];
}
$id_exercice = null;
if (isset($_GET["exercice"])) {
    $id_exercice = filter_input(INPUT_GET, "exercice", FILTER_SANITIZE_STRING);
    $exercice = getExercice($id_exercice, null, $connection);
    if (count($exercice) > 0) {
        $_SESSION["id_exercice"] = $exercice[0]["id"];
    } else {
        $_SESSION["id_exercice"] = getExercice(
            null,
            $id_copropriete,
            $connection,
        )[0]["id"];
    }
}
if (
    !isset($_SESSION["id_exercice"]) ||
    (isset($_SESSION["id_exercice"]) && $_SESSION["id_exercice"] === "")
) {
    $id_exercice = getExercice(null, $id_copropriete, $connection)[0]["id"];
} else {
    $id_exercice = $_SESSION["id_exercice"];
}
$copropriete = getCopropriete($id_copropriete, $connection);
$date = date("Y-m-d H:i:s");
$echeances = getEcheance(null, $date, null, $connection);
foreach ($echeances as $echeance) {
    $request = "INSERT INTO notificationsyndic (description, date, nomPage, idPage, id_copropriete) 
				VALUES (?, ?, ?, ?, ?)";
    $description = "Rappel de la date d'échéance";
    $nomPage = "echeances";
    if ($insert_stmt = $connection->prepare($request)) {
        $insert_stmt->bind_param(
            "sssss",
            $description,
            $date,
            $nomPage,
            $echeance["id"],
            $echeance["id_copropriete"],
        );
        // Execute the prepared query.
        if (!$insert_stmt->execute()) {
            echo $connection->error;
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-exercice="<?= $_SESSION["id_exercice"] ?>">
<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="copropriété, immeuble, villa, appartement, lot">
	<meta name="author" content="IARoTech">
	<meta name="robots" content="">
	<meta name="description" content="BEST COPRO facilite la vie des copropriétés autonomes">
	
	<!-- PAGE TITLE HERE -->
    <title>BEST COPRO - Tableau de bord</title>
    
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images\favicon.png">
	<!-- Datatable -->
    <link href="vendor\datatables\css\jquery.dataTables.min.css" rel="stylesheet">
	<?php if (
     $page == "actions" &&
     (isset($_GET["action"]) &&
         ($_GET["action"] == "add" || $_GET["action"] == "update"))
 ): ?>
	<!-- Nouislider -->
	<link rel="stylesheet" href="vendor\nouislider\nouislider.min.css">
	<?php endif; ?>
	<?php if (
     $page == "reclamations" &&
     (isset($_GET["action"]) &&
         ($_GET["action"] == "add" || $_GET["action"] == "update"))
 ): ?>
	<!-- lightgallery -->
	<link href="vendor\lightgallery\css\lightgallery.min.css" rel="stylesheet">
	<?php endif; ?>
	<?php if ($page == "dashboard" || $page == null || $page == ""): ?>
	<!-- owl-carousel -->
	<link href="vendor\owl-carousel\owl.carousel.css" rel="stylesheet">
	<?php endif; ?>
	<?php if ($page == "assemblee"): ?>
	<!-- dropzone -->
	<link href="vendor\dropzone\dist\dropzone.css" rel="stylesheet">
	<?php endif; ?>
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
	
		<?php include "./header.php"; ?>

        <!--**********************************
            Sidebar start
        ***********************************-->
        <div class="dlabnav d-print-none">
            <div class="dlabnav-scroll">
				<div class="dropdown header-profile2 ">
					<a class="nav-link " href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
						<div class="header-info2 d-flex align-items-center">
							<img src="<?= $image ?>" alt="">
							<div class="d-flex align-items-center sidebar-info">
								<div>
									<span class="font-w400 d-block"><?= $_SESSION["prenom"] ?></span>
									<small class="text-end font-w400">Superadmin</small>
								</div>	
								<i class="fas fa-chevron-down"></i>
							</div>
							
						</div>
					</a>
					<div class="dropdown-menu dropdown-menu-end">
						<a href="profile.php" class="dropdown-item ">
							<svg xmlns="http://www.w3.org/2000/svg" class="text-primary" width="18" height="18" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
							<span class="ms-2">Profile </span>
						</a>
						<a href="logout.php" class="dropdown-item ai-icon">
							<svg xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
							<span class="ms-2">Se déconnecter </span>
						</a>
					</div>
				</div>
				<ul class="metismenu" id="menu">
					<li class="<?php if ($page == "dashboard" || $page == "" || $page == null) {
         echo "mm-active";
     } ?>">
						<a href="dashboard.php" class="<?php if (
          $page == "dashboard" ||
          $page == "" ||
          $page == null
      ) {
          echo "mm-active";
      } ?>" aria-expanded="false">
							<i class="bi-columns-gap"></i>
							<span class="nav-text">Tableau de bord</span>
						</a>
					</li>
                    <li class="<?php if (
                        $page == "copropriete" ||
                        $page == "lots" ||
                        $page == "situation_immeuble" ||
                        $page == "suivi_cotisations_coproprietaires" ||
                        $page == "creation_poste_budgetaire" ||
                        $page == "contentieux" ||
                        $page == "proprietaires"
                    ) {
                        echo "mm-active";
                    } ?>">
						<a class="has-arrow" href="javascript:void()" aria-expanded="false">
							<i class="bi-building"></i>
							<span class="nav-text">Copropriété</span>
						</a>
						<ul aria-expanded="false">
                            <li class="<?php if ($page == "copropriete") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=copropriete" class="<?php if (
            $page == "copropriete"
        ) {
            echo "mm-active";
        } ?>">Copropriété</a>
							</li>
                            <li class="<?php if ($page == "lots") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=lots" class="<?php if ($page == "lots") {
            echo "mm-active";
        } ?>">Situation de copropriétaire</a>
							</li>
                            <li class="<?php if ($page == "situation_immeuble") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=situation_immeuble" class="<?php if (
            $page == "situation_immeuble"
        ) {
            echo "mm-active";
        } ?>">État des recouvrements et des impayés</a>
							</li>
                            <li class="<?php if ($page == "suivi_cotisations_coproprietaires") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=suivi_cotisations_coproprietaires" class="<?php if (
            $page == "suivi_cotisations_coproprietaires"
        ) {
            echo "mm-active";
        } ?>">Suivi des cotisations des copropriétaires</a>
							</li>
                            <li class="<?php if ($page == "creation_poste_budgetaire") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=creation_poste_budgetaire" class="<?php if (
            $page == "creation_poste_budgetaire"
        ) {
            echo "mm-active";
        } ?>">Creation poste budgétaire</a>
							</li>
                            <li class="<?php if ($page == "contentieux") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=contentieux" class="<?php if (
            $page == "contentieux"
        ) {
            echo "mm-active";
        } ?>">Contentieux</a>
							</li>
                            <li class="<?php if ($page == "proprietaires") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=proprietaires" class="<?php if (
            $page == "proprietaires"
        ) {
            echo "mm-active";
        } ?>">Propriétaires</a>
							</li>
                        </ul>
					</li>
                    <li class="<?php if (
                        $page == "paiements" ||
                        $page == "depenses" ||
                        $page == "fournisseurs" ||
                        $page == "fonctionnement" ||
                        $page == "investissement" ||
                        $page == "suivi_budget"
                    ) {
                        echo "mm-active";
                    } ?>">
						<a class="has-arrow" href="javascript:void()" aria-expanded="false">
							<i class="bi-calculator"></i>
							<span class="nav-text">Gestion financière</span>
						</a>
                        <ul aria-expanded="false">
							<li class="<?php if ($page == "paiements") {
           echo "mm-active";
       } ?>">
								<a href="dashboard.php?page=paiements" class="<?php if ($page == "paiements") {
            echo "mm-active";
        } ?>">Paiements</a>
							</li>
							<li class="<?php if ($page == "depenses") {
           echo "mm-active";
       } ?>">
								<a href="dashboard.php?page=depenses" class="<?php if ($page == "depenses") {
            echo "mm-active";
        } ?>">Dépenses</a>
							</li>
                            <li class="<?php if ($page == "fournisseurs") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=fournisseurs" class="<?php if (
            $page == "fournisseurs"
        ) {
            echo "mm-active";
        } ?>">Fournisseurs</a>
							</li>
							<li class="<?php if ($page == "fonctionnement") {
           echo "mm-active";
       } ?>">
								<a href="dashboard.php?page=fonctionnement" class="<?php if (
            $page == "fonctionnement"
        ) {
            echo "mm-active";
        } ?>">Budget de fonctionnement</a>
							</li>
                            <li class="<?php if ($page == "suivi_budget") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=suivi_budget" class="<?php if (
            $page == "suivi_budget"
        ) {
            echo "mm-active";
        } ?>">Suivi budget</a>
							</li>
                            <li class="<?php if ($page == "investissement") {
                                echo "mm-active";
                            } ?>">
								<a href="dashboard.php?page=investissement" class="<?php if (
            $page == "investissement"
        ) {
            echo "mm-active";
        } ?>">Budget d'investissement</a>
							</li>
                        </ul>
                    </li>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<li class="<?php if ($page == "assemblee") {
         echo "mm-active";
     } ?>">
						<a href="dashboard.php?page=assemblee" class="<?php if ($page == "assemblee") {
          echo "mm-active";
      } ?>" aria-expanded="false">
							<i class="bi-journal-check"></i>
							<span class="nav-text">Assemblée générale</span>
						</a>
					</li>
					<?php endif; ?>
					<li class="<?php if ($page == "reclamations") {
         echo "mm-active";
     } ?>">
						<a href="dashboard.php?page=reclamations" class="<?php if (
          $page == "reclamations"
      ) {
          echo "mm-active";
      } ?>" aria-expanded="false">
							<i class="bi-megaphone"></i>
							<span class="nav-text">Réclamations</span>
						</a>
					</li>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<li class="<?php if ($page == "actions") {
         echo "mm-active";
     } ?>">
						<a href="dashboard.php?page=actions" class="<?php if ($page == "actions") {
          echo "mm-active";
      } ?>" aria-expanded="false">
							<i class="bi-sliders"></i>
							<span class="nav-text">Plans d'action</span>
						</a>
					</li>
					<?php endif; ?>
					<li class="<?php if ($page == "echeances") {
         echo "mm-active";
     } ?>">
						<a href="dashboard.php?page=echeances" class="<?php if ($page == "echeances") {
          echo "mm-active";
      } ?>" aria-expanded="false">
							<i class="bi-stopwatch"></i>
							<span class="nav-text">Échéances</span>
						</a>
					</li>
					<li class="<?php if ($page == "documents") {
         echo "mm-active";
     } ?>">
						<a href="dashboard.php?page=documents" class="<?php if ($page == "documents") {
          echo "mm-active";
      } ?>" aria-expanded="false">
							<i class="bi-stickies"></i>
							<span class="nav-text">Documents</span>
						</a>
					</li>
                </ul>
				<div class="copyright">
					<p><strong>BEST COPRO</strong> © 2022-<?= date("Y") ?> Tous droits réservés</p>
					<p class="fs-12">Conçu et développé avec <span class="heart"></span> par IARoTech</p>
				</div>
			</div>
        </div>
        <!--**********************************
            Sidebar end
        ***********************************-->
		
		<!--**********************************
            Content body start
        ***********************************-->
        <?php getView($page); ?>
		<!--**********************************
            Content body end
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
		<!-- Modal -->
		<div class="modal fade" id="notificationModal">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Notification</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal">
						</button>
					</div>
					<div class="modal-body">
						<div class="text-center mb-4"><i class="fas fa-bell text-warning" style="font-size: 111px;"></i></div>
						<div class="text-center" id="notificationDescription">Êtes-vous sûr de vouloir supprimer cette copropriété ?</div>
					</div>
					<div class="modal-footer text-center">
						<button type="button" class="btn btn-rounded btn-outline-secondary btn-sm" data-bs-dismiss="modal">Rappelle moi plus tard</button>
						<a href="javascript:void(0);" type="button" class="btn btn-rounded btn-primary btn-sm" id="notificationLink">Afficher les détails</a>
					</div>
				</div>
			</div>
		</div>

        <!-- Footer -->
		<?php include "./footer.php"; ?>


    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="vendor\global\global.min.js"></script>
	<!-- Datatable -->
    <script src="vendor\datatables\js\jquery.dataTables.min.js"></script>
    <script src="js\plugins-init\datatables.init.js"></script>
	<?php if (
     $page == "actions" &&
     (isset($_GET["action"]) &&
         ($_GET["action"] == "add" || $_GET["action"] == "update"))
 ): ?>
	<!-- nouislider -->
	<script src="vendor\nouislider\nouislider.min.js"></script>
    <script src="vendor\wnumb\wNumb.js"></script>
    <script src="js\plugins-init\nouislider-init.js"></script>
	<?php endif; ?>
	<?php if ($page == "dashboard" || $page == null || $page == ""): ?>
	<!-- owl-carousel -->
	<script src="vendor\owl-carousel\owl.carousel.js"></script>
	<?php endif; ?>
	<?php if (
     $page == "reclamations" &&
     (isset($_GET["action"]) &&
         ($_GET["action"] == "add" || $_GET["action"] == "update"))
 ): ?>
	<!-- lightgallery -->
	<script src="vendor\lightgallery\js\lightgallery-all.min.js"></script>
	<?php endif; ?>
	<?php if ($page == "assemblee"): ?>
	<!-- dropzone -->
	<script src="vendor\dropzone\dist\dropzone.js"></script>
	<?php endif; ?>
	<script src="vendor\select2\js\select2.full.min.js"></script>
	<script src="vendor\jquery-nice-select\js\jquery.nice-select.min.js"></script>
    <script src="js\custom.min.js"></script>
	<script src="js\dlabnav-init.js"></script>
	<script src="js\demo.js"></script>
	<script>
		$(document).on('change', '.changeExercice', function (e) {
			window.location.replace("./dashboard.php?exercice="+$(this).val());
		});
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
				url : './views/'+url+'.php',
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
						if (words.length == 3) {
							if (words[2].length > 0) {
								$('#blockOFjustificatif iframe').attr('src', words[2]);
								$('#blockOFjustificatif').show();
							}
						} else if (words.length == 4) {
							if (words[1].length > 0 && words[2].length > 0 && words[3].length > 0) {
								$('#newCommentaireDate').text(words[1]);
								$('#newCommentaireSyndic').text(words[2]);
								$('#newCommentaireMessage').text(words[3]);
								$('#newCommentaire').show();
							}
						}
						/*
						if (!$('input[name="update"]').val())
							$(':input').not(':button, :submit, :reset, :hidden').val('');
						*/
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
				},
				error:function(xhr) {
					var message = 'Une erreur est survenue pendant le traitement.';
					if (xhr.responseText) {
						message = xhr.responseText;
					}
					$('#erreurMessage').html(message);
					$('.waitModal').css('display', 'none');
					$('.successModal').css('display', 'none');
					$('.errorModal').css('display', 'flex');
				}
			});
		});
		$('#showPass').on('click', function(){
			var passInput=$("#passInput");
			if(passInput.attr('type')==='password') {
				passInput.attr('type','text');
			} else {
				passInput.attr('type','password');
			}
			return false;
		});
		setInterval(function(){
			$.ajax({
				url:"./views/notifications.php",
				method:"POST",
				data: {
					id_copropriete: <?= $id_copropriete ?>,
					getNotificationsyndic: 'true'
				},
				success:function(notification) {
					if (notification.includes('done|')) {
						var newNotification = notification.split('|');
						$('#notificationDescription').text(newNotification[3]);
						$('#notificationLink').attr('href', './dashboard.php?page='+newNotification[2]+'&action=update&id='+newNotification[1]);
						$('#notificationModal').modal('show');
						$('.pulse-css').show();
					} else {
						$('.pulse-css').hide();
					}
				}
			});
		}, 20000);
	</script>
	<?php if ($page == "paiements"): ?>
	<script>
		$('#id_lot').on('change', function() {
			var id_lot = $(this).val();
			var id_paiement = $(this).attr('data-paiement');
			var action = $(this).attr('data-action');
			$.ajax({
				url:"./views/paiements.php",
				method:"POST",
				data: {
					lot: id_lot,
					paiement: id_paiement,
					action: action
				},
				success:function(response) {
					if (response.includes('done|')) {
						var lesImpayes = response.split('|')[1];
						$('#lesImpayes').html(lesImpayes);
					}
				}
			});
		});
		$('.delPaiementBtn').on('click', function() {
			var id_paiement = $(this).attr('data-id');
			$.ajax({
				url:"./views/paiements.php",
				method:"POST",
				data: {
					id: id_paiement,
					delete : "true"
				},
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						var table = $('#example').DataTable();
						table.row('.trPaiement-'+id_paiement).remove().draw(false);
						$('.delPaiement-'+id_paiement).modal('hide');
						return false;
					} else {
						return false;
					}
				}
			});
		});
		$('#windowPrint').on('click', function() {
			var id_paiement = $(this).attr('data-id');
			window.location.href = './export/export_recu_paiement.php?id=' + encodeURIComponent(id_paiement);
			return false;
		});
	</script>
	<?php endif; ?>
	<?php if ($page == "depenses"): ?>
	<script>
		if ($('#facturesNonPayees').length && !$.fn.DataTable.isDataTable('#facturesNonPayees')) {
			$('#facturesNonPayees').DataTable({
				createdRow: function(row) {
					$(row).addClass('selected');
				},
				language: {
					"url": "json/fr-FR.json"
				}
			});
		}
		$('#depenses_rubrique').on('change', function() {
			var id_rubrique = $(this).val();
			$.ajax({
				url:"./views/depenses.php",
				method:"POST",
				data: {
					select: id_rubrique
				},
				success:function(options) {
					if (options.includes('done|')) {
						var newOptions = options.split('|')[1];
						$('#id_poste').html(newOptions);
					}
				}
			});
		});
		$('.delDepenseBtn').on('click', function() {
			var id_depense = $(this).attr('data-id');
			
			$.ajax({
				url:"./views/depenses.php",
				method:"POST",
				data: {
					id: id_depense,
					delete : "true"
				},
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						window.location.reload();
						return false;
					} else {
						return false;
					}
				}
			});
		});
		function toggleDepensePaiementFields() {
			if ($('.situationPaiement').val() === 'paye') {
				$('.paiement-fields').show();
			} else {
				$('.paiement-fields').hide();
			}
		}
		toggleDepensePaiementFields();
		$('.situationPaiement').on('change', toggleDepensePaiementFields);
		$('input[name="date"]').on('change', function() {
			if (!$('input[name="datePaiement"]').val()) {
				$('input[name="datePaiement"]').val($(this).val());
			}
		});
		$('input[name="montant"]').on('change keyup', function() {
			if (!$('input[name="montantPaye"]').val()) {
				$('input[name="montantPaye"]').val($(this).val());
			}
		});
		$('.reglerDepenseBtn').on('click', function() {
			var id_depense = $(this).attr('data-id');
			var modal = $('.reglerDepense-' + id_depense);
			$.ajax({
				url:"./views/depenses.php",
				method:"POST",
				data: {
					id: id_depense,
					regler: "true",
					datePaiement: modal.find('.regler-date').val(),
					id_modePaiement: modal.find('.regler-mode').val(),
					montantPaye: modal.find('.regler-montant').val()
				},
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						window.location.reload();
					} else {
						$('#erreurMessage').html(response);
						$('.waitModal').css('display', 'none');
						$('.successModal').css('display', 'none');
						$('.errorModal').css('display', 'flex');
						$('#SuccessErreurAlert').modal('show');
					}
				}
			});
		});
	</script>
	<?php endif; ?>
	<?php if (
     $page == "lots" &&
     (isset($_GET["action"]) && $_GET["action"] == "view")
 ): ?>
	<script>
		$('.windowPrint').on('click', function() {
			var id_paiement = $(this).attr('data-id');
			$('#paiement-'+id_paiement).modal('hide');
			window.location.href = './export/export_recu_paiement.php?id=' + encodeURIComponent(id_paiement);
			return false;
		});
	</script>
	<?php endif; ?>
	<?php if (
     $page == "documents" &&
     (isset($_GET["action"]) &&
         ($_GET["action"] == "add" || $_GET["action"] == "update"))
 ): ?>
	<script>
		$('#addTypedocument').on('click', function() {
			$(this).hide();
			$('#id_typedocumentContainer').hide();
			$('#typedocument').show();
			$('#saveTypedocument').show();
		});
		$('#saveTypedocument').on('click', function() {
			$('.waitModal').css('display', 'flex');
			$('.successModal').css('display', 'none');
			$('.errorModal').css('display', 'none');
			$('#SuccessErreurAlert').modal('toggle');
			$.ajax({
				url:"./views/documents.php",
				method:"POST",
				data: {
					typedocument: $('#typedocument').val(),
					update_typedocument : "true"
				},
				success:function(options) {
					if (options.includes('done|') && !options.includes('done|0')) {
						var newOptions = options.split('|')[2];
						$('#id_typedocument').append(newOptions);
						$('#saveTypedocument').hide();
						$('#typedocument').hide();
						$('#id_typedocumentContainer').show();
						$('#addTypedocument').show();
						$('#successMessage').text("Opération effectuée avec succès");
						$('.waitModal').css('display', 'none');
						$('.successModal').css('display', 'flex');
						$('.errorModal').css('display', 'none');
						return false;
					} else {
						if (options.includes('done|0')) $('#erreurMessage').text('Une erreur est survenue');
						else $('#erreurMessage').html(options);
						$('.waitModal').css('display', 'none');
						$('.successModal').css('display', 'none');
						$('.errorModal').css('display', 'flex');
						return false;
					}
				}
			});
		});
	</script>
	<?php endif; ?>
	<?php if ($page == "fonctionnement"): ?>
	<script>
		$(".add_rubrique").on("click", function(event) {
			event.preventDefault();
			var rubrique_count =  parseInt($(this).attr('data-rubrique'));
			var codeHtml = '';
			codeHtml += '<div class="basic-list-group rubrique_'+rubrique_count+' mt-4">';
			codeHtml += '<ul class="list-group">';
			codeHtml += '<li class="list-group-item active">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-11">';
			codeHtml += '<input type="text" class="form-control input-rounded" name="rubrique_'+rubrique_count+'" placeholder="Nouveau poste" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-1">';
			codeHtml += '<button type="button" class="btn btn-outline-secondary btn-rounded del_rubrique" data-rubrique="'+rubrique_count+'"><i class="fa fa-trash"></i></button>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			codeHtml += '<li class="list-group-item rubrique_'+rubrique_count+'_poste_1">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-6">';
			codeHtml += '<input type="text" class="form-control input-rounded" name="rubrique_'+rubrique_count+'_poste_1" placeholder="Nouvelle rubrique" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-5">';
			codeHtml += '<input type="number" class="form-control input-rounded value" name="rubrique_'+rubrique_count+'_poste_1_value" placeholder="0.00" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-1">';
			codeHtml += '<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste" data-rubrique="'+rubrique_count+'" data-poste="1"></a>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			codeHtml += '<li class="list-group-item">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-12">';
			codeHtml += '<a href="#" class="btn light btn-primary btn-block add_poste" data-rubrique="'+rubrique_count+'" data-poste="2">Ajouter une rubrique</a>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			codeHtml += '</ul>';
			codeHtml += '</div>';
			$('.rubrique_'+(rubrique_count - 1)).after(codeHtml);				
			rubrique_count += 1;
			$(this).attr('data-rubrique',rubrique_count);
			$('#tab-content').height($('#copropriete_Budget').height());
			return false;
		});
		$("body").on("click", '.add_poste', function(event) {
			event.preventDefault();
			var rubrique_count =  parseInt($(this).attr('data-rubrique'));
			var poste_count =  parseInt($(this).attr('data-poste'));
			var codeHtml = '';
			codeHtml += '<li class="list-group-item rubrique_'+rubrique_count+'_poste_'+poste_count+'">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-6">';
			codeHtml += '<input type="text" class="form-control input-rounded" name="rubrique_'+rubrique_count+'_poste_'+poste_count+'" placeholder="Nouvelle rubrique" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-5">';
			codeHtml += '<input type="number" class="form-control input-rounded value" name="rubrique_'+rubrique_count+'_poste_'+poste_count+'_value" placeholder="0.00" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-1">';
			codeHtml += '<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste" data-rubrique="'+rubrique_count+'" data-poste="'+poste_count+'"></a>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			$('.rubrique_'+rubrique_count+'_poste_'+(poste_count - 1)).after(codeHtml);
			poste_count += 1;
			$(this).attr('data-poste',poste_count);
			$('#tab-content').height($('#copropriete_Budget').height());
			return false;
		});
		$("body").on("click", '.del_rubrique', function(event) {
			event.preventDefault();
			var rubrique_count =  parseInt($(this).attr('data-rubrique'));
			$('input[name="rubrique_'+rubrique_count+'"]').val("");
			var totalBudget = 0;
			$('.value').each(function(i) {
				totalBudget += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
			});
			$('#montantTotal').val(totalBudget);
			$('#totalBudget').text(totalBudget.toFixed(2));
			$('.rubrique_'+rubrique_count).hide();
			return false;
		});
		$("body").on("click", '.del_poste', function(event) {
			event.preventDefault();
			var rubrique_count =  parseInt($(this).attr('data-rubrique'));
			var poste_count =  parseInt($(this).attr('data-poste'));
			$('input[name="rubrique_'+rubrique_count+'_poste_'+poste_count+'"]').val("");
			$('input[name="rubrique_'+rubrique_count+'_poste_'+poste_count+'_value"]').val("");
			var totalBudget = 0;
			$('.value').each(function(i) {
				totalBudget += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
			});
			$('#montantTotal').val(totalBudget);
			$('#totalBudget').text(totalBudget.toFixed(2));
			$('.rubrique_'+rubrique_count+'_poste_'+poste_count).hide();
			return false;
		});
		$("body").on("change", '.value', function(event) {
			var totalBudget = 0;
			$('.value').each(function(i) {
				totalBudget += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
			});
			$('#totalBudget').text(totalBudget.toFixed(2));
			$('#montantTotal').val(totalBudget.toFixed(2));
		});
	</script>
	<?php endif; ?>
	<?php if ($page == "investissement"): ?>
	<script>
		$(".add_rubrique2").on("click", function(event) {
			event.preventDefault();
			var rubrique2_count =  parseInt($(this).attr('data-rubrique2'));
			var codeHtml = '';
			codeHtml += '<div class="basic-list-group rubrique2_'+rubrique2_count+' mt-4">';
			codeHtml += '<ul class="list-group">';
			codeHtml += '<li class="list-group-item active">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-11">';
			codeHtml += '<input type="text" class="form-control input-rounded" name="rubrique2_'+rubrique2_count+'" placeholder="Nouveau poste" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-1">';
			codeHtml += '<button type="button" class="btn btn-outline-secondary btn-rounded del_rubrique2" data-rubrique2="'+rubrique2_count+'"><i class="fa fa-trash"></i></button>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			codeHtml += '<li class="list-group-item rubrique2_'+rubrique2_count+'_poste2_1">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-6">';
			codeHtml += '<input type="text" class="form-control input-rounded" name="rubrique2_'+rubrique2_count+'_poste2_1" placeholder="Nouvelle rubrique" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-5">';
			codeHtml += '<input type="text" class="form-control input-rounded value" name="rubrique2_'+rubrique2_count+'_poste2_1_value" placeholder="Budget annuel" value="0.00">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-1">';
			codeHtml += '<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste2" data-rubrique2="'+rubrique2_count+'" data-poste2="1"></a>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			codeHtml += '<li class="list-group-item">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-12">';
			codeHtml += '<a href="#" class="btn light btn-primary btn-block add_poste2" data-rubrique2="'+rubrique2_count+'" data-poste2="2">Ajouter une rubrique</a>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			codeHtml += '</ul>';
			codeHtml += '</div>';
			$('.rubrique2_'+(rubrique2_count - 1)).after(codeHtml);				
			rubrique2_count += 1;
			$(this).attr('data-rubrique2',rubrique2_count);
			$('#tab-content').height($('#copropriete_Budget').height());
			return false;
		});
		$("body").on("click", '.add_poste2', function(event) {
			event.preventDefault();
			var rubrique2_count =  parseInt($(this).attr('data-rubrique2'));
			var poste2_count =  parseInt($(this).attr('data-poste2'));
			var codeHtml = '';
			codeHtml += '<li class="list-group-item rubrique2_'+rubrique2_count+'_poste2_'+poste2_count+'">';
			codeHtml += '<div class="row">';
			codeHtml += '<div class="col-6">';
			codeHtml += '<input type="text" class="form-control input-rounded" name="rubrique2_'+rubrique2_count+'_poste2_'+poste2_count+'" placeholder="Nouvelle rubrique" value="">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-5">';
			codeHtml += '<input type="text" class="form-control input-rounded value" name="rubrique2_'+rubrique2_count+'_poste2_'+poste2_count+'_value" placeholder="Budget annuel" value="0.00">';
			codeHtml += '</div>';
			codeHtml += '<div class="col-1">';
			codeHtml += '<a href="#" class="ti-close fs-35 text-secondary las la-times-circle mt-2 del_poste2" data-rubrique2="'+rubrique2_count+'" data-poste2="'+poste2_count+'"></a>';
			codeHtml += '</div>';
			codeHtml += '</div>';
			codeHtml += '</li>';
			$('.rubrique2_'+rubrique2_count+'_poste2_'+(poste2_count - 1)).after(codeHtml);
			poste2_count += 1;
			$(this).attr('data-poste2',poste2_count);
			$('#tab-content').height($('#copropriete_Budget').height());
			return false;
		});
		$("body").on("click", '.del_rubrique2', function(event) {
			event.preventDefault();
			var rubrique2_count =  parseInt($(this).attr('data-rubrique2'));
			$('input[name="rubrique2_'+rubrique2_count+'"]').val("");
			var totalBudget = 0;
			$('.value').each(function(i) {
				totalBudget += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
			});
			$('#totalBudget').text(totalBudget.toFixed(2));
			$('.rubrique2_'+rubrique2_count).hide();
			$('#tab-content').height($('#copropriete_Budget').height());
			return false;
		});
		$("body").on("click", '.del_poste2', function(event) {
			event.preventDefault();
			var rubrique2_count =  parseInt($(this).attr('data-rubrique2'));
			var poste2_count =  parseInt($(this).attr('data-poste2'));
			$('input[name="rubrique2_'+rubrique2_count+'_poste2_'+poste2_count+'"]').val("");
			$('input[name="rubrique2_'+rubrique2_count+'_poste2_'+poste2_count+'_value"]').val("");
			var totalBudget = 0;
			$('.value').each(function(i) {
				totalBudget += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
			});
			$('#totalBudget').text(totalBudget.toFixed(2));
			$('.rubrique2_'+rubrique2_count+'_poste2_'+poste2_count).hide();
			$('#tab-content').height($('#copropriete_Budget').height());
			return false;
		});
		$("body").on("change", '.value', function(event) {
			var totalBudget = 0;
			$('.value').each(function(i) {
				totalBudget += isNaN(parseFloat($(this).val()))?0:parseFloat($(this).val());
			});
			$('#totalBudget').text(totalBudget.toFixed(2));
			$('#montantTotal').val(totalBudget.toFixed(2));
		});
	</script>
	<?php endif; ?>
	<?php if (
     $page == "reclamations" &&
     (isset($_GET["action"]) &&
         ($_GET["action"] == "add" || $_GET["action"] == "update"))
 ): ?>
	<script>
		$(document).on('change', '.default-select', function (e) {
			if ($(this).val() == "1")
				$('#commentaire').show();
			else if($(this).val() == "2")
				$('#commentaire').hide();
		});
	</script>
	<?php endif; ?>
	<?php if ($page == "dashboard" || $page == null || $page == ""): ?>
	<script>
		function JobickCarousel()
			{

				/*  testimonial one function by = owl.carousel.js */
				jQuery('.front-view-slider').owlCarousel({
					loop:false,
					margin:30,
					nav:true,
					autoplaySpeed: 3000,
					navSpeed: 3000,
					autoWidth:true,
					paginationSpeed: 3000,
					slideSpeed: 3000,
					smartSpeed: 3000,
					autoplay: false,
					animateOut: 'fadeOut',
					dots:true,
					navText: ['', ''],
					responsive:{
						0:{
							items:1
						},
						
						480:{
							items:1
						},			
						
						767:{
							items:3
						},
						1750:{
							items:3
						}
					}
				})
			}

			jQuery(window).on('load',function(){
				setTimeout(function(){
					JobickCarousel();
				}, 1000); 
			});
	</script>
	<?php endif; ?>
	<?php if ($page == "copropriete"): ?>
	<script>
		$('#deleteCoproprieteBtn').on('click', function() {
			var id_copropriete = $(this).attr('data-id');
			$.ajax({
				url:"./views/copropriete.php",
				method:"POST",
				data: {
					id: id_copropriete,
					delete : "true"
				},
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						window.location.replace("./index.php");
						return false;
					} else {
						return false;
					}
				}
			});
		});
	</script>
	<?php endif; ?>
	<?php if ($page == "contentieux"): ?>
	<script>
		$("#saveContentieux").on("click", function(event) {
			$('.waitModal').css('display', 'flex');
			$('.successModal').css('display', 'none');
			$('.errorModal').css('display', 'none');
			$('#SuccessErreurAlert').modal('toggle');
			var form_data = new FormData();
			var url = $(this).attr("data-url");
			$('.content-body input, .content-body select, .content-body textarea').each(
				function(index){  
					var input = $(this);
					form_data.append(input.attr('name'), input.val());
				}
			);
			$.ajax({
				url : './views/'+url+'.php',
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
						if (words.length == 3) {
							if (words[2].length > 0) {
								$('#DZ_W_TimeLine ul').append(words[2]);
								$('#Historique').show();
							}
						}
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
		$('.delContentieuxBtn').on('click', function() {
			var id_contentieux = $(this).attr('data-id');
			
			$.ajax({
				url:"./views/contentieux.php",
				method:"POST",
				data: {
					id: id_contentieux,
					delete : "true"
				},
				success:function(response) {
					if (response.includes('done|') && !response.includes('done|0')) {
						var table = $('#example').DataTable();
						table.row('.trContentieux-'+id_contentieux).remove().draw(false);
						$('.delContentieux-'+id_contentieux).modal('hide');
						return false;
					} else {
						return false;
					}
				}
			});
		});
	</script>
	<?php endif; ?>
	<?php if ($page == "assemblee"): ?>
	<script>
		Dropzone.options.mydz = {
			dictDefaultMessage: "Glisser et déposez vos fichiers ici",
			maxFilesize: 5
		};
	</script>
	<?php endif; ?>
</body>
</html>
