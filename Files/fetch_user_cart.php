<?php
// fetch_user_cart.php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require 'db.php'; // defines $mysqli

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = session_id();

try {
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
        echo json_encode(['success' => true, 'cart' => [], 'total' => 0]);
        exit;
    }

    $cart = $res->fetch_assoc();
    $cart_id = (int)$cart['cart_id'];
    $stmt->close();

    $stmt = $mysqli->prepare("
        SELECT ci.item_id, ci.product_id, ci.quantity, ci.price_at_time, p.name, p.image
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $row['subtotal'] = $row['quantity'] * $row['price_at_time'];
        $total += $row['subtotal'];
        $items[] = $row;
    }

    echo json_encode(['success' => true, 'cart' => $items, 'total' => $total]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
