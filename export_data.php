<?php
session_start();
include 'functions.php';
requireLogin();
include 'db.php';

$type = $_GET['type'] ?? 'products';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'products':
        exportProducts($conn, $output);
        break;
    case 'transactions':
        exportTransactions($conn, $output);
        break;
    case 'reports':
        exportReports($conn, $output);
        break;
    default:
        fputcsv($output, ['Error: Invalid export type']);
}

fclose($output);

function exportProducts($conn, $output) {
    // Get filters
    $category = $_GET['category'] ?? '';
    $stock = $_GET['stock'] ?? '';
    
    // Build query
    $query = "
        SELECT p.*, c.name as category_name, sc.name as subcategory_name,
               CASE 
                   WHEN p.quantity = 0 THEN 'Out of Stock'
                   WHEN p.quantity <= p.reorder_level THEN 'Low Stock'
                   ELSE 'In Stock'
               END as stock_status
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    if ($category) {
        $query .= " AND p.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }
    
    if ($stock) {
        switch ($stock) {
            case 'in-stock':
                $query .= " AND p.quantity > p.reorder_level";
                break;
            case 'low-stock':
                $query .= " AND p.quantity <= p.reorder_level AND p.quantity > 0";
                break;
            case 'out-of-stock':
                $query .= " AND p.quantity = 0";
                break;
        }
    }
    
    $query .= " ORDER BY p.name";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // CSV headers
    fputcsv($output, [
        'ID', 'Name', 'SKU', 'Barcode', 'Category', 'Subcategory', 'Description',
        'Quantity', 'Price', 'Cost Price', 'Stock Status', 'Reorder Level',
        'Supplier', 'Expiry Date', 'Quality', 'Benefits', 'Specifications',
        'Created Date'
    ]);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['sku'],
            $row['barcode'],
            $row['category_name'],
            $row['subcategory_name'],
            $row['description'],
            $row['quantity'],
            $row['price'],
            $row['cost_price'],
            $row['stock_status'],
            $row['reorder_level'],
            $row['supplier'],
            $row['expiry_date'],
            $row['quality'],
            $row['benefits'],
            $row['specifications'],
            $row['created_at']
        ]);
    }
}

function exportTransactions($conn, $output) {
    // Get filters
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $transactionType = $_GET['type'] ?? '';
    
    // Build query
    $query = "
        SELECT t.*, p.name as product_name, p.sku, u.username as user_name
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    if ($startDate) {
        $query .= " AND t.transaction_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    
    if ($endDate) {
        $query .= " AND t.transaction_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    
    if ($transactionType) {
        $query .= " AND t.type = ?";
        $params[] = $transactionType;
        $types .= 's';
    }
    
    $query .= " ORDER BY t.transaction_date DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // CSV headers
    fputcsv($output, [
        'ID', 'Product Name', 'SKU', 'Type', 'Quantity', 'Unit Price',
        'Total Amount', 'Transaction Date', 'Notes', 'User', 'Created Date'
    ]);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['product_name'],
            $row['sku'],
            ucfirst($row['type']),
            $row['quantity'],
            $row['unit_price'],
            $row['total_amount'],
            $row['transaction_date'],
            $row['notes'],
            $row['user_name'],
            $row['created_at']
        ]);
    }
}

function exportReports($conn, $output) {
    $reportType = $_GET['report_type'] ?? 'inventory-summary';
    $month = $_GET['month'] ?? date('Y-m');
    
    switch ($reportType) {
        case 'inventory-summary':
            exportInventorySummary($conn, $output);
            break;
        case 'sales-report':
            exportSalesReport($conn, $output, $month);
            break;
        case 'low-stock-report':
            exportLowStockReport($conn, $output);
            break;
        case 'profit-loss':
            exportProfitLossReport($conn, $output, $month);
            break;
    }
}

function exportInventorySummary($conn, $output) {
    $query = "
        SELECT 
            c.name as category,
            COUNT(p.id) as total_products,
            SUM(p.quantity) as total_quantity,
            SUM(p.quantity * p.price) as total_value,
            SUM(p.quantity * p.cost_price) as total_cost,
            COUNT(CASE WHEN p.quantity = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN p.quantity <= p.reorder_level AND p.quantity > 0 THEN 1 END) as low_stock
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id, c.name
        ORDER BY c.name
    ";
    
    $result = $conn->query($query);
    
    // CSV headers
    fputcsv($output, [
        'Category', 'Total Products', 'Total Quantity', 'Total Value (₹)',
        'Total Cost (₹)', 'Out of Stock', 'Low Stock'
    ]);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['category'],
            $row['total_products'],
            $row['total_quantity'],
            number_format($row['total_value'], 2),
            number_format($row['total_cost'], 2),
            $row['out_of_stock'],
            $row['low_stock']
        ]);
    }
}

function exportSalesReport($conn, $output, $month) {
    $query = "
        SELECT 
            DATE(t.transaction_date) as date,
            p.name as product_name,
            p.sku,
            t.type,
            t.quantity,
            t.unit_price,
            t.total_amount,
            u.username as user_name
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
        ORDER BY t.transaction_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // CSV headers
    fputcsv($output, [
        'Date', 'Product', 'SKU', 'Type', 'Quantity', 'Unit Price',
        'Total Amount', 'User'
    ]);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['date'],
            $row['product_name'],
            $row['sku'],
            ucfirst($row['type']),
            $row['quantity'],
            $row['unit_price'],
            $row['total_amount'],
            $row['user_name']
        ]);
    }
}

function exportLowStockReport($conn, $output) {
    $query = "
        SELECT p.*, c.name as category_name, sc.name as subcategory_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
        WHERE p.quantity <= p.reorder_level
        ORDER BY p.quantity ASC
    ";
    
    $result = $conn->query($query);
    
    // CSV headers
    fputcsv($output, [
        'Product Name', 'SKU', 'Category', 'Subcategory', 'Current Stock',
        'Reorder Level', 'Supplier', 'Last Updated'
    ]);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['sku'],
            $row['category_name'],
            $row['subcategory_name'],
            $row['quantity'],
            $row['reorder_level'],
            $row['supplier'],
            $row['updated_at']
        ]);
    }
}

function exportProfitLossReport($conn, $output, $month) {
    $query = "
        SELECT 
            DATE(t.transaction_date) as date,
            SUM(CASE WHEN t.type = 'buy' THEN t.total_amount ELSE 0 END) as total_purchases,
            SUM(CASE WHEN t.type = 'sell' THEN t.total_amount ELSE 0 END) as total_sales,
            COUNT(CASE WHEN t.type = 'buy' THEN 1 END) as purchase_count,
            COUNT(CASE WHEN t.type = 'sell' THEN 1 END) as sales_count
        FROM transactions t
        WHERE DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
        GROUP BY DATE(t.transaction_date)
        ORDER BY date
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // CSV headers
    fputcsv($output, [
        'Date', 'Total Purchases (₹)', 'Total Sales (₹)', 'Gross Profit (₹)',
        'Purchase Count', 'Sales Count', 'Profit Margin (%)'
    ]);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        $grossProfit = $row['total_sales'] - $row['total_purchases'];
        $profitMargin = $row['total_sales'] > 0 ? ($grossProfit / $row['total_sales']) * 100 : 0;
        
        fputcsv($output, [
            $row['date'],
            number_format($row['total_purchases'], 2),
            number_format($row['total_sales'], 2),
            number_format($grossProfit, 2),
            $row['purchase_count'],
            $row['sales_count'],
            number_format($profitMargin, 2)
        ]);
    }
}
?>
