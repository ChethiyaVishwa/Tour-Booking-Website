<?php
// Include database configuration and helper functions
require_once 'config.php';
require_admin_login();

// Enable debugging mode to see errors
$debug_mode = true;
if ($debug_mode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Define page title
$page_title = "Manage Destinations";

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new destination
    if (isset($_POST['add_destination'])) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        // Handle main image upload
        $upload_result = upload_file($_FILES['main_image'], '../destinations/');
        
        if ($upload_result['success']) {
            $main_image = $upload_result['filename'];
            
            // Insert main destination data
            $insert_query = "INSERT INTO destinations (name, description, image) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "sss", $name, $description, $main_image);
            
            if (mysqli_stmt_execute($stmt)) {
                $destination_id = mysqli_insert_id($conn);
                
                // Handle sub-destinations if provided
                if (!empty($_POST['sub_name']) && is_array($_POST['sub_name'])) {
                    // Process each sub-destination
                    foreach ($_POST['sub_name'] as $key => $sub_name) {
                        if (empty($sub_name)) continue;
                        
                        $sub_name = sanitize_input($sub_name);
                        $sub_description = sanitize_input($_POST['sub_description'][$key]);
                        
                        // Create sub-destination directory if it doesn't exist
                        $sub_dir = '../destinations/sub/';
                        if (!file_exists($sub_dir)) {
                            mkdir($sub_dir, 0777, true);
                        }
                        
                        // Handle sub-image upload
                        if (isset($_FILES['sub_image']['name'][$key]) && !empty($_FILES['sub_image']['name'][$key])) {
                            // Create file array for the specific sub-image
                            $sub_file = [
                                'name' => $_FILES['sub_image']['name'][$key],
                                'type' => $_FILES['sub_image']['type'][$key],
                                'tmp_name' => $_FILES['sub_image']['tmp_name'][$key],
                                'error' => $_FILES['sub_image']['error'][$key],
                                'size' => $_FILES['sub_image']['size'][$key]
                            ];
                            
                            // Upload the sub-image
                            $sub_upload_result = upload_file($sub_file, '../destinations/sub/');
                            
                            if ($sub_upload_result['success']) {
                                $sub_image = $sub_upload_result['filename'];
                                
                                // Insert sub-destination data
                                $sub_insert_query = "INSERT INTO destination_sub_images (destination_id, name, description, image) 
                                                    VALUES (?, ?, ?, ?)";
                                $sub_stmt = mysqli_prepare($conn, $sub_insert_query);
                                
                                if ($sub_stmt) {
                                    mysqli_stmt_bind_param($sub_stmt, "isss", $destination_id, $sub_name, $sub_description, $sub_image);
                                    $sub_result = mysqli_stmt_execute($sub_stmt);
                                    
                                    if (!$sub_result) {
                                        // Log error for debugging
                                        error_log("Failed to insert sub-destination: " . mysqli_error($conn));
                                    }
                                } else {
                                    // Log error for debugging
                                    error_log("Failed to prepare sub-destination query: " . mysqli_error($conn));
                                }
                            } else {
                                // Log error for debugging
                                error_log("Sub-image upload failed: " . $sub_upload_result['message']);
                            }
                        } else {
                            // Even without image, still add the sub-destination with a placeholder image
                            $default_image = 'placeholder.jpg';
                            
                            // Insert sub-destination data with default image
                            $sub_insert_query = "INSERT INTO destination_sub_images (destination_id, name, description, image) 
                                                VALUES (?, ?, ?, ?)";
                            $sub_stmt = mysqli_prepare($conn, $sub_insert_query);
                            
                            if ($sub_stmt) {
                                mysqli_stmt_bind_param($sub_stmt, "isss", $destination_id, $sub_name, $sub_description, $default_image);
                                $sub_result = mysqli_stmt_execute($sub_stmt);
                                
                                if (!$sub_result) {
                                    // Log error for debugging
                                    error_log("Failed to insert sub-destination with placeholder: " . mysqli_error($conn));
                                }
                            }
                        }
                    }
                }
                
                header("Location: manage_destinations.php?success=destination_added");
                exit;
            } else {
                $error_message = "Error adding destination: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Error uploading image: " . $upload_result['message'];
        }
    }
    
    // Edit main destination
    if (isset($_POST['edit_destination'])) {
        $destination_id = intval($_POST['destination_id']);
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        // Check if a new image was uploaded
        $update_image = false;
        $main_image = '';
        
        if (isset($_FILES['main_image']) && !empty($_FILES['main_image']['name'])) {
            $upload_result = upload_file($_FILES['main_image'], '../destinations/');
            
            if ($upload_result['success']) {
                $update_image = true;
                $main_image = $upload_result['filename'];
                
                // Get the old image to delete it later
                $old_image_query = "SELECT image FROM destinations WHERE destination_id = ?";
                $old_image_stmt = mysqli_prepare($conn, $old_image_query);
                mysqli_stmt_bind_param($old_image_stmt, "i", $destination_id);
                mysqli_stmt_execute($old_image_stmt);
                $old_image_result = mysqli_stmt_get_result($old_image_stmt);
                $old_image = mysqli_fetch_assoc($old_image_result)['image'];
            } else {
                $error_message = "Error uploading new image: " . $upload_result['message'];
                header("Location: manage_destinations.php?error=" . urlencode($error_message));
                exit;
            }
        }
        
        // Update main destination data
        if ($update_image) {
            $update_query = "UPDATE destinations SET name = ?, description = ?, image = ? WHERE destination_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $main_image, $destination_id);
        } else {
            $update_query = "UPDATE destinations SET name = ?, description = ? WHERE destination_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $destination_id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            // Delete old image if a new one was uploaded
            if ($update_image && !empty($old_image)) {
                $old_image_path = '../destinations/' . $old_image;
                if (file_exists($old_image_path) && $old_image != 'placeholder.jpg') {
                    @unlink($old_image_path);
                }
            }
            
            header("Location: manage_destinations.php?success=destination_updated");
            exit;
        } else {
            $error_message = "Error updating destination: " . mysqli_error($conn);
            header("Location: manage_destinations.php?error=" . urlencode($error_message));
            exit;
        }
    }
    
    // Add new sub-destination to existing destination
    if (isset($_POST['add_sub_destination'])) {
        $destination_id = intval($_POST['destination_id']);
        $sub_name = sanitize_input($_POST['sub_name']);
        $sub_description = sanitize_input($_POST['sub_description']);
        
        // Create sub-destination directory if it doesn't exist
        $sub_dir = '../destinations/sub/';
        if (!file_exists($sub_dir)) {
            mkdir($sub_dir, 0777, true);
        }
        
        // Handle sub-image upload
        $sub_image = 'placeholder.jpg'; // Default value
        
        if (isset($_FILES['sub_image']) && !empty($_FILES['sub_image']['name'])) {
            $sub_upload_result = upload_file($_FILES['sub_image'], '../destinations/sub/');
            
            if ($sub_upload_result['success']) {
                $sub_image = $sub_upload_result['filename'];
            } else {
                $error_message = "Error uploading sub-image: " . $sub_upload_result['message'];
                header("Location: manage_destinations.php?error=" . urlencode($error_message));
                exit;
            }
        }
        
        // Insert sub-destination data
        $sub_insert_query = "INSERT INTO destination_sub_images (destination_id, name, description, image) 
                            VALUES (?, ?, ?, ?)";
        $sub_stmt = mysqli_prepare($conn, $sub_insert_query);
        
        if ($sub_stmt) {
            mysqli_stmt_bind_param($sub_stmt, "isss", $destination_id, $sub_name, $sub_description, $sub_image);
            $sub_result = mysqli_stmt_execute($sub_stmt);
            
            if ($sub_result) {
                header("Location: manage_destinations.php?success=sub_destination_added");
                exit;
            } else {
                $error_message = "Failed to insert sub-destination: " . mysqli_error($conn);
                header("Location: manage_destinations.php?error=" . urlencode($error_message));
                exit;
            }
        } else {
            $error_message = "Failed to prepare sub-destination query: " . mysqli_error($conn);
            header("Location: manage_destinations.php?error=" . urlencode($error_message));
            exit;
        }
    }
    
    // Edit sub-destination
    if (isset($_POST['edit_sub_destination'])) {
        $sub_image_id = intval($_POST['sub_image_id']);
        $sub_name = sanitize_input($_POST['sub_name']);
        $sub_description = sanitize_input($_POST['sub_description']);
        
        // Check if a new image was uploaded
        $update_sub_image = false;
        $sub_image = '';
        
        if (isset($_FILES['sub_image']) && !empty($_FILES['sub_image']['name'])) {
            $sub_upload_result = upload_file($_FILES['sub_image'], '../destinations/sub/');
            
            if ($sub_upload_result['success']) {
                $update_sub_image = true;
                $sub_image = $sub_upload_result['filename'];
                
                // Get the old image to delete it later
                $old_sub_image_query = "SELECT image FROM destination_sub_images WHERE sub_image_id = ?";
                $old_sub_image_stmt = mysqli_prepare($conn, $old_sub_image_query);
                mysqli_stmt_bind_param($old_sub_image_stmt, "i", $sub_image_id);
                mysqli_stmt_execute($old_sub_image_stmt);
                $old_sub_image_result = mysqli_stmt_get_result($old_sub_image_stmt);
                $old_sub_image = mysqli_fetch_assoc($old_sub_image_result)['image'];
            } else {
                $error_message = "Error uploading new sub-image: " . $sub_upload_result['message'];
                header("Location: manage_destinations.php?error=" . urlencode($error_message));
                exit;
            }
        }
        
        // Update sub-destination data
        if ($update_sub_image) {
            $update_sub_query = "UPDATE destination_sub_images SET name = ?, description = ?, image = ? WHERE sub_image_id = ?";
            $sub_stmt = mysqli_prepare($conn, $update_sub_query);
            mysqli_stmt_bind_param($sub_stmt, "sssi", $sub_name, $sub_description, $sub_image, $sub_image_id);
        } else {
            $update_sub_query = "UPDATE destination_sub_images SET name = ?, description = ? WHERE sub_image_id = ?";
            $sub_stmt = mysqli_prepare($conn, $update_sub_query);
            mysqli_stmt_bind_param($sub_stmt, "ssi", $sub_name, $sub_description, $sub_image_id);
        }
        
        if (mysqli_stmt_execute($sub_stmt)) {
            // Delete old image if a new one was uploaded
            if ($update_sub_image && !empty($old_sub_image)) {
                $old_sub_image_path = '../destinations/sub/' . $old_sub_image;
                if (file_exists($old_sub_image_path) && $old_sub_image != 'placeholder.jpg') {
                    @unlink($old_sub_image_path);
                }
            }
            
            header("Location: manage_destinations.php?success=sub_destination_updated");
            exit;
        } else {
            $error_message = "Error updating sub-destination: " . mysqli_error($conn);
            header("Location: manage_destinations.php?error=" . urlencode($error_message));
            exit;
        }
    }
    
    // Delete sub-destination
    if (isset($_POST['delete_sub_destination'])) {
        $sub_image_id = intval($_POST['sub_image_id']);
        
        // Get the image filename before deleting the record
        $get_image_query = "SELECT image FROM destination_sub_images WHERE sub_image_id = ?";
        $get_image_stmt = mysqli_prepare($conn, $get_image_query);
        mysqli_stmt_bind_param($get_image_stmt, "i", $sub_image_id);
        mysqli_stmt_execute($get_image_stmt);
        $image_result = mysqli_stmt_get_result($get_image_stmt);
        $image_file = mysqli_fetch_assoc($image_result)['image'];
        
        // Delete the sub-destination record
        $delete_sub_query = "DELETE FROM destination_sub_images WHERE sub_image_id = ?";
        $delete_sub_stmt = mysqli_prepare($conn, $delete_sub_query);
        mysqli_stmt_bind_param($delete_sub_stmt, "i", $sub_image_id);
        
        if (mysqli_stmt_execute($delete_sub_stmt)) {
            // Delete the image file if it exists and isn't the placeholder
            if (!empty($image_file) && $image_file != 'placeholder.jpg') {
                $image_path = '../destinations/sub/' . $image_file;
                if (file_exists($image_path)) {
                    @unlink($image_path);
                }
            }
            
            header("Location: manage_destinations.php?success=sub_destination_deleted");
            exit;
        } else {
            $error_message = "Error deleting sub-destination: " . mysqli_error($conn);
            header("Location: manage_destinations.php?error=" . urlencode($error_message));
            exit;
        }
    }
    
    // Delete destination
    if (isset($_POST['delete_destination'])) {
        $destination_id = intval($_POST['destination_id']);
        
        // First delete sub-images
        $delete_sub_query = "DELETE FROM destination_sub_images WHERE destination_id = ?";
        $delete_sub_stmt = mysqli_prepare($conn, $delete_sub_query);
        mysqli_stmt_bind_param($delete_sub_stmt, "i", $destination_id);
        mysqli_stmt_execute($delete_sub_stmt);
        
        // Then delete the main destination
        $delete_query = "DELETE FROM destinations WHERE destination_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $destination_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_destinations.php?success=destination_deleted");
            exit;
        } else {
            $error_message = "Error deleting destination: " . mysqli_error($conn);
        }
    }
}

