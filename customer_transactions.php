<?php
// Error suppression for production
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
include 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Transactions - Smart Inventory System</title>
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
            <h1><i class="fas fa-exchange-alt"></i> Customer Transactions</h1>
            <p>Complete transaction history for customer</p>
        </div>

        <?php
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            setFlashMessage('error', 'Invalid customer ID.');
            header("Location: customers.php");
            exit;
        }

        $customer_id = (int)$_GET['id'];

        // Get customer details
        $customer_stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $customer_stmt->bind_param("i", $customer_id);
        $customer_stmt->execute();
        $customer_result = $customer_stmt->get_result();
        $customer = $customer_result->fetch_assoc();

        if (!$customer) {
            setFlashMessage('error', 'Customer not found.');
            header("Location: customers.php");
            exit;
        }

        // Get transaction filters
        $type_filter = isset($_GET['type']) ? $_GET['type'] : '';
        $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

        // Build query with filters
        $where_conditions = array("t.customer_id = ?");
        $params = array($customer_id);
        $types = 'i';

        if (!empty($type_filter)) {
            $where_conditions[] = "t.type = ?";
            $params[] = $type_filter;
            $types .= 's';
        }

        if (!empty($date_from)) {
            $where_conditions[] = "DATE(t.transaction_date) >= ?";
            $params[] = $date_from;
            $types .= 's';
        }

        if (!empty($date_to)) {
            $where_conditions[] = "DATE(t.transaction_date) <= ?";
            $params[] = $date_to;
            $types .= 's';
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get transactions
        $transactions_query = "
            SELECT t.*, p.name as product_name, p.sku, p.barcode
            FROM transactions t
            LEFT JOIN products p ON t.product_id = p.id
            $where_clause
            ORDER BY t.transaction_date DESC
        ";

        $transactions_stmt = $conn->prepare($transactions_query);
        if (!empty($params)) {
            $refs = array();
            foreach($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }
            call_user_func_array(array($transactions_stmt, 'bind_param'), array_merge(array($types), $refs));
        }
        $transactions_stmt->execute();
        $transactions = $transactions_stmt->get_result();

        // Get transaction summary
        $summary_query = "
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN type = 'buy' THEN total_amount ELSE 0 END) as total_bought,
                SUM(CASE WHEN type = 'sell' THEN total_amount ELSE 0 END) as total_sold,
                SUM(CASE WHEN type = 'buy' THEN quantity ELSE 0 END) as total_bought_qty,
                SUM(CASE WHEN type = 'sell' THEN quantity ELSE 0 END) as total_sold_qty
            FROM transactions t
            $where_clause
        ";

        $summary_stmt = $conn->prepare($summary_query);
        if (!empty($params)) {
            $refs = array();
            foreach($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }
            call_user_func_array(array($summary_stmt, 'bind_param'), array_merge(array($types), $refs));
        }
        $summary_stmt->execute();
        $summary = $summary_stmt->get_result()->fetch_assoc();
        ?>

        <div class="form-container">
            <div class="form-section">
                <div class="customer-header">
                    <div class="customer-info">
                        <h2><?php echo htmlspecialchars($customer['full_name']); ?></h2>
                        <div class="customer-meta">
                            <span><?php echo htmlspecialchars($customer['email']); ?></span>
                            <span><?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                    </div>
                    <div class="customer-actions">
                        <a href="customer_details.php?id=<?php echo $customer['id']; ?>" class="btn btn-info">
                            <i class="fas fa-user"></i> Customer Details
                        </a>
                        <a href="customers.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                </div>

                <!-- Transaction Summary -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Total Transactions</h4>
                        <div class="summary-value"><?php echo number_format($summary['total_transactions'] ?: 0); ?></div>
                    </div>
                    <div class="summary-card">
                        <h4>Total Purchased</h4>
                        <div class="summary-value"><?php echo formatCurrency($summary['total_bought'] ?: 0); ?></div>
                        <div class="summary-sub"><?php echo number_format($summary['total_bought_qty'] ?: 0); ?> items</div>
                    </div>
                    <div class="summary-card">
                        <h4>Total Sold</h4>
                        <div class="summary-value"><?php echo formatCurrency($summary['total_sold'] ?: 0); ?></div>
                        <div class="summary-sub"><?php echo number_format($summary['total_sold_qty'] ?: 0); ?> items</div>
                    </div>
                    <div class="summary-card">
                        <h4>Net Amount</h4>
                        <div class="summary-value <?php echo (($summary['total_sold'] ?: 0) - ($summary['total_bought'] ?: 0)) >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo formatCurrency(($summary['total_sold'] ?: 0) - ($summary['total_bought'] ?: 0)); ?>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="search-container">
                    <form class="search-form" method="GET">
                        <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
                        
                        <select name="type" class="category-select">
                            <option value="">All Types</option>
                            <option value="buy" <?php echo $type_filter === 'buy' ? 'selected' : ''; ?>>Buy</option>
                            <option value="sell" <?php echo $type_filter === 'sell' ? 'selected' : ''; ?>>Sell</option>
                        </select>
                        
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="search-input" placeholder="From Date">
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="search-input" placeholder="To Date">
                        
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        
                        <a href="customer_transactions.php?id=<?php echo $customer_id; ?>" class="clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </form>
                </div>

                <!-- Transactions Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions->num_rows > 0): ?>
                                <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $transaction['type'] === 'buy' ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst($transaction['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(isset($transaction['product_name']) ? $transaction['product_name'] : 'N/A'); ?></strong>
                                            <?php if (isset($transaction['barcode']) && $transaction['barcode']): ?>
                                                <br><small>Barcode: <?php echo htmlspecialchars($transaction['barcode']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(isset($transaction['sku']) ? $transaction['sku'] : 'N/A'); ?></td>
                                        <td><?php echo number_format($transaction['quantity'] ?: 0); ?></td>
                                        <td><?php echo formatCurrency($transaction['unit_price'] ?: 0); ?></td>
                                        <td>
                                            <strong><?php echo formatCurrency($transaction['total_amount'] ?: 0); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">Completed</span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="no-data">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
            gap: 20px;
            color: #666;
        }

        .customer-actions {
            display: flex;
            gap: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .summary-card h4 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .summary-value.positive {
            color: #27ae60;
        }

        .summary-value.negative {
            color: #e74c3c;
        }

        .summary-sub {
            font-size: 12px;
            color: #666;
        }

        @media (max-width: 768px) {
            .customer-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .customer-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</body>
</html>