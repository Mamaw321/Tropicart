<?php
// simulate_tracking.php

// ----------------------------------------------------
// Configuration for Simulation
// ----------------------------------------------------
$order_id_to_track = 82; // <<< CHANGE THIS to the ORDER ID you want to test
$movement_step     = 0.0001; // Small change (approx 10 meters) for each step
$default_start_lat = 9.9851000; // San Carlos Area (Based on your Order #62 data)
$default_start_lng = 122.8146000;
// ----------------------------------------------------

header('Content-Type: text/plain');
require 'db.php'; // Your database connection file

// Check connection
if (!$mysqli) {
    die("ERROR: Database connection failed.");
}

// 1. Get the latest recorded location for this order
$stmt = $mysqli->prepare("
    SELECT se.lat, se.lng, s.shipment_id 
    FROM shipments s
    LEFT JOIN shipment_events se ON s.shipment_id = se.shipment_id
    WHERE s.order_id = ?
    ORDER BY se.event_time DESC 
    LIMIT 1
");
$stmt->bind_param("i", $order_id_to_track);
$stmt->execute();
$result = $stmt->get_result();

$current_lat = $default_start_lat;
$current_lng = $default_start_lng;
$shipment_id = null;

if ($row = $result->fetch_assoc()) {
    // Use the last recorded location if found
    if ($row['lat'] !== null) {
        $current_lat = $row['lat'];
        $current_lng = $row['lng'];
    }
    $shipment_id = $row['shipment_id'];
}
$stmt->close();

if (!$shipment_id) {
    echo "ERROR: Could not find a shipment record for Order #{$order_id_to_track}.";
    $mysqli->close();
    exit;
}

// 2. Calculate a new location (simulating movement by moving slightly NE)
$new_lat = $current_lat + $movement_step;
$new_lng = $current_lng + $movement_step;

// 3. Insert the new location into the shipment_events table
$stmt_insert = $mysqli->prepare("
    INSERT INTO shipment_events (shipment_id, order_id, lat, lng)
    VALUES (?, ?, ?, ?)
");
$stmt_insert->bind_param("iidd", $shipment_id, $order_id_to_track, $new_lat, $new_lng);
$success = $stmt_insert->execute();
$stmt_insert->close();

if ($success) {
    echo "✅ SUCCESS: Recorded new position for Order #{$order_id_to_track}.\n";
    echo "   Previous Coordinates: {$current_lat}, {$current_lng}\n";
    echo "   New Coordinates:      {$new_lat}, {$new_lng}\n";
    echo "\n-----------------------------------------------------------------\n";
    echo "Action Required: Open the User's tracking map for Order #{$order_id_to_track}.\n";
    echo "Reload this page every few seconds to see the driver icon move!";
} else {
    echo "❌ ERROR: Failed to insert event: " . $mysqli->error;
}

$mysqli->close();
?>