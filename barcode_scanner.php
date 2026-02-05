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
    <script src="https://unpkg.com/@zxing/library@0.19.1"></script>
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
                <!-- Camera Status Alert -->
                <div id="camera-status" class="camera-status" style="display: none;">
                    <div class="status-icon">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="status-message">
                        <h4>Checking camera...</h4>
                        <p>Please wait while we detect your camera</p>
                    </div>
                </div>

                <div class="scanner-video" id="scanner-video-container">
                    <video id="scanner-video" width="100%" height="400px" playsinline></video>
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
                    <button id="switch-camera" class="btn btn-info" style="display: none;">
                        <i class="fas fa-camera"></i> Switch Camera
                    </button>
                    <button id="refresh-camera" class="btn btn-warning" style="display: none;">
                        <i class="fas fa-sync"></i> Refresh Camera
                    </button>
                </div>

                <div id="camera-info" class="camera-info" style="display: none;">
                    <p><i class="fas fa-video"></i> <span id="camera-name">No camera selected</span></p>
                    <p><i class="fas fa-list"></i> Available cameras: <span id="camera-count">0</span></p>
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
        let availableCameras = [];
        let hasCamera = false;
        let cameraPermissionGranted = false;

        document.addEventListener('DOMContentLoaded', function() {
            videoElement = document.getElementById('scanner-video');
            codeReader = new ZXing.BrowserMultiFormatReader();

            // Check camera availability on page load
            checkCameraAvailability();

            // Start scanner button
            document.getElementById('start-scanner').addEventListener('click', startScanner);

            // Stop scanner button
            document.getElementById('stop-scanner').addEventListener('click', stopScanner);

            // Switch camera button
            document.getElementById('switch-camera').addEventListener('click', switchCamera);

            // Refresh camera button
            document.getElementById('refresh-camera').addEventListener('click', checkCameraAvailability);

            // Manual search button
            document.getElementById('search-barcode').addEventListener('click', searchManualBarcode);

            // Enter key for manual input
            document.getElementById('manual-barcode').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchManualBarcode();
                }
            });
        });

        async function checkCameraAvailability() {
            showCameraStatus('checking', 'Checking camera...', 'Please wait while we detect your camera');

            try {
                // First check if mediaDevices is supported
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showCameraStatus('error', 'Camera Not Supported', 'Your browser does not support camera access. Please use a modern browser like Chrome, Firefox, or Edge.');
                    disableScanner();
                    return;
                }

                // Request camera permission
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    stream.getTracks().forEach(track => track.stop());
                    cameraPermissionGranted = true;
                } catch (permErr) {
                    if (permErr.name === 'NotAllowedError' || permErr.name === 'PermissionDeniedError') {
                        showCameraStatus('error', 'Camera Permission Denied', 'Please allow camera access in your browser settings and refresh the page.');
                        disableScanner();
                        return;
                    }
                }

                // List available cameras using correct API
                const devices = await navigator.mediaDevices.enumerateDevices();
                availableCameras = devices.filter(device => device.kind === 'videoinput');

                if (availableCameras.length === 0) {
                    showCameraStatus('error', 'No Camera Connected', 'No camera device detected. Please connect a camera and click "Refresh Camera".');
                    disableScanner();
                    document.getElementById('refresh-camera').style.display = 'inline-block';
                    return;
                }

                // Camera found!
                hasCamera = true;
                selectedDeviceId = selectedDeviceId || availableCameras[0].deviceId;
                
                showCameraStatus('success', 'Camera Ready!', `${availableCameras.length} camera(s) detected. Click "Start Scanner" to begin.`);
                
                // Update camera info
                updateCameraInfo();
                
                // Enable scanner controls
                enableScanner();

                // Hide status after 3 seconds
                setTimeout(() => {
                    document.getElementById('camera-status').style.display = 'none';
                }, 3000);

            } catch (err) {
                console.error('Camera check error:', err);
                showCameraStatus('error', 'Camera Error', `Error detecting camera: ${err.message}`);
                disableScanner();
                document.getElementById('refresh-camera').style.display = 'inline-block';
            }
        }

        function showCameraStatus(type, title, message) {
            const statusDiv = document.getElementById('camera-status');
            const iconDiv = statusDiv.querySelector('.status-icon');
            const titleDiv = statusDiv.querySelector('.status-message h4');
            const messageDiv = statusDiv.querySelector('.status-message p');

            statusDiv.style.display = 'flex';
            statusDiv.className = 'camera-status camera-status-' + type;

            if (type === 'checking') {
                iconDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            } else if (type === 'success') {
                iconDiv.innerHTML = '<i class="fas fa-check-circle"></i>';
            } else if (type === 'error') {
                iconDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            }

            titleDiv.textContent = title;
            messageDiv.textContent = message;
        }

        function updateCameraInfo() {
            const cameraInfo = document.getElementById('camera-info');
            const cameraName = document.getElementById('camera-name');
            const cameraCount = document.getElementById('camera-count');

            if (availableCameras.length > 0) {
                const currentCamera = availableCameras.find(cam => cam.deviceId === selectedDeviceId);
                cameraName.textContent = currentCamera ? currentCamera.label || 'Camera ' + (availableCameras.indexOf(currentCamera) + 1) : 'Unknown';
                cameraCount.textContent = availableCameras.length;
                cameraInfo.style.display = 'block';

                // Show switch camera button if multiple cameras
                if (availableCameras.length > 1) {
                    document.getElementById('switch-camera').style.display = 'inline-block';
                }
            }
        }

        function enableScanner() {
            document.getElementById('start-scanner').disabled = false;
            document.getElementById('start-scanner').style.opacity = '1';
        }

        function disableScanner() {
            document.getElementById('start-scanner').disabled = true;
            document.getElementById('start-scanner').style.opacity = '0.5';
            document.getElementById('switch-camera').style.display = 'none';
        }

        async function startScanner() {
            if (!hasCamera) {
                showCameraStatus('error', 'No Camera Available', 'Please connect a camera and refresh.');
                return;
            }

            try {
                showCameraStatus('checking', 'Starting Scanner...', 'Initializing camera feed');

                await codeReader.decodeFromVideoDevice(selectedDeviceId, videoElement, (result, err) => {
                    if (result) {
                        // Play beep sound on successful scan
                        playBeep();
                        handleScanResult(result.text);
                    }
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error(err);
                    }
                });

                isScanning = true;
                document.getElementById('start-scanner').style.display = 'none';
                document.getElementById('stop-scanner').style.display = 'inline-block';
                document.getElementById('scanner-video-container').style.display = 'block';

                showCameraStatus('success', 'Scanner Active', 'Point your camera at a barcode or QR code');
                setTimeout(() => {
                    document.getElementById('camera-status').style.display = 'none';
                }, 2000);

            } catch (err) {
                console.error('Scanner start error:', err);
                showCameraStatus('error', 'Scanner Error', `Failed to start scanner: ${err.message}`);
                isScanning = false;
            }
        }

        function stopScanner() {
            if (codeReader) {
                codeReader.reset();
                isScanning = false;
                document.getElementById('start-scanner').style.display = 'inline-block';
                document.getElementById('stop-scanner').style.display = 'none';
                showCameraStatus('success', 'Scanner Stopped', 'Camera feed stopped');
                setTimeout(() => {
                    document.getElementById('camera-status').style.display = 'none';
                }, 2000);
            }
        }

        async function switchCamera() {
            if (availableCameras.length <= 1) {
                showCameraStatus('error', 'Only One Camera', 'No other camera available to switch to.');
                setTimeout(() => {
                    document.getElementById('camera-status').style.display = 'none';
                }, 2000);
                return;
            }

            const wasScanning = isScanning;
            if (isScanning) {
                stopScanner();
            }

            try {
                // Find next camera
                const currentIndex = availableCameras.findIndex(device => device.deviceId === selectedDeviceId);
                const nextIndex = (currentIndex + 1) % availableCameras.length;
                selectedDeviceId = availableCameras[nextIndex].deviceId;

                updateCameraInfo();
                showCameraStatus('success', 'Camera Switched', `Now using: ${availableCameras[nextIndex].label || 'Camera ' + (nextIndex + 1)}`);
                
                setTimeout(() => {
                    document.getElementById('camera-status').style.display = 'none';
                }, 2000);

                if (wasScanning) {
                    setTimeout(() => startScanner(), 500);
                }

            } catch (err) {
                console.error('Camera switch error:', err);
                showCameraStatus('error', 'Switch Failed', `Error switching camera: ${err.message}`);
            }
        }

        function playBeep() {
            // Create a simple beep sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
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
            
            // Add scan success animation
            document.getElementById('scan-result').style.animation = 'slideUp 0.3s ease-out';
            
            resultDiv.innerHTML = `
                <div class="product-found">
                    <div class="product-image">
                        ${product.image_path ? 
                            `<img src="${product.image_path}" alt="${product.name}">` : 
                            '<div class="no-image"><i class="fas fa-box-open fa-3x"></i></div>'
                        }
                    </div>
                    <div class="product-info">
                        <div class="product-header">
                            <h3><i class="fas fa-check-circle" style="color: #28a745;"></i> ${product.name}</h3>
                            <span class="badge badge-${product.stock_status === 'in-stock' ? 'success' : product.stock_status === 'low-stock-status' ? 'warning' : 'danger'}">
                                ${product.stock_status === 'in-stock' ? 'In Stock' : product.stock_status === 'low-stock-status' ? 'Low Stock' : 'Out of Stock'}
                            </span>
                        </div>
                        <div class="product-details-grid">
                            <div class="detail-box">
                                <i class="fas fa-barcode"></i>
                                <div>
                                    <span class="detail-label">Barcode</span>
                                    <span class="detail-value">${product.barcode || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="detail-box">
                                <i class="fas fa-tag"></i>
                                <div>
                                    <span class="detail-label">SKU</span>
                                    <span class="detail-value">${product.sku || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="detail-box">
                                <i class="fas fa-layer-group"></i>
                                <div>
                                    <span class="detail-label">Category</span>
                                    <span class="detail-value">${product.category_display}</span>
                                </div>
                            </div>
                            <div class="detail-box">
                                <i class="fas fa-cubes"></i>
                                <div>
                                    <span class="detail-label">Stock</span>
                                    <span class="detail-value stock-${product.stock_status}">${product.quantity} units</span>
                                </div>
                            </div>
                            <div class="detail-box">
                                <i class="fas fa-rupee-sign"></i>
                                <div>
                                    <span class="detail-label">Selling Price</span>
                                    <span class="detail-value price">₹${parseFloat(product.price).toFixed(2)}</span>
                                </div>
                            </div>
                            <div class="detail-box">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <span class="detail-label">Cost Price</span>
                                    <span class="detail-value">₹${parseFloat(product.cost_price).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                        ${product.description ? `
                            <div class="product-description">
                                <strong><i class="fas fa-info-circle"></i> Description:</strong>
                                <p>${product.description}</p>
                            </div>
                        ` : ''}
                        <div class="product-actions">
                            <a href="view_products.php?id=${product.id}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="edit_product.php?id=${product.id}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Edit Product
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
            document.getElementById('scan-result').style.animation = 'slideUp 0.3s ease-out';
            document.getElementById('result-content').innerHTML = `
                <div class="no-product-found">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Product Not Found</h4>
                    <p>No product found with barcode/QR code: <strong>${code}</strong></p>
                    <p class="suggestion">This code might not be registered in the system yet.</p>
                    <a href="add_product.php?barcode=${encodeURIComponent(code)}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Product with this Code
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