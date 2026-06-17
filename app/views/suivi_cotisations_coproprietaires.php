<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
include_once __DIR__ . "/../export/suivi_cotisations_coproprietaires_data.php";

$connection = $GLOBALS["connection"];
$exercice = getExercice($GLOBALS["id_exercice"], null, $connection);
$rows = getSuiviCotisationsCoproprietairesRows(
    $GLOBALS["id_copropriete"],
    $GLOBALS["id_exercice"],
    $connection
);
$totals = getSuiviCotisationsCoproprietairesTotals($rows);
$annee = count($exercice) > 0 ? date("Y", strtotime($exercice[0]["dateDebut"])) : date("Y");
?>
		<div class="content-body">
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Suivi des cotisations des copropriétaires</h2>
						<p class="mb-0"><?= htmlspecialchars($GLOBALS["copropriete"][0]["nom"]) ?></p>
					</div>
					<a href="export/export_suivi_cotisations_coproprietaires.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-file-pdf color-primary"></i></span> Exporter PDF
					</a>
					<a href="export/export_suivi_cotisations_coproprietaires_excel.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
						<span class="btn-icon-start text-primary"><i class="fa fa-file-excel color-primary"></i></span> Exporter Excel
					</a>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-body">
								<div class="table-responsive">
									<table class="table table-bordered table-striped">
										<thead>
											<tr>
												<th>NOM &amp; PRENOM des copropriétaires</th>
												<th>Ref de la propriété</th>
												<th>Solde au 01/01/<?= htmlspecialchars($annee) ?></th>
												<th>Montant annuel de la cotisation</th>
												<th>Versements de l'exercice</th>
												<th>Solde restant au 31/12/<?= htmlspecialchars($annee) ?></th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($rows as $row): ?>
											<tr>
												<td><?= htmlspecialchars($row["nomComplet"]) ?></td>
												<td><?= htmlspecialchars($row["code"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($row["soldeAnterieur"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($row["baseCotisation"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($row["encaissement"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($row["soldeRestant"]) ?></td>
											</tr>
											<?php endforeach; ?>
											<tr class="fw-bold table-warning">
												<td colspan="2">TOTAL</td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($totals["soldeAnterieur"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($totals["baseCotisation"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($totals["encaissement"]) ?></td>
												<td class="text-end"><?= formatSuiviCotisationsCoproprietairesAmount($totals["soldeRestant"]) ?></td>
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
