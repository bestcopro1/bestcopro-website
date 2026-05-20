<?php
/*
$today = date("Y-m-d-His");
$fp = fopen('updatePasse_'.$today.'.txt', 'w');
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
a:4:{s:9:"anMoPasse";s:9:"Guest_123";s:10:"nouMoPasse";s:6:"123456";s:10:"conMoPasse";s:6:"123456";s:5:"token";s:32:"75c3d21b5c97e6b87412b0f876417646";}
 --------------------------------------------------------------------------------------------------------------- 
anMoPasse=Guest_123nouMoPasse=123456conMoPasse=123456token=75c3d21b5c97e6b87412b0f876417646
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
$token = "";
$OK = "NOTOK";
if (isset($_GET["token"], $_GET["anMoPasse"], $_GET["nouMoPasse"], $_GET["conMoPasse"])) {
	$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
	$anMoPasse = filter_input(INPUT_GET, 'anMoPasse', FILTER_SANITIZE_STRING);
	$nouMoPasse = filter_input(INPUT_GET, 'nouMoPasse', FILTER_SANITIZE_STRING);
	$conMoPasse = filter_input(INPUT_GET, 'conMoPasse', FILTER_SANITIZE_STRING);
	if ($nouMoPasse === $conMoPasse) {
		$sql = "SELECT * From lot WHERE token = '{$token}' AND password = '{$anMoPasse}' ";
		$query = mysqli_query($connection, $sql);
		$rowCount = mysqli_num_rows($query);
		if ($rowCount > 0) {
			while($row = mysqli_fetch_array($query)) {
				$id_lot = $row['id'];
			}
			$request = "UPDATE lot SET password=? WHERE id=?";
			if ($insert_stmt = $connection->prepare($request)) {
				$insert_stmt->bind_param('ss', $nouMoPasse, $id_lot);
				// Execute the prepared query.
				if (! $insert_stmt->execute()) {
					echo $connection->error;
					exit();
				}
			}
			$OK = "OK";
		}
	}
}
?>
{
    "statut": "<?=$OK?>"
}