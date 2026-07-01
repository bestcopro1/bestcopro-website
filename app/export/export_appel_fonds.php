<?php
require_once __DIR__ . "/../session.php";
bestcopro_start_session();
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";

$connection = $GLOBALS["connection"];

function appelFondsAmount($value)
{
    return number_format(floatval($value), 2, ",", " ") . " DHS";
}

function appelFondsText($value)
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, "UTF-8");
}

function appelFondsGetInfo($id_lot, $id_exercice, $connection)
{
    $request =
        "SELECT id_periodePaiement, SUM(partFonct)+SUM(partInv) FROM exercice, rel_lot_exercice WHERE id_lot = ? AND id_exercice = ? AND exercice.id = ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $id_lot, $id_exercice, $id_exercice);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_periodePaiement, $sumPartFonctPartInv);
            $stmt->fetch();
            return [
                "id_periodePaiement" => $id_periodePaiement,
                "sumPartFonctPartInv" => $sumPartFonctPartInv,
            ];
        }
    }

    return [
        "id_periodePaiement" => null,
        "sumPartFonctPartInv" => 0,
    ];
}

function appelFondsGetEtats($id_lot, $id_periodePaiement, $connection)
{
    $interval = "1 MONTH";
    if ($id_periodePaiement == "2") {
        $interval = "3 MONTH";
    } elseif ($id_periodePaiement == "3") {
        $interval = "6 MONTH";
    } elseif ($id_periodePaiement == "4") {
        $interval = "1 YEAR";
    }

    $request =
        "SELECT id_lot, id_exercice, SUM(partFonct), SUM(partInv), SUM(cotisation) FROM rel_lot_exercice WHERE id_lot = ? AND DATE_SUB(dateFinPeriode, INTERVAL " .
        $interval .
        ") <= CURDATE() GROUP BY id_exercice ORDER BY id_exercice DESC";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_lot);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $idLot,
                $idExercice,
                $sumPartFonct,
                $sumPartInv,
                $sumCotisation,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id_lot" => $idLot,
                    "id_exercice" => $idExercice,
                    "sumPartFonct" => $sumPartFonct,
                    "sumPartInv" => $sumPartInv,
                    "sumCotisation" => $sumCotisation,
                ];
            }
            return $result;
        }
    }

    return [];
}

function appelFondsReplaceFirstTextNode($xpath, $node, $text)
{
    $textNodes = $xpath->query(".//w:t", $node);
    if ($textNodes->length === 0) {
        return;
    }

    for ($i = 0; $i < $textNodes->length; $i++) {
        $textNodes->item($i)->nodeValue = $i === 0 ? $text : "";
    }
}

function appelFondsSetCellText($dom, $xpath, $cell, $text)
{
    $tcPr = null;
    foreach (iterator_to_array($cell->childNodes) as $child) {
        if ($child->localName === "tcPr") {
            $tcPr = $child;
            continue;
        }
        $cell->removeChild($child);
    }

    if ($tcPr === null) {
        $tcPr = $dom->createElementNS(
            "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
            "w:tcPr"
        );
        $cell->appendChild($tcPr);
    }

    $p = $dom->createElementNS(
        "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
        "w:p"
    );
    $r = $dom->createElementNS(
        "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
        "w:r"
    );
    $t = $dom->createElementNS(
        "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
        "w:t"
    );
    $t->appendChild($dom->createTextNode($text));
    $r->appendChild($t);
    $p->appendChild($r);
    $cell->appendChild($p);
}

function appelFondsSetTableValue($dom, $xpath, $label, $value, $newLabel = null)
{
    foreach ($xpath->query("//w:tr") as $row) {
        $cells = $xpath->query("./w:tc", $row);
        if ($cells->length < 2) {
            continue;
        }

        $cellLabel = trim($xpath->evaluate("string(.)", $cells->item(0)));
        if (stripos($cellLabel, $label) === false) {
            continue;
        }

        if ($newLabel !== null) {
            appelFondsReplaceFirstTextNode($xpath, $cells->item(0), $newLabel);
        }
        appelFondsSetCellText($dom, $xpath, $cells->item(1), $value);
        return;
    }
}

