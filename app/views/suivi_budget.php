<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/../export/suivi_budget_data.php";
$connection = $GLOBALS["connection"];

$exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
$rubriques = getSuiviBudgetRows($GLOBALS["id_exercice"], $connection);
?>
		<div class="content-body">
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Suivi budget de fonctionnement</h2>
					</div>
					<div class="text-end d-none d-lg-block me-3">
						<p class="mb-0 fw-bold"><?= htmlspecialchars($GLOBALS["copropriete"][0]["nom"]) ?></p>
					</div>
					<a href="export/export_suivi_budget.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-download color-primary"></i></span> Exporter PDF
					</a>
					<a href="export/export_suivi_budget_excel.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-file-excel color-primary"></i></span> Exporter Excel
					</a>
				</div>
				<div class="alert alert-primary alert-alt fade show p-3 mb-4">
					<strong>Suivi budget de fonctionnement de l'<?= getNameexercice($exercice[0]["dateDebut"]) ?></strong>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-body">
								<div class="table-responsive">
									<table class="table table-bordered table-striped">
										<thead>
											<tr>
												<th rowspan="2">Rubrique</th>
												<th rowspan="2">Poste</th>
												<th rowspan="2">Montant budget</th>
												<th rowspan="2">Consommation</th>
												<th colspan="2" class="text-center">Suivi budget annuel</th>
											</tr>
											<tr>
												<th>Montant restant</th>
												<th>% restant</th>
											</tr>
										</thead>
										<tbody>
											<?php
           $globalTotals = getEmptySuiviBudgetTotals();
           foreach ($rubriques as $rubrique):
               $rubriqueTotals = $rubrique["totals"];
               addSuiviBudgetTotals($globalTotals, $rubriqueTotals);
               foreach ($rubrique["postes"] as $index => $poste): ?>
											<tr>
												<td><?= $index === 0 ? htmlspecialchars($rubrique["libelle"]) : "" ?></td>
												<td><?= htmlspecialchars($poste["poste"]) ?></td>
												<td><?= formatSuiviBudgetAmount($poste["budget"]) ?></td>
												<td><?= formatSuiviBudgetAmount($poste["cout"]) ?></td>
												<td><?= formatSuiviBudgetAmount($poste["annuelRestant"]) ?></td>
												<td><?= formatSuiviBudgetPercent($poste["annuelPourcentageRestant"]) ?></td>
											</tr>
											<?php endforeach; ?>
											<tr class="table-primary fw-bold">
												<td colspan="2">TOTAL <?= htmlspecialchars($rubrique["libelle"]) ?></td>
												<td><?= formatSuiviBudgetAmount($rubriqueTotals["budget"]) ?></td>
												<td><?= formatSuiviBudgetAmount($rubriqueTotals["cout"]) ?></td>
												<td><?= formatSuiviBudgetAmount($rubriqueTotals["annuelRestant"]) ?></td>
												<td><?= formatSuiviBudgetPercent(getSuiviBudgetPercent($rubriqueTotals["annuelRestant"], $rubriqueTotals["budget"])) ?></td>
											</tr>
											<?php
           endforeach;
           ?>
											<tr class="table-info fw-bold">
												<td colspan="2">TOTAL GENERAL</td>
												<td><?= formatSuiviBudgetAmount($globalTotals["budget"]) ?></td>
												<td><?= formatSuiviBudgetAmount($globalTotals["cout"]) ?></td>
												<td><?= formatSuiviBudgetAmount($globalTotals["annuelRestant"]) ?></td>
												<td><?= formatSuiviBudgetPercent(getSuiviBudgetPercent($globalTotals["annuelRestant"], $globalTotals["budget"])) ?></td>
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
