<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();

include_once __DIR__ . "/../config/db.php";
$connection = $GLOBALS["connection"];

if (!$connection) {
    http_response_code(500);
    echo "Connexion à la base de données indisponible.";
    exit();
}
if (!isset($_SESSION["id"])) {
    http_response_code(401);
    echo "Votre session a expiré. Veuillez vous reconnecter.";
    exit();
}

function id_typeLot($typelot, $connection)
{
    $request = "SELECT id FROM typelot WHERE libelle LIKE ? LIMIT 1";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $typelot);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id);
        $stmt->fetch();
        return $stmt->num_rows === 1 ? $id : "";
    }
    return "";
}

function id_typeProprietaire($typeProprietaire, $connection)
{
    $request = "SELECT id FROM typeproprietaire WHERE libelle LIKE ? LIMIT 1";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $typeProprietaire);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id);
        $stmt->fetch();
        return $stmt->num_rows === 1 ? $id : "";
    }
    return "";
}

function id_proprietaire($nom, $prenom, $telephone, $connection)
{
    $request = "SELECT id FROM proprietaire WHERE prenom LIKE ? AND nom LIKE ? AND (telephone LIKE ? OR mobile LIKE ?) LIMIT 1";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ssss", $prenom, $nom, $telephone, $telephone);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id);
        $stmt->fetch();
        return $stmt->num_rows === 1 ? $id : "";
    }
    return "";
}

function getPassword($length = 8)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars), 0, $length);
}

function csvToUtf8($value)
{
    $value = (string) $value;
    if ($value === "") {
        return "";
    }
    if (function_exists("mb_check_encoding") && mb_check_encoding($value, "UTF-8")) {
        return trim($value);
    }
    if (function_exists("mb_convert_encoding")) {
        return trim(mb_convert_encoding($value, "UTF-8", "Windows-1252"));
    }
    $converted = iconv("Windows-1252", "UTF-8//IGNORE", $value);
    return trim($converted === false ? $value : $converted);
}

function normalizeHeader($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', csvToUtf8($value));
    $ascii = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value);
    $ascii = $ascii === false ? $value : $ascii;
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $ascii));
}

function csvAmount($value, &$valid = null)
{
    $normalized = str_replace([" ", "\xc2\xa0", "\xa0"], "", trim((string) $value));
    $normalized = str_replace(",", ".", $normalized);
    $valid = $normalized !== "" && is_numeric($normalized);
    return $valid ? (float) $normalized : 0.0;
}

function emptyCsvRow($row)
{
    foreach ($row as $value) {
        if (trim((string) $value) !== "") {
            return false;
        }
    }
    return true;
}

function htmlValue($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function validCsvDate($value)
{
    if ($value === "") {
        return true;
    }
    $date = DateTime::createFromFormat("!d/m/Y", $value);
    return $date && $date->format("d/m/Y") === $value;
}

function databaseFailure($connection, $message)
{
    throw new RuntimeException($message . ($connection->error ? " " . $connection->error : ""));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit();
}
if (!isset($_FILES["file"], $_POST["id_copropriete"])) {
    echo "Aucun fichier n'a été reçu. Vérifiez la taille du fichier CSV et les limites du serveur.";
    exit();
}
if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la limite d'envoi du serveur.",
        UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la limite autorisée par le formulaire.",
        UPLOAD_ERR_PARTIAL => "Le fichier a été envoyé partiellement.",
        UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été sélectionné.",
        UPLOAD_ERR_NO_TMP_DIR => "Le dossier temporaire PHP est manquant.",
        UPLOAD_ERR_CANT_WRITE => "Le serveur n'a pas pu écrire le fichier importé.",
        UPLOAD_ERR_EXTENSION => "Une extension PHP a bloqué l'import du fichier.",
    ];
    echo $uploadErrors[$_FILES["file"]["error"]] ?? "Erreur inconnue pendant l'import du fichier.";
    exit();
}

$id_copropriete = trim((string) $_POST["id_copropriete"]);
if ($id_copropriete === "") {
    echo "La copropriété cible est introuvable pour cet import.";
    exit();
}
if (strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION)) !== "csv") {
    echo "Veuillez importer un fichier CSV conforme au modèle Majorelle.";
    exit();
}

$uploadDir = __DIR__ . "/../upload";
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    echo "Le dossier d'import est introuvable et n'a pas pu être créé.";
    exit();
}
if (!is_writable($uploadDir)) {
    echo "Le dossier d'import n'est pas accessible en écriture.";
    exit();
}

$location = $uploadDir . "/import-lots-" . uniqid("", true) . ".csv";
if (!move_uploaded_file($_FILES["file"]["tmp_name"], $location)) {
    echo "Le fichier CSV n'a pas pu être enregistré sur le serveur.";
    exit();
}

