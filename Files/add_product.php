<?php
// add_product.php - Add New Product Page

// 1. START SESSION (Critical for connecting with inventory)
session_start();

// --- FIXED SYSTEM TIME REFERENCE (Not used here, but good practice) ---
$current_time = strtotime("2025-11-16 12:27:23");

// --- ROLE DEFINITIONS ---
define('ROLE_ADMIN', 1);
define('ROLE_PHARMACIST', 2);
define('ROLE_CASHIER', 3);

// 2. ACCESS CONTROL (Same as inventory.php)
// Only Admins and Pharmacists can add products
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role_id"] == ROLE_CASHIER) {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: login.php");
    } else {
        header("location: dashboard.php?access_denied=add_product");
    }
    exit;
}

// Get User Info for Sidebar
$user_role_id = $_SESSION["role_id"] ?? ROLE_ADMIN;
$user_username = htmlspecialchars($_SESSION["username"] ?? "Guest User");

// Role-based styling (Copied from inventory.php for consistency)
$role_name = "Inventory Manager"; 
$role_color = "#6c757d"; 
$role_color_hover = "#495057";

switch ($user_role_id) {
    case ROLE_ADMIN:
        $role_name = "Administrator";
        $role_color = "#dc3545"; 
        $role_color_hover = "#c82333";
        break;
    case ROLE_PHARMACIST:
        $role_name = "Pharmacist";
        $role_color = "#007bff";
        $role_color_hover = "#0056b3";
        break;
}

// 3. FORM PROCESSING LOGIC
$name = $category = $expiry = "";
$store_a_amount = $store_b_amount = 0.00;
$store_a_stock = $store_b_stock = 0;
$errors = [];

