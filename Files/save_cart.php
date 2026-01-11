<?php
include 'db_connect.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'] ?? null; // logged-in user
$cart = $data['cart'] ?? [];

if(!$userId) exit(json_encode(['status'=>'error','message'=>'Not logged in']));

foreach ($cart as $item) {
    $stmt = $conn->prepare("
        INSERT INTO user_cart (user_id, product_id, name, price, image, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
    ");
    $stmt->bind_param("isssdi", $userId, $item['id'], $item['name'], $item['price'], $item['image'], $item['qty']);
    $stmt->execute();
}
echo json_encode(['status'=>'success']);
?>
