<?php

// Database connection
include_once __DIR__ . '/../config/db.php';
mysqli_set_charset($connection, 'utf8mb4');
mysqli_query($connection, 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

if (!isset($_SESSION)) {
    session_start();
}

global $wrongPwdErr,
    $accountNotExistErr,
    $emailPwdErr,
    $verificationRequiredErr,
    $email_empty_err,
    $pass_empty_err;

if (isset($_POST['login'])) {
    $email_signin = trim($_POST['email_signin'] ?? '');
    $password_signin = $_POST['password_signin'] ?? '';

    if ($email_signin !== '' && $password_signin !== '') {
        $user_email = filter_var($email_signin, FILTER_SANITIZE_EMAIL);

        $stmt = $connection->prepare(
            'SELECT id, civilite, prenom, nom, email, mobile, password, token, is_active, id_typeSyndic FROM syndic WHERE email = ? LIMIT 1'
        );

        if (!$stmt) {
            die('SQL prepare failed: ' . $connection->error);
        }

        $stmt->bind_param('s', $user_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $civilite,
                $firstname,
                $lastname,
                $email,
                $mobilenumber,
                $pass_word,
                $token,
                $is_active,
                $id_usertype
            );
            $stmt->fetch();

            if ($is_active !== '1') {
                $verificationRequiredErr = '<div class="alert alert-danger">Account verification is required for login.</div>';
            } else {
                $passwordMatches = false;
                $hashInfo = password_get_info((string) $pass_word);

                if (!empty($hashInfo['algo'])) {
                    $passwordMatches = password_verify($password_signin, $pass_word);
                } else {
                    $passwordMatches = hash_equals((string) $pass_word, (string) $password_signin);
                }

                if ($passwordMatches) {
                    session_regenerate_id(true);
                    $_SESSION['id'] = $id;
                    $_SESSION['prenom'] = $firstname;
                    $_SESSION['nom'] = $lastname;
                    $_SESSION['email'] = $email;
                    $_SESSION['mobile'] = $mobilenumber;
                    $_SESSION['token'] = $token;
                    $_SESSION['loggedin'] = 'ImIn';
                    $_SESSION['id_usertype'] = $id_usertype;

                    header('Location: ./index.php');
                    exit();
                }

                $emailPwdErr = '<div class="alert alert-danger">Either email or password is incorrect.</div>';
            }
        } else {
            $accountNotExistErr = '<div class="alert alert-danger">User account does not exist.</div>';
        }

        $stmt->close();
    } else {
        if ($email_signin === '') {
            $email_empty_err = "<div class='alert alert-danger email_alert'>Email not provided.</div>";
        }

        if ($password_signin === '') {
            $pass_empty_err = "<div class='alert alert-danger email_alert'>Password not provided.</div>";
        }
    }
}
?>
