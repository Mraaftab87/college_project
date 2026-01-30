<?php
session_start();
include 'functions.php';
requirePermission('view_transactions'); // Users can view transactions
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transactions - Smart Inventory System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

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
            <a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a>
            <?php if (hasPermission('view_reports')): ?>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <?php if (hasPermission('add_product')): ?>
                <a href="barcode_scanner.php"><i class="fas fa-barcode"></i> Scanner</a>
                <a href="bulk_operations.php"><i class="fas fa-upload"></i> Bulk Operations</a>
            <?php endif; ?>
            <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard">

        <div class="hero">
            <h1><i class="fas fa-exchange-alt"></i> Transaction Management</h1>
            <p>Manage buy and sell transactions for your inventory</p>
        </div>

        <?php
        include 'db.php';

        // Handle transaction actions
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'add_transaction':

                        $product_id = $_POST['product_id'] ?? null;
                        $type = $_POST['type'] ?? null;
                        $quantity = $_POST['quantity'] ?? null;
                        $unit_price = $_POST['unit_price'] ?? null;
                        $transaction_date = $_POST['transaction_date'] ?? null;
                        $notes = $_POST['notes'] ?? '';
                        $user_id = $_SESSION['user_id'] ?? null;

                        // Validate required fields
                        if (!$product_id || !$type || !$quantity || !$unit_price || !$transaction_date || !$user_id) {
                            setFlashMessage('error', 'Error adding transaction: Missing required data. Product: ' . ($product_id ? 'OK' : 'MISSING') . ', Type: ' . ($type ? 'OK' : 'MISSING') . ', Quantity: ' . ($quantity ? 'OK' : 'MISSING') . ', Price: ' . ($unit_price ? 'OK' : 'MISSING') . ', Date: ' . ($transaction_date ? 'OK' : 'MISSING') . ', User ID: ' . ($user_id ? 'OK' : 'MISSING'));
                            break;
                        }

                        $total_amount = $quantity * $unit_price;

                        $stmt = $conn->prepare("INSERT INTO transactions (product_id, type, quantity, unit_price, total_amount, transaction_date, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isiddssi", $product_id, $type, $quantity, $unit_price, $total_amount, $transaction_date, $notes, $user_id);

                        if ($stmt->execute()) {
                            setFlashMessage('success', 'Transaction added successfully!');
                        } else {
                            setFlashMessage('error', 'Error adding transaction: ' . $stmt->error);
                        }
                        break;

                    case 'delete_transaction':
                        $transaction_id = $_POST['transaction_id'];
                        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
                        $stmt->bind_param("i", $transaction_id);

                        if ($stmt->execute()) {
                            setFlashMessage('success', 'Transaction deleted successfully!');
                        } else {
                            setFlashMessage('error', 'Error deleting transaction: ' . $stmt->error);
                        }
                        break;
                }

                header("Location: transactions.php");
                exit;
            }
        }

        // Get flash message
        $flash = getFlashMessage();

        // Get products for dropdown
        $products = [];
        $prod_stmt = $conn->prepare("SELECT id, name, sku FROM products ORDER BY name");
        $prod_stmt->execute();
        $prod_result = $prod_stmt->get_result();
        while ($row = $prod_result->fetch_assoc()) {
            $products[] = $row;
        }

        // Get transactions with product details
        $transactions = [];
        $trans_stmt = $conn->prepare("
            SELECT t.*, p.name as product_name, p.sku, u.username as user_name 
            FROM transactions t 
            JOIN products p ON t.product_id = p.id 
            JOIN users u ON t.user_id = u.id 
            ORDER BY t.transaction_date DESC, t.created_at DESC
        ");
        $trans_stmt->execute();
        $trans_result = $trans_stmt->get_result();
        while ($row = $trans_result->fetch_assoc()) {
            $transactions[] = $row;
        }
        ?>

        <div class="content-grid">
            <!-- Add Transaction Form -->
            <?php if (hasPermission('add_transactions')): ?>
                <div class="form-section">
                    <h3><i class="fas fa-plus"></i> Add New Transaction</h3>

                    <?php if ($flash): ?>
                        <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
                    <?php endif; ?>

                    <form method="POST" class="registration-form">
                        <input type="hidden" name="action" value="add_transaction">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Product: <span class="required">*</span></label>
                                <select name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['id'] ?>"><?= h($product['name']) ?> (<?= h($product['sku']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Transaction Type: <span class="required">*</span></label>
                                <select name="type" required>
                                    <option value="buy">Buy (Purchase)</option>
                                    <option value="sell">Sell (Sale)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Quantity: <span class="required">*</span></label>
                                <input type="number" name="quantity" min="1" required placeholder="Enter quantity">
                            </div>

                            <div class="form-group">
                                <label>Unit Price: <span class="required">*</span></label>
                                <input type="number" name="unit_price" step="0.01" min="0.01" required placeholder="Price per unit">
                            </div>

                            <div class="form-group">
                                <label>Transaction Date: <span class="required">*</span></label>
                                <input type="date" name="transaction_date" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes:</label>
                            <textarea name="notes" rows="2" placeholder="Transaction notes..."></textarea>
                        </div>

                        <div class="form-actions">
                            <?php if (hasPermission('add_transactions')): ?>
                                <input type="submit" value="Add Transaction" class="btn btn-primary">
                            <?php else: ?>
                                <p class="no-data">You don't have permission to add transactions.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Transactions List -->
            <div class="form-section">
                <h3><i class="fas fa-list"></i> Transaction History</h3>

                <?php if (empty($transactions)): ?>
                    <p class="no-data">No transactions found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>User</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr class="<?= $transaction['type'] === 'buy' ? 'buy-row' : 'sell-row' ?>">
                                        <td><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></td>
                                        <td>
                                            <strong><?= h($transaction['product_name']) ?></strong><br>
                                            <small>SKU: <?= h($transaction['sku']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $transaction['type'] === 'buy' ? 'success' : 'primary' ?>">
                                                <?= ucfirst($transaction['type']) ?>
                                            </span>
                                        </td>
                                        <td><?= $transaction['quantity'] ?></td>
                                        <td><?= formatCurrency($transaction['unit_price']) ?></td>
                                        <td><strong><?= formatCurrency($transaction['total_amount']) ?></strong></td>
                                        <td><?= h($transaction['user_name']) ?></td>
                                        <td>
                                            <?php if (hasPermission('delete_transactions')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this transaction?')">
                                                    <input type="hidden" name="action" value="delete_transaction">
                                                    <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Transaction">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="no-data">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

</body>

</html>