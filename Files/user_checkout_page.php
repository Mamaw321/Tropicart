<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php'; // your database connection

// Handle POST request for checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_submit'])) {

    $full_name = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $street_address = trim($_POST['streetAddress']);
    $city = trim($_POST['city']);
    $zip = trim($_POST['zipCode']);
    $delivery_type = $_POST['delivery_type'];
    $payment_method = $_POST['payment_method'];
    $cart = $_SESSION['cart'] ?? [];

    if (!$cart) {
        die("Your cart is empty.");
    }

    $total_amount = 0;

    foreach ($cart as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $stmt = $conn->prepare("SELECT stock, price, name FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) die("Product {$item['name']} does not exist.");
        if ($product['stock'] < $quantity) die("Not enough stock for product: {$product['name']}");

        $total_amount += $product['price'] * $quantity;
    }

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (full_name, email, phone, street_address, city, zip, payment_method, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssd", $full_name, $email, $phone, $street_address, $city, $zip, $payment_method, $total_amount);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order items and update stock
    $stmt_item = $conn->prepare("INSERT INTO order_items (orders_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");

    foreach ($cart as $item) {
        $product_id = $item['product_id'];
        $product_name = $item['name'];
        $quantity = $item['quantity'];

        $stmt_price = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
        $stmt_price->bind_param("i", $product_id);
        $stmt_price->execute();
        $price = $stmt_price->get_result()->fetch_assoc()['price'];
        $stmt_price->close();

        $subtotal = $price * $quantity;

        $stmt_item->bind_param("iisidd", $order_id, $product_id, $product_name, $quantity, $price, $subtotal);
        $stmt_item->execute();

        $stmt_update_stock->bind_param("ii", $quantity, $product_id);
        $stmt_update_stock->execute();
    }

    $stmt_item->close();
    $stmt_update_stock->close();

    // Clear cart
    $_SESSION['cart'] = [];

    $success_msg = "Order placed successfully! Your Order ID is #$order_id";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Checkout - Tropicart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* Entire CSS from your original code (unchanged) */
        :root { --primary-color:#313133;--accent-pink:#febaba;--accent-light:#fbe5e5;--accent-dark:#cc8484;--border-color:#e0e0e0;--background-page:#f5f5f5;--background-card:#ffffff;--text-muted:#6c757d;--text-dark:#2a3342;--success-green:#4CAF50;--success-light:#e6f7e6;--shadow-light:0 4px 10px rgba(0,0,0,.05); }
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
        body{background-color:var(--background-page);color:var(--text-dark);min-height:100vh}
        .page-header{max-width:1200px;margin:0 auto;padding:30px 20px;display:flex;justify-content:space-between;align-items:center}
        .logo{font-size:28px;font-weight:700;color:var(--primary-color)}
        .step-tracker{display:flex;align-items:center;gap:15px}
        .step-tracker>div{display:flex;align-items:center;color:var(--text-muted);font-weight:500}
        .step-icon{width:25px;height:25px;display:flex;align-items:center;justify-content:center;border-radius:50%;margin-right:5px;font-size:14px;font-weight:600;background-color:var(--border-color);color:var(--text-dark)}
        .step-tracker .completed{color:var(--success-green)} .step-tracker .completed .step-icon{background-color:var(--success-green);color:white}
        .step-tracker .active{color:var(--primary-color)} .step-tracker .active .step-icon{background-color:var(--accent-dark);color:white}
        .checkout-container{max-width:1200px;margin:20px auto 50px;padding:20px;display:grid;grid-template-columns:2fr 1.5fr;gap:40px;background:var(--background-card);border-radius:8px;box-shadow:var(--shadow-light)}
        .shipping-info h2{font-size:24px;font-weight:600;margin-bottom:30px}
        .delivery-options{display:flex;gap:20px;margin-bottom:30px}
        .delivery-option{flex:1;padding:15px;border:1px solid var(--border-color);border-radius:8px;display:flex;align-items:center;gap:10px;font-weight:500;cursor:pointer;transition:all .2s}
        .delivery-option input[type="radio"]{appearance:none;width:18px;height:18px;border:2px solid var(--border-color);border-radius:50%;position:relative}
        .delivery-option input[type="radio"]:checked{border-color:var(--accent-dark);background-color:var(--accent-dark)}
        .delivery-option.selected{border-color:var(--accent-dark);background-color:var(--accent-light);color:var(--text-dark)}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;font-size:14px;font-weight:500;color:var(--text-dark);margin-bottom:5px}
        .form-group label.required::after{content:"*";color:var(--accent-dark);margin-left:5px}
        .form-row{display:flex;gap:15px}
        .form-row .form-group{flex:1}
        input:not([type="checkbox"]):not([type="radio"]),select{width:100%;padding:12px 15px;border:1px solid var(--border-color);border-radius:8px;font-size:15px;color:var(--text-dark)}
        input:focus,select:focus{outline:none;border-color:var(--accent-dark);box-shadow:0 0 0 1px var(--accent-pink)}
        .terms-checkbox{display:flex;align-items:center;font-size:14px;color:var(--text-muted);margin-top:10px}
        .terms-checkbox input{margin-right:8px;width:16px;height:16px}
        .pay-button{width:100%;padding:15px;background-color:var(--accent-dark);color:white;border:none;border-radius:8px;font-size:18px;font-weight:600;cursor:pointer;transition:background-color 0.2s}
        .pay-button:hover{background-color:#996c6c}
        .pay-button:disabled{background-color:#ccc;cursor:not-allowed}
        @media (max-width:992px){.checkout-container{grid-template-columns:1fr;gap:30px;padding:20px}}
    </style>
</head>
<body>

<header class="page-header">
    <div class="logo">Tropicart</div>
    <div class="step-tracker">
        <div class="completed"><i data-feather="check-circle" class="step-icon"></i> Cart</div>
        <div class="completed"><i data-feather="check-circle" class="step-icon"></i> Review</div>
        <div class="active"><span class="step-icon">3</span> Checkout</div>
    </div>
</header>

<div class="checkout-container">
    <div class="shipping-info">
        <h2>Checkout</h2>
        <?php if(isset($success_msg)) echo "<p style='color:green;font-weight:bold;'>$success_msg</p>"; ?>
        <form method="POST" id="checkoutForm">
            <div class="form-group">
                <label for="fullName" class="required">Full name</label>
                <input type="text" id="fullName" name="fullName" placeholder="Enter full name" required>
            </div>
            <div class="form-group">
                <label for="email" class="required">Email address</label>
                <input type="email" id="email" name="email" placeholder="Enter email address" required>
            </div>
            <div class="form-group">
                <label for="phone" class="required">Phone number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="city" class="required">City</label>
                    <input type="text" id="city" name="city" placeholder="Enter city" required>
                </div>
                <div class="form-group">
                    <label for="streetAddress" class="required">Full Address</label>
                    <input type="text" id="streetAddress" name="streetAddress" placeholder="House number, Street, Barangay" required>
                </div>
                <div class="form-group">
                    <label for="zipCode" class="required">ZIP Code</label>
                    <input type="text" id="zipCode" name="zipCode" placeholder="Enter ZIP code" required>
                </div>
            </div>

            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="card">Credit / Debit Card</option>
                    <option value="gcash">GCash</option>
                    <option value="cod">Cash on Delivery</option>
                </select>
            </div>

            <button type="submit" name="checkout_submit" class="pay-button">Pay Now</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    feather.replace();
});
</script>
</body>
</html>
