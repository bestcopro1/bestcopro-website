<?php
/*
$today = date("Y-m-d-His");
$fp = fopen('notification_'.$today.'.txt', 'w');
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
a:1:{s:5:"token";s:32:"0bec910695c6fbc232058914a7ab3406";}
 --------------------------------------------------------------------------------------------------------------- 
token=0bec910695c6fbc232058914a7ab3406
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
        $sql = "SELECT * From lot WHERE token = '{$token}' ";
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
?>
{
    "civilite": "<?=$proprietaire[0]["civilite"]?>",
    "nom": "<?=$proprietaire[0]["nom"]?>",
    "prenom": "<?=$proprietaire[0]["prenom"]?>",
    "situation": [
        {
            "date": "01/02/2023",
            "text": "Bienvenue dans l'application mobile BestCopro"
        }
    ]
}
<?php
		endif;
	endif;
endif;
?>