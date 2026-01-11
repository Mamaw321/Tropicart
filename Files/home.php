<?php
require 'db.php';
if (empty($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}
?>
<h1>Welcome to Tropicart Products Page</h1>
<p>You are logged in and can manage your fruit products.</p>
