<?php
header('Content-Type: application/json');
require 'db.php';

if (!$mysqli) { echo json_encode([]); exit; }

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

try {
    if ($order_id) {
        $stmt = $mysqli->prepare("SELECT * FROM shipments WHERE order_id = ? LIMIT 1");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        echo json_encode($row ?: null);
        exit;
    }

    $res = $mysqli->query("SELECT * FROM shipments ORDER BY updated_at DESC, shipment_id DESC");
    $list = [];
    while ($r = $res->fetch_assoc()) $list[] = $r;
    echo json_encode($list);

} catch (Exception $e) {
    echo json_encode([]);
}
?>
