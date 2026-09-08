<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";
include_once __DIR__ . "/../controllers/document_storage.php";

$token = input_value("token");
$lotRow = mobile_token_lot($connection, $token);

if (!$lotRow) {
    mobile_error("Session invalide ou copropriété inactive.");
}

$lot = getLot($lotRow["id"], null, null, $connection);
if (!$lot) {
    mobile_error("Lot introuvable.");
}

$lot = $lot[0];
$proprietaire = getProprietaire($lot["id_proprietaire"], null, $connection);
$copropriete = getCopropriete($lot["id_copropriete"], $connection);
$exercice = getExercice(null, $copropriete[0]["id"], $connection);

if (!$proprietaire || !$copropriete || !$exercice) {
    mobile_error("Données de copropriété incomplètes.");
}

$proprietaire = $proprietaire[0];
$copropriete = $copropriete[0];
$exercice = $exercice[0];
$typeLot = getTypelot($lot["id_typeLot"], $connection);
$typeProprietaire = getTypeproprietaire($lot["id_typeProprietaire"], $connection);
$formatDate = static function ($value): string {
    if (!$value || $value === "0000-00-00" || strtotime((string) $value) === false) {
        return "N/A";
    }
    return date("d/m/Y", strtotime((string) $value));
};
$documents = getDocument(null, $lot["id_copropriete"], 1, $connection);
$relAll = getRel_lot_exercice($lot["id"], null, $connection);
$relCurrent = getRel_lot_exercice($lot["id"], $exercice["id"], $connection);
$paiements = getPaiement(null, null, $lot["id"], $connection);

$debit = 0.0;
foreach ($relCurrent as $periode) {
    $debit += (float) $periode["partFonct"] + (float) $periode["partInv"];
}

$creditCurrent = 0.0;
$stmt = $connection->prepare(
    "SELECT COALESCE(SUM(rrp.montant), 0) AS total
     FROM rel_rel_paiement rrp
     INNER JOIN rel_lot_exercice rle ON rle.id_rel = rrp.id_rel
     WHERE rle.id_lot = ? AND rle.id_exercice = ?"
);
if ($stmt) {
    $stmt->bind_param("ss", $lot["id"], $exercice["id"]);
    $stmt->execute();
    $stmt->bind_result($totalCredit);
    if ($stmt->fetch()) {
        $creditCurrent = (float) $totalCredit;
    }
    $stmt->close();
}

if ($creditCurrent <= 0) {
    foreach ($relCurrent as $periode) {
        $creditCurrent += (float) $periode["cotisation"];
    }
}

$nbrMonth = 1;
if ($exercice["id_periodePaiement"] === "2") {
    $nbrMonth = 3;
} elseif ($exercice["id_periodePaiement"] === "3") {
    $nbrMonth = 6;
} elseif ($exercice["id_periodePaiement"] === "4") {
    $nbrMonth = 12;
}
$today = date("Y-m-d");
$periodMonths = static function ($periodType): int {
    if ((string) $periodType === "2") {
        return 3;
    }
    if ((string) $periodType === "3") {
        return 6;
    }
    if ((string) $periodType === "4") {
        return 12;
    }
    return 1;
};
$shortPeriodLabel = static function (string $start, string $end, int $months): string {
    if ($months === 1) {
        return date("m/y", strtotime($start));
    }
    return date("m/y", strtotime($start)) . " - " . date("m/y", strtotime($end));
};

$solde = 0.0;
$impayes = [];
$exerciseCache = [];
foreach ($relAll as $periode) {
    $due = (float) $periode["partFonct"] + (float) $periode["partInv"];
    $paid = (float) $periode["cotisation"];
    $periodExerciseId = (int) $periode["id_exercice"];
    if ($periodExerciseId !== (int) $exercice["id"] && $paid < $due) {
        $missing = $due - $paid;
        $periodEndBoundary = strtotime($periode["dateFinPeriode"]);
        $historicalMonths = 12;
        $exerciseLabel = "Impayé antérieur";

        if ($periodExerciseId > 0) {
            if (!array_key_exists($periodExerciseId, $exerciseCache)) {
                $exerciseRows = getExercice($periodExerciseId, null, $connection);
                $exerciseCache[$periodExerciseId] = $exerciseRows[0] ?? null;
            }
            $historicalExercise = $exerciseCache[$periodExerciseId];
            if (!$historicalExercise || strtotime($historicalExercise["dateFin"]) >= strtotime($exercice["dateDebut"])) {
                continue;
            }
            $historicalMonths = $periodMonths($historicalExercise["id_periodePaiement"]);
            $exerciseLabel = "Exercice " . getNameexercice($historicalExercise["dateDebut"]);
        } elseif ($periodExerciseId === 0) {
            $exerciseLabel = "Impayé promoteur";
        } else {
            $exerciseLabel = "Cumul des impayés N" . $periodExerciseId;
        }

        $periodStart = date("Y-m-d", strtotime("-" . $historicalMonths . " months", $periodEndBoundary));
        $periodEnd = date("Y-m-d", strtotime("-1 day", $periodEndBoundary));
        $periodLabel = $shortPeriodLabel($periodStart, $periodEnd, $historicalMonths);
        $solde += $missing;
        $impayes[] = [
            "id_rel" => $periode["id_rel"],
            "date" => $periodLabel,
            "periodeLabel" => $periodLabel,
            "exerciceLabel" => $exerciseLabel,
            "dateDebut" => $periodStart,
            "dateFin" => $periodEnd,
            "montant_attendu" => mobile_money($due),
            "montant_paye" => mobile_money($paid),
            "reste" => mobile_money($missing),
            "statut" => $paid > 0 ? "partiel" : "nonpaye",
        ];
    }
}

