<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';

// Check if admin is logged in
require_admin_login();

// Get package ID
$package_id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $package_id = intval($_GET['id']);
} else {
    header("Location: admin.php");
    exit;
}

// Get package information
$package_query = "SELECT p.*, pt.type_name 
                FROM packages p 
                JOIN package_types pt ON p.type_id = pt.type_id 
                WHERE p.package_id = ?";
$package_stmt = mysqli_prepare($conn, $package_query);
mysqli_stmt_bind_param($package_stmt, "i", $package_id);
mysqli_stmt_execute($package_stmt);
$package_result = mysqli_stmt_get_result($package_stmt);

if (mysqli_num_rows($package_result) == 0) {
    header("Location: admin.php");
    exit;
}

$package = mysqli_fetch_assoc($package_result);

// Get all destinations
$destinations_query = "SELECT * FROM destinations ORDER BY name ASC";
$destinations_result = mysqli_query($conn, $destinations_query);
$destinations = [];
while ($destination = mysqli_fetch_assoc($destinations_result)) {
    $destinations[] = $destination;
}

// Get all hotels
$hotels_query = "SELECT h.*, d.name as destination_name 
                FROM hotels h 
                LEFT JOIN destinations d ON h.destination_id = d.destination_id 
                ORDER BY h.name ASC";
$hotels_result = mysqli_query($conn, $hotels_query);
$hotels = [];
while ($hotel = mysqli_fetch_assoc($hotels_result)) {
    $hotels[] = $hotel;
}

// Initialize editing variables
$is_editing = false;
$detail_to_edit = null;
$edit_id = 0;

// Check if editing an existing detail
if (isset($_GET['edit_detail']) && is_numeric($_GET['edit_detail'])) {
    $edit_id = intval($_GET['edit_detail']);
    $edit_query = "SELECT * FROM package_details WHERE detail_id = ? AND package_id = ?";
    $edit_stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($edit_stmt, "ii", $edit_id, $package_id);
    mysqli_stmt_execute($edit_stmt);
    $edit_result = mysqli_stmt_get_result($edit_stmt);
    
    if (mysqli_num_rows($edit_result) > 0) {
        $is_editing = true;
        $detail_to_edit = mysqli_fetch_assoc($edit_result);
    }
}

// Get package details
$details_query = "SELECT pd.*, d.name as destination_name, h.name as hotel_name 
                FROM package_details pd
                LEFT JOIN destinations d ON pd.destination_id = d.destination_id
                LEFT JOIN hotels h ON pd.hotel_id = h.hotel_id
                WHERE pd.package_id = ? AND pd.package_type = ?
                ORDER BY pd.day_number ASC";
$details_stmt = mysqli_prepare($conn, $details_query);
mysqli_stmt_bind_param($details_stmt, "ii", $package_id, $package['type_id']);
mysqli_stmt_execute($details_stmt);
$details_result = mysqli_stmt_get_result($details_stmt);
$package_details = [];
while ($detail = mysqli_fetch_assoc($details_result)) {
    $package_details[] = $detail;
}

// Get package includes/excludes
$includes_query = "SELECT * FROM package_includes_excludes WHERE package_id = ? ORDER BY is_included DESC";
$includes_stmt = mysqli_prepare($conn, $includes_query);
mysqli_stmt_bind_param($includes_stmt, "i", $package_id);
mysqli_stmt_execute($includes_stmt);
$includes_result = mysqli_stmt_get_result($includes_stmt);
$includes = [];
$excludes = [];
while ($item = mysqli_fetch_assoc($includes_result)) {
    if ($item['is_included']) {
        $includes[] = $item;
    } else {
        $excludes[] = $item;
    }
}

