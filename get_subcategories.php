<?php
session_start();
include 'functions.php';
requireLogin();

header('Content-Type: application/json');

include 'db.php';

$category_id = $_GET['category_id'] ?? null;

if (!$category_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY name");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

$subcategories = [];
while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

echo json_encode($subcategories);
?>
