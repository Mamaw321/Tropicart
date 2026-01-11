<?php
require 'db.php';

$orderId = intval($_GET['order_id']);

$q = $mysqli->query("SELECT * FROM shipment_tracking WHERE orders_id = $orderId LIMIT 1");
$tracking = $q->fetch_assoc();

if (!$tracking) {
    echo json_encode(["error" => "No tracking row"]);
    exit;
}

// current pos
$lat = $tracking['current_lat'];
$lng = $tracking['current_lng'];

// destination
$destLat = $tracking['destination_lat'];
$destLng = $tracking['destination_lng'];

// how fast to move each update
$step = 0.0005;

// move lat toward dest
if ($lat < $destLat) $lat += $step;
else if ($lat > $destLat) $lat -= $step;

// move lng toward dest
if ($lng < $destLng) $lng += $step;
else if ($lng > $destLng) $lng -= $step;

// check if arrived
$arrived = (abs($lat - $destLat) < 0.0006 && abs($lng - $destLng) < 0.0006);

if ($arrived) {
    // mark delivered
    $mysqli->query("UPDATE orders SET status='delivered' WHERE orders_id=$orderId");
    $mysqli->query("UPDATE shipment_tracking SET delivered=1 WHERE orders_id=$orderId");
}

$mysqli->query("
    UPDATE shipment_tracking 
    SET current_lat=$lat, current_lng=$lng, last_update=NOW() 
    WHERE orders_id=$orderId
");

echo json_encode([
    "lat" => $lat,
    "lng" => $lng,
    "delivered" => $arrived
]);
?>
