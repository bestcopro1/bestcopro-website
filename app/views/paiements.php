<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
// get Impaye
function getImpaye($id_lot, $id_paiement = null, $connection)
{
    if ($id_paiement != null) {
        $request =
            "SELECT id_rel, id_lot, id_exercice, partFonct, partInv, dateFinPeriode, cotisation FROM rel_lot_exercice WHERE id_lot = ? AND id_rel IN (SELECT id_rel FROM rel_rel_paiement WHERE id_paiement = ?) ORDER BY dateFinPeriode ASC";
    } elseif ($id_lot != null) {
        $request =
            "SELECT id_rel, id_lot, id_exercice, partFonct, partInv, dateFinPeriode, cotisation FROM rel_lot_exercice WHERE (partFonct + partInv) > cotisation AND id_lot = ? ORDER BY dateFinPeriode ASC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id_paiement != null) {
            $stmt->bind_param("ss", $id_lot, $id_paiement);
        } elseif ($id_lot != null) {
            $stmt->bind_param("s", $id_lot);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id_rel,
                $id_lot,
                $id_exercice,
                $partFonct,
                $partInv,
                $dateFinPeriode,
                $cotisation,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id_rel" => $id_rel,
                    "id_lot" => $id_lot,
                    "id_exercice" => $id_exercice,
                    "partFonct" => $partFonct,
                    "partInv" => $partInv,
                    "dateFinPeriode" => $dateFinPeriode,
                    "cotisation" => $cotisation,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}

function getPaymentImpayeLabel($impaye, $connection)
{
    if (intval($impaye["id_exercice"]) < 0) {
        $exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
        if (count($exercice) > 0) {
            return "Cumul des impayés " .
                getExercisePeriodLabel(
                    $exercice[0]["dateDebut"],
                    intval($impaye["id_exercice"])
                );
        }

        return "Cumul des impayés N" . $impaye["id_exercice"];
    }

    if (intval($impaye["id_exercice"]) == 0) {
        return "Impayé promoteur";
    }

    if (intval($impaye["id_exercice"]) > 0) {
        if (isset($GLOBALS["id_exercice"]) && intval($impaye["id_exercice"]) === intval($GLOBALS["id_exercice"])) {
            return "";
        }

        $exercice = getExercice($impaye["id_exercice"], null, $connection);
        if (count($exercice) > 0) {
            return "Cumul des impayés " . getNameexercice($exercice[0]["dateDebut"]);
        }
    }

    return "";
}
if (isset($_POST["id"], $_POST["printZone"])) {
    $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $printZone = filter_input(INPUT_POST, "printZone", FILTER_SANITIZE_STRING);
    if ($id != "" && $printZone == "true") {
        $codeHtml = "";
        $paiement = getPaiement($id, null, null, $connection);
        $lot = getLot($paiement[0]["id_lot"], null, null, $connection);
        $proprietaire = getProprietaire(
            $lot[0]["id_proprietaire"],
            null,
            $connection,
        );
        $copropriete = getCopropriete($lot[0]["id_copropriete"], $connection);
        if (count($paiement) > 0) {
            $codeHtml .= '<div class="col-lg-12">';
            $codeHtml .= '<div class="card mt-0 mb-0">';
            $codeHtml .= '<div class="card-header">';
            $codeHtml .=
                "<strong>Reçu de paiement N° : " .
                date("y") .
                sprintf("%'.05d", $paiement[0]["id"]) .
                "</strong>";
            $codeHtml .=
                '<span class="float-end">' .
                $copropriete[0]["ville"] .
                " le " .
                date("d/m/Y") .
                "</span>";
            $codeHtml .= "</div>";
            $codeHtml .= '<div class="card-body">';
            $codeHtml .= '<div class="row mb-0">';
            $codeHtml .=
                '<div class="mt-2 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6">';
            $codeHtml .= '<div class="row align-items-center">';
            $codeHtml .= '<div class="col-sm-12">';
            $codeHtml .= '<div class="brand-logo mb-3">';
            $codeHtml .=
                '<img class="brand-title d-inline" width="160" src="best_copro_logo.svg" alt="Best Copro">';
            $codeHtml .= "</div>";
            $codeHtml .=
                "<!--<span>+212 66 03 010 51 / +212 66 36 376 20</span><br>";
            $codeHtml .= "<span>contact@bestcopro.ma</span><br>";
            $codeHtml .= "<span>https://www.bestcopro.ma/</span>-->";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .=
                '<div class="mt-2 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6">';
            $codeHtml .=
                "<div><strong>" .
                $proprietaire[0]["civilite"] .
                " " .
                $proprietaire[0]["prenom"] .
                " " .
                $proprietaire[0]["nom"] .
                "</strong></div>";
            $codeHtml .=
                "<div>Copropriété : " . $copropriete[0]["nom"] . "</div>";
            $codeHtml .=
                "<div>" .
                getTypelot($lot[0]["id_typeLot"], $connection)[0]["libelle"] .
                " N° : " .
                $lot[0]["numero"] .
                "</div>";
            $codeHtml .=
                "<div>Immeuble N° : " . $lot[0]["numeroImm"] . "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "<hr>";
            $codeHtml .= '<div class="row">';
            $codeHtml .= '<div class="col-xl-12 col-md-12">';
            $modepaiement = getModepaiement(
                $paiement[0]["id_modePaiement"],
                $connection,
            );
            $codeHtml .=
                '<p class="font-w400 mb-2 d-flex"><span class="custom-label-2">Montant total : </span><span class="font-w600">' .
                number_format(floatval($paiement[0]["montant"]), 2, ",", " ") .
                " MAD</span></p>";
            $codeHtml .=
                '<p class="font-w400 mb-2 d-flex"><span class="custom-label-2">Mode de paiement : </span><span class="font-w600">' .
                $modepaiement[0]["libelle"] .
                "</span></p>";
            $codeHtml .=
                '<p class="font-w400 mb-2 d-flex"><span class="custom-label-2">Date de paiement : </span><span class="font-w600">' .
                date("d/m/Y", strtotime($paiement[0]["date"])) .
                "</span></p>";
            $codeHtml .=
                '<p class="font-w400 mb-2 d-flex"><span class="custom-label-2">Périodes associées : </span>';
            $codeHtml .=
                '<div class="table-responsive" style="margin-top: -20px;">';
            $codeHtml .=
                '<table class="table table-bordered table-responsive-sm">';
            $codeHtml .= "<tbody>";
            $codeHtml .= "<tr>";
            $relRelPaiements = getRel_rel_paiement(
                $paiement[0]["id"],
                $connection,
            );
            if (count($relRelPaiements) <= 6) {
                $TotalRelPaiement = 0;
                foreach ($relRelPaiements as $relRelPaiement) {
                    $periodeInfo = periodeInfo(
                        $relRelPaiement["id_rel"],
                        $connection,
                    );
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;"><strong>' .
                        $periodeInfo[0]["nomPeriode"] .
                        "</strong></td></p>";
                    $TotalRelPaiement += floatval($relRelPaiement["montant"]);
                }
                if (floatval($paiement[0]["montant"]) > $TotalRelPaiement) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;"><strong>Avance</strong></td>';
                }
                $codeHtml .= "</tr>";
                $codeHtml .= "<tr>";
                foreach ($relRelPaiements as $relRelPaiement) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;">' .
                        number_format(
                            floatval($relRelPaiement["montant"]),
                            2,
                            ",",
                            " ",
                        ) .
                        "</td>";
                }
                if (floatval($paiement[0]["montant"]) > $TotalRelPaiement) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;">' .
                        number_format(
                            floatval($paiement[0]["montant"]) -
                                $TotalRelPaiement,
                            2,
                            ",",
                            " ",
                        ) .
                        "</td>";
                }
            } else {
                $TotalRelPaiement = 0;
                foreach ($relRelPaiements as $relRelPaiement) {
                    $TotalRelPaiement += floatval($relRelPaiement["montant"]);
                }
                for ($i = 0; $i < 6; $i++) {
                    $periodeInfo = periodeInfo(
                        $relRelPaiements[$i]["id_rel"],
                        $connection,
                    );
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;"><strong>' .
                        $periodeInfo[0]["nomPeriode"] .
                        "</strong></td></p>";
                }
                $codeHtml .= "</tr>";
                $codeHtml .= "<tr>";
                for ($i = 0; $i < 6; $i++) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;">' .
                        number_format(
                            floatval($relRelPaiements[$i]["montant"]),
                            2,
                            ",",
                            " ",
                        ) .
                        "</td>";
                }
                $codeHtml .= "</tr>";
                $codeHtml .= "</tbody>";
                $codeHtml .= "</table>";
                $codeHtml .= "</div>";
                $codeHtml .=
                    '<div class="table-responsive" style="margin-top: -20px;">';
                $codeHtml .=
                    '<table class="table table-bordered table-responsive-sm">';
                $codeHtml .= "<tbody>";
                $codeHtml .= "<tr>";
                for ($i = 6; $i < count($relRelPaiements); $i++) {
                    $periodeInfo = periodeInfo(
                        $relRelPaiements[$i]["id_rel"],
                        $connection,
                    );
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;"><strong>' .
                        $periodeInfo[0]["nomPeriode"] .
                        "</strong></td></p>";
                }
                if (floatval($paiement[0]["montant"]) > $TotalRelPaiement) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;"><strong>Avance</strong></td>';
                }
                $codeHtml .= "</tr>";
                $codeHtml .= "<tr>";
                for ($i = 6; $i < count($relRelPaiements); $i++) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;">' .
                        number_format(
                            floatval($relRelPaiements[$i]["montant"]),
                            2,
                            ",",
                            " ",
                        ) .
                        "</td>";
                }
                if (floatval($paiement[0]["montant"]) > $TotalRelPaiement) {
                    $codeHtml .=
                        '<td class="text-center font-w400 p-2" style="font-size: 10px;">' .
                        number_format(
                            floatval($paiement[0]["montant"]) -
                                $TotalRelPaiement,
                            2,
                            ",",
                            " ",
                        ) .
                        "</td>";
                }
            }
            $codeHtml .= "</tr>";
            $codeHtml .= "</tbody>";
            $codeHtml .= "</table>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= '<div class="row">';
            $codeHtml .= '<div class="col-lg-4 col-sm-5 col-6"> </div>';
            $codeHtml .=
                '<div class="col-lg-4 col-sm-5 col-6 ms-auto pt-2 pb-5">';
            $codeHtml .= "Signature";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
            $codeHtml .= "</div>";
        }
        echo "done|" . $codeHtml;
        exit();
    } else {
        $error_msg .= "Une erreur est survenue";
        exit();
    }
} elseif (isset($_POST["id"], $_POST["delete"])) {
    $error_msg = "";

    $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
    $delete = filter_input(INPUT_POST, "delete", FILTER_SANITIZE_STRING);

    if ($id != "" && $delete == "true") {
        $relRelPaiements = getRel_rel_paiement($id, $connection);
        foreach ($relRelPaiements as $relRelPaiement) {
            $request =
                "UPDATE rel_lot_exercice SET cotisation = (cotisation - ? ) WHERE id_rel = ?";
            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param(
                    "ss",
                    $relRelPaiement["montant"],
                    $relRelPaiement["id_rel"],
                );
                if (!$insert_stmt->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
        }
        $request = "DELETE FROM rel_rel_paiement WHERE id_paiement = ?";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $id);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $request = "DELETE FROM paiement WHERE id=?";
        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param("s", $id);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a supprimé|paiement|" . $id;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $_SESSION["id"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }
        echo "done|" . $id;
        exit();
    } else {
        $error_msg .= "Une erreur est survenue";
        exit();
    }
} elseif (isset($_POST["lot"], $_POST["paiement"], $_POST["action"])) {
    $id_lot = filter_input(INPUT_POST, "lot", FILTER_SANITIZE_STRING);
    $id_paiement = filter_input(INPUT_POST, "paiement", FILTER_SANITIZE_STRING);
    $action = filter_input(INPUT_POST, "action", FILTER_SANITIZE_STRING);
    if ($id_lot != "" || $id_lot != null) {
        if ($action == "add") {
            $impayes = getImpaye($id_lot, null, $connection);
            $checked = "";
        } elseif ($action == "update") {
            $impayes = getImpaye($id_lot, $id_paiement, $connection);
            $checked = "checked";
            /*if ($impayes[0]["id_lot"] != $id_lot) {
				$impayes = getImpaye($id_lot, null, $connection);
				$checked = "";
			}*/
        }
        $codeHtml = "";
        foreach ($impayes as $impaye) {
            if (intval($impaye["id_exercice"]) < 0) {
                $codeHtml .= '<div class="form-check form-check-inline">';
                $codeHtml .= '<label class="form-check-label">';
                $codeHtml .=
                    '<input type="checkbox" class="form-check-input" name="impayes[]" value="' .
                    $impaye["id_rel"] .
                    '" ' .
                    $checked .
                    ">" .
                    getPaymentImpayeLabel($impaye, $connection);
                $codeHtml .= "</label>";
                $codeHtml .= "</div>";
            } elseif (intval($impaye["id_exercice"]) == 0) {
                $codeHtml .= '<div class="form-check form-check-inline">';
                $codeHtml .= '<label class="form-check-label">';
                $codeHtml .=
                    '<input type="checkbox" class="form-check-input" name="impayes[]" value="' .
                    $impaye["id_rel"] .
                    '" ' .
                    $checked .
                    ">Impayé promoteur";
                $codeHtml .= "</label>";
                $codeHtml .= "</div>";
            }
        }
        echo "done|" . $codeHtml;
        exit();
    }
    exit();
} elseif (
    isset(
        $_POST["id_lot"],
        $_POST["date"],
        $_POST["montant"],
        $_POST["id_modePaiement"],
    )
) {
    $error_msg = "";

    $id_lot = filter_input(INPUT_POST, "id_lot", FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, "date", FILTER_SANITIZE_STRING);
    $montant = filter_input(INPUT_POST, "montant", FILTER_SANITIZE_STRING);
    $id_modePaiement = filter_input(
        INPUT_POST,
        "id_modePaiement",
        FILTER_SANITIZE_STRING,
    );
    $commentaire = filter_input(
        INPUT_POST,
        "commentaire",
        FILTER_SANITIZE_STRING,
    );
    if (isset($_SESSION["id"])) {
        $id_syndic = $_SESSION["id"];
    } else {
        $id_syndic = 1;
    }
    $impayesAtraiter = [];

    if ($id_lot == "") {
        $error_msg .= "Veuillez sélectionner un lot";
        echo $error_msg;
        exit();
    }
    if ($date == "") {
        $error_msg .= "Veuillez entrer la date";
        echo $error_msg;
        exit();
    }
    if ($montant == "") {
        $error_msg .= "Veuillez entrer le montant";
        echo $error_msg;
        exit();
    }
    if (!is_numeric($montant) && floatval($montant) > 0) {
        $error_msg .= "Veuillez entrer un montant valide";
        echo $error_msg;
        exit();
    }
    if ($id_modePaiement == "") {
        $error_msg .= "Veuillez sélectionner un mode de paiment";
        echo $error_msg;
        exit();
    }
    if (!empty($_POST["impayes"])) {
        foreach ($_POST["impayes"] as $value) {
            $impayesAtraiter[] = $value;
        }
    }
    if (empty($error_msg) && isset($_POST["id"], $_POST["update"])) {
        $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_STRING);
        $update = filter_input(INPUT_POST, "update", FILTER_SANITIZE_STRING);
        if ($id != "" && $update == "true") {
            $relRelPaiements = getRel_rel_paiement($id, $connection);
            foreach ($relRelPaiements as $relRelPaiement) {
                $request =
                    "UPDATE rel_lot_exercice SET cotisation = (cotisation - ? ) WHERE id_rel = ?";
                if ($insert_stmt = $connection->prepare($request)) {
                    $insert_stmt->bind_param(
                        "ss",
                        $relRelPaiement["montant"],
                        $relRelPaiement["id_rel"],
                    );
                    if (!$insert_stmt->execute()) {
                        echo $connection->error;
                        exit();
                    }
                }
            }
            $request = "DELETE FROM rel_rel_paiement WHERE id_paiement = ?";
            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param("s", $id);
                // Execute the prepared query.
                if (!$insert_stmt->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
            $request =
                "UPDATE paiement SET id_lot=?, date=?, montant=?, id_modePaiement=?, commentaire=? WHERE id=?";
            if ($insert_stmt = $connection->prepare($request)) {
                $insert_stmt->bind_param(
                    "ssssss",
                    $id_lot,
                    $date,
                    $montant,
                    $id_modePaiement,
                    $commentaire,
                    $id,
                );
                // Execute the prepared query.
                if (!$insert_stmt->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
            if (
                $insert_stmt_history = $connection->prepare(
                    "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
                )
            ) {
                $date = date("Y-m-d H:i:s");
                $action = "a modifié|paiement|" . $id;
                $insert_stmt_history->bind_param(
                    "sss",
                    $date,
                    $action,
                    $_SESSION["id"],
                );
                // Execute the prepared query.
                if (!$insert_stmt_history->execute()) {
                    echo $connection->error;
                    exit();
                }
            }
            $linkFile = "";
            if (isset($_FILES["file"]["name"])) {
                if ($_FILES["file"]["name"] != "") {
                    $fileType = pathinfo(
                        $_FILES["file"]["name"],
                        PATHINFO_EXTENSION,
                    );
                    $fileType = strtolower($fileType);
                    $location =
                        __DIR__ .
                        "/../justificatifs/paiements/" .
                        $id .
                        "." .
                        $fileType;
                    $oldFiles = glob(
                        "../justificatifs/paiements/" . $id . ".*",
                    );
                    foreach ($oldFiles as $oldFile) {
                        if (is_file($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    move_uploaded_file($_FILES["file"]["tmp_name"], $location);
                    $linkFile =
                        getURL() .
                        "/../justificatifs/paiements/" .
                        $id .
                        "." .
                        $fileType;
                }
            }
            $impayes = getImpaye($id_lot, null, $connection);
            foreach ($impayes as $impaye) {
                if ($montant == 0) {
                    break;
                }
                $tmpCotisation =
                    floatval($impaye["partFonct"]) +
                    floatval($impaye["partInv"]) -
                    floatval($impaye["cotisation"]);
                $tmpCotisation =
                    $tmpCotisation < $montant ? $tmpCotisation : $montant;
                if (intval($impaye["id_exercice"]) <= 0) {
                    if (in_array($impaye["id_rel"], $impayesAtraiter)) {
                        $cotisation =
                            $tmpCotisation + floatval($impaye["cotisation"]);
                        $request =
                            "UPDATE rel_lot_exercice SET cotisation=? WHERE id_rel=?";
                        if ($insert_stmt = $connection->prepare($request)) {
                            $insert_stmt->bind_param(
                                "ss",
                                $cotisation,
                                $impaye["id_rel"],
                            );
                            if (!$insert_stmt->execute()) {
                                echo $connection->error;
                                exit();
                            } else {
                                $request =
                                    "INSERT INTO rel_rel_paiement (id_rel, id_paiement, montant) VALUES (?, ?, ?)";
                                if (
                                    $insert_stmt = $connection->prepare(
                                        $request,
                                    )
                                ) {
                                    $insert_stmt->bind_param(
                                        "sss",
                                        $impaye["id_rel"],
                                        $id,
                                        $tmpCotisation,
                                    );
                                    // Execute the prepared query.
                                    if (!$insert_stmt->execute()) {
                                        echo $connection->error;
                                        exit();
                                    }
                                }
                                $montant -= $tmpCotisation;
                            }
                        }
                    }
                } else {
                    $cotisation =
                        $tmpCotisation + floatval($impaye["cotisation"]);
                    $request =
                        "UPDATE rel_lot_exercice SET cotisation=? WHERE id_rel=?";
                    if ($insert_stmt = $connection->prepare($request)) {
                        $insert_stmt->bind_param(
                            "ss",
                            $cotisation,
                            $impaye["id_rel"],
                        );
                        if (!$insert_stmt->execute()) {
                            echo $connection->error;
                            exit();
                        } else {
                            $request =
                                "INSERT INTO rel_rel_paiement (id_rel, id_paiement, montant) VALUES (?, ?, ?)";
                            if ($insert_stmt = $connection->prepare($request)) {
                                $insert_stmt->bind_param(
                                    "sss",
                                    $impaye["id_rel"],
                                    $id,
                                    $tmpCotisation,
                                );
                                // Execute the prepared query.
                                if (!$insert_stmt->execute()) {
                                    echo $connection->error;
                                    exit();
                                }
                            }
                            $montant -= $tmpCotisation;
                        }
                    }
                }
            }
            echo "done|" . $id . "|" . $linkFile;
            exit();
        } else {
            $error_msg .= "Une erreur est survenue";
            exit();
        }
    } elseif (empty($error_msg)) {
        $request = "INSERT INTO paiement (id_lot, date, montant, id_modePaiement, commentaire, id_syndic) 
		VALUES (?, ?, ?, ?, ?, ?)";

        $insert_id = "";

        if ($insert_stmt = $connection->prepare($request)) {
            $insert_stmt->bind_param(
                "ssssss",
                $id_lot,
                $date,
                $montant,
                $id_modePaiement,
                $commentaire,
                $id_syndic,
            );
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $insert_id = $connection->insert_id;
        if (
            $insert_stmt_history = $connection->prepare(
                "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)",
            )
        ) {
            $date = date("Y-m-d H:i:s");
            $action = "a ajouté|paiement|" . $insert_id;
            $insert_stmt_history->bind_param(
                "sss",
                $date,
                $action,
                $_SESSION["id"],
            );
            // Execute the prepared query.
            if (!$insert_stmt_history->execute()) {
                echo $connection->error;
                exit();
            }
        }
        $linkFile = "";
        if (isset($_FILES["file"]["name"])) {
            if ($_FILES["file"]["name"] != "") {
                $fileType = pathinfo(
                    $_FILES["file"]["name"],
                    PATHINFO_EXTENSION,
                );
                $fileType = strtolower($fileType);
                $location =
                    __DIR__ .
                    "/../justificatifs/paiements/" .
                    $insert_id .
                    "." .
                    $fileType;
                move_uploaded_file($_FILES["file"]["tmp_name"], $location);
                $linkFile =
                    getURL() .
                    "/../justificatifs/paiements/" .
                    $insert_id .
                    "." .
                    $fileType;
            }
        }
        $impayes = getImpaye($id_lot, null, $connection);
        foreach ($impayes as $impaye) {
            if ($montant == 0) {
                break;
            }
            $tmpCotisation =
                floatval($impaye["partFonct"]) +
                floatval($impaye["partInv"]) -
                floatval($impaye["cotisation"]);
            $tmpCotisation =
                $tmpCotisation < $montant ? $tmpCotisation : $montant;
            if (intval($impaye["id_exercice"]) <= 0) {
                if (in_array($impaye["id_rel"], $impayesAtraiter)) {
                    $cotisation =
                        $tmpCotisation + floatval($impaye["cotisation"]);
                    $request =
                        "UPDATE rel_lot_exercice SET cotisation=? WHERE id_rel=?";
                    if ($insert_stmt = $connection->prepare($request)) {
                        $insert_stmt->bind_param(
                            "ss",
                            $cotisation,
                            $impaye["id_rel"],
                        );
                        if (!$insert_stmt->execute()) {
                            echo $connection->error;
                            exit();
                        } else {
                            $request =
                                "INSERT INTO rel_rel_paiement (id_rel, id_paiement, montant) VALUES (?, ?, ?)";
                            if ($insert_stmt = $connection->prepare($request)) {
                                $insert_stmt->bind_param(
                                    "sss",
                                    $impaye["id_rel"],
                                    $insert_id,
                                    $tmpCotisation,
                                );
                                // Execute the prepared query.
                                if (!$insert_stmt->execute()) {
                                    echo $connection->error;
                                    exit();
                                }
                            }
                            $montant -= $tmpCotisation;
                        }
                    }
                }
            } else {
                $cotisation = $tmpCotisation + floatval($impaye["cotisation"]);
                $request =
                    "UPDATE rel_lot_exercice SET cotisation=? WHERE id_rel=?";
                if ($insert_stmt = $connection->prepare($request)) {
                    $insert_stmt->bind_param(
                        "ss",
                        $cotisation,
                        $impaye["id_rel"],
                    );
                    if (!$insert_stmt->execute()) {
                        echo $connection->error;
                        exit();
                    } else {
                        $request =
                            "INSERT INTO rel_rel_paiement (id_rel, id_paiement, montant) VALUES (?, ?, ?)";
                        if ($insert_stmt = $connection->prepare($request)) {
                            $insert_stmt->bind_param(
                                "sss",
                                $impaye["id_rel"],
                                $insert_id,
                                $tmpCotisation,
                            );
                            // Execute the prepared query.
                            if (!$insert_stmt->execute()) {
                                echo $connection->error;
                                exit();
                            }
                        }
                        $montant -= $tmpCotisation;
                    }
                }
            }
        }

        echo "done|" . $insert_id . "|" . $linkFile;
        exit();
    } else {
        echo $error_msg;
        exit();
    }
}
if (isset($_GET["action"], $_GET["id"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
    $paiement = getPaiement($id, null, null, $connection);
    if (
        count($paiement) == 0 ||
        ($_SESSION["id_usertype"] !== "1" &&
            $_SESSION["id_usertype"] !== "2" &&
            $_SESSION["id_usertype"] !== "3")
    ) {
        goto iomEnd;
    }
    $date = date("d/m/Y", strtotime($paiement[0]["date"]));
    if ($action == "update" && $id != ""): ?>
		<style>
		<!--
		@media print {
			@page {
				size: 8.5in 11in; 
			}
			.card {
				box-shadow: none;
			}
		}
		-->
		</style>
		<div class="content-body d-print-none">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Modifier les données d'un paiement</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=paiements" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="paiements">Enregistrer</button>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="windowPrint" data-id="<?= $paiement[0][
         "id"
     ] ?>">Télécharger le reçu</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                            <div class="row">
											<div class="col-6 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id" value="<?= $paiement[0]["id"] ?>">
													<input type="hidden" name="update" value="true">
                                                    <label class="text-label">Lot*</label>
                                                    <select name="id_lot" id="id_lot" data-paiement="<?= $paiement[0][
                                                        "id"
                                                    ] ?>" data-action="update" class="single-select2 form-control wide mb-3" disabled>
                                                        <?php
                                                        $lots = getLot(
                                                            null,
                                                            null,
                                                            $GLOBALS[
                                                                "id_copropriete"
                                                            ],
                                                            $connection,
                                                        );
                                                        foreach ($lots as $lot):
                                                            $proprietaire = getProprietaire(
                                                                $lot[
                                                                    "id_proprietaire"
                                                                ],
                                                                null,
                                                                $connection,
                                                            ); ?>
                                                        <option value="<?= $lot[
                                                            "id"
                                                        ] ?>" <?php if (
    $lot["id"] == $paiement[0]["id_lot"]
) {
    echo "selected";
} ?>><?= $lot["code"] ?> (<?= $proprietaire[0][
     "prenom"
 ] ?> <?= $proprietaire[0]["nom"] ?>)</option>
                                                        <?php
                                                        endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date*</label>
                                                    <input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="jj/mm/aaaa" value="<?= $paiement[0][
                                                        "date"
                                                    ] ?>">
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Montant*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="montant" placeholder="Montant" value="<?= $paiement[0][
                 "montant"
             ] ?>">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Justificatif </label>
													<input type="file" class="form-control input-rounded form-file-input" name="justificatif" id="justificatif" placeholder="Justificatif" accept="image/jpeg,image/png,application/pdf">
												</div>
											</div>
                                            <div class="col-6 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Mode de paiment*</label>
                                                    <select name="id_modePaiement" class="default-select form-control input-rounded wide mb-3">
														<?php
              $modepaiements = getModepaiement(null, $connection);
              foreach ($modepaiements as $modepaiement): ?>
                                                        <option value="<?= $modepaiement[
                                                            "id"
                                                        ] ?>" <?php if (
    $modepaiement["id"] == $paiement[0]["id_modePaiement"]
) {
    echo "selected";
} ?>><?= $modepaiement["libelle"] ?></option>
                                                        <?php endforeach;
              ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Commentaire</label>
                                                    <textarea class="form-control input-rounded input-default mb-3" name="commentaire" placeholder="Commentaire"><?= $paiement[0][
                                                        "commentaire"
                                                    ] ?></textarea>
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Les impayés</label><br>
													<span id="lesImpayes">
														<?php
              $impayes = getImpaye(
                  $paiement[0]["id_lot"],
                  $paiement[0]["id"],
                  $connection,
              );
              foreach ($impayes as $impaye):
                  if (intval($impaye["id_exercice"]) < 0): ?>
														<div class="form-check form-check-inline">
															<label class="form-check-label">
																<input type="checkbox" class="form-check-input" name="impayes[]" value="<?= $impaye[
                    "id_rel"
                ] ?>" checked><?= getPaymentImpayeLabel($impaye, $connection) ?>
															</label>
														</div>
														<?php elseif (intval($impaye["id_exercice"]) == 0): ?>
														<div class="form-check form-check-inline">
															<label class="form-check-label">
																<input type="checkbox" class="form-check-input" name="impayes[]" value="<?= $impaye[
                    "id_rel"
                ] ?>" checked>Impayé promoteur
															</label>
														</div>
														<?php endif;
              endforeach;
              ?>
													</span>
												</div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<?php $preuves = glob(
        "./justificatifs/paiements/" . $paiement[0]["id"] . ".*",
    ); ?>
				<div class="row" id="blockOFjustificatif" <?php if (count($preuves) == 0) {
        echo 'style="display: none;"';
    } ?>>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Justificatif</h4>
                            </div>
							<div class="card-body">
								<div class="row">
									<div class="col-12">
										<?php if (count($preuves) > 0) {
              echo '<iframe src="' .
                  $preuves[0] .
                  "?" .
                  uniqid() .
                  '" width="100%" height="500px"></iframe>';
          } else {
              echo '<iframe src="#" width="100%" height="500px"></iframe>';
          } ?>
									</div>
								</div>
							</div>
						</div>
                    </div>
                </div>
			</div>
        </div>
		<div class="row d-none d-print-block" id="printZone"></div>
			
