<?php
session_start();
include 'functions.php';
requireLogin();

header('Content-Type: application/json');

include 'db.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'No code provided']);
    exit;
}

// Search for product by barcode or SKU
$stmt = $conn->prepare("
    SELECT p.*, 
           c.name as category_name, 
           sc.name as subcategory_name,
           pt.name as product_type_name,
           pi.name as product_item_name,
           co.name as company_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
    LEFT JOIN product_types pt ON p.product_type_id = pt.id
    LEFT JOIN product_items pi ON p.product_item_id = pi.id
    LEFT JOIN companies co ON p.company_id = co.id
    WHERE p.barcode = ? OR p.sku = ?
    LIMIT 1
");

$stmt->bind_param("ss", $code, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    
    // Determine stock status
    if ($product['quantity'] == 0) {
        $product['stock_status'] = 'out-of-stock';
    } elseif ($product['quantity'] <= $product['reorder_level']) {
        $product['stock_status'] = 'low-stock-status';
    } else {
        $product['stock_status'] = 'in-stock';
    }
    
    // Format category display
    $product['category_display'] = ($product['category_name'] ?? 'N/A') . ' > ' . ($product['subcategory_name'] ?? 'N/A');
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found'
    ]);
}
?>
