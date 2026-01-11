<?php
header('Content-Type: application/json');
require 'db.php';

$order_id = $_POST['order_id'];

$sql = "SELECT * FROM order_shipment WHERE order_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$shipment = $stmt->get_result()->fetch_assoc();

if (!$shipment) {
    echo json_encode(["error" => "Shipment not found"]);
    exit;
}

$current_lat = $shipment['current_lat'];
$current_lng = $shipment['current_lng'];

$dest_lat = $shipment['dest_lat'];
$dest_lng = $shipment['dest_lng'];

// Movement speed (small increments)
$step = 0.0003;

// Compute next movement
$new_lat = $current_lat + ($dest_lat - $current_lat) * $step;
$new_lng = $current_lng + ($dest_lng - $current_lng) * $step;

// If close enough → delivered
$distance = sqrt(pow($dest_lat - $new_lat, 2) + pow($dest_lng - $new_lng, 2));

if ($distance < 0.0002) {
    // Arrived
    $sql = "UPDATE order_shipment 
            SET current_lat = ?, current_lng = ?, status = 'delivered', delivered_time = NOW()
            WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ddi", $dest_lat, $dest_lng, $order_id);
    $stmt->execute();
    echo json_encode(["status" => "delivered"]);
    exit;
}

$sql = "UPDATE order_shipment SET current_lat = ?, current_lng = ? WHERE order_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ddi", $new_lat, $new_lng, $order_id);
$stmt->execute();

echo json_encode(["status" => "moving"]);
?>
