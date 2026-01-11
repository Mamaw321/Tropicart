<?php

header("Content-Type: application/json; charset=UTF-8");
require 'db.php'; // Make sure this connects to your MySQL database

// Get product_id from GET request
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid product ID"
    ]);
    exit;
}

// Fetch product details from database
$sql = "SELECT product_id, name, description, price, stock, image 
        FROM products 
        WHERE product_id = $product_id 
        LIMIT 1";

$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc();

    // Ensure image path is correct
    $product['image'] = $product['image']; // Example: 'uploads/filename.jpg'

    echo json_encode([
        "status" => "success",
        "product" => $product
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Product not found"
    ]);
}

$mysqli->close();
?>
