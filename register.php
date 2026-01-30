<?php
session_start();
// register.php
include 'db.php'; // Database connection
include 'functions.php'; // Shared functions

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST["username"]);
  $password = trim($_POST["password"]);
  $confirm_password = trim($_POST["confirm_password"]);
  $email = trim($_POST["email"]);
  $phone = trim($_POST["phone"]);
  $full_name = trim($_POST["full_name"]);
  $company = trim($_POST["company"]);
  $address = trim($_POST["address"]);
  $role = trim($_POST["role"]);
  $security_question = trim($_POST["security_question"] ?? '');
  $security_answer = trim($_POST["security_answer"] ?? '');

  // Store form data in session for potential errors
  $_SESSION['form_data'] = [
    'username' => $username,
    'email' => $email,
    'phone' => $phone,
    'full_name' => $full_name,
    'company' => $company,
    'address' => $address,
    'role' => $role,
    'security_question' => $security_question,
    'security_answer' => $security_answer
  ];

  $error_field = ''; // Track which field has error

  // Basic validation
  if (empty($username)) {
    setFlashMessage('error', 'Username is required.');
    $error_field = 'username';
  } elseif (preg_match('/\s/', $username)) {
    setFlashMessage('error', 'Username cannot contain spaces.');
    $error_field = 'username';
  } elseif (strlen($password) < 8) {
    setFlashMessage('error', 'Password must be at least 8 characters.');
    $error_field = 'password';
  } elseif ($password !== $confirm_password) {
    setFlashMessage('error', 'Passwords do not match.');
    $error_field = 'confirm_password';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlashMessage('error', 'Please enter a valid email address.');
    $error_field = 'email';
  } elseif (empty($full_name)) {
    setFlashMessage('error', 'Full name is required.');
    $error_field = 'full_name';
  } elseif (empty($phone)) {
    setFlashMessage('error', 'Phone number is required.');
    $error_field = 'phone';
  } elseif (!preg_match('/^\d{10}$/', $phone)) {
    setFlashMessage('error', 'Mobile number must be exactly 10 digits.');
    $error_field = 'phone';
  } elseif (empty($security_question)) {
    setFlashMessage('error', 'Security question is required.');
    $error_field = 'security_question';
  } elseif (empty($security_answer)) {
    setFlashMessage('error', 'Security answer is required.');
    $error_field = 'security_answer';
  } else {
    // Check if user already exists
    $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if (!$check) {
      setFlashMessage('error', 'Database error: ' . $conn->error);
      $error_field = 'username';
    } else {
      $check->bind_param("s", $username);
      $check->execute();
      $result = $check->get_result();

      if ($result->num_rows > 0) {
        setFlashMessage('error', 'User already registered. Please login.');
        $error_field = 'username';
      } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, full_name, company, address, role, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
          setFlashMessage('error', 'Database error: ' . $conn->error);
        } else {
          $stmt->bind_param("ssssssssss", $username, $hashed_password, $email, $phone, $full_name, $company, $address, $role, $security_question, $security_answer);
          if ($stmt->execute()) {
            // Clear form data on success
            unset($_SESSION['form_data']);
            setFlashMessage('success', 'Registration successful! Welcome to Smart Inventory System. <a href="login.php">Login here</a>');
          } else {
            setFlashMessage('error', 'Registration failed. Please try again later.');
          }
        }
      }
    }
  }

  // Store error field info
  if ($error_field) {
    $_SESSION['error_field'] = $error_field;
  }

  // Redirect to prevent form resubmission
  header("Location: register.php");
  exit;
}

// Get flash message and form data
$flash = getFlashMessage();
$form_data = $_SESSION['form_data'] ?? [];
$error_field = $_SESSION['error_field'] ?? '';

