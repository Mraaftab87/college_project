<?php
session_start();
include 'functions.php';
requireLogin();
header('Content-Type: application/json');

if (!isset($_GET['product_item_id'])) {
  echo json_encode([]);
  exit;
}

$product_item_id = (int)$_GET['product_item_id'];
include 'db.php';

$stmt = $conn->prepare(
  "SELECT ticm.category_id, c.name AS category_name, ticm.subcategory_id, sc.name AS subcategory_name
   FROM type_item_category_map ticm
   LEFT JOIN categories c ON c.id = ticm.category_id
   LEFT JOIN subcategories sc ON sc.id = ticm.subcategory_id
   WHERE ticm.product_item_id = ? LIMIT 1"
);
$stmt->bind_param('i', $product_item_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
echo json_encode($row ?: []);
?>

