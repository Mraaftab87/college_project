<?php
session_start();
include 'functions.php';
requireLogin();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request method.');
    header("Location: bulk_operations.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid form token. Please try again.');
    header("Location: bulk_operations.php");
    exit;
}

$type = $_POST['type'] ?? '';
$skip_duplicates = isset($_POST['skip_duplicates']);
$update_existing = isset($_POST['update_existing']);
$validate_products = isset($_POST['validate_products']);

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    setFlashMessage('error', 'Please select a valid CSV file.');
    header("Location: bulk_operations.php");
    exit;
}

$file = $_FILES['csv_file'];
$filename = $file['name'];
$tmp_name = $file['tmp_name'];

// Validate file type
if (pathinfo($filename, PATHINFO_EXTENSION) !== 'csv') {
    setFlashMessage('error', 'Please upload a CSV file.');
    header("Location: bulk_operations.php");
    exit;
}

// Read CSV file
$handle = fopen($tmp_name, 'r');
if (!$handle) {
    setFlashMessage('error', 'Unable to read the uploaded file.');
    header("Location: bulk_operations.php");
    exit;
}

$headers = fgetcsv($handle);
if (!$headers) {
    setFlashMessage('error', 'Invalid CSV format.');
    fclose($handle);
    header("Location: bulk_operations.php");
    exit;
}

$success_count = 0;
$error_count = 0;
$errors = [];

switch ($type) {
    case 'products':
        $success_count = processProductsImport($handle, $headers, $skip_duplicates, $update_existing, $errors);
        break;
        
    case 'transactions':
        $success_count = processTransactionsImport($handle, $headers, $validate_products, $errors);
        break;
        
    case 'categories':
        $success_count = processCategoriesImport($handle, $headers, $errors);
        break;
        
    default:
        setFlashMessage('error', 'Invalid import type.');
        fclose($handle);
        header("Location: bulk_operations.php");
        exit;
}

fclose($handle);

$error_count = count($errors);
$message = "Import completed. Successfully imported: $success_count records.";
if ($error_count > 0) {
    $message .= " Errors: $error_count records.";
    if (count($errors) <= 5) {
        $message .= " Error details: " . implode(', ', $errors);
    }
}

setFlashMessage($error_count > 0 ? 'warning' : 'success', $message);
header("Location: bulk_operations.php");
exit;

function processProductsImport($handle, $headers, $skip_duplicates, $update_existing, &$errors) {
    global $conn;
    $success_count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) !== count($headers)) {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Column count mismatch";
            continue;
        }
        
        $row = array_combine($headers, $data);
        
        // Validate required fields
        if (empty($row['name']) || empty($row['quantity']) || empty($row['price']) || empty($row['cost_price'])) {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Missing required fields";
            continue;
        }
        
        // Check for duplicates if skip_duplicates is enabled
        if ($skip_duplicates && !empty($row['sku'])) {
            $check_stmt = $conn->prepare("SELECT id FROM products WHERE sku = ?");
            $check_stmt->bind_param("s", $row['sku']);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                continue; // Skip duplicate
            }
        }
        
        // Resolve foreign keys
        $product_type_id = resolveProductType($row['product_type'] ?? '');
        $product_item_id = resolveProductItem($row['product_item'] ?? '', $product_type_id);
        $company_id = resolveCompany($row['company'] ?? '');
        $category_id = resolveCategory($row['category'] ?? '');
        $subcategory_id = resolveSubcategory($row['subcategory'] ?? '', $category_id);
        
        // Prepare insert statement
        $stmt = $conn->prepare("INSERT INTO products (name, quantity, price, cost_price, description, quality, benefits, specifications, product_type_id, product_item_id, company_id, category_id, subcategory_id, sku, barcode, supplier, reorder_level, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $quantity = (int)$row['quantity'];
        $price = (float)$row['price'];
        $cost_price = (float)$row['cost_price'];
        $reorder_level = (int)($row['reorder_level'] ?? 5);
        $expiry_date = !empty($row['expiry_date']) ? $row['expiry_date'] : null;
        
        $stmt->bind_param("siddssssiiiiissssis", 
            $row['name'], $quantity, $price, $cost_price,
            $row['description'] ?? '', $row['quality'] ?? '', $row['benefits'] ?? '', $row['specifications'] ?? '',
            $product_type_id, $product_item_id, $company_id, $category_id, $subcategory_id,
            $row['sku'] ?? '', $row['barcode'] ?? '', $row['supplier'] ?? '', $reorder_level, $expiry_date
        );
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": " . $stmt->error;
        }
    }
    
    return $success_count;
}

