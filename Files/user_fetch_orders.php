<?php 
// 1. Start the session at the very top
session_start();

header('Content-Type: application/json');
require 'db.php'; // Ensure this file correctly establishes the database connection

// Check connection
if (!$mysqli) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 2. Get user_id from the session
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// IMPORTANT: Simulation for testing if you don't have a login system running.
// If you MUST hardcode for testing, uncomment the line below.
// $user_id = 1; 

// 3. Check if the user is logged in
if ($user_id === 0) {
    // Respond with an error indicating the user is not authenticated
    // Client-side JavaScript will catch this and show a 'not logged in' message
    http_response_code(401); // Set HTTP status code to Unauthorized
    echo json_encode(['error' => 'User not logged in or session expired.']);
    exit;
}


// 4. Prepare the SQL query with a WHERE clause to filter by user_id
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
        WHERE o.user_id = ? -- <<< Filter by the session user ID
        ORDER BY o.order_date DESC, o.orders_id DESC";

// 5. Use prepared statements to prevent SQL Injection
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error]);
    exit;
}

// Bind the user ID parameter
$stmt->bind_param("i", $user_id); 
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => $mysqli->error]);
    exit;
}

$orders = [];

// 6. Process the results 
while ($row = $result->fetch_assoc()) {
    $orderId = $row['orders_id'];

    if (!isset($orders[$orderId])) {
        // determine final status (shipment overrides order)
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
            // ... all other fields
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
$stmt->close();
$mysqli->close();