$transactionStarted = false;
try {
    $handle = fopen($location, "r");
    if ($handle === false) {
        throw new RuntimeException("Le fichier CSV ne peut pas être lu.");
    }

    $header = fgetcsv($handle, 0, ";");
    $expectedHeaders = [
        "Type", "code", "Immeuble", "Etage", "Numero", "Titre foncier",
        "Date d'acquisition", "Tantième", "BASE COTISATION", "Cumul impayés N-5",
        "Cumul impayés N-4", "Cumul impayés N-3", "Cumul impayés N-2",
        "Cumul impayés N-1", "Impaye promoteur", "Résident/Promoteur", "Civilité",
        "Nom", "Prenom", "Adresse", "Ville", "Code postale", "Email",
        "Telephone 1", "Telephone 2",
    ];
    if ($header === false || count($header) !== count($expectedHeaders)) {
        fclose($handle);
        throw new RuntimeException("Le fichier CSV ne correspond pas au modèle Majorelle attendu (25 colonnes). ");
    }
    foreach ($expectedHeaders as $index => $expectedHeader) {
        if (normalizeHeader($header[$index]) !== normalizeHeader($expectedHeader)) {
            fclose($handle);
            throw new RuntimeException("La colonne " . ($index + 1) . " doit être « " . $expectedHeader . " ».");
        }
    }

    $rows = [];
    $lineNumber = 1;
    $allowedLotTypes = ["Appartement", "Local commercial", "Magasin", "Villa"];
    $allowedOwnerTypes = ["Résident", "Promoteur"];
    while (($data = fgetcsv($handle, 0, ";")) !== false) {
        ++$lineNumber;
        $data = array_map("csvToUtf8", $data);
        if (emptyCsvRow($data)) {
            continue;
        }
        if (count($data) !== count($expectedHeaders)) {
            fclose($handle);
            throw new RuntimeException("La ligne " . $lineNumber . " ne contient pas 25 colonnes.");
        }
        if (!in_array($data[0], $allowedLotTypes, true)) {
            fclose($handle);
            throw new RuntimeException("Type de lot invalide à la ligne " . $lineNumber . ".");
        }
        if ($data[1] === "" || $data[2] === "" || $data[4] === "") {
            fclose($handle);
            throw new RuntimeException("Code, immeuble ou numéro manquant à la ligne " . $lineNumber . ".");
        }
        if (filter_var($data[3], FILTER_VALIDATE_INT) === false || (int) $data[3] < 0) {
            fclose($handle);
            throw new RuntimeException("L'étage doit être un entier positif ou nul à la ligne " . $lineNumber . ".");
        }
        if (!validCsvDate($data[6])) {
            fclose($handle);
            throw new RuntimeException("Date d'acquisition invalide à la ligne " . $lineNumber . " (format attendu : JJ/MM/AAAA). ");
        }
        $tantieme = csvAmount($data[7], $validTantieme);
        if (!$validTantieme || $tantieme <= 0) {
            fclose($handle);
            throw new RuntimeException("Tantième invalide à la ligne " . $lineNumber . ".");
        }
        $baseCotisation = csvAmount($data[8], $validBase);
        if (!$validBase || $baseCotisation < 0) {
            fclose($handle);
            throw new RuntimeException("BASE COTISATION invalide à la ligne " . $lineNumber . ".");
        }
        if (!in_array($data[15], $allowedOwnerTypes, true)) {
            fclose($handle);
            throw new RuntimeException("Type de propriétaire invalide à la ligne " . $lineNumber . ".");
        }
        if ($data[17] === "" || $data[18] === "") {
            fclose($handle);
            throw new RuntimeException("Nom ou prénom du propriétaire manquant à la ligne " . $lineNumber . ".");
        }

        $arrears = [];
        foreach ([14, 13, 12, 11, 10, 9] as $columnIndex) {
            if ($data[$columnIndex] === "") {
                $arrears[] = 0.0;
                continue;
            }
            $arrears[] = csvAmount($data[$columnIndex], $validArrear);
            if (!$validArrear || end($arrears) < 0) {
                fclose($handle);
                throw new RuntimeException("Montant d'impayé invalide à la ligne " . $lineNumber . ".");
            }
        }

        $rows[] = [
            "line" => $lineNumber,
            "type" => $data[0], "code" => $data[1], "immeuble" => $data[2],
            "etage" => (int) $data[3], "numero" => $data[4], "foncier" => $data[5],
            "date_display" => $data[6],
            "date" => $data[6] === "" ? null : DateTime::createFromFormat("!d/m/Y", $data[6])->format("Y-m-d"),
            "tantieme" => $tantieme, "base_cotisation" => $baseCotisation,
            "arrears" => $arrears, "owner_type" => $data[15], "civilite" => $data[16],
            "nom" => $data[17], "prenom" => $data[18], "adresse" => $data[19],
            "ville" => $data[20], "code_postal" => $data[21], "email" => $data[22],
            "telephone" => $data[23], "mobile" => $data[24],
        ];
    }
    fclose($handle);

    if (count($rows) === 0) {
        throw new RuntimeException("Le fichier CSV ne contient aucun lot.");
    }

    $expectedLotCount = null;
    $countStmt = $connection->prepare("SELECT nbrLot FROM copropriete WHERE id = ? LIMIT 1");
    if (!$countStmt) {
        databaseFailure($connection, "Impossible de vérifier la copropriété.");
    }
    $countStmt->bind_param("s", $id_copropriete);
    if (!$countStmt->execute()) {
        databaseFailure($connection, "Impossible de vérifier la copropriété.");
    }
    $countStmt->bind_result($expectedLotCount);
    if (!$countStmt->fetch()) {
        throw new RuntimeException("La copropriété cible est introuvable.");
    }
    $countStmt->close();
    if ((int) $expectedLotCount !== count($rows)) {
        throw new RuntimeException("Le CSV contient " . count($rows) . " lots, alors que la copropriété en attend " . (int) $expectedLotCount . ".");
    }

    $connection->begin_transaction();
    $transactionStarted = true;
    $deleteStmt = $connection->prepare("DELETE FROM lot WHERE id_copropriete = ?");
    if (!$deleteStmt) {
        databaseFailure($connection, "Impossible de préparer l'import des lots.");
    }
    $deleteStmt->bind_param("s", $id_copropriete);
    if (!$deleteStmt->execute()) {
        databaseFailure($connection, "Impossible de remplacer les lots existants.");
    }

    $codeHtml = "";
    $codeHtml2 = "";
    $counter = 0;
    foreach ($rows as $row) {
        ++$counter;
        $id_typeLot = id_typeLot($row["type"], $connection);
        if ($id_typeLot === "") {
            throw new RuntimeException("Le type de lot « " . $row["type"] . " » n'existe pas dans la base.");
        }

        $id_proprietaire = id_proprietaire($row["nom"], $row["prenom"], $row["telephone"], $connection);
        $allowedCivilites = ["M.", "Mme.", "Mlle.", "Mme/M.", "Sté."];
        $civilite = in_array($row["civilite"], $allowedCivilites, true) ? $row["civilite"] : "Mme/M.";
        if ($id_proprietaire === "") {
            $ownerStmt = $connection->prepare("INSERT INTO proprietaire (civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$ownerStmt) {
                databaseFailure($connection, "Impossible de préparer le propriétaire de la ligne " . $row["line"] . ".");
            }
            $ownerStmt->bind_param("sssssssss", $civilite, $row["prenom"], $row["nom"], $row["email"], $row["telephone"], $row["mobile"], $row["adresse"], $row["ville"], $row["code_postal"]);
            if (!$ownerStmt->execute()) {
                databaseFailure($connection, "Impossible d'enregistrer le propriétaire de la ligne " . $row["line"] . ".");
            }
            $id_proprietaire = $connection->insert_id;
        }

        $id_typeProprietaire = id_typeProprietaire($row["owner_type"], $connection);
        if ($id_typeProprietaire === "") {
            throw new RuntimeException("Le type de propriétaire « " . $row["owner_type"] . " » n'existe pas dans la base.");
        }

        $lotStmt = $connection->prepare("INSERT INTO lot (code, id_typeLot, numeroImm, etage, numero, foncier, tantieme, dateAcquisition, dateRemiseCle, id_proprietaire, id_typeProprietaire, impaye0, impaye1, impaye2, impaye3, impaye4, impaye5, id_copropriete) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$lotStmt) {
            databaseFailure($connection, "Impossible de préparer le lot de la ligne " . $row["line"] . ".");
        }
        $dateAcquisition = $row["date"];
        $dateRemiseCle = $row["date"];
        $lotStmt->bind_param("ssssssssssssssssss", $row["code"], $id_typeLot, $row["immeuble"], $row["etage"], $row["numero"], $row["foncier"], $row["tantieme"], $dateAcquisition, $dateRemiseCle, $id_proprietaire, $id_typeProprietaire, $row["arrears"][0], $row["arrears"][1], $row["arrears"][2], $row["arrears"][3], $row["arrears"][4], $row["arrears"][5], $id_copropriete);
        if (!$lotStmt->execute()) {
            databaseFailure($connection, "Impossible d'enregistrer le lot de la ligne " . $row["line"] . ".");
        }
        $id_lot = $connection->insert_id;

        $password = getPassword();
        $token = md5(uniqid((string) $id_lot, true));
        $tokenStmt = $connection->prepare("UPDATE lot SET password = ?, token = ? WHERE id = ?");
        if (!$tokenStmt) {
            databaseFailure($connection, "Impossible de finaliser le lot de la ligne " . $row["line"] . ".");
        }
        $tokenStmt->bind_param("sss", $password, $token, $id_lot);
        if (!$tokenStmt->execute()) {
            databaseFailure($connection, "Impossible de finaliser le lot de la ligne " . $row["line"] . ".");
        }

        $lotCode = htmlValue($row["code"]);
        $lotType = htmlValue($row["type"]);
        $foncier = htmlValue($row["foncier"]);
        $owner = htmlValue(trim($civilite . " " . $row["prenom"] . " " . $row["nom"]));
        $tantieme = number_format($row["tantieme"], 2, ".", "");
        $baseCotisation = number_format($row["base_cotisation"], 2, ".", "");
        $dateDisplay = htmlValue($row["date_display"]);

        $hidden = '<input type="hidden" name="id_lot_' . $counter . '" value="' . htmlValue($id_lot) . '">';
        $hidden .= '<input type="hidden" name="id_typeLot_' . $counter . '" value="' . htmlValue($id_typeLot) . '">';
        $hidden .= '<input type="hidden" name="numeroImm_' . $counter . '" value="' . htmlValue($row["immeuble"]) . '">';
        $hidden .= '<input type="hidden" name="etage_' . $counter . '" value="' . htmlValue($row["etage"]) . '">';
        $hidden .= '<input type="hidden" name="numero_' . $counter . '" value="' . htmlValue($row["numero"]) . '">';
        $hidden .= '<input type="hidden" name="foncier_' . $counter . '" value="' . $foncier . '">';
        $hidden .= '<input type="hidden" name="tantieme_' . $counter . '" value="' . $tantieme . '" class="tantieme">';
        $hidden .= '<input type="hidden" name="dateAcquisition_' . $counter . '" value="' . htmlValue($dateAcquisition) . '">';
        $hidden .= '<input type="hidden" name="dateRemiseCle_' . $counter . '" value="' . htmlValue($dateRemiseCle) . '">';
        $hidden .= '<input type="hidden" name="id_proprietaire_' . $counter . '" value="' . htmlValue($id_proprietaire) . '">';
        $hidden .= '<input type="hidden" name="id_typeProprietaire_' . $counter . '" value="' . htmlValue($id_typeProprietaire) . '">';
        foreach ($row["arrears"] as $arrearIndex => $arrear) {
            $hidden .= '<input type="hidden" name="impaye' . $arrearIndex . '_' . $counter . '" value="' . number_format($arrear, 2, ".", "") . '">';
        }

        $codeHtml .= '<tr><td><span id="tdCode_' . $counter . '">' . $lotCode . '</span>' . $hidden . '</td>';
        $codeHtml .= '<td><span id="tdType_' . $counter . '">' . $lotType . '</span></td><td><span id="tdTitre_' . $counter . '">' . $foncier . '</span></td>';
        $codeHtml .= '<td><span id="tdProprio_' . $counter . '">' . $owner . '</span></td><td><span id="tdTantieme_' . $counter . '">' . $tantieme . '</span></td>';
        $codeHtml .= '<td><span id="tdAcqui_' . $counter . '">' . $dateDisplay . '</span></td><td><span id="tdRemise_' . $counter . '">' . $dateDisplay . '</span></td>';
        $codeHtml .= '<td><a href="#" class="btn btn-primary shadow btn-xs sharp me-1 edit_lot" data-lot-line="' . $counter . '"><i class="fas fa-pencil-alt"></i></a></td></tr>';

        $codeHtml2 .= '<tr><td><span id="tdCode2_' . $counter . '">' . $lotCode . '</span><input type="hidden" name="id_lot2_' . $counter . '" value="' . htmlValue($id_lot) . '"></td>';
        $codeHtml2 .= '<td><span id="tdType2_' . $counter . '">' . $lotType . '</span></td><td><span id="tdProprio2_' . $counter . '">' . $owner . '</span></td>';
        $codeHtml2 .= '<td><span id="tdTantieme2_' . $counter . '">' . $tantieme . '</span></td>';
        $codeHtml2 .= '<td><input type="number" min="0" step="0.01" class="form-control input-default partFonct allocation-value" name="partFonct_' . $counter . '" value="' . $baseCotisation . '" placeholder="0.00"></td>';
        $codeHtml2 .= '<td><input type="number" min="0" step="0.01" class="form-control input-default partInv allocation-value" name="partInv_' . $counter . '" value="0.00" placeholder="0.00"></td></tr>';
    }

    $connection->commit();
    $transactionStarted = false;
    echo "done|" . $codeHtml . "|" . $codeHtml2 . "|" . $counter;
} catch (Throwable $error) {
    if ($transactionStarted) {
        $connection->rollback();
    }
    http_response_code(400);
    echo htmlValue($error->getMessage());
} finally {
    if (is_file($location)) {
        unlink($location);
    }
}
