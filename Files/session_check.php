<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("SELECT username, email FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($username, $email);
    $stmt->fetch();
    $stmt->close();

    echo json_encode([
        'loggedIn' => true,
        'username' => $username,
        'email' => $email   // <-- add this line
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
