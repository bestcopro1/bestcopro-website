<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/../export/situation_immeuble_data.php";
$connection = $GLOBALS["connection"];

$exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
$situationImmeubleData = getSituationImmeubleData(
    $GLOBALS["id_copropriete"],
    $GLOBALS["id_exercice"],
    $connection
);
$situationAnterieureRows = $situationImmeubleData["anterieur"];
$situationActuelleRows = $situationImmeubleData["actuel"];
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
					<a href="export/export_situation_immeuble.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-file-pdf color-primary"></i></span> Export PDF
					</a>
					<a href="export/export_situation_immeuble_excel.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-file-excel color-primary"></i></span> Export Excel
					</a>
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
