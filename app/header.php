        <!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header d-print-none">
            <a href="index.php" class="brand-logo">
				<img class="brand-title" src="best_copro_logo.svg" alt="Best Copro" style="max-width: 165px; height: auto;">

            </a>
            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->
		
		<!--**********************************
            Chat box start
        ***********************************-->
		<div class="chatbox">
			<div class="chatbox-close"></div>
			<div class="custom-tab-1">
				<div class="tab-content">
					<div class="tab-pane fade active show" id="alerts" role="tabpanel">
						<div class="card mb-sm-3 mb-md-0 contacts_card">
							<div class="card-header chat-list-header text-center">
								<div>
									<h6 class="mb-1">Historique</h6>
								</div>
							</div>
							<div class="card-body contacts_body p-0 dz-scroll" id="DZ_W_Contacts_Body1">
								<ul class="contacts">
									<li class="active">
										<div class="d-flex bd-highlight">
											<div class="img_cont primary">KK</div>
											<div class="user_info">
												<span>David Nester Birthday</span>
												<p class="text-primary">Today</p>
											</div>
										</div>
									</li>
									<li>
										<div class="d-flex bd-highlight">
											<div class="img_cont success">RU<i class="icon fa-birthday-cake"></i></div>
											<div class="user_info">
												<span>Perfection Simplified</span>
												<p>Jame Smith commented on your status</p>
											</div>
										</div>
									</li>
								</ul>
							</div>
							<div class="card-footer text-center">
								<a class="all-notification" href="javascript:void(0);">Afficher tout <i class="ti-arrow-end"></i></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!--**********************************
            Chat box End
        ***********************************-->
		
		<!--**********************************
            Header start
        ***********************************-->
        <div class="header d-print-none">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left"></div>
                        <ul class="navbar-nav header-right">
							<?php if ($_SESSION["id_usertype"] === "1"): ?>
							<li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link bell-link " href="javascript:void(0);">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="22.871" viewbox="0 0 24 22.871">
									  <g data-name="Layer 2" transform="translate(-2 -2)">
										<path id="Path_9" data-name="Path 9" d="M23.268,2H4.73A2.733,2.733,0,0,0,2,4.73V17.471A2.733,2.733,0,0,0,4.73,20.2a.911.911,0,0,1,.91.91v1.94a1.82,1.82,0,0,0,2.83,1.514l6.317-4.212a.9.9,0,0,1,.5-.153h4.436a2.742,2.742,0,0,0,2.633-2L25.9,5.462A2.735,2.735,0,0,0,23.268,2Zm.879,2.978-3.539,12.74a.915.915,0,0,1-.88.663H15.292a2.718,2.718,0,0,0-1.514.459L7.46,23.051v-1.94a2.733,2.733,0,0,0-2.73-2.73.911.911,0,0,1-.91-.91V4.73a.911.911,0,0,1,.91-.91H23.268a.914.914,0,0,1,.879,1.158Z" transform="translate(0 0)"></path>
										<path id="Path_10" data-name="Path 10" d="M7.91,10.82h4.55a.91.91,0,1,0,0-1.82H7.91a.91.91,0,1,0,0,1.82Z" transform="translate(-0.45 -0.63)"></path>
										<path id="Path_11" data-name="Path 11" d="M16.1,13H7.91a.91.91,0,1,0,0,1.82H16.1a.91.91,0,1,0,0-1.82Z" transform="translate(-0.45 -0.99)"></path>
									  </g>
									</svg>
                                </a>
							</li>	
							<?php endif; ?>
							<?php if (isset($GLOBALS["copropriete"]) && $GLOBALS["copropriete"] != ""): ?>
							<li class="nav-item dropdown notification_dropdown">
                                <?php
                                $notifications = getNotificationsyndic(
                                    5,
                                    $GLOBALS["copropriete"][0]["id"],
                                    $connection,
                                );
                                $bell = "";
                                foreach ($notifications as $notification) {
                                    if ($notification["seen"] == "0") {
                                        $bell = "bell";
                                        break;
                                    }
                                }
                                ?>
								<a class="nav-link <?= $bell ?>" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24">
									  <g data-name="Layer 2" transform="translate(-2 -2)">
										<path id="Path_20" data-name="Path 20" d="M22.571,15.8V13.066a8.5,8.5,0,0,0-7.714-8.455V2.857a.857.857,0,0,0-1.714,0V4.611a8.5,8.5,0,0,0-7.714,8.455V15.8A4.293,4.293,0,0,0,2,20a2.574,2.574,0,0,0,2.571,2.571H9.8a4.286,4.286,0,0,0,8.4,0h5.23A2.574,2.574,0,0,0,26,20,4.293,4.293,0,0,0,22.571,15.8ZM7.143,13.066a6.789,6.789,0,0,1,6.78-6.78h.154a6.789,6.789,0,0,1,6.78,6.78v2.649H7.143ZM14,24.286a2.567,2.567,0,0,1-2.413-1.714h4.827A2.567,2.567,0,0,1,14,24.286Zm9.429-3.429H4.571A.858.858,0,0,1,3.714,20a2.574,2.574,0,0,1,2.571-2.571H21.714A2.574,2.574,0,0,1,24.286,20a.858.858,0,0,1-.857.857Z"></path>
									  </g>
									</svg>
									<div class="pulse-css" <?php if ($bell != "bell") {
             echo 'style="display: none;"';
         } ?>></div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div id="DZ_W_Notification1" class="widget-media dlab-scroll p-3" style="height:380px;">
										<ul class="timeline">
											<?php
           $notifications = getNotificationsyndic(
               5,
               $GLOBALS["copropriete"][0]["id"],
               $connection,
           );
           foreach ($notifications as $notification): ?>
											<li>
												<a href="./dashboard.php?page=<?= $notification[
                "nomPage"
            ] ?>&action=update&id=<?= $notification["idPage"] ?>">
													<div class="timeline-panel">
														<?php if ($notification["nomPage"] == "reclamations") {
                  echo '<div class="media me-2 media-danger"><i class="bi-megaphone"></i></div>';
              } elseif ($notification["nomPage"] == "echeances") {
                  echo '<div class="media me-2 media-success"><i class="bi-stopwatch"></i></div>';
              } ?>
														<div class="media-body">
															<h6 class="mb-1 text-break"><?= substr(
                   $notification["description"],
                   0,
                   24,
               ) ?>...</h6>
															<small class="d-block"><?= date(
                   "d/m/Y",
                   strtotime($notification["date"]),
               ) ?></small>
														</div>
													</div>
												</a>
											</li>
											<?php endforeach;
           ?>
										</ul>
									</div>
                                    <a class="all-notification" href="./dashboard.php?page=notifications">Voir toutes les notifications <i class="ti-arrow-end"></i></a>
                                </div>
                            </li>
							<?php endif; ?>
							<?php if (
           $_SESSION["id_usertype"] === "1" ||
           $_SESSION["id_usertype"] === "2"
       ): ?>
							<li class="nav-item">
                                <a class="nav-link " href="./syndics.php">
									 <svg xmlns="http://www.w3.org/2000/svg" width="23.262" height="24" viewBox="0 0 23.262 24">
									  <g id="icon" transform="translate(-1565 90)">
										<path id="setting_1_" data-name="setting (1)" d="M30.45,13.908l-1-.822a1.406,1.406,0,0,1,0-2.171l1-.822a1.869,1.869,0,0,0,.432-2.385L28.911,4.293a1.869,1.869,0,0,0-2.282-.818l-1.211.454a1.406,1.406,0,0,1-1.88-1.086l-.213-1.276A1.869,1.869,0,0,0,21.475,0H17.533a1.869,1.869,0,0,0-1.849,1.567L15.47,2.842a1.406,1.406,0,0,1-1.88,1.086l-1.211-.454a1.869,1.869,0,0,0-2.282.818L8.126,7.707a1.869,1.869,0,0,0,.432,2.385l1,.822a1.406,1.406,0,0,1,0,2.171l-1,.822a1.869,1.869,0,0,0-.432,2.385L10.1,19.707a1.869,1.869,0,0,0,2.282.818l1.211-.454a1.406,1.406,0,0,1,1.88,1.086l.213,1.276A1.869,1.869,0,0,0,17.533,24h3.943a1.869,1.869,0,0,0,1.849-1.567l.213-1.276a1.406,1.406,0,0,1,1.88-1.086l1.211.454a1.869,1.869,0,0,0,2.282-.818l1.972-3.415a1.869,1.869,0,0,0-.432-2.385ZM27.287,18.77l-1.211-.454a3.281,3.281,0,0,0-4.388,2.533l-.213,1.276H17.533l-.213-1.276a3.281,3.281,0,0,0-4.388-2.533l-1.211.454L9.75,15.355l1-.822a3.281,3.281,0,0,0,0-5.067l-1-.822L11.721,5.23l1.211.454A3.281,3.281,0,0,0,17.32,3.151l.213-1.276h3.943l.213,1.276a3.281,3.281,0,0,0,4.388,2.533l1.211-.454,1.972,3.414h0l-1,.822a3.281,3.281,0,0,0,0,5.067l1,.822ZM19.5,7.375A4.625,4.625,0,1,0,24.129,12,4.63,4.63,0,0,0,19.5,7.375Zm0,7.375A2.75,2.75,0,1,1,22.254,12,2.753,2.753,0,0,1,19.5,14.75Z" transform="translate(1557.127 -90)"></path>
									  </g>
									</svg>
                                </a>
							</li>
							<?php endif; ?>
							<li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                                    <?php
                                    $image = "";
                                    if (
                                        file_exists(
                                            "images/profile/" .
                                                $_SESSION["id"] .
                                                ".jpg",
                                        )
                                    ) {
                                        $image =
                                            "images/profile/" .
                                            $_SESSION["id"] .
                                            ".jpg?" .
                                            uniqid();
                                    } else {
                                        $image =
                                            "images/profile/0.jpg?" . uniqid();
                                    }
                                    ?>
									<img src="<?= $image ?>" width="20" alt="">
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="syndics.php?action=update&id=<?= $_SESSION[
                                        "id"
                                    ] ?>" class="dropdown-item ai-icon">
                                        <svg id="icon-user2" xmlns="http://www.w3.org/2000/svg" class="text-primary" width="18" height="18" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <span class="ms-2">Profile </span>
                                    </a>
                                    <a href="logout.php" class="dropdown-item ai-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                        <span class="ms-2">Se déconnecter </span>
                                    </a>
                                </div>
                            </li>
							<li class="nav-item">
								
							</li>
                        </ul>
                    </div>
				</nav>
			</div>
		</div>
                    
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->