<?php
require 'db.php';
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['login']) && isset($_POST['password'])) {

        $login = $_POST['login'];
        $password = $_POST['password'];

        $stmt = $mysqli->prepare("SELECT user_id, password FROM users WHERE username=? OR email=? LIMIT 1");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($uid, $hash);
            $stmt->fetch();
            $stmt->close();

            if (password_verify($password, $hash)) {

                // Store TEMP UID until OTP is confirmed
                $_SESSION['temp_uid'] = $uid;

                // VERY IMPORTANT: DO NOT assign user_id here
                // Only verify.php sets it after OTP is correct

                header("Location: verify.php");
                exit;
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "Username or Email not found!";
        }
    }
}
?>
