<?php
// prepare_checkout.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php'; // make sure $mysqli is defined

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Unknown error'];

// Get JSON payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data) || empty($data['cartItems']) || $data['total'] <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid checkout or empty cart']);
    exit;
}

// Extract customer info
$customer_name = $data['fullName'] ?? '';
$customer_email = $data['email'] ?? '';
$customer_phone = $data['phone'] ?? '';
$delivery_type = $data['delivery_type'] ?? 'delivery';
$shipping_city = $data['city'] ?? '';
$shipping_address = $data['streetAddress'] ?? '';
$shipping_zip = $data['zip'] ?? '';
$subtotal = (float)($data['subtotal'] ?? 0);
$shipping_fee = (float)($data['shipping'] ?? 0);
$discount_amount = (float)($data['discount'] ?? 0);
$total_amount = (float)($data['total'] ?? 0);
$cart_items = $data['cartItems'] ?? [];

// Validate payment method ENUM
$allowed_methods = ['card','gcash','cod'];
$payment_method = $data['payment_method'] ?? 'cod';
if (!in_array($payment_method, $allowed_methods)) {
    $payment_method = 'cod';
}

// Optional: log what PHP received
error_log("DEBUG Checkout Data: " . print_r($data, true));
error_log("DEBUG Payment Method: $payment_method");

// Validate required fields
if (empty($customer_name) || empty($customer_email) || empty($shipping_city)) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_id_db = $user_id !== null ? (int)$user_id : null;

$mysqli->begin_transaction();

try {
    // Insert order
    $stmt = $mysqli->prepare("INSERT INTO orders 
        (user_id, status, customer_name, customer_email, customer_phone, delivery_type, shipping_city, shipping_address, shipping_zip, payment_method, subtotal, shipping_fee, discount_amount, total_amount)
        VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) throw new Exception("Prepare failed: " . $mysqli->error);

    $stmt->bind_param(
        "issssssssdddd",
        $user_id_db,
        $customer_name,
        $customer_email,
        $customer_phone,
        $delivery_type,
        $shipping_city,
        $shipping_address,
        $shipping_zip,
        $payment_method,
        $subtotal,
        $shipping_fee,
        $discount_amount,
        $total_amount
    );

    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

    $order_id = $mysqli->insert_id;
    $stmt->close();

    // Insert order items
    $stmt_item = $mysqli->prepare("INSERT INTO order_items (orders_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
    if (!$stmt_item) throw new Exception("Prepare items failed: " . $mysqli->error);

    foreach ($cart_items as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);
        $price = (float)($item['price'] ?? 0);

        if ($product_id <= 0 || $qty <= 0) continue;

        $unit_price = $price / $qty;
        $stmt_item->bind_param("iiid", $order_id, $product_id, $qty, $unit_price);
        if (!$stmt_item->execute()) throw new Exception("Insert item failed: " . $stmt_item->error);
    }
    $stmt_item->close();

    // Clear cart
    if ($user_id) {
        $stmt_clear = $mysqli->prepare("DELETE FROM carts WHERE user_id = ?");
        $stmt_clear->bind_param("i", $user_id);
        $stmt_clear->execute();
        $stmt_clear->close();
    }

    $mysqli->commit();

    echo json_encode(['success'=>true, 'message'=>'Order placed', 'orders_id'=>$order_id]);

} catch (Exception $e) {
    $mysqli->rollback();
    error_log("Checkout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Server error during checkout']);
}
?>
