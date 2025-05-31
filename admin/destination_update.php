<?php
// Include database configuration
require_once 'config.php';
require_admin_login();

// Add destination_names column to package_details table if it doesn't exist
function add_destination_names_column() {
    global $conn;
    
    // Check if column already exists
    $check_column = "SHOW COLUMNS FROM package_details LIKE 'destination_names'";
    $result = mysqli_query($conn, $check_column);
    
    if (mysqli_num_rows($result) == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE package_details ADD COLUMN destination_names TEXT AFTER description";
        
        if (mysqli_query($conn, $sql)) {
            return true;
        } else {
            return false;
        }
    }
    
    return true; // Column already exists
}

// Execute the function and display result
$success = add_destination_names_column();

if ($success) {
    header("Location: admin.php?success=column_added");
    exit;
} else {
    header("Location: admin.php?error=column_failed");
    exit;
}
?>