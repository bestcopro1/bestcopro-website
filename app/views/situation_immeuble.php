<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];

function formatSituationImmeubleAmount($value)
{
    return number_format((float) $value, 2, ",", " ");
}

function formatSituationImmeublePercent($value)
{
    return number_format((float) $value, 2, ",", " ") . " %";
}

function getSituationImmeubleRows($id_copropriete, $id_exercice, $isCurrent, $connection)
{
    $rows = [];
    $exerciseCondition = $isCurrent ? "r.id_exercice = ?" : "r.id_exercice <= 0";
    $request =
        "SELECT l.numeroImm, " .
        "SUM(COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0)) AS base_total, " .
        "SUM(COALESCE(r.cotisation, 0)) AS encaissement_total, " .
        "SUM(CASE WHEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) > COALESCE(r.cotisation, 0) " .
        "THEN COALESCE(r.partFonct, 0) + COALESCE(r.partInv, 0) - COALESCE(r.cotisation, 0) ELSE 0 END) AS reste_total " .
        "FROM lot l INNER JOIN rel_lot_exercice r ON r.id_lot = l.id " .
        "WHERE l.id_copropriete = ? AND " .
        $exerciseCondition .
        " GROUP BY l.numeroImm ORDER BY l.numeroImm ASC";

    if ($stmt = $connection->prepare($request)) {
        if ($isCurrent) {
            $stmt->bind_param("ss", $id_copropriete, $id_exercice);
        } else {
            $stmt->bind_param("s", $id_copropriete);
        }

        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($numeroImm, $baseTotal, $encaissementTotal, $resteTotal);

        while ($stmt->fetch()) {
            $baseTotal = (float) $baseTotal;
            $resteTotal = (float) $resteTotal;
            $rows[] = [
                "immeuble" => $numeroImm,
                "baseTotal" => $baseTotal,
                "encaissementTotal" => (float) $encaissementTotal,
                "resteTotal" => $resteTotal,
                "restePercent" => $baseTotal > 0 ? ($resteTotal * 100) / $baseTotal : 0,
            ];
        }
    }

    return $rows;
}

$exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
$situationAnterieureRows = getSituationImmeubleRows(
    $GLOBALS["id_copropriete"],
    $GLOBALS["id_exercice"],
    false,
    $connection
);
$situationActuelleRows = getSituationImmeubleRows(
    $GLOBALS["id_copropriete"],
    $GLOBALS["id_exercice"],
    true,
    $connection
);
?>
		<div class="content-body">
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Situation par immeuble</h2>
					</div>
					<div class="text-end d-none d-lg-block me-3">
						<p class="mb-0 fw-bold"><?= htmlspecialchars($GLOBALS["copropriete"][0]["nom"]) ?></p>
					</div>
				</div>
				<?php if (count($exercice) > 0): ?>
				<div class="alert alert-primary alert-alt fade show p-3 mb-4">
					<strong>Situation par immeuble de l'<?= getNameexercice($exercice[0]["dateDebut"]) ?></strong>
				</div>
				<?php endif; ?>
				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<h4 class="card-title">Situation antérieur</h4>
							</div>
							<div class="card-body">
								<div class="table-responsive">
									<table class="table table-bordered table-striped">
										<thead>
											<tr>
												<th rowspan="2">Immeuble</th>
												<th rowspan="2">Total impayés antérieur</th>
												<th rowspan="2">Encaissement</th>
												<th colspan="2" class="text-center">Reste du</th>
											</tr>
											<tr>
												<th>Montant total en chiffre</th>
												<th>Montant total en pourcentage</th>
											</tr>
										</thead>
										<tbody>
											<?php
           $anterieurTotals = [
               "baseTotal" => 0,
               "encaissementTotal" => 0,
               "resteTotal" => 0,
           ];
           if (count($situationAnterieureRows) > 0):
               foreach ($situationAnterieureRows as $row):
                   $anterieurTotals["baseTotal"] += $row["baseTotal"];
                   $anterieurTotals["encaissementTotal"] += $row["encaissementTotal"];
                   $anterieurTotals["resteTotal"] += $row["resteTotal"];
                   ?>
											<tr>
												<td><?= htmlspecialchars($row["immeuble"]) ?></td>
												<td><?= formatSituationImmeubleAmount($row["baseTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($row["encaissementTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($row["resteTotal"]) ?></td>
												<td><?= formatSituationImmeublePercent($row["restePercent"]) ?></td>
											</tr>
											<?php
               endforeach;
           else:
               ?>
											<tr>
												<td colspan="5" class="text-center">Aucune donnée disponible dans le tableau</td>
											</tr>
											<?php
           endif;
           $anterieurPercent =
               $anterieurTotals["baseTotal"] > 0
                   ? ($anterieurTotals["resteTotal"] * 100) / $anterieurTotals["baseTotal"]
                   : 0;
           ?>
											<tr class="table-info fw-bold">
												<td>TOTAL GENERAL</td>
												<td><?= formatSituationImmeubleAmount($anterieurTotals["baseTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($anterieurTotals["encaissementTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($anterieurTotals["resteTotal"]) ?></td>
												<td><?= formatSituationImmeublePercent($anterieurPercent) ?></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<h4 class="card-title">Situation actuelle</h4>
							</div>
							<div class="card-body">
								<div class="table-responsive">
									<table class="table table-bordered table-striped">
										<thead>
											<tr>
												<th rowspan="2">Immeubles</th>
												<th rowspan="2">Base de cotisation</th>
												<th rowspan="2">Encaissante</th>
												<th colspan="2" class="text-center">Reste du</th>
											</tr>
											<tr>
												<th>Montant total en chiffre</th>
												<th>Montant total en pourcentage</th>
											</tr>
										</thead>
										<tbody>
											<?php
           $actuelTotals = [
               "baseTotal" => 0,
               "encaissementTotal" => 0,
               "resteTotal" => 0,
           ];
           if (count($situationActuelleRows) > 0):
               foreach ($situationActuelleRows as $row):
                   $actuelTotals["baseTotal"] += $row["baseTotal"];
                   $actuelTotals["encaissementTotal"] += $row["encaissementTotal"];
                   $actuelTotals["resteTotal"] += $row["resteTotal"];
                   ?>
											<tr>
												<td><?= htmlspecialchars($row["immeuble"]) ?></td>
												<td><?= formatSituationImmeubleAmount($row["baseTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($row["encaissementTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($row["resteTotal"]) ?></td>
												<td><?= formatSituationImmeublePercent($row["restePercent"]) ?></td>
											</tr>
											<?php
               endforeach;
           else:
               ?>
											<tr>
												<td colspan="5" class="text-center">Aucune donnée disponible dans le tableau</td>
											</tr>
											<?php
           endif;
           $actuelPercent =
               $actuelTotals["baseTotal"] > 0
                   ? ($actuelTotals["resteTotal"] * 100) / $actuelTotals["baseTotal"]
                   : 0;
           ?>
											<tr class="table-info fw-bold">
												<td>TOTAL GENERAL</td>
												<td><?= formatSituationImmeubleAmount($actuelTotals["baseTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($actuelTotals["encaissementTotal"]) ?></td>
												<td><?= formatSituationImmeubleAmount($actuelTotals["resteTotal"]) ?></td>
												<td><?= formatSituationImmeublePercent($actuelPercent) ?></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
