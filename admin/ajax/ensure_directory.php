<?php
// Include database configuration
require_once '../config.php';
require_admin_login();

// Set content type to JSON
header('Content-Type: application/json');

// Get the path parameter
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Sanitize the path to prevent directory traversal attacks
$path = str_replace('../', '', $path);
$path = str_replace('..\\', '', $path);

// Add parent path if it doesn't start with a slash
if (!preg_match('/^\/|^\w:/', $path)) {
    $path = '../' . $path;
}

if (empty($path)) {
    echo json_encode(array('success' => false, 'message' => 'Path parameter is required'));
    exit;
}

// Check if directory exists and create it if it doesn't
if (!file_exists($path)) {
    if (mkdir($path, 0777, true)) {
        echo json_encode(array('success' => true, 'message' => 'Directory created successfully'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to create directory'));
    }
} else {
    echo json_encode(array('success' => true, 'message' => 'Directory already exists'));
}
?> 