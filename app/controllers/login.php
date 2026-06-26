<?php

// Database connection
include "config/db.php";
mysqli_set_charset($connection, "utf8mb4");
mysqli_query($connection, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

global $wrongPwdErr,
    $accountNotExistErr,
    $emailPwdErr,
    $verificationRequiredErr,
    $email_empty_err,
    $pass_empty_err;

if (isset($_POST["login"])) {
    $email_signin = $_POST["email_signin"];
    $password_signin = $_POST["password_signin"];

    // clean data
    $user_email = filter_var($email_signin, FILTER_SANITIZE_EMAIL);
    $pswd = mysqli_real_escape_string($connection, $password_signin);

    // Query if email exists in db
    $sql = "SELECT * From syndic WHERE email = '{$email_signin}' ";
    $query = mysqli_query($connection, $sql);
    $rowCount = mysqli_num_rows($query);

    // If query fails, show the reason
    if (!$query) {
        die("SQL query failed: " . mysqli_error($connection));
    }

    if (!empty($email_signin) && !empty($password_signin)) {
        // Check if email exist
        if ($rowCount <= 0) {
            $accountNotExistErr = '<div class="alert alert-danger">
                        User account does not exist.
                    </div>';
        } else {
            // Fetch user data and store in php session
            while ($row = mysqli_fetch_array($query)) {
                $id = $row["id"];
                $firstname = $row["prenom"];
                $lastname = $row["nom"];
                $email = $row["email"];
                $mobilenumber = $row["mobile"];
                $pass_word = $row["password"];
                $token = $row["token"];
                $is_active = $row["is_active"];
                $id_usertype = $row["id_typeSyndic"];
            }

            // Verify password
            $password = password_verify($password_signin, $pass_word);

            if ($email_signin == $email && $password === true) {
                $_SESSION["id"] = $id;
                $_SESSION["prenom"] = $firstname;
                $_SESSION["nom"] = $lastname;
                $_SESSION["email"] = $email;
                $_SESSION["mobile"] = $mobilenumber;
                $_SESSION["token"] = $token;
                $_SESSION["loggedin"] = "ImIn";
                $_SESSION["id_usertype"] = $id_usertype;

                header("Location: ./index.php");
                exit();
            } else {
                $emailPwdErr = '<div class="alert alert-danger">
                                Either email or password is incorrect.
                            </div>';
            }
        }
    } else {
        if (empty($email_signin)) {
            $email_empty_err = "<div class='alert alert-danger email_alert'>
                            Email not provided.
                    </div>";
        }

        if (empty($password_signin)) {
            $pass_empty_err = "<div class='alert alert-danger email_alert'>
                            Password not provided.
                        </div>";
        }
    }
}
?>

