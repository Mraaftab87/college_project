<?php
session_start();
include 'db.php';
include 'functions.php';
// requirePermission('edit_product'); // Only managers and admins can edit products

// Disable error reporting for production (enable with ?debug=1)
if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid product ID.');
    header("Location: view_products.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid form token. Please try again.');
        header("Location: edit_product.php?id=" . urlencode($id));
        exit;
    }
    
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $cost_price = $_POST['cost_price'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $quality = isset($_POST['quality']) ? $_POST['quality'] : '';
    $benefits = isset($_POST['benefits']) ? $_POST['benefits'] : '';
    $specifications = isset($_POST['specifications']) ? $_POST['specifications'] : '';
    $product_type_id = isset($_POST['product_type_id']) && !empty($_POST['product_type_id']) ? $_POST['product_type_id'] : null;
    $product_item_id = isset($_POST['product_item_id']) && !empty($_POST['product_item_id']) ? $_POST['product_item_id'] : null;
    $company_id = isset($_POST['company_id']) && !empty($_POST['company_id']) ? $_POST['company_id'] : null;
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $subcategory_id = isset($_POST['subcategory_id']) && !empty($_POST['subcategory_id']) ? $_POST['subcategory_id'] : null;
    $sku = isset($_POST['sku']) ? $_POST['sku'] : '';
    $barcode = isset($_POST['barcode']) ? $_POST['barcode'] : '';
    $supplier = isset($_POST['supplier']) ? $_POST['supplier'] : '';
    $reorder_level = isset($_POST['reorder_level']) ? $_POST['reorder_level'] : 5;
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    if ($quantity < 0 || $price <= 0 || $cost_price <= 0) {
        setFlashMessage('error', 'Quantity must be 0 or greater, Price and Cost Price must be greater than 0.');
    } else {
        // Validate foreign key references
        $validation_errors = [];
        
        if ($product_type_id && !is_numeric($product_type_id)) {
            $validation_errors[] = 'Invalid product type selected.';
        }
        if ($product_item_id && !is_numeric($product_item_id)) {
            $validation_errors[] = 'Invalid product item selected.';
        }
        if ($company_id && !is_numeric($company_id)) {
            $validation_errors[] = 'Invalid company selected.';
        }
        if ($category_id && !is_numeric($category_id)) {
            $validation_errors[] = 'Invalid category selected.';
        }
        if ($subcategory_id && !is_numeric($subcategory_id)) {
            $validation_errors[] = 'Invalid subcategory selected.';
        }
        
        if (!empty($validation_errors)) {
            setFlashMessage('error', implode(' ', $validation_errors));
        } else {
        // Handle image upload
        $image_path = isset($_POST['current_image']) ? $_POST['current_image'] : null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            $max_size = 5 * 1024 * 1024; // 5 MB
            
            if ($_FILES['product_image']['size'] > $max_size) {
                setFlashMessage('error', 'Image too large. Maximum size is 5MB.');
            } else {
                $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_extension;
                $upload_path = 'images/products/' . $file_name;
                if (!is_dir('images')) { @mkdir('images', 0755); }
                if (!is_dir('images/products')) { @mkdir('images/products', 0755); }
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $upload_path;
                } else {
                    setFlashMessage('error', 'Failed to upload new image.');
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE products SET name=?, quantity=?, price=?, cost_price=?, description=?, quality=?, benefits=?, specifications=?, product_type_id=?, product_item_id=?, company_id=?, category_id=?, subcategory_id=?, image_path=?, sku=?, barcode=?, supplier=?, reorder_level=?, expiry_date=? WHERE id=?");
        $stmt->bind_param("siddssssiiiiissssisi", $name, $quantity, $price, $cost_price, $description, $quality, $benefits, $specifications, $product_type_id, $product_item_id, $company_id, $category_id, $subcategory_id, $image_path, $sku, $barcode, $supplier, $reorder_level, $expiry_date, $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Product updated successfully. <a href="view_products.php">View All Products</a>');
        } else {
            setFlashMessage('error', 'Error updating product: ' . $stmt->error);
        }
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: edit_product.php?id=$id");
    exit;
} else {
    $res = $conn->prepare("
        SELECT p.*, c.name as category_name, sc.name as subcategory_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id 
        WHERE p.id=?
    ");
    $res->bind_param("i", $id);
    $res->execute();
    $result = $res->get_result();
    $data = $result->fetch_assoc();
    if (!$data) {
        setFlashMessage('error', 'Product not found.');
        header("Location: view_products.php");
        exit;
    }
}

// Get product types, items, companies, categories and subcategories
$product_types = array();
$product_items = array();
$companies_all = array();
$categories = array();
$subcategories = array();

$type_stmt = $conn->prepare("SELECT id, name FROM product_types ORDER BY name");
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

$comp_stmt = $conn->prepare("SELECT id, name FROM companies ORDER BY name");
$comp_stmt->execute();
$comp_result = $comp_stmt->get_result();
while ($row = $comp_result->fetch_assoc()) { 
    $companies_all[] = $row; 
}

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Smart Inventory System</title>
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
            <h1><i class="fas fa-edit"></i> Edit Product</h1>
            <p>Update product information with complete details</p>
        </div>

        <div class="form-container">
            <div class="form-section">
                <h3>Product Information</h3>

                <?php if ($flash): ?>
                    <div class="<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="registration-form" enctype="multipart/form-data">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars(isset($data['image_path']) ? $data['image_path'] : ''); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name: <span class="required">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" required placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label>SKU:</label>
                        <input type="text" name="sku" value="<?php echo htmlspecialchars(isset($data['sku']) ? $data['sku'] : ''); ?>" placeholder="Stock Keeping Unit">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Product Type:</label>
                        <select name="product_type_id" id="product_type_id">
                            <option value="">Select Product Type</option>
                            <?php foreach ($product_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo (isset($data['product_type_id']) && $data['product_type_id'] == $type['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Product Item:</label>
                        <select name="product_item_id" id="product_item_id">
                            <option value="">Select Product Item</option>
                            <?php foreach ($product_items as $item): ?>
                                <?php if (isset($data['product_type_id']) && $item['product_type_id'] == $data['product_type_id']): ?>
                                    <option value="<?php echo $item['id']; ?>" <?php echo (isset($data['product_item_id']) && $data['product_item_id'] == $item['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Company/Brand:</label>
                        <select name="company_id" id="company_id">
                            <option value="">Select Company/Brand</option>
                            <?php foreach ($companies_all as $company): ?>
                                <option value="<?php echo $company['id']; ?>" <?php echo (isset($data['company_id']) && $data['company_id'] == $company['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity: <span class="required">*</span></label>
                        <input type="number" name="quantity" min="0" value="<?php echo $data['quantity']; ?>" required placeholder="Enter quantity">
                    </div>

                    <div class="form-group">
                        <label>Price: <span class="required">*</span></label>
                        <input type="number" name="price" step="0.01" min="0.01" value="<?php echo $data['price']; ?>" required placeholder="Selling price">
                    </div>

                    <div class="form-group">
                        <label>Cost Price: <span class="required">*</span></label>
                        <input type="number" name="cost_price" step="0.01" min="0.01" value="<?php echo $data['cost_price'] ?? 0; ?>" required placeholder="Purchase cost">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category_id" id="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($data['category_id']) && $data['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subcategory:</label>
                        <select name="subcategory_id" id="subcategory_id">
                            <option value="">Select Subcategory</option>
                            <?php 
                            if (isset($data['category_id'])) {
                                foreach ($subcategories as $subcat) {
                                    if ($subcat['category_id'] == $data['category_id']) {
                                        echo '<option value="' . $subcat['id'] . '" ' . (isset($data['subcategory_id']) && $data['subcategory_id'] == $subcat['id'] ? 'selected' : '') . '>' . htmlspecialchars($subcat['name']) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reorder Level:</label>
                        <input type="number" name="reorder_level" min="0" value="<?php echo isset($data['reorder_level']) ? $data['reorder_level'] : 5; ?>" placeholder="Reorder level">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Supplier:</label>
                        <input type="text" name="supplier" value="<?php echo htmlspecialchars(isset($data['supplier']) ? $data['supplier'] : ''); ?>" placeholder="Supplier name">
                    </div>

                    <div class="form-group">
                        <label>Expiry Date:</label>
                        <input type="date" name="expiry_date" value="<?php echo isset($data['expiry_date']) ? $data['expiry_date'] : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Image:</label>
                    <div class="image-upload-container">
                        <div class="image-preview" id="imagePreview">
                            <?php if (isset($data['image_path']) && $data['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($data['image_path']); ?>" alt="Current Image" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                <div class="current-image-label">Current Image</div>
                            <?php else: ?>
                                <div class="preview-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload image</p>
                                    <small>Drag & drop or click to select</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="product_image" id="productImage" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-secondary" id="chooseImageBtn">
                            <i class="fas fa-upload"></i> Choose New Image
                        </button>
                        <?php if (isset($data['image_path']) && $data['image_path']): ?>
                            <button type="button" class="btn btn-danger" id="removeImage">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        <?php endif; ?>
                    </div>
                    <small>Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB. Leave empty to keep current image.</small>
                </div>

                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3" placeholder="Product description..."><?php echo htmlspecialchars(isset($data['description']) ? $data['description'] : ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Quality:</label>
                    <textarea name="quality" rows="2" placeholder="Product quality details..."><?php echo htmlspecialchars(isset($data['quality']) ? $data['quality'] : ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Benefits:</label>
                    <textarea name="benefits" rows="2" placeholder="Product benefits and features..."><?php echo htmlspecialchars(isset($data['benefits']) ? $data['benefits'] : ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Specifications:</label>
                    <textarea name="specifications" rows="3" placeholder="Technical specifications..."><?php echo htmlspecialchars(isset($data['specifications']) ? $data['specifications'] : ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Barcode:</label>
                    <input type="text" name="barcode" value="<?php echo htmlspecialchars(isset($data['barcode']) ? $data['barcode'] : ''); ?>" placeholder="Product barcode">
                </div>

                <div class="form-actions">
                    <input type="submit" value="Update Product" class="btn btn-primary">
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
        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('productImage');
            const imagePreview = document.getElementById('imagePreview');
            const removeButton = document.getElementById('removeImage');
            const chooseImageBtn = document.getElementById('chooseImageBtn');

            if (chooseImageBtn) {
                chooseImageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (imageInput) imageInput.click();
                });
            }

            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (file.size > 5 * 1024 * 1024) {
                            alert('Image size must be less than 5MB');
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; max-height: 200px; object-fit: cover;">';
                            if (removeButton) removeButton.style.display = 'inline-block';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    if (imageInput) imageInput.value = '';
                    imagePreview.innerHTML = '<div class="preview-placeholder"><i class="fas fa-cloud-upload-alt"></i><p>Click to upload image</p><small>Drag & drop or click to select</small></div>';
                    removeButton.style.display = 'none';
                });
            }
        });
    </script>

</body>
</html>