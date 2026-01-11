<?php
// admin_get_shipments.php
require 'db.php';

// Fetch all shipments with order info
$stmt = $pdo->query("
    SELECT s.*, o.customer_name, o.address
    FROM shipments s
    JOIN orders o ON s.order_id = o.orders_id
    ORDER BY s.created_at DESC
");
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($shipments);
