<?php
include 'db_connect.php';
session_start();

$userId = $_SESSION['user_id'] ?? null;
if(!$userId) exit(json_encode([]));

$stmt = $conn->prepare("SELECT product_id AS id, name, price, image, quantity AS qty FROM user_cart WHERE user_id=?");
$stmt->bind_param("i",$userId);
$stmt->execute();
$res = $stmt->get_result();

$cart = [];
while($row = $res->fetch_assoc()) $cart[] = $row;

echo json_encode($cart);
?>
