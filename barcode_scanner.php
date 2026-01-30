<?php
session_start();
include 'functions.php';
requirePermission('add_product'); // Only users with add_product permission can access scanner
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Barcode Scanner - Smart Inventory System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest"></script>
</head>

<body>
    <?php include 'db.php'; ?>

    <header class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; color: white;">
            <i class="fas fa-warehouse fa-2x"></i>
            <span>Smart Inventory System</span>
        </a>
        <nav class="nav-links">
            <?php if (hasPermission('add_product')): ?>
                <a href="add_product.php"><i class="fas fa-plus"></i> Add Product</a>
            <?php endif; ?>
            <a href="view_products.php"><i class="fas fa-boxes"></i> View Products</a>
            <?php if (hasPermission('view_transactions')): ?>
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
            <?php endif; ?>
            <?php if (hasPermission('view_reports')): ?>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="barcode_scanner.php" class="active"><i class="fas fa-barcode"></i> Scanner</a>
            <?php if (hasPermission('add_product')): ?>
                <a href="bulk_operations.php"><i class="fas fa-upload"></i> Bulk Operations</a>
            <?php endif; ?>
            <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <div class="hero">
            <h1><i class="fas fa-barcode"></i> Barcode & QR Code Scanner</h1>
            <p>Scan product barcodes for quick inventory management</p>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-barcode"></i> Barcode & QR Code Scanner</h3>

            <div class="scanner-container">
                <div class="scanner-video">
                    <video id="scanner-video" width="100%" height="400px"></video>
                    <div class="scanner-overlay">
                        <div class="scanner-frame"></div>
                        <p class="scanner-instructions">Position barcode/QR code within the frame</p>
                    </div>
                </div>

                <div class="scanner-controls">
                    <button id="start-scanner" class="btn btn-primary">
                        <i class="fas fa-play"></i> Start Scanner
                    </button>
                    <button id="stop-scanner" class="btn btn-secondary" style="display: none;">
                        <i class="fas fa-stop"></i> Stop Scanner
                    </button>
                    <button id="switch-camera" class="btn btn-info">
                        <i class="fas fa-camera"></i> Switch Camera
                    </button>
                </div>

                <div class="manual-input">
                    <h4>Manual Entry</h4>
                    <div class="form-group">
                        <input type="text" id="manual-barcode" placeholder="Enter barcode/QR code manually">
                        <button id="search-barcode" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>

            <div id="scan-result" class="scan-result" style="display: none;">
                <h4>Scan Result</h4>
                <div id="result-content"></div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
    </footer>

    <script>
        let codeReader;
        let selectedDeviceId;
        let videoElement;
        let isScanning = false;

        document.addEventListener('DOMContentLoaded', function() {
            videoElement = document.getElementById('scanner-video');
            codeReader = new ZXing.BrowserMultiFormatReader();

            // Start scanner button
            document.getElementById('start-scanner').addEventListener('click', startScanner);

            // Stop scanner button
            document.getElementById('stop-scanner').addEventListener('click', stopScanner);

            // Switch camera button
            document.getElementById('switch-camera').addEventListener('click', switchCamera);

            // Manual search button
            document.getElementById('search-barcode').addEventListener('click', searchManualBarcode);

            // Enter key for manual input
            document.getElementById('manual-barcode').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchManualBarcode();
                }
            });
        });

        async function startScanner() {
            try {
                const videoInputDevices = await ZXing.BrowserMultiFormatReader.listVideoInputDevices();

                if (videoInputDevices.length === 0) {
                    alert('No camera found!');
                    return;
                }

                selectedDeviceId = selectedDeviceId || videoInputDevices[0].deviceId;

                await codeReader.decodeFromVideoDevice(selectedDeviceId, videoElement, (result, err) => {
                    if (result) {
                        handleScanResult(result.text);
                    }
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error(err);
                    }
                });

                isScanning = true;
                document.getElementById('start-scanner').style.display = 'none';
                document.getElementById('stop-scanner').style.display = 'inline-block';

            } catch (err) {
                console.error(err);
                alert('Error starting scanner: ' + err.message);
            }
        }

        function stopScanner() {
            if (codeReader) {
                codeReader.reset();
                isScanning = false;
                document.getElementById('start-scanner').style.display = 'inline-block';
                document.getElementById('stop-scanner').style.display = 'none';
            }
        }

        async function switchCamera() {
            if (isScanning) {
                stopScanner();
            }

            try {
                const videoInputDevices = await ZXing.BrowserMultiFormatReader.listVideoInputDevices();

                if (videoInputDevices.length <= 1) {
                    alert('Only one camera available!');
                    return;
                }

                // Find next camera
                const currentIndex = videoInputDevices.findIndex(device => device.deviceId === selectedDeviceId);
                const nextIndex = (currentIndex + 1) % videoInputDevices.length;
                selectedDeviceId = videoInputDevices[nextIndex].deviceId;

                if (isScanning) {
                    startScanner();
                }

            } catch (err) {
                console.error(err);
                alert('Error switching camera: ' + err.message);
            }
        }

        function searchManualBarcode() {
            const barcode = document.getElementById('manual-barcode').value.trim();
            if (barcode) {
                handleScanResult(barcode);
            } else {
                alert('Please enter a barcode/QR code');
            }
        }

        function handleScanResult(code) {
            // Show result container
            document.getElementById('scan-result').style.display = 'block';
            document.getElementById('result-content').innerHTML = '<p>Searching for: <strong>' + code + '</strong></p>';

            // Search in database
            fetch('search_by_barcode.php?code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayProductResult(data.product);
                    } else {
                        displayNoResult(code);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayError('Error searching for product');
                });
        }

        function displayProductResult(product) {
            const resultDiv = document.getElementById('result-content');
            resultDiv.innerHTML = `
                <div class="product-found">
                    <div class="product-image">
                        ${product.image_path ? 
                            `<img src="${product.image_path}" alt="${product.name}">` : 
                            '<div class="no-image"><i class="fas fa-image"></i></div>'
                        }
                    </div>
                    <div class="product-info">
                        <h3>${product.name}</h3>
                        <p><strong>SKU:</strong> ${product.sku || 'N/A'}</p>
                        <p><strong>Barcode:</strong> ${product.barcode || 'N/A'}</p>
                        <p><strong>Category:</strong> ${product.category_name} > ${product.subcategory_name}</p>
                        <p><strong>Stock:</strong> <span class="stock-${product.stock_status}">${product.quantity}</span></p>
                        <p><strong>Price:</strong> ₹${product.price}</p>
                        <p><strong>Cost:</strong> ₹${product.cost_price}</p>
                        <div class="product-actions">
                            <a href="view_products.php?id=${product.id}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="edit_product.php?id=${product.id}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="transactions.php?product_id=${product.id}" class="btn btn-primary btn-sm">
                                <i class="fas fa-exchange-alt"></i> Add Transaction
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }

        function displayNoResult(code) {
            document.getElementById('result-content').innerHTML = `
                <div class="no-product-found">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Product Not Found</h4>
                    <p>No product found with barcode/QR code: <strong>${code}</strong></p>
                    <a href="add_product.php?barcode=${encodeURIComponent(code)}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
            `;
        }

        function displayError(message) {
            document.getElementById('result-content').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-times-circle"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>

</html>