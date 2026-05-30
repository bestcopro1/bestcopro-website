<?php
/*
$today = date("Y-m-d-His");
$fp = fopen('updateProfile_'.$today.'.txt', 'w');
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
a:7:{s:3:"Nom";s:18:"issam el ouakouak ";s:6:"Prenom";s:6:"ASMAE ";s:9:"FATHALLAH";s:0:"";s:9:"Telephone";s:12:"076776765678";s:5:"Email";s:19:"fathallah@gmail.com";s:7:"Adresse";s:70:"IMM 59 APPT 01K Marbella 3, Résidence Ryad Al Andalous GH K, Hay Ryad";s:5:"token";s:32:"75c3d21b5c97e6b87412b0f876417646";}
 --------------------------------------------------------------------------------------------------------------- 
Nom=issam el ouakouak Prenom=ASMAE FATHALLAH=Telephone=076776765678Email=fathallah@gmail.comAdresse=IMM 59 APPT 01K Marbella 3, Résidence Ryad Al Andalous GH K, Hay Ryadtoken=aadaff1423a62a1624d286d79692a76d
 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
 --------------------------------------------------------------------------------------------------------------- 

 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
*/

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
header("Content-Type: application/json; charset=utf-8");
$token = "";
if (
    isset(
        $_GET["token"],
        $_GET["Nom"],
        $_GET["Prenom"],
        $_GET["Telephone"],
        $_GET["Email"],
        $_GET["Adresse"],
    )
) {
    $token = filter_input(INPUT_GET, "token", FILTER_SANITIZE_STRING);
    $nom = filter_input(INPUT_GET, "Nom", FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_GET, "Prenom", FILTER_SANITIZE_STRING);
    $telephone = filter_input(INPUT_GET, "Telephone", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_GET, "Email", FILTER_SANITIZE_STRING);
    $adresse = filter_input(INPUT_GET, "Adresse", FILTER_SANITIZE_STRING);

    $sql = "SELECT * From lot WHERE token = '{$token}' ";
    $query = mysqli_query($connection, $sql);
    $rowCount = mysqli_num_rows($query);
    while ($row = mysqli_fetch_array($query)) {
        $id_lot = $row["id"];
    }
    $lot = getLot($id_lot, null, null, $connection);
    $request =
        "UPDATE proprietaire SET email=?, telephone=?, adresse=? WHERE id=?";
    if ($insert_stmt = $connection->prepare($request)) {
        $insert_stmt->bind_param(
            "ssss",
            $email,
            $telephone,
            $adresse,
            $lot[0]["id_proprietaire"],
        );
        // Execute the prepared query.
        if (!$insert_stmt->execute()) {
            echo $connection->error;
            exit();
        }
    }
}
?>
{
    "statut": "OK"
}