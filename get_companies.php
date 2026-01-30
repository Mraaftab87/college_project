<?php
session_start();
include 'functions.php';
requireLogin();
header('Content-Type: application/json');

if (!isset($_GET['product_item_id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'product_item_id is required']);
  exit;
}

$product_item_id = (int)$_GET['product_item_id'];
include 'db.php';

$stmt = $conn->prepare(
  "SELECT c.id, c.name FROM company_item_map m
   JOIN companies c ON c.id = m.company_id
   WHERE m.product_item_id = ? ORDER BY c.name"
);
$stmt->bind_param('i', $product_item_id);
$stmt->execute();
$res = $stmt->get_result();
$companies = [];
while ($row = $res->fetch_assoc()) { $companies[] = $row; }

// Only return companies mapped to this product item
// We don't want to show all companies if none are mapped

echo json_encode($companies);