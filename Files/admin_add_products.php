<?php

header('Content-Type: application/json'); // ensures JSON response

require 'db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Retrieve POST fields
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $published = isset($_POST['published']) ? (int)$_POST['published'] : 0;

    if (empty($name) || empty($description)) {
        throw new Exception('Name and Description are required.');
    }

    // Handle file upload
    $imagePath = 'uploads/default.png'; // default image
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

    // Insert into database
    $stmt = $mysqli->prepare("INSERT INTO products (name, description, price, stock, image, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param('ssdisi', $name, $description, $price, $stock, $imagePath, $published);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $id = $stmt->insert_id;

        echo json_encode([
            'status' => 'success',
            'product' => [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => number_format((float)$price, 2, '.', ''),
                'stock' => (int)$stock,
                'image' => $imagePath,
                'published' => (bool)$published
            ]
        ]);
    } else {
        throw new Exception('Failed to insert product.');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
