<?php

$mysqli = new mysqli('localhost', 'root', '', 'db_tropicart');

if ($mysqli->connect_error) { 
    die('DB error: ' . $mysqli->connect_error); 
}

$mysqli->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(){
    if (empty($_SESSION['uid'])) {
        header('Location: login.html'); 
        exit;
    }
}

function requireVerified(){
    requireLogin();
    if (empty($_SESSION['verified'])){
        header('Location: verify.php'); 
        exit;
    }
}
?>
