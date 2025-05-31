<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';

// Check if admin is logged in
require_admin_login();

// Check if package ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$package_id = intval($_GET['id']);

// Redirect to manage package details
header("Location: manage_package_details.php?id=$package_id");
exit;
?>