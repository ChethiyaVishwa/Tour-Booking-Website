<?php
// Database Configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'adventure_travel');

// Create connection
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to sanitize user inputs
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to handle file uploads
function upload_file($file, $destination_folder = '../images/') {
    $target_dir = $destination_folder;
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if file is valid image
    $valid_extensions = array("jpg", "jpeg", "png", "gif", "webp");
    if (!in_array($file_extension, $valid_extensions)) {
        return array('success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.');
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5000000) {
        return array('success' => false, 'message' => 'File size must be less than 5MB.');
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return array('success' => true, 'filename' => $new_filename);
    } else {
        return array('success' => false, 'message' => 'Error uploading file.');
    }
}

// Session management - Check if session already exists before starting
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in as admin
function is_admin_logged_in() {
    if (isset($_SESSION['admin_id'])) {
        return true;
    }
    return false;
}

// Redirect if not logged in as admin
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header("Location: admin_login.php");
        exit;
    }
}
?> 