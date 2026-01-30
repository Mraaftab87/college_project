<?php
require_once __DIR__ . '/security.php';
initSecurity();
include 'db.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid form token. Please try again.');
    header("Location: login.php");
    exit;
  }
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Store username for potential errors
  $_SESSION['login_username'] = $username;
  $error_field = '';

  // Prepared statement to check user
  $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      // Clear stored username on successful login
      unset($_SESSION['login_username']);
      $_SESSION['username'] = $username;
      $_SESSION['role'] = $user['role'];
      $_SESSION['user_id'] = $user['id'];
      
      // Track user login
      trackUserLogin($user['id'], $username, $user['role']);
      
      header("Location: index.php");
      exit;
    } else {
      setFlashMessage('error', 'Invalid password.');
      $error_field = 'password';
    }
  } else {
    setFlashMessage('error', 'Invalid username or password.');
    $error_field = 'username';
  }

  // Store error field info
  if ($error_field) {
    $_SESSION['login_error_field'] = $error_field;
  }

  header("Location: login.php");
  exit;
}

// Get flash message and stored username
$flash = getFlashMessage();
$stored_username = $_SESSION['login_username'] ?? '';
$error_field = $_SESSION['login_error_field'] ?? '';

// Clear error field info after retrieving
unset($_SESSION['login_error_field']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login - Smart Inventory System</title>
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

	<header class="navbar">
		<?php if (isLoggedIn()): ?>
			<a href="index.php" class="logo" style="text-decoration: none; color: white;">
				<i class="fas fa-warehouse fa-2x"></i>
				<span>Smart Inventory System</span>
			</a>
		<?php else: ?>
			<div class="logo">
				<i class="fas fa-warehouse fa-2x"></i>
				<span>Smart Inventory System</span>
			</div>
		<?php endif; ?>
    <nav class="nav-links">
      <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
    </nav>
  </header>

  <main class="dashboard">

    <div class="hero">
      <h1><i class="fas fa-sign-in-alt"></i> Welcome Back</h1>
      <p>Login to access your inventory management dashboard</p>
    </div>

    <div class="form-container">
      <?php if ($flash): ?>
        <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
      <?php endif; ?>

      <form method="POST" class="registration-form">
        <?= csrfTokenInput(); ?>
        <div class="form-section">
          <h3>Login Information</h3>

          <div class="form-group">
            <label>Username: <span class="required">*</span></label>
            <input type="text" name="username" required placeholder="Enter your username" value="<?= $stored_username ?>">
          </div>

          <div class="form-group">
            <label>Password: <span class="required">*</span></label>
            <div class="password-container">
              <input type="password" name="password" id="password" required placeholder="Enter your password" <?php if ($error_field === 'password') echo 'autofocus'; ?>>
              <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <i class="fas fa-eye" id="password-icon"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <input type="submit" value="Login" class="btn btn-primary">
          <a href="register.php" class="btn btn-secondary">Don't have an account? Register</a>
          <a href="forgot_password.php" class="btn btn-secondary">Forgot Password?</a>
        </div>
      </form>
    </div>

  </main>

  <footer class="footer">
    <p>&copy; 2025 Smart Inventory System - Professional Inventory Management</p>
  </footer>

  <script>
    function togglePassword(fieldId) {
      const passwordField = document.getElementById(fieldId);
      const icon = document.getElementById(fieldId + '-icon');

      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    // Clear only the field with error on page load
    document.addEventListener('DOMContentLoaded', function() {
      const errorField = '<?= $error_field ?>';
      if (errorField) {
        const field = document.querySelector(`[name="${errorField}"]`);
        if (field) {
          // Clear the field value
          field.value = '';
          field.focus();

          // Add visual indication of error field
          field.style.borderColor = '#e74c3c';
          field.style.boxShadow = '0 0 0 2px rgba(231, 76, 60, 0.2)';

          // Remove error styling after user starts typing
          field.addEventListener('input', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
          });
        }
      }
    });
  </script>

</body>

</html>