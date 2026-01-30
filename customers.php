<?php
session_start();
include 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Management - Smart Inventory System</title>
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
            <h1><i class="fas fa-users"></i> Customer Management</h1>
            <p>Manage your customer database and relationships</p>
        </div>
        
        <!-- Add Customer Section -->
        <div class="form-section">
            <h3><i class="fas fa-user-plus"></i> Add New Customer</h3>
            
            <form action="process_customer.php" method="post" class="customer-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>Customer Type</label>
                        <select name="customer_type">
                            <option value="regular">Regular</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="vip">VIP</option>
                            <option value="corporate">Corporate</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state">
                    </div>
                    <div class="form-group">
                        <label>PIN Code</label>
                        <input type="text" name="pincode">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>GST Number</label>
                        <input type="text" name="gst_number">
                    </div>
                    <div class="form-group">
                        <label>Credit Limit (₹)</label>
                        <input type="number" name="credit_limit" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Customer
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Customer List Section -->
        <div class="form-section">
            <h3><i class="fas fa-users"></i> Customer Directory</h3>
            
            <!-- Search and Filter -->
            <div class="search-container">
                <form class="search-form" method="GET">
                    <input type="text" name="search" placeholder="Search by name, email, or phone" 
                           value="<?= $_GET['search'] ?? '' ?>" class="search-input">
                    <select name="type" class="category-select">
                        <option value="">All Types</option>
                        <option value="regular" <?= ($_GET['type'] ?? '') === 'regular' ? 'selected' : '' ?>>Regular</option>
                        <option value="wholesale" <?= ($_GET['type'] ?? '') === 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
                        <option value="vip" <?= ($_GET['type'] ?? '') === 'vip' ? 'selected' : '' ?>>VIP</option>
                        <option value="corporate" <?= ($_GET['type'] ?? '') === 'corporate' ? 'selected' : '' ?>>Corporate</option>
                    </select>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="customers.php" class="clear-btn">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>
            
            <?php
            // Build query with filters
            $where_conditions = [];
            $params = [];
            $types = '';
            
            if (!empty($_GET['search'])) {
                $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $search_term = '%' . $_GET['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $types .= 'sss';
            }
            
            if (!empty($_GET['type'])) {
                $where_conditions[] = "customer_type = ?";
                $params[] = $_GET['type'];
                $types .= 's';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "
                SELECT c.*, 
                       COUNT(t.id) as total_transactions,
                       SUM(CASE WHEN t.type = 'sell' THEN t.total_amount ELSE 0 END) as total_spent,
                       MAX(t.transaction_date) as last_purchase_date
                FROM customers c
                LEFT JOIN transactions t ON c.id = t.customer_id
                $where_clause
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $customers = $stmt->get_result();
            ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Total Spent</th>
                            <th>Transactions</th>
                            <th>Last Purchase</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers->num_rows > 0): ?>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= h($customer['full_name']) ?></strong>
                                        <?php if ($customer['gst_number']): ?>
                                            <br><small>GST: <?= h($customer['gst_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= h($customer['email']) ?></div>
                                        <div><?= h($customer['phone']) ?></div>
                                        <?php if ($customer['city']): ?>
                                            <div><small><?= h($customer['city']) ?>, <?= h($customer['state']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= getCustomerTypeBadge($customer['customer_type']) ?>">
                                            <?= ucfirst($customer['customer_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= formatCurrency($customer['total_spent'] ?? 0) ?></strong>
                                    </td>
                                    <td>
                                        <?= number_format($customer['total_transactions']) ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_purchase_date']): ?>
                                            <?= date('M d, Y', strtotime($customer['last_purchase_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">No purchases</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="customer_details.php?id=<?= $customer['id'] ?>" 
                                               class="btn btn-info btn-sm" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_customer.php?id=<?= $customer['id'] ?>" 
                                               class="btn btn-secondary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="customer_transactions.php?id=<?= $customer['id'] ?>" 
                                               class="btn btn-primary btn-sm" title="Transactions">
                                                <i class="fas fa-exchange-alt"></i>
                                            </a>
                                            <?php if ($customer['is_active']): ?>
                                                <a href="process_customer.php?action=deactivate&id=<?= $customer['id'] ?>" 
                                                   class="btn btn-warning btn-sm" title="Deactivate"
                                                   onclick="return confirm('Are you sure you want to deactivate this customer?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="process_customer.php?action=activate&id=<?= $customer['id'] ?>" 
                                                   class="btn btn-success btn-sm" title="Activate">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Analytics -->
        <div class="form-section">
            <h3><i class="fas fa-chart-pie"></i> Customer Analytics</h3>
            
            <?php
            // Get customer statistics
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_customers,
                    COUNT(CASE WHEN customer_type = 'vip' THEN 1 END) as vip_customers,
                    COUNT(CASE WHEN customer_type = 'wholesale' THEN 1 END) as wholesale_customers,
                    AVG(total_spent) as avg_spending
                FROM (
                    SELECT c.*, 
                           SUM(CASE WHEN t.type = 'sell' THEN t.total_amount ELSE 0 END) as total_spent
                    FROM customers c
                    LEFT JOIN transactions t ON c.id = t.customer_id
                    GROUP BY c.id
                ) as customer_stats
            ")->fetch_assoc();
            ?>
            
            <div class="report-grid">
                <div class="report-card">
                    <h4>Total Customers</h4>
                    <div class="report-stats">
                        <div class="stat-item">
                            <span class="stat-label">All Customers:</span>
                            <span class="stat-value"><?= number_format($stats['total_customers']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Active:</span>
                            <span class="stat-value"><?= number_format($stats['active_customers']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="report-card">
                    <h4>Customer Types</h4>
                    <div class="report-stats">
                        <div class="stat-item">
                            <span class="stat-label">VIP Customers:</span>
                            <span class="stat-value"><?= number_format($stats['vip_customers']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Wholesale:</span>
                            <span class="stat-value"><?= number_format($stats['wholesale_customers']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="report-card">
                    <h4>Average Spending</h4>
                    <div class="report-stats">
                        <div class="stat-item">
                            <span class="stat-label">Per Customer:</span>
                            <span class="stat-value"><?= formatCurrency($stats['avg_spending'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <script>
        // Auto-hide success/error messages
        setTimeout(function() {
            const messages = document.querySelectorAll('.success, .error');
            messages.forEach(msg => {
                msg.style.display = 'none';
            });
        }, 5000);
    </script>
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
