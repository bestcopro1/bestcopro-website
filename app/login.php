<?php
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $cookiePath = ($scriptDir === '.' || $scriptDir === '/') ? '/' : rtrim($scriptDir, '/');
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params(0, $cookiePath, $cookieParams['domain'], $isHttps, true);
    session_start();

    if ($cookiePath !== '/' && !headers_sent()) {
        $secureCookie = $isHttps ? '; Secure' : '';
        header('Set-Cookie: ' . session_name() . '=deleted; expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; path=/' . $secureCookie . '; HttpOnly', false);
    }
}
// If the user is logged in redirect to the dashboard page...
if (
    isset($_SESSION["loggedin"], $_SESSION["id"]) &&
    $_SESSION["loggedin"] === "ImIn" &&
    is_int(intval($_SESSION["id"]))
) {
    header("Location: ./index.php");
    exit();
}

// Login script must run before any HTML so redirect and session cookies are reliable.
include __DIR__ . "/controllers/login.php";
?>
<!DOCTYPE html>
<html lang="fr" class="h-100">

<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="copropriété, immeuble, villa, appartement, lot">
	<meta name="author" content="IARoTech">
	<meta name="robots" content="">
	<meta name="description" content="BEST COPRO facilite la vie des copropriétés autonomes">
	
	<!-- PAGE TITLE HERE -->
	<title>BEST COPRO - Connexion</title>
	
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images\favicon.png">
	<link href="vendor\jquery-nice-select\css\nice-select.css" rel="stylesheet">
    <link href="css\style.css" rel="stylesheet">
    <link href="css\bestcopro-refresh.css" rel="stylesheet">

</head>

<body class="vh-100">

    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
									<div class="text-center mb-3">
										<a href="index.php" class="brand-logo">
											<img src="best_copro_logo.svg" alt="Best Copro" style="max-width: 220px; height: auto;">
										</a>
									</div>
                                    <p class="text-center mb-3">Hello BESTCOPRO</p>
                                    <h4 class="text-center mb-4">Authentifiez-vous pour accéder à votre compte</h4>
                                    <?php echo $accountNotExistErr; ?>
									<?php echo $emailPwdErr; ?>
									<?php echo $verificationRequiredErr; ?>
									<?php echo $email_empty_err; ?>
									<?php echo $pass_empty_err; ?>
                                    <form action="" method="post">
                                        <input type="hidden" name="login" value="1">
                                        <div class="mb-3">
                                            <input type="email" class="form-control input-rounded input-default mb-3" name="email_signin" id="email_signin" value="" placeholder="Adresse e-mail">
                                        </div>
                                        <div class="mb-3">
                                            <input type="password" class="form-control input-rounded input-default mb-3" name="password_signin" id="password_signin" value="" placeholder="Mot de passe">
                                        </div>
                                        <!--div class="row d-flex justify-content-between mt-4 mb-2">
                                            <div class="mb-3">
                                               <div class="form-check custom-checkbox ms-1">
													<input type="checkbox" class="form-check-input" id="basic_checkbox_1">
													<label class="form-check-label" for="basic_checkbox_1">Se souvenir de moi</label>
												</div>
                                            </div>
                                            <div class="mb-3">
                                                <a href="forgot-password.php">Mot de passe oublié ?</a>
                                            </div>
                                        </div-->
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-rounded btn-block" name="login" id="sign_in">Connexion</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
	
    <script src="vendor\global\global.min.js"></script>
	<script src="vendor\jquery-nice-select\js\jquery.nice-select.min.js"></script>
    <script src="js\custom.min.js"></script>
    <script src="js\dlabnav-init.js"></script>
	<script src="js\bestcopro-refresh.js"></script>
</body>
</html>
