<?php
// user_fetch_shipment_events.php (Updated for Warehouse and Linear Mock Movement)
header('Content-Type: application/json');
require 'db.php';

// --- FIXED WAREHOUSE LOCATION (SM) ---
// We will use the main city in your project area (Bacolod) as the fixed warehouse/SM location.
const WAREHOUSE_LAT = 10.6760; 
const WAREHOUSE_LNG = 122.9530; 
const WAREHOUSE_CITY = 'Bacolod, SM Warehouse';

// Check connection
if (!$mysqli) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 1. Get the order_id from the query string
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid order ID.']);
    exit;
}

// 2. Fetch the LATEST shipment event and the final destination/status
$sql = "SELECT 
            se.lat AS event_lat, se.lng AS event_lng, se.event_time,
            s.dest_lat, s.dest_lng, s.status AS shipment_status,
            o.shipping_city, o.shipping_address
        FROM shipments s
        LEFT JOIN shipment_events se ON s.shipment_id = se.shipment_id
        JOIN orders o ON s.order_id = o.orders_id
        WHERE s.order_id = ?
        ORDER BY se.event_time DESC
        LIMIT 1";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $order_id); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Shipment record not found for this order.']);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

$shipment_status = $row['shipment_status'];
$dest_lat = (float)$row['dest_lat'];
$dest_lng = (float)$row['dest_lng'];
$location = null;

// --- DYNAMIC MOCK MOVEMENT LOGIC (UPDATED) ---

if ($row['event_lat'] !== null && $row['event_lng'] !== null) {
    // Case A: Real Location Found (Use it)
    $location = [
        'lat' => (float)$row['event_lat'],
        'lng' => (float)$row['event_lng'],
        'time' => $row['event_time']
    ];

} else if (in_array($shipment_status, ['in_transit', 'out_for_delivery'])) {
    
    // Case B: In Transit, but NO real location found (Linear Mock Movement)
    
    // Time-based progress calculation (0.0 to 1.0)
    // The driver should take approximately 3 hours (10800 seconds) to complete the journey.
    $max_duration = 10800.0; 
    
    // We use the current time modulo the max_duration to create a cycling progress.
    // Progress goes from 0 (Warehouse) to 1 (Destination) over 3 hours, then loops back.
    $progress = fmod(time(), $max_duration) / $max_duration; 
    
    // Linear Interpolation (Lerp) from Warehouse to Destination
    $mock_lat = WAREHOUSE_LAT + $progress * ($dest_lat - WAREHOUSE_LAT); 
    $mock_lng = WAREHOUSE_LNG + $progress * ($dest_lng - WAREHOUSE_LNG); 
    
    // Add small random noise for "natural" movement off the straight line (~10 meters)
    $noise_offset = 0.0001; 
    $mock_lat += (rand(-100, 100) / 100) * $noise_offset;
    $mock_lng += (rand(-100, 100) / 100) * $noise_offset;

    $location = [
        'lat' => $mock_lat,
        'lng' => $mock_lng,
        'time' => date('Y-m-d H:i:s') . ' (Mocked - ' . number_format($progress * 100, 0) . '% done)'
    ];
} 

// Case C: Status is delivered/cancelled/pending (location remains NULL/Static)

// --- FINAL RESPONSE ---
echo json_encode([
    'status' => $shipment_status,
    'location' => $location, // Driver Location
    'destination' => [
        'lat' => $dest_lat,
        'lng' => $dest_lng,
        'address' => $row['shipping_address']
    ],
    'warehouse' => [ // NEW: Warehouse (SM) Location
        'lat' => WAREHOUSE_LAT,
        'lng' => WAREHOUSE_LNG,
        'city' => WAREHOUSE_CITY
    ]
]);

$mysqli->close();
?>