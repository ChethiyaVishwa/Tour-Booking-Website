<?php
// Include database configuration
require_once 'admin/config.php';

// Check if admin is logged in - optional security measure
if (!is_admin_logged_in()) {
    echo "Please login as admin to run this script.";
    exit;
}

// Display any errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<h2>Users Table Fix Script</h2>";

// Check if users table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($check_table) == 0) {
    echo "<p>Users table does not exist yet. It will be created properly when a user registers.</p>";
    exit;
}

// Get current auto_increment value
$auto_increment_query = "SELECT `AUTO_INCREMENT` 
                         FROM INFORMATION_SCHEMA.TABLES 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'users'";
$auto_result = mysqli_query($conn, $auto_increment_query);
$auto_info = mysqli_fetch_assoc($auto_result);

echo "<p>Current AUTO_INCREMENT value: " . ($auto_info['AUTO_INCREMENT'] ?? 'Not set') . "</p>";

// Get highest user_id
$max_id_query = "SELECT MAX(user_id) as max_id FROM users";
$max_result = mysqli_query($conn, $max_id_query);
$max_info = mysqli_fetch_assoc($max_result);
$max_id = $max_info['max_id'] ?? 0;

echo "<p>Highest user_id in table: " . $max_id . "</p>";

// Fix the auto_increment
$new_auto_increment = $max_id + 1;
$alter_query = "ALTER TABLE users AUTO_INCREMENT = " . $new_auto_increment;

if (mysqli_query($conn, $alter_query)) {
    echo "<p style='color:green'>Successfully reset AUTO_INCREMENT to " . $new_auto_increment . "</p>";
} else {
    echo "<p style='color:red'>Error updating AUTO_INCREMENT: " . mysqli_error($conn) . "</p>";
}

// Check ENGINE
$engine_query = "SHOW TABLE STATUS WHERE Name = 'users'";
$engine_result = mysqli_query($conn, $engine_query);
$engine_info = mysqli_fetch_assoc($engine_result);

echo "<p>Current engine: " . ($engine_info['Engine'] ?? 'Unknown') . "</p>";

// Ensure InnoDB engine if not already set
if (($engine_info['Engine'] ?? '') !== 'InnoDB') {
    $engine_query = "ALTER TABLE users ENGINE = InnoDB";
    if (mysqli_query($conn, $engine_query)) {
        echo "<p style='color:green'>Successfully changed engine to InnoDB</p>";
    } else {
        echo "<p style='color:red'>Error changing engine: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p>Table repair completed. <a href='register.php'>Go to registration page</a></p>";
?> 