<?php

require 'db.php';
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

if (session_status() === PHP_SESSION_NONE) session_start();

// User must come from login
$uid = $_SESSION['temp_uid'] ?? null;
if (!$uid) {
    header("Location: login.html");
    exit;
}

$error = '';

// Fetch 2FA secret from DB
$stmt = $mysqli->prepare("SELECT secret FROM user_2fa WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($secret);
$stmt->fetch();
$stmt->close();

// If secret does not exist, generate one
if (empty($secret)) {
    $g = new GoogleAuthenticator();
    $secret = $g->generateSecret();

    $stmt2 = $mysqli->prepare("INSERT INTO user_2fa (user_id, secret) VALUES (?,?)");
    $stmt2->bind_param("is", $uid, $secret);
    $stmt2->execute();
    $stmt2->close();
}

// Generate QR for Google Authenticator
$qr = GoogleQrUrl::generate('user'.$uid.'@tropicart.com', $secret, 'Tropicart');

// Handle OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = $_POST['otp'];
    $g = new GoogleAuthenticator();
    if ($g->checkCode($secret, $otp)) {
        // OTP correct → log in user
        $_SESSION['user_id'] = $uid;

        unset($_SESSION['temp_uid']);
        header("Location: user_product_page.html"); // redirect to product homepage
        exit;
    } else {
        $error = "Invalid OTP. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Tropicart 2FA Login</title>
<style>
body{font-family:Arial;background:#f9f9f9;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.verify-box{background:#fff;padding:30px 25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.05);width:320px;text-align:center;}
.verify-box h2{margin-bottom:20px;color:#333;font-size:22px;}
.verify-box input{width:100%;padding:10px 12px;margin:10px 0;border:1px solid #ccc;border-radius:6px;font-size:14px;}
.verify-box button{width:100%;padding:12px;background:#333;color:#fff;font-size:15px;border:none;border-radius:6px;cursor:pointer;margin-top:10px;}
.verify-box button:hover{background:#555;}
.error{color:red;margin-bottom:10px;font-size:14px;}
.qr-img{margin:15px 0;}
</style>
</head>
<body>
<div class="verify-box">
<h2>Two-Factor Authentication</h2>

<?php if($error) echo "<div class='error'>$error</div>"; ?>

<p>Scan this QR with your authenticator app:</p>
<img src="<?php echo $qr; ?>" class="qr-img" width="200" height="200">
<p>Then enter the 6-digit code below:</p>

<form method="POST">
<input type="text" name="otp" placeholder="6-digit code" required>
<button type="submit">Verify</button>
</form>

</div>
</body>
</html>
