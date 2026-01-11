<?php
// admin_get_shipment_events.php
require 'db.php';

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_id']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM shipment_events
    WHERE order_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$order_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);
