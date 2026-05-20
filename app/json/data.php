<?php
/*
$today = date("Y-m-d-His");
$fp = fopen('data_'.$today.'.txt', 'w');
fwrite($fp, serialize($_GET));
fwrite($fp, "\n --------------------------------------------------------------------------------------------------------------- \n");
foreach($_GET as $key=>$value){fwrite($fp, "$key=$value");}
fwrite($fp, "\n --------------------------------------------------------------------------------------------------------------- \n");
fwrite($fp, serialize($_POST));
fwrite($fp, "\n --------------------------------------------------------------------------------------------------------------- \n");
foreach($_POST as $key=>$value){fwrite($fp, "$key=$value");}
fwrite($fp, "\n --------------------------------------------------------------------------------------------------------------- \n");
function incoming_files() {
    $files = $_FILES;
    $files2 = [];
    foreach ($files as $input => $infoArr) {
        $filesByInput = [];
        foreach ($infoArr as $key => $valueArr) {
            if (is_array($valueArr)) { // file input "multiple"
                foreach($valueArr as $i=>$value) {
                    $filesByInput[$i][$key] = $value;
                }
            }
            else { // -> string, normal file input
                $filesByInput[] = $infoArr;
                break;
            }
        }
        $files2 = array_merge($files2,$filesByInput);
    }
    $files3 = [];
    foreach($files2 as $file) { // let's filter empty & errors
        if (!$file['error']) $files3[] = $file;
    }
    return $files3;
}

$tmpFiles = incoming_files();
fwrite($fp, serialize($tmpFiles));
fclose($fp);
*/

/*
a:1:{s:5:"token";s:32:"c5dfa537e70f2442815c5300f6344a2d";}
 --------------------------------------------------------------------------------------------------------------- 
token=c5dfa537e70f2442815c5300f6344a2d
 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
 --------------------------------------------------------------------------------------------------------------- 

 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
*/

include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
header('Content-Type: application/json; charset=utf-8');
if ( !isset($_GET['token']) ) :
?>
{}
<?php
else :
	if ( $_GET['token'] == "" ) :
?>
{}
<?php
	else :
		$token = $_GET['token'];
        $token = mysqli_real_escape_string($connection, $token);
        $sql = "SELECT * From lot WHERE token = '{$token}' AND id_copropriete IN (SELECT id FROM copropriete WHERE display = 1)";
        $query = mysqli_query($connection, $sql);
        $rowCount = mysqli_num_rows($query);
		if(!$query) :
?>
{}
<?php
		endif;
		if($rowCount <= 0) :
