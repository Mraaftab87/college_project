<?php
session_start();
include 'functions.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['product_type_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product type ID is required']);
    exit;
}

$product_type_id = (int)$_GET['product_type_id'];

include 'db.php';

$stmt = $conn->prepare("SELECT id, name FROM product_items WHERE product_type_id = ? ORDER BY name");
$stmt->bind_param("i", $product_type_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?> 