<?php
require 'db.php';
session_start();
header("Content-Type: application/json");

$item_id = $_POST['item_id'] ?? null;

if (!$item_id) {
    echo json_encode(["success" => false, "message" => "No item ID"]);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM cart_items WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();

echo json_encode(["success" => true]);
