<?php
session_start();
include 'functions.php';
requirePermission('add_product'); // Only managers and admins can add products
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Product - Smart Inventory System</title>
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
            <a href="add_product.php" class="active"><i class="fas fa-plus"></i> Add Product</a>
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
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard">

        <div class="hero">
            <h1><i class="fas fa-plus"></i> Add New Product</h1>
            <p>Add a new product to your inventory with complete details</p>
        </div>

        <?php
        include 'db.php';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('error', 'Invalid form token. Please try again.');
                header("Location: add_product.php");
                exit;
            }
            $name = $_POST['name'];
            $quantity = $_POST['quantity'];
            $price = $_POST['price'];
            $cost_price = $_POST['cost_price'];
            $description = $_POST['description'] ?? '';
            $quality = $_POST['quality'] ?? '';
            $benefits = $_POST['benefits'] ?? '';
            $specifications = $_POST['specifications'] ?? '';
            $product_type_id = $_POST['product_type_id'] ?? null;
            $product_item_id = $_POST['product_item_id'] ?? null;
            $company_id = $_POST['company_id'] ?? null;
            $product_type_other = trim($_POST['product_type_other'] ?? '');
            $product_item_other = trim($_POST['product_item_other'] ?? '');
            $company_other = trim($_POST['company_other'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $subcategory_id = $_POST['subcategory_id'] ?? null;

            // Convert ID fields to integers or null
            $category_id = $category_id && is_numeric($category_id) && $category_id !== '' ? (int)$category_id : null;
            $subcategory_id = $subcategory_id && is_numeric($subcategory_id) && $subcategory_id !== '' ? (int)$subcategory_id : null;
            $sku = $_POST['sku'] ?? '';
            $barcode = $_POST['barcode'] ?? '';
            $supplier = $_POST['supplier'] ?? '';
            $reorder_level = $_POST['reorder_level'] ?? 5;
            $expiry_date = $_POST['expiry_date'] ?? null;

            if ($quantity <= 0 || $price <= 0 || $cost_price <= 0) {
                setFlashMessage('error', 'Quantity, Price, and Cost Price must be greater than 0.');
            } else {
                // Resolve manual category/subcategory if provided
                if (!empty($_POST['category_other'])) {
                    $catName = trim($_POST['category_other']);
                    $findCat = $conn->prepare("SELECT id FROM categories WHERE LOWER(name)=LOWER(?) LIMIT 1");
                    $findCat->bind_param("s", $catName);
                    $findCat->execute();
                    $catRes = $findCat->get_result();
                    if ($c = $catRes->fetch_assoc()) {
                        $category_id = (int)$c['id'];
                    } else {
                        $insCat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                        $insCat->bind_param("s", $catName);
                        $insCat->execute();
                        $category_id = (int)$conn->insert_id;
                    }
                }
                if (!empty($_POST['subcategory_other'])) {
                    $subName = trim($_POST['subcategory_other']);
                    if ($category_id) {
                        $findSub = $conn->prepare("SELECT id FROM subcategories WHERE category_id=? AND LOWER(name)=LOWER(?) LIMIT 1");
                        $findSub->bind_param("is", $category_id, $subName);
                        $findSub->execute();
                        $subRes = $findSub->get_result();
                        if ($s = $subRes->fetch_assoc()) {
                            $subcategory_id = (int)$s['id'];
                        } else {
                            $insSub = $conn->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, ?)");
                            $insSub->bind_param("is", $category_id, $subName);
                            if ($insSub->execute()) {
                                $subcategory_id = (int)$conn->insert_id;
                            } else {
                                setFlashMessage('error', 'Failed to create subcategory: ' . $insSub->error);
                                header("Location: add_product.php");
                                exit;
                            }
                        }
                    } else {
                        setFlashMessage('error', 'Please select a category before creating a subcategory.');
                        header("Location: add_product.php");
                        exit;
                    }
                }
                // Handle image upload
                $image_path = null;
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    // Determine MIME type using finfo instead of trusting client-provided type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $file_type = $finfo ? finfo_file($finfo, $_FILES['product_image']['tmp_name']) : '';
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    $max_size = 5 * 1024 * 1024; // 5 MB

                    if (!in_array($file_type, $allowed_types)) {
                        setFlashMessage('error', 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP');
                    } elseif ($_FILES['product_image']['size'] > $max_size) {
                        setFlashMessage('error', 'Image too large. Maximum size is 5MB.');
                    } else {
                        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                        $file_name = uniqid() . '.' . $file_extension;
                        $upload_path = 'images/products/' . $file_name;
                        if (!is_dir('images')) {
                            @mkdir('images', 0755);
                        }
                        if (!is_dir('images/products')) {
                            @mkdir('images/products', 0755);
                        }

                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $image_path = $upload_path;
                            // Create thumbnail
                            $thumb_path = 'images/products/thumbs/' . $file_name;
                            createImageThumbnail($image_path, $thumb_path, 400, 400);
                        } else {
                            setFlashMessage('error', 'Failed to upload image.');
                        }
                    }
                }

                // Handle "Other..." selections for type/item/company
                // Product Type
                if ($product_type_id === 'other') {
                    if ($product_type_other === '') {
                        setFlashMessage('error', 'Please enter Product Type in "Write it yourself" field.');
                        header("Location: add_product.php");
                        exit;
                    }
                    $findType = $conn->prepare("SELECT id FROM product_types WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $findType->bind_param("s", $product_type_other);
                    $findType->execute();
                    $typeRes = $findType->get_result();
                    if ($typeRow = $typeRes->fetch_assoc()) {
                        $product_type_id = (int)$typeRow['id'];
                    } else {
                        $insType = $conn->prepare("INSERT INTO product_types (name) VALUES (?)");
                        $insType->bind_param("s", $product_type_other);
                        $insType->execute();
                        $product_type_id = (int)$conn->insert_id;
                    }
                } else {
                    $product_type_id = $product_type_id ? (int)$product_type_id : null;
                }

                // Product Item
                if ($product_item_id === 'other') {
                    if ($product_item_other === '') {
                        setFlashMessage('error', 'Please enter Product Item in "Write it yourself" field.');
                        header("Location: add_product.php");
                        exit;
                    }
                    if (!$product_type_id) {
                        setFlashMessage('error', 'Please select or create a Product Type before adding an Item.');
                        header("Location: add_product.php");
                        exit;
                    }
                    $findItem = $conn->prepare("SELECT id FROM product_items WHERE product_type_id = ? AND LOWER(name) = LOWER(?) LIMIT 1");
                    $findItem->bind_param("is", $product_type_id, $product_item_other);
                    $findItem->execute();
                    $itemRes = $findItem->get_result();
                    if ($itemRow = $itemRes->fetch_assoc()) {
                        $product_item_id = (int)$itemRow['id'];
                    } else {
                        $insItem = $conn->prepare("INSERT INTO product_items (product_type_id, name) VALUES (?, ?)");
                        $insItem->bind_param("is", $product_type_id, $product_item_other);
                        $insItem->execute();
                        $product_item_id = (int)$conn->insert_id;
                    }
                } else {
                    $product_item_id = $product_item_id ? (int)$product_item_id : null;
                }

                // Company/Brand
                if ($company_id === 'other') {
                    if ($company_other === '') {
                        setFlashMessage('error', 'Please enter Company/Brand in "Write it yourself" field.');
                        header("Location: add_product.php");
                        exit;
                    }
                    $findCompany = $conn->prepare("SELECT id FROM companies WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $findCompany->bind_param("s", $company_other);
                    $findCompany->execute();
                    $compRes = $findCompany->get_result();
                    if ($compRow = $compRes->fetch_assoc()) {
                        $company_id = (int)$compRow['id'];
                    } else {
                        $insCompany = $conn->prepare("INSERT INTO companies (name) VALUES (?)");
                        $insCompany->bind_param("s", $company_other);
                        $insCompany->execute();
                        $company_id = (int)$conn->insert_id;
                    }
                } else {
                    $company_id = $company_id ? (int)$company_id : null;
                }

                // Validate category and subcategory consistency if both are provided
                if ($category_id && $subcategory_id) {
                    if (!validateCategoryConsistency($category_id, $subcategory_id)) {
                        // If subcategory doesn't belong to the selected category, update category to match subcategory's parent
                        $findSubcatParent = $conn->prepare("SELECT category_id FROM subcategories WHERE id = ?");
                        $findSubcatParent->bind_param("i", $subcategory_id);
                        $findSubcatParent->execute();
                        $parentResult = $findSubcatParent->get_result();
                        if ($parentRow = $parentResult->fetch_assoc()) {
                            $category_id = $parentRow['category_id'];
                        }
                    }
                }



                // Validate that subcategory_id exists in the database if provided
                if ($subcategory_id) {
                    $validateSub = $conn->prepare("SELECT id FROM subcategories WHERE id = ?");
                    $validateSub->bind_param("i", $subcategory_id);
                    $validateSub->execute();
                    $validateResult = $validateSub->get_result();
                    if ($validateResult->num_rows === 0) {
                        setFlashMessage('error', 'Selected subcategory does not exist.');
                        header("Location: add_product.php");
                        exit;
                    }
                }

                $stmt = $conn->prepare("INSERT INTO products (name, quantity, price, cost_price, description, quality, benefits, specifications, product_type_id, product_item_id, company_id, category_id, subcategory_id, image_path, sku, barcode, supplier, reorder_level, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siddssssiiiiissssis", $name, $quantity, $price, $cost_price, $description, $quality, $benefits, $specifications, $product_type_id, $product_item_id, $company_id, $category_id, $subcategory_id, $image_path, $sku, $barcode, $supplier, $reorder_level, $expiry_date);

                if ($stmt->execute()) {
                    setFlashMessage('success', 'Product added successfully. <a href="view_products.php">View All</a>');
                } else {
                    setFlashMessage('error', 'Error: ' . $stmt->error);
                }
            }

            // Redirect to prevent form resubmission
            header("Location: add_product.php");
            exit;
        }

        // Get product types, items, and companies
        $product_types = [];
        $product_items = [];
        $companies = [];

        $type_stmt = $conn->prepare("SELECT id, name, icon FROM product_types ORDER BY name");
        $type_stmt->execute();
        $type_result = $type_stmt->get_result();
        while ($row = $type_result->fetch_assoc()) {
            $product_types[] = $row;
        }

        $item_stmt = $conn->prepare("SELECT id, name, product_type_id FROM product_items ORDER BY name");
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        while ($row = $item_result->fetch_assoc()) {
            $product_items[] = $row;
        }

        $company_stmt = $conn->prepare("SELECT id, name FROM companies ORDER BY name");
        $company_stmt->execute();
        $company_result = $company_stmt->get_result();
        while ($row = $company_result->fetch_assoc()) {
            $companies[] = $row;
        }

        // Get categories and subcategories
        $categories = [];
        $subcategories = [];

        $cat_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
        $cat_stmt->execute();
        $cat_result = $cat_stmt->get_result();
        while ($row = $cat_result->fetch_assoc()) {
            $categories[] = $row;
        }

        $subcat_stmt = $conn->prepare("SELECT id, name, category_id FROM subcategories ORDER BY name");
        $subcat_stmt->execute();
        $subcat_result = $subcat_stmt->get_result();
        while ($row = $subcat_result->fetch_assoc()) {
            $subcategories[] = $row;
        }

        // Get flash message
        $flash = getFlashMessage();
        ?>

        <div class="form-container">
            <div class="form-section">
                <h3>Product Information</h3>

                <?php if ($flash): ?>
                    <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
                <?php endif; ?>

                <form method="POST" class="registration-form" enctype="multipart/form-data">
                    <?= csrfTokenInput(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name: <span class="required">*</span></label>
                            <input type="text" name="name" required placeholder="Enter product name">
                        </div>

                        <div class="form-group">
                            <label>SKU:</label>
                            <input type="text" name="sku" placeholder="Stock Keeping Unit">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Type: <span class="required">*</span></label>
                            <select name="product_type_id" id="product_type_id" required onchange="loadProductItems(); toggleTypeOther();">
                                <option value="">Select Product Type</option>
                                <?php foreach ($product_types as $type): ?>
                                    <option value="<?= $type['id'] ?>"><?= h($type['name']) ?></option>
                                <?php endforeach; ?>
                                <option value="other">Other...</option>
                            </select>
                            <div id="type_other_group" style="display:none; margin-top:6px;">
                                <label style="display:block; margin-bottom:6px;">Write it yourself</label>
                                <input type="text" name="product_type_other" id="product_type_other" placeholder="Enter new product type">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Product Item: <span class="required">*</span></label>
                            <select name="product_item_id" id="product_item_id" required onchange="loadCompaniesAjax(); toggleItemOther();">
                                <option value="">Select Product Type First</option>
                            </select>
                            <div id="item_other_group" style="display:none; margin-top:6px;">
                                <label style="display:block; margin-bottom:6px;">Write it yourself</label>
                                <input type="text" name="product_item_other" id="product_item_other" placeholder="Enter new product item">
                            </div>
                            <div style="margin-top:6px">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="autofillCategoryFromItem()">Auto-fill Category (Optional)</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Company/Brand:</label>
                            <select name="company_id" id="company_id" onchange="toggleCompanyOther();">
                                <option value="">Select Product Item First</option>
                            </select>
                            <div id="company_other_group" style="display:none; margin-top:6px;">
                                <label style="display:block; margin-bottom:6px;">Write it yourself</label>
                                <input type="text" name="company_other" id="company_other" placeholder="Enter new company/brand">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Category:</label>
                            <select name="category_id" id="category_id" onchange="loadSubcategories()">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= h($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="margin-top:6px">
                                <input type="text" name="category_other" id="category_other" placeholder="Write category (optional)" onblur="maybeCreateCategory()">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Subcategory:</label>
                            <select name="subcategory_id" id="subcategory_id">
                                <option value="">Select Subcategory</option>
                            </select>
                            <div style="margin-top:6px">
                                <input type="text" name="subcategory_other" id="subcategory_other" placeholder="Write subcategory (optional)" onblur="maybeCreateSubcategory()">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity: <span class="required">*</span></label>
                            <input type="number" name="quantity" min="0" required placeholder="Enter quantity">
                        </div>

                        <div class="form-group">
                            <label>Price: <span class="required">*</span></label>
                            <input type="number" name="price" step="0.01" min="0.01" required placeholder="Selling price">
                        </div>

                        <div class="form-group">
                            <label>Cost Price: <span class="required">*</span></label>
                            <input type="number" name="cost_price" step="0.01" min="0.01" required placeholder="Purchase cost">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Reorder Level:</label>
                            <input type="number" name="reorder_level" min="0" value="5" placeholder="Reorder level">
                        </div>

                        <div class="form-group">
                            <label>Expiry Date:</label>
                            <input type="date" name="expiry_date">
                        </div>

                        <div class="form-group">
                            <label>Supplier:</label>
                            <input type="text" name="supplier" placeholder="Supplier name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Product Image:</label>
                        <div class="image-upload-container">
                            <div class="image-preview" id="imagePreview">
                                <div class="preview-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload image</p>
                                    <small>Drag & drop or click to select</small>
                                </div>
                            </div>
                            <input type="file" name="product_image" id="productImage" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('productImage').click()">
                                <i class="fas fa-upload"></i> Choose Image
                            </button>
                            <button type="button" class="btn btn-danger" id="removeImage" style="display: none;">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                        <small>Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB</small>
                    </div>

                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" rows="3" placeholder="Product description..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Quality:</label>
                        <textarea name="quality" rows="2" placeholder="Product quality details..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Benefits:</label>
                        <textarea name="benefits" rows="2" placeholder="Product benefits and features..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Specifications:</label>
                        <textarea name="specifications" rows="3" placeholder="Technical specifications..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Barcode:</label>
                        <input type="text" name="barcode" placeholder="Product barcode">
                    </div>

                    <div class="form-actions">
                        <input type="submit" value="Add Product" class="btn btn-primary">
                        <a href="view_products.php" class="btn btn-secondary">← Back to Products</a>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <script>
        // Load product items based on selected product type
        function loadProductItems() {
            const productTypeId = document.getElementById('product_type_id').value;
            const productItemSelect = document.getElementById('product_item_id');
            const companySelect = document.getElementById('company_id');

            // Reset dependent fields
            productItemSelect.innerHTML = '<option value="">Select Product Type First</option>';
            companySelect.innerHTML = '<option value="">Select Product Item First</option>';
            const itemOther = document.getElementById('item_other_group');
            const companyOther = document.getElementById('company_other_group');
            if (itemOther) itemOther.style.display = 'none';
            if (companyOther) companyOther.style.display = 'none';

            if (productTypeId && productTypeId !== 'other') {
                const productItems = <?= json_encode($product_items) ?>;
                const filteredItems = productItems.filter(item => String(item.product_type_id) === String(productTypeId));
                productItemSelect.innerHTML = '<option value="">Select Product Item</option>';
                filteredItems.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    productItemSelect.appendChild(option);
                });
                // Add Other option
                const otherOpt = document.createElement('option');
                otherOpt.value = 'other';
                otherOpt.textContent = 'Other...';
                productItemSelect.appendChild(otherOpt);
            } else if (productTypeId === 'other') {
                // If type is Other, also allow item Other directly
                productItemSelect.innerHTML = '<option value="other">Other...</option>';
                const itemOther = document.getElementById('item_other_group');
                if (itemOther) itemOther.style.display = 'block';
            }
        }

        // Load companies based on selected product item via AJAX mapping
        function loadCompaniesAjax() {
            const productItemId = document.getElementById('product_item_id').value;
            const companySelect = document.getElementById('company_id');

            companySelect.innerHTML = '<option value="">Select Company/Brand</option>';
            const companyOther = document.getElementById('company_other_group');
            if (companyOther) companyOther.style.display = 'none';

            if (productItemId && productItemId !== 'other') {
                fetch(`get_companies.php?product_item_id=${encodeURIComponent(productItemId)}`)
                    .then(r => r.json())
                    .then(data => {
                        companySelect.innerHTML = '<option value="">Select Company/Brand</option>';
                        data.forEach(company => {
                            const option = document.createElement('option');
                            option.value = company.id;
                            option.textContent = company.name;
                            companySelect.appendChild(option);
                        });
                        const otherOpt = document.createElement('option');
                        otherOpt.value = 'other';
                        otherOpt.textContent = 'Other...';
                        companySelect.appendChild(otherOpt);
                    })
                    .catch(() => {
                        const otherOpt = document.createElement('option');
                        otherOpt.value = 'other';
                        otherOpt.textContent = 'Other...';
                        companySelect.appendChild(otherOpt);
                    });
            } else if (productItemId === 'other') {
                companySelect.innerHTML = '<option value="other">Other...</option>';
                if (companyOther) companyOther.style.display = 'block';
            }
        }

        function toggleTypeOther() {
            const productTypeId = document.getElementById('product_type_id').value;
            const group = document.getElementById('type_other_group');
            if (group) group.style.display = (productTypeId === 'other') ? 'block' : 'none';
            if (productTypeId === 'other') {
                // When type is other, force item other as well
                const itemSelect = document.getElementById('product_item_id');
                if (itemSelect) itemSelect.value = 'other';
                const itemOther = document.getElementById('item_other_group');
                if (itemOther) itemOther.style.display = 'block';
            }
        }

        function toggleItemOther() {
            const productItemId = document.getElementById('product_item_id').value;
            const group = document.getElementById('item_other_group');
            if (group) group.style.display = (productItemId === 'other') ? 'block' : 'none';
        }

        function toggleCompanyOther() {
            const companyId = document.getElementById('company_id').value;
            const group = document.getElementById('company_other_group');
            if (group) group.style.display = (companyId === 'other') ? 'block' : 'none';
        }

        // Auto-fill Category/Subcategory from Item mapping (AJAX)
        function autofillCategoryFromItem() {
            const itemId = document.getElementById('product_item_id').value;
            if (!itemId || itemId === 'other') return;
            fetch(`get_type_item_category.php?product_item_id=${encodeURIComponent(itemId)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data) return;
                    if (data.category_id) {
                        document.getElementById('category_id').value = data.category_id;
                        loadSubcategories();
                    }
                    if (data.subcategory_id) {
                        setTimeout(() => {
                            document.getElementById('subcategory_id').value = data.subcategory_id;
                        }, 200);
                    }
                });
        }

        // Load subcategories based on selected category (AJAX)
        function loadSubcategories() {
            const categoryId = document.getElementById('category_id').value;
            const subcategorySelect = document.getElementById('subcategory_id');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            if (!categoryId) return;
            fetch(`get_subcategories.php?category_id=${encodeURIComponent(categoryId)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'No subcategories available';
                        subcategorySelect.appendChild(opt);
                        return;
                    }
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subcategorySelect.appendChild(option);
                    });
                })
                .catch(() => {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'Failed to load subcategories';
                    subcategorySelect.appendChild(opt);
                });
        }

        function maybeCreateCategory() {
            const val = (document.getElementById('category_other').value || '').trim();
            if (!val) return;
            document.getElementById('category_id').value = '';
        }

        function maybeCreateSubcategory() {
            const val = (document.getElementById('subcategory_other').value || '').trim();
            if (!val) return;
            document.getElementById('subcategory_id').value = '';
        }

        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('productImage');
            const imagePreview = document.getElementById('imagePreview');
            const removeButton = document.getElementById('removeImage');

            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Image size must be less than 5MB');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; object-fit: cover;">`;
                        removeButton.style.display = 'inline-block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            removeButton.addEventListener('click', function() {
                imageInput.value = '';
                imagePreview.innerHTML = `
                    <div class="preview-placeholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload image</p>
                        <small>Drag & drop or click to select</small>
                    </div>
                `;
                removeButton.style.display = 'none';
            });

            // Drag and drop functionality
            imagePreview.addEventListener('dragover', function(e) {
                e.preventDefault();
                imagePreview.style.borderColor = '#3498db';
            });

            imagePreview.addEventListener('dragleave', function(e) {
                e.preventDefault();
                imagePreview.style.borderColor = '#ddd';
            });

            imagePreview.addEventListener('drop', function(e) {
                e.preventDefault();
                imagePreview.style.borderColor = '#ddd';
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    imageInput.files = files;
                    imageInput.dispatchEvent(new Event('change'));
                }
            });
        });
    </script>

</body>

</html>