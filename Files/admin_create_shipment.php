<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$order_id = intval($data['order_id'] ?? 0);
$dest_lat = $data['dest_lat'] ?? null;
$dest_lng = $data['dest_lng'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Missing order_id']);
    exit;
}

/* --- If JS sent null coordinates, assign a random Negros city --- */
if ($dest_lat === null || $dest_lng === null) {
    $cities = [
        ['lat'=>10.6760, 'lng'=>122.9530], // Bacolod
        ['lat'=>10.6969, 'lng'=>122.9655], // Talisay
        ['lat'=>10.8454, 'lng'=>122.9566], // Silay
        ['lat'=>10.5331, 'lng'=>122.8331], // Bago
        ['lat'=>10.6050, 'lng'=>123.0410], // Murcia
        ['lat'=>10.9014, 'lng'=>123.0856], // Victorias
        ['lat'=>10.4891, 'lng'=>123.4132], // San Carlos
        ['lat'=>9.9851, 'lng'=>122.8146],  // Kabankalan
        ['lat'=>10.4239, 'lng'=>122.9221], // La Carlota
    ];

    $pick = $cities[array_rand($cities)];
    $dest_lat = $pick['lat'];
    $dest_lng = $pick['lng'];
}

/* --- Check if shipment row already exists --- */
$stmt = $mysqli->prepare("SELECT shipment_id FROM shipments WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Shipment exists → update it
    $stmt->close();

    $stmt2 = $mysqli->prepare("UPDATE shipments 
        SET dest_lat = ?, dest_lng = ?, status = 'in_transit' 
        WHERE order_id = ?");
    $stmt2->bind_param("ddi", $dest_lat, $dest_lng, $order_id);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode(['success' => true, 'updated' => true]);
    exit;
}

$stmt->close();

/* --- Insert NEW shipment record --- */
$stmt3 = $mysqli->prepare("
    INSERT INTO shipments (order_id, status, courier, dest_lat, dest_lng) 
    VALUES (?, 'in_transit', 'Tropicart', ?, ?)
");
$stmt3->bind_param("idd", $order_id, $dest_lat, $dest_lng);
$stmt3->execute();
$stmt3->close();

echo json_encode(['success' => true, 'created' => true]);
?>
