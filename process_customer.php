<?php
session_start();
include 'functions.php';
requireLogin();
include 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        addCustomer();
        break;
    case 'activate':
        activateCustomer();
        break;
    case 'deactivate':
        deactivateCustomer();
        break;
    default:
        header('Location: customers.php');
        exit;
}

function addCustomer() {
    global $conn;
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $customer_type = $_POST['customer_type'];
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $gst_number = trim($_POST['gst_number']);
    $credit_limit = floatval($_POST['credit_limit'] ?? 0);
    $notes = trim($_POST['notes']);
    
    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: customers.php');
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'A customer with this email already exists.';
        header('Location: customers.php');
        exit;
    }
    
    // Insert customer
    $stmt = $conn->prepare("
        INSERT INTO customers (full_name, email, phone, customer_type, address, city, state, pincode, gst_number, credit_limit, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param('ssssssssdss', $full_name, $email, $phone, $customer_type, $address, $city, $state, $pincode, $gst_number, $credit_limit, $notes);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Customer added successfully!';
    } else {
        $_SESSION['error'] = 'Error adding customer: ' . $conn->error;
    }
    
    header('Location: customers.php');
    exit;
}

function activateCustomer() {
    global $conn;
    
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("UPDATE customers SET is_active = 1 WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Customer activated successfully!';
    } else {
        $_SESSION['error'] = 'Error activating customer.';
    }
    
    header('Location: customers.php');
    exit;
}

function deactivateCustomer() {
    global $conn;
    
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("UPDATE customers SET is_active = 0 WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Customer deactivated successfully!';
    } else {
        $_SESSION['error'] = 'Error deactivating customer.';
    }
    
    header('Location: customers.php');
    exit;
}
?>
