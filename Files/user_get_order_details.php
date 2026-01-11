<?php
header("Content-Type: application/json");
session_start();

if (!isset($_GET["order_id"])) {
    echo json_encode(["success" => false, "message" => "Order ID missing"]);
    exit;
}

$order_id = intval($_GET["order_id"]);

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION["user_id"];

include "db.php";


// -----------------------------
// GET MAIN ORDER DETAILS
// -----------------------------
$sql = "
    SELECT 
        orders_id,
        order_date,
        customer_name,
        customer_email,
        customer_phone,
        delivery_type,
        shipping_address,
        shipping_city,
        shipping_zip,
        subtotal,
        shipping_fee,
        discount_amount,
        total_amount,
        payment_method,
        status
    FROM orders
    WHERE orders_id = ? AND user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Order not found"]);
    exit;
}

$order = $result->fetch_assoc();


// -----------------------------
// GET ORDER ITEMS
// -----------------------------
$sql_items = "
    SELECT 
        oi.quantity,
        oi.price_at_time,
        p.name AS product_name,
        (oi.quantity * oi.price_at_time) AS item_total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.orders_id = ?
";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

$items = [];
while ($row = $res_items->fetch_assoc()) {
    $items[] = $row;
}


// -----------------------------
// RETURN JSON
// -----------------------------
echo json_encode([
    "success" => true,
    "order" => $order,
    "items" => $items
]);

$conn->close();
?>
