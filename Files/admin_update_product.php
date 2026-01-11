<?php

header('Content-Type: application/json');
require 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $id = $_POST['product_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $published = isset($_POST['published']) ? (int)$_POST['published'] : 0;

    if (!$id || empty($name) || empty($description)) {
        throw new Exception('Missing required fields.');
    }

    // Handle file upload
    $imagePath = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetDir = 'uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($fileTmp, $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    if($imagePath){
        $stmt = $mysqli->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, published=?, image=?, updated_at=NOW() WHERE product_id=?");
        $stmt->bind_param('ssdisii', $name, $description, $price, $stock, $published, $imagePath, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, published=?, updated_at=NOW() WHERE product_id=?");
        $stmt->bind_param('ssdisi', $name, $description, $price, $stock, $published, $id);
    }

    $stmt->execute();

    if($stmt->affected_rows >= 0){
        echo json_encode(['status'=>'success']);
    } else {
        throw new Exception('Failed to update product.');
    }

} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
