<?php

/**
 * Security initialization and configuration
 * This file handles basic security setup for the application
 */

/**
 * Initialize security settings
 * Sets up session security, CSRF protection, and other security measures
 */
function initSecurity()
{
  // Set secure session parameters BEFORE starting session
  if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);

    // Start session after setting parameters
    session_start();
  }

  // Regenerate session ID periodically for security
  if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
  } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
  }

  // Set security headers
  if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
  }

  // Initialize CSRF token if not exists
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
}
