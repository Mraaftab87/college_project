<?php
session_start();
include 'db.php';
include 'functions.php';

// Steps: 1) enter username -> 2) answer security question -> 3) set new password

// Initialize state
$step = 1;
$username = '';
$question = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'find_user') {
        $username = trim($_POST['username'] ?? '');
        if ($username === '') {
            setFlashMessage('error', 'Please enter your username.');
        } else {
            $stmt = $conn->prepare('SELECT username, security_question FROM users WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 1) {
                $row = $res->fetch_assoc();
                $_SESSION['fp_username'] = $row['username'];
                $_SESSION['fp_question'] = $row['security_question'];
                $step = 2;
            } else {
                setFlashMessage('error', 'User not found.');
            }
        }
    } elseif ($action === 'verify_answer') {
        $username = $_SESSION['fp_username'] ?? '';
        $question = $_SESSION['fp_question'] ?? '';
        $answer = trim($_POST['security_answer'] ?? '');
        if ($username === '' || $question === '') {
            setFlashMessage('error', 'Session expired. Please try again.');
        } elseif ($answer === '') {
            setFlashMessage('error', 'Please enter your security answer.');
            $step = 2;
        } else {
            $stmt = $conn->prepare('SELECT security_answer FROM users WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 1) {
                $row = $res->fetch_assoc();
                if (hash_equals(strtolower(trim($row['security_answer'])), strtolower($answer))) {
                    $_SESSION['fp_verified'] = true;
                    $step = 3;
                } else {
                    setFlashMessage('error', 'Incorrect answer.');
                    $step = 2;
                }
            } else {
                setFlashMessage('error', 'User not found.');
            }
        }
    } elseif ($action === 'reset_password') {
        $username = $_SESSION['fp_username'] ?? '';
        $verified = $_SESSION['fp_verified'] ?? false;
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        if (!$verified || $username === '') {
            setFlashMessage('error', 'Unauthorized or expired session. Start again.');
        } elseif (strlen($new_password) < 8) {
            setFlashMessage('error', 'Password must be at least 8 characters.');
            $step = 3;
        } elseif ($new_password !== $confirm_password) {
            setFlashMessage('error', 'Passwords do not match.');
            $step = 3;
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
            $stmt->bind_param('ss', $hashed, $username);
            if ($stmt->execute()) {
                unset($_SESSION['fp_username'], $_SESSION['fp_question'], $_SESSION['fp_verified']);
                setFlashMessage('success', 'Password changed successfully. You can now login.');
                header('Location: login.php');
                exit;
            } else {
                setFlashMessage('error', 'Could not update password. Try again.');
                $step = 3;
            }
        }
    }
}

// derive current state from session if needed
if ($step === 1) {
    $username = '';
} else {
    $username = $_SESSION['fp_username'] ?? '';
    $question = $_SESSION['fp_question'] ?? '';
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Smart Inventory System</title>
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .subtitle { margin-top: 8px; color: #555; }
  </style>
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
      <h1><i class="fas fa-key"></i> Forgot Password</h1>
      <p class="subtitle">Recover your account using your security question</p>
    </div>

    <div class="form-container">
      <?php if ($flash): ?>
        <div class="<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
      <?php endif; ?>

      <?php if ($step === 1): ?>
        <form method="POST" class="registration-form">
          <div class="form-section">
            <h3>Find Your Account</h3>
            <div class="form-group">
              <label>Username: <span class="required">*</span></label>
              <input type="text" name="username" required placeholder="Enter your username">
            </div>
          </div>
          <div class="form-actions">
            <input type="hidden" name="action" value="find_user">
            <input type="submit" class="btn btn-primary" value="Continue">
            <a href="login.php" class="btn btn-secondary">Back to Login</a>
          </div>
        </form>
      <?php elseif ($step === 2): ?>
        <form method="POST" class="registration-form">
          <div class="form-section">
            <h3>Verify Security Question</h3>
            <div class="form-group">
              <label>Username</label>
              <input type="text" value="<?= h($username) ?>" disabled>
            </div>
            <div class="form-group">
              <label>Security Question</label>
              <input type="text" value="<?= h($question) ?>" disabled>
            </div>
            <div class="form-group">
              <label>Your Answer: <span class="required">*</span></label>
              <input type="text" name="security_answer" required placeholder="Enter your answer">
            </div>
          </div>
          <div class="form-actions">
            <input type="hidden" name="action" value="verify_answer">
            <input type="submit" class="btn btn-primary" value="Verify">
            <a href="forgot_password.php" class="btn btn-secondary">Start Over</a>
          </div>
        </form>
      <?php else: ?>
        <form method="POST" class="registration-form">
          <div class="form-section">
            <h3>Set New Password</h3>
            <div class="form-group">
              <label>New Password: <span class="required">*</span></label>
              <div class="password-container">
                <input type="password" name="new_password" id="new_password" required placeholder="Enter new password (min 8 characters)">
                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                  <i class="fas fa-eye" id="new_password-icon"></i>
                </button>
              </div>
            </div>
            <div class="form-group">
              <label>Confirm Password: <span class="required">*</span></label>
              <div class="password-container">
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                  <i class="fas fa-eye" id="confirm_password-icon"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <input type="hidden" name="action" value="reset_password">
            <input type="submit" class="btn btn-primary" value="Change Password">
            <a href="login.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      <?php endif; ?>
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
  </script>
</body>
</html>