// Check if destination_sub_images table exists and create it if it doesn't
$check_table_query = "SHOW TABLES LIKE 'destination_sub_images'";
$table_result = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($table_result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE IF NOT EXISTS `destination_sub_images` (
        `sub_image_id` INT(11) NOT NULL AUTO_INCREMENT,
        `destination_id` INT(11) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT NOT NULL,
        `image` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`sub_image_id`),
        FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`destination_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $table_created = mysqli_query($conn, $create_table_query);
    
    if (!$table_created) {
        error_log("Failed to create destination_sub_images table: " . mysqli_error($conn));
        
        // Try alternative query without foreign key constraint in case that's causing issues
        $create_table_alternative = "CREATE TABLE IF NOT EXISTS `destination_sub_images` (
            `sub_image_id` INT(11) NOT NULL AUTO_INCREMENT,
            `destination_id` INT(11) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT NOT NULL,
            `image` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`sub_image_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        mysqli_query($conn, $create_table_alternative);
    }
    
    // Add placeholder image to destinations/sub directory
    $placeholder_path = '../destinations/sub/placeholder.jpg';
    if (!file_exists($placeholder_path)) {
        // Simple copy of a default image from the main destinations directory if it exists
        if (file_exists('../destinations/destination-1.jpg')) {
            copy('../destinations/destination-1.jpg', $placeholder_path);
        }
    }
}

