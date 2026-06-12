<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/../controllers/functions.php";
$connection = $GLOBALS["connection"];
function getRubriqueByPoste($id_poste, $connection)
{
    $request =
        "SELECT id, libelle, id_exercice, id_typeRubrique FROM rubrique WHERE id = (SELECT id_rubrique FROM poste WHERE id = ?)";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_poste);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle, $id_exercice, $id_typeRubrique);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                    "id_exercice" => $id_exercice,
                    "id_typeRubrique" => $id_typeRubrique,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
function getDepenseByDates($id_exercice, $from, $to, $connection)
{
    $request =
        "SELECT id, id_poste, date, montant, id_fournisseur, id_modePaiement, commentaire, id_exercice, id_syndic FROM depense WHERE id_exercice = ? AND CAST(date as date) BETWEEN ? AND ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $id_exercice, $from, $to);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $id_poste,
                $date,
                $montant,
                $id_fournisseur,
                $id_modePaiement,
                $commentaire,
                $id_exercice,
                $id_syndic,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "id_poste" => $id_poste,
                    "date" => $date,
                    "montant" => $montant,
                    "id_fournisseur" => $id_fournisseur,
                    "id_modePaiement" => $id_modePaiement,
                    "commentaire" => $commentaire,
                    "id_exercice" => $id_exercice,
                    "id_syndic" => $id_syndic,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
function getPaiementByDates($id_copropriete, $from, $to, $connection)
{
    $request =
        "SELECT id, id_lot, date, montant, id_modePaiement, commentaire, id_syndic FROM paiement WHERE id_lot IN (SELECT id FROM lot WHERE id_copropriete = ?) AND CAST(date as date) BETWEEN ? AND ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("sss", $id_copropriete, $from, $to);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $id_lot,
                $date,
                $montant,
                $id_modePaiement,
                $commentaire,
                $id_syndic,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "id_lot" => $id_lot,
                    "date" => $date,
                    "montant" => $montant,
                    "id_modePaiement" => $id_modePaiement,
                    "commentaire" => $commentaire,
                    "id_syndic" => $id_syndic,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}

function getPaiementStatsByDates($id_copropriete, $id_exercice, $from, $to, $connection)
{
    $stats = [
        "anterieur" => 0,
        "encours" => 0,
        "total" => 0,
    ];
    $request =
        "SELECT p.id, p.montant, " .
        "COALESCE(SUM(CASE WHEN r.id_exercice = ? THEN rrp.montant ELSE 0 END), 0) AS montant_encours, " .
        "COALESCE(SUM(CASE WHEN r.id_exercice <= 0 THEN rrp.montant ELSE 0 END), 0) AS montant_anterieur " .
        "FROM paiement p " .
        "INNER JOIN lot l ON l.id = p.id_lot " .
        "LEFT JOIN rel_rel_paiement rrp ON rrp.id_paiement = p.id " .
        "LEFT JOIN rel_lot_exercice r ON r.id_rel = rrp.id_rel " .
        "WHERE l.id_copropriete = ? AND CAST(p.date as date) BETWEEN ? AND ? " .
        "GROUP BY p.id, p.montant";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("ssss", $id_exercice, $id_copropriete, $from, $to);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $montant, $montantEncours, $montantAnterieur);
            while ($stmt->fetch()) {
                $stats["total"] += (float) $montant;
                $stats["encours"] += (float) $montantEncours;
                $stats["anterieur"] += (float) $montantAnterieur;
            }
        }
    }

    return $stats;
}

function getDashboardProgressPercent($amount, $base)
{
    $base = (float) $base;
    if (abs($base) <= 0.00001) {
        return 0;
    }

    return min(100, ((float) $amount * 100) / $base);
}

