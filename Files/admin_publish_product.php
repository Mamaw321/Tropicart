<?php

header('Content-Type: application/json');
require 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['product_id'] ?? 0;
    $published = $data['published'] ?? 0;

    if (!$id) throw new Exception('Missing product ID.');

    $stmt = $mysqli->prepare("UPDATE products SET published=?, updated_at=NOW() WHERE product_id=?");
    $stmt->bind_param('ii', $published, $id);
    $stmt->execute();

    if($stmt->affected_rows >= 0){
        echo json_encode(['status'=>'success']);
    } else {
        throw new Exception('Failed to update publish status.');
    }

} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
