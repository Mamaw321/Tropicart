<?php
header('Content-Type: application/json');
require 'db.php';

$order_id = $_GET['order_id'];

$sql = "SELECT * FROM order_shipment WHERE order_id = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();
echo json_encode($result->fetch_assoc());
?>
