<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';

// Check if admin is logged in
require_admin_login();

// Get vehicle ID
$vehicle_id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $vehicle_id = intval($_GET['id']);
} else {
    header("Location: admin.php#vehicles-section");
    exit;
}

// Get vehicle information
$vehicle_query = "SELECT * FROM vehicles WHERE vehicle_id = ?";
$vehicle_stmt = mysqli_prepare($conn, $vehicle_query);
mysqli_stmt_bind_param($vehicle_stmt, "i", $vehicle_id);
mysqli_stmt_execute($vehicle_stmt);
$vehicle_result = mysqli_stmt_get_result($vehicle_stmt);

if (mysqli_num_rows($vehicle_result) == 0) {
    header("Location: admin.php#vehicles-section");
    exit;
}

$vehicle = mysqli_fetch_assoc($vehicle_result);

// Initialize editing variables
$is_editing_image = false;
$image_to_edit = null;
$edit_image_id = 0;

$is_editing_detail = false;
$detail_to_edit = null;
$edit_detail_id = 0;

// Check if editing an existing sub-image
if (isset($_GET['edit_image']) && is_numeric($_GET['edit_image'])) {
    $edit_image_id = intval($_GET['edit_image']);
    $edit_query = "SELECT * FROM vehicle_sub_images WHERE sub_image_id = ? AND vehicle_id = ?";
    $edit_stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($edit_stmt, "ii", $edit_image_id, $vehicle_id);
    mysqli_stmt_execute($edit_stmt);
    $edit_result = mysqli_stmt_get_result($edit_stmt);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $is_editing_image = true;
        $image_to_edit = mysqli_fetch_assoc($edit_result);
    }
}

// Check if editing an existing sub-detail
if (isset($_GET['edit_detail']) && is_numeric($_GET['edit_detail'])) {
    $edit_detail_id = intval($_GET['edit_detail']);
    $edit_query = "SELECT * FROM vehicle_sub_details WHERE detail_id = ? AND vehicle_id = ?";
    $edit_stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($edit_stmt, "ii", $edit_detail_id, $vehicle_id);
    mysqli_stmt_execute($edit_stmt);
    $edit_result = mysqli_stmt_get_result($edit_stmt);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $is_editing_detail = true;
        $detail_to_edit = mysqli_fetch_assoc($edit_result);
    }
}

// Get vehicle sub-images
$sub_images_query = "SELECT * FROM vehicle_sub_images WHERE vehicle_id = ? ORDER BY created_at DESC";
$sub_images_stmt = mysqli_prepare($conn, $sub_images_query);
mysqli_stmt_bind_param($sub_images_stmt, "i", $vehicle_id);
mysqli_stmt_execute($sub_images_stmt);
$sub_images_result = mysqli_stmt_get_result($sub_images_stmt);
$sub_images = [];
while ($sub_image = mysqli_fetch_assoc($sub_images_result)) {
    $sub_images[] = $sub_image;
}

// Get vehicle sub-details
$sub_details_query = "SELECT * FROM vehicle_sub_details WHERE vehicle_id = ? ORDER BY order_num ASC";
$sub_details_stmt = mysqli_prepare($conn, $sub_details_query);
mysqli_stmt_bind_param($sub_details_stmt, "i", $vehicle_id);
mysqli_stmt_execute($sub_details_stmt);
$sub_details_result = mysqli_stmt_get_result($sub_details_stmt);
$sub_details = [];
while ($sub_detail = mysqli_fetch_assoc($sub_details_result)) {
    $sub_details[] = $sub_detail;
}

