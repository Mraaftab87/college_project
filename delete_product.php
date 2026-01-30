<?php
session_start();
include 'functions.php';
requirePermission('delete_product'); // Only admins can delete products
?>
<?php
include 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid product ID.');
    header("Location: view_products.php");
    exit;
}

// CSRF validate
if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request token.');
    header("Location: view_products.php");
    exit;
}

$id = $_GET['id'];

// Optional: Check if product exists before delete
$check = $conn->prepare("SELECT * FROM products WHERE id=?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    setFlashMessage('error', 'Product not found.');
    header("Location: view_products.php");
    exit;
}

$imgQ = $conn->prepare("SELECT image_path FROM products WHERE id=?");
$imgQ->bind_param("i", $id);
$imgQ->execute();
$imgRes = $imgQ->get_result();
$imgPath = null;
if ($row = $imgRes->fetch_assoc()) { $imgPath = $row['image_path'] ?? null; }

$stmt = $conn->prepare("DELETE FROM products WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Remove image and thumbnail
    if ($imgPath && file_exists($imgPath)) { @unlink($imgPath); }
    $thumb = preg_replace('~^images/products/~','images/products/thumbs/',$imgPath ?? '');
    if (!empty($thumb) && file_exists($thumb)) { @unlink($thumb); }
    setFlashMessage('success', 'Product deleted successfully.');
} else {
    setFlashMessage('error', 'Error deleting product.');
}

header("Location: view_products.php");
exit;
?>
