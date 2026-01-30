<?php
session_start();
include 'functions.php';
requireLogin();

header('Content-Type: application/json');

include 'db.php';

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, sc.name as subcategory_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN subcategories sc ON p.subcategory_id = sc.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    
    // Format currency values
    $product['price'] = formatCurrency($product['price']);
    $product['cost_price'] = formatCurrency($product['cost_price']);
    
    echo json_encode($product);
} else {
    echo json_encode(['error' => 'Product not found']);
}
?>
