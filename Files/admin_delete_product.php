<?php

header('Content-Type: application/json');
require 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['product_id'] ?? 0;

    if (!$id) throw new Exception('Missing product ID.');

    $stmt = $mysqli->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if($stmt->affected_rows > 0){
        echo json_encode(['status'=>'success']);
    } else {
        throw new Exception('Product not found or already deleted.');
    }

} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
