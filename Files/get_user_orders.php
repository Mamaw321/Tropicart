<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "orders" => [],
        "message" => "User not logged in."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

include "db.php";

$sql = "
    SELECT 
        orders_id,
        order_date,
        total_amount,
        payment_method,
        delivery_type,
        status
    FROM orders
    WHERE user_id = ?
    ORDER BY order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode([
    "success" => true,
    "orders" => $orders
]);

$conn->close();
?>
