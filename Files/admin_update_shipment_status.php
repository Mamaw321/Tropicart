<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$order_id = intval($data['order_id'] ?? 0);
$status   = $data['status'] ?? null;
$action   = $data['action'] ?? null;

$dest_lat = $data['dest_lat'] ?? null;
$dest_lng = $data['dest_lng'] ?? null;

if ($order_id <= 0) {
    echo json_encode(['success'=>false, 'message'=>'Invalid order_id']);
    exit;
}
if ($action === 'delivered') $status = 'delivered';

if (!$status && $action) {
    $map = [
        'deliver'           => 'delivered',
        'delivered'         => 'delivered',
        'ship'              => 'in_transit',
        'out_for_delivery'  => 'out_for_delivery',
        'returned'          => 'returned'
    ];
    $status = $map[$action] ?? $action;
}

if (!$status) {
    echo json_encode(['success'=>false, 'message'=>'No status']);
    exit;
}

try {
    $stmt = $mysqli->prepare("
        UPDATE shipments
        SET status=?, dest_lat=?, dest_lng=?, updated_at=CURRENT_TIMESTAMP
        WHERE order_id=?
    ");
    $stmt->bind_param("sddi", $status, $dest_lat, $dest_lng, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        // create if it didn't exist
        $stmt = $mysqli->prepare("
            INSERT INTO shipments (order_id, status, dest_lat, dest_lng)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isdd", $order_id, $status, $dest_lat, $dest_lng);
        $stmt->execute();
    }

    $stmt->close();

    echo json_encode(['success'=>true]);

} catch (Exception $e) {
    echo json_encode(['success'=>false]);
}
?>