?>
{}
<?php
        else :
			while($row = mysqli_fetch_array($query)) {
				$id_lot = $row['id'];
			}
			$lot = getLot($id_lot, null, null, $connection);
			$proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
			$copropriete = getCopropriete($lot[0]["id_copropriete"], $connection);
			$exercice = getExercice(null, $copropriete[0]["id"], $connection);
			$documents = getDocument(null, $lot[0]["id_copropriete"], 1, $connection);
			$relLotExercice = getRel_lot_exercice($lot[0]["id"], null, $connection);
			$solde = 0; 
			$impayes = [];
			foreach ($relLotExercice as $periode)
				if (intval($periode["id_exercice"]) < 0 && floatval($periode["cotisation"]) < (floatval($periode["partFonct"]) + floatval($periode["partInv"]))) {
					$impayes[] = array("id_exercice" => $periode["id_exercice"], "dateFinPeriode" => $periode["dateFinPeriode"], "cotisation" => $periode["cotisation"], "partFonct" => $periode["partFonct"], "partInv" => $periode["partInv"]);
					$solde += floatval($periode["partFonct"]) + floatval($periode["partInv"]) - floatval($periode["cotisation"]);
				}
			$relLotExercice = getRel_lot_exercice($lot[0]["id"], $exercice[0]["id"], $connection);
			$paiements = getPaiement(null, null, $lot[0]["id"], $connection);
			$credit = 0;
			$debit = 0;
			foreach($paiements as $paiement)
				$credit += floatval($paiement["montant"]);
			
			$totalPayeChecker = 0;
			$totalImpayeChecker = 0;
			if ($exercice[0]["id_periodePaiement"] == "1") {
				$nbrMonth = 1;
			} elseif ($exercice[0]["id_periodePaiement"] == "2") {
				$nbrMonth = 3;
			} elseif ($exercice[0]["id_periodePaiement"] == "3") {
				$nbrMonth = 6;
			} elseif ($exercice[0]["id_periodePaiement"] == "4") {
				$nbrMonth = 12;
			}
			foreach ($relLotExercice as $periode) {
				$debit += floatval($periode["partFonct"]) + floatval($periode["partInv"]);
			}
			foreach ($relLotExercice as $periode) {
				if (strtotime(date('Y-m-d')) <= strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - ".$nbrMonth." month"))
					break;
				$totalImpayeChecker += floatval($periode["partFonct"]) + floatval($periode["partInv"]);
				$totalPayeChecker += floatval($periode["cotisation"]);
			}
			$solde += $totalImpayeChecker - $totalPayeChecker;
?>
{
    "civilite": "<?=$proprietaire[0]["civilite"]?>",
    "nom": "<?=$proprietaire[0]["nom"]?>",
    "prenom": "<?=$proprietaire[0]["prenom"]?>",
    "telephone": "<?=$proprietaire[0]["telephone"]?>",
    "email": "<?=$proprietaire[0]["email"]?>",
    "adresse": "<?=$proprietaire[0]["adresse"]?>",
    "code": "<?=$lot[0]["code"]?>",
    "Copropriete": "<?=$copropriete[0]["nom"]?>",
    "Numero": "<?=$lot[0]["numero"]?>",
    "Tantieme": "<?=floatval($lot[0]["tantieme"])?>",
    "Titrefonciere": "<?=$lot[0]["foncier"]?>",
    "Debit": "<?=number_format($debit, 2, ",", " ")?> MAD",
    "Credit": "<?=number_format($credit, 2, ",", " ")?> MAD",
    "CreVotCom": "<?=number_format($solde, 2, ",", " ")?> MAD",
    "Exercice":"<?=getNameexercice($exercice[0]["dateDebut"])?>",
    "RIB":"<?=$copropriete[0]["rib"]?>",
    "impayes": [
<?php
			$i = 0;
			foreach ($impayes as $impaye) : 
				if ($impaye["id_exercice"] == "0")
					continue; //$monthYear = "Impayé de l'année : ".date("Y",strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 year"));
				else
					$monthYear = "Impayé de l'année : ".date("Y",strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - ".abs($impaye["id_exercice"])." year"));
?>
		{
			"date": "<?=$monthYear?>",
			"cotisation": "<?=number_format(abs(floatval($impaye["cotisation"]) - floatval($impaye["partFonct"]) - floatval($impaye["partInv"])), 2, ",", " ")?> MAD",
			"statut": "nonpaye"
		}
<?php
				if (++$i < count($impayes)) echo ",";
			endforeach;
?>
	],
	"situation": [
<?php
			$trimestre = 1;
			$semestre = 1;
			$i = 0;
			foreach ($relLotExercice as $periode) : 
				if ($exercice[0]["id_periodePaiement"] == "1") :
					$monthYear = "Cotisations du mois : ".date("m/Y",strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 month"));
				elseif ($exercice[0]["id_periodePaiement"] == "2") :
					$monthYear = "Cotisations du trimestre : T".$trimestre++;
				elseif ($exercice[0]["id_periodePaiement"] == "3") :
					$monthYear = "Cotisations du semestre : S".$semestre++;
				elseif ($exercice[0]["id_periodePaiement"] == "4") :
					$monthYear = "Cotisations de l'année : ".date("Y",strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 year"));
				endif;
				if (floatval($periode["cotisation"]) < (floatval($periode["partFonct"]) + floatval($periode["partInv"])))
					$statut = "nonpaye";
				else
					$statut = "paye";
?>
		{
			"date": "<?=$monthYear?>",
			"cotisation": "<?=number_format(floatval($periode["cotisation"]), 2, ",", " ")?> MAD",
			"statut": "<?=$statut?>"
		}
<?php
				if (++$i < count($relLotExercice)) echo ",";
			endforeach;
?>
    ],
    "Paiements": [
<?php
			$i = 0;
			foreach($paiements as $paiement):
?>
        {
            "designation":"<?=$paiement["commentaire"]?>",
            "date": "<?=date("d/m/Y", strtotime($paiement["date"]))?>",
            "cotisation": "<?=number_format(floatval($paiement["montant"]), 2, ",", " ")?> MAD"
        }
<?php
				if (++$i < count($paiements)) echo ",";
			endforeach;
?>
    ],
    "documents": [
<?php
			$i = 0;
			foreach($documents as $document):
				$typedocument = getTypedocument($document["id_typedocument"],$connection);
				$preuves = glob ("../justificatifs/documents/".$document["id"].".*");
?>
        {
            "titre":"<?=$document["titre"]?>",
            "date": "<?=date("d/m/Y", strtotime($document["date"]))?>",
            "id": "<?=$document["id"]?>",
			"type": "<?=$typedocument[0]["libelle"]?>",
<?php
				if(count($preuves) > 0) :
?>
            "lien": "https://bestcopro.ma/app/<?=str_replace("../", "", $preuves[0])?>"
<?php
				else :
?>
            "lien": "#"
<?php
				endif;
?>
        }
<?php
				if (++$i < count($documents)) echo ",";
			endforeach;
?>
    ]
}
<?php
		endif;
	endif;
endif;
?>