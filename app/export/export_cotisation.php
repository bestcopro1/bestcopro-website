<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "../vendor/dompdf/autoload.inc.php";

include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/cotisation_export_data.php";
$connection = $GLOBALS["connection"];

function getImmeuble($id_copropriete, $connection)
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
        } else {
            return [];
        }
    }
}
function getLotByImmeuble($immeuble, $id_copropriete, $connection)
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
        } else {
            return [];
        }
    }
}

function getCotisationExportPeriods($exercice)
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
            $label = "T" . ($i + 1) . "<br>De " . $start . " à " . $end;
        } elseif ($periodePaiement == "3") {
            $label = "S" . ($i + 1) . "<br>De " . $start . " à " . $end;
        } else {
            $label = "De " . $start . " à " . $end;
        }

        $periods[] = [
            "label" => $label,
            "startOffset" => $startOffset,
        ];
    }

    return $periods;
}

function renderCotisationExportTableHeader($nameExercice, $cotisationPeriods)
{
    $htmlContent = '<table style="width:100%;font-size: 10px;border-collapse: collapse;table-layout: fixed;">';
    $htmlContent .= "<tr>";
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 105px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">Code</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 82px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">Années</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 68px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">État des impayés</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 68px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">Encaissements</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 62px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">Reste dû</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000;text-align: center;background-color: #d9eaf7;font-weight: bold;padding: 3px;" colspan="' .
        count($cotisationPeriods) .
        '">Détails des cotisations - ' .
        $nameExercice .
        "</td>";
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 50px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">Avance</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000; width: 58px;text-align: center;background-color: #d9eaf7;font-weight: bold;" rowspan="2">Reste à Payer</td>';
    $htmlContent .= "</tr>";
    $htmlContent .= "<tr>";
    foreach ($cotisationPeriods as $period) {
        $htmlContent .=
            '<td style="border: 1px solid #000; width: 44px;text-align: center;background-color: #d9eaf7;font-weight: bold;">' .
            $period["label"] .
            "</td>";
    }
    $htmlContent .= "</tr>";

    return $htmlContent;
}

// reference the Dompdf namespace
use Dompdf\Dompdf;

// instantiate and use the dompdf class
$dompdf = new Dompdf();

$exercice = getExercice($_GET["id_exercice"], null, $connection);
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
$cotisationPeriods = getCotisationExportPeriods($exercice[0]);
$cotisationPeriodCount = count($cotisationPeriods);
$nameExercice = str_replace(
    "Exercice ",
    "",
    getNameexercice($exercice[0]["dateDebut"]),
);
$exportData = getCotisationExportData(
    $_GET["id_copropriete"],
    $_GET["id_exercice"],
    $connection,
    $dateSituation
);
$copropriete = getCopropriete($_GET["id_copropriete"], $connection);
$residenceName = count($copropriete) > 0 ? $copropriete[0]["nom"] : "";
$immeubles = $exportData["immeubles"];
$periodDueFlags = getCotisationExportPeriodDueFlags(
    $exercice[0]["dateDebut"],
    $cotisationPeriods,
    $dateSituation
);

$htmlContent = "";
$htmlContent .=
    "<style> @page { margin: 8px 10px 0 10px; } * { font-family: DejaVu Sans, sans-serif; } body { margin: 0; } span, p {font-size: 10px;} table td { padding: 2px; line-height: 1.25; }</style>";
