<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';
require_once 'chat_functions.php';

// Check if admin is logged in
require_admin_login();

// Get unread messages count for admin dashboard
$unread_messages_count = get_unread_count(0, true);

// Get destinations for the dropdown
$destinations_query = "SELECT * FROM destinations ORDER BY name ASC";
$destinations_result = mysqli_query($conn, $destinations_query);
$destinations = [];
if ($destinations_result) {
    while ($destination = mysqli_fetch_assoc($destinations_result)) {
        $destinations[] = $destination;
    }
}

// Handle hotel operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new hotel
    if (isset($_POST['add_hotel'])) {
        $name = sanitize_input($_POST['hotelName']);
        $description = sanitize_input($_POST['hotelDescription']);
        $destination_id = isset($_POST['hotelDestination']) ? intval($_POST['hotelDestination']) : null;
        $star_rating = intval($_POST['hotelRating']);
        $price_per_night = floatval($_POST['hotelPrice']);
        
        // Handle file upload
        $image = 'default-hotel.jpg'; // Default image
        if (isset($_FILES['hotelImage']) && $_FILES['hotelImage']['size'] > 0) {
            $upload_result = upload_file($_FILES['hotelImage']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
            }
        }
        
        $add_query = "INSERT INTO hotels (name, description, destination_id, star_rating, price_per_night, image) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $add_stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($add_stmt, "ssiids", $name, $description, $destination_id, $star_rating, $price_per_night, $image);
        
        if (mysqli_stmt_execute($add_stmt)) {
            header("Location: manage_hotels.php?success=hotel_added");
            exit;
        } else {
            $error_message = "Error adding hotel: " . mysqli_error($conn);
        }
    }
    
    // Update hotel
    if (isset($_POST['update_hotel'])) {
        $hotel_id = intval($_POST['hotel_id']);
        $name = sanitize_input($_POST['editHotelName']);
        $description = sanitize_input($_POST['editHotelDescription']);
        $destination_id = isset($_POST['editHotelDestination']) ? intval($_POST['editHotelDestination']) : null;
        $star_rating = intval($_POST['editHotelRating']);
        $price_per_night = floatval($_POST['editHotelPrice']);
        
        if (isset($_FILES['editHotelImage']) && $_FILES['editHotelImage']['size'] > 0) {
            // Handle file upload for image update
            $upload_result = upload_file($_FILES['editHotelImage']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                $update_query = "UPDATE hotels SET name = ?, description = ?, destination_id = ?, 
                               star_rating = ?, price_per_night = ?, image = ? WHERE hotel_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ssiiisi", $name, $description, $destination_id, 
                                   $star_rating, $price_per_night, $image, $hotel_id);
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            // Update without changing image
            $update_query = "UPDATE hotels SET name = ?, description = ?, destination_id = ?, 
                           star_rating = ?, price_per_night = ? WHERE hotel_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssiidi", $name, $description, $destination_id, 
                               $star_rating, $price_per_night, $hotel_id);
        }
        
        if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
            header("Location: manage_hotels.php?success=hotel_updated");
            exit;
        } else if (!isset($error_message)) {
            $error_message = "Error updating hotel: " . mysqli_error($conn);
        }
    }
    
    // Delete hotel
    if (isset($_POST['delete_hotel'])) {
        $hotel_id = intval($_POST['delete_hotel_id']);
        
        $delete_query = "DELETE FROM hotels WHERE hotel_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $hotel_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_hotels.php?success=hotel_deleted");
            exit;
        } else {
            $error_message = "Error deleting hotel: " . mysqli_error($conn);
        }
    }

    // Add hotel sub-image
    if (isset($_POST['add_sub_image'])) {
        $hotel_id = intval($_POST['hotel_id']);
        $title = sanitize_input($_POST['subImageTitle']);
        $description = sanitize_input($_POST['subImageDescription']);
        
        // Handle file upload
        if (isset($_FILES['subImageFile']) && $_FILES['subImageFile']['size'] > 0) {
            $upload_result = upload_file($_FILES['subImageFile']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                $add_query = "INSERT INTO hotel_sub_images (hotel_id, title, description, image) 
                              VALUES (?, ?, ?, ?)";
                $add_stmt = mysqli_prepare($conn, $add_query);
                mysqli_stmt_bind_param($add_stmt, "isss", $hotel_id, $title, $description, $image);
                
                if (mysqli_stmt_execute($add_stmt)) {
                    header("Location: manage_hotels.php?success=sub_image_added&hotel_id=".$hotel_id);
                    exit;
                } else {
                    $error_message = "Error adding sub image: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            $error_message = "Please select an image to upload.";
        }
    }
    
    // Delete hotel sub-image
    if (isset($_POST['delete_sub_image'])) {
        $sub_image_id = intval($_POST['sub_image_id']);
        $hotel_id = intval($_POST['hotel_id']);
        
        $delete_query = "DELETE FROM hotel_sub_images WHERE sub_image_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $sub_image_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_hotels.php?success=sub_image_deleted&hotel_id=".$hotel_id);
            exit;
        } else {
            $error_message = "Error deleting sub image: " . mysqli_error($conn);
        }
    }
    
    // Update hotel sub-image
    if (isset($_POST['update_sub_image'])) {
        $sub_image_id = intval($_POST['sub_image_id']);
        $hotel_id = intval($_POST['hotel_id']);
        $title = sanitize_input($_POST['editSubImageTitle']);
        $description = sanitize_input($_POST['editSubImageDescription']);
        
        // Handle file upload for image update
        if (isset($_FILES['editSubImageFile']) && $_FILES['editSubImageFile']['size'] > 0) {
            $upload_result = upload_file($_FILES['editSubImageFile']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                // Get the old image to delete it later
                $old_image_query = "SELECT image FROM hotel_sub_images WHERE sub_image_id = ?";
                $old_image_stmt = mysqli_prepare($conn, $old_image_query);
                mysqli_stmt_bind_param($old_image_stmt, "i", $sub_image_id);
                mysqli_stmt_execute($old_image_stmt);
                $old_image_result = mysqli_stmt_get_result($old_image_stmt);
                $old_image = mysqli_fetch_assoc($old_image_result)['image'];
                
                $update_query = "UPDATE hotel_sub_images SET title = ?, description = ?, image = ? WHERE sub_image_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "sssi", $title, $description, $image, $sub_image_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Delete old image file if it exists
                    if (!empty($old_image)) {
                        $old_image_path = '../images/' . $old_image;
                        if (file_exists($old_image_path) && $old_image != 'default-hotel.jpg') {
                            @unlink($old_image_path);
                        }
                    }
                    
                    header("Location: manage_hotels.php?success=sub_image_updated&hotel_id=".$hotel_id);
                    exit;
                } else {
                    $error_message = "Error updating sub image: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            // Update without changing image
            $update_query = "UPDATE hotel_sub_images SET title = ?, description = ? WHERE sub_image_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssi", $title, $description, $sub_image_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                header("Location: manage_hotels.php?success=sub_image_updated&hotel_id=".$hotel_id);
                exit;
            } else {
                $error_message = "Error updating sub image: " . mysqli_error($conn);
            }
        }
    }
    
    // Add hotel sub-detail
    if (isset($_POST['add_sub_detail'])) {
        $hotel_id = intval($_POST['hotel_id']);
        $header = sanitize_input($_POST['detailHeader']);
        $content = sanitize_input($_POST['detailContent']);
        $icon = sanitize_input($_POST['detailIcon']);
        $order_num = intval($_POST['detailOrder']);
        
        $add_query = "INSERT INTO hotel_sub_details (hotel_id, header, content, icon, order_num) 
                      VALUES (?, ?, ?, ?, ?)";
        $add_stmt = mysqli_prepare($conn, $add_query);
        mysqli_stmt_bind_param($add_stmt, "isssi", $hotel_id, $header, $content, $icon, $order_num);
        
        if (mysqli_stmt_execute($add_stmt)) {
            header("Location: manage_hotels.php?success=sub_detail_added&hotel_id=".$hotel_id);
            exit;
        } else {
            $error_message = "Error adding sub detail: " . mysqli_error($conn);
        }
    }
    
    // Update hotel sub-detail
    if (isset($_POST['update_sub_detail'])) {
        $detail_id = intval($_POST['detail_id']);
        $hotel_id = intval($_POST['hotel_id']);
        $header = sanitize_input($_POST['editDetailHeader']);
        $content = sanitize_input($_POST['editDetailContent']);
        $icon = sanitize_input($_POST['editDetailIcon']);
        $order_num = intval($_POST['editDetailOrder']);
        
        $update_query = "UPDATE hotel_sub_details SET header = ?, content = ?, icon = ?, order_num = ? 
                        WHERE detail_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssii", $header, $content, $icon, $order_num, $detail_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: manage_hotels.php?success=sub_detail_updated&hotel_id=".$hotel_id);
            exit;
        } else {
            $error_message = "Error updating sub detail: " . mysqli_error($conn);
        }
    }
    
    // Delete hotel sub-detail
    if (isset($_POST['delete_sub_detail'])) {
        $detail_id = intval($_POST['detail_id']);
        $hotel_id = intval($_POST['hotel_id']);
        
        $delete_query = "DELETE FROM hotel_sub_details WHERE detail_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $detail_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_hotels.php?success=sub_detail_deleted&hotel_id=".$hotel_id);
            exit;
        } else {
            $error_message = "Error deleting sub detail: " . mysqli_error($conn);
        }
    }
}

