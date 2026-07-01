<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

$connection = $GLOBALS["connection"];
$id_copropriete = isset($_GET["id_copropriete"]) ? $_GET["id_copropriete"] : null;
if ($id_copropriete === null) {
    http_response_code(400);
    exit("Parametres invalides");
}

function contentieuxExcelEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function contentieuxExcelDate($date)
{
    if ($date === null || $date === "" || $date === "0000-00-00") {
        return "";
    }

    return date("d/m/Y", strtotime($date));
}

function contentieuxExcelOwnerName($proprietaire)
{
    if (count($proprietaire) === 0) {
        return "";
    }

    return trim(
        $proprietaire[0]["civilite"] . " " . $proprietaire[0]["prenom"] . " " . $proprietaire[0]["nom"]
    );
}

function contentieuxExcelFilename($residenceName)
{
    $filename = strtolower("contentieux_" . $residenceName);
    $filename = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filename);
    $filename = preg_replace("/[^a-z0-9]+/", "_", $filename);
    $filename = trim($filename, "_");

    return ($filename !== "" ? $filename : "contentieux") . ".xls";
}

$copropriete = getCopropriete($id_copropriete, $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$contentieuxs = getContentieux(null, $id_copropriete, $connection);

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=" .
        contentieuxExcelFilename($residenceName)
);
echo "\xEF\xBB\xBF";
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #c8c8c8; font-weight: bold; text-align: center; }
        .title { font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>
<table>
    <tr><td colspan="4" class="title">Contentieux</td></tr>
    <tr><td colspan="4"><?= contentieuxExcelEscape($residenceName) ?></td></tr>
    <tr>
        <th>Code du lot</th>
        <th>Proprietaire</th>
        <th>Etat</th>
        <th>Date d'etat</th>
    </tr>
    <?php foreach ($contentieuxs as $contentieux): ?>
        <?php
        $lot = getLot($contentieux["id_lot"], null, null, $connection);
        if (count($lot) === 0) {
            continue;
        }
        $proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
        $etat = getEtat($contentieux["id_etat"], $connection);
        ?>
    <tr>
        <td><?= contentieuxExcelEscape($lot[0]["code"]) ?></td>
        <td><?= contentieuxExcelEscape(contentieuxExcelOwnerName($proprietaire)) ?></td>
        <td><?= contentieuxExcelEscape(count($etat) > 0 ? $etat[0]["libelle"] : "") ?></td>
        <td><?= contentieuxExcelDate($contentieux["date"]) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