function appelFondsApplyTemplateValues($xml, $values)
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($xml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace(
        "w",
        "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    );

    foreach ($xpath->query("//w:p") as $paragraph) {
        $text = $xpath->evaluate("string(.)", $paragraph);
        if (strpos($text, "Rabat, le") !== false) {
            appelFondsReplaceFirstTextNode($xpath, $paragraph, $values["date"]);
        } elseif (strpos($text, "Syndicat") !== false && strpos($text, "Copropri") !== false) {
            appelFondsReplaceFirstTextNode(
                $xpath,
                $paragraph,
                "Syndicat des Coproprietaires : " . $values["copropriete"]
            );
        } elseif (strpos($text, "SYNDIC RESIDENCE") !== false) {
            appelFondsReplaceFirstTextNode($xpath, $paragraph, "SYNDIC RESIDENCE");
        } elseif (strpos($text, "RYAD AL ANDALOUS") !== false) {
            appelFondsReplaceFirstTextNode(
                $xpath,
                $paragraph,
                strtoupper($values["copropriete"])
            );
        }
    }

    appelFondsSetTableValue($dom, $xpath, "NOM", $values["proprietaire"]);
    appelFondsSetTableValue($dom, $xpath, "Code", $values["code"]);
    appelFondsSetTableValue(
        $dom,
        $xpath,
        "Solde",
        $values["soldeAnterieur"],
        "Solde d'impaye anterieur au " . $values["dateSoldeAnterieur"]
    );
    appelFondsSetTableValue(
        $dom,
        $xpath,
        "Base",
        $values["baseCotisation"],
        "Base de cotisation " . $values["annee"]
    );
    appelFondsSetTableValue($dom, $xpath, "Encaissement", $values["encaissement"]);
    appelFondsSetTableValue($dom, $xpath, "Montant", $values["montantRestant"]);

    return $dom->saveXML();
}

$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING);
$idExercice = filter_input(INPUT_GET, "id_exercice", FILTER_SANITIZE_STRING);
if ($id == "" || $idExercice == "") {
    http_response_code(400);
    exit("Parametres invalides");
}

$lot = getLot($id, null, null, $connection);
if (count($lot) === 0) {
    http_response_code(404);
    exit("Lot introuvable");
}

$exercice = getExercice($idExercice, null, $connection);
if (count($exercice) === 0) {
    http_response_code(404);
    exit("Exercice introuvable");
}

$copropriete = getCopropriete($lot[0]["id_copropriete"], $connection);
$proprietaire = getProprietaire($lot[0]["id_proprietaire"], null, $connection);
if (count($copropriete) === 0 || count($proprietaire) === 0) {
    http_response_code(404);
    exit("Informations introuvables");
}

$info = appelFondsGetInfo($lot[0]["id"], $idExercice, $connection);
$etats = appelFondsGetEtats(
    $lot[0]["id"],
    $info["id_periodePaiement"],
    $connection
);
$relLotExercice = getRel_lot_exercice($lot[0]["id"], $idExercice, $connection);

$annee = date("Y", strtotime($exercice[0]["dateDebut"]));
$dateSoldeAnterieur = "31/12/" . (intval($annee) - 1);
$soldeAnterieur = 0;
foreach ($etats as $etat) {
    if ((string) $etat["id_exercice"] === (string) $idExercice) {
        continue;
    }

    $etatImpaye = floatval($etat["sumPartFonct"]) + floatval($etat["sumPartInv"]);
    $soldeAnterieur += max(0, $etatImpaye - floatval($etat["sumCotisation"]));
}

$baseCotisation = floatval($info["sumPartFonctPartInv"]);
$encaissement = 0;
foreach ($relLotExercice as $periode) {
    $encaissement += floatval($periode["cotisation"]);
}
$montantRestant = max(0, $soldeAnterieur + $baseCotisation - $encaissement);

$template = __DIR__ . "/../templates/appel_fonds_marbella.docx";
if (!file_exists($template)) {
    http_response_code(500);
    exit("Modele d'appel de fonds introuvable");
}
if (!class_exists("ZipArchive")) {
    http_response_code(500);
    exit("Extension ZipArchive indisponible");
}

$output = tempnam(sys_get_temp_dir(), "appel_fonds_");
if ($output === false || !copy($template, $output)) {
    http_response_code(500);
    exit("Impossible de preparer le document");
}

$zip = new ZipArchive();
if ($zip->open($output) !== true) {
    http_response_code(500);
    exit("Impossible d'ouvrir le document");
}

$documentXml = $zip->getFromName("word/document.xml");
if ($documentXml === false) {
    $zip->close();
    http_response_code(500);
    exit("Document Word invalide");
}

$values = [
    "date" => "Rabat, le " . date("d/m/Y"),
    "copropriete" => $copropriete[0]["nom"],
    "proprietaire" => trim(
        $proprietaire[0]["civilite"] .
            " " .
            $proprietaire[0]["nom"] .
            " " .
            $proprietaire[0]["prenom"]
    ),
    "code" => $lot[0]["code"],
    "dateSoldeAnterieur" => $dateSoldeAnterieur,
    "annee" => $annee,
    "soldeAnterieur" => appelFondsAmount($soldeAnterieur),
    "baseCotisation" => appelFondsAmount($baseCotisation),
    "encaissement" => appelFondsAmount($encaissement),
    "montantRestant" => appelFondsAmount($montantRestant),
];

$zip->addFromString(
    "word/document.xml",
    appelFondsApplyTemplateValues($documentXml, $values)
);
$zip->close();

$fileName = "appel_de_fonds_" . preg_replace("/[^A-Za-z0-9_-]+/", "_", $lot[0]["code"]) . ".docx";
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
header("Content-Length: " . filesize($output));
readfile($output);
unlink($output);
