<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

require_once 'db.php'; // Your DB connection file

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email);
if ($stmt->fetch()) {
    echo json_encode(['success' => true, 'email' => $email]);
} else {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
}
$stmt->close();
$conn->close();
