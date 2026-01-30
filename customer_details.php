<?php
session_start();
include 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Details - Smart Inventory System</title>
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
            <h1><i class="fas fa-user"></i> Customer Details</h1>
            <p>Complete customer information and transaction history</p>
        </div>

        <?php
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            setFlashMessage('error', 'Invalid customer ID.');
            header("Location: customers.php");
            exit;
        }

        $customer_id = $_GET['id'];

        // Get customer details
        $stmt = $conn->prepare("
            SELECT c.*, 
                   COUNT(t.id) as total_transactions,
                   SUM(CASE WHEN t.type = 'sell' THEN t.total_amount ELSE 0 END) as total_spent,
                   MAX(t.transaction_date) as last_purchase_date
            FROM customers c
            LEFT JOIN transactions t ON c.id = t.customer_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();

        if (!$customer) {
            setFlashMessage('error', 'Customer not found.');
            header("Location: customers.php");
            exit;
        }

        // Get recent transactions
        $transactions_stmt = $conn->prepare("
            SELECT t.*, p.name as product_name
            FROM transactions t
            LEFT JOIN products p ON t.product_id = p.id
            WHERE t.customer_id = ?
            ORDER BY t.transaction_date DESC
            LIMIT 10
        ");
        $transactions_stmt->bind_param("i", $customer_id);
        $transactions_stmt->execute();
        $recent_transactions = $transactions_stmt->get_result();
        ?>

        <div class="form-container">
            <div class="form-section">
                <div class="customer-header">
                    <div class="customer-info">
                        <h2><?= h($customer['full_name']) ?></h2>
                        <div class="customer-meta">
                            <span class="badge badge-<?= getCustomerTypeBadge($customer['customer_type']) ?>">
                                <?= ucfirst($customer['customer_type']) ?>
                            </span>
                            <?php if ($customer['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="customer-actions">
                        <a href="edit_customer.php?id=<?= $customer['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Customer
                        </a>
                        <a href="customer_transactions.php?id=<?= $customer['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-exchange-alt"></i> View All Transactions
                        </a>
                        <a href="customers.php" class="btn btn-info">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                </div>

                <div class="customer-details-grid">
                    <div class="detail-section">
                        <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Email:</label>
                                <span><?= h($customer['email']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Phone:</label>
                                <span><?= h($customer['phone']) ?></span>
                            </div>
                            <?php if ($customer['address']): ?>
                                <div class="detail-item">
                                    <label>Address:</label>
                                    <span><?= h($customer['address']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($customer['city']): ?>
                                <div class="detail-item">
                                    <label>City:</label>
                                    <span><?= h($customer['city']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($customer['state']): ?>
                                <div class="detail-item">
                                    <label>State:</label>
                                    <span><?= h($customer['state']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($customer['pincode']): ?>
                                <div class="detail-item">
                                    <label>PIN Code:</label>
                                    <span><?= h($customer['pincode']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-chart-line"></i> Business Information</h3>
                        <div class="detail-grid">
                            <?php if ($customer['gst_number']): ?>
                                <div class="detail-item">
                                    <label>GST Number:</label>
                                    <span><?= h($customer['gst_number']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($customer['credit_limit']): ?>
                                <div class="detail-item">
                                    <label>Credit Limit:</label>
                                    <span><?= formatCurrency($customer['credit_limit']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <label>Total Spent:</label>
                                <span class="highlight"><?= formatCurrency($customer['total_spent'] ?? 0) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Total Transactions:</label>
                                <span class="highlight"><?= number_format($customer['total_transactions']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Last Purchase:</label>
                                <span>
                                    <?php if ($customer['last_purchase_date']): ?>
                                        <?= date('M d, Y', strtotime($customer['last_purchase_date'])) ?>
                                    <?php else: ?>
                                        No purchases yet
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Customer Since:</label>
                                <span><?= date('M d, Y', strtotime($customer['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($customer['notes']): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                        <div class="notes-content">
                            <?= nl2br(h($customer['notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($recent_transactions->num_rows > 0): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $transaction['type'] === 'buy' ? 'success' : 'primary' ?>">
                                                    <?= ucfirst($transaction['type']) ?>
                                                </span>
                                            </td>
                                            <td><?= h(isset($transaction['product_name']) ? $transaction['product_name'] : 'N/A') ?></td>
                                            <td><?= number_format($transaction['quantity']) ?></td>
                                            <td><?= formatCurrency($transaction['total_amount']) ?></td>
                                            <td>
                                                <span class="badge badge-success">Completed</span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center" style="margin-top: 15px;">
                            <a href="customer_transactions.php?id=<?= $customer['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-list"></i> View All Transactions
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <style>
        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .customer-info h2 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        .customer-meta {
            display: flex;
            gap: 10px;
        }

        .customer-actions {
            display: flex;
            gap: 10px;
        }

        .customer-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .detail-section h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .detail-grid {
            display: grid;
            gap: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
        }

        .detail-item span {
            color: #2c3e50;
        }

        .highlight {
            font-weight: bold;
            color: #27ae60 !important;
        }

        .notes-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }

        @media (max-width: 768px) {
            .customer-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .customer-details-grid {
                grid-template-columns: 1fr;
            }

            .customer-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</body>
</html>

<?php
function getCustomerTypeBadge($type) {
    switch ($type) {
        case 'vip': return 'warning';
        case 'wholesale': return 'primary';
        case 'corporate': return 'info';
        default: return 'secondary';
    }
}
?>
