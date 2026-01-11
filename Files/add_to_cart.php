<?php
// add_to_cart.php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require 'db.php'; // your existing DB connection that defines $mysqli

// accept JSON payload or POST fallback
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) $data = $_POST;

$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$quantity   = isset($data['quantity']) ? (int)$data['quantity'] : 1;
if ($quantity < 0) $quantity = 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product_id']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = session_id();

try {
    // get product info (price, stock)
    $stmt = $mysqli->prepare("SELECT price, stock FROM products WHERE product_id = ? LIMIT 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    $prod = $res->fetch_assoc();
    $price = (float)$prod['price'];
    $stock = (int)$prod['stock'];
    $stmt->close();

    if ($quantity > $stock) $quantity = $stock;

    // find or create cart (prefer user_id if available)
    if ($user_id) {
        $stmt = $mysqli->prepare("SELECT cart_id FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $cart_row = $res->fetch_assoc();
            $cart_id = (int)$cart_row['cart_id'];
        } else {
            $stmt2 = $mysqli->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)");
            $stmt2->bind_param("is", $user_id, $session_id);
            $stmt2->execute();
            $cart_id = $stmt2->insert_id;
            $stmt2->close();
        }
        $stmt->close();
    } else {
        // guest
        $stmt = $mysqli->prepare("SELECT cart_id FROM carts WHERE session_id = ? LIMIT 1");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $cart_row = $res->fetch_assoc();
            $cart_id = (int)$cart_row['cart_id'];
        } else {
            $stmt2 = $mysqli->prepare("INSERT INTO carts (user_id, session_id) VALUES (NULL, ?)");
            $stmt2->bind_param("s", $session_id);
            $stmt2->execute();
            $cart_id = $stmt2->insert_id;
            $stmt2->close();
        }
        $stmt->close();
    }

    // if quantity == 0 -> delete
    if ($quantity === 0) {
        $d = $mysqli->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $d->bind_param("ii", $cart_id, $product_id);
        $d->execute();
        $d->close();
        echo json_encode(['success' => true, 'action' => 'removed']);
        exit;
    }

    // check if item exists
    $check = $mysqli->prepare("SELECT item_id FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
    $check->bind_param("ii", $cart_id, $product_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // update quantity & price_at_time
        $row = $res->fetch_assoc();
        $item_id = (int)$row['item_id'];
        $upd = $mysqli->prepare("UPDATE cart_items SET quantity = ?, price_at_time = ? WHERE item_id = ?");
        $upd->bind_param("idi", $quantity, $price, $item_id);
        $upd->execute();
        $upd->close();
        echo json_encode(['success' => true, 'action' => 'updated', 'quantity' => $quantity]);
        exit;
    } else {
        // insert new row
        $ins = $mysqli->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
        $ins->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
        $ins->execute();
        $ins->close();
        echo json_encode(['success' => true, 'action' => 'inserted', 'quantity' => $quantity]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
