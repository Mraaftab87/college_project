<?php
session_start();
include 'functions.php';
requirePermission('add_product'); // Only users with add_product permission can access bulk operations
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bulk Operations - Smart Inventory System</title>
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
            <?php endif; ?>
            <a href="bulk_operations.php" class="active"><i class="fas fa-upload"></i> Bulk Operations</a>
            <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <div class="hero">
            <h1><i class="fas fa-upload"></i> Bulk Operations</h1>
            <p>Import and export data in bulk for efficient inventory management</p>
        </div>

        <!-- Import Section -->
        <div class="form-section">
            <h3><i class="fas fa-download"></i> Import Data</h3>

            <div class="bulk-import-container">
                <div class="import-tabs">
                    <button class="tab-btn active" data-tab="products">Products</button>
                    <button class="tab-btn" data-tab="transactions">Transactions</button>
                    <button class="tab-btn" data-tab="categories">Categories</button>
                </div>

                <div class="tab-content active" id="products-tab">
                    <div class="import-form">
                        <h4>Import Products</h4>
                        <p>Upload CSV file with product data. <a href="download_template.php?type=products" class="btn btn-info btn-sm">Download Template</a></p>

                        <form action="process_import.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="type" value="products">
                            <div class="form-group">
                                <label>Select CSV File:</label>
                                <input type="file" name="csv_file" accept=".csv" required>
                            </div>
                            
                            <!-- Enhanced Checkbox Options -->
                            <div class="checkbox-options">
                                <h4><i class="fas fa-cog"></i> Import Options</h4>
                                
                                <div class="checkbox-item">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="skip_duplicates" value="1">
                                        <span class="checkmark"></span>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title">Skip Duplicate Products</div>
                                            <div class="checkbox-description">Skip products that already exist based on SKU/Barcode</div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="checkbox-item">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="update_existing" value="1">
                                        <span class="checkmark"></span>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title">Update Existing Products</div>
                                            <div class="checkbox-description">Update product information if it already exists</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Import Products
                            </button>
                        </form>
                    </div>
                </div>

                <div class="tab-content" id="transactions-tab">
                    <div class="import-form">
                        <h4>Import Transactions</h4>
                        <p>Upload CSV file with transaction data. <a href="download_template.php?type=transactions" class="btn btn-info btn-sm">Download Template</a></p>

                        <form action="process_import.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="type" value="transactions">
                            <div class="form-group">
                                <label>Select CSV File:</label>
                                <input type="file" name="csv_file" accept=".csv" required>
                            </div>
                            <!-- Enhanced Checkbox Options -->
                            <div class="checkbox-options">
                                <h4><i class="fas fa-cog"></i> Import Options</h4>
                                
                                <div class="checkbox-item">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="validate_products" value="1" checked>
                                        <span class="checkmark"></span>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title">Validate Product Existence</div>
                                            <div class="checkbox-description">Verify that referenced products exist before importing</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Import Transactions
                            </button>
                        </form>
                    </div>
                </div>

                <div class="tab-content" id="categories-tab">
                    <div class="import-form">
                        <h4>Import Categories</h4>
                        <p>Upload CSV file with category data. <a href="download_template.php?type=categories" class="btn btn-info btn-sm">Download Template</a></p>

                        <form action="process_import.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="type" value="categories">
                            <div class="form-group">
                                <label>Select CSV File:</label>
                                <input type="file" name="csv_file" accept=".csv" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Import Categories
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Section -->
        <div class="form-section">
            <h3><i class="fas fa-upload"></i> Export Data</h3>

            <div class="bulk-export-container">
                <div class="export-options">
                    <div class="export-card">
                        <h4><i class="fas fa-box"></i> Export Products</h4>
                        <p>Export all products with complete details</p>
                        <div class="export-filters">
                            <label>Category:</label>
                            <select id="export-category">
                                <option value="">All Categories</option>
                                <?php
                                $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                                while ($cat = $categories->fetch_assoc()) {
                                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                                }
                                ?>
                            </select>
                            <label>Stock Status:</label>
                            <select id="export-stock">
                                <option value="">All</option>
                                <option value="in-stock">In Stock</option>
                                <option value="low-stock">Low Stock</option>
                                <option value="out-of-stock">Out of Stock</option>
                            </select>
                        </div>
                        <a href="export_data.php?type=products" class="btn btn-success" id="export-products-btn">
                            <i class="fas fa-download"></i> Export Products
                        </a>
                    </div>

                    <div class="export-card">
                        <h4><i class="fas fa-exchange-alt"></i> Export Transactions</h4>
                        <p>Export transaction history with filters</p>
                        <div class="export-filters">
                            <label>Date Range:</label>
                            <input type="date" id="export-start-date">
                            <input type="date" id="export-end-date">
                            <label>Type:</label>
                            <select id="export-transaction-type">
                                <option value="">All</option>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>
                        </div>
                        <a href="export_data.php?type=transactions" class="btn btn-success" id="export-transactions-btn">
                            <i class="fas fa-download"></i> Export Transactions
                        </a>
                    </div>

                    <div class="export-card">
                        <h4><i class="fas fa-chart-bar"></i> Export Reports</h4>
                        <p>Export various reports and analytics</p>
                        <div class="export-filters">
                            <label>Report Type:</label>
                            <select id="export-report-type">
                                <option value="inventory-summary">Inventory Summary</option>
                                <option value="sales-report">Sales Report</option>
                                <option value="low-stock-report">Low Stock Report</option>
                                <option value="profit-loss">Profit & Loss</option>
                            </select>
                            <label>Month:</label>
                            <input type="month" id="export-month" value="<?= date('Y-m') ?>">
                        </div>
                        <a href="export_data.php?type=reports" class="btn btn-success" id="export-reports-btn">
                            <i class="fas fa-download"></i> Export Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import History -->
        <div class="form-section">
            <h3><i class="fas fa-history"></i> Import History</h3>
            <div class="import-history">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>File Name</th>
                            <th>Records</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="no-data">No import history available</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab
                this.classList.add('active');
                document.getElementById(this.dataset.tab + '-tab').classList.add('active');
            });
        });

        // Export with filters
        function updateExportLinks() {
            const category = document.getElementById('export-category').value;
            const stock = document.getElementById('export-stock').value;
            const startDate = document.getElementById('export-start-date').value;
            const endDate = document.getElementById('export-end-date').value;
            const transactionType = document.getElementById('export-transaction-type').value;
            const reportType = document.getElementById('export-report-type').value;
            const month = document.getElementById('export-month').value;

            // Update product export link
            let productUrl = 'export_data.php?type=products';
            if (category) productUrl += '&category=' + category;
            if (stock) productUrl += '&stock=' + stock;
            document.getElementById('export-products-btn').href = productUrl;

            // Update transaction export link
            let transactionUrl = 'export_data.php?type=transactions';
            if (startDate) transactionUrl += '&start_date=' + startDate;
            if (endDate) transactionUrl += '&end_date=' + endDate;
            if (transactionType) transactionUrl += '&type=' + transactionType;
            document.getElementById('export-transactions-btn').href = transactionUrl;

            // Update report export link
            let reportUrl = 'export_data.php?type=reports&report_type=' + reportType;
            if (month) reportUrl += '&month=' + month;
            document.getElementById('export-reports-btn').href = reportUrl;
        }

        // Add event listeners to all filter inputs
        document.querySelectorAll('.export-filters select, .export-filters input').forEach(input => {
            input.addEventListener('change', updateExportLinks);
        });

        // Initialize export links
        updateExportLinks();
    </script>

    <style>
        /* Enhanced Checkbox Styles */
        .checkbox-options {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }

        .checkbox-options h4 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-options h4 i {
            color: #3498db;
        }

        .checkbox-item {
            margin-bottom: 15px;
        }

        .checkbox-item:last-child {
            margin-bottom: 0;
        }

        .custom-checkbox {
            display: flex;
            align-items: flex-start;
            cursor: pointer;
            position: relative;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .custom-checkbox:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
        }

        .custom-checkbox input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            position: relative;
            width: 20px;
            height: 20px;
            background: #fff;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .custom-checkbox input[type="checkbox"]:checked + .checkmark {
            background: #3498db;
            border-color: #3498db;
        }

        .custom-checkbox input[type="checkbox"]:checked + .checkmark:after {
            content: '';
            position: absolute;
            left: 6px;
            top: 2px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-content {
            flex: 1;
        }

        .checkbox-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .checkbox-description {
            color: #6c757d;
            font-size: 12px;
            line-height: 1.4;
        }

        /* Active state */
        .custom-checkbox input[type="checkbox"]:checked ~ .checkbox-content .checkbox-title {
            color: #3498db;
        }

        /* Focus state */
        .custom-checkbox input[type="checkbox"]:focus + .checkmark {
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkbox-options {
                padding: 15px;
            }
            
            .custom-checkbox {
                padding: 12px;
            }
            
            .checkmark {
                width: 18px;
                height: 18px;
                margin-right: 12px;
            }
            
            .custom-checkbox input[type="checkbox"]:checked + .checkmark:after {
                left: 5px;
                top: 1px;
                width: 5px;
                height: 9px;
            }
        }

        /* Animation for better UX */
        .custom-checkbox {
            animation: fadeInUp 0.3s ease-out;
        }

        .checkbox-item:nth-child(1) .custom-checkbox {
            animation-delay: 0.1s;
        }

        .checkbox-item:nth-child(2) .custom-checkbox {
            animation-delay: 0.2s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>

</html>