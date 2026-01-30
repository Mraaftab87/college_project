<?php
session_start();
include 'functions.php';
requireLogin();

// Get the type of template requested
$type = $_GET['type'] ?? '';

// Set appropriate headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $type . '_template.csv"');

// Create output stream
$output = fopen('php://output', 'w');

switch ($type) {
    case 'products':
        // Products template
        fputcsv($output, [
            'name',
            'quantity',
            'price',
            'cost_price',
            'description',
            'quality',
            'benefits',
            'specifications',
            'product_type',
            'product_item',
            'company',
            'category',
            'subcategory',
            'sku',
            'barcode',
            'supplier',
            'reorder_level',
            'expiry_date'
        ]);
        
        // Add sample data
        fputcsv($output, [
            'Sample Product',
            '100',
            '1500.00',
            '1200.00',
            'Sample product description',
            'High quality product',
            'Multiple benefits',
            'Technical specifications',
            'Electronics',
            'Mobile Phone',
            'Samsung',
            'Electronics',
            'Smartphones',
            'SAMPLE-SKU-001',
            '1234567890123',
            'Sample Supplier',
            '10',
            '2025-12-31'
        ]);
        break;
        
    case 'transactions':
        // Transactions template
        fputcsv($output, [
            'product_name',
            'type',
            'quantity',
            'unit_price',
            'total_amount',
            'transaction_date',
            'notes',
            'customer_email'
        ]);
        
        // Add sample data
        fputcsv($output, [
            'Sample Product',
            'sell',
            '5',
            '1500.00',
            '7500.00',
            '2025-01-15',
            'Sample transaction',
            'customer@example.com'
        ]);
        break;
        
    case 'categories':
        // Categories template
        fputcsv($output, [
            'category_name',
            'description',
            'subcategory_name',
            'subcategory_description'
        ]);
        
        // Add sample data
        fputcsv($output, [
            'Electronics',
            'Electronic devices and accessories',
            'Smartphones',
            'Mobile phones and related accessories'
        ]);
        break;
        
    default:
        // Default template
        fputcsv($output, ['Column 1', 'Column 2', 'Column 3']);
        fputcsv($output, ['Sample Data 1', 'Sample Data 2', 'Sample Data 3']);
}

fclose($output);
exit;
?> 