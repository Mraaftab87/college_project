<?php
session_start();
include 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - Smart Inventory System</title>
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <?php
  include 'db.php';

  // Enhanced Stats
  $total = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];
  $totalItems = $conn->query("SELECT SUM(quantity) AS total FROM products")->fetch_assoc()['total'] ?? 0;
  $totalValue = $conn->query("SELECT SUM(quantity * price) AS total FROM products")->fetch_assoc()['total'] ?? 0;
  $totalCost = $conn->query("SELECT SUM(quantity * cost_price) AS total FROM products")->fetch_assoc()['total'] ?? 0;

  // Low stock items (using reorder level)
  $lowStockCount = $conn->query("SELECT COUNT(*) AS low FROM products WHERE quantity <= reorder_level")->fetch_assoc()['low'];
  $lowStockItems = $conn->query("SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 5");

  // Recent transactions
  $recentTransactions = $conn->query("
    SELECT t.*, p.name as product_name, p.sku 
    FROM transactions t 
    JOIN products p ON t.product_id = p.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
  ");

  // Monthly summary
  $currentMonth = date('Y-m');
  $monthlyStats = $conn->query("
    SELECT 
      SUM(CASE WHEN type = 'buy' THEN total_amount ELSE 0 END) as total_purchases,
      SUM(CASE WHEN type = 'sell' THEN total_amount ELSE 0 END) as total_sales,
      COUNT(CASE WHEN type = 'buy' THEN 1 END) as purchase_count,
      COUNT(CASE WHEN type = 'sell' THEN 1 END) as sales_count
    FROM transactions 
    WHERE DATE_FORMAT(transaction_date, '%Y-%m') = '$currentMonth'
  ")->fetch_assoc();

  $monthlyStats = array_map(function ($value) {
    return $value ?? 0;
  }, $monthlyStats);
  ?>

  <header class="navbar">
    <a href="index.php" class="logo" style="text-decoration: none; color: white; cursor: pointer;" onclick="window.location.href='index.php'; return false;">
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
      <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </header>

  <main class="dashboard">

    <div class="hero">
      <h1><i class="fas fa-warehouse"></i> Welcome, <?= h($_SESSION['username']) ?></h1>
      <p>Role: <strong><?= ucfirst(getUserRole()) ?></strong> | Monitor your inventory performance below</p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
      <a href="view_products.php" class="summary-card clickable">
        <div class="card-icon">
          <i class="fas fa-box"></i>
        </div>
        <div class="card-content">
          <h3><?= number_format($total) ?></h3>
          <p>Total Products</p>
        </div>
      </a>

      <a href="view_products.php" class="summary-card clickable">
        <div class="card-icon">
          <i class="fas fa-cubes"></i>
        </div>
        <div class="card-content">
          <h3><?= number_format($totalItems) ?></h3>
          <p>Total Items</p>
        </div>
      </a>

      <?php if (hasPermission('view_reports')): ?>
        <a href="reports.php" class="summary-card clickable">
          <div class="card-icon">
            <i class="fas fa-rupee-sign"></i>
          </div>
          <div class="card-content">
            <h3><?= formatCurrency($totalValue) ?></h3>
            <p>Inventory Value</p>
          </div>
        </a>

        <a href="reports.php" class="summary-card clickable">
          <div class="card-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="card-content">
            <h3><?= formatCurrency($totalCost) ?></h3>
            <p>Total Cost</p>
          </div>
        </a>
      <?php endif; ?>
    </div>

    <!-- Monthly Overview -->
    <?php if (hasPermission('view_reports')): ?>
      <div class="form-section">
        <h3><i class="fas fa-calendar-alt"></i> Monthly Overview - <?= date('F Y') ?></h3>

        <div class="report-grid">
          <div class="report-card">
            <h4>Purchases</h4>
            <div class="report-stats">
              <div class="stat-item">
                <span class="stat-label">Total Amount:</span>
                <span class="stat-value"><?= formatCurrency($monthlyStats['total_purchases']) ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Transactions:</span>
                <span class="stat-value"><?= number_format($monthlyStats['purchase_count']) ?></span>
              </div>
            </div>
          </div>

          <div class="report-card">
            <h4>Sales</h4>
            <div class="report-stats">
              <div class="stat-item">
                <span class="stat-label">Total Revenue:</span>
                <span class="stat-value"><?= formatCurrency($monthlyStats['total_sales']) ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Transactions:</span>
                <span class="stat-value"><?= number_format($monthlyStats['sales_count']) ?></span>
              </div>
            </div>
          </div>

          <div class="report-card profit-loss">
            <h4>Profit & Loss</h4>
            <div class="report-stats">
              <div class="stat-item">
                <span class="stat-label">Gross Profit:</span>
                <span class="stat-value <?= ($monthlyStats['total_sales'] - $monthlyStats['total_purchases']) >= 0 ? 'positive' : 'negative' ?>">
                  <?= formatCurrency($monthlyStats['total_sales'] - $monthlyStats['total_purchases']) ?>
                </span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Status:</span>
                <span class="stat-value <?= ($monthlyStats['total_sales'] - $monthlyStats['total_purchases']) >= 0 ? 'positive' : 'negative' ?>">
                  <?= ($monthlyStats['total_sales'] - $monthlyStats['total_purchases']) >= 0 ? 'Profit' : 'Loss' ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Low Stock Alert -->
    <div class="form-section">
      <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>

      <?php if ($lowStockItems->num_rows > 0): ?>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $lowStockItems->fetch_assoc()): ?>
                <tr class="<?= $row['quantity'] == 0 ? 'out-of-stock' : 'low-stock' ?>">
                  <td><strong><?= h($row['name']) ?></strong></td>
                  <td><?= h($row['sku'] ?: 'N/A') ?></td>
                  <td><?= $row['quantity'] ?></td>
                  <td><?= $row['reorder_level'] ?></td>
                  <td>
                    <span class="badge badge-<?= $row['quantity'] == 0 ? 'danger' : 'warning' ?>">
                      <?= $row['quantity'] == 0 ? 'Out of Stock' : 'Low Stock' ?>
                    </span>
                  </td>
                  <td>
                    <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm">
                      <i class="fas fa-edit"></i> Update
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <?php if ($lowStockCount > 5): ?>
          <div style="text-align: center; margin-top: 20px;">
            <a href="view_products.php" class="btn btn-primary">
              <i class="fas fa-eye"></i> View All Low Stock Items
            </a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p class="no-data">All products are above reorder level. 🎉</p>
      <?php endif; ?>
    </div>

    <!-- Recent Transactions -->
    <?php if (hasPermission('view_transactions')): ?>
      <div class="form-section">
        <h3><i class="fas fa-history"></i> Recent Transactions</h3>

        <?php if ($recentTransactions->num_rows > 0): ?>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Product</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $recentTransactions->fetch_assoc()): ?>
                  <tr class="<?= $row['type'] === 'buy' ? 'buy-row' : 'sell-row' ?>">
                    <td><?= date('M d, Y', strtotime($row['transaction_date'])) ?></td>
                    <td>
                      <strong><?= h($row['product_name']) ?></strong><br>
                      <small>SKU: <?= h($row['sku'] ?: 'N/A') ?></small>
                    </td>
                    <td>
                      <span class="badge badge-<?= $row['type'] === 'buy' ? 'success' : 'primary' ?>">
                        <?= ucfirst($row['type']) ?>
                      </span>
                    </td>
                    <td><?= $row['quantity'] ?></td>
                    <td><strong><?= formatCurrency($row['total_amount']) ?></strong></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <div style="text-align: center; margin-top: 20px;">
            <a href="transactions.php" class="btn btn-primary">
              <i class="fas fa-exchange-alt"></i> View All Transactions
            </a>
          </div>
        <?php else: ?>
          <p class="no-data">No transactions recorded yet.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- User Access Tracking -->
    <?php if (isAdmin()): ?>
      <div class="form-section">
        <h3><i class="fas fa-users"></i> User Access Statistics</h3>

        <?php
        // Get user access statistics
        $access_stats = getUserAccessStats();
        ?>

        <div class="report-grid">
          <div class="report-card">
            <h4>Today's Activity</h4>
            <div class="report-stats">
              <div class="stat-item">
                <span class="stat-label">Total Logins:</span>
                <span class="stat-value"><?= number_format($access_stats['today_logins']) ?></span>
              </div>
              <?php foreach ($access_stats['role_logins'] as $role => $count): ?>
                <div class="stat-item">
                  <span class="stat-label"><?= ucfirst($role) ?> Logins:</span>
                  <span class="stat-value"><?= number_format($count) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="report-card">
            <h4>Monthly Activity</h4>
            <div class="report-stats">
              <div class="stat-item">
                <span class="stat-label">Total Logins:</span>
                <span class="stat-value"><?= number_format($access_stats['month_logins']) ?></span>
              </div>
            </div>
          </div>

          <div class="report-card">
            <h4>Recent Logins</h4>
            <div class="report-stats">
              <?php foreach (array_slice($access_stats['recent_logins'], 0, 5) as $login): ?>
                <div class="stat-item">
                  <span class="stat-label"><?= h($login['username']) ?> (<?= ucfirst($login['role']) ?>):</span>
                  <span class="stat-value"><?= date('M d, H:i', strtotime($login['login_time'])) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </main>

  <footer class="footer">
    <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
  </footer>

  <script>
    // Ensure logo is clickable
    document.addEventListener('DOMContentLoaded', function() {
      const logo = document.querySelector('.logo');
      if (logo) {
        logo.addEventListener('click', function(e) {
          console.log('Logo clicked - redirecting to home page');
          window.location.href = 'index.php';
        });

        // Also make sure the link works
        logo.style.cursor = 'pointer';
        logo.style.pointerEvents = 'auto';
      }
    });
  </script>

</body>

</html>