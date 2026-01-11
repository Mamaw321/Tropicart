<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = $_POST['login']; // username or email
    $password = $_POST['password'];

    // Check username OR email
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($uid, $hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($password, $hash)) {
            // Store temp session for 2FA
            $_SESSION['temp_uid'] = $uid;

            // Redirect to 2FA verification
            header("Location: verify.php");
            exit;

        } else {
            echo "Incorrect password!";
        }
    } else {
        echo "Username or Email not found!";
    }
}
?>
