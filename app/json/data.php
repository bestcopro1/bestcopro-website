<?php
declare(strict_types=1);

include_once __DIR__ . "/_mobile.php";

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

$solde = 0.0;
$impayes = [];
foreach ($relAll as $periode) {
    $due = (float) $periode["partFonct"] + (float) $periode["partInv"];
    $paid = (float) $periode["cotisation"];
    if ((int) $periode["id_exercice"] < 0 && $paid < $due) {
        $missing = $due - $paid;
        $solde += $missing;
        $year = date("Y", strtotime($periode["dateFinPeriode"]));
        $impayes[] = [
            "date" => "Impayé de l'année : " . $year,
            "cotisation" => mobile_money($missing),
            "statut" => "nonpaye",
        ];
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

$totalPayeChecker = 0.0;
$totalImpayeChecker = 0.0;
foreach ($relCurrent as $periode) {
    $periodStartLimit = strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - " . $nbrMonth . " month");
    if (strtotime(date("Y-m-d")) <= $periodStartLimit) {
        break;
    }
    $totalImpayeChecker += (float) $periode["partFonct"] + (float) $periode["partInv"];
    $totalPayeChecker += (float) $periode["cotisation"];
}
$solde += $totalImpayeChecker - $totalPayeChecker;

$situation = [];
$trimestre = 1;
$semestre = 1;
foreach ($relCurrent as $periode) {
    if ($exercice["id_periodePaiement"] === "1") {
        $monthYear = "Cotisations du mois : " . date("m/Y", strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 month"));
    } elseif ($exercice["id_periodePaiement"] === "2") {
        $monthYear = "Cotisations du trimestre : T" . $trimestre++;
    } elseif ($exercice["id_periodePaiement"] === "3") {
        $monthYear = "Cotisations du semestre : S" . $semestre++;
    } else {
        $monthYear = "Cotisations de l'année : " . date("Y", strtotime(date("Y-m-d", strtotime($periode["dateFinPeriode"])) . " - 1 year"));
    }

    $due = (float) $periode["partFonct"] + (float) $periode["partInv"];
    $paid = (float) $periode["cotisation"];
    $situation[] = [
        "date" => $monthYear,
        "cotisation" => mobile_money($paid),
        "montant_attendu" => mobile_money($due),
        "reste" => mobile_money(max(0, $due - $paid)),
        "statut" => $paid >= $due ? "paye" : "nonpaye",
    ];
}

$paiementsData = [];
foreach ($paiements as $paiement) {
    $paiementsData[] = [
        "id" => $paiement["id"],
        "designation" => $paiement["commentaire"] ?: "Paiement",
        "date" => date("d/m/Y", strtotime($paiement["date"])),
        "cotisation" => mobile_money((float) $paiement["montant"]),
        "montant" => (float) $paiement["montant"],
    ];
}

$documentsData = [];
foreach ($documents as $document) {
    $typedocument = getTypedocument($document["id_typedocument"], $connection);
    $preuves = glob(__DIR__ . "/../justificatifs/documents/" . $document["id"] . ".*");
    $documentsData[] = [
        "titre" => $document["titre"],
        "date" => date("d/m/Y", strtotime($document["date"])),
        "id" => $document["id"],
        "type" => $typedocument[0]["libelle"] ?? "",
        "lien" => count($preuves) > 0 ? mobile_public_url("justificatifs/documents/" . basename($preuves[0])) : "#",
    ];
}

$data = [
    "civilite" => $proprietaire["civilite"],
    "nom" => $proprietaire["nom"],
    "prenom" => $proprietaire["prenom"],
    "telephone" => $proprietaire["telephone"],
    "email" => $proprietaire["email"],
    "adresse" => $proprietaire["adresse"],
    "code" => $lot["code"],
    "Copropriete" => $copropriete["nom"],
    "Numero" => $lot["numero"],
    "Tantieme" => (float) $lot["tantieme"],
    "Titrefonciere" => $lot["foncier"],
    "Debit" => mobile_money($debit),
    "Credit" => mobile_money($creditCurrent),
    "CreVotCom" => mobile_money($solde),
    "Exercice" => getNameexercice($exercice["dateDebut"]),
    "RIB" => $copropriete["rib"],
    "impayes" => $impayes,
    "situation" => $situation,
    "Paiements" => $paiementsData,
    "documents" => $documentsData,
];

mobile_response(true, $data);
