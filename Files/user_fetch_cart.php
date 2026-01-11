<?php
session_start();
include "db.php";

// 1. Check if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// 2. Session ID for guests
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = bin2hex(random_bytes(16));
}
$session_id = $_SESSION['session_id'];

// 3. Get cart ID
if ($user_id) {
    $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ? ORDER BY cart_id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE session_id = ? ORDER BY cart_id DESC LIMIT 1");
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["items" => [], "subtotal" => 0]);
    exit;
}

$cart = $result->fetch_assoc();
$cart_id = $cart['cart_id'];

// 4. Get cart items
$stmt = $conn->prepare("
    SELECT 
        ci.item_id,
        ci.product_id,
        ci.quantity,
        ci.price_at_time,
        p.product_name,
        p.image
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    $row['total_price'] = $row['price_at_time'] * $row['quantity'];
    $subtotal += $row['total_price'];
    $items[] = $row;
}

echo json_encode([
    "items" => $items,
    "subtotal" => $subtotal
]);
?>
