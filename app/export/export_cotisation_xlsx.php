<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/cotisation_export_data.php";
$connection = $GLOBALS["connection"];

function getImmeubleXlsx($id_copropriete, $connection)
{
    $request = "SELECT DISTINCT numeroImm FROM lot WHERE id_copropriete = ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_copropriete);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($numeroImm);
            while ($stmt->fetch()) {
                $result[] = [
                    "numeroImm" => $numeroImm,
                ];
            }
            return $result;
        }
    }

    return [];
}

function getLotByImmeubleXlsx($immeuble, $id_copropriete, $connection)
{
    $request =
        "SELECT lot.id,lot.code,lot.numero,proprietaire.prenom,proprietaire.nom FROM lot,proprietaire WHERE lot.numeroImm = ? AND lot.id_copropriete = ? AND lot.id_proprietaire = proprietaire.id ORDER BY lot.code ASC";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ss", $immeuble, $id_copropriete);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $code, $numero, $prenom, $nom);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "code" => $code,
                    "numero" => $numero,
                    "prenom" => $prenom,
                    "nom" => $nom,
                ];
            }
            return $result;
        }
    }

    return [];
}

function getCotisationExportPeriodsXlsx($exercice)
{
    $periodePaiement = isset($exercice["id_periodePaiement"])
        ? $exercice["id_periodePaiement"]
        : "1";
    $monthsByPeriod = [
        "1" => 1,
        "2" => 3,
        "3" => 6,
        "4" => 12,
    ];
    $monthsPerPeriod = isset($monthsByPeriod[$periodePaiement])
        ? $monthsByPeriod[$periodePaiement]
        : 1;
    $periodCount = intval(12 / $monthsPerPeriod);
    $periods = [];

    for ($i = 0; $i < $periodCount; $i++) {
        $startOffset = $i * $monthsPerPeriod;
        $endOffset = $startOffset + $monthsPerPeriod - 1;
        $start = date(
            "m/Y",
            strtotime($exercice["dateDebut"] . " + " . $startOffset . " month"),
        );
        $end = date(
            "m/Y",
            strtotime($exercice["dateDebut"] . " + " . $endOffset . " month"),
        );

        if ($periodePaiement == "1") {
            $label = $start;
        } elseif ($periodePaiement == "2") {
            $label = "T" . ($i + 1) . " - Du " . $start . " au " . $end;
        } elseif ($periodePaiement == "3") {
            $label = "S" . ($i + 1) . " - Du " . $start . " au " . $end;
        } else {
            $label = "Du " . $start . " au " . $end;
        }

        $periods[] = [
            "label" => $label,
            "startOffset" => $startOffset,
        ];
    }

    return $periods;
}

function xmlEscapeXlsx($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, "UTF-8");
}

function xlsxColumnName($index)
{
    $name = "";
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $name = chr(65 + $mod) . $name;
        $index = intval(($index - $mod) / 26);
    }
    return $name;
}

function xlsxCell(
    $row,
    $column,
    $value,
    $style = 0,
    $isNumber = false,
    $decimals = 2
) {
    $reference = xlsxColumnName($column) . $row;
    $styleAttribute = $style > 0 ? ' s="' . $style . '"' : "";

    if ($isNumber) {
        return '<c r="' .
            $reference .
            '"' .
            $styleAttribute .
            '><v>' .
            number_format((float) $value, $decimals, ".", "") .
            "</v></c>";
    }

    return '<c r="' .
        $reference .
        '" t="inlineStr"' .
        $styleAttribute .
        '><is><t>' .
        xmlEscapeXlsx($value) .
        "</t></is></c>";
}

function xlsxRow($rowNumber, $cells)
{
    $xml = '<row r="' . $rowNumber . '">';
    foreach ($cells as $index => $cell) {
        $xml .= xlsxCell(
            $rowNumber,
            $index + 1,
            $cell["value"],
            isset($cell["style"]) ? $cell["style"] : 0,
            isset($cell["number"]) ? $cell["number"] : false,
            isset($cell["decimals"]) ? $cell["decimals"] : 2,
        );
    }
    return $xml . "</row>";
}