// Clear error field info after retrieving
unset($_SESSION['error_field']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Register - Smart Inventory System</title>
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
      <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
    </nav>
  </header>

  <main class="dashboard">

    <div class="hero">
      <h1><i class="fas fa-user-plus"></i> Join Our Platform</h1>
      <p>Create your account and start managing inventory like a pro</p>
    </div>

    <div class="form-container">
      <?php if ($flash): ?>
        <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
      <?php endif; ?>

      <form method="POST" class="registration-form">
        <div class="form-section">
          <h3>Account Information</h3>

          <div class="form-group">
            <label>Username: <span class="required">*</span></label>
            <input type="text" name="username" required placeholder="Enter username" value="<?= $form_data['username'] ?? '' ?>" pattern="\S+" title="No spaces allowed in username" autocomplete="username" autocapitalize="none" spellcheck="false">
          </div>

          <div class="form-group">
            <label>Password: <span class="required">*</span></label>
            <div class="password-container">
              <input type="password" name="password" id="password" required placeholder="Enter password (min 8 characters)" value="<?= $form_data['password'] ?? '' ?>" pattern=".{8,}" title="Use any characters you like (letters, numbers, and special characters). Minimum 8 characters." autocomplete="new-password">
              <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <i class="fas fa-eye" id="password-icon"></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Confirm Password: <span class="required">*</span></label>
            <div class="password-container">
              <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your password" value="<?= $form_data['confirm_password'] ?? '' ?>" pattern=".{8,}" title="Re-enter your password (min 8 characters)" autocomplete="new-password">
              <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                <i class="fas fa-eye" id="confirm_password-icon"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Personal Information</h3>

          <div class="form-group">
            <label>Full Name: <span class="required">*</span></label>
            <input type="text" name="full_name" required placeholder="Enter your full name" value="<?= $form_data['full_name'] ?? '' ?>">
          </div>

          <div class="form-group">
            <label>Email Address: <span class="required">*</span></label>
            <input type="email" name="email" required placeholder="Enter your email address" value="<?= $form_data['email'] ?? '' ?>">
          </div>

          <div class="form-group">
            <label>Phone Number: <span class="required">*</span></label>
            <input type="tel" name="phone" required placeholder="Enter 10-digit mobile number" value="<?= $form_data['phone'] ?? '' ?>" pattern="[0-9]{10}" minlength="10" maxlength="10" inputmode="numeric" title="Please enter a 10-digit mobile number (digits only)">
          </div>

          <div class="form-group">
            <label>Company/Organization:</label>
            <input type="text" name="company" placeholder="Enter company name (optional)" value="<?= $form_data['company'] ?? '' ?>">
          </div>

          <div class="form-group">
            <label>Address:</label>
            <textarea name="address" placeholder="Enter your address (optional)" rows="3"><?= $form_data['address'] ?? '' ?></textarea>
          </div>

          <div class="form-group">
            <label>Role: <span class="required">*</span></label>
            <select name="role" required>
              <option value="">Select your role</option>
              <option value="user" <?= ($form_data['role'] ?? '') == 'user' ? 'selected' : '' ?>>User</option>
              <option value="manager" <?= ($form_data['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
              <option value="admin" <?= ($form_data['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
          </div>
        </div>

        <div class="form-section">
          <h3>Account Recovery</h3>

          <div class="form-group">
            <label>Security Question: <span class="required">*</span></label>
            <select name="security_question" required>
              <option value="">Select a question</option>
              <option value="What is your favorite color?" <?= ($form_data['security_question'] ?? '') == 'What is your favorite color?' ? 'selected' : '' ?>>What is your favorite color?</option>
              <option value="What is your mother's maiden name?" <?= ($form_data['security_question'] ?? '') == "What is your mother's maiden name?" ? 'selected' : '' ?>>What is your mother's maiden name?</option>
              <option value="What city were you born in?" <?= ($form_data['security_question'] ?? '') == 'What city were you born in?' ? 'selected' : '' ?>>What city were you born in?</option>
              <option value="What was your first pet's name?" <?= ($form_data['security_question'] ?? '') == "What was your first pet's name?" ? 'selected' : '' ?>>What was your first pet's name?</option>
            </select>
          </div>

          <div class="form-group">
            <label>Security Answer: <span class="required">*</span></label>
            <input type="text" name="security_answer" required placeholder="Enter your answer" value="<?= $form_data['security_answer'] ?? '' ?>">
          </div>
        </div>

        <div class="form-actions">
          <input type="submit" value="Create Account" class="btn btn-primary">
          <a href="login.php" class="btn btn-secondary">Already have an account? Login</a>
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
      const passwordIcon = document.getElementById(fieldId + '-icon');
      
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        passwordIcon.classList.remove('fa-eye');
        passwordIcon.classList.add('fa-eye-slash');
      } else {
        passwordField.type = 'password';
        passwordIcon.classList.remove('fa-eye-slash');
        passwordIcon.classList.add('fa-eye');
      }
    }

    // Clear only the field with error on page load
    document.addEventListener('DOMContentLoaded', function() {
      const errorField = '<?= $error_field ?>';
      if (errorField) {
        const field = document.querySelector(`[name="${errorField}"]`);
        if (field) {
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