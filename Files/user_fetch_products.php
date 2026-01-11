<?php

header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

// ---- FETCH PRODUCTS ----
$sql = "SELECT product_id, name, description, price, stock, image, published, created_at 
        FROM products 
        WHERE published = 1
        ORDER BY created_at DESC";

$result = $mysqli->query($sql);
$products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Make sure the image path is relative to the web root
        // If your HTML page is in the same folder as "uploads", this works:
        $row["image"] = $row["image"]; // already stored as "uploads/filename.ext"

        $products[] = $row;
    }
}

echo json_encode($products);
$mysqli->close();
?>
