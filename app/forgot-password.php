<?php
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    if ($scriptName === '' && isset($_SERVER['REQUEST_URI'])) {
        $scriptName = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    $scriptName = str_replace('\\', '/', (string) $scriptName);
    $segments = array_values(array_filter(explode('/', $scriptName), 'strlen'));
    $baseSegment = $segments[0] ?? '';
    $cookiePath = $baseSegment === '' ? '/' : '/' . $baseSegment;
    $sessionSuffix = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($baseSegment));
    $sessionName = 'BESTCOPRO' . ($sessionSuffix !== '' ? $sessionSuffix : 'ROOT');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    session_name($sessionName);
    session_set_cookie_params(0, $cookiePath, $cookieParams['domain'], $secure, true);
    session_start();

    if (!headers_sent()) {
        setcookie('PHPSESSID', '', time() - 3600, $cookiePath, $cookieParams['domain'], $secure, true);
        if ($cookiePath !== '/') {
            setcookie('PHPSESSID', '', time() - 3600, '/', $cookieParams['domain'], $secure, true);
        }
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
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="">
	<meta name="author" content="">
	<meta name="robots" content="">
	<meta name="description" content="Davur - Restaurant Bootstrap Admin Dashboard + FrontEnd">
	<meta property="og:title" content="Davur - Restaurant Bootstrap Admin Dashboard + FrontEnd">
	<meta property="og:description" content="Davur - Restaurant Bootstrap Admin Dashboard + FrontEnd">
	<meta property="og:image" content="https://davur.dexignzone.com/dashboard/social-image.png">
	<meta name="format-detection" content="telephone=no">
    <title>Davur - Restaurant Bootstrap Admin Dashboard + FrontEnd </title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images\favicon.png">
    <link href="css\style.css" rel="stylesheet">
    <link href="css\bestcopro-refresh.css" rel="stylesheet">

</head>

<body class="h-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
									<div class="text-center mb-3">
										<a href="index.php"><img src="best_copro_logo.svg" alt="Best Copro" style="max-width: 220px; height: auto;"></a>
									</div>
                                    <h4 class="text-center mb-4">Réinitialiser le mot de passe</h4>
                                    <form action="index.php">
                                        <div class="form-group">
                                            <input type="email" class="form-control" value="" placeholder="Adresse e-mail">
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-block">Envoyer</button>
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
    <script src="js\deznav-init.js"></script>
	<script src="js\bestcopro-refresh.js"></script>

</body>

</html>