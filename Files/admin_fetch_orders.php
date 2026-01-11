<?php 
header('Content-Type: application/json');
require 'db.php';

// Check connection
if (!$mysqli) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch orders
$sql = "SELECT 
            o.orders_id, o.user_id, o.status AS order_status,
            o.customer_name, o.customer_email, o.customer_phone,
            o.delivery_type, o.shipping_city, o.shipping_address, o.shipping_zip,
            o.payment_method, o.subtotal, o.shipping_fee, o.discount_amount,
            o.total_amount, o.order_date,

            oi.order_item_id, oi.product_id, oi.quantity, oi.price_at_time,
            p.name AS product_name,

            -- shipment merge fields
            s.status AS shipment_status
        FROM orders o
        LEFT JOIN order_items oi ON o.orders_id = oi.orders_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN shipments s ON s.order_id = o.orders_id
        ORDER BY o.order_date DESC, o.orders_id DESC";

$result = $mysqli->query($sql);

if (!$result) {
    echo json_encode(['error' => $mysqli->error]);
    exit;
}

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orderId = $row['orders_id'];

    if (!isset($orders[$orderId])) {
        // decide final status (shipment overrides order)
        $orderStatus = strtolower($row['order_status']);
        $shipStatus = strtolower($row['shipment_status'] ?? '');

        // highest priority: shipment delivered/cancelled
        if ($shipStatus === 'delivered') {
            $finalStatus = 'delivered';
        } else if ($shipStatus === 'cancelled') {
            $finalStatus = 'cancelled';
        } else if ($shipStatus === 'in_transit' || $shipStatus === 'out_for_delivery') {
            $finalStatus = $shipStatus;
        } else {
            // fall back to order table status
            $finalStatus = $orderStatus ?: 'pending';
        }

        $orders[$orderId] = [
            'order_id' => $orderId,
            'user_id' => $row['user_id'],
            'status' => $finalStatus,

            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'customer_phone' => $row['customer_phone'],
            'delivery_type' => $row['delivery_type'],
            'shipping_city' => $row['shipping_city'],
            'shipping_address' => $row['shipping_address'],
            'shipping_zip' => $row['shipping_zip'],
            'payment_method' => $row['payment_method'],

            'subtotal' => (float)$row['subtotal'],
            'shipping_fee' => (float)$row['shipping_fee'],
            'discount_amount' => (float)$row['discount_amount'],
            'total_amount' => (float)$row['total_amount'],
            'order_date' => $row['order_date'],

            'items' => []
        ];
    }

    // add items
    if ($row['order_item_id']) {
        $orders[$orderId]['items'][] = [
            'order_item_id' => $row['order_item_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price_at_time' => (float)$row['price_at_time']
        ];
    }
}

echo json_encode(array_values($orders));
$mysqli->close();