// Get all hotels with destination names
$hotels_query = "SELECT h.*, d.name as destination_name 
                FROM hotels h 
                LEFT JOIN destinations d ON h.destination_id = d.destination_id";

// Check if search term is provided
$search_term = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = sanitize_input($_GET['search']);
    $hotels_query .= " WHERE h.name LIKE ?";
}

$hotels_query .= " ORDER BY h.name ASC";
$hotels = [];

// Prepare and execute the query with or without search term
if (!empty($search_term)) {
    $search_param = "%$search_term%";
    $stmt = mysqli_prepare($conn, $hotels_query);
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $hotels_result = mysqli_stmt_get_result($stmt);
} else {
    $hotels_result = mysqli_query($conn, $hotels_query);
}

if ($hotels_result) {
    while ($hotel = mysqli_fetch_assoc($hotels_result)) {
        $hotels[] = $hotel;
    }
}

// Get sub-images and sub-details for a specific hotel if hotel_id is in GET
$selected_hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : null;
$sub_images = [];
$sub_details = [];

if ($selected_hotel_id) {
    // Get sub-images
    $sub_images_query = "SELECT * FROM hotel_sub_images WHERE hotel_id = ? ORDER BY created_at DESC";
    $sub_images_stmt = mysqli_prepare($conn, $sub_images_query);
    mysqli_stmt_bind_param($sub_images_stmt, "i", $selected_hotel_id);
    mysqli_stmt_execute($sub_images_stmt);
    $sub_images_result = mysqli_stmt_get_result($sub_images_stmt);
    while ($sub_image = mysqli_fetch_assoc($sub_images_result)) {
        $sub_images[] = $sub_image;
    }
    
    // Get sub-details
    $sub_details_query = "SELECT * FROM hotel_sub_details WHERE hotel_id = ? ORDER BY order_num ASC";
    $sub_details_stmt = mysqli_prepare($conn, $sub_details_query);
    mysqli_stmt_bind_param($sub_details_stmt, "i", $selected_hotel_id);
    mysqli_stmt_execute($sub_details_stmt);
    $sub_details_result = mysqli_stmt_get_result($sub_details_stmt);
    while ($sub_detail = mysqli_fetch_assoc($sub_details_result)) {
        $sub_details[] = $sub_detail;
    }
    
    // Get the selected hotel details
    $selected_hotel = null;
    foreach ($hotels as $hotel) {
        if ($hotel['hotel_id'] == $selected_hotel_id) {
            $selected_hotel = $hotel;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotels - Adventure Travel.lk</title>
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

        .hotel-section {
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

        .hotel-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .hotel-card {
            background-color:rgb(123, 255, 222);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .hotel-image {
            height: 180px;
            overflow: hidden;
        }

        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hotel-details {
            padding: 15px;
        }

        .hotel-rating {
            color: gold;
            margin-bottom: 5px;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .edit-btn, .delete-btn {
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

        .edit-btn:hover {
            background-color: #ec971f;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: #fff;
        }

        .delete-btn:hover {
            background-color: #bd2130;
        }

        .feature-btn {
            background-color: var(--primary-color);
            color: #fff;
            margin-right: 5px;
        }

        .feature-btn:hover {
            background-color: #134d47;
        }

        /* Sub-images and Sub-details Styles */
        .sub-section {
            margin-top: 30px;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .sub-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .sub-section-title {
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .sub-section-title i {
            margin-right: 10px;
        }

        .sub-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .sub-image-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .sub-image-card:hover {
            transform: translateY(-5px);
        }

        .sub-image {
            height: 150px;
            overflow: hidden;
        }

        .sub-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sub-image-details {
            padding: 10px 15px;
            flex-grow: 1;
        }

        .sub-image-details h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .sub-image-details p {
            margin: 0;
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        .sub-image-actions {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }
        
        .sub-image-actions button {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .sub-details-list {
            width: 100%;
        }

        .sub-detail-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .sub-detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .sub-detail-icon {
            flex: 0 0 40px;
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 15px;
            text-align: center;
        }

        .sub-detail-content {
            flex: 1;
        }

        .sub-detail-content h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .sub-detail-content p {
            margin: 0;
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        .sub-detail-actions {
            margin-left: 15px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .back-btn i {
            margin-right: 5px;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .no-items {
            padding: 20px;
            text-align: center;
            color: #666;
            background-color: #f9f9f9;
            border-radius: 5px;
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

        .add-btn:hover, .form-submit:hover {
            background-color: #124d47;
        }

        /* Success Message Styles */
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            animation: slideIn 0.5s ease-out forwards, fadeOut 0.5s ease-out 3.5s forwards;
            display: flex;
            align-items: center;
        }

        .success-message i {
            margin-right: 10px;
            font-size: 20px;
        }

        @keyframes slideIn {
            0% { transform: translateX(100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['success'])): ?>
    <div class="success-message" id="successMessage">
        <i class="fas fa-check-circle"></i>
        <span id="successText">
            <?php
            $success_message = '';
            switch ($_GET['success']) {
                case 'hotel_added':
                    echo 'Hotel added successfully!';
                    break;
                case 'hotel_updated':
                    echo 'Hotel updated successfully!';
                    break;
                case 'hotel_deleted':
                    echo 'Hotel deleted successfully!';
                    break;
                case 'sub_image_added':
                    echo 'Sub-image added successfully!';
                    break;
                case 'sub_image_updated':
                    echo 'Sub-image updated successfully!';
                    break;
                case 'sub_image_deleted':
                    echo 'Sub-image deleted successfully!';
                    break;
                case 'sub_detail_added':
                    echo 'Sub-detail added successfully!';
                    break;
                case 'sub_detail_updated':
                    echo 'Sub-detail updated successfully!';
                    break;
                case 'sub_detail_deleted':
                    echo 'Sub-detail deleted successfully!';
                    break;
                default:
                    echo 'Operation completed successfully!';
            }
            ?>
        </span>
    </div>
    <?php endif; ?>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo-4.png" alt="Adventure Travel.lk Logo">
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="admin.php"><span><i class="fas fa-home"></i> Dashboard</span></a></li>
                    <li><a href="manage_destinations.php"><span><i class="fas fa-map-marker-alt"></i> Manage Destinations</span></a></li>
                    <li><a href="manage_hotels.php" class="active"><span><i class="fas fa-hotel"></i> Manage Hotels</span></a></li>
                    <li><a href="manage_policies.php"><span><i class="fas fa-file-alt"></i> Manage Policies</span></a></li>
                    <li><a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Manage Admins</span></a></li>
                    <li><a href="user_messages.php" style="color: #fff; background-color:rgb(0, 0, 0);"><span><i class="fas fa-comment-dots"></i> User Messages</span>
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="badge" style="background-color: white; color: #dc3545; padding: 2px 6px; border-radius: 50%;"><?php echo $unread_messages_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Hotels</h1>
                <a href="admin.php" class="add-btn">Back to Dashboard</a>
            </div>

            <?php if ($selected_hotel_id && $selected_hotel): ?>
                <!-- Hotel Sub-Images and Sub-Details Management -->
                <div class="header" style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center;">
                        <a href="manage_hotels.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Hotels</a>
                        <h2>Managing: <?php echo htmlspecialchars($selected_hotel['name']); ?></h2>
                    </div>
                    
                    <div>
                        <button type="button" class="add-btn" onclick="showModal('addSubImageModal')">Add Sub-Image</button>
                        <button type="button" class="add-btn" onclick="showModal('addSubDetailModal')">Add Sub-Detail</button>
                    </div>
                </div>

                <!-- Sub-Images Section -->
                <div class="sub-section">
                    <div class="sub-section-header">
                        <h3 class="sub-section-title"><i class="fas fa-images"></i> Hotel Sub-Images</h3>
                    </div>
                    
                    <?php if (count($sub_images) > 0): ?>
                        <div class="sub-images-grid">
                            <?php foreach ($sub_images as $sub_image): ?>
                                <div class="sub-image-card">
                                    <div class="sub-image">
                                        <img src="../images/<?php echo htmlspecialchars($sub_image['image']); ?>" alt="<?php echo htmlspecialchars($sub_image['title']); ?>">
                                    </div>
                                    <div class="sub-image-details">
                                        <h4><?php echo htmlspecialchars($sub_image['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($sub_image['description']); ?></p>
                                    </div>
                                    <div class="sub-image-actions">
                                        <button type="button" class="edit-btn" onclick="handleClick('edit_sub_image', <?php echo $sub_image['sub_image_id']; ?>, '<?php echo htmlspecialchars($sub_image['title']); ?>')">Edit</button>
                                        <button type="button" class="delete-btn" onclick="handleClick('delete_sub_image', <?php echo $sub_image['sub_image_id']; ?>, '<?php echo htmlspecialchars($sub_image['title']); ?>')">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-items">
                            <p>No sub-images found. Add a new sub-image using the "Add Sub-Image" button.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sub-Details Section -->
                <div class="sub-section">
                    <div class="sub-section-header">
                        <h3 class="sub-section-title"><i class="fas fa-list-alt"></i> Hotel Sub-Details</h3>
                    </div>
                    
                    <?php if (count($sub_details) > 0): ?>
                        <div class="sub-details-list">
                            <?php foreach ($sub_details as $sub_detail): ?>
                                <div class="sub-detail-item">
                                    <div class="sub-detail-icon">
                                        <i class="<?php echo htmlspecialchars($sub_detail['icon']); ?>"></i>
                                    </div>
                                    <div class="sub-detail-content">
                                        <h4><?php echo htmlspecialchars($sub_detail['header']); ?></h4>
                                        <p><?php echo htmlspecialchars($sub_detail['content']); ?></p>
                                    </div>
                                    <div class="sub-detail-actions">
                                        <button type="button" class="edit-btn" onclick="handleClick('edit_sub_detail', <?php echo $sub_detail['detail_id']; ?>, '<?php echo htmlspecialchars($sub_detail['header']); ?>')">Edit</button>
                                        <button type="button" class="delete-btn" onclick="handleClick('delete_sub_detail', <?php echo $sub_detail['detail_id']; ?>, '<?php echo htmlspecialchars($sub_detail['header']); ?>')">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-items">
                            <p>No sub-details found. Add a new sub-detail using the "Add Sub-Detail" button.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Hotels Section -->
                <div class="hotel-section">
                    <div class="section-header">
                        <h2>Hotels</h2>
                        <div style="display: flex; gap: 10px;">
                            <!-- Hotel Search Form -->
                            <form method="GET" action="" style="display: flex; align-items: center; margin-right: 10px;">
                                <input type="text" name="search" placeholder="Search hotel name..." class="form-control" style="height: 38px;" value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                                <button type="submit" class="add-btn" style="margin-left: 5px; height: 38px;"> Search
                                </button>
                                <?php if (!empty($search_term)): ?>
                                <a href="manage_hotels.php" class="add-btn" style="margin-left: 5px; height: 38px; display: inline-flex; align-items: center;"> Clear
                                </a>
                                <?php endif; ?>
                            </form>
                            <button type="button" class="add-btn" onclick="showModal('addHotelModal')">Add New Hotel</button>
                        </div>
                    </div>

                    <div class="hotel-cards">
                        <?php if (count($hotels) > 0): ?>
                            <?php foreach ($hotels as $hotel): ?>
                                <div class="hotel-card">
                                    <div class="hotel-image">
                                        <img src="../images/<?php echo htmlspecialchars($hotel['image']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                                    </div>
                                    <div class="hotel-details">
                                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                        <div class="hotel-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $hotel['star_rating']): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <p><strong>Location:</strong> <?php echo htmlspecialchars($hotel['destination_name'] ?? 'Not specified'); ?></p>
                                        <p><strong>Price:</strong> $<?php echo number_format($hotel['price_per_night'], 2); ?> per night</p>
                                        <p><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)) . (strlen($hotel['description']) > 100 ? '...' : ''); ?></p>
                                        <div class="card-actions">
                                            <a href="manage_hotels.php?hotel_id=<?php echo $hotel['hotel_id']; ?>" class="feature-btn edit-btn">Manage Features</a>
                                            <button type="button" class="edit-btn" onclick="handleClick('edit_hotel', <?php echo $hotel['hotel_id']; ?>, '<?php echo htmlspecialchars($hotel['name']); ?>')">Edit</button>
                                            <button type="button" class="delete-btn" onclick="handleClick('delete_hotel', <?php echo $hotel['hotel_id']; ?>, '<?php echo htmlspecialchars($hotel['name']); ?>')">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php echo !empty($search_term) ? "No hotels found matching '".$search_term."'. Try a different search term or " : "No hotels found. "; ?>Add your first hotel using the "Add New Hotel" button.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Hotel Modal -->
    <div id="addHotelModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Hotel</h2>
                <span class="close-btn" onclick="hideModal('addHotelModal')">&times;</span>
            </div>
            <form id="hotelForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="hotelName">Hotel Name</label>
                    <input type="text" id="hotelName" name="hotelName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="hotelDescription">Description</label>
                    <textarea id="hotelDescription" name="hotelDescription" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="hotelDestination">Destination</label>
                    <select id="hotelDestination" name="hotelDestination" class="form-control" required>
                        <option value="">Select a destination</option>
                        <?php foreach ($destinations as $destination): ?>
                            <option value="<?php echo $destination['destination_id']; ?>"><?php echo htmlspecialchars($destination['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hotelRating">Star Rating</label>
                    <select id="hotelRating" name="hotelRating" class="form-control" required>
                        <option value="1">1 Star</option>
                        <option value="2">2 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hotelPrice">Price per Night ($)</label>
                    <input type="number" id="hotelPrice" name="hotelPrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="hotelImage">Image</label>
                    <input type="file" id="hotelImage" name="hotelImage" class="form-control" accept="image/*" required>
                </div>
                <input type="hidden" name="add_hotel" value="1">
                <button type="submit" class="form-submit">Add Hotel</button>
            </form>
        </div>
    </div>

    <!-- Edit Hotel Modal -->
    <div id="editHotelModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Hotel</h2>
                <span class="close-btn" onclick="hideModal('editHotelModal')">&times;</span>
            </div>
            <form id="editHotelForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="hotel_id" name="hotel_id">
                <div class="form-group">
                    <label for="editHotelName">Hotel Name</label>
                    <input type="text" id="editHotelName" name="editHotelName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editHotelDescription">Description</label>
                    <textarea id="editHotelDescription" name="editHotelDescription" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editHotelDestination">Destination</label>
                    <select id="editHotelDestination" name="editHotelDestination" class="form-control" required>
                        <option value="">Select a destination</option>
                        <?php foreach ($destinations as $destination): ?>
                            <option value="<?php echo $destination['destination_id']; ?>"><?php echo htmlspecialchars($destination['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editHotelRating">Star Rating</label>
                    <select id="editHotelRating" name="editHotelRating" class="form-control" required>
                        <option value="1">1 Star</option>
                        <option value="2">2 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editHotelPrice">Price per Night ($)</label>
                    <input type="number" id="editHotelPrice" name="editHotelPrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editHotelImage">Image (Leave empty to keep current image)</label>
                    <input type="file" id="editHotelImage" name="editHotelImage" class="form-control" accept="image/*">
                    <div id="currentHotelImage" style="margin-top: 10px; display: none;">
                        <p>Current Image:</p>
                        <img id="hotelImagePreview" src="" alt="Current Hotel Image" style="max-width: 200px; max-height: 150px;">
                    </div>
                </div>
                <input type="hidden" name="update_hotel" value="1">
                <button type="submit" class="form-submit">Update Hotel</button>
            </form>
        </div>
    </div>

    <!-- Delete Hotel Confirmation Modal -->
    <div id="deleteHotelModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="hideModal('deleteHotelModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the hotel: <span id="deleteHotelName"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn" style="background: #ccc; margin-right: 10px;" onclick="hideModal('deleteHotelModal')">Cancel</button>
                <form id="deleteHotelForm" method="POST">
                    <input type="hidden" id="deleteHotelId" name="delete_hotel_id">
                    <input type="hidden" name="delete_hotel" value="1">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Sub-Image Modal -->
    <div id="addSubImageModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Sub-Image</h2>
                <span class="close-btn" onclick="hideModal('addSubImageModal')">&times;</span>
            </div>
            <form id="subImageForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="hotel_id" value="<?php echo $selected_hotel_id; ?>">
                <div class="form-group">
                    <label for="subImageTitle">Title</label>
                    <input type="text" id="subImageTitle" name="subImageTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subImageDescription">Description</label>
                    <textarea id="subImageDescription" name="subImageDescription" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label for="subImageFile">Image</label>
                    <input type="file" id="subImageFile" name="subImageFile" class="form-control" accept="image/*" required>
                </div>
                <input type="hidden" name="add_sub_image" value="1">
                <button type="submit" class="form-submit">Add Sub-Image</button>
            </form>
        </div>
    </div>

    <!-- Edit Sub-Image Modal -->
    <div id="editSubImageModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Sub-Image</h2>
                <span class="close-btn" onclick="hideModal('editSubImageModal')">&times;</span>
            </div>
            <form id="editSubImageForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="hotel_id" value="<?php echo $selected_hotel_id; ?>">
                <input type="hidden" id="editSubImageId" name="sub_image_id">
                <div class="form-group">
                    <label for="editSubImageTitle">Title</label>
                    <input type="text" id="editSubImageTitle" name="editSubImageTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editSubImageDescription">Description</label>
                    <textarea id="editSubImageDescription" name="editSubImageDescription" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Current Image</label>
                    <div>
                        <img id="currentSubImage" src="" alt="Current Sub Image" style="max-height: 150px; margin: 10px 0;">
                    </div>
                    <div style="margin-bottom: 10px; color: #6c757d; font-size: 0.875rem;">Leave empty to keep current image</div>
                    <input type="file" id="editSubImageFile" name="editSubImageFile" class="form-control" accept="image/*">
                </div>
                <input type="hidden" name="update_sub_image" value="1">
                <button type="submit" class="form-submit">Update Sub-Image</button>
            </form>
        </div>
    </div>

    <!-- Delete Sub-Image Confirmation Modal -->
    <div id="deleteSubImageModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="hideModal('deleteSubImageModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the sub-image: <span id="deleteSubImageTitle"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn" style="background: #ccc; margin-right: 10px;" onclick="hideModal('deleteSubImageModal')">Cancel</button>
                <form id="deleteSubImageForm" method="POST">
                    <input type="hidden" id="deleteSubImageId" name="sub_image_id">
                    <input type="hidden" name="hotel_id" value="<?php echo $selected_hotel_id; ?>">
                    <input type="hidden" name="delete_sub_image" value="1">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Sub-Detail Modal -->
    <div id="addSubDetailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Sub-Detail</h2>
                <span class="close-btn" onclick="hideModal('addSubDetailModal')">&times;</span>
            </div>
            <form id="subDetailForm" method="POST">
                <input type="hidden" name="hotel_id" value="<?php echo $selected_hotel_id; ?>">
                <div class="form-group">
                    <label for="detailHeader">Header</label>
                    <input type="text" id="detailHeader" name="detailHeader" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="detailContent">Content</label>
                    <textarea id="detailContent" name="detailContent" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="detailIcon">Icon Class (FontAwesome)</label>
                    <select id="detailIcon" name="detailIcon" class="form-control" required>
                        <option value="fas fa-bed">Bed (&#xf236;)</option>
                        <option value="fas fa-wifi">WiFi (&#xf1eb;)</option>
                        <option value="fas fa-utensils">Restaurant (&#xf2e7;)</option>
                        <option value="fas fa-swimming-pool">Swimming Pool (&#xf5c5;)</option>
                        <option value="fas fa-dumbbell">Gym (&#xf44b;)</option>
                        <option value="fas fa-concierge-bell">Room Service (&#xf562;)</option>
                        <option value="fas fa-parking">Parking (&#xf540;)</option>
                        <option value="fas fa-cocktail">Bar (&#xf561;)</option>
                        <option value="fas fa-spa">Spa (&#xf5bb;)</option>
                        <option value="fas fa-coffee">Coffee (&#xf0f4;)</option>
                        <option value="fas fa-shower">Shower (&#xf2cc;)</option>
                        <option value="fas fa-snowflake">Air Conditioning (&#xf2dc;)</option>
                        <option value="fas fa-tv">TV (&#xf26c;)</option>
                        <option value="fas fa-phone">Phone (&#xf095;)</option>
                        <option value="fas fa-car">Transport (&#xf1b9;)</option>
                        <option value="fas fa-helicopter">Helipad (&#xf533;)</option>
                        <option value="fas fa-tshirt">Laundry (&#xf553;)</option>
                        <option value="fas fa-praying-hands">Yoga (&#xf684;)</option>
                        <option value="fas fa-user-md">Doctor (&#xf0f0;)</option>
                        <option value="fas fa-car-side">Rides for rent (&#xf5e4;)</option>
                        <option value="fas fa-wheelchair">Accessible (&#xf193;)</option>
                        <option value="fas fa-shopping-bag">Shopping (&#xf290;)</option>
                        <option value="fas fa-leaf">Ayurvedic Centre (&#xf06c;)</option>
                    </select>
                    <small>These are common hotel amenity icons from FontAwesome.</small>
                </div>
                <div class="form-group">
                    <label for="detailOrder">Order Number</label>
                    <input type="number" id="detailOrder" name="detailOrder" class="form-control" value="0" min="0">
                </div>
                <input type="hidden" name="add_sub_detail" value="1">
                <button type="submit" class="form-submit">Add Sub-Detail</button>
            </form>
        </div>
    </div>

    <!-- Edit Sub-Detail Modal -->
    <div id="editSubDetailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Sub-Detail</h2>
                <span class="close-btn" onclick="hideModal('editSubDetailModal')">&times;</span>
            </div>
            <form id="editSubDetailForm" method="POST">
                <input type="hidden" id="detail_id" name="detail_id">
                <input type="hidden" name="hotel_id" value="<?php echo $selected_hotel_id; ?>">
                <div class="form-group">
                    <label for="editDetailHeader">Header</label>
                    <input type="text" id="editDetailHeader" name="editDetailHeader" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editDetailContent">Content</label>
                    <textarea id="editDetailContent" name="editDetailContent" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editDetailIcon">Icon Class (FontAwesome)</label>
                    <select id="editDetailIcon" name="editDetailIcon" class="form-control" required>
                        <option value="fas fa-bed">Bed (&#xf236;)</option>
                        <option value="fas fa-wifi">WiFi (&#xf1eb;)</option>
                        <option value="fas fa-utensils">Restaurant (&#xf2e7;)</option>
                        <option value="fas fa-swimming-pool">Swimming Pool (&#xf5c5;)</option>
                        <option value="fas fa-dumbbell">Gym (&#xf44b;)</option>
                        <option value="fas fa-concierge-bell">Room Service (&#xf562;)</option>
                        <option value="fas fa-parking">Parking (&#xf540;)</option>
                        <option value="fas fa-cocktail">Bar (&#xf561;)</option>
                        <option value="fas fa-spa">Spa (&#xf5bb;)</option>
                        <option value="fas fa-coffee">Coffee (&#xf0f4;)</option>
                        <option value="fas fa-shower">Shower (&#xf2cc;)</option>
                        <option value="fas fa-snowflake">Air Conditioning (&#xf2dc;)</option>
                        <option value="fas fa-tv">TV (&#xf26c;)</option>
                        <option value="fas fa-phone">Phone (&#xf095;)</option>
                        <option value="fas fa-car">Transport (&#xf1b9;)</option>
                        <option value="fas fa-helicopter">Helipad (&#xf533;)</option>
                        <option value="fas fa-tshirt">Laundry (&#xf553;)</option>
                        <option value="fas fa-praying-hands">Yoga (&#xf684;)</option>
                        <option value="fas fa-user-md">Doctor (&#xf0f0;)</option>
                        <option value="fas fa-car-side">Rides for rent (&#xf5e4;)</option>
                        <option value="fas fa-wheelchair">Accessible (&#xf193;)</option>
                        <option value="fas fa-shopping-bag">Shopping (&#xf290;)</option>
                        <option value="fas fa-leaf">Ayurvedic Centre (&#xf06c;)</option>
                    </select>
                    <small>These are common hotel amenity icons from FontAwesome.</small>
                </div>
                <div class="form-group">
                    <label for="editDetailOrder">Order Number</label>
                    <input type="number" id="editDetailOrder" name="editDetailOrder" class="form-control" value="0" min="0">
                </div>
                <input type="hidden" name="update_sub_detail" value="1">
                <button type="submit" class="form-submit">Update Sub-Detail</button>
            </form>
        </div>
    </div>

    <!-- Delete Sub-Detail Confirmation Modal -->
    <div id="deleteSubDetailModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="hideModal('deleteSubDetailModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the sub-detail: <span id="deleteSubDetailHeader"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn" style="background: #ccc; margin-right: 10px;" onclick="hideModal('deleteSubDetailModal')">Cancel</button>
                <form id="deleteSubDetailForm" method="POST">
                    <input type="hidden" id="deleteSubDetailId" name="detail_id">
                    <input type="hidden" name="hotel_id" value="<?php echo $selected_hotel_id; ?>">
                    <input type="hidden" name="delete_sub_detail" value="1">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showModal(id) {
            document.getElementById(id).style.display = 'block';
        }
        
        function hideModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        function handleClick(action, id, name) {
            if (action === 'edit_hotel') {
                // Set values in the edit form
                <?php if (!empty($hotels)): ?>
                <?php foreach ($hotels as $hotel): ?>
                if (id == <?php echo $hotel['hotel_id']; ?>) {
                    document.getElementById('hotel_id').value = '<?php echo $hotel['hotel_id']; ?>';
                    document.getElementById('editHotelName').value = '<?php echo addslashes($hotel['name']); ?>';
                    document.getElementById('editHotelDescription').value = '<?php echo addslashes($hotel['description']); ?>';
                    document.getElementById('editHotelDestination').value = '<?php echo $hotel['destination_id'] ? $hotel['destination_id'] : ''; ?>';
                    document.getElementById('editHotelRating').value = '<?php echo $hotel['star_rating']; ?>';
                    document.getElementById('editHotelPrice').value = '<?php echo $hotel['price_per_night']; ?>';
                    
                    <?php if (!empty($hotel['image'])): ?>
                    document.getElementById('currentHotelImage').style.display = 'block';
                    document.getElementById('hotelImagePreview').src = '../images/<?php echo $hotel['image']; ?>';
                    <?php else: ?>
                    document.getElementById('currentHotelImage').style.display = 'none';
                    <?php endif; ?>
                    
                    showModal('editHotelModal');
                }
                <?php endforeach; ?>
                <?php endif; ?>
            } 
            else if (action === 'delete_hotel') {
                document.getElementById('deleteHotelId').value = id;
                document.getElementById('deleteHotelName').innerHTML = name;
                showModal('deleteHotelModal');
            }
            else if (action === 'delete_sub_image') {
                document.getElementById('deleteSubImageId').value = id;
                document.getElementById('deleteSubImageTitle').innerHTML = name;
                showModal('deleteSubImageModal');
            }
            else if (action === 'edit_sub_detail') {
                <?php if (!empty($sub_details)): ?>
                <?php foreach ($sub_details as $detail): ?>
                if (id == <?php echo $detail['detail_id']; ?>) {
                    document.getElementById('detail_id').value = '<?php echo $detail['detail_id']; ?>';
                    document.getElementById('editDetailHeader').value = '<?php echo addslashes($detail['header']); ?>';
                    document.getElementById('editDetailContent').value = '<?php echo addslashes($detail['content']); ?>';
                    
                    // Set icon
                    var iconSelect = document.getElementById('editDetailIcon');
                    var iconFound = false;
                    for (var i = 0; i < iconSelect.options.length; i++) {
                        if (iconSelect.options[i].value === '<?php echo $detail['icon']; ?>') {
                            iconSelect.selectedIndex = i;
                            iconFound = true;
                            break;
                        }
                    }
                    
                    // Add option if not found
                    if (!iconFound) {
                        var option = new Option('<?php echo $detail['icon']; ?>', '<?php echo $detail['icon']; ?>');
                        iconSelect.add(option);
                        iconSelect.value = '<?php echo $detail['icon']; ?>';
                    }
                    
                    document.getElementById('editDetailOrder').value = '<?php echo $detail['order_num']; ?>';
                    showModal('editSubDetailModal');
                }
                <?php endforeach; ?>
                <?php endif; ?>
            }
            else if (action === 'delete_sub_detail') {
                document.getElementById('deleteSubDetailId').value = id;
                document.getElementById('deleteSubDetailHeader').innerHTML = name;
                showModal('deleteSubDetailModal');
            }
            else if (action === 'edit_sub_image') {
                <?php if (!empty($sub_images)): ?>
                <?php foreach ($sub_images as $sub_image): ?>
                if (id == <?php echo $sub_image['sub_image_id']; ?>) {
                    document.getElementById('editSubImageId').value = '<?php echo $sub_image['sub_image_id']; ?>';
                    document.getElementById('editSubImageTitle').value = '<?php echo addslashes($sub_image['title']); ?>';
                    document.getElementById('editSubImageDescription').value = '<?php echo addslashes($sub_image['description']); ?>';
                    document.getElementById('currentSubImage').src = '../images/<?php echo $sub_image['image']; ?>';
                    showModal('editSubImageModal');
                }
                <?php endforeach; ?>
                <?php endif; ?>
            }
        }
        
        // Auto-hide success message after 4 seconds
        window.onload = function() {
            setTimeout(function() {
                var successMessage = document.getElementById('successMessage');
                if (successMessage) {
                    successMessage.style.display = 'none';
                }
            }, 4000);
            
            // Handle closing modals when clicking outside
            var modals = document.getElementsByClassName('modal');
            for (var i = 0; i < modals.length; i++) {
                modals[i].addEventListener('click', function(event) {
                    if (event.target === this) {
                        this.style.display = 'none';
                    }
                });
            }
        }
    </script>
</body>
</html> 