// Helper function to generate a new, unique ID
function get_new_product_id($inventory) {
    if (empty($inventory)) {
        return 1001; // Starting ID
    }
    $max_id = 0;
    foreach ($inventory as $item) {
        if ($item['id'] > $max_id) {
            $max_id = $item['id'];
        }
    }
    return $max_id + 1;
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- VALIDATION ---
    $name = trim($_POST['name']);
    if (empty($name)) {
        $errors['name'] = "Product name is required.";
    }

    $category = trim($_POST['category']);
    if (empty($category)) {
        $errors['category'] = "Category is required.";
    }

    $store_a_amount = trim($_POST['store_a_amount']);
    if (!is_numeric($store_a_amount) || $store_a_amount < 0) {
        $errors['store_a_amount'] = "Store A price must be a valid number (0 or more).";
    }

    $store_b_amount = trim($_POST['store_b_amount']);
    if (!is_numeric($store_b_amount) || $store_b_amount < 0) {
        $errors['store_b_amount'] = "Store B price must be a valid number (0 or more).";
    }

    $store_a_stock = trim($_POST['store_a_stock']);
    if (!filter_var($store_a_stock, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]) && $store_a_stock !== '0') {
        $errors['store_a_stock'] = "Store A stock must be a valid whole number (0 or more).";
    }

    $store_b_stock = trim($_POST['store_b_stock']);
    if (!filter_var($store_b_stock, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]) && $store_b_stock !== '0') {
        $errors['store_b_stock'] = "Store B stock must be a valid whole number (0 or more).";
    }
    
    $expiry = trim($_POST['expiry']);
    if (empty($expiry)) {
        $errors['expiry'] = "Expiry date is required.";
    } elseif (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $expiry)) {
        // Basic YYYY-MM-DD format check
        $errors['expiry'] = "Invalid date format. Please use YYYY-MM-DD.";
    }

    // --- PROCESS IF NO ERRORS ---
    if (empty($errors)) {
        // Ensure inventory session exists (though inventory.php should have)
        if (!isset($_SESSION['inventory_data'])) {
            $_SESSION['inventory_data'] = [];
        }

        // Get the new ID
        $new_id = get_new_product_id($_SESSION['inventory_data']);

        // Create the new product array
        $new_product = [
            'id' => $new_id,
            'name' => $name,
            'category' => $category,
            'store_a_amount' => (float)$store_a_amount,
            'store_b_amount' => (float)$store_b_amount,
            'store_a_stock' => (int)$store_a_stock,
            'store_b_stock' => (int)$store_b_stock,
            'expiry' => $expiry,
        ];

        // Add the new product to the session array
        $_SESSION['inventory_data'][] = $new_product;

        // Redirect back to inventory page with success message
        header("location: inventory.php?action=added");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCMS | Add Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-color: <?php echo $role_color; ?>;
            --sidebar-color-dark: <?php echo $role_color_hover; ?>;
            --bg-color: #eef1f6;
            --text-color: #343a40;
            --white: #ffffff;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;
            --info: #17a2b8;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); color: var(--text-color); margin: 0; display: flex; min-height: 100vh; }

        /* --- SIDEBAR STYLES --- */
        .sidebar { 
            width: 250px; 
            background-color: var(--white); 
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            display: flex; 
            flex-direction: column; 
            position: fixed; 
            height: 100%; 
        }
        .sidebar-header { padding: 25px 30px 15px; text-align: center; }
        .sidebar-header h1 { font-size: 2em; color: var(--sidebar-color); font-weight: 900; margin: 0; letter-spacing: 1px;}
        .sidebar-header p { font-size: 0.8em; color: #a0a0a0; margin: 0; font-weight: 500; }
        .sidebar-user { padding: 20px 25px; margin: 15px 15px 25px 15px; border-radius: 8px; background-color: #f1f3f5; border: 1px solid #e9ecef; text-align: center; }
        .sidebar-user .role { font-weight: 600; color: #6c757d; font-size: 0.85em; margin-bottom: 2px; }
        .sidebar-user .name { font-weight: 700; font-size: 1em; color: var(--text-color); }
        .nav-menu { flex-grow: 1; padding: 0 15px; }
        .nav-menu a { display: flex; align-items: center; padding: 12px 15px; color: #6c757d; text-decoration: none; font-weight: 600; transition: all 0.2s; border-radius: 6px; margin-bottom: 5px; }
        .nav-menu a:hover { background-color: #e9ecef; color: var(--text-color); }
        .nav-menu a.active { background-color: var(--sidebar-color); color: var(--white); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .nav-menu a.active:hover { background-color: var(--sidebar-color); color: var(--white); }
        .nav-menu a i { width: 20px; margin-right: 15px; text-align: center; }
        .nav-section-title { color: #a0a0a0; font-size: 0.8em; font-weight: 700; padding: 20px 15px 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .logout-container { padding: 15px; border-top: 1px solid #e9ecef; }
        .logout-btn { width: 100%; padding: 12px; background-color: var(--danger); color: var(--white); border: none; border-radius: 6px; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; display: block; transition: background-color 0.2s; }
        .logout-btn:hover { background-color: #c82333; }

        /* --- MAIN CONTENT & HEADER --- */
        .main-content { margin-left: 250px; flex-grow: 1; padding: 40px; }
        .page-header { display: flex; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e9ecef; padding-bottom: 15px; }
        .header-title h2 { font-size: 2em; font-weight: 700; color: var(--sidebar-color); margin: 0; white-space: nowrap; }

        /* --- NEW FORM STYLES --- */
        .form-container {
            background-color: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            max-width: 900px;
            margin: 0 auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"] {
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95em;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--sidebar-color);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            border-top: 1px solid #e9ecef;
            padding-top: 25px;
        }
        .btn { padding: 12px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; color: white; border: none; cursor: pointer; }
        .btn-primary { background-color: var(--sidebar-color); }
        .btn-primary:hover { background-color: var(--sidebar-color-dark); }
        .btn-cancel { background-color: #adb5bd; }
        .btn-cancel:hover { background-color: #6c757d; }
        .error-msg {
            color: var(--danger);
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 5px;
        }
        .form-group input.has-error {
            border-color: var(--danger);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 20px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h1>PCMS</h1>
            <p>Pharmacy Control System</p>
        </div>
        
        <div class="sidebar-user">
            <p class="role"><?php echo $role_name; ?></p>
            <p class="name"><?php echo $user_username; ?></p>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            
            <div class="nav-section-title">Operations</div>
            <a href="inventory.php" class="active"><i class="fas fa-boxes"></i> Inventory Management</a>
            <a href="supplier.php"><i class="fas fa-truck-loading"></i> Supplier Management</a>
            <?php if ($user_role_id != ROLE_PHARMACIST): ?>
            <a href="#"><i class="fas fa-cash-register"></i> Point of Sale</a>
            <?php endif; ?>
        </nav>
        
        <div class="logout-container"><a href="dashboard.php?action=logout" class="logout-btn">Log Out</a></div>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <div class="header-title"><h2><i class="fas fa-plus-circle"></i> Add New Product</h2></div>
        </div>

        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">

                    <div class="form-group full-width">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" class="<?php echo !empty($errors['name']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['name'])): ?><span class="error-msg"><?php echo $errors['name']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>" class="<?php echo !empty($errors['category']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['category'])): ?><span class="error-msg"><?php echo $errors['category']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="store_a_amount">Unit Price (Store A)</label>
                        <input type="number" id="store_a_amount" name="store_a_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($store_a_amount); ?>" class="<?php echo !empty($errors['store_a_amount']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['store_a_amount'])): ?><span class="error-msg"><?php echo $errors['store_a_amount']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="store_b_amount">Unit Price (Store B)</label>
                        <input type="number" id="store_b_amount" name="store_b_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($store_b_amount); ?>" class="<?php echo !empty($errors['store_b_amount']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['store_b_amount'])): ?><span class="error-msg"><?php echo $errors['store_b_amount']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="store_a_stock">Stock (Store A)</label>
                        <input type="number" id="store_a_stock" name="store_a_stock" step="1" min="0" value="<?php echo htmlspecialchars($store_a_stock); ?>" class="<?php echo !empty($errors['store_a_stock']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['store_a_stock'])): ?><span class="error-msg"><?php echo $errors['store_a_stock']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="store_b_stock">Stock (Store B)</label>
                        <input type="number" id="store_b_stock" name="store_b_stock" step="1" min="0" value="<?php echo htmlspecialchars($store_b_stock); ?>" class="<?php echo !empty($errors['store_b_stock']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['store_b_stock'])): ?><span class="error-msg"><?php echo $errors['store_b_stock']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="expiry">Expiry Date</label>
                        <input type="date" id="expiry" name="expiry" value="<?php echo htmlspecialchars($expiry); ?>" class="<?php echo !empty($errors['expiry']) ? 'has-error' : ''; ?>">
                        <?php if (!empty($errors['expiry'])): ?><span class="error-msg"><?php echo $errors['expiry']; ?></span><?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <a href="inventory.php" class="btn btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
                    </div>

                </div>
            </form>
        </div>

    </div>

    </body>
</html>