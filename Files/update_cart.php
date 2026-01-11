<?php
require 'db.php';
session_start();
header("Content-Type: application/json");

$item_id = $_POST['item_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if (!$item_id || !$quantity || $quantity < 0) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$stmt = $mysqli->prepare("UPDATE cart_items SET quantity = ? WHERE item_id = ?");
$stmt->bind_param("ii", $quantity, $item_id);
$stmt->execute();

echo json_encode(["success" => true]);