// Fetch all destinations
$destinations_query = "SELECT * FROM destinations ORDER BY name";
$destinations_result = mysqli_query($conn, $destinations_query);
$destinations = [];
if ($destinations_result) {
    while ($destination = mysqli_fetch_assoc($destinations_result)) {
        $destinations[] = $destination;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Adventure Travel.lk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        :root {
            --primary-color: rgb(23, 108, 101);
            --secondary-color: rgb(101, 255, 193);
            --dark-color: #333;
            --light-color: #f4f4f4;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }

        body {
            background-color: rgb(68, 202, 148);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color:rgb(4, 39, 37);
            color: #fff;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 60px;
            width: 130px;
            margin-right: 10px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            padding: 5px;
            background-color: rgba(255, 255, 255, 0.74);
            transition: all 0.3s ease;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a.active, .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--secondary-color);
            border-left: 4px solid var(--secondary-color);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .destination-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .destination-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .destination-card {
            background-color:rgb(123, 255, 222);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .destination-image {
            height: 180px;
            overflow: hidden;
        }

        .destination-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .destination-details {
            padding: 15px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .edit-btn, .view-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: #f0ad4e;
            color: #fff;
        }

        .view-btn {
            background-color: var(--primary-color);
            color: #fff;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: #fff;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 70%;
            max-width: 800px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .add-btn, .form-submit {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Table Styling */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th, .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
        }

        .table img {
            max-height: 80px;
            border-radius: 4px;
        }

        /* Responsive design */
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .destination-cards {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo-4.png" alt="Adventure Travel.lk Logo">
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="admin.php"><span><i class="fas fa-home"></i> Dashboard</span></a></li>
                    <li><a href="manage_destinations.php" class="active"><span><i class="fas fa-map-marker-alt"></i> Manage Destinations</span></a></li>
                    <li><a href="manage_hotels.php"><span><i class="fas fa-hotel"></i> Manage Hotels</span></a></li>
                    <li><a href="manage_policies.php"><span><i class="fas fa-file-alt"></i> Manage Policies</span></a></li>
                    <li><a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Manage Admins</span></a></li>
                    <li><a href="user_messages.php" style="color: #fff; background-color:rgb(0, 0, 0);"><span><i class="fas fa-comment-dots"></i> User Messages</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo $page_title; ?></h1>
                <a href="admin.php" class="add-btn">Back to Dashboard</a>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php 
                        if ($_GET['success'] == 'destination_added') echo "Destination added successfully!";
                        if ($_GET['success'] == 'destination_deleted') echo "Destination deleted successfully!";
                        if ($_GET['success'] == 'destination_updated') echo "Destination updated successfully!";
                        if ($_GET['success'] == 'sub_destination_added') echo "Sub-destination added successfully!";
                        if ($_GET['success'] == 'sub_destination_updated') echo "Sub-destination updated successfully!";
                        if ($_GET['success'] == 'sub_destination_deleted') echo "Sub-destination deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -10px;">
                <div class="col-md-5" style="width: 40%; padding: 0 10px;">
                    <div class="destination-section">
                        <div class="section-header">
                            <h2><i class="fas fa-map-marked-alt"></i> Add New Destination</h2>
                        </div>
                        <form action="manage_destinations.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="name">Destination Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="main_image">Main Image</label>
                                <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Destination Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            
                            <hr style="margin: 15px 0 10px 0; border-color: #eee;">
                            <h5 style="font-size: 0.95rem; margin-bottom: 5px;">Sub Destinations (Optional)</h5>
                            <p style="color: #6c757d; font-size: 0.8rem; margin-bottom: 8px;">Add sub-destinations associated with this main destination</p>
                            
                            <div id="sub-destinations-container">
                                <div class="sub-destination form-group" style="border: 1px solid #eee; padding: 10px; border-radius: 5px; margin-bottom: 10px; background-color: #f9f9f9;">
                                    <div class="form-group" style="margin-bottom: 8px;">
                                        <label style="font-size: 0.85rem; margin-bottom: 3px;">Sub Destination Name</label>
                                        <input type="text" class="form-control" name="sub_name[]" style="padding: 5px 8px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 8px;">
                                        <label style="font-size: 0.85rem; margin-bottom: 3px;">Sub Image</label>
                                        <input type="file" class="form-control" name="sub_image[]" accept="image/*" style="padding: 5px; font-size: 0.85rem;">
                                        <small style="color: #6c757d; display: block; margin-top: 2px; font-size: 0.75rem;">Optional - a placeholder will be used if no image</small>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 8px;">
                                        <label style="font-size: 0.85rem; margin-bottom: 3px;">Sub Description</label>
                                        <textarea class="form-control" name="sub_description[]" rows="2" style="padding: 5px 8px; font-size: 0.9rem;"></textarea>
                                    </div>
                                    <div class="form-group" style="display: flex; align-items: center; margin-bottom: 0;">
                                        <input type="checkbox" class="sub-enabled" id="sub-enabled-1" checked style="margin-right: 5px;">
                                        <label for="sub-enabled-1" style="font-size: 0.85rem; margin-bottom: 0;">Enable this sub-destination</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 10px; margin-bottom: 10px;">
                                <button type="button" id="add-sub-destination" class="add-btn" style="background-color: #6c757d; padding: 4px 8px; font-size: 0.85rem;">
                                    <i class="fas fa-plus"></i> Add Another Sub Destination
                                </button>
                            </div>
                            
                            <div class="form-group" style="margin-top: 20px;">
                                <button type="submit" name="add_destination" class="form-submit">Add Destination</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-7" style="width: 60%; padding: 0 10px;">
                    <div class="destination-section">
                        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #eee;">
                            <h2><i class="fas fa-table"></i> Destinations List</h2>
                            
                            <!-- Search Bar -->
                            <div style="display: flex; width: 250px; position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-size: 0.8rem;"></i>
                                <input type="text" id="destinationSearch" style="padding: 5px 5px 5px 25px; border: 1px solid #ddd; border-radius: 4px; width: 100%; font-size: 0.85rem;" placeholder="Search destinations...">
                                <button style="background: none; border: none; color: #666; cursor: pointer; position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; display: none;" id="clearSearchBtn" onclick="clearSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="15%">Image</th>
                                        <th width="20%">Name</th>
                                        <th width="35%">Description</th>
                                        <th width="10%">Sub Images</th>
                                        <th width="15%" style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($destinations as $destination): ?>
                                        <tr>
                                            <td><?php echo $destination['destination_id']; ?></td>
                                            <td>
                                                <img src="../destinations/<?php echo htmlspecialchars($destination['image']); ?>" 
                                                    alt="<?php echo htmlspecialchars($destination['name']); ?>" style="max-width: 100px;">
                                            </td>
                                            <td><?php echo htmlspecialchars($destination['name']); ?></td>
                                            <td><?php echo substr(htmlspecialchars($destination['description']), 0, 80) . '...'; ?></td>
                                            <td style="text-align: center;">
                                                <?php 
                                                    $sub_count_query = "SELECT COUNT(*) as count FROM destination_sub_images WHERE destination_id = " . $destination['destination_id'];
                                                    $sub_count_result = mysqli_query($conn, $sub_count_query);
                                                    $sub_count = mysqli_fetch_assoc($sub_count_result)['count'];
                                                    echo '<span class="badge">' . $sub_count . '</span>';
                                                ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; justify-content: center; gap: 5px;">
                                                    <button class="view-btn view-sub-btn" title="View Sub-Destinations" data-id="<?php echo $destination['destination_id']; ?>">
                                                        <i class="fas fa-images"></i>
                                                    </button>
                                                    <button class="edit-btn" title="Edit Destination"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal"
                                                            data-id="<?php echo $destination['destination_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($destination['name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($destination['description']); ?>"
                                                            data-image="<?php echo htmlspecialchars($destination['image']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="delete-btn" title="Delete Destination"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal"
                                                            data-id="<?php echo $destination['destination_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($destination['name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteModal')" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <div style="margin-bottom: 20px;">
                Are you sure you want to delete <span id="destination-name" style="font-weight: bold;"></span>? This action cannot be undone.
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <form action="manage_destinations.php" method="post">
                    <input type="hidden" name="destination_id" id="delete-destination-id">
                    <button type="button" class="add-btn" style="background-color: #6c757d; margin-right: 10px;" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_destination" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sub Images Modal -->
    <div class="modal" id="subImagesModal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h2>Sub Destinations for <span id="sub-destination-name"></span></h2>
                <span class="close-btn" onclick="closeModal('subImagesModal')" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <div id="sub-images-container">
                <!-- Sub images will be loaded here via AJAX -->
                <div style="text-align: center; padding: 20px;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary-color); border-radius: 50%; animation: spin 2s linear infinite;"></div>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <button class="add-btn" id="addSubDestinationBtn" style="display: none;">
                    <i class="fas fa-plus"></i> Add Sub Destination
                </button>
                <button class="add-btn" style="background-color: #6c757d;" onclick="closeModal('subImagesModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Main Destination Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h2>Edit Destination</h2>
                <span class="close-btn" onclick="closeModal('editModal')" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <form action="manage_destinations.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="destination_id" id="edit-destination-id">
                
                <div class="form-group">
                    <label for="edit-name">Destination Name</label>
                    <input type="text" class="form-control" id="edit-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-description">Description</label>
                    <textarea class="form-control" id="edit-description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Current Image</label>
                    <div>
                        <img id="current-image" src="" alt="Current Image" style="max-height: 150px; margin: 10px 0;">
                    </div>
                    <div style="margin-bottom: 10px; color: #6c757d; font-size: 0.875rem;">Leave empty to keep current image</div>
                    <input type="file" class="form-control" id="edit-main-image" name="main_image" accept="image/*">
                </div>
                
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="add-btn" style="background-color: #6c757d; margin-right: 10px;" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_destination" class="form-submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Sub-Destination Modal -->
    <div class="modal" id="addSubModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h2>Add Sub-Destination to <span id="parent-destination-name"></span></h2>
                <span class="close-btn" onclick="closeModal('addSubModal')" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <form action="manage_destinations.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="destination_id" id="parent-destination-id">
                
                <div class="form-group">
                    <label for="sub-name">Sub-Destination Name</label>
                    <input type="text" class="form-control" id="sub-name" name="sub_name" required>
                </div>
                
                <div class="form-group">
                    <label for="sub-description">Description</label>
                    <textarea class="form-control" id="sub-description" name="sub_description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="sub-image">Image</label>
                    <input type="file" class="form-control" id="sub-image" name="sub_image" accept="image/*">
                    <div style="color: #6c757d; font-size: 0.875rem; margin-top: 5px;">Optional - a placeholder will be used if no image is provided</div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="add-btn" style="background-color: #6c757d; margin-right: 10px;" onclick="closeModal('addSubModal')">Cancel</button>
                    <button type="submit" name="add_sub_destination" class="form-submit">Add Sub-Destination</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Sub-Destination Modal -->
    <div class="modal" id="editSubModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h2>Edit Sub-Destination</h2>
                <span class="close-btn" onclick="closeModal('editSubModal')" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <form action="manage_destinations.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="sub_image_id" id="edit-sub-image-id">
                
                <div class="form-group">
                    <label for="edit-sub-name">Sub-Destination Name</label>
                    <input type="text" class="form-control" id="edit-sub-name" name="sub_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-sub-description">Description</label>
                    <textarea class="form-control" id="edit-sub-description" name="sub_description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Current Image</label>
                    <div>
                        <img id="current-sub-image" src="" alt="Current Sub Image" style="max-height: 150px; margin: 10px 0;">
                    </div>
                    <div style="margin-bottom: 10px; color: #6c757d; font-size: 0.875rem;">Leave empty to keep current image</div>
                    <input type="file" class="form-control" id="edit-sub-image" name="sub_image" accept="image/*">
                </div>
                
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="add-btn" style="background-color: #6c757d; margin-right: 10px;" onclick="closeModal('editSubModal')">Cancel</button>
                    <button type="submit" name="edit_sub_destination" class="form-submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Sub-Destination Modal -->
    <div class="modal" id="deleteSubModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteSubModal')" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <div style="margin-bottom: 20px;">
                Are you sure you want to delete this sub-destination? This action cannot be undone.
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <form action="manage_destinations.php" method="post">
                    <input type="hidden" name="sub_image_id" id="delete-sub-image-id">
                    <button type="button" class="add-btn" style="background-color: #6c757d; margin-right: 10px;" onclick="closeModal('deleteSubModal')">Cancel</button>
                    <button type="submit" name="delete_sub_destination" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
            document.body.style.overflow = "hidden"; // Prevent scrolling when modal is open
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
            document.body.style.overflow = ""; // Restore scrolling
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
                document.body.style.overflow = ""; // Restore scrolling
            }
        }
        
        // Search Destinations functionality
        function searchDestinations() {
            const input = document.getElementById('destinationSearch');
            const filter = input.value.toUpperCase();
            const table = document.querySelector('.table');
            const tr = table.getElementsByTagName('tr');
            const clearBtn = document.getElementById('clearSearchBtn');
            
            // Show/hide clear button based on search input
            if (input.value.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
            
            // Loop through all table rows, and hide those who don't match the search query
            for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header row
                const nameColumn = tr[i].getElementsByTagName('td')[2]; // Name is in the 3rd column (index 2)
                const descColumn = tr[i].getElementsByTagName('td')[3]; // Description is in the 4th column (index 3)
                
                if (nameColumn && descColumn) {
                    const nameText = nameColumn.textContent || nameColumn.innerText;
                    const descText = descColumn.textContent || descColumn.innerText;
                    
                    if (nameText.toUpperCase().indexOf(filter) > -1 || descText.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        
        function clearSearch() {
            document.getElementById('destinationSearch').value = '';
            document.getElementById('clearSearchBtn').style.display = 'none';
            searchDestinations();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Set up search functionality
            document.getElementById('destinationSearch').addEventListener('keyup', searchDestinations);
            
            // Add another sub-destination field
            document.getElementById('add-sub-destination').addEventListener('click', function() {
                const container = document.getElementById('sub-destinations-container');
                const newSubDestination = document.querySelector('.sub-destination').cloneNode(true);
                
                // Clear input values
                newSubDestination.querySelectorAll('input[type="text"], textarea, input[type="file"]').forEach(input => {
                    input.value = '';
                });
                
                // Ensure checkbox is checked
                newSubDestination.querySelector('.sub-enabled').checked = true;
                
                container.appendChild(newSubDestination);
                
                // Add event listener to the new checkbox
                addCheckboxHandlers(newSubDestination.querySelector('.sub-enabled'));
            });
            
            // Function to handle enable/disable sub-destination fields
            function addCheckboxHandlers(checkbox) {
                checkbox.addEventListener('change', function() {
                    const subDestination = this.closest('.sub-destination');
                    const inputs = subDestination.querySelectorAll('input[type="text"], textarea, input[type="file"]');
                    
                    if (this.checked) {
                        inputs.forEach(input => {
                            input.removeAttribute('disabled');
                        });
                    } else {
                        inputs.forEach(input => {
                            input.setAttribute('disabled', 'disabled');
                        });
                    }
                });
            }
            
            // Add handlers to existing checkboxes
            document.querySelectorAll('.sub-enabled').forEach(addCheckboxHandlers);
            
            // Handle view sub-images modal
            const viewSubButtons = document.querySelectorAll('.view-sub-btn');
            viewSubButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const name = row.querySelector('td:nth-child(3)').textContent;
                    
                    document.getElementById('sub-destination-name').textContent = name;
                    document.getElementById('parent-destination-id').value = id;
                    document.getElementById('parent-destination-name').textContent = name;
                    
                    // Show Add Sub Destination button
                    document.getElementById('addSubDestinationBtn').style.display = 'inline-block';
                    document.getElementById('addSubDestinationBtn').onclick = function() {
                        closeModal('subImagesModal');
                        openModal('addSubModal');
                    };
                    
                    // Show the modal
                    openModal('subImagesModal');
                    
                    // Load sub-images via AJAX
                    fetch(`get_sub_images.php?destination_id=${id}`)
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('sub-images-container').innerHTML = data;
                        })
                        .catch(error => {
                            console.error('Error fetching sub-images:', error);
                            document.getElementById('sub-images-container').innerHTML = 
                                '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;">Error loading sub-images.</div>';
                        });
                });
            });
            
            // Handle edit destination modal
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const description = this.getAttribute('data-description');
                    const image = this.getAttribute('data-image');
                    
                    document.getElementById('edit-destination-id').value = id;
                    document.getElementById('edit-name').value = name;
                    document.getElementById('edit-description').value = description;
                    document.getElementById('current-image').src = '../destinations/' + image;
                    
                    openModal('editModal');
                });
            });
            
            // Make directory 'destinations/sub' if it doesn't exist
            function ensureSubDirectoryExists() {
                fetch('ajax/ensure_directory.php?path=../destinations/sub')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Error creating directory:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
            
            // Call the function to ensure directory exists
            ensureSubDirectoryExists();
            
            // Use event delegation for all buttons including delete buttons
            document.addEventListener('click', function(e) {
                // Handle main destination delete buttons
                if (e.target.classList.contains('delete-btn') || 
                    (e.target.parentElement && e.target.parentElement.classList.contains('delete-btn'))) {
                    
                    const button = e.target.classList.contains('delete-btn') ? e.target : e.target.parentElement;
                    
                    // Skip if this is inside a form (already being handled)
                    if (button.closest('form')) return;
                    
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    
                    document.getElementById('delete-destination-id').value = id;
                    document.getElementById('destination-name').textContent = name;
                    
                    openModal('deleteModal');
                }
                
                // Delegate event handling for dynamically created elements
                if (e.target && e.target.classList.contains('edit-sub-btn') || 
                   (e.target.parentElement && e.target.parentElement.classList.contains('edit-sub-btn'))) {
                    
                    const button = e.target.classList.contains('edit-sub-btn') ? e.target : e.target.parentElement;
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const description = button.getAttribute('data-description');
                    const image = button.getAttribute('data-image');
                    
                    document.getElementById('edit-sub-image-id').value = id;
                    document.getElementById('edit-sub-name').value = name;
                    document.getElementById('edit-sub-description').value = description;
                    document.getElementById('current-sub-image').src = '../destinations/sub/' + image;
                    
                    openModal('editSubModal');
                }
                
                if (e.target && e.target.classList.contains('delete-sub-btn') || 
                   (e.target.parentElement && e.target.parentElement.classList.contains('delete-sub-btn'))) {
                    
                    const button = e.target.classList.contains('delete-sub-btn') ? e.target : e.target.parentElement;
                    const id = button.getAttribute('data-id');
                    
                    document.getElementById('delete-sub-image-id').value = id;
                    
                    openModal('deleteSubModal');
                }
            });
        });
    </script>
</body>
</html> 
