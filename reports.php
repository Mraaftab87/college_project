<?php
session_start();
include 'functions.php';
requirePermission('view_reports');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reports & Analytics - Smart Inventory System</title>
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
            <?php if (hasPermission('view_transactions')): ?>
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
            <?php endif; ?>
            <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
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
            <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
            <p>Generate comprehensive reports and analyze your inventory performance</p>
        </div>

        <?php
        include 'db.php';

        // Get selected month/year for reports
        $selected_month = $_GET['month'] ?? date('Y-m');
        $current_month = date('Y-m');

        // Parse month and year
        $year = substr($selected_month, 0, 4);
        $month = substr($selected_month, 5, 2);

        // Get month name
        $month_name = date('F Y', strtotime("$year-$month-01"));

        // Get current inventory value
        $inventory_stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_products,
                SUM(quantity) as total_items,
                SUM(quantity * cost_price) as total_cost_value,
                SUM(quantity * price) as total_sale_value
            FROM products
        ");
        $inventory_stmt->execute();
        $inventory_data = $inventory_stmt->get_result()->fetch_assoc();

        // Get monthly transactions
        $transactions_stmt = $conn->prepare("
            SELECT 
                type,
                SUM(quantity) as total_quantity,
                SUM(total_amount) as total_amount,
                COUNT(*) as transaction_count
            FROM transactions 
            WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?
            GROUP BY type
        ");
        $transactions_stmt->bind_param("s", $selected_month);
        $transactions_stmt->execute();
        $transactions_result = $transactions_stmt->get_result();

        $monthly_data = [
            'buy' => ['quantity' => 0, 'amount' => 0, 'count' => 0],
            'sell' => ['quantity' => 0, 'amount' => 0, 'count' => 0]
        ];

        while ($row = $transactions_result->fetch_assoc()) {
            $monthly_data[$row['type']] = [
                'quantity' => $row['total_quantity'],
                'amount' => $row['total_amount'],
                'count' => $row['transaction_count']
            ];
        }

        // Calculate profit/loss
        $total_buy_amount = $monthly_data['buy']['amount'];
        $total_sell_amount = $monthly_data['sell']['amount'];
        $gross_profit = $total_sell_amount - $total_buy_amount;
        $profit_margin = $total_sell_amount > 0 ? ($gross_profit / $total_sell_amount) * 100 : 0;

        // Get top selling products
        $top_products_stmt = $conn->prepare("
            SELECT 
                p.name,
                p.sku,
                SUM(t.quantity) as total_sold,
                SUM(t.total_amount) as total_revenue
            FROM transactions t
            JOIN products p ON t.product_id = p.id
            WHERE t.type = 'sell' AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            GROUP BY t.product_id
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $top_products_stmt->bind_param("s", $selected_month);
        $top_products_stmt->execute();
        $top_products = $top_products_stmt->get_result();

        // Get low stock products
        $low_stock_stmt = $conn->prepare("
            SELECT name, sku, quantity, reorder_level
            FROM products
            WHERE quantity <= reorder_level
            ORDER BY quantity ASC
        ");
        $low_stock_stmt->execute();
        $low_stock_products = $low_stock_stmt->get_result();
        ?>

        <!-- Month Selector -->
        <div class="form-section">
            <form method="GET" class="inline-form">
                <div class="form-group">
                    <label>Select Month:</label>
                    <input type="month" name="month" value="<?= $selected_month ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <div class="content-grid">
            <!-- Summary Cards -->
            <div class="summary-cards">
                <a class="summary-card clickable" href="view_products.php" style="text-decoration:none; color:inherit;">
                    <div class="card-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-content">
                        <h3><?= number_format($inventory_data['total_products']) ?></h3>
                        <p>Total Products</p>
                    </div>
                </a>

                <a class="summary-card clickable" href="view_products.php" style="text-decoration:none; color:inherit;">
                    <div class="card-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="card-content">
                        <h3><?= number_format($inventory_data['total_items']) ?></h3>
                        <p>Total Items</p>
                    </div>
                </a>

                <a class="summary-card clickable" href="reports.php#monthly-report" style="text-decoration:none; color:inherit;">
                    <div class="card-icon">
                        <i class="fas fa-indian-rupee-sign"></i>
                    </div>
                    <div class="card-content">
                        <h3><?= formatCurrency($inventory_data['total_cost_value']) ?></h3>
                        <p>Inventory Value (Cost)</p>
                    </div>
                </a>

                <a class="summary-card clickable" href="reports.php#monthly-report" style="text-decoration:none; color:inherit;">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-content">
                        <h3><?= formatCurrency($inventory_data['total_sale_value']) ?></h3>
                        <p>Inventory Value (Sale)</p>
                    </div>
                </a>
            </div>

            <!-- Monthly Report -->
            <div class="form-section" id="monthly-report">
                <h3><i class="fas fa-calendar-alt"></i> Monthly Report - <?= $month_name ?></h3>
                
                <div class="report-grid">
                    <div class="report-card">
                        <h4>Purchase Summary</h4>
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Purchases:</span>
                                <span class="stat-value"><?= number_format($monthly_data['buy']['count']) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Items Bought:</span>
                                <span class="stat-value"><?= number_format($monthly_data['buy']['quantity']) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Spent:</span>
                                <span class="stat-value"><?= formatCurrency($monthly_data['buy']['amount']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="report-card">
                        <h4>Sales Summary</h4>
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Sales:</span>
                                <span class="stat-value"><?= number_format($monthly_data['sell']['count']) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Items Sold:</span>
                                <span class="stat-value"><?= number_format($monthly_data['sell']['quantity']) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Revenue:</span>
                                <span class="stat-value"><?= formatCurrency($monthly_data['sell']['amount']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="report-card profit-loss">
                        <h4>Profit & Loss</h4>
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Gross Profit:</span>
                                <span class="stat-value <?= $gross_profit >= 0 ? 'positive' : 'negative' ?>">
                                    <?= formatCurrency($gross_profit) ?>
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Profit Margin:</span>
                                <span class="stat-value <?= $profit_margin >= 0 ? 'positive' : 'negative' ?>">
                                    <?= number_format($profit_margin, 1) ?>%
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Status:</span>
                                <span class="stat-value <?= $gross_profit >= 0 ? 'positive' : 'negative' ?>">
                                    <?= $gross_profit >= 0 ? 'Profit' : 'Loss' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="form-section">
                <h3><i class="fas fa-trophy"></i> Top Selling Products - <?= $month_name ?></h3>
                
                <?php if ($top_products->num_rows == 0): ?>
                    <p class="no-data">No sales recorded for this month.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Quantity Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                while ($product = $top_products->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?= $rank <= 3 ? $rank : 'other' ?>">
                                                <?= $rank ?>
                                            </span>
                                        </td>
                                        <td><strong><?= h($product['name']) ?></strong></td>
                                        <td><?= h($product['sku']) ?></td>
                                        <td><?= number_format($product['total_sold']) ?></td>
                                        <td><strong><?= formatCurrency($product['total_revenue']) ?></strong></td>
                                    </tr>
                                <?php 
                                    $rank++;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Low Stock Alert -->
            <div class="form-section">
                <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>
                
                <?php if ($low_stock_products->num_rows == 0): ?>
                    <p class="no-data">All products are above reorder level.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $low_stock_products->fetch_assoc()): ?>
                                    <tr class="<?= $product['quantity'] == 0 ? 'out-of-stock' : 'low-stock' ?>">
                                        <td><strong><?= h($product['name']) ?></strong></td>
                                        <td><?= h($product['sku']) ?></td>
                                        <td><?= $product['quantity'] ?></td>
                                        <td><?= $product['reorder_level'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= $product['quantity'] == 0 ? 'danger' : 'warning' ?>">
                                                <?= $product['quantity'] == 0 ? 'Out of Stock' : 'Low Stock' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
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
