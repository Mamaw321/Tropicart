<?php
header('Content-Type: application/json');
session_start();
include 'db.php'; // Make sure this connects to your MySQL database

// Read JSON payload from fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

// Validate required fields
$required = ['fullName','email','phone','city','streetAddress','zip','delivery_type','payment_method','subtotal','shipping','discount','total','cartItems'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success'=>false, 'message'=>"Missing field: $field"]);
        exit;
    }
}

$fullName = $data['fullName'];
$email = $data['email'];
$phone = $data['phone'];
$city = $data['city'];
$streetAddress = $data['streetAddress'];
$zip = $data['zip'];
$delivery_type = $data['delivery_type'];
$payment_method = $data['payment_method'];
$subtotal = $data['subtotal'];
$shipping = $data['shipping'];
$discount = $data['discount'];
$total = $data['total'];
$cartItems = $data['cartItems'];

try {
    $pdo->beginTransaction();

    // Insert into orders table
    $stmt = $pdo->prepare("INSERT INTO orders (fullName,email,phone,city,streetAddress,zip,delivery_type,payment_method,subtotal,discount,shipping,total) 
        VALUES (:fullName,:email,:phone,:city,:streetAddress,:zip,:delivery_type,:payment_method,:subtotal,:discount,:shipping,:total)");
    $stmt->execute([
        ':fullName' => $fullName,
        ':email' => $email,
        ':phone' => $phone,
        ':city' => $city,
        ':streetAddress' => $streetAddress,
        ':zip' => $zip,
        ':delivery_type' => $delivery_type,
        ':payment_method' => $payment_method,
        ':subtotal' => $subtotal,
        ':discount' => $discount,
        ':shipping' => $shipping,
        ':total' => $total
    ]);

    $order_id = $pdo->lastInsertId();

    // Insert each cart item
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
    foreach ($cartItems as $item) {
        $stmtItem->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

    $pdo->commit();

    echo json_encode(['success'=>true,'orders_id'=>$order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'Checkout failed: '.$e->getMessage()]);
}
?>
