<?php

require 'db.php';
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

$uid = $_SESSION['temp_uid'] ?? null;
if (!$uid) {
    header("Location: register.html");
    exit;
}

$otp = $_POST['otp'] ?? '';
if (empty($otp)) { echo "Enter the 6-digit code."; exit; }

// Get 2FA secret
$stmt = $mysqli->prepare("SELECT secret FROM user_2fa WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($secret);
$stmt->fetch();
$stmt->close();

// Verify OTP
$g = new GoogleAuthenticator();
if ($g->checkCode($secret, $otp)) {
    // Mark user as verified
    $stmt2 = $mysqli->prepare("UPDATE users SET is_verified=1 WHERE user_id=?");
    $stmt2->bind_param("i", $uid);
    $stmt2->execute();
    $stmt2->close();

    unset($_SESSION['temp_uid']);

    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Verified!</title>
    <style>
    body{font-family:Arial;background:#f9f9f9;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
    .msg-box{background:#fff;padding:30px 25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.05);width:360px;text-align:center;}
    .msg-box h2{margin-bottom:20px;color:green;font-size:22px;}
    .msg-box a{display:inline-block;margin-top:15px;text-decoration:none;color:#333;font-weight:bold;}
    .msg-box a:hover{text-decoration:underline;}
    </style>
    </head>
    <body>
    <div class="msg-box">
    <h2>Verification Successful!</h2>
    <p class="success-msg">You can now go to login.</p>
    <a href="login.html">Go to Login</a>
    </div>
    </body>
    </html>
    ';
} else {
    echo "Invalid 2FA code. Try again.";
}
?>
