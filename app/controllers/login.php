<?php

include_once __DIR__ . '/../config/db.php';

$wrongPwdErr = '';
$accountNotExistErr = '';
$emailPwdErr = '';
$verificationRequiredErr = '';
$email_empty_err = '';
$pass_empty_err = '';

function redirect_to_accueil()
{
    $accueilUrl = 'index.php';

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="fr">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($accueilUrl, ENT_QUOTES, 'UTF-8') . '">';
    echo '<title>Redirection</title>';
    echo '<script>window.location.replace("' . htmlspecialchars($accueilUrl, ENT_QUOTES, 'UTF-8') . '");</script>';
    echo '</head>';
    echo '<body>';
    echo '<p><a href="' . htmlspecialchars($accueilUrl, ENT_QUOTES, 'UTF-8') . '">Continuer vers l\'accueil</a></p>';
    echo '</body>';
    echo '</html>';
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

$email_signin = trim((string) ($_POST['email_signin'] ?? ''));
$password_signin = (string) ($_POST['password_signin'] ?? '');

if ($email_signin === '') {
    $email_empty_err = "<div class='alert alert-danger email_alert'>Email not provided.</div>";
}

if ($password_signin === '') {
    $pass_empty_err = "<div class='alert alert-danger email_alert'>Password not provided.</div>";
}

if ($email_empty_err !== '' || $pass_empty_err !== '') {
    return;
}

if (!filter_var($email_signin, FILTER_VALIDATE_EMAIL)) {
    $emailPwdErr = '<div class="alert alert-danger">Either email or password is incorrect.</div>';
    return;
}

$stmt = $connection->prepare(
    'SELECT id, prenom, nom, email, mobile, password, token, is_active, id_typeSyndic FROM syndic WHERE email = ? LIMIT 1'
);

if (!$stmt) {
    $emailPwdErr = '<div class="alert alert-danger">Unable to process login right now.</div>';
    return;
}

$stmt->bind_param('s', $email_signin);
$stmt->execute();
$stmt->bind_result(
    $id,
    $firstname,
    $lastname,
    $email,
    $mobilenumber,
    $password_hash,
    $token,
    $is_active,
    $id_usertype
);

$user_found = $stmt->fetch();
$stmt->close();

if (!$user_found) {
    $accountNotExistErr = '<div class="alert alert-danger">User account does not exist.</div>';
    return;
}

if ((string) $is_active !== '1') {
    $verificationRequiredErr = '<div class="alert alert-danger">Account verification is required for login.</div>';
    return;
}

if (!password_verify($password_signin, (string) $password_hash)) {
    $emailPwdErr = '<div class="alert alert-danger">Either email or password is incorrect.</div>';
    return;
}

session_regenerate_id(true);

$_SESSION['id'] = (string) $id;
$_SESSION['prenom'] = (string) $firstname;
$_SESSION['nom'] = (string) $lastname;
$_SESSION['email'] = (string) $email;
$_SESSION['mobile'] = (string) $mobilenumber;
$_SESSION['token'] = (string) $token;
$_SESSION['loggedin'] = 'ImIn';
$_SESSION['id_usertype'] = (string) $id_usertype;

redirect_to_accueil();
