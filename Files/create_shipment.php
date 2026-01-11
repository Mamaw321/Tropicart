<?php
header('Content-Type: application/json');
require 'db.php';

// Read JSON body
$input = json_decode(file_get_contents("php://input"), true);
$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
$dest_lat = isset($input['dest_lat']) ? floatval($input['dest_lat']) : null;
$dest_lng = isset($input['dest_lng']) ? floatval($input['dest_lng']) : null;

if (!$order_id || $dest_lat === null || $dest_lng === null) {
    echo json_encode([
        "success" => false,
        "error" => "Missing order_id or destination coordinates"
    ]);
    exit;
}

// Warehouse (SM Bacolod exact)
$warehouse_lat = 10.683100;
$warehouse_lng = 122.956300;

$sql = "
INSERT INTO order_shipment (
    order_id, status,
    warehouse_lat, warehouse_lng,
    dest_lat, dest_lng,
    current_lat, current_lng,
    start_time
) VALUES (
    ?, 'in_transit',
    ?, ?, ?, ?, ?, ?,
    NOW()
)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param(
    "idddddd",
    $order_id,
    $warehouse_lat, $warehouse_lng,
    $dest_lat, $dest_lng,
    $warehouse_lat, $warehouse_lng
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "shipment_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => $stmt->error
    ]);
}
?>