$totalPayeChecker = 0.0;
$totalImpayeChecker = 0.0;
foreach ($relCurrent as $periode) {
    $periodStartLimit = strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - " . $nbrMonth . " month");
    if (strtotime($today) < $periodStartLimit) {
        break;
    }
    $totalImpayeChecker += (float) $periode["partFonct"] + (float) $periode["partInv"];
    $totalPayeChecker += (float) $periode["cotisation"];
}
$solde += $totalImpayeChecker - $totalPayeChecker;

$situation = [];
$trimestre = 1;
$semestre = 1;
$lastDuePeriodEnd = null;
foreach ($relCurrent as $periode) {
    $periodEndBoundary = strtotime($periode["dateFinPeriode"]);
    $periodStart = date("Y-m-d", strtotime("-" . $nbrMonth . " months", $periodEndBoundary));
    $periodEnd = date("Y-m-d", strtotime("-1 day", $periodEndBoundary));
    $isDue = $today >= $periodStart;
    if ($isDue) {
        $lastDuePeriodEnd = $periodEnd;
    }
    // Keep the same headings as the annual contribution statement: each
    // response carries a concrete month or date range, never a vague label.
    $monthYear = $shortPeriodLabel($periodStart, $periodEnd, $nbrMonth);

    $due = (float) $periode["partFonct"] + (float) $periode["partInv"];
    $paid = (float) $periode["cotisation"];
    $situation[] = [
        "id_rel" => $periode["id_rel"],
        "date" => $monthYear,
        "periodeLabel" => $monthYear,
        "dateDebut" => $periodStart,
        "dateFin" => $periodEnd,
        "exigible" => $isDue,
        "cotisation" => mobile_money($paid),
        "montant_attendu" => mobile_money($due),
        "reste" => mobile_money(max(0, $due - $paid)),
        "statut" => $paid >= $due ? "paye" : ($isDue ? "nonpaye" : "nonechue"),
    ];
}

$paiementsData = [];
foreach ($paiements as $paiement) {
    $paiementsData[] = [
        "id" => $paiement["id"],
        "reference" => $paiement["id"],
        "date" => date("d/m/Y", strtotime($paiement["date"])),
        "cotisation" => mobile_money((float) $paiement["montant"]),
        "montant" => (float) $paiement["montant"],
    ];
}

$documentsData = [];
foreach ($documents as $document) {
    $typedocument = getTypedocument($document["id_typedocument"], $connection);
    $preuves = bestcoproDocumentFiles($document["id"]);
    $documentsData[] = [
        "titre" => $document["titre"],
        "date" => date("d/m/Y", strtotime($document["date"])),
        "id" => $document["id"],
        "type" => $typedocument[0]["libelle"] ?? "",
        "lien" => count($preuves) > 0 ? bestcoproDocumentPublicUrl($preuves[0]) : "#",
    ];
}

$data = [
    "civilite" => $proprietaire["civilite"],
    "nom" => $proprietaire["nom"],
    "prenom" => $proprietaire["prenom"],
    "telephone" => $proprietaire["telephone"],
    "email" => $proprietaire["email"],
    "adresse" => $proprietaire["adresse"],
    "mobile" => $proprietaire["mobile"],
    "LotId" => $lot["id"],
    "code" => $lot["code"],
    "Copropriete" => $copropriete["nom"],
    "TypeLot" => $typeLot[0]["libelle"] ?? "N/A",
    "NumeroImmeuble" => $lot["numeroImm"] ?: "N/A",
    "Etage" => $lot["etage"] !== null && $lot["etage"] !== "" ? $lot["etage"] : "N/A",
    "Numero" => $lot["numero"],
    "Tantieme" => (float) $lot["tantieme"],
    "Titrefonciere" => $lot["foncier"],
    "Proprietaire" => trim($proprietaire["civilite"] . " " . $proprietaire["prenom"] . " " . $proprietaire["nom"]),
    "TypeProprietaire" => $typeProprietaire[0]["libelle"] ?? "N/A",
    "DateAcquisition" => $formatDate($lot["dateAcquisition"]),
    "DateRemiseCle" => $formatDate($lot["dateRemiseCle"]),
    "Debit" => mobile_money($debit),
    "Credit" => mobile_money($creditCurrent),
    "CreVotCom" => mobile_money($solde),
    "Exercice" => getNameexercice($exercice["dateDebut"]),
    "RIB" => $copropriete["rib"],
    "RIBResidence" => $copropriete["rib"] ?: "N/A",
    "SituationArreteeAu" => $lastDuePeriodEnd ? $formatDate($lastDuePeriodEnd) : "N/A",
    "impayes" => $impayes,
    "situation" => $situation,
    "Paiements" => $paiementsData,
    "documents" => $documentsData,
];

mobile_response(true, $data);
