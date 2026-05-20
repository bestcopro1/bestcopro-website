<?php
/*
$today = date("Y-m-d-His");
$fp = fopen('authentication_'.$today.'.txt', 'w');
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
a:2:{s:8:"username";s:15:"guest@guest.com";s:8:"password";s:9:"Guest_123";}
 --------------------------------------------------------------------------------------------------------------- 
username=guest@guest.compassword=Guest_123
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
if ( !isset($_GET['username'], $_GET['password']) ) :
?>
{
    "statut": "OK",
    "token": "",
    "message": "Accès interdit"
}
<?php
else :
	if ( $_GET['username'] == "" || $_GET['password'] == "" ) :
?>
{
    "statut": "OK",
    "token": "",
    "message": "Identifiant et/ou mot de passe incorrect!"
}
<?php
	else :
		$username_signin = $_GET['username'];
        $password_signin = $_GET['password'];
		$username = mysqli_real_escape_string($connection, $username_signin);
		$pswd = mysqli_real_escape_string($connection, $password_signin);
		$sql = "SELECT * From lot WHERE code LIKE '{$username}' AND password = '{$pswd}' AND id_copropriete IN (SELECT id FROM copropriete WHERE display = 1)";
        $query = mysqli_query($connection, $sql);
        $rowCount = mysqli_num_rows($query);
		if(!$query) :
?>
{
    "statut": "OK",
    "token": "",
    "message": "Accès interdit"
}
<?php
		endif;
		if($rowCount <= 0) :
?>
{
    "statut": "OK",
    "token": "",
    "message": "Identifiant et/ou mot de passe incorrect!"
}
<?php
        else :
			while($row = mysqli_fetch_array($query)) {
				$pass_word     = $row['password'];
				$token         = $row['token'];
			}
?>
{
    "statut": "OK",
    "token": "<?=$token?>",
    "message": ""
}
<?php
		endif;
	endif;
endif;