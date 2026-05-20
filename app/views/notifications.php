<?php
include_once(__DIR__.'/../config/db.php');
include_once(__DIR__.'/../controllers/functions.php');
$connection = $GLOBALS["connection"];
if (isset(
		$_POST['id_copropriete'],
		$_POST['getNotificationsyndic']
	)) {
	$error_msg = "";
	
    $id_copropriete = filter_input(INPUT_POST, 'id_copropriete', FILTER_SANITIZE_STRING);
    $getNotificationsyndic = filter_input(INPUT_POST, 'getNotificationsyndic', FILTER_SANITIZE_STRING);
   
	if ($id_copropriete != '' && $getNotificationsyndic == 'true') {
			
		$notifications = getNotificationsyndic("seen", $id_copropriete, $connection);
		foreach($notifications as $notification)
			if ($notification["seen"] == "0") {
				echo "done|".$notification["idPage"]."|".$notification["nomPage"]."|".$notification["description"];
				break;
				exit();
			}
		$error_msg .= 'Une erreur est survenue';
		exit();
	} else {
		$error_msg .= 'Une erreur est survenue';
		exit();
	}
}
$notifications = getNotificationsyndic(null, $GLOBALS["copropriete"][0]["id"], $connection);
?>
		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head d-flex mb-3 align-items-start">
					<div class="me-auto d-none d-lg-block">
						<h2 class="text-primary font-w600 mb-0">Notifications</h2>
						<p class="mb-0"><?=$GLOBALS["copropriete"][0]["nom"]?></p>
					</div>
				</div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Vu</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
											<?php
											foreach($notifications as $notification):
											?>
                                            <tr>
                                                <td><?=date("d/m/Y", strtotime($notification["date"]))?></td>
                                                <td><?=$notification["description"]?></td>
                                                <td><?php if ($notification["seen"] == "1") echo "OUI"; else echo "NON"; ?></td>
                                                <td class="text-center">
													<a href="./dashboard.php?page=<?=$notification["nomPage"]?>&action=update&id=<?=$notification["idPage"]?>" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-eye"></i></a>
												</td>
                                            </tr>
                                            <?php
											endforeach;
											?>
                                            
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Vu</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
        </div>