<?php

header('Content-Type: application/json'); // tell the browser it's JSON

require 'db.php'; // include your database connection

try {
    // Fetch all products
    $result = $mysqli->query("SELECT * FROM products ORDER BY product_id DESC");
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Optional: make published a boolean for JS
        $row['published'] = (bool)$row['published'];
        $products[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'products' => $products
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