// Get vehicle features (includes/excludes)
$features_query = "SELECT * FROM vehicle_features WHERE vehicle_id = ? ORDER BY is_included DESC";
$features_stmt = mysqli_prepare($conn, $features_query);
mysqli_stmt_bind_param($features_stmt, "i", $vehicle_id);
mysqli_stmt_execute($features_stmt);
$features_result = mysqli_stmt_get_result($features_stmt);
$includes = [];
$excludes = [];
while ($feature = mysqli_fetch_assoc($features_result)) {
    if ($feature['is_included']) {
        $includes[] = $feature;
    } else {
        $excludes[] = $feature;
    }
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add sub-image
    if (isset($_POST['add_sub_image'])) {
        $title = sanitize_input($_POST['image_title']);
        $description = sanitize_input($_POST['image_description']);
        
        // Handle image upload
        $image = null;
        if (isset($_FILES['sub_image']) && $_FILES['sub_image']['size'] > 0) {
            $upload_result = upload_file($_FILES['sub_image']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                $add_query = "INSERT INTO vehicle_sub_images (vehicle_id, title, description, image) 
                             VALUES (?, ?, ?, ?)";
                $add_stmt = mysqli_prepare($conn, $add_query);
                mysqli_stmt_bind_param($add_stmt, "isss", $vehicle_id, $title, $description, $image);
                
                if (mysqli_stmt_execute($add_stmt)) {
                    header("Location: manage_vehicle_details.php?id=$vehicle_id&success=image_added");
                    exit;
                } else {
                    $error_message = "Error adding sub-image: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            $error_message = "Please select an image to upload.";
        }
    }
    
    // Update sub-image
    if (isset($_POST['update_sub_image'])) {
        $sub_image_id = intval($_POST['sub_image_id']);
        $title = sanitize_input($_POST['image_title']);
        $description = sanitize_input($_POST['image_description']);
        
        if (isset($_FILES['sub_image']) && $_FILES['sub_image']['size'] > 0) {
            // Handle file upload for image update
            $upload_result = upload_file($_FILES['sub_image']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                $update_query = "UPDATE vehicle_sub_images SET title = ?, description = ?, image = ? 
                               WHERE sub_image_id = ? AND vehicle_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "sssii", $title, $description, $image, $sub_image_id, $vehicle_id);
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            // Update without changing image
            $update_query = "UPDATE vehicle_sub_images SET title = ?, description = ? 
                           WHERE sub_image_id = ? AND vehicle_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssii", $title, $description, $sub_image_id, $vehicle_id);
        }
        
        if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=image_updated");
            exit;
        } else if (!isset($error_message)) {
            $error_message = "Error updating sub-image: " . mysqli_error($conn);
        }
    }
    
    // Delete sub-image
    if (isset($_POST['delete_sub_image'])) {
        $sub_image_id = intval($_POST['sub_image_id']);
        
        $delete_query = "DELETE FROM vehicle_sub_images WHERE sub_image_id = ? AND vehicle_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $sub_image_id, $vehicle_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=image_deleted");
            exit;
        } else {
            $error_message = "Error deleting sub-image: " . mysqli_error($conn);
        }
    }
    
    // Add sub-detail
    if (isset($_POST['add_sub_detail'])) {
        $header = sanitize_input($_POST['detail_header']);
        $content = sanitize_input($_POST['detail_content']);
        $icon = sanitize_input($_POST['detail_icon']);
        $order_num = intval($_POST['detail_order']);
        $price = floatval($_POST['detail_price']);
        
        // Handle image upload for detail
        $detail_image = null;
        if (isset($_FILES['detail_image']) && $_FILES['detail_image']['size'] > 0) {
            $upload_result = upload_file($_FILES['detail_image']);
            if ($upload_result['success']) {
                $detail_image = $upload_result['filename'];
            } else {
                $error_message = "Error uploading detail image: " . $upload_result['message'];
                // Continue without the image if there's an error
            }
        }
        
        // Note: This assumes the 'image' column exists in the vehicle_sub_details table
        // Modify the query based on whether we have an image or not
        if ($detail_image) {
            $add_query = "INSERT INTO vehicle_sub_details (vehicle_id, header, content, icon, order_num, price, image) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $add_stmt = mysqli_prepare($conn, $add_query);
            mysqli_stmt_bind_param($add_stmt, "isssiis", $vehicle_id, $header, $content, $icon, $order_num, $price, $detail_image);
        } else {
            $add_query = "INSERT INTO vehicle_sub_details (vehicle_id, header, content, icon, order_num, price) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $add_stmt = mysqli_prepare($conn, $add_query);
            mysqli_stmt_bind_param($add_stmt, "isssid", $vehicle_id, $header, $content, $icon, $order_num, $price);
        }
        
        if (mysqli_stmt_execute($add_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=detail_added");
            exit;
        } else {
            $error_message = "Error adding sub-detail: " . mysqli_error($conn);
        }
    }
    
    // Update sub-detail
    if (isset($_POST['update_sub_detail'])) {
        $detail_id = intval($_POST['detail_id']);
        $header = sanitize_input($_POST['detail_header']);
        $content = sanitize_input($_POST['detail_content']);
        $icon = sanitize_input($_POST['detail_icon']);
        $order_num = intval($_POST['detail_order']);
        $price = floatval($_POST['detail_price']);
        
        // Handle image upload for detail update
        $detail_image = null;
        $has_new_image = false;
        
        if (isset($_FILES['detail_image']) && $_FILES['detail_image']['size'] > 0) {
            $upload_result = upload_file($_FILES['detail_image']);
            if ($upload_result['success']) {
                $detail_image = $upload_result['filename'];
                $has_new_image = true;
            } else {
                $error_message = "Error uploading detail image: " . $upload_result['message'];
                // Continue without changing the image if there's an error
            }
        }
        
        // Note: This assumes the 'image' column exists in the vehicle_sub_details table
        // Update with or without changing the image
        if ($has_new_image) {
            $update_query = "UPDATE vehicle_sub_details SET header = ?, content = ?, icon = ?, order_num = ?, price = ?, image = ? 
                            WHERE detail_id = ? AND vehicle_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssiisii", $header, $content, $icon, $order_num, $price, $detail_image, $detail_id, $vehicle_id);
        } else {
            $update_query = "UPDATE vehicle_sub_details SET header = ?, content = ?, icon = ?, order_num = ?, price = ? 
                            WHERE detail_id = ? AND vehicle_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssiiii", $header, $content, $icon, $order_num, $price, $detail_id, $vehicle_id);
        }
        
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=detail_updated");
            exit;
        } else {
            $error_message = "Error updating sub-detail: " . mysqli_error($conn);
        }
    }
    
    // Delete sub-detail
    if (isset($_POST['delete_sub_detail'])) {
        $detail_id = intval($_POST['detail_id']);
        
        $delete_query = "DELETE FROM vehicle_sub_details WHERE detail_id = ? AND vehicle_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $detail_id, $vehicle_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=detail_deleted");
            exit;
        } else {
            $error_message = "Error deleting sub-detail: " . mysqli_error($conn);
        }
    }
    
    // Add feature (include/exclude)
    if (isset($_POST['add_feature'])) {
        $feature_description = sanitize_input($_POST['feature_description']);
        $is_included = isset($_POST['is_included']) ? 1 : 0;
        
        $add_query = "INSERT INTO vehicle_features (vehicle_id, feature_description, is_included)
                     VALUES (?, ?, ?)";
        $add_stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($add_stmt, "isi", $vehicle_id, $feature_description, $is_included);
        
        if (mysqli_stmt_execute($add_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=feature_added");
            exit;
        } else {
            $error_message = "Error adding feature: " . mysqli_error($conn);
        }
    }
    
    // Delete feature
    if (isset($_POST['delete_feature'])) {
        $feature_id = intval($_POST['feature_id']);
        
        $delete_query = "DELETE FROM vehicle_features WHERE feature_id = ? AND vehicle_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $feature_id, $vehicle_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_vehicle_details.php?id=$vehicle_id&success=feature_deleted");
            exit;
        } else {
            $error_message = "Error deleting feature: " . mysqli_error($conn);
        }
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'image_added':
            $success_message = "Vehicle sub-image added successfully.";
            break;
        case 'image_updated':
            $success_message = "Vehicle sub-image updated successfully.";
            break;
        case 'image_deleted':
            $success_message = "Vehicle sub-image deleted successfully.";
            break;
        case 'detail_added':
            $success_message = "Vehicle sub-detail added successfully.";
            break;
        case 'detail_updated':
            $success_message = "Vehicle sub-detail updated successfully.";
            break;
        case 'detail_deleted':
            $success_message = "Vehicle sub-detail deleted successfully.";
            break;
        case 'feature_added':
            $success_message = "Vehicle feature added successfully.";
            break;
        case 'feature_deleted':
            $success_message = "Vehicle feature deleted successfully.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicle Details - Adventure Travel.lk Admin</title>
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
            background-color: rgb(4, 39, 37);
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

        .back-link {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background-color: #145a55;
        }

        .vehicle-info {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .vehicle-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .vehicle-image {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 5px;
            margin-top: 15px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            background-color: #f8f9fa;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s ease;
        }

        .tab.active {
            border-color: #ddd;
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .tab:hover {
            background-color: #e9ecef;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-check-input {
            margin-right: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #145a55;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: var(--primary-color);
            color: #fff;
            font-weight: 600;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        table tr:hover {
            background-color: rgba(101, 255, 193, 0.1);
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .gallery-item {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .gallery-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .gallery-caption {
            padding: 15px;
        }

        .gallery-actions {
            display: flex;
            justify-content: space-between;
            padding: 0 15px 15px;
        }

        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2, .sidebar-menu a span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-width: 90%;
            position: relative;
            transform: translateY(-50px);
            animation: slideDown 0.3s forwards;
        }
        
        @keyframes slideDown {
            to { transform: translateY(0); }
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal p {
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
                    <li><a href="admin.php#tour-packages-section"><span><i class="fas fa-suitcase"></i> Tour Packages</span></a></li>
                    <li><a href="admin.php#one-day-tour-packages-section"><span><i class="fas fa-clock"></i> One Day Tours</span></a></li>
                    <li><a href="admin.php#special-tour-packages-section"><span><i class="fas fa-star"></i> Special Tours</span></a></li>
                    <li><a href="admin.php#vehicles-section" class="active"><span><i class="fas fa-car"></i> Vehicles</span></a></li>
                    <li><a href="manage_destinations.php"><span><i class="fas fa-map-marker-alt"></i> Destinations</span></a></li>
                    <li><a href="manage_hotels.php"><span><i class="fas fa-hotel"></i> Hotels</span></a></li>
                    <li><a href="manage_policies.php"><span><i class="fas fa-file-alt"></i> Policies</span></a></li>
                    <li><a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Admins</span></a></li>
                    <li><a href="user_messages.php"><span><i class="fas fa-comment-dots"></i> Messages</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Vehicle Details</h1>
                <a href="admin.php#vehicles-section" class="back-link">Back to Vehicles</a>
            </div>
            
            <div class="vehicle-info">
                <h2><?php echo htmlspecialchars($vehicle['name']); ?> (<?php echo htmlspecialchars($vehicle['type']); ?>)</h2>
                <div class="vehicle-meta">
                    <div><strong>Capacity:</strong> <?php echo $vehicle['capacity']; ?> persons</div>
                    <div><strong>Price:</strong> $<?php echo number_format($vehicle['price_per_day'], 2); ?>/day</div>
                    <div><strong>Status:</strong> 
                        <span style="color: <?php echo $vehicle['available'] ? 'green' : 'red'; ?>">
                            <?php echo $vehicle['available'] ? 'Available' : 'Not Available'; ?>
                        </span>
                    </div>
                </div>
                <p><?php echo htmlspecialchars(substr($vehicle['description'], 0, 200)) . '...'; ?></p>
                <img src="../images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="vehicle-image">
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab active" data-tab="sub-images">Images Gallery</div>
                <div class="tab" data-tab="sub-details">Vehicle Details</div>
                <div class="tab" data-tab="features">Features</div>
            </div>
            
            <!-- Sub-Images Tab -->
            <div class="tab-content active" id="sub-images-tab">
                <div class="card">
                    <h2><?php echo $is_editing_image ? 'Edit Image' : 'Add New Image'; ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($is_editing_image): ?>
                            <input type="hidden" name="sub_image_id" value="<?php echo $image_to_edit['sub_image_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="image_title">Title</label>
                            <input type="text" id="image_title" name="image_title" class="form-control" required value="<?php echo $is_editing_image ? htmlspecialchars($image_to_edit['title']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="image_description">Description</label>
                            <textarea id="image_description" name="image_description" class="form-control" rows="3"><?php echo $is_editing_image ? htmlspecialchars($image_to_edit['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="sub_image">
                                <?php if ($is_editing_image): ?>
                                    Image (Leave empty to keep current image)
                                <?php else: ?>
                                    Image
                                <?php endif; ?>
                            </label>
                            <input type="file" id="sub_image" name="sub_image" class="form-control" accept="image/*" <?php echo !$is_editing_image ? 'required' : ''; ?>>
                            
                            <?php if ($is_editing_image && !empty($image_to_edit['image'])): ?>
                                <div style="margin-top: 10px;">
                                    <p>Current Image:</p>
                                    <img src="../images/<?php echo htmlspecialchars($image_to_edit['image']); ?>" alt="Current Image" style="max-width: 200px; max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_editing_image): ?>
                            <button type="submit" name="update_sub_image" class="btn btn-primary">Update Image</button>
                            <a href="manage_vehicle_details.php?id=<?php echo $vehicle_id; ?>" class="btn" style="background-color: #6c757d; color: #fff; margin-left: 10px;">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_sub_image" class="btn btn-primary">Add Image</button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (!empty($sub_images)): ?>
                    <h2>Image Gallery</h2>
                    <div class="gallery">
                        <?php foreach($sub_images as $sub_image): ?>
                            <div class="gallery-item">
                                <img src="../images/<?php echo htmlspecialchars($sub_image['image']); ?>" alt="<?php echo htmlspecialchars($sub_image['title']); ?>" class="gallery-image">
                                <div class="gallery-caption">
                                    <h3><?php echo htmlspecialchars($sub_image['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($sub_image['description']); ?></p>
                                </div>
                                <div class="gallery-actions">
                                    <a href="manage_vehicle_details.php?id=<?php echo $vehicle_id; ?>&edit_image=<?php echo $sub_image['sub_image_id']; ?>" class="btn btn-primary">Edit</a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="sub_image_id" value="<?php echo $sub_image['sub_image_id']; ?>">
                                        <button type="submit" name="delete_sub_image" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No images added yet. Add your first image using the form above.</p>
                <?php endif; ?>
            </div>
            
            <!-- Sub-Details Tab -->
            <div class="tab-content" id="sub-details-tab">
                <div class="card">
                    <h2><?php echo $is_editing_detail ? 'Edit Detail' : 'Add New Detail'; ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($is_editing_detail): ?>
                            <input type="hidden" name="detail_id" value="<?php echo $detail_to_edit['detail_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="detail_header">Header</label>
                            <input type="text" id="detail_header" name="detail_header" class="form-control" required value="<?php echo $is_editing_detail ? htmlspecialchars($detail_to_edit['header']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_content">Content</label>
                            <textarea id="detail_content" name="detail_content" class="form-control" rows="4" required><?php echo $is_editing_detail ? htmlspecialchars($detail_to_edit['content']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_image">Image</label>
                            <input type="file" id="detail_image" name="detail_image" class="form-control" accept="image/*">
                            
                            <?php if ($is_editing_detail && isset($detail_to_edit['image']) && !empty($detail_to_edit['image'])): ?>
                                <div style="margin-top: 10px;">
                                    <p>Current Image:</p>
                                    <img src="../images/<?php echo htmlspecialchars($detail_to_edit['image']); ?>" alt="Detail Image" style="max-width: 200px; max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_price">Price ($)</label>
                            <input type="number" id="detail_price" name="detail_price" class="form-control" min="0" step="0.01" value="<?php echo $is_editing_detail ? $detail_to_edit['price'] : '0.00'; ?>">
                            <small>Enter the price in USD. Leave as 0 if not applicable.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_icon">Icon Class (FontAwesome)</label>
                            <select id="detail_icon" name="detail_icon" class="form-control">
                                <option value="fas fa-car" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-car' ? 'selected' : ''; ?>>Car (&#xf1b9;)</option>
                                <option value="fas fa-bus" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-bus' ? 'selected' : ''; ?>>Bus (&#xf207;)</option>
                                <option value="fas fa-truck" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-truck' ? 'selected' : ''; ?>>Truck (&#xf0d1;)</option>
                                <option value="fas fa-motorcycle" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-motorcycle' ? 'selected' : ''; ?>>Motorcycle (&#xf21c;)</option>
                                <option value="fas fa-bicycle" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-bicycle' ? 'selected' : ''; ?>>Bicycle (&#xf206;)</option>
                                <option value="fas fa-gas-pump" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-gas-pump' ? 'selected' : ''; ?>>Gas Pump (&#xf52f;)</option>
                                <option value="fas fa-oil-can" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-oil-can' ? 'selected' : ''; ?>>Oil Can (&#xf613;)</option>
                                <option value="fas fa-cog" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-cog' ? 'selected' : ''; ?>>Cog (&#xf013;)</option>
                                <option value="fas fa-tools" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-tools' ? 'selected' : ''; ?>>Tools (&#xf7d9;)</option>
                                <option value="fas fa-tachometer-alt" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-tachometer-alt' ? 'selected' : ''; ?>>Speedometer (&#xf3fd;)</option>
                                <option value="fas fa-road" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-road' ? 'selected' : ''; ?>>Road (&#xf018;)</option>
                                <option value="fas fa-map-marker-alt" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-map-marker-alt' ? 'selected' : ''; ?>>Map Marker (&#xf3c5;)</option>
                                <option value="fas fa-calendar-alt" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-calendar-alt' ? 'selected' : ''; ?>>Calendar (&#xf073;)</option>
                                <option value="fas fa-users" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-users' ? 'selected' : ''; ?>>Users (&#xf0c0;)</option>
                                <option value="fas fa-suitcase" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-suitcase' ? 'selected' : ''; ?>>Suitcase (&#xf0f2;)</option>
                                <option value="fas fa-snowflake" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-snowflake' ? 'selected' : ''; ?>>Air Conditioning (&#xf2dc;)</option>
                                <option value="fas fa-music" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-music' ? 'selected' : ''; ?>>Music System (&#xf001;)</option>
                                <option value="fas fa-wifi" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-wifi' ? 'selected' : ''; ?>>Wi-Fi (&#xf1eb;)</option>
                                <option value="fas fa-plug" <?php echo $is_editing_detail && $detail_to_edit['icon'] == 'fas fa-plug' ? 'selected' : ''; ?>>Power Outlet (&#xf1e6;)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_order">Display Order</label>
                            <input type="number" id="detail_order" name="detail_order" class="form-control" min="0" value="<?php echo $is_editing_detail ? $detail_to_edit['order_num'] : '0'; ?>">
                            <small>Lower numbers will be displayed first</small>
                        </div>
                        
                        <?php if ($is_editing_detail): ?>
                            <button type="submit" name="update_sub_detail" class="btn btn-primary">Update Detail</button>
                            <a href="manage_vehicle_details.php?id=<?php echo $vehicle_id; ?>" class="btn" style="background-color: #6c757d; color: #fff; margin-left: 10px;">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_sub_detail" class="btn btn-primary">Add Detail</button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (!empty($sub_details)): ?>
                    <h2>Vehicle Details</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Icon</th>
                                <th>Header</th>
                                <th>Content</th>
                                <th>Image</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sub_details as $detail): ?>
                                <tr>
                                    <td><?php echo $detail['order_num']; ?></td>
                                    <td><i class="<?php echo htmlspecialchars($detail['icon']); ?>"></i></td>
                                    <td><?php echo htmlspecialchars($detail['header']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($detail['content'], 0, 100)) . (strlen($detail['content']) > 100 ? '...' : ''); ?></td>
                                    <td>
                                        <?php if (isset($detail['image']) && !empty($detail['image'])): ?>
                                            <img src="../images/<?php echo htmlspecialchars($detail['image']); ?>" alt="Detail Image" style="max-width: 80px; max-height: 60px;">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($detail['price'], 2); ?></td>
                                    <td>
                                        <a href="manage_vehicle_details.php?id=<?php echo $vehicle_id; ?>&edit_detail=<?php echo $detail['detail_id']; ?>#sub-details-tab" class="btn btn-primary" style="margin-right: 5px;">Edit</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="detail_id" value="<?php echo $detail['detail_id']; ?>">
                                            <button type="submit" name="delete_sub_detail" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No details added yet. Add your first detail using the form above.</p>
                <?php endif; ?>
            </div>
            
            <!-- Features Tab -->
            <div class="tab-content" id="features-tab">
                <div class="card">
                    <h2>Add Feature</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="feature_description">Feature Description</label>
                            <input type="text" id="feature_description" name="feature_description" class="form-control" required>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="is_included" name="is_included" class="form-check-input" checked>
                            <label for="is_included" class="form-check-label">This feature is included</label>
                        </div>
                        
                        <button type="submit" name="add_feature" class="btn btn-primary">Add Feature</button>
                    </form>
                </div>
                
                <?php if (!empty($includes)): ?>
                    <h2>Included Features</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Feature Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($includes as $feature): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($feature['feature_description']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="feature_id" value="<?php echo $feature['feature_id']; ?>">
                                            <button type="submit" name="delete_feature" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No included features added yet.</p>
                <?php endif; ?>
                
                <?php if (!empty($excludes)): ?>
                    <h2>Not Included Features</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Feature Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($excludes as $feature): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($feature['feature_description']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="feature_id" value="<?php echo $feature['feature_id']; ?>">
                                            <button type="submit" name="delete_feature" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No excluded features added yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Confirm Deletion</h2>
            <p id="deleteMessage">Are you sure you want to delete this item?</p>
            <div class="modal-actions">
                <button id="cancelDelete" class="btn" style="background-color: #6c757d; color: #fff;">Cancel</button>
                <button id="confirmDelete" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all tab content
                    const tabContents = document.querySelectorAll('.tab-content');
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Show the selected tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
            
            // Check if hash exists and activate appropriate tab
            const hash = window.location.hash;
            if (hash) {
                const tabId = hash.substring(1).replace('-tab', '');
                const tab = document.querySelector(`.tab[data-tab="${tabId}"]`);
                if (tab) {
                    tab.click();
                }
            }

            // Delete confirmation modal functionality
            const modal = document.getElementById('deleteModal');
            const closeBtn = document.querySelector('.close');
            const cancelBtn = document.getElementById('cancelDelete');
            const confirmBtn = document.getElementById('confirmDelete');
            let deleteForm = null;
            let deleteButton = null;
            
            // Intercept all form submissions
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Check if this form contains a delete button
                    const deleteBtn = this.querySelector('button[name^="delete_"]');
                    if (deleteBtn) {
                        e.preventDefault(); // Stop form submission
                        deleteForm = this;
                        deleteButton = deleteBtn;
                        
                        // Set appropriate message based on what is being deleted
                        const deleteMessage = document.getElementById('deleteMessage');
                        if (deleteBtn.name === 'delete_sub_image') {
                            deleteMessage.textContent = 'Are you sure you want to delete this image? This action cannot be undone.';
                        } else if (deleteBtn.name === 'delete_sub_detail') {
                            deleteMessage.textContent = 'Are you sure you want to delete this vehicle detail? This action cannot be undone.';
                        } else if (deleteBtn.name === 'delete_feature') {
                            deleteMessage.textContent = 'Are you sure you want to delete this feature? This action cannot be undone.';
                        }
                        
                        // Show the modal
                        modal.style.display = 'block';
                    }
                });
            });
            
            // Close modal when clicking close button
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking cancel button
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Submit form when clicking confirm button
            confirmBtn.addEventListener('click', function() {
                if (deleteForm && deleteButton) {
                    // Get current URL
                    const currentUrl = window.location.href.split('#')[0];
                    
                    // Get form data
                    const formData = new FormData(deleteForm);
                    formData.append(deleteButton.name, 'true');
                    
                    // Hide modal
                    modal.style.display = 'none';
                    
                    // Show loading indicator
                    const loadingDiv = document.createElement('div');
                    loadingDiv.style.position = 'fixed';
                    loadingDiv.style.top = '0';
                    loadingDiv.style.left = '0';
                    loadingDiv.style.width = '100%';
                    loadingDiv.style.height = '100%';
                    loadingDiv.style.backgroundColor = 'rgba(0,0,0,0.3)';
                    loadingDiv.style.zIndex = '9999';
                    loadingDiv.style.display = 'flex';
                    loadingDiv.style.alignItems = 'center';
                    loadingDiv.style.justifyContent = 'center';
                    
                    const spinner = document.createElement('div');
                    spinner.style.border = '4px solid #f3f3f3';
                    spinner.style.borderTop = '4px solid var(--primary-color)';
                    spinner.style.borderRadius = '50%';
                    spinner.style.width = '50px';
                    spinner.style.height = '50px';
                    spinner.style.animation = 'spin 2s linear infinite';
                    
                    loadingDiv.appendChild(spinner);
                    document.body.appendChild(loadingDiv);
                    
                    // Submit the form using fetch to avoid resubmission warning
                    fetch(currentUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred during deletion. Please try again.');
                        document.body.removeChild(loadingDiv);
                    });
                }
            });
            
            // Close the modal when clicking outside of it
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Add CSS for spinner animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>