$immeubleIndex = 0;
$immeubleCount = count($immeubles);
foreach ($immeubles as $immeuble):
    $immeubleKey = (string) $immeuble["numeroImm"];
    $lotsbyimmeuble = isset($exportData["lotsByImmeuble"][$immeubleKey])
        ? $exportData["lotsByImmeuble"][$immeubleKey]
        : [];

    $htmlContent .= '<table style="width: 100%;">';
    $htmlContent .= "<tr>";
    $htmlContent .= '<td style="vertical-align:top;">';
    $htmlContent .=
        '<img src="data:image/png;base64,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" alt="logo">';
    $htmlContent .= "</td>";
    $htmlContent .= '<td style="text-align:right;vertical-align:top;">';
    $htmlContent .=
        '<div style="font-weight:bold;">' .
        htmlspecialchars($residenceName, ENT_QUOTES, "UTF-8") .
        "</div>";
    $htmlContent .= "<span>" . date("d/m/Y") . "</span>";
    if ($dateSituation !== null) {
        $htmlContent .=
            '<br><span>Situation au ' .
            date("d/m/Y", strtotime($dateSituation)) .
            "</span>";
    }
    $htmlContent .= "</td>";
    $htmlContent .= "</tr>";
    $htmlContent .= "<tr>";
    $htmlContent .=
        '<td colspan="2" style="text-align:center;padding-top: 8px;"><strong>Relevé annuel des cotisations - ' .
        $nameExercice .
        "</strong></td>";
    $htmlContent .= "</tr>";
    $htmlContent .= "</table>";
    $htmlContent .=
        '<p style="margin-top: 8px;margin-bottom: 4px;">NB : Sauf erreur, omission, règlement en cours ou non identifié</p>';
    $htmlContent .=
        '<div style="font-size: 12px;border: 1px solid #000;background-color: #ffa755;text-align: center;padding: 3px;margin-bottom: 4px;">';
    $htmlContent .= "<strong>IMMEUBLE " . $immeuble["numeroImm"] . "</strong>";
    $htmlContent .= "</div>";

    $htmlContent .= renderCotisationExportTableHeader($nameExercice, $cotisationPeriods);
    $totalEtatsImpayes = 0;
    $totalEncaissementsImpayes = 0;
    $totalRestesDusImpayes = 0;
    $totalCotisations = array_fill(0, $cotisationPeriodCount, 0);
    $totalAvances = 0;
    $totalRestesAPayer = 0;
    $line = 1;
    foreach ($lotsbyimmeuble as $lotbyimmeuble):
        if ($line % 25 == 0):
            $htmlContent .= "</table>";
            $htmlContent .= '<div style="page-break-after: always;"></div>';
            $htmlContent .= '<table style="width: 100%;">';
            $htmlContent .= "<tr>";
            $htmlContent .= '<td style="vertical-align:top;">';
            $htmlContent .=
                '<img src="data:image/png;base64,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" alt="logo">';
            $htmlContent .= "</td>";
            $htmlContent .= '<td style="text-align:right;vertical-align:top;">';
            $htmlContent .=
                '<div style="font-weight:bold;">' .
                htmlspecialchars($residenceName, ENT_QUOTES, "UTF-8") .
                "</div>";
            $htmlContent .= "<span>" . date("d/m/Y") . "</span>";
            if ($dateSituation !== null) {
                $htmlContent .=
                    '<br><span>Situation au ' .
                    date("d/m/Y", strtotime($dateSituation)) .
                    "</span>";
            }
            $htmlContent .= "</td>";
            $htmlContent .= "</tr>";
            $htmlContent .= "<tr>";
            $htmlContent .=
                '<td colspan="2" style="text-align:center;padding-top: 8px;"><strong>Relevé annuel des cotisations - ' .
                $nameExercice .
                "</strong></td>";
            $htmlContent .= "</tr>";
            $htmlContent .= "</table>";
            $htmlContent .=
                '<p style="margin-top: 8px;margin-bottom: 4px;">NB : Sauf erreur, omission, règlement en cours ou non identifié</p>';
            $htmlContent .=
                '<div style="font-size: 12px;border: 1px solid #000;background-color: #ffa755;text-align: center;padding: 3px;margin-bottom: 4px;">';
            $htmlContent .=
                "<strong>IMMEUBLE " . $immeuble["numeroImm"] . "</strong>";
            $htmlContent .= "</div>";

            $htmlContent .= renderCotisationExportTableHeader($nameExercice, $cotisationPeriods);
        endif;
        $line += 1;
        $previousDebtDetail = getCotisationExportPreviousDebtDetail(
            $exportData["previousDebtDetails"],
            $lotbyimmeuble["id"]
        );
        $anneesImpayes =
            count($previousDebtDetail["labels"]) > 0
                ? implode("<br>", $previousDebtDetail["labels"])
                : "";
        $etatImpaye = $previousDebtDetail["etatImpaye"];
        $encaissementImpaye = $previousDebtDetail["encaissement"];
        $resteDuImpaye = $previousDebtDetail["resteDu"];
        $currentSummary = getCotisationExportSummary(
            $exportData["currentRelSummaries"],
            $lotbyimmeuble["id"]
        );
        $totalPayeCotisation = $currentSummary["totalPaye"];
        $totalImpayeCotisation = $currentSummary["totalImpaye"];
        $cotisation =
            ($totalPayeCotisation + $totalImpayeCotisation) /
            $cotisationPeriodCount;
        $tmpCotisation = $totalPayeCotisation;
        $totalPaiement = getCotisationExportPaymentTotal(
            $exportData["paymentTotals"],
            $lotbyimmeuble["id"]
        );
        $avance = $totalPaiement - $encaissementImpaye - $totalPayeCotisation;
        $htmlContent .= "<tr>";
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            $lotbyimmeuble["code"] .
            "</td>";
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            $anneesImpayes .
            "</td>";
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            number_format($etatImpaye, 2) .
            "</td>";
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            number_format($encaissementImpaye, 2) .
            "</td>";
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            number_format($resteDuImpaye, 2) .
            "</td>";
        $resteAPayer = 0;
        for ($i = 0; $i < $cotisationPeriodCount; $i++):
            if ($tmpCotisation >= $cotisation):
                //if (intval(date("m")) < $i)
                //	$avance += $cotisation;
                $htmlContent .=
                    '<td style="border: 1px solid #000;text-align: center;">' .
                    number_format($cotisation, 2) .
                    "</td>";
                $totalCotisations[$i] += $cotisation;
                //else
                //	$avance += $tmpCotisation;
            elseif ($tmpCotisation > 0):
                if ($periodDueFlags[$i]) {
                    $resteAPayer += $cotisation - $tmpCotisation;
                }
                $htmlContent .=
                    '<td style="border: 1px solid #000;text-align: center;">' .
                    number_format($tmpCotisation, 2) .
                    "</td>";
                $totalCotisations[$i] += $tmpCotisation;
            else:
                if ($periodDueFlags[$i]):
                    $resteAPayer += $cotisation;
                    $htmlContent .=
                        '<td style="border: 1px solid #000;text-align: center;background-color: #ffe9d5;"></td>';
                else:
                    $htmlContent .=
                        '<td style="border: 1px solid #000;text-align: center;"></td>';
                endif;
            endif;
            $tmpCotisation -= $cotisation;
        endfor;
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            number_format($avance, 2) .
            "</td>";
        $htmlContent .=
            '<td style="border: 1px solid #000;text-align: center;">' .
            number_format($resteAPayer + $resteDuImpaye, 2) .
            "</td>";
        $htmlContent .= "</tr>";
        $totalEtatsImpayes += $etatImpaye;
        $totalEncaissementsImpayes += $encaissementImpaye;
        $totalRestesDusImpayes += $resteDuImpaye;
        $totalAvances += $avance;
        $totalRestesAPayer += $resteAPayer + $resteDuImpaye;
    endforeach;
    $htmlContent .= "<tr>";
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;padding: 3px;text-align: center; ">TOTAL</td>';
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
        "" .
        "</td>";
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
        number_format($totalEtatsImpayes, 2) .
        "</td>";
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
        number_format($totalEncaissementsImpayes, 2) .
        "</td>";
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
        number_format($totalRestesDusImpayes, 2) .
        "</td>";
    foreach ($totalCotisations as $totalCotisation) {
        $htmlContent .=
            '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
            number_format($totalCotisation, 2) .
            "</td>";
    }
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
        number_format($totalAvances, 2) .
        "</td>";
    $htmlContent .=
        '<td style="border: 1px solid #000;background-color: #d9eaf7;font-weight: bold;text-align: center;">' .
        number_format($totalRestesAPayer, 2) .
        "</td>";
    $htmlContent .= "</tr>";
    $htmlContent .= "</table>";
    if ($immeubleIndex < $immeubleCount - 1) {
        $htmlContent .= '<div style="page-break-after: always;"></div>';
    }
    $immeubleIndex++;
endforeach;

//echo $htmlContent;
//exit();

$dompdf->loadHtml($htmlContent);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper("A4", "landscape");

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream();
