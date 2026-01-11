<?php

require 'db.php';
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check for existing username/email
    $stmt_check = $mysqli->prepare("SELECT user_id FROM users WHERE username=? OR email=?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        echo "Username or Email already exists!";
        exit;
    }
    $stmt_check->close();

    // Insert user
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password) VALUES (?,?,?)");
    $stmt->bind_param("sss", $username, $email, $password);
    if ($stmt->execute()) {
        $uid = $stmt->insert_id;
        $stmt->close();

        // Generate 2FA secret
        $g = new GoogleAuthenticator();
        $secret = $g->generateSecret();

        // Save secret
        $stmt2 = $mysqli->prepare("INSERT INTO user_2fa (user_id, secret) VALUES (?,?)");
        $stmt2->bind_param("is", $uid, $secret);
        $stmt2->execute();
        $stmt2->close();

        // Store temp uid for verification
        $_SESSION['temp_uid'] = $uid;

        // Show verification page with QR
        $qr = GoogleQrUrl::generate($email, $secret, 'Tropicart');
        echo '
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <title>2FA Verification</title>
        <style>
        body{font-family:Arial;background:#f9f9f9;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
        .verify-box{background:#fff;padding:30px 25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.05);width:360px;text-align:center;}
        .verify-box h2{margin-bottom:20px;color:#333;font-size:22px;}
        .verify-box p{font-size:14px;color:#666;margin-bottom:20px;}
        .verify-box img{margin-bottom:20px;width:200px;height:200px;}
        .verify-box input[type="text"]{width:100%;padding:10px 12px;margin-bottom:15px;border:1px solid #ccc;border-radius:6px;font-size:14px;}
        .verify-box input:focus{border-color:#666;outline:none;}
        .verify-box button{width:100%;padding:12px;background:#333;color:#fff;font-size:15px;border:none;border-radius:6px;cursor:pointer;}
        .verify-box button:hover{background:#555;}
        .success-msg{margin-top:15px;color:green;font-weight:bold;}
        </style>
        </head>
        <body>
        <div class="verify-box">
        <h2>2FA Setup</h2>
        <p>Scan this QR in Google Authenticator and enter the 6-digit code:</p>
        <img src="'.$qr.'" alt="2FA QR Code">
        <form action="verify_check.php" method="POST">
        <input type="text" name="otp" placeholder="Enter 6-digit code" required>
        <button type="submit">Verify</button>
        </form>
        </div>
        </body>
        </html>
        ';
        exit;

    } else {
        echo "Registration failed: ".$stmt->error;
    }
}
?>
