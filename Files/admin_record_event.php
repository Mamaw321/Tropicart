<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$order_id = intval($data['order_id'] ?? 0);
$lat      = $data['lat'] ?? null;
$lng      = $data['lng'] ?? null;

if ($order_id <= 0 || $lat === null || $lng === null) {
    echo json_encode(['success'=>false]);
    exit;
}

/* Ensure shipment exists */
$stmt = $mysqli->prepare("SELECT shipment_id FROM shipments WHERE order_id=? LIMIT 1");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->bind_result($shipment_id);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    $stmt = $mysqli->prepare("INSERT INTO shipments (order_id, status, start_time) VALUES (?, 'in_transit', NOW())");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $shipment_id = $stmt->insert_id;
    $stmt->close();
}

/* Insert event */
$stmt = $mysqli->prepare("
    INSERT INTO shipment_events (shipment_id, order_id, lat, lng)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iidd", $shipment_id, $order_id, $lat, $lng);
$stmt->execute();
$stmt->close();

/* Update shipments last location */
$stmt2 = $mysqli->prepare("
    UPDATE shipments
    SET last_lat=?, last_lng=?, updated_at=NOW()
    WHERE shipment_id=?
");
$stmt2->bind_param("ddi", $lat, $lng, $shipment_id);
$stmt2->execute();
$stmt2->close();

echo json_encode(['success'=>true]);
?>