function xlsxTextCell($value, $style = 0)
{
    return [
        "value" => $value,
        "style" => $style,
        "number" => false,
    ];
}

function xlsxNumberCell($value, $style = 0, $decimals = 2)
{
    return [
        "value" => $value,
        "style" => $style,
        "number" => true,
        "decimals" => $decimals,
    ];
}

function buildWorksheetXml($rows, $merges, $columnCount)
{
    $columns = '<cols>';
    for ($i = 1; $i <= $columnCount; $i++) {
        $width = $i == 1 ? 18 : 13;
        $columns .=
            '<col min="' .
            $i .
            '" max="' .
            $i .
            '" width="' .
            $width .
            '" customWidth="1"/>';
    }
    $columns .= "</cols>";

    $sheetData = "<sheetData>";
    foreach ($rows as $rowNumber => $cells) {
        $sheetData .= xlsxRow($rowNumber, $cells);
    }
    $sheetData .= "</sheetData>";

    $mergeXml = "";
    if (count($merges) > 0) {
        $mergeXml = '<mergeCells count="' . count($merges) . '">';
        foreach ($merges as $merge) {
            $mergeXml .= '<mergeCell ref="' . $merge . '"/>';
        }
        $mergeXml .= "</mergeCells>";
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        $columns .
        $sheetData .
        $mergeXml .
        "</worksheet>";
}

function createXlsxArchive($files)
{
    $zip = "";
    $centralDirectory = "";
    $offset = 0;

    foreach ($files as $name => $content) {
        $nameLength = strlen($name);
        $contentLength = strlen($content);
        $crc = crc32($content);

        $localHeader =
            pack(
                "VvvvvvVVVvv",
                0x04034b50,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $contentLength,
                $contentLength,
                $nameLength,
                0,
            ) . $name;
        $zip .= $localHeader . $content;

        $centralDirectory .=
            pack(
                "VvvvvvvVVVvvvvvVV",
                0x02014b50,
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $contentLength,
                $contentLength,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset,
            ) . $name;

        $offset += strlen($localHeader) + $contentLength;
    }

    $centralDirectoryOffset = strlen($zip);
    $centralDirectorySize = strlen($centralDirectory);
    $entriesCount = count($files);

    return $zip .
        $centralDirectory .
        pack(
            "VvvvvVVv",
            0x06054b50,
            0,
            0,
            $entriesCount,
            $entriesCount,
            $centralDirectorySize,
            $centralDirectoryOffset,
            0,
        );
}

function renderExcelHtml($rows, $columnCount)
{
    $html =
        '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;font-family:Arial,sans-serif;font-size:10px;}td{border:1px solid #000;padding:3px;text-align:center;white-space:nowrap;}.title{font-weight:bold;font-size:14px;border:0;}.immeuble{font-weight:bold;background:#ffa755;}.header{background:#d9eaf7;font-weight:bold;}.total{font-weight:bold;background:#d9eaf7;}.note{text-align:left;border:0;font-weight:bold;}</style></head><body><table>';

    foreach ($rows as $cells) {
        $class = "";
        $colspan = 1;

        if (count($cells) === 1) {
            $value = $cells[0]["value"];
            if (strpos($value, "IMMEUBLE") === 0) {
                $class = "immeuble";
                $colspan = $columnCount;
            } elseif (strpos($value, "NB :") === 0) {
                $class = "note";
                $colspan = $columnCount;
            }
        } elseif (
            isset($cells[1]) &&
            strpos($cells[1]["value"], "Relev") === 0
        ) {
            $class = "title";
        } elseif (isset($cells[0]) && $cells[0]["value"] === "Code") {
            $class = "header";
        } elseif (isset($cells[0]) && $cells[0]["value"] === "TOTAL") {
            $class = "total";
        }

        $html .= "<tr>";
        foreach ($cells as $index => $cell) {
            $cellClass = $class !== "" ? ' class="' . $class . '"' : "";
            $cellColspan =
                $index === 0 && $colspan > 1
                    ? ' colspan="' . $colspan . '"'
                    : "";
            $value = isset($cell["number"]) && $cell["number"]
                ? formatCotisationExportAmount(
                    $cell["value"],
                    isset($cell["decimals"]) ? $cell["decimals"] : 2
                )
                : $cell["value"];
            $html .=
                "<td" .
                $cellClass .
                $cellColspan .
                ">" .
                xmlEscapeXlsx($value) .
                "</td>";
            if ($colspan > 1) {
                break;
            }
        }
        $html .= "</tr>";
    }

    return $html . "</table></body></html>";
}

function buildCotisationRows(
    $immeubles,
    $exercice,
    $nameExercice,
    $periods,
    $periodCount,
    $exportData,
    $dateSituation = null
) {
    $rows = [];
    $merges = [];
    $row = 1;
    $columnCount = 4 + $periodCount;
    $lastColumn = xlsxColumnName($columnCount);
    $periodDueFlags = getCotisationExportPeriodDueFlags(
        $exercice["dateDebut"],
        $periods,
        $dateSituation
    );

    $exportDateLabel = date("d/m/Y");
    if ($dateSituation !== null) {
        $exportDateLabel .=
            " - Situation arrêtée au " .
            date("d/m/Y", strtotime($dateSituation));
    }

    $rows[$row] = [
        xlsxTextCell("BEST COPRO", 1),
        xlsxTextCell("Relevé annuel des cotisations - " . $nameExercice, 1),
        xlsxTextCell($exportDateLabel),
    ];
    $merges[] = "B" . $row . ":" . $lastColumn . $row;
    $row += 2;

    $rows[$row] = [
        xlsxTextCell(
            "NB : Sauf erreur, omission, règlement en cours ou non identifié",
        ),
    ];
    $merges[] = "A" . $row . ":" . $lastColumn . $row;
    $row++;

    foreach ($immeubles as $immeuble) {
        $rows[$row] = [
            xlsxTextCell("IMMEUBLE " . $immeuble["numeroImm"], 2),
        ];
        $merges[] = "A" . $row . ":" . $lastColumn . $row;
        $row++;

        $header = [
            xlsxTextCell("Code", 3),
            xlsxTextCell("Total des impayés antérieurs", 3),
        ];
        foreach ($periods as $period) {
            $header[] = xlsxTextCell($period["label"], 3);
        }
        $header[] = xlsxTextCell("Avance sur cotisation", 3);
        $header[] = xlsxTextCell("Reste à Payer", 3);
        $rows[$row] = $header;
        $row++;

        $totalImpayes = 0;
        $totalCotisations = array_fill(0, $periodCount, 0);
        $totalAvances = 0;
        $totalRestesAPayer = 0;
        $immeubleKey = (string) $immeuble["numeroImm"];
        $lots = isset($exportData["lotsByImmeuble"][$immeubleKey])
            ? $exportData["lotsByImmeuble"][$immeubleKey]
            : [];

        foreach ($lots as $lot) {
            $impayeSummary = getCotisationExportSummary(
                $exportData["previousRelSummaries"],
                $lot["id"],
            );
            $totalPaye = $impayeSummary["totalPaye"];
            $totalImpaye = $impayeSummary["totalImpaye"];
            $currentSummary = getCotisationExportSummary(
                $exportData["currentRelSummaries"],
                $lot["id"],
            );
            $totalPayeCotisation = $currentSummary["totalPaye"];
            $totalImpayeCotisation = $currentSummary["totalImpaye"];

            $cotisation =
                ($totalPayeCotisation + $totalImpayeCotisation) / $periodCount;
            $tmpCotisation = $totalPayeCotisation;
            $totalPaiement = getCotisationExportPaymentTotal(
                $exportData["paymentTotals"],
                $lot["id"],
            );
            $avance = $totalPaiement - $totalPaye - $totalPayeCotisation;
            $avanceAffichee = getCotisationExportDisplayAdvance($avance);
            $resteAPayer = 0;

            $line = [
                xlsxTextCell($lot["code"]),
                xlsxNumberCell($totalImpaye, 4),
            ];
            for ($i = 0; $i < $periodCount; $i++) {
                if ($tmpCotisation >= $cotisation) {
                    $value = $cotisation;
                    $totalCotisations[$i] += $cotisation;
                } elseif ($tmpCotisation > 0) {
                    if ($periodDueFlags[$i]) {
                        $resteAPayer += $cotisation - $tmpCotisation;
                    }
                    $value = $tmpCotisation;
                    $totalCotisations[$i] += $tmpCotisation;
                } else {
                    if ($periodDueFlags[$i]) {
                        $resteAPayer += $cotisation;
                    }
                    $value = "";
                }
                $line[] =
                    $value === "" ? xlsxTextCell("") : xlsxNumberCell($value, 4);
                $tmpCotisation -= $cotisation;
            }
            $resteAPayerAffiche = getCotisationExportDisplayResteAPayer(
                $resteAPayer + $totalImpaye
            );
            $line[] = xlsxNumberCell($avanceAffichee, 4, 0);
            $line[] = xlsxNumberCell($resteAPayerAffiche, 4, 0);
            $rows[$row] = $line;
            $row++;

            $totalImpayes += $totalImpaye;
            $totalAvances += $avanceAffichee;
            $totalRestesAPayer += $resteAPayerAffiche;
        }

        $totalLine = [
            xlsxTextCell("TOTAL", 5),
            xlsxNumberCell($totalImpayes, 5),
        ];
        foreach ($totalCotisations as $totalCotisation) {
            $totalLine[] = xlsxNumberCell($totalCotisation, 5);
        }
        $totalLine[] = xlsxNumberCell($totalAvances, 5, 0);
        $totalLine[] = xlsxNumberCell($totalRestesAPayer, 5, 0);
        $rows[$row] = $totalLine;
        $row += 2;
    }

    return [
        "rows" => $rows,
        "merges" => $merges,
        "columnCount" => $columnCount,
    ];
}

$id_copropriete = $_GET["id_copropriete"];
$id_exercice = $_GET["id_exercice"];
$exercice = getExercice($id_exercice, null, $connection);
$dateSituation = null;
if (
    isset($_GET["date_situation"]) &&
    $_GET["date_situation"] !== "" &&
    strtotime($_GET["date_situation"]) !== false
) {
    $dateSituation = date("Y-m-d", strtotime($_GET["date_situation"]));
}
if (
    count($exercice) > 0 &&
    isset($_GET["id_periodePaiement"]) &&
    in_array($_GET["id_periodePaiement"], ["1", "2", "3", "4"], true)
) {
    $exercice[0]["id_periodePaiement"] = $_GET["id_periodePaiement"];
}
$periods = getCotisationExportPeriodsXlsx($exercice[0]);
$periodCount = count($periods);
$nameExercice = str_replace(
    "Exercice ",
    "",
    getNameexercice($exercice[0]["dateDebut"]),
);
$exportData = getCotisationExportData(
    $id_copropriete,
    $id_exercice,
    $connection,
    $dateSituation
);
$copropriete = getCopropriete($id_copropriete, $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$immeubles = $exportData["immeubles"];
$worksheetData = buildCotisationRows(
    $immeubles,
    $exercice[0],
    $nameExercice,
    $periods,
    $periodCount,
    $exportData,
    $dateSituation
);

$excelContent = renderExcelHtml(
    $worksheetData["rows"],
    $worksheetData["columnCount"],
);

$filename = getCotisationExportFilename(
    "tableau_cotisations",
    $residenceName,
    $nameExercice,
    $dateSituation,
    "xls"
);
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Content-Length: " . strlen($excelContent));
echo $excelContent;
exit();
