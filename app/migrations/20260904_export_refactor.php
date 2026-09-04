<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Cette migration doit être exécutée depuis le terminal.\n");
}

include_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../export/export_calculations.php";
$connection = $GLOBALS["connection"];
$apply = in_array("--apply", isset($argv) ? $argv : [], true);

function migrationOut($message)
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function migrationColumns($connection, $table)
{
    $columns = [];
    $result = $connection->query("SHOW COLUMNS FROM `" . $table . "`");
    if (!$result) {
        throw new RuntimeException($connection->error);
    }
    while ($row = $result->fetch_assoc()) {
        $columns[$row["Field"]] = $row;
    }
    $result->free();
    return $columns;
}

function migrationPrepareSchema($connection, $apply)
{
    $exerciceColumns = migrationColumns($connection, "exercice");
    $depenseColumns = migrationColumns($connection, "depense");
    $changes = [];

    $exerciseDefinitions = [
        "cloture" => "TINYINT(1) NOT NULL DEFAULT 0",
        "dateCloture" => "DATETIME NULL",
        "id_cloture_par" => "INT(11) NULL",
    ];
    foreach ($exerciseDefinitions as $column => $definition) {
        if (!isset($exerciceColumns[$column])) {
            $changes[] = "exercice." . $column;
            if ($apply && !$connection->query("ALTER TABLE exercice ADD `" . $column . "` " . $definition)) {
                throw new RuntimeException($connection->error);
            }
        }
    }

    $depenseDefinitions = [
        "situationPaiement" => "VARCHAR(20) NOT NULL DEFAULT 'paye'",
        "datePaiement" => "DATE NULL",
        "montantPaye" => "DECIMAL(12,2) NULL",
    ];
    foreach ($depenseDefinitions as $column => $definition) {
        if (!isset($depenseColumns[$column])) {
            $changes[] = "depense." . $column;
            if ($apply && !$connection->query("ALTER TABLE depense ADD `" . $column . "` " . $definition)) {
                throw new RuntimeException($connection->error);
            }
        }
    }

    if (
        isset($depenseColumns["id_modePaiement"]) &&
        strtoupper($depenseColumns["id_modePaiement"]["Null"]) === "NO"
    ) {
        $changes[] = "depense.id_modePaiement nullable";
        if (
            $apply &&
            !$connection->query(
                "ALTER TABLE depense MODIFY id_modePaiement " .
                    $depenseColumns["id_modePaiement"]["Type"] .
                    " NULL DEFAULT NULL"
            )
        ) {
            throw new RuntimeException($connection->error);
        }
    }

    if ($apply) {
        if (
            !$connection->query(
                "CREATE TABLE IF NOT EXISTS typesyndic_permission (
                    id_typeSyndic INT NOT NULL,
                    permission_code VARCHAR(80) NOT NULL,
                    autorise TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (id_typeSyndic, permission_code),
                    INDEX idx_permission_code (permission_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            )
        ) {
            throw new RuntimeException($connection->error);
        }
        $backfills = [
            "UPDATE depense SET situationPaiement = 'paye' WHERE situationPaiement IS NULL OR situationPaiement = ''",
            "UPDATE depense SET datePaiement = date WHERE situationPaiement = 'paye' AND datePaiement IS NULL",
            "UPDATE depense SET montantPaye = montant WHERE situationPaiement = 'paye' AND montantPaye IS NULL",
        ];
        foreach ($backfills as $backfill) {
            if (!$connection->query($backfill)) {
                throw new RuntimeException($connection->error);
            }
        }
    }

    return $changes;
}

function migrationFetchLots($connection)
{
    $lots = [];
    $result = $connection->query(
        "SELECT DISTINCT id_lot FROM paiement
         UNION
         SELECT DISTINCT id_lot FROM rel_lot_exercice WHERE COALESCE(cotisation, 0) > 0
         ORDER BY id_lot"
    );
    if (!$result) {
        throw new RuntimeException($connection->error);
    }
    while ($row = $result->fetch_assoc()) {
        $lots[] = (int) $row["id_lot"];
    }
    $result->free();
    return $lots;
}

function migrationFetchPayments($connection, $idLot)
{
    $rows = [];
    $stmt = $connection->prepare(
        "SELECT p.id, p.montant, COALESCE(SUM(rrp.montant), 0) AS allocated
         FROM paiement p
         LEFT JOIN rel_rel_paiement rrp ON rrp.id_paiement = p.id
         WHERE p.id_lot = ?
         GROUP BY p.id, p.montant, p.date
         ORDER BY CAST(p.date AS DATE), p.id"
    );
    if (!$stmt) {
        throw new RuntimeException($connection->error);
    }
    $stmt->bind_param("i", $idLot);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    $stmt->bind_result($id, $amount, $allocated);
    while ($stmt->fetch()) {
        $rows[] = [
            "id" => (int) $id,
            "remaining" => max(0, (float) $amount - (float) $allocated),
        ];
    }
    $stmt->close();
    return $rows;
}

function migrationFetchPeriods($connection, $idLot)
{
    $rows = [];
    $stmt = $connection->prepare(
        "SELECT r.id_rel, LEAST(
            GREATEST(COALESCE(r.cotisation, 0), 0),
            GREATEST(COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0), 0)
         ) AS target_paid,
         COALESCE(SUM(rrp.montant), 0) AS linked_paid
         FROM rel_lot_exercice r
         LEFT JOIN rel_rel_paiement rrp ON rrp.id_rel = r.id_rel
         WHERE r.id_lot = ?
         GROUP BY r.id_rel, r.cotisation, r.partFonct, r.partInv, r.dateFinPeriode, r.id_exercice
         HAVING target_paid > linked_paid + 0.009
         ORDER BY r.dateFinPeriode, r.id_exercice, r.id_rel"
    );
    if (!$stmt) {
        throw new RuntimeException($connection->error);
    }
    $stmt->bind_param("i", $idLot);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    $stmt->bind_result($idRel, $targetPaid, $linkedPaid);
    while ($stmt->fetch()) {
        $rows[] = [
            "id_rel" => (int) $idRel,
            "missing" => max(0, (float) $targetPaid - (float) $linkedPaid),
        ];
    }
    $stmt->close();
    return $rows;
}

