<?php
// Shared functions for the inventory system

// Ensure database connection is available
if (!isset($conn)) {
    require_once __DIR__ . '/db.php';
}

// Function to ensure subcategory and category are consistent
function validateCategoryConsistency($category_id, $subcategory_id)
{
    global $conn;

    // Check if the subcategory belongs to the selected category
    $stmt = $conn->prepare("SELECT category_id FROM subcategories WHERE id = ?");
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['category_id'] == $category_id;
    }

    return false;
}

// Function to set flash messages
function setFlashMessage($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to get and clear flash message
function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['username']);
}

// Function to redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Function to get current user's role
function getUserRole()
{
    return $_SESSION['role'] ?? 'user';
}

// Function to check if user has specific role
function hasRole($role)
{
    return getUserRole() === $role;
}

// Function to check if user has admin role
function isAdmin()
{
    return hasRole('admin');
}

// Function to check if user has manager role
function isManager()
{
    return hasRole('manager') || hasRole('admin');
}

// Function to get role permissions
function getRolePermissions($role = null)
{
    if ($role === null) {
        $role = getUserRole();
    }

    $permissions = [
        'user' => [
            'view_dashboard' => true,
            'view_products' => true,
            'search_products' => true,
            'add_product' => false,
            'edit_product' => false,
            'delete_product' => false,
            'manage_users' => false,
            'view_reports' => false,
            'system_settings' => false,
            'view_transactions' => false,
            'add_transactions' => false,
            'delete_transactions' => false,
            'view_user_access' => true
        ],
        'manager' => [
            'view_dashboard' => true,
            'view_products' => true,
            'search_products' => true,
            'add_product' => false,
            'edit_product' => false,
            'delete_product' => false,
            'manage_users' => false,
            'view_reports' => true,
            'system_settings' => false,
            'view_transactions' => true,
            'add_transactions' => true,
            'delete_transactions' => false,
            'view_user_access' => true
        ],
        'admin' => [
            'view_dashboard' => true,
            'view_products' => true,
            'search_products' => true,
            'add_product' => true,
            'edit_product' => true,
            'delete_product' => true,
            'manage_users' => true,
            'view_reports' => true,
            'system_settings' => true,
            'view_transactions' => true,
            'add_transactions' => true,
            'delete_transactions' => true,
            'view_user_access' => true
        ]
    ];

    return $permissions[$role] ?? $permissions['user'];
}

// Function to check if user has specific permission
function hasPermission($permission)
{
    $permissions = getRolePermissions();
    return $permissions[$permission] ?? false;
}

// Function to require specific permission
function requirePermission($permission)
{
    requireLogin();
    if (!hasPermission($permission)) {
        setFlashMessage('error', 'Access denied. You do not have permission to perform this action.');
        header("Location: index.php");
        exit;
    }
}

// Function to sanitize output
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to format currency
function formatCurrency($amount)
{
    $amount = $amount ?: 0; // Handle null values
    return '₹' . number_format($amount, 2);
}

// Function to get stock status
function getStockStatus($quantity)
{
    if ($quantity == 0) {
        return ['status' => 'out-of-stock', 'text' => 'Out of Stock'];
    } elseif ($quantity < 5) {
        return ['status' => 'low-stock-status', 'text' => 'Low Stock'];
    } else {
        return ['status' => 'in-stock', 'text' => 'In Stock'];
    }
}

// Function to track user login
function trackUserLogin($user_id, $username, $role)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO user_logins (user_id, username, role, login_time, ip_address) VALUES (?, ?, ?, NOW(), ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt->bind_param("isss", $user_id, $username, $role, $ip);
    $stmt->execute();
}

// Create image thumbnail using GD. Supports JPEG/PNG/GIF/WEBP
function createImageThumbnail($sourcePath, $destPath, $maxWidth = 400, $maxHeight = 400)
{
    if (!file_exists($sourcePath)) {
        return false;
    }
    $info = getimagesize($sourcePath);
    if ($info === false) {
        return false;
    }
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $src = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            if (!function_exists('imagecreatefromwebp')) {
                return false;
            }
            $src = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    if (!$src) {
        return false;
    }

    $origW = imagesx($src);
    $origH = imagesy($src);
    if ($origW <= 0 || $origH <= 0) {
        imagedestroy($src);
        return false;
    }

    $ratio = min($maxWidth / $origW, $maxHeight / $origH, 1);
    $newW = (int)floor($origW * $ratio);
    $newH = (int)floor($origH * $ratio);
    $dst = imagecreatetruecolor($newW, $newH);

    // Preserve transparency for PNG/GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $ok = false;
    switch ($mime) {
        case 'image/jpeg':
            $ok = imagejpeg($dst, $destPath, 82);
            break;
        case 'image/png':
            $ok = imagepng($dst, $destPath, 6);
            break;
        case 'image/gif':
            $ok = imagegif($dst, $destPath);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $ok = imagewebp($dst, $destPath, 82);
            }
            break;
    }

    imagedestroy($dst);
    imagedestroy($src);
    return $ok;
}

