<?php
// get_cart_items.php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require 'db.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = session_id();

// STEP 1: Find the cart
if ($user_id) {
    $stmt = $mysqli->prepare("SELECT cart_id FROM carts WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $mysqli->prepare("SELECT cart_id FROM carts WHERE session_id = ? LIMIT 1");
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["items" => [], "subtotal" => 0]);
    exit;
}

$row = $res->fetch_assoc();
$cart_id = (int)$row['cart_id'];
$stmt->close();


// STEP 2: Fetch cart items + product info
$sql = "
    SELECT ci.item_id, ci.product_id, ci.quantity, ci.price_at_time,
           p.name AS product_name, p.image
    FROM cart_items ci
    INNER JOIN products p ON p.product_id = ci.product_id
    WHERE ci.cart_id = ?
";

$stmt2 = $mysqli->prepare($sql);
$stmt2->bind_param("i", $cart_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

$items = [];
$subtotal = 0;

while ($row = $res2->fetch_assoc()) {
    $row['total_price'] = $row['price_at_time'] * $row['quantity'];
    $subtotal += $row['total_price'];
    $items[] = $row;
}

echo json_encode([
    "items" => $items,
    "subtotal" => $subtotal
]);
exit;
?>
