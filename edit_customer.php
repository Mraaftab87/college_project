<?php
session_start();
include 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Customer - Smart Inventory System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include 'db.php'; ?>
    
    <header class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; color: white;">
            <i class="fas fa-warehouse fa-2x"></i>
            <span>Smart Inventory System</span>
        </a>
        <nav class="nav-links">
            <?php if (hasPermission('add_product')): ?>
                <a href="add_product.php"><i class="fas fa-plus"></i> Add Product</a>
            <?php endif; ?>
            <a href="view_products.php"><i class="fas fa-boxes"></i> View Products</a>
            <?php if (hasPermission('view_transactions')): ?>
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
            <?php endif; ?>
            <?php if (hasPermission('view_reports')): ?>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <?php if (hasPermission('add_product')): ?>
                <a href="barcode_scanner.php"><i class="fas fa-barcode"></i> Scanner</a>
                <a href="bulk_operations.php"><i class="fas fa-upload"></i> Bulk Operations</a>
            <?php endif; ?>
            <a href="customers.php" class="active"><i class="fas fa-users"></i> Customers</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <div class="hero">
            <h1><i class="fas fa-user-edit"></i> Edit Customer</h1>
            <p>Update customer information and details</p>
        </div>

        <?php
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            setFlashMessage('error', 'Invalid customer ID.');
            header("Location: customers.php");
            exit;
        }

        $customer_id = $_GET['id'];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $customer_type = $_POST['customer_type'];
            $address = trim($_POST['address']);
            $city = trim($_POST['city']);
            $state = trim($_POST['state']);
            $pincode = trim($_POST['pincode']);
            $gst_number = trim($_POST['gst_number']);
            $credit_limit = $_POST['credit_limit'] ?: 0;
            $notes = trim($_POST['notes']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validate required fields
            if (empty($full_name) || empty($email) || empty($phone)) {
                setFlashMessage('error', 'Please fill in all required fields.');
            } else {
                // Check if email already exists for another customer
                $check_email = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
                $check_email->bind_param("si", $email, $customer_id);
                $check_email->execute();
                $email_result = $check_email->get_result();

                if ($email_result->num_rows > 0) {
                    setFlashMessage('error', 'Email address already exists for another customer.');
                } else {
                    // Update customer
                    $stmt = $conn->prepare("
                        UPDATE customers SET 
                            full_name = ?, email = ?, phone = ?, customer_type = ?, 
                            address = ?, city = ?, state = ?, pincode = ?, 
                            gst_number = ?, credit_limit = ?, notes = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssssssssdsii", 
                        $full_name, $email, $phone, $customer_type, 
                        $address, $city, $state, $pincode, 
                        $gst_number, $credit_limit, $notes, $is_active, $customer_id
                    );

                    if ($stmt->execute()) {
                        setFlashMessage('success', 'Customer updated successfully.');
                        header("Location: customer_details.php?id=" . $customer_id);
                        exit;
                    } else {
                        setFlashMessage('error', 'Error updating customer: ' . $stmt->error);
                    }
                }
            }
        }

        // Get customer details
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();

        if (!$customer) {
            setFlashMessage('error', 'Customer not found.');
            header("Location: customers.php");
            exit;
        }

        $flash = getFlashMessage();
        ?>

        <div class="form-container">
            <div class="form-section">
                <h3>Customer Information</h3>
                
                <?php if ($flash): ?>
                    <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
                <?php endif; ?>
                
                <form method="POST" class="customer-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" value="<?= h($customer['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" value="<?= h($customer['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="tel" name="phone" value="<?= h($customer['phone']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Customer Type</label>
                            <select name="customer_type">
                                <option value="regular" <?= $customer['customer_type'] === 'regular' ? 'selected' : '' ?>>Regular</option>
                                <option value="wholesale" <?= $customer['customer_type'] === 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
                                <option value="vip" <?= $customer['customer_type'] === 'vip' ? 'selected' : '' ?>>VIP</option>
                                <option value="corporate" <?= $customer['customer_type'] === 'corporate' ? 'selected' : '' ?>>Corporate</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3"><?= h($customer['address']) ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?= h($customer['city']) ?>">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" value="<?= h($customer['state']) ?>">
                        </div>
                        <div class="form-group">
                            <label>PIN Code</label>
                            <input type="text" name="pincode" value="<?= h($customer['pincode']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" name="gst_number" value="<?= h($customer['gst_number']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Credit Limit (₹)</label>
                            <input type="number" name="credit_limit" step="0.01" min="0" value="<?= $customer['credit_limit'] ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"><?= h($customer['notes']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" <?= $customer['is_active'] ? 'checked' : '' ?>>
                            Active Customer
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Customer
                        </button>
                        <a href="customer_details.php?id=<?= $customer['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="customers.php" class="btn btn-info">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <style>
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: normal;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
    </style>
</body>
</html>