function migrationReconcileAllocations($connection, $apply)
{
    $insertedRows = 0;
    $insertedAmount = 0.0;
    $unresolvedAmount = 0.0;
    $insert = null;
    if ($apply) {
        $insert = $connection->prepare(
            "INSERT INTO rel_rel_paiement (id_rel, id_paiement, montant)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE montant = montant + VALUES(montant)"
        );
        if (!$insert) {
            throw new RuntimeException($connection->error);
        }
    }

    foreach (migrationFetchLots($connection) as $idLot) {
        $payments = migrationFetchPayments($connection, $idLot);
        $periods = migrationFetchPeriods($connection, $idLot);
        $plan = bestcopro_export_build_reconciliation_plan($payments, $periods);
        foreach ($plan["allocations"] as $allocation) {
            if ($apply) {
                $idRel = $allocation["id_rel"];
                $idPaiement = $allocation["id_paiement"];
                $amount = $allocation["amount"];
                $insert->bind_param("iid", $idRel, $idPaiement, $amount);
                if (!$insert->execute()) {
                    throw new RuntimeException($insert->error);
                }
            }
            $insertedRows++;
            $insertedAmount += $allocation["amount"];
        }
        $unresolvedAmount += $plan["unresolved"];
    }

    if ($insert) {
        $insert->close();
    }
    return [
        "rows" => $insertedRows,
        "amount" => $insertedAmount,
        "unresolved" => $unresolvedAmount,
    ];
}

$transactionStarted = false;
try {
    migrationOut($apply ? "MODE APPLICATION" : "MODE SIMULATION - aucune donnée ne sera modifiée");
    $schemaChanges = migrationPrepareSchema($connection, $apply);
    migrationOut(
        empty($schemaChanges)
            ? "Schéma : déjà à jour"
            : "Schéma : " . implode(", ", $schemaChanges)
    );

    if ($apply) {
        $connection->begin_transaction();
        $transactionStarted = true;
    }
    $stats = migrationReconcileAllocations($connection, $apply);
    if ($apply) {
        $connection->commit();
        $transactionStarted = false;
    }

    migrationOut("Affectations à créer : " . $stats["rows"]);
    migrationOut("Montant à réconcilier : " . number_format($stats["amount"], 2, ".", "") . " MAD");
    migrationOut("Montant non réconciliable : " . number_format($stats["unresolved"], 2, ".", "") . " MAD");
    if (!$apply) {
        migrationOut("Relancer avec --apply après sauvegarde et validation de cette simulation.");
    }
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $connection->rollback();
    }
    fwrite(STDERR, "Migration échouée : " . $exception->getMessage() . PHP_EOL);
    exit(1);
}
