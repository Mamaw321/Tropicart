<?php
// user_cancel_order.php

// Set headers for JSON response
header('Content-Type: application/json');

// Assuming db.php handles the database connection and might start the session
// You must adjust this path if your db.php is located elsewhere
require_once 'db.php'; 

// --- 1. Basic Request Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Read the JSON data sent from the JavaScript fetch request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$orderId = $data['order_id'] ?? null;

if (empty($orderId) || !is_numeric($orderId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing Order ID.']);
    exit;
}

// --- 2. CRITICAL SECURITY & AUTHORIZATION CHECK ---
// You must uncomment and implement this logic based on your session management.
// This prevents one user from cancelling another user's order.
/*
session_start();
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}
*/

try {
    // Assuming $pdo is the PDO connection object established in db.php
    if (!isset($pdo)) {
        throw new Exception("Database connection not initialized. Check db.php.");
    }

    // Begin a transaction for safety
    $pdo->beginTransaction();

    // --- 3. Check Order Eligibility (Status and Ownership) ---
    // NOTE: If you are using session management, you MUST include user_id in the WHERE clause.
    $sql_select = "SELECT user_id, status FROM orders WHERE orders_id = :order_id FOR UPDATE"; // FOR UPDATE locks the row
    $stmt = $pdo->prepare($sql_select);
    $stmt->execute([':order_id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        // If no order is found, tell the user, but log this if you suspect abuse
        throw new Exception("Order not found or access denied.");
    }
    
    // --- Security Check (UNCOMMENT AND USE) ---
    /*
    if ($order['user_id'] != $currentUserId) {
        throw new Exception("Unauthorized access to order.");
    }
    */

    // Check if the status allows cancellation
    $cancellableStatuses = ['pending', 'processing'];
    if (!in_array($order['status'], $cancellableStatuses)) {
        $pdo->rollBack();
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Order cannot be cancelled. Status is currently: ' . ucfirst($order['status'])]);
        exit;
    }

    // --- 4. Update Status to 'cancelled' ---
    $sql_update = "UPDATE orders SET status = 'cancelled' WHERE orders_id = :order_id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([':order_id' => $orderId]);
    
    // Check if the update was successful
    if ($stmt_update->rowCount() === 0) {
         throw new Exception("Failed to update order status to cancelled.");
    }
    
    // Commit the transaction
    $pdo->commit();

    // --- 5. Success Response ---
    echo json_encode(['success' => true, 'message' => 'Order successfully cancelled.']);

} catch (Exception $e) {
    // Rollback the transaction on failure
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error (optional but recommended)
    error_log("Order Cancellation Error: " . $e->getMessage());

    // Send a generic error response to the client
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred during cancellation.']);
}

?>