<?php
/*
$today = date("Y-m-d-His");
$fp = fopen('reclamation_'.$today.'.txt', 'w');
fwrite($fp, serialize($_GET));
fwrite($fp, "\n --------------------------------------------------------------------------------------------------------------- \n");
fwrite($fp, serialize($_POST));
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
a:0:{}
 --------------------------------------------------------------------------------------------------------------- 
a:4:{s:2:"id";s:0:"";s:5:"token";s:32:"1c3576e5be4de05b5f9fe600574ec038";s:6:"object";s:6:"test 1";s:7:"message";s:7:"test 11";}
 --------------------------------------------------------------------------------------------------------------- 
a:1:{i:0;a:6:{s:4:"name";s:4:"blob";s:9:"full_path";s:4:"blob";s:4:"type";s:10:"image/jpeg";s:8:"tmp_name";s:14:"/tmp/phpi3wBAe";s:5:"error";i:0;s:4:"size";i:180959;}}
*/

/*
a:2:{s:5:"token";s:32:"fa8774ab015ed5ac00fdc4e76d426837";s:3:"get";s:3:"all";}
 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
*/

/*
a:4:{s:5:"token";s:32:"c855177c470101a32851e6ef644afb3d";s:6:"update";s:3:"yes";s:2:"id";s:1:"2";s:7:"message";s:11:"hake molana";}
 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
 --------------------------------------------------------------------------------------------------------------- 
a:0:{}
*/

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
header("Content-Type: application/json; charset=utf-8");
$token = "";
if (isset($_POST["token"], $_POST["object"], $_POST["id"], $_POST["message"])) {
    $id_reclamation = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $token = filter_input(INPUT_POST, "token", FILTER_SANITIZE_STRING);
    $objet = filter_input(INPUT_POST, "object", FILTER_SANITIZE_STRING);
    $commentaire = filter_input(INPUT_POST, "message", FILTER_SANITIZE_STRING);
    if ($token != "" && $objet != "" && $commentaire != "") {
        $sql = "SELECT * From lot WHERE token = '{$token}' ";
        $query = mysqli_query($connection, $sql);
        $rowCount = mysqli_num_rows($query);
        while ($row = mysqli_fetch_array($query)) {
            $id_lot = $row["id"];
        }
        $lot = getLot($id_lot, null, null, $connection);

        $request = "INSERT INTO reclamation (date, objet, id_lot) 
					VALUES (?, ?, ?)";
        $date = date("Y-m-d H:i:s");
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("sss", $date, $objet, $lot[0]["id"]);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_reclamation = $connection->insert_id;

        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $action = "a ajouté|reclamation|" . $id_reclamation;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $lot[0]["id_proprietaire"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }

        $request = "INSERT INTO message (date, id_proprietaire, commentaire, id_reclamation) 
					VALUES (?, ?, ?, ?)";
        $date = date("Y-m-d H:i:s");
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssss",
                $date,
                $lot[0]["id_proprietaire"],
                $commentaire,
                $id_reclamation,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_message = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $action = "a ajouté|message|" . $id_message;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $lot[0]["id_proprietaire"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }

        $request = "INSERT INTO message (date, id_syndic, commentaire, id_reclamation) 
					VALUES (?, ?, ?, ?)";
        $date = date("Y-m-d H:i:s");
        $id_syndic = 1;
        $reponseAuto =
            "Bonjour, votre réclamation est bien enregistrée, elle sera traitée dans les plus brefs délais.";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssss",
                $date,
                $id_syndic,
                $reponseAuto,
                $id_reclamation,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_message = $connection->insert_id;

        $request = "INSERT INTO notificationsyndic (description, date, nomPage, idPage, id_copropriete) 
					VALUES (?, ?, ?, ?, ?)";
        $date = date("Y-m-d H:i:s");
        $description = "Une nouvelle réclamation a été enregistrée.";
        $nomPage = "reclamations";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "sssss",
                $description,
                $date,
                $nomPage,
                $id_reclamation,
                $lot[0]["id_copropriete"],
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }

        if (isset($_FILES["file"]["name"])) {
            if ($_FILES["file"]["name"] != "") {
                $fileType = pathinfo(
                    $_FILES["file"]["name"],
                    PATHINFO_EXTENSION,
                );
                $fileType = strtolower($fileType);
                if ($fileType == "") {
                    $fileType = "jpg";
                }
                $location =
                    __DIR__ .
                    "/../justificatifs/reclamations/" .
                    $id_reclamation .
                    "." .
                    $fileType;
                move_uploaded_file($_FILES["file"]["tmp_name"], $location);
            }
        }
    }
} elseif (
    isset($_GET["token"], $_GET["update"], $_GET["id"], $_GET["message"])
) {
    $token = filter_input(INPUT_GET, "token", FILTER_SANITIZE_STRING);
    $update = filter_input(INPUT_GET, "update", FILTER_SANITIZE_STRING);
    $id_reclamation = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
    $commentaire = filter_input(INPUT_GET, "message", FILTER_SANITIZE_STRING);
    if (
        $token != "" &&
        $update == "yes" &&
        $id_reclamation != "" &&
        $commentaire != ""
    ) {
        $sql = "SELECT * From lot WHERE token = '{$token}' ";
        $query = mysqli_query($connection, $sql);
        $rowCount = mysqli_num_rows($query);
        while ($row = mysqli_fetch_array($query)) {
            $id_lot = $row["id"];
        }
        $lot = getLot($id_lot, null, null, $connection);

        $request = "INSERT INTO message (date, id_proprietaire, commentaire, id_reclamation) 
					VALUES (?, ?, ?, ?)";
        $date = date("Y-m-d H:i:s");
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssss",
                $date,
                $lot[0]["id_proprietaire"],
                $commentaire,
                $id_reclamation,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $id_message = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $action = "a ajouté|message2|" . $id_message;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $lot[0]["id_proprietaire"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }
    }
} elseif (isset($_GET["token"], $_GET["get"])) {
    $get = filter_input(INPUT_GET, "get", FILTER_SANITIZE_STRING);
    if ($get == "all") {
        $token = filter_input(INPUT_GET, "token", FILTER_SANITIZE_STRING);
    }
}
if ($token == ""): ?>
{}
<?php else:$token = mysqli_real_escape_string($connection, $token);
    $sql = "SELECT * From lot WHERE token = '{$token}' ";
    $query = mysqli_query($connection, $sql);
    $rowCount = mysqli_num_rows($query);
    if (!$query): ?>
{}
<?php endif;
    if ($rowCount <= 0): ?>
{}
<?php else:
        while ($row = mysqli_fetch_array($query)) {
            $id_lot = $row["id"];
        }
        $lot = getLot($id_lot, null, null, $connection);
        $reclamations = getReclamation(null, $lot[0]["id"], null, $connection);
        ?>
