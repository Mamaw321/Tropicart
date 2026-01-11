<?php
// get_order_details.php

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(["success" => false, "message" => "Order ID missing"]);
    exit;
}

$order_id = intval($_GET['order_id']);

require 'db.php';

// 1. FETCH ORDER HEADER
$sql_order = "
    SELECT 
        orders_id,
        customer_name,
        customer_email,
        customer_phone,
        delivery_type,
        shipping_city,
        shipping_address,
        shipping_zip,
        payment_method,
        subtotal,
        shipping_fee,
        discount_amount,
        total_amount,
        order_date
    FROM orders
    WHERE orders_id = ?
    LIMIT 1
";

$stmt_order = $mysqli->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Order not found"]);
    exit;
}

$order = $result_order->fetch_assoc();
$stmt_order->close();

// 2. FETCH ORDER ITEMS + PRODUCT NAMES  
$sql_items = "
    SELECT 
        oi.product_id,
        p.name AS product_name,
        oi.quantity,
        oi.price_at_time,
        (oi.quantity * oi.price_at_time) AS item_total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.orders_id = ?
";

$stmt_items = $mysqli->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$items = [];
while ($row = $result_items->fetch_assoc()) {
    $items[] = $row;
}

$stmt_items->close();

// 3. FINAL JSON RESPONSE
echo json_encode([
    "success" => true,
    "order" => $order,
    "items" => $items
]);

?>
