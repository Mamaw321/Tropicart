<?php
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
$lat = isset($input['lat']) ? floatval($input['lat']) : null;
$lng = isset($input['lng']) ? floatval($input['lng']) : null;
$action = isset($input['action']) ? $input['action'] : null;

if (!$order_id) { echo json_encode(['success'=>false,'error'=>'Missing order_id']); exit; }

// find shipment for order
$stmt = $mysqli->prepare("SELECT shipment_id FROM order_shipment WHERE order_id = ? LIMIT 1");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) { echo json_encode(['success'=>false,'error'=>'Shipment not found']); exit; }
$shipment_id = $row['shipment_id'];

if ($action === 'delivered') {
    $u = $mysqli->prepare("UPDATE order_shipment SET status='delivered', current_lat = ?, current_lng = ?, delivered_time = NOW() WHERE shipment_id = ?");
    $u->bind_param("ddi", $lat, $lng, $shipment_id);
    $u->execute();
    // record event
    $e = $mysqli->prepare("INSERT INTO shipment_events (order_id, shipment_id, event_type, lat, lng) VALUES (?,?,?,?,?)");
    $etype = 'delivered';
    $lat_s = (string)$lat; $lng_s = (string)$lng;
    $e->bind_param("iisss", $order_id, $shipment_id, $etype, $lat_s, $lng_s);
    $e->execute();
    echo json_encode(['success'=>true,'status'=>'delivered']);
    $mysqli->close();
    exit;
}

if ($lat !== null && $lng !== null) {
    $u = $mysqli->prepare("UPDATE order_shipment SET current_lat = ?, current_lng = ?, last_update = NOW() WHERE shipment_id = ?");
    $u->bind_param("ddi", $lat, $lng, $shipment_id);
    $u->execute();
    // record location event
    $e = $mysqli->prepare("INSERT INTO shipment_events (order_id, shipment_id, event_type, lat, lng) VALUES (?,?,?,?,?)");
    $etype = 'location';
    $lat_s = (string)$lat; $lng_s = (string)$lng;
    $e->bind_param("iisss", $order_id, $shipment_id, $etype, $lat_s, $lng_s);
    $e->execute();

    echo json_encode(['success'=>true,'status'=>'updated']);
    $mysqli->close();
    exit;
}

echo json_encode(['success'=>false,'error'=>'Nothing to update']);
$mysqli->close();