<?php else:goto iomEnd;endif;
elseif (isset($_GET["action"])):
    $action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING);
    if ($action == "add"): ?>
		<style>
		<!--
		@media print {
			@page {
				size: 8.5in 11in; 
			}
			.card {
				box-shadow: none;
			}
		}
		-->
		</style>
		<div class="content-body d-print-none">
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Ajouter un paiement</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="./dashboard.php?page=paiements" type="button" class="btn btn-rounded btn-outline-secondary px-3 my-1 me-2" id="back">Annuler</a>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" id="saveORedit" data-url="paiements">Enregistrer</button>
					<button type="button" class="btn btn-rounded btn-primary px-3 my-1 me-2" style="display: none;" id="windowPrint" data-id="">Télécharger le reçu</button>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
								<div class="basic-form">
                                    <form>
                                        <div class="row">
											<div class="col-6 mb-2">
                                                <div class="form-group">
													<input type="hidden" name="id_exercice" value="<?= $GLOBALS["id_exercice"] ?>">
                                                    <label class="text-label">Lot*</label>
                                                    <select name="id_lot" id="id_lot" data-paiement="" data-action="add" class="single-select2 form-control wide mb-3">
                                                        <?php
                                                        $lots = getLot(
                                                            null,
                                                            null,
                                                            $GLOBALS[
                                                                "id_copropriete"
                                                            ],
                                                            $connection,
                                                        );
                                                        foreach ($lots as $lot):
                                                            $proprietaire = getProprietaire(
                                                                $lot[
                                                                    "id_proprietaire"
                                                                ],
                                                                null,
                                                                $connection,
                                                            ); ?>
                                                        <option value="<?= $lot[
                                                            "id"
                                                        ] ?>"><?= $lot[
    "code"
] ?> (<?= $proprietaire[0]["prenom"] ?> <?= $proprietaire[0]["nom"] ?>)</option>
                                                        <?php
                                                        endforeach;
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Date*</label>
                                                    <input type="date" class="form-control input-rounded input-default mb-3" name="date" placeholder="jj/mm/aaaa">
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Montant*</label>
													<input type="text" class="form-control input-rounded input-default mb-3" name="montant" placeholder="Montant">
												</div>
											</div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Justificatif</label>
													<input type="file" class="form-control input-rounded form-file-input" name="justificatif" id="justificatif" placeholder="Justificatif" accept="image/jpeg,image/png,application/pdf">
												</div>
											</div>
                                            <div class="col-6 mb-2">
                                                <div class="form-group">
                                                    <label class="text-label">Mode de paiment*</label>
                                                    <select name="id_modePaiement" class="default-select form-control input-rounded wide mb-3">
														<?php
              $modepaiements = getModepaiement(null, $connection);
              foreach ($modepaiements as $modepaiement): ?>
                                                        <option value="<?= $modepaiement[
                                                            "id"
                                                        ] ?>"><?= $modepaiement[
    "libelle"
] ?></option>
                                                        <?php endforeach;
              ?>
                                                    </select>
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
													<label class="text-label">Commentaire</label>
                                                    <textarea class="form-control input-rounded input-default mb-3" name="commentaire" placeholder="Commentaire"></textarea>
                                                </div>
                                            </div>
											<div class="col-6 mb-2">
												<div class="form-group">
                                                    <label class="text-label">Les impayés</label><br>
													<span id="lesImpayes">
														<?php
              $impayes = getImpaye($lots[0]["id"], null, $connection);
              foreach ($impayes as $impaye):
                  if (intval($impaye["id_exercice"]) < 0): ?>
														<div class="form-check form-check-inline">
															<label class="form-check-label">
																<input type="checkbox" class="form-check-input" name="impayes[]" value="<?= $impaye[
                    "id_rel"
                ] ?>"><?= getPaymentImpayeLabel($impaye, $connection) ?>
															</label>
														</div>
														<?php elseif (intval($impaye["id_exercice"]) == 0): ?>
														<div class="form-check form-check-inline">
															<label class="form-check-label">
																<input type="checkbox" class="form-check-input" name="impayes[]" value="<?= $impaye[
                    "id_rel"
                ] ?>">Impayé promoteur
															</label>
														</div>
														<?php endif;
              endforeach;
              ?>
													</span>
												</div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
							</div>
                        </div>
                    </div>
                </div>
				<div class="row" id="blockOFjustificatif" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Justificatif</h4>
                            </div>
							<div class="card-body">
								<div class="row">
									<div class="col-12">
										<iframe src="#" width="100%" height="500px"></iframe>
									</div>
								</div>
							</div>
						</div>
                    </div>
                </div>
			</div>
        </div>
		<div class="row d-none d-print-block" id="printZone"></div>
