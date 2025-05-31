<?php
// Include database configuration
require_once 'config.php';

// Check if the vehicle_sub_images, vehicle_sub_details, and vehicle_features tables exist
$response = array(
    'tables_exist' => false
);

// Check vehicle_sub_images
$check_images_table = "SHOW TABLES LIKE 'vehicle_sub_images'";
$images_result = mysqli_query($conn, $check_images_table);
$images_exists = mysqli_num_rows($images_result) > 0;

// Check vehicle_sub_details
$check_details_table = "SHOW TABLES LIKE 'vehicle_sub_details'";
$details_result = mysqli_query($conn, $check_details_table);
$details_exists = mysqli_num_rows($details_result) > 0;

// Check vehicle_features
$check_features_table = "SHOW TABLES LIKE 'vehicle_features'";
$features_result = mysqli_query($conn, $check_features_table);
$features_exists = mysqli_num_rows($features_result) > 0;

// All tables must exist
$response['tables_exist'] = $images_exists && $details_exists && $features_exists;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 