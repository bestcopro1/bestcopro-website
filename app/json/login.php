<?php
$today = date("Y-m-d-His");
$fp = fopen("login_" . $today . ".txt", "w");
fwrite($fp, serialize($_GET));
fwrite(
    $fp,
    "\n --------------------------------------------------------------------------------------------------------------- \n",
);
foreach ($_GET as $key => $value) {
    fwrite($fp, "$key=$value");
}
fwrite(
    $fp,
    "\n --------------------------------------------------------------------------------------------------------------- \n",
);
fwrite($fp, serialize($_POST));
fwrite(
    $fp,
    "\n --------------------------------------------------------------------------------------------------------------- \n",
);
foreach ($_POST as $key => $value) {
    fwrite($fp, "$key=$value");
}
fwrite(
    $fp,
    "\n --------------------------------------------------------------------------------------------------------------- \n",
);
function incoming_files()
{
    $files = $_FILES;
    $files2 = [];
    foreach ($files as $input => $infoArr) {
        $filesByInput = [];
        foreach ($infoArr as $key => $valueArr) {
            if (is_array($valueArr)) {
                // file input "multiple"
                foreach ($valueArr as $i => $value) {
                    $filesByInput[$i][$key] = $value;
                }
            } else {
                // -> string, normal file input
                $filesByInput[] = $infoArr;
                break;
            }
        }
        $files2 = array_merge($files2, $filesByInput);
    }
    $files3 = [];
    foreach ($files2 as $file) {
        // let's filter empty & errors
        if (!$file["error"]) {
            $files3[] = $file;
        }
    }
    return $files3;
}

$tmpFiles = incoming_files();
fwrite($fp, serialize($tmpFiles));
fclose($fp);
header("Content-Type: application/json; charset=utf-8");
if (!isset($_POST["username"], $_POST["password"])): ?>
{
    "statut": "OK",
    "token": "",
    "message": "Accès interdit"
}
<?php else:if ($_POST["username"] == "" || $_POST["password"] == ""): ?>
{
    "statut": "OK",
    "token": "",
    "message": "Identifiant et/ou mot de passe incorrect!"
}
<?php else:if (
            $_POST["username"] == "issamglobe@gmail.com" &&
            $_POST["password"] == "Iss@m1987"
        ):

            $user_login = 1;
            $token = md5(uniqid($user_login, true));
            ?>
{
    "statut": "OK",
    "token": "<?= $token ?>",
    "message": ""
}
<?php
        else:
             ?>
{
    "statut": "OK",
    "token": "",
    "message": "Identifiant et/ou mot de passe incorrect!"
}
<?php
        endif;endif;endif;
