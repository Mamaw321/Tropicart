<?php
header('Content-Type: application/json');
require 'db.php';

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(null);
    exit;
}

// get most recent event
$stmt = $mysqli->prepare("
    SELECT lat, lng, event_time 
    FROM shipment_events 
    WHERE order_id = ?
    ORDER BY event_time DESC
    LIMIT 1
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
echo json_encode($res->fetch_assoc() ?: null);
?>
