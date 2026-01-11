<?php
header('Content-Type: application/json');
require 'db.php';

$sql = "
    SELECT 
        orders.orders_id,
        orders.customer_name,
        orders.customer_email,
        orders.customer_phone,
        orders.shipping_city,
        orders.shipping_address,
        orders.order_date,
        orders.subtotal,
        orders.shipping_fee,
        orders.total_amount,
        orders.status
    FROM orders
    WHERE status = 'processing'
    ORDER BY orders.order_date DESC
";

$result = $mysqli->query($sql);
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode($orders);
$mysqli->close();