// Get package highlights
$highlights_query = "SELECT * FROM package_highlights WHERE package_id = ?";
$highlights_stmt = mysqli_prepare($conn, $highlights_query);
mysqli_stmt_bind_param($highlights_stmt, "i", $package_id);
mysqli_stmt_execute($highlights_stmt);
$highlights_result = mysqli_stmt_get_result($highlights_stmt);
$highlights = [];
while ($highlight = mysqli_fetch_assoc($highlights_result)) {
    $highlights[] = $highlight;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add package detail
    if (isset($_POST['add_detail'])) {
        $day_number = isset($_POST['day_number']) ? intval($_POST['day_number']) : null;
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $destination_id = !empty($_POST['destination_id']) ? intval($_POST['destination_id']) : null;
        $hotel_id = !empty($_POST['hotel_id']) ? intval($_POST['hotel_id']) : null;
        $meal_plan = sanitize_input($_POST['meal_plan']);
        $activities = sanitize_input($_POST['activities']);
        $transport_type = sanitize_input($_POST['transport_type']);
        
        // Handle image upload
        $image = null;
        if (isset($_FILES['detail_image']) && $_FILES['detail_image']['size'] > 0) {
            $upload_result = upload_file($_FILES['detail_image']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
            }
        }
        
        $add_query = "INSERT INTO package_details (package_id, package_type, day_number, title, description, 
                     destination_id, hotel_id, meal_plan, activities, transport_type, image)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $add_stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($add_stmt, "iiissiissss", $package_id, $package['type_id'], $day_number, 
                           $title, $description, $destination_id, $hotel_id, $meal_plan, 
                           $activities, $transport_type, $image);
        
        if (mysqli_stmt_execute($add_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=detail_added#details-tab");
            exit;
        } else {
            $error_message = "Error adding package detail: " . mysqli_error($conn);
        }
    }
    
    // Update existing package detail
    if (isset($_POST['update_detail'])) {
        $detail_id = intval($_POST['detail_id']);
        $day_number = isset($_POST['day_number']) ? intval($_POST['day_number']) : null;
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $destination_id = !empty($_POST['destination_id']) ? intval($_POST['destination_id']) : null;
        $hotel_id = !empty($_POST['hotel_id']) ? intval($_POST['hotel_id']) : null;
        $meal_plan = sanitize_input($_POST['meal_plan']);
        $activities = sanitize_input($_POST['activities']);
        $transport_type = sanitize_input($_POST['transport_type']);
        
        // Check if a new image is uploaded
        $image_query = "";
        $image_value = null;
        if (isset($_FILES['detail_image']) && $_FILES['detail_image']['size'] > 0) {
            $upload_result = upload_file($_FILES['detail_image']);
            if ($upload_result['success']) {
                $image_query = ", image = ?";
                $image_value = $upload_result['filename'];
            }
        }
        
        $update_query = "UPDATE package_details SET 
                        day_number = ?, 
                        title = ?, 
                        description = ?, 
                        destination_id = ?, 
                        hotel_id = ?, 
                        meal_plan = ?, 
                        activities = ?, 
                        transport_type = ?" . $image_query . "
                        WHERE detail_id = ? AND package_id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if ($image_value) {
            mysqli_stmt_bind_param($update_stmt, "issiissssii", $day_number, $title, $description, 
                                $destination_id, $hotel_id, $meal_plan, $activities, 
                                $transport_type, $image_value, $detail_id, $package_id);
        } else {
            mysqli_stmt_bind_param($update_stmt, "issiisssii", $day_number, $title, $description, 
                                $destination_id, $hotel_id, $meal_plan, $activities, 
                                $transport_type, $detail_id, $package_id);
        }
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=detail_updated#details-tab");
            exit;
        } else {
            $error_message = "Error updating package detail: " . mysqli_error($conn);
        }
    }
    
    // Add package include/exclude
    if (isset($_POST['add_include_exclude'])) {
        $item_description = sanitize_input($_POST['item_description']);
        $is_included = isset($_POST['is_included']) ? 1 : 0;
        
        $add_query = "INSERT INTO package_includes_excludes (package_id, item_description, is_included)
                     VALUES (?, ?, ?)";
        $add_stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($add_stmt, "isi", $package_id, $item_description, $is_included);
        
        if (mysqli_stmt_execute($add_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=item_added#includes-excludes-tab");
            exit;
        } else {
            $error_message = "Error adding item: " . mysqli_error($conn);
        }
    }
    
    // Add package highlight
    if (isset($_POST['add_highlight'])) {
        $title = sanitize_input($_POST['highlight_title']);
        $description = sanitize_input($_POST['highlight_description']);
        $icon = sanitize_input($_POST['highlight_icon']);
        
        $add_query = "INSERT INTO package_highlights (package_id, title, description, icon)
                     VALUES (?, ?, ?, ?)";
        $add_stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($add_stmt, "isss", $package_id, $title, $description, $icon);
        
        if (mysqli_stmt_execute($add_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=highlight_added#highlights-tab");
            exit;
        } else {
            $error_message = "Error adding highlight: " . mysqli_error($conn);
        }
    }
    
    // Delete package detail
    if (isset($_POST['delete_detail'])) {
        $detail_id = intval($_POST['detail_id']);
        
        $delete_query = "DELETE FROM package_details WHERE detail_id = ? AND package_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $detail_id, $package_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=detail_deleted#details-tab");
            exit;
        } else {
            $error_message = "Error deleting package detail: " . mysqli_error($conn);
        }
    }
    
    // Delete include/exclude item
    if (isset($_POST['delete_item'])) {
        $item_id = intval($_POST['item_id']);
        
        $delete_query = "DELETE FROM package_includes_excludes WHERE id = ? AND package_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $item_id, $package_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=item_deleted#includes-excludes-tab");
            exit;
        } else {
            $error_message = "Error deleting item: " . mysqli_error($conn);
        }
    }
    
    // Delete highlight
    if (isset($_POST['delete_highlight'])) {
        $highlight_id = intval($_POST['highlight_id']);
        
        $delete_query = "DELETE FROM package_highlights WHERE highlight_id = ? AND package_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $highlight_id, $package_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // Redirect to avoid form resubmission
            header("Location: manage_package_details.php?id=$package_id&success=highlight_deleted#highlights-tab");
            exit;
        } else {
            $error_message = "Error deleting highlight: " . mysqli_error($conn);
        }
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'detail_added':
            $success_message = "Package detail added successfully.";
            break;
        case 'detail_updated':
            $success_message = "Package detail updated successfully.";
            break;
        case 'detail_deleted':
            $success_message = "Package detail deleted successfully.";
            break;
        case 'item_added':
            $success_message = "Include/exclude item added successfully.";
            break;
        case 'item_deleted':
            $success_message = "Include/exclude item deleted successfully.";
            break;
        case 'highlight_added':
            $success_message = "Highlight added successfully.";
            break;
        case 'highlight_deleted':
            $success_message = "Highlight deleted successfully.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Package Details - Adventure Travel.lk Admin</title>
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

        .package-info {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .package-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .package-image {
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
                    <li><a href="admin.php#vehicles-section"><span><i class="fas fa-car"></i> Vehicles</span></a></li>
                    <li><a href="manage_destinations.php"><span><i class="fas fa-map-marker-alt"></i> Manage Destinations</span></a></li>
                    <li><a href="manage_hotels.php"><span><i class="fas fa-hotel"></i> Manage Hotels</span></a></li>
                    <li><a href="manage_policies.php"><span><i class="fas fa-file-alt"></i> Manage Policies</span></a></li>
                    <li><a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Manage Admins</span></a></li>
                    <li><a href="user_messages.php"><span><i class="fas fa-comment-dots"></i> User Messages</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Package Details</h1>
                <a href="admin.php" class="back-link">Back to Dashboard</a>
            </div>
            
            <div class="package-info">
                <h2><?php echo htmlspecialchars($package['name']); ?></h2>
                <div class="package-meta">
                    <div><strong>Type:</strong> <?php echo htmlspecialchars($package['type_name']); ?></div>
                    <div><strong>Duration:</strong> <?php echo htmlspecialchars($package['duration']); ?></div>
                    <div><strong>Price:</strong> $<?php echo number_format($package['price'], 2); ?></div>
                </div>
                <p><?php echo htmlspecialchars(substr($package['description'], 0, 200)) . '...'; ?></p>
                <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" class="package-image">
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
                <div class="tab active" data-tab="details">Itinerary/Details</div>
                <div class="tab" data-tab="includes-excludes">Includes/Excludes</div>
                <div class="tab" data-tab="highlights">Highlights</div>
            </div>
            
            <!-- Package Details Tab -->
            <div class="tab-content active" id="details-tab">
                <div class="card">
                    <h2>
                        <?php if ($is_editing): ?>
                            Edit 
                            <?php if ($package['type_id'] == 1): ?>
                                Itinerary Day
                            <?php elseif ($package['type_id'] == 2): ?>
                                Tour Details
                            <?php else: ?>
                                Tour Plan
                            <?php endif; ?>
                        <?php else: ?>
                            Add 
                            <?php if ($package['type_id'] == 1): ?>
                                Itinerary Day
                            <?php elseif ($package['type_id'] == 2): ?>
                                Tour Details
                            <?php else: ?>
                                Tour Plan
                            <?php endif; ?>
                        <?php endif; ?>
                    </h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($is_editing): ?>
                            <input type="hidden" name="detail_id" value="<?php echo $detail_to_edit['detail_id']; ?>">
                        <?php endif; ?>

                        <?php if ($package['type_id'] == 1): ?>
                            <div class="form-group">
                                <label for="day_number">Day Number</label>
                                <input type="number" id="day_number" name="day_number" class="form-control" min="1" required value="<?php echo $is_editing ? $detail_to_edit['day_number'] : ''; ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" required value="<?php echo $is_editing ? htmlspecialchars($detail_to_edit['title']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo $is_editing ? htmlspecialchars($detail_to_edit['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="destination_id">Destination</label>
                            <select id="destination_id" name="destination_id" class="form-control">
                                <option value="">-- Select Destination --</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?php echo $destination['destination_id']; ?>" <?php echo $is_editing && $detail_to_edit['destination_id'] == $destination['destination_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($destination['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="hotel_id">Hotel/Accommodation</label>
                            <select id="hotel_id" name="hotel_id" class="form-control">
                                <option value="">-- Select Hotel --</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel['hotel_id']; ?>" <?php echo $is_editing && $detail_to_edit['hotel_id'] == $hotel['hotel_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hotel['name']); ?> (<?php echo htmlspecialchars($hotel['destination_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="meal_plan">Meal Plan</label>
                            <input type="text" id="meal_plan" name="meal_plan" class="form-control" placeholder="e.g., Breakfast, Lunch, Dinner" value="<?php echo $is_editing ? htmlspecialchars($detail_to_edit['meal_plan']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="activities">Activities</label>
                            <textarea id="activities" name="activities" class="form-control" rows="3"><?php echo $is_editing ? htmlspecialchars($detail_to_edit['activities']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="transport_type">Transportation</label>
                            <input type="text" id="transport_type" name="transport_type" class="form-control" placeholder="e.g., Private Car, Train, Bus" value="<?php echo $is_editing ? htmlspecialchars($detail_to_edit['transport_type']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_image">
                                <?php if ($is_editing && !empty($detail_to_edit['image'])): ?>
                                    Change Image (Current: <?php echo htmlspecialchars($detail_to_edit['image']); ?>)
                                <?php else: ?>
                                    Additional Image
                                <?php endif; ?>
                            </label>
                            <input type="file" id="detail_image" name="detail_image" class="form-control" accept="image/*">
                            <?php if ($is_editing && !empty($detail_to_edit['image'])): ?>
                                <div style="margin-top: 10px;">
                                    <img src="../images/<?php echo htmlspecialchars($detail_to_edit['image']); ?>" alt="Current Image" style="max-width: 200px; max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_editing): ?>
                            <button type="submit" name="update_detail" class="btn btn-primary">Update Detail</button>
                            <a href="manage_package_details.php?id=<?php echo $package_id; ?>" class="btn" style="background-color: #6c757d; color: #fff; margin-left: 10px;">Cancel Edit</a>
                        <?php else: ?>
                            <button type="submit" name="add_detail" class="btn btn-primary">Add Detail</button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (!empty($package_details)): ?>
                    <h2>Existing Details</h2>
                    <table>
                        <thead>
                            <tr>
                                <?php if ($package['type_id'] == 1): ?>
                                    <th>Day</th>
                                <?php endif; ?>
                                <th>Title</th>
                                <th>Destination</th>
                                <th>Hotel</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($package_details as $detail): ?>
                                <tr>
                                    <?php if ($package['type_id'] == 1): ?>
                                        <td><?php echo $detail['day_number']; ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($detail['title']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['destination_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($detail['hotel_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="manage_package_details.php?id=<?php echo $package_id; ?>&edit_detail=<?php echo $detail['detail_id']; ?>#details-tab" class="btn btn-primary" style="margin-right: 5px;">Edit</a>
                                        <form method="POST" style="display: inline;" class="delete-form">
                                            <input type="hidden" name="detail_id" value="<?php echo $detail['detail_id']; ?>">
                                            <button type="button" class="btn btn-danger delete-btn" data-type="detail">Delete</button>
                                            <input type="submit" name="delete_detail" value="Delete" style="display: none;">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No details added yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Includes/Excludes Tab -->
            <div class="tab-content" id="includes-excludes-tab">
                <div class="card">
                    <h2>Add Include/Exclude Item</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="item_description">Item Description</label>
                            <input type="text" id="item_description" name="item_description" class="form-control" required>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="is_included" name="is_included" class="form-check-input" checked>
                            <label for="is_included" class="form-check-label">This item is included in the package</label>
                        </div>
                        
                        <button type="submit" name="add_include_exclude" class="btn btn-primary">Add Item</button>
                    </form>
                </div>
                
                <div>
                    <?php if (!empty($includes)): ?>
                        <h2>Included Items</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Item Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($includes as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;" class="delete-form">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="button" class="btn btn-danger delete-btn" data-type="item">Delete</button>
                                                <input type="submit" name="delete_item" value="Delete" style="display: none;">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No included items added yet.</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($excludes)): ?>
                        <h2>Excluded Items</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Item Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($excludes as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;" class="delete-form">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="button" class="btn btn-danger delete-btn" data-type="item">Delete</button>
                                                <input type="submit" name="delete_item" value="Delete" style="display: none;">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No excluded items added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Highlights Tab -->
            <div class="tab-content" id="highlights-tab">
                <div class="card">
                    <h2>Add Package Highlight</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="highlight_title">Title</label>
                            <input type="text" id="highlight_title" name="highlight_title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="highlight_description">Description</label>
                            <textarea id="highlight_description" name="highlight_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="highlight_icon">Icon (optional)</label>
                            <input type="text" id="highlight_icon" name="highlight_icon" class="form-control" placeholder="e.g., fa-mountain, fa-car, fa-hotel">
                        </div>
                        
                        <button type="submit" name="add_highlight" class="btn btn-primary">Add Highlight</button>
                    </form>
                </div>
                
                <?php if (!empty($highlights)): ?>
                    <h2>Package Highlights</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($highlights as $highlight): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($highlight['title']); ?></td>
                                    <td><?php echo htmlspecialchars($highlight['description']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" class="delete-form">
                                            <input type="hidden" name="highlight_id" value="<?php echo $highlight['highlight_id']; ?>">
                                            <button type="button" class="btn btn-danger delete-btn" data-type="highlight">Delete</button>
                                            <input type="submit" name="delete_highlight" value="Delete" style="display: none;">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No highlights added yet.</p>
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

            // Delete confirmation modal functionality
            const modal = document.getElementById('deleteModal');
            const closeBtn = document.querySelector('.close');
            const cancelBtn = document.getElementById('cancelDelete');
            const confirmBtn = document.getElementById('confirmDelete');
            let activeForm = null;
            
            // Get all delete buttons and attach event listeners
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Find the closest form to this button
                    activeForm = this.closest('.delete-form');
                    
                    // Set appropriate message based on what is being deleted
                    const deleteMessage = document.getElementById('deleteMessage');
                    const type = this.getAttribute('data-type');
                    
                    if (type === 'detail') {
                        deleteMessage.textContent = 'Are you sure you want to delete this package detail? This action cannot be undone.';
                    } else if (type === 'item') {
                        deleteMessage.textContent = 'Are you sure you want to delete this include/exclude item? This action cannot be undone.';
                    } else if (type === 'highlight') {
                        deleteMessage.textContent = 'Are you sure you want to delete this package highlight? This action cannot be undone.';
                    }
                    
                    // Show the modal
                    modal.style.display = 'block';
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
                if (activeForm) {
                    // Find the hidden submit button and click it
                    const submitBtn = activeForm.querySelector('input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    }
                }
                modal.style.display = 'none';
            });
            
            // Close the modal when clicking outside of it
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 