function formatDashboardAmount($amount)
{
    return number_format((float) $amount, 2, ",", " ");
}
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="mb-4">
						<h2 class="text-primary font-w600 mb-0">Tableau de bord</h2>
						<p class="mb-0"><?= $GLOBALS["copropriete"][0]["nom"] ?></p>
					</div>
					<div class="d-flex mt-sm-0 mt-3">
						<select class="default-select dashboard-select changeExercice">
							<?php $currentExercice = getExercice(
           $GLOBALS["id_exercice"],
           null,
           $connection,
       ); ?>
							<option value="<?= $currentExercice[0][
           "id"
       ] ?>" data-display="<?= getNameexercice(
    $currentExercice[0]["dateDebut"],
) ?>"><?= getNameexercice($currentExercice[0]["dateDebut"]) ?></option>
							<?php
       $exercices = getExercice(null, $GLOBALS["id_copropriete"], $connection);
       foreach ($exercices as $exercice): ?>
							<option value="<?= $exercice["id"] ?>"><?= getNameexercice(
    $exercice["dateDebut"],
) ?></option>
							<?php endforeach;
       ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-body">
								<div class="row shapreter-row">
									<div class="col-xl-3 col-lg-3 col-sm-3 col-6">
										<div class="static-icon">
											<h3 class="count"><?= number_format(
               $currentExercice[0]["montantFonct"],
               2,
           ) ?></h3>
											<span class="fs-14">Budget annuel du fonctionnement</span>
										</div>
									</div>
									<div class="col-xl-3 col-lg-3 col-sm-3 col-6">
										<div class="static-icon">
											<h3 class="count"><?= number_format(
               $currentExercice[0]["montantInvest"],
               2,
           ) ?></h3>
											<span class="fs-14">Budget annuel d'investissement</span>
										</div>
									</div>
									<div class="col-xl-3 col-lg-3 col-sm-3 col-6">
										<div class="static-icon">
											<?php
           $depenses = getDepense(null, $GLOBALS["id_exercice"], $connection);
           $totalDepenses = 0;
           foreach ($depenses as $depense) {
               $totalDepenses += $depense["montant"];
           }
           ?>
											<h3 class="count"><?= number_format($totalDepenses, 2) ?></h3>
											<span class="fs-14">Total des dépenses</span>
										</div>
									</div>
									<div class="col-xl-3 col-lg-3 col-sm-3 col-6">
										<div class="static-icon">
											<?php
           $from = date("Y-m-d", strtotime($currentExercice[0]["dateDebut"]));
           $to = date(
               "Y-m-d",
               strtotime(
                   date("Y-m-d", strtotime($currentExercice[0]["dateDebut"])) .
                       " + 1 year",
               ),
           );
           $allPaiements = getPaiementByDates(
               $GLOBALS["id_copropriete"],
               $from,
               $to,
               $connection,
           );
           $totalPaiements = 0;
           foreach ($allPaiements as $paiement) {
               $totalPaiements += $paiement["montant"];
           }
           ?>
											<h3 class="count"><?= number_format($totalPaiements, 2) ?></h3>
											<span class="fs-14">Total des cotisations</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header pb-0 border-0 flex-wrap">
								<h4 class="fs-20 ">Statistiques des dépenses</h4>
								<div>	
									<select class="default-select dashboard-select">
									  <option data-display="Grille">Grille</option>
									  
									  <option value="2">Graphique</option>
									</select>
								</div>	
							</div>
							<div class="card-body">
								<div class="owl-carousel owl-carousel owl-loaded front-view-slider ">
									<?php for ($i = 0; $i < 12; $i++):

             $monthYear = date(
                 "m/Y",
                 strtotime(
                     date(
                         "Y-m-d",
                         strtotime($currentExercice[0]["dateDebut"]),
                     ) .
                         " + " .
                         $i .
                         " month",
                 ),
             );
             $from = date(
                 "Y-m-d",
                 strtotime(
                     date(
                         "Y-m-d",
                         strtotime($currentExercice[0]["dateDebut"]),
                     ) .
                         " + " .
                         $i .
                         " month",
                 ),
             );
             $to = date(
                 "Y-m-d",
                 strtotime(
                     date(
                         "Y-m-d",
                         strtotime($currentExercice[0]["dateDebut"]),
                     ) .
                         " + " .
                         ($i + 1) .
                         " month -1 day",
                 ),
             );
             $allDepenses = getDepenseByDates(
                 $currentExercice[0]["id"],
                 $from,
                 $to,
                 $connection,
             );
             $totalDepenses = 0;
             foreach ($allDepenses as $depense) {
                 $totalDepenses += $depense["montant"];
             }
             $montantDepenses =
                 (floatval($currentExercice[0]["montantFonct"]) +
                     floatval($currentExercice[0]["montantInvest"])) /
                 12;
             ?>
									<div class="items" data-bs-toggle="modal" data-bs-target="#mois-<?= $i ?>-d">
										<div class="jobs">
											<div class="text-center">
												<span class="text-primary mb-0 d-block"><?= $monthYear ?></span>
												<h4 class="mb-3"><i class="fas fa-coins"></i> <?= number_format(
                $totalDepenses,
                2,
            ) ?></h4>
											</div>
											<div>
												<?php if ($totalDepenses - $montantDepenses <= 0) {
                echo '<span class="d-block mb-1 text-success text-end">' .
                    number_format($totalDepenses - $montantDepenses, 2) .
                    "</span>";
            } else {
                echo '<span class="d-block mb-1 text-danger text-end">' .
                    number_format($totalDepenses - $montantDepenses, 2) .
                    "</span>";
            } ?>
												<span>
													<div class="progress" style="min-width:140px;">
														<?php
              $depensePercent = ($totalDepenses * 100) / $montantDepenses;
              if ($depensePercent <= 100) {
                  $progressbarColor = "success";
              } else {
                  $progressbarColor = "danger";
              }
              ?>
														<div class="progress-bar bg-<?= $progressbarColor ?>" style="width: <?= $depensePercent ?>%; height:6px;" role="progressbar">
															<span class="sr-only"><?= $depensePercent ?>%</span>
														</div>
													</div>
												</span>
											</div>
										</div>	
									</div>
									<?php
         endfor; ?>
								</div>
								<?php for ($i = 0; $i < 12; $i++):
            $monthYear = date(
                "m/Y",
                strtotime(
                    date("Y-m-d", strtotime($currentExercice[0]["dateDebut"])) .
                        " + " .
                        $i .
                        " month",
                ),
            ); ?>
								<div id="mois-<?= $i ?>-d" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog modal-lg">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title">Les dépenses du mois <?= $monthYear ?></h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal">
												</button>
											</div>
											<div class="modal-body">
												<div class="table-responsive">
													<table class="table table-bordered table-responsive-sm">
														<thead>
															<tr>
																<th>Rubrique</th>
																<th>Poste de dépense</th>
																<th>Budget mensuel</th>
																<th>Montant de la dépense</th>
																<th class="text-start">Ecart</th>
															</tr>
														</thead>
														<tbody>
															<?php
               $from = date(
                   "Y-m-d",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           $i .
                           " month",
                   ),
               );
               $to = date(
                   "Y-m-d",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           ($i + 1) .
                           " month -1 day",
                   ),
               );
               $allDepenses = getDepenseByDates(
                   $currentExercice[0]["id"],
                   $from,
                   $to,
                   $connection,
               );
               if (count($allDepenses) == 0) {
                   echo '<td colspan="5" class="text-center">Aucune dépense pour ce mois</td>';
               }
               foreach ($allDepenses as $depense):

                   $poste = getPoste(
                       $depense["id_poste"],
                       null,
                       null,
                       $connection,
                   );
                   $rubrique = getRubriqueByPoste(
                       $depense["id_poste"],
                       $connection,
                   );
                   ?>
															<tr>
																<td><?= $rubrique[0]["libelle"] ?></th>
																<td><?= $poste[0]["libelle"] ?></td>
																<td><?= number_format(floatval($poste[0]["montant"]) / 12, 2) ?></td>
																<td><?= $depense["montant"] ?></td>
																<td class="text-primary"><?= number_format(
                    floatval($poste[0]["montant"]) / 12 -
                        floatval($depense["montant"]),
                    2,
                ) ?></td>
															</tr>
															<?php
               endforeach;
               ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php
        endfor; ?>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-header pb-0 border-0 flex-wrap">
								<h4 class="fs-20 ">Situation des encaissements</h4>
								<div>
									<a href="export/export_situation_encaissements.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1 me-2">
										<span class="btn-icon-start text-primary"><i class="fa fa-file-pdf color-primary"></i></span> Export PDF
									</a>
									<a href="export/export_situation_encaissements_excel.php?id_exercice=<?= htmlspecialchars($GLOBALS["id_exercice"]) ?>" class="btn btn-rounded btn-primary px-3 my-1">
										<span class="btn-icon-start text-primary"><i class="fa fa-file-excel color-primary"></i></span> Export Excel
									</a>
								</div>
							</div>
							<div class="card-body">
								<style>
									.encaissements-table thead th {
										text-align: center;
										vertical-align: middle;
										background: #f8f9fa;
										font-weight: 700;
									}
									.encaissements-table td {
										vertical-align: middle;
									}
									.encaissements-table .amount {
										text-align: right;
										white-space: nowrap;
										font-variant-numeric: tabular-nums;
									}
									.encaissements-table .month-cell {
										font-weight: 600;
									}
									.encaissements-table .total-row {
										background: #fff3e0;
										font-weight: 700;
									}
								</style>
								<div class="table-responsive">
									<table class="table table-bordered table-striped table-hover table-responsive-sm encaissements-table">
										<thead>
											<tr>
												<th rowspan="2">Mois</th>
												<th rowspan="2">Base théorique</th>
												<th colspan="4">Encaissement</th>
												<th rowspan="2">Écart Mensuel</th>
											</tr>
											<tr>
												<th>Antérieur</th>
												<th>En cours</th>
												<th>Avance</th>
												<th>Total Mensuel</th>
											</tr>
										</thead>
										<tbody>
											<?php
           $baseTheorique =
               floatval($currentExercice[0]["montantFonct"]) / 12;
           $totalBaseTheorique = 0;
           $totalAnterieur = 0;
           $totalEncours = 0;
           $totalAvance = 0;
           $totalMensuel = 0;
           $totalEcart = 0;
           for ($i = 0; $i < 12; $i++):
               $monthYear = date(
                   "m/Y",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           $i .
                           " month",
                   ),
               );
               $from = date(
                   "Y-m-d",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           $i .
                           " month",
                   ),
               );
               $to = date(
                   "Y-m-d",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           ($i + 1) .
                           " month -1 day",
                   ),
               );
               $paiementStats = getPaiementStatsByDates(
                   $GLOBALS["id_copropriete"],
                   $GLOBALS["id_exercice"],
                   $from,
                   $to,
                   $connection,
               );
               $paiementsAnterieurs = $paiementStats["anterieur"];
               $paiementsEncours = $paiementStats["encours"];
               $totalPaiements = $paiementStats["total"];
               $paiementsAvance = max(
                   0,
                   $totalPaiements - $paiementsAnterieurs - $paiementsEncours
               );
               $ecartMensuel = $paiementsEncours - $baseTheorique;
               $totalBaseTheorique += $baseTheorique;
               $totalAnterieur += $paiementsAnterieurs;
               $totalEncours += $paiementsEncours;
               $totalAvance += $paiementsAvance;
               $totalMensuel += $totalPaiements;
               $totalEcart += $ecartMensuel;
               ?>
											<tr>
												<td class="month-cell"><?= $monthYear ?></td>
												<td class="amount"><?= formatDashboardAmount($baseTheorique) ?></td>
												<td class="amount"><?= formatDashboardAmount($paiementsAnterieurs) ?></td>
												<td class="amount"><?= formatDashboardAmount($paiementsEncours) ?></td>
												<td class="amount"><?= formatDashboardAmount($paiementsAvance) ?></td>
												<td class="amount"><?= formatDashboardAmount($totalPaiements) ?></td>
												<td class="amount <?= $ecartMensuel >= 0 ? "text-success" : "text-danger" ?>"><?= formatDashboardAmount($ecartMensuel) ?></td>
											</tr>
											<?php endfor;
           ?>
											<tr class="total-row">
												<td>TOTAL</td>
												<td class="amount"><?= formatDashboardAmount($totalBaseTheorique) ?></td>
												<td class="amount"><?= formatDashboardAmount($totalAnterieur) ?></td>
												<td class="amount"><?= formatDashboardAmount($totalEncours) ?></td>
												<td class="amount"><?= formatDashboardAmount($totalAvance) ?></td>
												<td class="amount"><?= formatDashboardAmount($totalMensuel) ?></td>
												<td class="amount <?= $totalEcart >= 0 ? "text-success" : "text-danger" ?>"><?= formatDashboardAmount($totalEcart) ?></td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="owl-carousel owl-carousel owl-loaded front-view-slider d-none">
									<?php for ($i = 0; $i < 12; $i++):

             $monthYear = date(
                 "m/Y",
                 strtotime(
                     date(
                         "Y-m-d",
                         strtotime($currentExercice[0]["dateDebut"]),
                     ) .
                         " + " .
                         $i .
                         " month",
                 ),
             );
             $from = date(
                 "Y-m-d",
                 strtotime(
                     date(
                         "Y-m-d",
                         strtotime($currentExercice[0]["dateDebut"]),
                     ) .
                         " + " .
                         $i .
                         " month",
                 ),
             );
             $to = date(
                 "Y-m-d",
                 strtotime(
                     date(
                         "Y-m-d",
                         strtotime($currentExercice[0]["dateDebut"]),
                     ) .
                         " + " .
                         ($i + 1) .
                         " month -1 day",
                 ),
             );
             $paiementStats = getPaiementStatsByDates(
                  $GLOBALS["id_copropriete"],
                  $GLOBALS["id_exercice"],
                  $from,
                  $to,
                  $connection,
              );
             $totalPaiements = $paiementStats["total"];
             $paiementsAnterieurs = $paiementStats["anterieur"];
             $paiementsEncours = $paiementStats["encours"];
             $montantPaiements =
                 (floatval($currentExercice[0]["montantFonct"]) +
                     floatval($currentExercice[0]["montantInvest"])) /
                 12;
             $progressBase = max(
                 $montantPaiements,
                 $totalPaiements,
                 $paiementsAnterieurs,
                 $paiementsEncours,
                 1
             );
              ?>
									<div class="items" data-bs-toggle="modal" data-bs-target="#mois-<?= $i ?>-p">
										<div class="jobs">
											<div class="text-center">
												<span class="text-primary mb-0 d-block"><?= $monthYear ?></span>
												<h4 class="mb-3"><i class="far fa-credit-card"></i> <?= number_format(
                $totalPaiements,
                2,
            ) ?></h4>
											</div>
											<div>
												<?php if ($totalPaiements - $montantPaiements <= 0) {
                 echo '<span class="d-block mb-1 text-danger text-end">' .
                     number_format($totalPaiements - $montantPaiements, 2) .
                    "</span>";
            } else {
                echo '<span class="d-block mb-1 text-success text-end">' .
                     number_format($totalPaiements - $montantPaiements, 2) .
                     "</span>";
             } ?>
												<div class="mb-2">
													<div class="d-flex justify-content-between small">
														<span>Antérieur</span>
														<span><?= number_format($paiementsAnterieurs, 2) ?></span>
													</div>
													<div class="progress" style="min-width:140px;">
														<div class="progress-bar bg-warning" style="width: <?= getDashboardProgressPercent($paiementsAnterieurs, $progressBase) ?>%; height:6px;" role="progressbar"></div>
													</div>
												</div>
												<div class="mb-2">
													<div class="d-flex justify-content-between small">
														<span>Exercice en cours</span>
														<span><?= number_format($paiementsEncours, 2) ?></span>
													</div>
													<div class="progress" style="min-width:140px;">
														<div class="progress-bar bg-primary" style="width: <?= getDashboardProgressPercent($paiementsEncours, $progressBase) ?>%; height:6px;" role="progressbar"></div>
													</div>
												</div>
												<div>
													<div class="d-flex justify-content-between small">
														<span>Total</span>
														<span><?= number_format($totalPaiements, 2) ?></span>
													</div>
													<div class="progress" style="min-width:140px;">
														<?php $totalProgressColor = $totalPaiements >= $montantPaiements ? "success" : "danger"; ?>
														<div class="progress-bar bg-<?= $totalProgressColor ?>" style="width: <?= getDashboardProgressPercent($totalPaiements, $progressBase) ?>%; height:6px;" role="progressbar"></div>
													</div>
												</div>
											</div>
										</div>	
									</div>
									<?php
         endfor; ?>
								</div>
								<?php for ($i = 0; $i < 12; $i++):
            $monthYear = date(
                "m/Y",
                strtotime(
                    date("Y-m-d", strtotime($currentExercice[0]["dateDebut"])) .
                        " + " .
                        $i .
                        " month",
                ),
            ); ?>
								<div id="mois-<?= $i ?>-p" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog modal-lg">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title">Les paiements du mois <?= $monthYear ?></h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal">
												</button>
											</div>
											<div class="modal-body">
												<div class="table-responsive">
													<table class="table table-bordered table-responsive-sm">
														<thead>
															<tr>
																<th>Lot</th>
																<th>Cotisation mensuel</th>
																<th>Montant du paiement</th>
																<th class="text-start">Ecart</th>
															</tr>
														</thead>
														<tbody>
															<?php
               $from = date(
                   "Y-m-d",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           $i .
                           " month",
                   ),
               );
               $to = date(
                   "Y-m-d",
                   strtotime(
                       date(
                           "Y-m-d",
                           strtotime($currentExercice[0]["dateDebut"]),
                       ) .
                           " + " .
                           ($i + 1) .
                           " month -1 day",
                   ),
               );
               $allPaiements = getPaiementByDates(
                   $GLOBALS["id_copropriete"],
                   $from,
                   $to,
                   $connection,
               );
               if (count($allPaiements) == 0) {
                   echo '<td colspan="5" class="text-center">Aucun paiement pour ce mois</td>';
               }
               $montantPaiements =
                   (floatval($currentExercice[0]["montantFonct"]) +
                       floatval($currentExercice[0]["montantInvest"])) /
                   12 /
                   intval($GLOBALS["copropriete"][0]["nbrLot"]);
               foreach ($allPaiements as $paiement):
                   $lot = getLot(
                       $paiement["id_lot"],
                       null,
                       null,
                       $connection,
                   ); ?>
															<tr>
																<td><?= $lot[0]["code"] ?></td>
																<td><?= number_format(floatval($montantPaiements), 2) ?></td>
																<td><?= $paiement["montant"] ?></td>
																<td class="text-primary"><?= number_format(
                    floatval($paiement["montant"]) - $montantPaiements,
                    2,
                ) ?></td>
															</tr>
															<?php
               endforeach;
               ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php
        endfor; ?>
							</div>
						</div>
					</div>
				</div>
				
				<!--div class="row">
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-primary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">Tableau de contrôle de gestion</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-primary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">Tableau de caisse</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-primary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">État du compte bancaire</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-primary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">État de recouvrement</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-primary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">État des contentieux</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-primary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">Trésorerie</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-secondary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">Balance des fournisseurs</h4>
								</div>
							</a>
						</div>
					</div>
					<div class="col-xl-4 col-xxl-4 col-lg-12 col-md-12">
						<div class="card bg-secondary" style="background-image:url(images/bg-icon.png); background-repeat:no-repeat; background-position:top right;">
							<a href="#">
								<div class="card-body p-5 mt-3">
									<h4 class="text-white text-center mb-3">Grand livre de l’exercice</h4>
								</div>
							</a>
						</div>
					</div>
				</div-->
            </div>
        </div>
        
