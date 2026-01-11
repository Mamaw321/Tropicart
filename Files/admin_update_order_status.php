<?php
// admin_update_order_status.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$allowed = ['pending','processing','shipped','in_transit','out_for_delivery','delivered','returned','cancelled'];

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$action = isset($input['action']) ? strtolower(trim($input['action'])) : '';

if (!$order_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'missing parameters']);
    exit;
}

// map some synonyms
if ($action === 'shipped') $action = 'shipped';
if ($action === 'delivered') $action = 'delivered';
if ($action === 'processing') $action = 'processing';
if ($action === 'cancelled') $action = 'cancelled';

// ensure allowed
if (!in_array($action, $allowed)) {
    // fallback: allow shipped/delivered/processing/cancelled only
    if (!in_array($action, ['shipped','delivered','processing','cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'invalid status']);
        exit;
    }
}

$stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE orders_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'db prepare failed']);
    exit;
}
$stmt->bind_param('si', $action, $order_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
exit;
?>
