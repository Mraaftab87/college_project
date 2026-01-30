<?php
session_start();
include 'functions.php';
requirePermission('view_products'); // Ensure user has permission to view products
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Products - Smart Inventory System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            <a href="view_products.php" class="active"><i class="fas fa-boxes"></i> View Products</a>
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
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard">

        <div class="hero">
            <h1><i class="fas fa-boxes"></i> Product Management</h1>
            <p>View and manage all your inventory products</p>
        </div>

        <?php
        include 'db.php';

        // Get flash message
        $flash = getFlashMessage();

        // Search functionality
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
        $subcategory_filter = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';
        $product_type_filter = isset($_GET['product_type']) ? $_GET['product_type'] : '';
        $product_item_filter = isset($_GET['product_item']) ? $_GET['product_item'] : '';
        $company_filter = isset($_GET['company']) ? $_GET['company'] : '';

        $query = "
            SELECT p.*, c.name as category_name, sc.name as subcategory_name,
                   pt.name as product_type_name, pi.name as product_item_name, comp.name as company_name
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id 
            LEFT JOIN product_types pt ON p.product_type_id = pt.id
            LEFT JOIN product_items pi ON p.product_item_id = pi.id
            LEFT JOIN companies comp ON p.company_id = comp.id
            WHERE 1=1
        ";
        $params = array();
        $types = "";

        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ssss";
        }

        if (!empty($category_filter)) {
            $query .= " AND p.category_id = ?";
            $params[] = $category_filter;
            $types .= "i";
        }

        if (!empty($subcategory_filter)) {
            $query .= " AND p.subcategory_id = ?";
            $params[] = $subcategory_filter;
            $types .= "i";
        }

        if (!empty($product_type_filter)) {
            $query .= " AND p.product_type_id = ?";
            $params[] = $product_type_filter;
            $types .= "i";
        }

        if (!empty($product_item_filter)) {
            $query .= " AND p.product_item_id = ?";
            $params[] = $product_item_filter;
            $types .= "i";
        }

        if (!empty($company_filter)) {
            $query .= " AND p.company_id = ?";
            $params[] = $company_filter;
            $types .= "i";
        }

        $query .= " ORDER BY p.name ASC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Get product types, items, and companies for filter
        $product_types = $conn->query("SELECT id, name FROM product_types ORDER BY name");
        $product_items = [];
        $companies = $conn->query("SELECT id, name FROM companies ORDER BY name");

        if (!empty($product_type_filter)) {
            $item_stmt = $conn->prepare("SELECT id, name FROM product_items WHERE product_type_id = ? ORDER BY name");
            $item_stmt->bind_param("i", $product_type_filter);
            $item_stmt->execute();
            $item_result = $item_stmt->get_result();
            while ($row = $item_result->fetch_assoc()) {
                $product_items[] = $row;
            }
        }

        // Get categories for filter
        $categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

        // Get subcategories for filter
        $subcategories = [];
        if (!empty($category_filter)) {
            $subcat_stmt = $conn->prepare("SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY name");
            $subcat_stmt->bind_param("i", $category_filter);
            $subcat_stmt->execute();
            $subcat_result = $subcat_stmt->get_result();
            while ($row = $subcat_result->fetch_assoc()) {
                $subcategories[] = $row;
            }
        }
        ?>

        <div class="form-container">
            <div class="form-section">
                <h3>Product List</h3>

                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if (hasPermission('add_product')): ?>
                        <a href="add_product.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($flash): ?>
                    <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
                <?php endif; ?>

                <!-- Search and Filter Form -->
                <div class="search-container">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search products by name, description, SKU, or barcode..." value="<?= h($search) ?>" class="search-input">

                        <select name="product_type" id="product_type_filter" class="category-select" onchange="loadProductItems()">
                            <option value="">All Product Types</option>
                            <?php
                            $product_types->data_seek(0);
                            while ($type = $product_types->fetch_assoc()):
                            ?>
                                <option value="<?= $type['id'] ?>" <?= $product_type_filter == $type['id'] ? 'selected' : '' ?>>
                                    <?= h($type['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <select name="product_item" id="product_item_filter" class="category-select">
                            <option value="">All Product Items</option>
                            <?php foreach ($product_items as $item): ?>
                                <option value="<?= $item['id'] ?>" <?= $product_item_filter == $item['id'] ? 'selected' : '' ?>>
                                    <?= h($item['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="company" id="company_filter" class="category-select">
                            <option value="">All Companies</option>
                            <?php
                            $companies->data_seek(0);
                            while ($comp = $companies->fetch_assoc()):
                            ?>
                                <option value="<?= $comp['id'] ?>" <?= $company_filter == $comp['id'] ? 'selected' : '' ?>>
                                    <?= h($comp['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <select name="category" id="category_filter" class="category-select" onchange="loadSubcategories()">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <select name="subcategory" id="subcategory_filter" class="category-select">
                            <option value="">All Subcategories</option>
                            <?php foreach ($subcategories as $subcat): ?>
                                <option value="<?= $subcat['id'] ?>" <?= $subcategory_filter == $subcat['id'] ? 'selected' : '' ?>>
                                    <?= h($subcat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search) || !empty($category_filter) || !empty($subcategory_filter) || !empty($product_type_filter) || !empty($product_item_filter) || !empty($company_filter)): ?>
                            <a href="view_products.php" class="clear-btn">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if ($result->num_rows > 0) { ?>
                    <div class="products-grid">
                        <?php while ($row = $result->fetch_assoc()) {
                            $stockStatus = getStockStatus($row['quantity']);
                        ?>
                            <div class="product-card <?= $row['quantity'] <= $row['reorder_level'] ? 'low-stock' : '' ?>">
                                <div class="product-image">
                                    <?php if ($row['image_path'] && file_exists($row['image_path'])): ?>
                                        <?php
                                        $thumb = preg_replace('~^images/products/~', 'images/products/thumbs/', $row['image_path']);
                                        $imgToShow = (file_exists($thumb) ? $thumb : $row['image_path']);
                                        ?>
                                        <img src="<?= h($imgToShow) ?>" alt="<?= h($row['name']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'no-image\'><i class=\'fas fa-image\'></i></div>'">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                            <span class="no-image-text">No Image Available</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-info">
                                    <h4 class="product-name"><?= h($row['name']) ?></h4>

                                    <div class="product-details">
                                        <div class="detail-item">
                                            <span class="label">SKU:</span>
                                            <span class="value"><?= h($row['sku'] ?: 'N/A') ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="label">Product Type:</span>
                                            <span class="value"><?= h($row['product_type_name'] ?: 'N/A') ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="label">Product Item:</span>
                                            <span class="value"><?= h($row['product_item_name'] ?: 'N/A') ?></span>
                                        </div>

                                        <?php if ($row['company_name']): ?>
                                            <div class="detail-item">
                                                <span class="label">Company:</span>
                                                <span class="value"><?= h($row['company_name']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="detail-item">
                                            <span class="label">Category:</span>
                                            <span class="value"><?= h($row['category_name'] ?: 'N/A') ?></span>
                                        </div>

                                        <?php if ($row['subcategory_name']): ?>
                                            <div class="detail-item">
                                                <span class="label">Subcategory:</span>
                                                <span class="value"><?= h($row['subcategory_name']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="detail-item">
                                            <span class="label">Stock:</span>
                                            <span class="value stock-<?= $stockStatus['status'] ?>">
                                                <?= $row['quantity'] ?>
                                            </span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="label">Price:</span>
                                            <span class="value price"><?= formatCurrency($row['price']) ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="label">Cost:</span>
                                            <span class="value cost"><?= formatCurrency($row['cost_price']) ?></span>
                                        </div>

                                        <?php if ($row['supplier']): ?>
                                            <div class="detail-item">
                                                <span class="label">Supplier:</span>
                                                <span class="value"><?= h($row['supplier']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($row['expiry_date']): ?>
                                            <div class="detail-item">
                                                <span class="label">Expiry:</span>
                                                <span class="value"><?= date('M d, Y', strtotime($row['expiry_date'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-status">
                                        <span class="status-badge <?= $stockStatus['status'] ?>">
                                            <?= $stockStatus['text'] ?>
                                        </span>
                                        <?php if ($row['quantity'] <= $row['reorder_level']): ?>
                                            <span class="reorder-alert">
                                                <i class="fas fa-exclamation-triangle"></i> Reorder needed
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-actions">
                                        <?php if (hasPermission('edit_product')): ?>
                                            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        <?php endif; ?>

                                        <?php if (hasPermission('delete_product')): ?>
                                            <a href="delete_product.php?id=<?= $row['id'] ?>&csrf_token=<?= h(getCsrfToken()) ?>"
                                                onclick="return confirm('Are you sure you want to delete this product?')"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>

                                        <button class="btn btn-info btn-sm" onclick="showProductDetails(<?= $row['id'] ?>)">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-box-open fa-3x" style="color: #ccc; margin-bottom: 20px;"></i>
                        <p style="color: #666; font-size: 18px;">No products found.</p>
                        <?php if (hasPermission('add_product')): ?>
                            <a href="add_product.php" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i> Add Your First Product
                            </a>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
        </div>

    </main>

    <!-- Product Details Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="productDetails"></div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <script>
        // Load product items based on selected product type
        function loadProductItems() {
            const productTypeId = document.getElementById('product_type_filter').value;
            const productItemSelect = document.getElementById('product_item_filter');

            // Clear product items
            productItemSelect.innerHTML = '<option value="">All Product Items</option>';

            if (productTypeId) {
                // Fetch product items for selected type
                fetch(`get_product_items.php?product_type_id=${productTypeId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name;
                            productItemSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        // Load subcategories based on selected category
        function loadSubcategories() {
            const categoryId = document.getElementById('category_filter').value;
            const subcategorySelect = document.getElementById('subcategory_filter');

            // Clear subcategories
            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';

            if (categoryId) {
                // Fetch subcategories for selected category
                fetch(`get_subcategories.php?category_id=${categoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id;
                            option.textContent = sub.name;
                            subcategorySelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        // Show product details modal
        function showProductDetails(productId) {
            fetch(`get_product_details.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('productDetails').innerHTML = `
                        <h2>${data.name}</h2>
                        <div class="product-details-modal">
                            <div class="detail-row">
                                <div class="detail-col">
                                    <strong>SKU:</strong> ${data.sku || 'N/A'}<br>
                                    <strong>Category:</strong> ${data.category_name || 'N/A'}<br>
                                    <strong>Subcategory:</strong> ${data.subcategory_name || 'N/A'}<br>
                                    <strong>Supplier:</strong> ${data.supplier || 'N/A'}
                                </div>
                                <div class="detail-col">
                                    <strong>Stock:</strong> ${data.quantity}<br>
                                    <strong>Price:</strong> ${data.price}<br>
                                    <strong>Cost:</strong> ${data.cost_price}<br>
                                    <strong>Reorder Level:</strong> ${data.reorder_level}
                                </div>
                            </div>
                            ${data.description ? `<div class="description"><strong>Description:</strong> ${data.description}</div>` : ''}
                            ${data.quality ? `<div class="quality"><strong>Quality:</strong> ${data.quality}</div>` : ''}
                            ${data.benefits ? `<div class="benefits"><strong>Benefits:</strong> ${data.benefits}</div>` : ''}
                            ${data.specifications ? `<div class="specifications"><strong>Specifications:</strong> ${data.specifications}</div>` : ''}
                        </div>
                    `;
                    document.getElementById('productModal').style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        // Close modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('productModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

</body>

</html>