// Function to get user access statistics
function getUserAccessStats()
{
    global $conn;

    $stats = [
        'today_logins' => 0,
        'month_logins' => 0,
        'role_logins' => [],
        'recent_logins' => []
    ];

    try {
        // Check if database connection exists
        if (!$conn) {
            // Try to establish connection if not exists
            $conn = new mysqli('localhost', 'root', '', 'inventory');
            if ($conn->connect_error) {
                return $stats;
            }
        }

        // Check if user_logins table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'user_logins'");
        if ($table_check->num_rows == 0) {
            return $stats;
        }

        // Total logins today
        $today_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_logins WHERE DATE(login_time) = CURDATE()");
        if ($today_stmt && $today_stmt->execute()) {
            $result = $today_stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['today_logins'] = $row['count'] ?? 0;
            }
        }

        // Total logins this month
        $month_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_logins WHERE DATE_FORMAT(login_time, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
        if ($month_stmt && $month_stmt->execute()) {
            $result = $month_stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['month_logins'] = $row['count'] ?? 0;
            }
        }

        // Logins by role today
        $role_stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM user_logins WHERE DATE(login_time) = CURDATE() GROUP BY role");
        if ($role_stmt && $role_stmt->execute()) {
            $result = $role_stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $stats['role_logins'][$row['role']] = $row['count'];
                }
            }
        }

        // Recent logins
        $recent_stmt = $conn->prepare("SELECT username, role, login_time FROM user_logins ORDER BY login_time DESC LIMIT 10");
        if ($recent_stmt && $recent_stmt->execute()) {
            $result = $recent_stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $stats['recent_logins'][] = $row;
                }
            }
        }
    } catch (Exception $e) {
        // Log error silently and return default stats
        error_log("Error in getUserAccessStats: " . $e->getMessage());
    }

    return $stats;
}

// CSRF protection helpers
function getCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfTokenInput()
{
    $t = h(getCsrfToken());
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

// Determine current page filename
function currentPage()
{
    return basename($_SERVER['PHP_SELF'] ?? '');
}

// Check if a given filename is the active page
function isActive($filename)
{
    return currentPage() === $filename;
}

// Input validation and sanitization functions
function validateInput($input, $type = 'string', $options = [])
{
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_VALIDATE_EMAIL) ? trim($input) : false;

        case 'int':
            $value = filter_var($input, FILTER_VALIDATE_INT);
            if ($value === false) return false;
            if (isset($options['min']) && $value < $options['min']) return false;
            if (isset($options['max']) && $value > $options['max']) return false;
            return $value;

        case 'float':
            $value = filter_var($input, FILTER_VALIDATE_FLOAT);
            if ($value === false) return false;
            if (isset($options['min']) && $value < $options['min']) return false;
            if (isset($options['max']) && $value > $options['max']) return false;
            return $value;

        case 'url':
            return filter_var(trim($input), FILTER_VALIDATE_URL) ? trim($input) : false;

        case 'phone':
            $phone = preg_replace('/[^0-9]/', '', $input);
            if (strlen($phone) >= 10 && strlen($phone) <= 15) {
                return $phone;
            }
            return false;

        case 'string':
        default:
            $value = trim($input);
            if (isset($options['min_length']) && strlen($value) < $options['min_length']) return false;
            if (isset($options['max_length']) && strlen($value) > $options['max_length']) return false;
            if (isset($options['pattern']) && !preg_match($options['pattern'], $value)) return false;
            return $value;
    }
}

// Function to sanitize output for different contexts
function sanitizeOutput($string, $context = 'html')
{
    switch ($context) {
        case 'html':
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

        case 'attribute':
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

        case 'javascript':
            return json_encode($string);

        case 'url':
            return urlencode($string);

        case 'sql':
            // This should not be used for SQL - use prepared statements instead
            return $string;

        default:
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Function to validate file upload
function validateFileUpload($file, $allowed_types = [], $max_size = 5242880)
{
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed with error code: ' . $file['error'];
        return $errors;
    }

    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds maximum allowed size of ' . formatBytes($max_size);
    }

    if (!empty($allowed_types)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
        }
    }

    return $errors;
}

// Function to format bytes
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

// Function to generate secure random token
function generateSecureToken($length = 32)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    } else {
        // Fallback - less secure but still functional
        return bin2hex(md5(uniqid(mt_rand(), true)));
    }
}