[
<?php
$i = 0;
foreach ($reclamations as $reclamation):

    $statutReclamation = getStatutreclamation(
        $reclamation["id_statutReclamation"],
        $connection,
    );
    $preuves = glob(
        "../justificatifs/reclamations/" . $reclamation["id"] . ".*",
    );
    if (count($preuves) == 0) {
        $image =
            "https://bestcopro.ma/app/justificatifs/reclamations/no-image.jpg";
    } else {
        $image =
            "https://bestcopro.ma/app/" . str_replace("../", "", $preuves[0]);
    }
    $messages = getMessages($reclamation["id"], $connection);
    ?>
    {
        "id":"<?= $reclamation["id"] ?>",
		"Object": "<?= $reclamation["objet"] ?>",
		"image" :"<?= $image ?>",
		"dateD":"<?= date("d/m/Y", strtotime($reclamation["date"])) ?>",
		"statut" : "<?= $statutReclamation[0]["libelle"] ?>",
		"Message" : [
<?php
$j = 0;
foreach ($messages as $message):
    if ($message["id_syndic"] != null) {
        $source = "Syndic";
    } else {
        $proprietaire = getProprietaire(
            $message["id_proprietaire"],
            null,
            $connection,
        );
        $source =
            $proprietaire[0]["civilite"] .
            " " .
            $proprietaire[0]["prenom"] .
            " " .
            $proprietaire[0]["nom"];
    } ?>
		{
			"id":"<?= $message["id"] ?>",
			"source" :"<?= $source ?>",
			"date":"<?= date("d/m/Y", strtotime($message["date"])) ?>",
			"text" : "<?= str_replace(
       ["<br>", "<br />", "\n", "\r"],
       ["", "", " ", ""],
       $message["commentaire"],
   ) ?>"
		}
<?php if (++$j < count($messages)) {
    echo ",";
}
endforeach;
?>
		]
    }
<?php if (++$i < count($reclamations)) {
    echo ",";
}
endforeach;
?>
]
<?php endif;endif;
?>