function processTransactionsImport($handle, $headers, $validate_products, &$errors) {
    global $conn;
    $success_count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) !== count($headers)) {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Column count mismatch";
            continue;
        }
        
        $row = array_combine($headers, $data);
        
        // Validate required fields
        if (empty($row['product_name']) || empty($row['type']) || empty($row['quantity']) || empty($row['unit_price'])) {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Missing required fields";
            continue;
        }
        
        // Validate product exists if required
        if ($validate_products) {
            $product_stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
            $product_stmt->bind_param("s", $row['product_name']);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            if ($product_result->num_rows === 0) {
                $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Product not found";
                continue;
            }
            $product_id = $product_result->fetch_assoc()['id'];
        } else {
            $product_id = 1; // Default product ID
        }
        
        // Resolve customer
        $customer_id = null;
        if (!empty($row['customer_email'])) {
            $customer_stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            $customer_stmt->bind_param("s", $row['customer_email']);
            $customer_stmt->execute();
            $customer_result = $customer_stmt->get_result();
            if ($customer_result->num_rows > 0) {
                $customer_id = $customer_result->fetch_assoc()['id'];
            }
        }
        
        // Calculate total amount
        $total_amount = (float)$row['quantity'] * (float)$row['unit_price'];
        
        // Insert transaction
        $stmt = $conn->prepare("INSERT INTO transactions (product_id, type, quantity, unit_price, total_amount, transaction_date, notes, user_id, customer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $quantity = (int)$row['quantity'];
        $unit_price = (float)$row['unit_price'];
        $transaction_date = !empty($row['transaction_date']) ? $row['transaction_date'] : date('Y-m-d');
        $user_id = $_SESSION['user_id'] ?? 1;
        
        $stmt->bind_param("isidssiis", 
            $product_id, $row['type'], $quantity, $unit_price, $total_amount,
            $transaction_date, $row['notes'] ?? '', $user_id, $customer_id
        );
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": " . $stmt->error;
        }
    }
    
    return $success_count;
}

function processCategoriesImport($handle, $headers, &$errors) {
    global $conn;
    $success_count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) !== count($headers)) {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Column count mismatch";
            continue;
        }
        
        $row = array_combine($headers, $data);
        
        // Validate required fields
        if (empty($row['category_name'])) {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": Missing category name";
            continue;
        }
        
        // Insert category
        $category_stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
        $category_stmt->bind_param("ss", $row['category_name'], $row['description'] ?? '');
        
        if ($category_stmt->execute()) {
            $category_id = $conn->insert_id;
            
            // Insert subcategory if provided
            if (!empty($row['subcategory_name'])) {
                $subcategory_stmt = $conn->prepare("INSERT IGNORE INTO subcategories (category_id, name, description) VALUES (?, ?, ?)");
                $subcategory_stmt->bind_param("iss", $category_id, $row['subcategory_name'], $row['subcategory_description'] ?? '');
                $subcategory_stmt->execute();
            }
            
            $success_count++;
        } else {
            $errors[] = "Row " . ($success_count + count($errors) + 1) . ": " . $category_stmt->error;
        }
    }
    
    return $success_count;
}

// Helper functions for resolving foreign keys
function resolveProductType($name) {
    global $conn;
    if (empty($name)) return null;
    
    $stmt = $conn->prepare("SELECT id FROM product_types WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    
    // Create new product type
    $insert_stmt = $conn->prepare("INSERT INTO product_types (name) VALUES (?)");
    $insert_stmt->bind_param("s", $name);
    $insert_stmt->execute();
    return $conn->insert_id;
}

function resolveProductItem($name, $product_type_id) {
    global $conn;
    if (empty($name) || !$product_type_id) return null;
    
    $stmt = $conn->prepare("SELECT id FROM product_items WHERE name = ? AND product_type_id = ?");
    $stmt->bind_param("si", $name, $product_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    
    // Create new product item
    $insert_stmt = $conn->prepare("INSERT INTO product_items (product_type_id, name) VALUES (?, ?)");
    $insert_stmt->bind_param("is", $product_type_id, $name);
    $insert_stmt->execute();
    return $conn->insert_id;
}

function resolveCompany($name) {
    global $conn;
    if (empty($name)) return null;
    
    $stmt = $conn->prepare("SELECT id FROM companies WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    
    // Create new company
    $insert_stmt = $conn->prepare("INSERT INTO companies (name) VALUES (?)");
    $insert_stmt->bind_param("s", $name);
    $insert_stmt->execute();
    return $conn->insert_id;
}

function resolveCategory($name) {
    global $conn;
    if (empty($name)) return null;
    
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    
    // Create new category
    $insert_stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $insert_stmt->bind_param("s", $name);
    $insert_stmt->execute();
    return $conn->insert_id;
}

function resolveSubcategory($name, $category_id) {
    global $conn;
    if (empty($name) || !$category_id) return null;
    
    $stmt = $conn->prepare("SELECT id FROM subcategories WHERE name = ? AND category_id = ?");
    $stmt->bind_param("si", $name, $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    
    // Create new subcategory
    $insert_stmt = $conn->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, ?)");
    $insert_stmt->bind_param("is", $category_id, $name);
    $insert_stmt->execute();
    return $conn->insert_id;
}
?> 