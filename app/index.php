<?php
if(!isset($_SESSION)) {
	session_start();
}
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'], $_SESSION['id']) || (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] !== "ImIn") || (isset($_SESSION['id']) && !is_int(intval($_SESSION['id'])))) {
	header('Location: ./login.php');
	exit;
}
include_once('config/db.php');
include_once('controllers/functions.php');
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
function getVille($connection) {
	$request = "SELECT DISTINCT ville FROM copropriete";
	if ($stmt = $connection->prepare($request)) {
		$stmt->execute();
		$stmt->store_result();

		if ($stmt->num_rows > 0) {
			$stmt->bind_result(
				$ville
			);
			while($stmt->fetch()){
				$result[] = 
					array(
						"ville" => $ville
					);
			}
			return $result;
		} else {
			return [];
		}
	}
}
?>
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
    <title>BEST COPRO - Accueil</title>
    
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images\favicon.png">
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
        <div class="content-body" style="padding-top: 5rem;">
            <!-- row -->
			<div class="container-fluid">
				
				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="mb-4">
						<h2 class="text-primary font-w600 mb-0">Liste des copropriétés</h2>
						<span>Bienvenue dans votre espace de gestion de copropriétés!</span>
					</div>
					<div class="d-flex align-items-center mb-4">
						<div>
							<?php
							if ($_SESSION['id_usertype'] === "1" || $_SESSION['id_usertype'] === "2" || $_SESSION['id_usertype'] === "3") :
							?>
							<a href="copropriete.php" type="button" class="btn btn-rounded btn-primary">
								<span class="btn-icon-start text-primary"><i class="fa fa-plus color-primary"></i></span> Ajouter une copropriété
							</a>
							<?php
							endif;
							?>
						</div>
					</div>
				</div>
                
				<div class="mt-4 d-flex align-items-center flex-wrap search-job bg-white px-0 mb-4 row">
					<div class="col-xl-2 col-xxl-3 search-dropdown d-flex align-items-center">
						<select class="form-control border-0 default-select style-1 h-auto">
							<option value="">Choisir la ville</option>
							<?php
							$villes = getVille($connection);
							foreach($villes as $ville):
							?>
							<option value="<?=$ville["ville"]?>"><?=$ville["ville"]?></option>
							<?php
							endforeach;
							?>
						</select>
					</div>
					<div class="col-xl-2 col-xxl-3 search-dropdown d-flex align-items-center">
						<select class="form-control border-0 default-select style-1 h-auto">
							<option>Nombre de lots</option>
							<option>&lt;= 50</option>
							<option>&lt;= 100</option>
							<option>&lt;= 300</option>
							<option>&lt;= 500</option>
						</select>
					</div>
					<div class="col-xl-8 col-xxl-6 d-md-flex job-title-search pe-0">
						<div class="input-group search-area">
							<input type="text" class="form-control h-auto" placeholder="Chercher une copropriété...">
						<span class="input-group-text"><a href="javascript:void(0)" class="btn btn-primary btn-rounded">Chercher<i class="flaticon-381-search-2 ms-2"></i></a></span>
						</div>	
					</div>
				</div>
				
				<div class="row">
					<div class="col-xl-12">
						<div class="row">
							<?php
							$coproprietes = getCopropriete(null, $connection);
							$relCoproprieteSyndic = getRel_copropriete_syndic($_SESSION["id"], $connection);
							$i = 0;
							foreach($coproprietes as $copropriete):
								if (!in_array($copropriete["id"], $relCoproprieteSyndic) && ($_SESSION['id_usertype'] === "3" || $_SESSION['id_usertype'] === "4"))
									continue;
							?>
							<div class="col-xl-6">
								<div class="card">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-center flex-wrap">
											<div class="d-flex">
												<span class="Studios-info">
													<svg xmlns="http://www.w3.org/2000/svg" width="97" height="97" viewbox="0 0 97 97">
													  <g transform="translate(-0.785)">
														<rect width="97" height="97" rx="12" transform="translate(0.785)" fill="#c5c5c5"></rect>
														<g transform="translate(0.348)">
														  <rect data-name="placeholder" width="97" height="97" rx="12" transform="translate(0.438)" fill="#<?=getSVG($i++)?>"></rect>
														  <ellipse data-name="Ellipse 12" cx="24.359" cy="24.702" rx="24.359" ry="24.702" transform="translate(20.2 27.447)" fill="#fff"></ellipse>
														  <ellipse data-name="Ellipse 11" cx="14.853" cy="15.096" rx="14.853" ry="15.096" transform="translate(49.907 20.585)" fill="#ffe70c" style="mix-blend-mode: multiply;isolation: isolate"></ellipse>
														</g>
													  </g>
													</svg>
												</span>
												<div>
													<h4 class="fs-20 mb-1"><?=$copropriete["nom"]?></h4>
													<span class="mb-3 d-block">Nombre de lots : <?=$copropriete["nbrLot"]?></span>
													<span class="d-block"><i class="fas fa-map-marker-alt me-2"></i><?=$copropriete["adresse"]?>, <?=$copropriete["ville"]?></span>
												</div>
											</div>
											<div class="job-available">
												<a href="dashboard.php?copropriete=<?=$copropriete["id"]?>" class="btn btn-outline-primary btn-rounded">Choisir</a>
											</div>
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
        <!--**********************************
            Content body end
        ***********************************-->

		<?php include('./footer.php'); ?>


    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="vendor\global\global.min.js"></script>
	<script src="vendor\jquery-nice-select\js\jquery.nice-select.min.js"></script>
    <script src="js\custom.min.js"></script>
	<script src="js\dlabnav-init.js"></script>
	<script src="js\demo.js"></script>
	
</body>
</html>