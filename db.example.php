<?php
/**
 * Database Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file and rename it to 'db.php'
 * 2. Update the database credentials below
 * 3. Never commit db.php to version control (it's in .gitignore)
 */

// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Database Configuration
$servername = "localhost";
$username = "root";           // Change this to your MySQL username
$password = "";               // Change this to your MySQL password
$dbname = "inventory";        // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}

// Set charset to utf8
$conn->set_charset("utf8");
