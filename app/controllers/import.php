<?php
include_once(__DIR__.'/../config/db.php');

$connection = $GLOBALS["connection"];
/*
$files = glob("../upload/*");
foreach($files as $file){
	if(is_file($file)) {
		unlink($file);
	}
}
*/
function id_typeLot($typelot, $connection) {
	$request = "SELECT id FROM typelot WHERE libelle LIKE ? LIMIT 1";
	if ($stmt = $connection->prepare($request)) {
		$stmt->bind_param('s', $typelot);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);
        $stmt->fetch();
		if ($stmt->num_rows == 1) {
			return $id;
		} else {
			return "";
		}
	}
}
function id_typeProprietaire($typeProprietaire, $connection) {
	$request = "SELECT id FROM typeproprietaire WHERE libelle LIKE ? LIMIT 1";
	if ($stmt = $connection->prepare($request)) {
		$stmt->bind_param('s', $typeProprietaire);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);
        $stmt->fetch();
		if ($stmt->num_rows == 1) {
			return $id;
		} else {
			return "";
		}
	}
}
function id_proprietaire($nom, $prenom, $telephone, $connection) {
	$request = "SELECT id FROM proprietaire WHERE prenom LIKE ? AND nom LIKE ? AND (telephone LIKE ? OR mobile LIKE ?) LIMIT 1";
	if ($stmt = $connection->prepare($request)) {
		$stmt->bind_param('ssss', $prenom, $nom, $telephone, $telephone);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id);
        $stmt->fetch();
		if ($stmt->num_rows == 1) {
			return $id;
		} else {
			return "";
		}
	}
}
function getPassword( $length = 8 ) {
    //$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $password = substr( str_shuffle( $chars ), 0, $length );
    return $password;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_FILES['file'], $_POST['id_copropriete'])) {
		echo "Aucun fichier n'a ete recu. Verifiez la taille du fichier CSV et les limites PHP upload_max_filesize/post_max_size.";
		exit();
	}
	if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
		$uploadErrors = [
			UPLOAD_ERR_INI_SIZE => "Le fichier depasse la limite upload_max_filesize du serveur.",
			UPLOAD_ERR_FORM_SIZE => "Le fichier depasse la limite autorisee par le formulaire.",
			UPLOAD_ERR_PARTIAL => "Le fichier a ete envoye partiellement.",
			UPLOAD_ERR_NO_FILE => "Aucun fichier n'a ete selectionne.",
			UPLOAD_ERR_NO_TMP_DIR => "Le dossier temporaire PHP est manquant.",
			UPLOAD_ERR_CANT_WRITE => "Le serveur n'a pas pu ecrire le fichier importe.",
			UPLOAD_ERR_EXTENSION => "Une extension PHP a bloque l'import du fichier.",
		];
		echo $uploadErrors[$_FILES['file']['error']] ?? "Erreur inconnue pendant l'import du fichier.";
		exit();
	}
	if ($_FILES['file']['name'] == "") {
		echo "Aucun fichier CSV n'a ete selectionne ou le fichier depasse la limite d'upload du serveur.";
		exit();
	}
	if ($_POST['id_copropriete'] == "") {
		echo "La copropriete cible est introuvable pour cet import.";
		exit();
	}
	if ($_FILES['file']['name'] != "" && $_POST['id_copropriete'] != "") {
		$id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
		$importFileType = pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
		$importFileType = strtolower($importFileType);
		if ($importFileType !== "csv") {
			echo "Veuillez importer un fichier CSV.";
			exit();
		}
		$uploadDir = __DIR__."/../upload";
		if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
			echo "Le dossier d'import est introuvable et n'a pas pu etre cree.";
			exit();
		}
		if (!is_writable($uploadDir)) {
			echo "Le dossier d'import n'est pas accessible en ecriture.";
			exit();
		}
		$location = __DIR__."/../upload/import-lots_".date('Y-m-d-H-i-s').".".$importFileType;
		if (!move_uploaded_file($_FILES['file']['tmp_name'],$location)) {
			echo "Le fichier CSV n'a pas pu etre enregistre sur le serveur.";
			exit();
		}
		if (($handle = fopen($location, "r")) !== FALSE) {
			fgetcsv($handle);
			$counter = 1;
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				++$counter;
				for ($i = 0; $i < count($data); $i++)
					$data[$i] = utf8_encode($data[$i]);
				if ($data[0] != "Appartement" && $data[0] != "Local commercial" && $data[0] != "Magasin" && $data[0] != "Villa") {
					echo "Veuillez vous assurer du type du lot dans la ligne : ".$counter; 
					exit();
				} elseif ($data[1] == "") {
					echo "Veuillez vous assurer du code du lot dans la ligne : ".$counter; 
					exit();
				} elseif ($data[2] == "") {
					echo "Veuillez vous assurer de l'immeuble dans la ligne : ".$counter; 
					exit();
				} elseif ($data[3] == "" || !is_numeric($data[3])) {
					echo "Veuillez vous assurer de l'étage dans la ligne : ".$counter."<br>L'étage doit être un entier supérieur ou égal à 0"; 
					exit();
				} elseif ($data[4] == "") {
					echo "Veuillez vous assurer du numéro du lot dans la ligne : ".$counter; 
					exit();
				} elseif (trim($data[6]) != "" && (count(explode("/", trim($data[6]))) != 3 || strlen(explode("/", trim($data[6]))[0]) != 2 || strlen(explode("/", trim($data[6]))[1]) != 2 || strlen(explode("/", trim($data[6]))[2]) != 4 || !checkdate(explode("/", trim($data[6]))[1], explode("/", trim($data[6]))[0], explode("/", trim($data[6]))[2]))) {
					echo "Veuillez vous assurer de la date d'acquisition dans la ligne : ".$counter; 
					exit();
				} elseif ($data[7] == "" || !is_numeric($data[7]) || floatval($data[7]) <= 0) {
					echo "Veuillez vous assurer du tantième dans la ligne : ".$counter."<br>Le tantième doit être un nombre flottant non nul"; 
					exit();
				} elseif ($data[14] != "Résident" && $data[14] != "Promoteur") {
					echo "Veuillez vous assurer du type du propriétaire dans la ligne : ".$counter; 
					exit();
				} elseif ($data[16] == "") {
					echo "Veuillez vous assurer du nom du propriétaire dans la ligne : ".$counter; 
					exit();
				} elseif ($data[17] == "") {
					echo "Veuillez vous assurer du prénom du propriétaire dans la ligne : ".$counter; 
					exit();
				}
			}
			fclose($handle);
		}
		$request = "DELETE FROM lot WHERE id_copropriete = ?";
		if ($insert_stmt = $connection->prepare($request)) {
			$insert_stmt->bind_param('s', $id_copropriete);
			// Execute the prepared query.
			if (! $insert_stmt->execute()) {
				echo $connection->error;
				exit();
			}
		}
		if (($handle = fopen($location, "r")) !== FALSE) {
			fgetcsv($handle);
			$counter = 0;
			$codeHtml = '';
			$codeHtml2 = '';
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				for ($i = 0; $i < count($data); $i++)
					$data[$i] = utf8_encode($data[$i]);
				$counter += 1;
				$code = trim($data[1]);
				$id_typeLot = id_typeLot(trim($data[0]), $connection);
				if (trim($data[6]) == "")
					$dateAcquisition = null;
				else {
					$dateAcquisition = sprintf("%04d-%02d-%02d", explode("/", trim($data[6]))[2], explode("/", trim($data[6]))[1], explode("/", trim($data[6]))[0]);
				}
				$dateRemiseCle = $dateAcquisition;
				$id_proprietaire = id_proprietaire(trim($data[16]), trim($data[17]), trim($data[22]), $connection);
				if (trim($data[15]) != "M." && trim($data[15]) != "Mme." && trim($data[15]) != "Mlle." && trim($data[15]) != "Mme/M." && trim($data[15]) != "Sté.")
					$civilite = "Mme/M.";
				else 
					$civilite = trim($data[15]);
				if ($id_proprietaire == "") {
					$date = date('Y-m-d H:i:s');
					$prenom = trim($data[17]);
					$nom = trim($data[16]);
					$email = trim($data[21]);
					$telephone = trim($data[22]);
					$mobile = trim($data[23]);
					$adresse = trim($data[18]);
					$ville = trim($data[19]);
					$codePostale = trim($data[20]);
					$request = "INSERT INTO proprietaire (civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
					if ($insert_stmt = $connection->prepare($request)) {
						$insert_stmt->bind_param('sssssssss', $civilite, $prenom, $nom, $email, $telephone, $mobile, $adresse, $ville, $codePostale);
						// Execute the prepared query.
						if (! $insert_stmt->execute()) {
							echo $connection->error;
							exit();
						}
					}
					$id_proprietaire = $connection->insert_id;
				}
				$id_typeProprietaire = id_typeProprietaire(trim($data[14]), $connection);
				if ($id_typeProprietaire == "")
					$id_typeProprietaire = 1;
				$request = "INSERT INTO lot (code, id_typeLot, numeroImm, etage, numero, foncier, tantieme, dateAcquisition, dateRemiseCle, id_proprietaire, id_typeProprietaire, impaye0, impaye1, impaye2, impaye3, impaye4, impaye5, id_copropriete) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				if ($insert_stmt = $connection->prepare($request)) {
					$numeroImm = trim($data[2]);
					$etage = intval(trim($data[3]));
					$numero = trim($data[4]);
					$foncier = trim($data[5]);
					$tantieme = trim($data[7]);
					$impaye0 = floatval(trim($data[13]));
					$impaye1 = floatval(trim($data[12]));
					$impaye2 = floatval(trim($data[11]));
					$impaye3 = floatval(trim($data[10]));
					$impaye4 = floatval(trim($data[9]));
					$impaye5 = floatval(trim($data[8]));
					$insert_stmt->bind_param('ssssssssssssssssss', $code, $id_typeLot, $numeroImm, $etage, $numero, $foncier, $tantieme, $dateAcquisition, $dateRemiseCle, $id_proprietaire, $id_typeProprietaire, $impaye0, $impaye1, $impaye2, $impaye3, $impaye4, $impaye5, $id_copropriete);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
				$id_lot = $connection->insert_id;
				
				$password = getPassword();
				$token = md5(uniqid($id_lot, true));
				
				$request = "UPDATE lot SET password=?, token=? WHERE id=?";

				if ($insert_stmt = $connection->prepare($request)) {
					$insert_stmt->bind_param('sss', $password, $token, $id_lot);
					// Execute the prepared query.
					if (! $insert_stmt->execute()) {
						echo $connection->error;
						exit();
					}
				}
				
				$codeHtml .= '<tr>';
				$codeHtml .= '<td>';
				$codeHtml .= '<span id="tdCode_'.$counter.'">'.trim($data[1]).'</span>';
				$codeHtml .= '<input type="hidden" name="id_lot_'.$counter.'" value="'.$id_lot.'">';
				$codeHtml .= '<input type="hidden" name="id_typeLot_'.$counter.'" value="'.$id_typeLot.'">';
				$codeHtml .= '<input type="hidden" name="numeroImm_'.$counter.'" value="'.trim($data[2]).'">';
				$codeHtml .= '<input type="hidden" name="etage_'.$counter.'" value="'.trim($data[3]).'">';
				$codeHtml .= '<input type="hidden" name="numero_'.$counter.'" value="'.trim($data[4]).'">';
				$codeHtml .= '<input type="hidden" name="foncier_'.$counter.'" value="'.trim($data[5]).'">';
				$codeHtml .= '<input type="hidden" name="tantieme_'.$counter.'" value="'.trim($data[7]).'" class="tantieme">';
				$codeHtml .= '<input type="hidden" name="dateAcquisition_'.$counter.'" value="'.$dateAcquisition.'">';
				$codeHtml .= '<input type="hidden" name="dateRemiseCle_'.$counter.'" value="'.$dateRemiseCle.'">';
				$codeHtml .= '<input type="hidden" name="id_proprietaire_'.$counter.'" value="'.$id_proprietaire.'">';
				$codeHtml .= '<input type="hidden" name="id_typeProprietaire_'.$counter.'" value="'.$id_typeProprietaire.'">';
				$codeHtml .= '<input type="hidden" name="impaye0_'.$counter.'" value="'.trim($data[13]).'">';
				$codeHtml .= '<input type="hidden" name="impaye1_'.$counter.'" value="'.trim($data[12]).'">';
				$codeHtml .= '<input type="hidden" name="impaye2_'.$counter.'" value="'.trim($data[11]).'">';
				$codeHtml .= '<input type="hidden" name="impaye3_'.$counter.'" value="'.trim($data[10]).'">';
				$codeHtml .= '<input type="hidden" name="impaye4_'.$counter.'" value="'.trim($data[9]).'">';
				$codeHtml .= '<input type="hidden" name="impaye5_'.$counter.'" value="'.trim($data[8]).'">';
				$codeHtml .= '</td>';
				$codeHtml .= '<td><span id="tdType_'.$counter.'">'.trim($data[0]).'</span></td>';
				$codeHtml .= '<td><span id="tdTitre_'.$counter.'">'.trim($data[5]).'</span></td>';
				$codeHtml .= '<td><span id="tdProprio_'.$counter.'">'.$civilite.' '.trim($data[17]).' '.trim($data[16]).'</span></td>';
				$codeHtml .= '<td><span id="tdTantieme_'.$counter.'">'.trim($data[7]).'</span></td>';
				$codeHtml .= '<td><span id="tdAcqui_'.$counter.'">'.trim($data[6]).'</span></td>';
				$codeHtml .= '<td><span id="tdRemise_'.$counter.'">'.trim($data[6]).'</span></td>';
				$codeHtml .= '<td><a href="#" class="btn btn-primary shadow btn-xs sharp me-1 edit_lot" data-lot-line="'.$counter.'"><i class="fas fa-pencil-alt"></i></a></td>';
				$codeHtml .= '</tr>';
				$codeHtml2 .= '<tr>';
				$codeHtml2 .= '<td><span id="tdCode2_'.$counter.'">'.trim($data[1]);
				$codeHtml2 .= '<input type="hidden" name="id_lot2_'.$counter.'" value="'.$id_lot.'">'.'</td>';
				$codeHtml2 .= '<td><span id="tdType2_'.$counter.'">'.trim($data[0]).'</td>';
				$codeHtml2 .= '<td><span id="tdProprio2_'.$counter.'">'.$civilite.' '.trim($data[17]).' '.trim($data[16]).'</td>';
				$codeHtml2 .= '<td><span id="tdTantieme2_'.$counter.'">'.trim($data[7]).'</td>';
				$codeHtml2 .= '<td><input type="number" class="form-control input-default partFonct" name="partFonct_'.$counter.'" placeholder="0.00" readonly></td>';
				$codeHtml2 .= '<td><input type="number" class="form-control input-default partInv" name="partInv_'.$counter.'" placeholder="0.00" readonly></td>';
				$codeHtml2 .= '</tr>';
			}
			fclose($handle);
			echo 'done|'.$codeHtml.'|'.$codeHtml2.'|'.$counter;
			exit();
		}
	} else {
		echo "Import impossible: fichier CSV ou copropriete manquant."; 
		exit();
	}
}