<?php else:goto iomEnd;endif;
else:

    iomEnd:
    $paiements = getPaiement(
        null,
        $GLOBALS["id_copropriete"],
        null,
        $connection,
    );
    ?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Paiements</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<a href="dashboard.php?page=paiements&action=add" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-plus color-primary"></i></span> Ajouter
					</a>
					<?php if (
         $_SESSION["id_usertype"] === "1" ||
         $_SESSION["id_usertype"] === "2" ||
         $_SESSION["id_usertype"] === "3"
     ): ?>
					<a href="export/export_paiement.php?id_copropriete=<?= $GLOBALS[
         "id_copropriete"
     ] ?>&id_exercice=<?= $GLOBALS[
    "id_exercice"
] ?>" type="button" class="btn btn-rounded btn-primary me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter
					</a>
					<?php endif; ?>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Code lot</th>
                                                <th>Propriétaire</th>
                                                <th>Montant</th>
                                                <th>Commentaire</th>
                                                <th>Responsable</th>
												<?php if (
                $_SESSION["id_usertype"] === "1" ||
                $_SESSION["id_usertype"] === "2" ||
                $_SESSION["id_usertype"] === "3"
            ): ?>
                                                <th class="text-center">Actions</th>
												<?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($paiements as $paiement):

                                            $lot = getLot(
                                                $paiement["id_lot"],
                                                null,
                                                null,
                                                $connection,
                                            );
                                            $proprietaire = getProprietaire(
                                                $lot[0]["id_proprietaire"],
                                                null,
                                                $connection,
                                            );
                                            $syndic = getSyndic(
                                                $paiement["id_syndic"],
                                                null,
                                                $connection,
                                            );
                                            ?>
                                            <tr class="trPaiement-<?= $paiement[
                                                "id"
                                            ] ?>">
                                                <td data-order="<?= htmlspecialchars(
                                                    date("Y-m-d", strtotime($paiement["date"])),
                                                    ENT_QUOTES,
                                                    "UTF-8",
                                                ) ?>"><?= date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $paiement["date"],
                                                    ),
                                                ) ?></td>
                                                <td><?= $lot[0]["code"] ?></td>
                                                <td><?= $proprietaire[0][
                                                    "civilite"
                                                ] ?> <?= $proprietaire[0][
     "prenom"
 ] ?> <?= $proprietaire[0]["nom"] ?></td>
                                                <td><?= $paiement[
                                                    "montant"
                                                ] ?></td>
                                                <td><?= $paiement[
                                                    "commentaire"
                                                ] ?></td>
                                                <td><?= $syndic[0]["civilite"] .
                                                    " " .
                                                    $syndic[0]["prenom"] .
                                                    " " .
                                                    $syndic[0]["nom"] ?></td>
												<?php if (
                $_SESSION["id_usertype"] === "1" ||
                $_SESSION["id_usertype"] === "2" ||
                $_SESSION["id_usertype"] === "3"
            ): ?>
												<td class="text-center">
													<a href="./dashboard.php?page=paiements&action=update&id=<?= $paiement[
                 "id"
             ] ?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
													<a href="javascript:void(0);" class="btn btn-secondary shadow btn-xs sharp me-1" data-bs-toggle="modal" data-bs-target=".delPaiement-<?= $paiement[
                 "id"
             ] ?>"><i class="fas fa-trash"></i></a>
												</td>
												<?php endif; ?>
                                            </tr>
											<?php if (
               $_SESSION["id_usertype"] === "1" ||
               $_SESSION["id_usertype"] === "2" ||
               $_SESSION["id_usertype"] === "3"
           ): ?>
											<!-- Modal -->
											<div class="modal fade delPaiement-<?= $paiement["id"] ?>">
												<div class="modal-dialog modal-dialog-centered" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title">Supprimer le paiement</h5>
															<button type="button" class="btn-close" data-bs-dismiss="modal">
															</button>
														</div>
														<div class="modal-body">
															<div class="text-center mb-4"><i class="fas fa-exclamation-triangle" style="font-size: 111px;"></i></div>
															<div class="text-center">Êtes-vous sûr de vouloir supprimer ce paiement ?</div>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-rounded btn-outline-primary" data-bs-dismiss="modal">Non</button>
															<button type="button" class="btn btn-rounded btn-danger delPaiementBtn" data-id="<?= $paiement[
                   "id"
               ] ?>">Oui</button>
														</div>
													</div>
												</div>
											</div>
											<?php endif; ?>
                                            <?php
                                        endforeach; ?>
                                        <tfoot>
                                             <tr>
                                                <th>Date</th>
                                                <th>Code lot</th>
                                                <th>Propriétaire</th>
                                                <th>Montant</th>
                                                <th>Commentaire</th>
                                                <th>Responsable</th>
												<?php if (
                $_SESSION["id_usertype"] === "1" ||
                $_SESSION["id_usertype"] === "2" ||
                $_SESSION["id_usertype"] === "3"
            ): ?>
                                                <th class="text-center">Actions</th>
												<?php endif; ?>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
        </div>
<?php
endif;
?>
