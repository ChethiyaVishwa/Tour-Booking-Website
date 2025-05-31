<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';
require_once 'chat_functions.php'; // Add the chat functions include

// Check if admin is logged in
require_admin_login();

// Check if team_members table exists, create if not
function create_team_members_table_if_not_exists() {
    global $conn;
    $check_table = "SHOW TABLES LIKE 'team_members'";
    $table_exists = mysqli_query($conn, $check_table);
    
    if (mysqli_num_rows($table_exists) == 0) {
        $create_table = "CREATE TABLE team_members (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            position VARCHAR(255) NOT NULL,
            bio TEXT NOT NULL DEFAULT 'Experienced travel professional with a passion for creating unforgettable adventures. Expert in Sri Lanka tourism and committed to exceptional customer service.',
            image VARCHAR(255) NOT NULL,
            facebook VARCHAR(255),
            twitter VARCHAR(255),
            instagram VARCHAR(255),
            linkedin VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (mysqli_query($conn, $create_table)) {
            return true;
        } else {
            return false;
        }
    }
    return true;
}

// Initialize team_members table
create_team_members_table_if_not_exists();

// Check if reviews table exists, create if not
function create_reviews_table_if_not_exists() {
    global $conn;
    $check_table = "SHOW TABLES LIKE 'reviews'";
    $table_exists = mysqli_query($conn, $check_table);
    
    if (mysqli_num_rows($table_exists) == 0) {
        $create_table = "CREATE TABLE reviews (
            review_id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            tour_type VARCHAR(100) NOT NULL,
            rating INT(1) NOT NULL,
            review_text TEXT NOT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (review_id),
            KEY status_index (status),
            KEY rating_index (rating)
        )";
        
        if (mysqli_query($conn, $create_table)) {
            return true;
        } else {
            return false;
        }
    }
    return true;
}

// Initialize reviews table
create_reviews_table_if_not_exists();

// Get unread messages count for admin dashboard
$unread_messages_count = get_unread_count(0, true);

// Get unreadContact Messages count
$contact_messages_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
$contact_result = mysqli_query($conn, $contact_messages_query);
$unread_contact_count = 0;
if ($contact_result && $row = mysqli_fetch_assoc($contact_result)) {
    $unread_contact_count = $row['count'];
}

// Check if destination_names column exists in package_details table
function check_destination_column_exists() {
    global $conn;
    $check_column = "SHOW COLUMNS FROM package_details LIKE 'destination_names'";
    $result = mysqli_query($conn, $check_column);
    return mysqli_num_rows($result) > 0;
}

// Function to add package details with destination safely
function add_package_detail_with_destination($package_id, $destinations) {
    global $conn;
    
    if (check_destination_column_exists()) {
        // Column exists, use it
        $detail_query = "INSERT INTO package_details (package_id, destination_names) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $detail_query);
        mysqli_stmt_bind_param($stmt, "is", $package_id, $destinations);
        return mysqli_stmt_execute($stmt);
    } else {
        // Column doesn't exist, use a basic insert without destination
        $detail_query = "INSERT INTO package_details (package_id) VALUES (?)";
        $stmt = mysqli_prepare($conn, $detail_query);
        mysqli_stmt_bind_param($stmt, "i", $package_id);
        return mysqli_stmt_execute($stmt);
    }
}

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get registered users for the modal
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);
$users = [];
if ($users_result) {
    while ($user = mysqli_fetch_assoc($users_result)) {
        $users[] = $user;
    }
}

// Get tour packages by type
$tour_packages = get_packages_by_type(1);
$one_day_tours = get_packages_by_type(2);
$special_tours = get_packages_by_type(3);

// Get vehicles data
$vehicles_query = "SELECT * FROM vehicles ORDER BY created_at DESC";
$vehicles_result = mysqli_query($conn, $vehicles_query);
$vehicles = [];
if ($vehicles_result) {
    while ($vehicle = mysqli_fetch_assoc($vehicles_result)) {
        $vehicles[] = $vehicle;
    }
}

// Get team members data
$team_members_query = "SELECT * FROM team_members ORDER BY id DESC";
$team_members_result = mysqli_query($conn, $team_members_query);
$team_members = [];
if ($team_members_result) {
    while ($member = mysqli_fetch_assoc($team_members_result)) {
        $team_members[] = $member;
    }
}

// Get reviews data
$reviews_query = "SELECT * FROM reviews ORDER BY created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_query);
$reviews = [];
if ($reviews_result) {
    while ($review = mysqli_fetch_assoc($reviews_result)) {
        $reviews[] = $review;
    }
}

// Count pending reviews
$pending_reviews_count = 0;
foreach ($reviews as $review) {
    if ($review['status'] === 'pending') {
        $pending_reviews_count++;
    }
}

// Handle package operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new tour package
    if (isset($_POST['add_tour_package'])) {
        $name = sanitize_input($_POST['packageName']);
        $price = sanitize_input($_POST['packagePrice']);
        $duration = sanitize_input($_POST['packageDuration']);
        $description = sanitize_input($_POST['packageDescription']);
        $destinations = sanitize_input($_POST['packageDestinations']);
        $type_id = 1; // Tour Package
        
        // Handle file upload
        $upload_result = upload_file($_FILES['packageImage']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
            $package_id = add_package($type_id, $name, $description, $price, $duration, $image);
            
            if ($package_id) {
                // Add destination to package details
                if (!empty($destinations)) {
                    add_package_detail_with_destination($package_id, $destinations);
                }
                
                // Redirect to show success message
                header("Location: admin.php?success=package_added");
                exit;
            }
        }
    }
    
    // Add new one day tour package
    if (isset($_POST['add_one_day_tour'])) {
        $name = sanitize_input($_POST['oneDayName']);
        $price = sanitize_input($_POST['oneDayPrice']);
        $duration = sanitize_input($_POST['oneDayDuration']);
        $description = sanitize_input($_POST['oneDayDescription']);
        $destinations = sanitize_input($_POST['oneDayDestinations']);
        $type_id = 2; // One Day Tour
        
        // Handle file upload
        $upload_result = upload_file($_FILES['oneDayImage']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
            $package_id = add_package($type_id, $name, $description, $price, $duration, $image);
            
            if ($package_id) {
                // Add destination to package details
                if (!empty($destinations)) {
                    add_package_detail_with_destination($package_id, $destinations);
                }
                
                // Redirect to show success message
                header("Location: admin.php?success=one_day_tour_added");
                exit;
            }
        }
    }
    
    // Add new special tour package
    if (isset($_POST['add_special_tour'])) {
        $name = sanitize_input($_POST['specialName']);
        $price = sanitize_input($_POST['specialPrice']);
        $duration = sanitize_input($_POST['specialDuration']);
        $description = sanitize_input($_POST['specialDescription']);
        $destinations = sanitize_input($_POST['specialDestinations']);
        $type_id = 3; // Special Tour
        
        // Handle file upload
        $upload_result = upload_file($_FILES['specialImage']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
            $package_id = add_package($type_id, $name, $description, $price, $duration, $image);
            
            if ($package_id) {
                // Add destination to package details
                if (!empty($destinations)) {
                    add_package_detail_with_destination($package_id, $destinations);
                }
                
                // Redirect to show success message
                header("Location: admin.php?success=special_tour_added");
                exit;
            }
        }
    }
    
    // Add new vehicle
    if (isset($_POST['add_vehicle'])) {
        $type = sanitize_input($_POST['vehicleType']);
        $name = sanitize_input($_POST['vehicleName']);
        $description = sanitize_input($_POST['vehicleDescription']);
        $capacity = intval($_POST['vehicleCapacity']);
        $price_per_day = floatval($_POST['vehiclePrice']);
        $available = isset($_POST['vehicleAvailable']) ? 1 : 0;
        
        // Handle file upload
        $upload_result = upload_file($_FILES['vehicleImage']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
            
            $add_query = "INSERT INTO vehicles (type, name, description, capacity, price_per_day, image, available) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $add_stmt = mysqli_prepare($conn, $add_query);
            mysqli_stmt_bind_param($add_stmt, "sssiisi", $type, $name, $description, $capacity, $price_per_day, $image, $available);
            
            if (mysqli_stmt_execute($add_stmt)) {
                header("Location: admin.php?success=vehicle_added#vehicles-section");
                exit;
            } else {
                $error_message = "Error adding vehicle: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Error uploading image: " . $upload_result['message'];
        }
    }
    
    // Update vehicle
    if (isset($_POST['update_vehicle'])) {
        $vehicle_id = intval($_POST['vehicle_id']);
        $type = sanitize_input($_POST['editVehicleType']);
        $name = sanitize_input($_POST['editVehicleName']);
        $description = sanitize_input($_POST['editVehicleDescription']);
        $capacity = intval($_POST['editVehicleCapacity']);
        $price_per_day = floatval($_POST['editVehiclePrice']);
        $available = isset($_POST['editVehicleAvailable']) ? 1 : 0;
        
        if (isset($_FILES['editVehicleImage']) && $_FILES['editVehicleImage']['size'] > 0) {
            // Handle file upload for image update
            $upload_result = upload_file($_FILES['editVehicleImage']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                $update_query = "UPDATE vehicles SET type = ?, name = ?, description = ?, capacity = ?, 
                               price_per_day = ?, image = ?, available = ? WHERE vehicle_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "sssiisii", $type, $name, $description, $capacity, 
                                   $price_per_day, $image, $available, $vehicle_id);
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            // Update without changing image
            $update_query = "UPDATE vehicles SET type = ?, name = ?, description = ?, capacity = ?, 
                           price_per_day = ?, available = ? WHERE vehicle_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssiiii", $type, $name, $description, $capacity, 
                               $price_per_day, $available, $vehicle_id);
        }
        
        if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
            header("Location: admin.php?success=vehicle_updated#vehicles-section");
            exit;
        } else if (!isset($error_message)) {
            $error_message = "Error updating vehicle: " . mysqli_error($conn);
        }
    }
    
    // Delete vehicle
    if (isset($_POST['delete_vehicle'])) {
        $vehicle_id = intval($_POST['delete_vehicle_id']);
        
        $delete_query = "DELETE FROM vehicles WHERE vehicle_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $vehicle_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: admin.php?success=vehicle_deleted#vehicles-section");
            exit;
        } else {
            $error_message = "Error deleting vehicle: " . mysqli_error($conn);
        }
    }
    
    // Handle package deletion
    if (isset($_POST['delete_package'])) {
        $package_id = sanitize_input($_POST['delete_package_id']);
        
        if (delete_package($package_id)) {
            header("Location: admin.php?success=package_deleted");
            exit;
        }
    }
    
    // Add new team member
    if (isset($_POST['add_team_member'])) {
        $name = sanitize_input($_POST['memberName']);
        $position = sanitize_input($_POST['memberPosition']);
        $bio = sanitize_input($_POST['memberBio']);
        $facebook = sanitize_input($_POST['memberFacebook']);
        $twitter = sanitize_input($_POST['memberTwitter']);
        $instagram = sanitize_input($_POST['memberInstagram']);
        $linkedin = sanitize_input($_POST['memberLinkedin']);
        
        // Handle file upload for team member image
        $upload_result = upload_file($_FILES['memberImage']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
            
            $add_query = "INSERT INTO team_members (name, position, bio, image, facebook, twitter, instagram, linkedin) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $add_stmt = mysqli_prepare($conn, $add_query);
            mysqli_stmt_bind_param($add_stmt, "ssssssss", $name, $position, $bio, $image, $facebook, $twitter, $instagram, $linkedin);
            
            if (mysqli_stmt_execute($add_stmt)) {
                header("Location: admin.php?success=team_member_added#team-members-section");
                exit;
            } else {
                $error_message = "Error adding team member: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Error uploading image: " . $upload_result['message'];
        }
    }
    
    // Update team member
    if (isset($_POST['update_team_member'])) {
        $member_id = intval($_POST['member_id']);
        $name = sanitize_input($_POST['editMemberName']);
        $position = sanitize_input($_POST['editMemberPosition']);
        $bio = sanitize_input($_POST['editMemberBio']);
        $facebook = sanitize_input($_POST['editMemberFacebook']);
        $twitter = sanitize_input($_POST['editMemberTwitter']);
        $instagram = sanitize_input($_POST['editMemberInstagram']);
        $linkedin = sanitize_input($_POST['editMemberLinkedin']);
        
        if (isset($_FILES['editMemberImage']) && $_FILES['editMemberImage']['size'] > 0) {
            // Handle file upload for image update
            $upload_result = upload_file($_FILES['editMemberImage']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                $update_query = "UPDATE team_members SET name = ?, position = ?, bio = ?, image = ?, 
                                facebook = ?, twitter = ?, instagram = ?, linkedin = ? 
                                WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param(
                    $update_stmt, 
                    "ssssssssi", 
                    $name, $position, $bio, $image, $facebook, $twitter, $instagram, $linkedin, $member_id
                );
            } else {
                $error_message = "Error uploading image: " . $upload_result['message'];
            }
        } else {
            // Update without changing image
            $update_query = "UPDATE team_members SET name = ?, position = ?, bio = ?, 
                           facebook = ?, twitter = ?, instagram = ?, linkedin = ? 
                           WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param(
                $update_stmt, 
                "sssssssi", 
                $name, $position, $bio, $facebook, $twitter, $instagram, $linkedin, $member_id
            );
        }
        
        if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
            header("Location: admin.php?success=team_member_updated#team-members-section");
            exit;
        } else if (!isset($error_message)) {
            $error_message = "Error updating team member: " . mysqli_error($conn);
        }
    }
    
    // Delete team member
    if (isset($_POST['delete_team_member'])) {
        $member_id = intval($_POST['delete_member_id']);
        
        $delete_query = "DELETE FROM team_members WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $member_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: admin.php?success=team_member_deleted#team-members-section");
            exit;
        } else {
            $error_message = "Error deleting team member: " . mysqli_error($conn);
        }
    }
    
    // Approve review
    if (isset($_POST['approve_review'])) {
        $review_id = intval($_POST['review_id']);
        
        $update_query = "UPDATE reviews SET status = 'approved' WHERE review_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $review_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: admin.php?success=review_approved#reviews-section");
            exit;
        } else {
            $error_message = "Error approving review: " . mysqli_error($conn);
        }
    }
    
    // Reject review
    if (isset($_POST['reject_review'])) {
        $review_id = intval($_POST['review_id']);
        
        $update_query = "UPDATE reviews SET status = 'rejected' WHERE review_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $review_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: admin.php?success=review_rejected#reviews-section");
            exit;
        } else {
            $error_message = "Error rejecting review: " . mysqli_error($conn);
        }
    }
    
    // Delete review
    if (isset($_POST['delete_review'])) {
        $review_id = intval($_POST['review_id']);
        
        $delete_query = "DELETE FROM reviews WHERE review_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $review_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: admin.php?success=review_deleted#reviews-section");
            exit;
        } else {
            $error_message = "Error deleting review: " . mysqli_error($conn);
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adventure Travel.lk - Admin Dashboard</title>
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

        /* Dropdown menu */
        .dropdown-container {
            display: none;
            background-color: rgba(0, 0, 0, 0.2);
            padding-left: 0;
        }
        
        .dropdown-container a {
            padding-left: 35px;
            font-size: 0.95em;
        }
        
        .dropdown-btn {
            position: relative;
        }
        
        .dropdown-btn::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 20px;
            transition: all 0.3s ease;
        }
        
        .dropdown-btn.active::after {
            transform: rotate(180deg);
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

        .sidebar-header img:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(101, 255, 193, 0.5);
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

        /* Badge styles for consistent positioning */
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative; /* Ensure position relative for badge positioning */
        }
        
        .sidebar-menu .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            min-width: 20px;
            height: 20px;
        }

        .sidebar-menu a.active, .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--secondary-color);
            border-left: 4px solid var(--secondary-color);
        }

        .sidebar-menu a i {
            margin-right: 10px;
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

        .header h1 {
            color: var(--dark-color);
            font-size: 1.5rem;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .admin-user span {
            font-weight: bold;
        }

        .logout-btn {
            margin-left: 15px;
            padding: 5px 15px;
            background-color: var(--danger-color);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        /* Package Management Styles */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .dashboard-card p {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        .package-section {
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

        .section-header h2 {
            color: var(--primary-color);
        }

        .add-btn {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background-color: #145a55;
        }

        .package-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .package-card {
            background-color:rgb(123, 255, 222);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .package-image {
            height: 180px;
            overflow: hidden;
        }

        .package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .package-details {
            padding: 15px;
        }

        .package-details h3 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .package-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .package-desc {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
            line-height: 1.4;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
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

        .edit-btn:hover {
            background-color: #ec971f;
        }

        .view-btn {
            background-color: var(--primary-color);
            color: #fff;
        }

        .view-btn:hover {
            background-color: #145a55;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: #fff;
        }

        .delete-btn:hover {
            background-color: #c82333;
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
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 70%;
            max-width: 800px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: var(--primary-color);
        }

        .close-btn {
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--danger-color);
        }

        .form-group {
            margin-bottom: 20px;
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-submit {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-submit:hover {
            background-color: #145a55;
        }

        /* Responsive design */
        @media (max-width: 1200px) {
            .dashboard-cards {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .package-cards {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 991px) {
            .admin-container {
                position: relative;
            }
            
            .sidebar {
                width: 70px;
                text-align: center;
                position: fixed;
                left: 0;
                top: 0;
                height: 100%;
                z-index: 100;
            }

            .sidebar-header h2, .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a .badge {
                position: absolute;
                top: 5px;
                right: 5px;
                font-size: 0.6rem;
                padding: 3px 5px;
                transform: none;
                min-width: 16px;
                height: 16px;
            }

            .sidebar-menu a i {
                margin-right: 0;
                font-size: 1.3rem;
            }

            .sidebar-menu a {
                padding: 15px;
                position: relative;
            }

            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .dropdown-container {
                position: absolute;
                left: 70px;
                top: 0;
                background-color: rgb(4, 39, 37);
                z-index: 101;
                box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
                border-radius: 0 5px 5px 0;
                width: 200px;
            }
            
            .dropdown-container a {
                padding: 10px 15px;
                width: 100%;
                text-align: left;
            }
            
            .dropdown-container a span {
                display: inline-block;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .admin-user {
                width: 100%;
                justify-content: space-between;
            }
            
            .message-btn {
                margin-bottom: 10px;
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .dashboard-cards, .package-cards {
                grid-template-columns: 1fr;
            }
            
            .header {
                border-radius: 0;
                margin-bottom: 15px;
            }
            
            .admin-user {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            
            .modal-content {
                width: 95%;
                margin: 2% auto;
            }
            
            .users-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .section-header h2 {
                margin-bottom: 10px;
            }
            
            .package-view-header {
                flex-direction: column;
            }
            
            .package-view-image {
                flex: 0 0 100%;
                margin-bottom: 15px;
            }
            
            /* Mobile navigation toggle */
            .mobile-nav-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 200;
                background-color: var(--primary-color);
                color: white;
                border: none;
                border-radius: 4px;
                width: 40px;
                height: 40px;
                cursor: pointer;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 250px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-header h2, .sidebar-menu a span {
                display: inline-block;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 60px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                font-size: 14px;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .header {
                padding: 12px;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .admin-user {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .package-section {
                padding: 15px;
            }
            
            .message-btn, .logout-btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .modal-header h2 {
                font-size: 1.3rem;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-control {
                padding: 8px;
            }
            
            .modal-content {
                padding: 15px;
            }
            
            .add-btn, .form-submit {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        
        @media (max-height: 700px) and (min-width: 992px) {
            .sidebar {
                overflow-y: auto;
            }
            
            .sidebar-menu a {
                padding: 8px 20px;
            }
        }

        /* Mobile navigation toggle button - hidden by default */
        .mobile-nav-toggle {
            display: none;
            font-size: 1.5rem;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Package View Styles */
        .package-view {
            padding: 15px;
        }
        
        .package-view-header {
            display: flex;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .package-view-image {
            flex: 0 0 40%;
        }
        
        .package-view-image img {
            width: 100%;
            border-radius: 5px;
        }
        
        .package-view-info {
            flex: 1;
        }
        
        .package-view-info h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .package-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #666;
        }
        
        .package-meta .price {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .package-section {
            margin-bottom: 20px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .package-section h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .package-section ul, .package-section ol {
            padding-left: 20px;
        }
        
        .package-section li {
            margin-bottom: 5px;
        }
        
        .package-view-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .error {
            color: var(--danger-color);
            text-align: center;
            padding: 20px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            background-color: var(--danger-color);
            color: white;
        }

        .message-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
            margin-right: 15px;
        }

        .message-btn:hover {
            background-color: #124d47;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .message-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 22px;
            height: 22px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s infinite;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Users table styles */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .users-table th, .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background-color: var(--primary-color);
            color: #fff;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .users-table tr:hover {
            background-color: rgba(101, 255, 193, 0.1);
        }
        
        .users-table .user-action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .users-table .user-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .users-modal-content {
            width: 90%;
            max-width: 1000px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-body {
            padding: 5px;
        }

        /* Reviews Table Styles */
        .review-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 15px;
            background-color: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
        }

        .reviews-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .reviews-table th, .reviews-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .reviews-table th {
            background-color: var(--primary-color);
            color: #fff;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .reviews-table tr:last-child td {
            border-bottom: none;
        }
        
        .reviews-table tr:hover {
            background-color: rgba(101, 255, 193, 0.1);
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        
        .status-badge.pending {
            background-color: #ffc107;
            color: white;
        }
        
        .status-badge.approved {
            background-color: #28a745;
            color: white;
        }
        
        .status-badge.rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .review-text-preview {
            max-width: 300px;
            line-height: 1.4;
        }
        
        .view-full-review {
            display: inline-block;
            margin-top: 5px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .review-photo-preview {
            margin-top: 5px;
        }
        
        .approve-btn, .reject-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        
        .approve-btn:hover {
            background-color: #218838;
        }
        
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .reject-btn:hover {
            background-color: #c82333;
        }
        
        .empty-reviews {
            text-align: center;
            padding: 50px 0;
            color: #666;
        }

        /* Responsive file upload */
        @media (max-width: 576px) {
            .review-modal-content {
                padding: 20px;
                margin: 30px auto;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .cancel-review, 
            .submit-review {
                width: 100%;
                text-align: center;
            }
            
            .rating-select {
                gap: 5px;
            }
            
            .rating-star {
                font-size: 20px;
            }
        }
        
        /* Custom Alert Styles */
        .custom-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .custom-alert-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .custom-alert {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 400px;
            padding: 0;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .custom-alert-overlay.active .custom-alert {
            transform: translateY(0);
        }
        
        .alert-header {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .alert-icon {
            margin-right: 15px;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .alert-success .alert-icon {
            background-color: var(--success-color);
        }
        
        .alert-warning .alert-icon {
            background-color: #ffc107;
        }
        
        .alert-danger .alert-icon {
            background-color: var(--danger-color);
        }
        
        .alert-info .alert-icon {
            background-color: #17a2b8;
        }
        
        .alert-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        
        .alert-body {
            padding: 20px;
            color: #666;
            line-height: 1.5;
        }
        
        .alert-footer {
            padding: 10px 20px 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .alert-btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
        }
        
        .alert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .alert-btn-primary {
            background-color: var(--primary-color);
            color: white;
            margin-left: 10px;
        }
        
        .alert-btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Mobile Navigation Toggle Button -->
        <button class="mobile-nav-toggle" id="mobileNavToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo-4.png" alt="Adventure Travel.lk Logo">
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="admin.php" class="active"> <span><i class="fas fa-home"></i> Dashboard</span></a></li>
                    <li><a href="#tour-packages-section"> <span><i class="fas fa-suitcase"></i> Tour Packages</span></a></li>
                    <li><a href="#one-day-tour-packages-section"> <span><i class="fas fa-clock"></i> One Day Tours</span></a></li>
                    <li><a href="#special-tour-packages-section"> <span><i class="fas fa-star"></i> Special Tours</span></a></li>
                    <li><a href="#vehicles-section"> <span><i class="fas fa-car"></i> Vehicles</span></a></li>
                    <li><a href="#team-members-section"> <span><i class="fas fa-user-tie"></i> Team Members</span></a></li>
                    <li><a href="#reviews-section"> <span><i class="fas fa-star"></i> Reviews</span>
                        <?php if ($pending_reviews_count > 0): ?>
                            <span class="badge" style="background-color: rgb(255, 174, 0); color:white;"><?php echo $pending_reviews_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li>
                        <a href="javascript:void(0);" class="dropdown-btn"><span><i class="fas fa-cog"></i> Manage</span></a>
                        <div class="dropdown-container">
                            <a href="manage_destinations.php"><span><i class="fas fa-map-marker-alt"></i> Destinations</span></a>
                            <a href="manage_hotels.php"><span><i class="fas fa-hotel"></i> Hotels</span></a>
                            <a href="manage_policies.php"><span><i class="fas fa-file-alt"></i> Policies</span></a>
                            <a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Admins</span></a>
                        </div>
                    </li>
                    <li><a href="user_messages.php" style="color: #fff; background-color:rgb(196, 0, 56);"> <span><i class="fas fa-comment-dots"></i> User Messages</span> 
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="badge" style="background-color: rgb(255, 255, 255); color:rgb(255, 0, 0);"><?php echo $unread_messages_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="contact_messages.php"> <span><i class="fas fa-envelope"></i> Contact Messages</span>
                        <?php if ($unread_contact_count > 0): ?>
                            <span class="badge" style="background-color: rgb(255, 0, 0); color:white;"><?php echo $unread_contact_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="#" onclick="window.location.reload()"> <span><i class="fas fa-sync-alt"></i> Refresh</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <a href="user_messages.php" class="message-btn">
                        <i class="fas fa-comments"></i> User Messages
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="message-badge"><?php echo $unread_messages_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div style="display: flex; align-items: center;">
                    <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Admin">
                        <span style="margin: 0 10px;"><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin User'; ?></span>
                    <a href="admin.php?logout=1" class="logout-btn">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Dashboard Summary Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <h3>Total Packages</h3>
                    <p><?php echo $stats['total_packages']; ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Active Bookings</h3>
                    <p><?php echo $stats['active_bookings']; ?></p>
                </div>
                <div class="dashboard-card" onclick="openModal('usersModal')" style="cursor: pointer;">
                    <h3>Registered Users</h3>
                    <p><?php echo $stats['registered_users']; ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Revenue</h3>
                    <p>$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                </div>
            </div>

            <!-- Tour Packages Section -->
            <div class="package-section" id="tour-packages-section"> 
                <div class="section-header">
                    <h2>Tour Packages</h2>
                    <div>
                        <button class="add-btn" onclick="openModal('tourPackageModal')" style="margin-right: 10px;">Add New</button>
                        <a href="edit_tour_type.php?type=1" class="edit-btn" style="text-decoration: none; padding: 8px 15px; display: inline-block;">Edit Category</a>
                    </div>
                </div>
                <div class="package-cards">
                    <?php if (count($tour_packages) > 0): ?>
                        <?php foreach ($tour_packages as $package): ?>
                            <div class="package-card">
                                <div class="package-image">
                                    <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>">
                                </div>
                                <div class="package-details">
                                    <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                                    <div class="package-meta">
                                        <span>$<?php echo number_format($package['price'], 2); ?></span>
                                        <span><?php echo htmlspecialchars($package['duration']); ?></span>
                                    </div>
                                    <p class="package-desc"><?php echo htmlspecialchars(substr($package['description'], 0, 100)) . '...'; ?></p>
                                    <div class="card-actions">
                                        <button class="edit-btn" onclick="editPackage(<?php echo $package['package_id']; ?>)">Edit Features</button>
                                        <button class="view-btn" onclick="viewPackage(<?php echo (int)$package['package_id']; ?>); return false;">View</button>
                                        <button class="delete-btn" onclick="deletePackage(<?php echo $package['package_id']; ?>, '<?php echo htmlspecialchars($package['name']); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No tour packages found. Add your first package using the "Add New" button.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- One Day Tour Packages Section -->
            <div class="package-section" id="one-day-tour-packages-section">
                <div class="section-header">
                    <h2>One Day Tour Packages</h2>
                    <div>
                        <button class="add-btn" onclick="openModal('oneDayTourModal')" style="margin-right: 10px;">Add New</button>
                        <a href="edit_tour_type.php?type=2" class="edit-btn" style="text-decoration: none; padding: 8px 15px; display: inline-block;">Edit Category</a>
                    </div>
                </div>
                <div class="package-cards">
                    <?php if (count($one_day_tours) > 0): ?>
                        <?php foreach ($one_day_tours as $package): ?>
                            <div class="package-card">
                                <div class="package-image">
                                    <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>">
                                </div>
                                <div class="package-details">
                                    <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                                    <div class="package-meta">
                                        <span>$<?php echo number_format($package['price'], 2); ?></span>
                                        <span><?php echo htmlspecialchars($package['duration']); ?></span>
                                    </div>
                                    <p class="package-desc"><?php echo htmlspecialchars(substr($package['description'], 0, 100)) . '...'; ?></p>
                                    <div class="card-actions">
                                        <button class="edit-btn" onclick="editPackage(<?php echo $package['package_id']; ?>)">Edit Features</button>
                                        <button class="view-btn" onclick="viewPackage(<?php echo (int)$package['package_id']; ?>); return false;">View</button>
                                        <button class="delete-btn" onclick="deletePackage(<?php echo $package['package_id']; ?>, '<?php echo htmlspecialchars($package['name']); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No one day tour packages found. Add your first package using the "Add New" button.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Special Tour Packages Section -->
            <div class="package-section" id="special-tour-packages-section">
                <div class="section-header">
                    <h2>Special Tour Packages</h2>
                    <div>
                        <button class="add-btn" onclick="openModal('specialTourModal')" style="margin-right: 10px;">Add New</button>
                        <a href="edit_tour_type.php?type=3" class="edit-btn" style="text-decoration: none; padding: 8px 15px; display: inline-block;">Edit Category</a>
                    </div>
                </div>
                <div class="package-cards">
                    <?php if (count($special_tours) > 0): ?>
                        <?php foreach ($special_tours as $package): ?>
                            <div class="package-card">
                                <div class="package-image">
                                    <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>">
                                </div>
                                <div class="package-details">
                                    <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                                    <div class="package-meta">
                                        <span>$<?php echo number_format($package['price'], 2); ?></span>
                                        <span><?php echo htmlspecialchars($package['duration']); ?></span>
                                    </div>
                                    <p class="package-desc"><?php echo htmlspecialchars(substr($package['description'], 0, 100)) . '...'; ?></p>
                                    <div class="card-actions">
                                        <button class="edit-btn" onclick="editPackage(<?php echo $package['package_id']; ?>)">Edit Features</button>
                                        <button class="view-btn" onclick="viewPackage(<?php echo (int)$package['package_id']; ?>); return false;">View</button>
                                        <button class="delete-btn" onclick="deletePackage(<?php echo $package['package_id']; ?>, '<?php echo htmlspecialchars($package['name']); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No special tour packages found. Add your first package using the "Add New" button.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vehicles Section -->
            <div class="package-section" id="vehicles-section">
                <div class="section-header">
                    <h2>Vehicles</h2>
                    <div>
                        <button class="add-btn" onclick="openModal('vehicleModal')" style="margin-right: 10px;">Add New</button>
                    </div>
                </div>
                <div class="package-cards">
                    <?php if (count($vehicles) > 0): ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <div class="package-card">
                                <div class="package-image">
                                    <img src="../images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                                </div>
                                <div class="package-details">
                                    <h3><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                                    <div class="package-meta">
                                        <span>$<?php echo number_format($vehicle['price_per_day'], 2); ?> per day</span>
                                        <span>Capacity: <?php echo $vehicle['capacity']; ?></span>
                                        <span class="badge" style="background-color: <?php echo $vehicle['available'] ? '#28a745' : '#dc3545'; ?>; color: white; padding: 2px 6px; border-radius: 3px;">
                                            <?php echo $vehicle['available'] ? 'Available' : 'Not Available'; ?>
                                        </span>
                                    </div>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($vehicle['type']); ?></p>
                                    <p class="package-desc"><?php echo htmlspecialchars(substr($vehicle['description'], 0, 100)) . '...'; ?></p>
                                    <div class="card-actions">
                                        <a href="manage_vehicle_details.php?id=<?php echo $vehicle['vehicle_id']; ?>" class="feature-btn edit-btn">Manage Features</a>
                                        <button type="button" class="edit-btn" onclick="editVehicle(<?php echo $vehicle['vehicle_id']; ?>)">Edit</button>
                                        <button type="button" class="delete-btn" onclick="deleteVehicle(<?php echo $vehicle['vehicle_id']; ?>, '<?php echo htmlspecialchars($vehicle['name']); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No vehicles found. Add your first vehicle using the "Add New" button.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Team Members Section -->
            <div class="package-section" id="team-members-section">
                <div class="section-header">
                    <h2>Team Members</h2>
                    <div>
                        <button class="add-btn" onclick="openModal('teamMemberModal')" style="margin-right: 10px;">Add New Team Member</button>
                    </div>
                </div>
                <div class="package-cards">
                    <?php if (count($team_members) > 0): ?>
                        <?php foreach ($team_members as $member): ?>
                            <div class="package-card">
                                <div class="package-image">
                                    <img src="../images/<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                </div>
                                <div class="package-details">
                                    <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                                    <div class="package-meta">
                                        <span><?php echo htmlspecialchars($member['position']); ?></span>
                                    </div>
                                    <div class="social-links" style="display: flex; justify-content: center; margin: 15px 0;">
                                        <?php if (!empty($member['facebook'])): ?>
                                            <a href="<?php echo htmlspecialchars($member['facebook']); ?>" target="_blank" style="margin: 0 5px; color: #3b5998;"><i class="fab fa-facebook-f"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['twitter'])): ?>
                                            <a href="<?php echo htmlspecialchars($member['twitter']); ?>" target="_blank" style="margin: 0 5px; color: #1da1f2;"><i class="fab fa-twitter"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['instagram'])): ?>
                                            <a href="<?php echo htmlspecialchars($member['instagram']); ?>" target="_blank" style="margin: 0 5px; color: #e1306c;"><i class="fab fa-instagram"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['linkedin'])): ?>
                                            <a href="<?php echo htmlspecialchars($member['linkedin']); ?>" target="_blank" style="margin: 0 5px; color: #0077b5;"><i class="fab fa-linkedin-in"></i></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-actions">
                                        <button type="button" class="edit-btn" onclick="editTeamMember(<?php echo $member['id']; ?>)">Edit</button>
                                        <button type="button" class="delete-btn" onclick="deleteTeamMember(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No team members found. Add your first team member using the "Add New Team Member" button.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="package-section" id="reviews-section">
                <div class="section-header">
                    <h2>Customer Reviews</h2>
                    <div class="review-filters">
                        <button class="filter-btn active" data-filter="all">All Reviews</button>
                        <button class="filter-btn" data-filter="pending">Pending</button>
                        <button class="filter-btn" data-filter="approved">Approved</button>
                        <button class="filter-btn" data-filter="rejected">Rejected</button>
                    </div>
                </div>
                
                <?php if (count($reviews) > 0): ?>
                    <table class="reviews-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Tour Type</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr class="review-row <?php echo $review['status']; ?>">
                                    <td><?php echo $review['review_id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($review['name']); ?><br>
                                        <small><?php echo htmlspecialchars($review['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($review['tour_type']); ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-text-preview">
                                            <?php echo htmlspecialchars(substr($review['review_text'], 0, 100)) . (strlen($review['review_text']) > 100 ? '...' : ''); ?>
                                            <?php if (strlen($review['review_text']) > 100): ?>
                                                <a href="#" class="view-full-review" data-review="<?php echo htmlspecialchars($review['review_text']); ?>">Read More</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($review['photo'])): ?>
                                            <div class="review-photo-preview">
                                                <a href="../images/<?php echo htmlspecialchars($review['photo']); ?>" target="_blank">View Photo</a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $review['status']; ?>">
                                            <?php echo ucfirst($review['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($review['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <button type="submit" name="approve_review" class="approve-btn">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <button type="submit" name="reject_review" class="reject-btn">Reject</button>
                                            </form>
                                        <?php elseif ($review['status'] === 'approved'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <button type="submit" name="reject_review" class="reject-btn">Reject</button>
                                            </form>
                                        <?php elseif ($review['status'] === 'rejected'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <button type="submit" name="approve_review" class="approve-btn">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this review? This action cannot be undone.')">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <button type="submit" name="delete_review" class="delete-btn" style="margin-top: 5px;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-reviews">
                        <i class="far fa-comment-dots" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p>No reviews found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Tour Package Modal -->
    <div id="tourPackageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Tour Package</h2>
                <span class="close-btn" onclick="closeModal('tourPackageModal')">&times;</span>
            </div>
            <form id="tourPackageForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="packageName">Package Name</label>
                    <input type="text" id="packageName" name="packageName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="packagePrice">Price ($)</label>
                    <input type="number" id="packagePrice" name="packagePrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="packageDuration">Duration (Days)</label>
                    <input type="text" id="packageDuration" name="packageDuration" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="packageDestinations">Destinations (comma separated)</label>
                    <input type="text" id="packageDestinations" name="packageDestinations" class="form-control" placeholder="e.g. Paris, London, Rome" required>
                </div>
                <div class="form-group">
                    <label for="packageImage">Image</label>
                    <input type="file" id="packageImage" name="packageImage" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="packageDescription">Description</label>
                    <textarea id="packageDescription" name="packageDescription" class="form-control" required></textarea>
                </div>
                <input type="hidden" name="add_tour_package" value="1">
                <button type="submit" class="form-submit">Add Package</button>
            </form>
        </div>
    </div>

    <!-- Add One Day Tour Modal -->
    <div id="oneDayTourModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New One Day Tour</h2>
                <span class="close-btn" onclick="closeModal('oneDayTourModal')">&times;</span>
            </div>
            <form id="oneDayTourForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="oneDayName">Tour Name</label>
                    <input type="text" id="oneDayName" name="oneDayName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="oneDayPrice">Price ($)</label>
                    <input type="number" id="oneDayPrice" name="oneDayPrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="oneDayDuration">Duration (Hours)</label>
                    <input type="text" id="oneDayDuration" name="oneDayDuration" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="oneDayDestinations">Destinations (comma separated)</label>
                    <input type="text" id="oneDayDestinations" name="oneDayDestinations" class="form-control" placeholder="e.g. Paris, London, Rome" required>
                </div>
                <div class="form-group">
                    <label for="oneDayImage">Image</label>
                    <input type="file" id="oneDayImage" name="oneDayImage" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="oneDayDescription">Description</label>
                    <textarea id="oneDayDescription" name="oneDayDescription" class="form-control" required></textarea>
                </div>
                <input type="hidden" name="add_one_day_tour" value="1">
                <button type="submit" class="form-submit">Add One Day Tour</button>
            </form>
        </div>
    </div>

    <!-- Add Special Tour Modal -->
    <div id="specialTourModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Special Tour</h2>
                <span class="close-btn" onclick="closeModal('specialTourModal')">&times;</span>
            </div>
            <form id="specialTourForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="specialName">Tour Name</label>
                    <input type="text" id="specialName" name="specialName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="specialPrice">Price ($)</label>
                    <input type="number" id="specialPrice" name="specialPrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="specialDuration">Duration (Days)</label>
                    <input type="text" id="specialDuration" name="specialDuration" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="specialDestinations">Destinations (comma separated)</label>
                    <input type="text" id="specialDestinations" name="specialDestinations" class="form-control" placeholder="e.g. Paris, London, Rome" required>
                </div>
                <div class="form-group">
                    <label for="specialImage">Image</label>
                    <input type="file" id="specialImage" name="specialImage" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="specialDescription">Description</label>
                    <textarea id="specialDescription" name="specialDescription" class="form-control" required></textarea>
                </div>
                <input type="hidden" name="add_special_tour" value="1">
                <button type="submit" class="form-submit">Add Special Tour</button>
            </form>
        </div>
    </div>

    <!-- View Package Modal -->
    <div id="viewPackageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Package Details</h2>
                <span class="close-btn" onclick="closeModal('viewPackageModal')">&times;</span>
            </div>
            <div id="packageDetails">
                <!-- Package details will be loaded here via AJAX -->
                <div class="loading">Loading package details...</div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteConfirmModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the package: <span id="deletePackageName"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <form id="deletePackageForm" method="POST">
                    <input type="hidden" id="deletePackageId" name="delete_package_id">
                    <input type="hidden" name="delete_package" value="1">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="vehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Vehicle</h2>
                <span class="close-btn" onclick="closeModal('vehicleModal')">&times;</span>
            </div>
            <form id="vehicleForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="vehicleType">Type</label>
                    <input type="text" id="vehicleType" name="vehicleType" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vehicleName">Name</label>
                    <input type="text" id="vehicleName" name="vehicleName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vehicleDescription">Description</label>
                    <textarea id="vehicleDescription" name="vehicleDescription" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="vehicleCapacity">Capacity</label>
                    <input type="number" id="vehicleCapacity" name="vehicleCapacity" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vehiclePrice">Price per Day ($)</label>
                    <input type="number" id="vehiclePrice" name="vehiclePrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vehicleAvailable">Available</label>
                    <input type="checkbox" id="vehicleAvailable" name="vehicleAvailable" checked>
                </div>
                <div class="form-group">
                    <label for="vehicleImage">Image</label>
                    <input type="file" id="vehicleImage" name="vehicleImage" class="form-control" accept="image/*" required>
                </div>
                <input type="hidden" name="add_vehicle" value="1">
                <button type="submit" class="form-submit">Add Vehicle</button>
            </form>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div id="editVehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Vehicle</h2>
                <span class="close-btn" onclick="closeModal('editVehicleModal')">&times;</span>
            </div>
            <form id="editVehicleForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="vehicle_id" name="vehicle_id">
                <div class="form-group">
                    <label for="editVehicleType">Type</label>
                    <input type="text" id="editVehicleType" name="editVehicleType" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editVehicleName">Name</label>
                    <input type="text" id="editVehicleName" name="editVehicleName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editVehicleDescription">Description</label>
                    <textarea id="editVehicleDescription" name="editVehicleDescription" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editVehicleCapacity">Capacity</label>
                    <input type="number" id="editVehicleCapacity" name="editVehicleCapacity" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editVehiclePrice">Price per Day ($)</label>
                    <input type="number" id="editVehiclePrice" name="editVehiclePrice" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editVehicleAvailable">Available</label>
                    <input type="checkbox" id="editVehicleAvailable" name="editVehicleAvailable">
                </div>
                <div class="form-group">
                    <label for="editVehicleImage">Image (Leave empty to keep current image)</label>
                    <input type="file" id="editVehicleImage" name="editVehicleImage" class="form-control" accept="image/*">
                    <div id="currentVehicleImage" style="margin-top: 10px; display: none;">
                        <p>Current Image:</p>
                        <img id="vehicleImagePreview" src="" alt="Current Vehicle Image" style="max-width: 200px; max-height: 150px;">
                    </div>
                </div>
                <input type="hidden" name="update_vehicle" value="1">
                <button type="submit" class="form-submit">Update Vehicle</button>
            </form>
        </div>
    </div>

    <!-- Delete Vehicle Confirmation Modal -->
    <div id="deleteVehicleModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteVehicleModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the vehicle: <span id="deleteVehicleName"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('deleteVehicleModal')">Cancel</button>
                <form id="deleteVehicleForm" method="POST">
                    <input type="hidden" id="deleteVehicleId" name="delete_vehicle_id">
                    <input type="hidden" name="delete_vehicle" value="1">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Team Member Modal -->
    <div id="teamMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Team Member</h2>
                <span class="close-btn" onclick="closeModal('teamMemberModal')">&times;</span>
            </div>
            <form id="teamMemberForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="memberName">Full Name</label>
                    <input type="text" id="memberName" name="memberName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="memberPosition">Position</label>
                    <input type="text" id="memberPosition" name="memberPosition" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="memberBio">Bio/Description</label>
                    <textarea id="memberBio" name="memberBio" class="form-control" required>Experienced travel professional with a passion for creating unforgettable adventures. Expert in Sri Lanka tourism and committed to exceptional customer service.</textarea>
                </div>
                <div class="form-group">
                    <label for="memberImage">Profile Image (Recommended size: 500x500px)</label>
                    <input type="file" id="memberImage" name="memberImage" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="memberFacebook">Facebook URL (Optional)</label>
                    <input type="url" id="memberFacebook" name="memberFacebook" class="form-control" placeholder="https://facebook.com/username">
                </div>
                <div class="form-group">
                    <label for="memberTwitter">Twitter URL (Optional)</label>
                    <input type="url" id="memberTwitter" name="memberTwitter" class="form-control" placeholder="https://twitter.com/username">
                </div>
                <div class="form-group">
                    <label for="memberInstagram">Instagram URL (Optional)</label>
                    <input type="url" id="memberInstagram" name="memberInstagram" class="form-control" placeholder="https://instagram.com/username">
                </div>
                <div class="form-group">
                    <label for="memberLinkedin">LinkedIn URL (Optional)</label>
                    <input type="url" id="memberLinkedin" name="memberLinkedin" class="form-control" placeholder="https://linkedin.com/in/username">
                </div>
                <input type="hidden" name="add_team_member" value="1">
                <button type="submit" class="form-submit">Add Team Member</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Team Member Modal -->
    <div id="editTeamMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Team Member</h2>
                <span class="close-btn" onclick="closeModal('editTeamMemberModal')">&times;</span>
            </div>
            <form id="editTeamMemberForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="member_id" name="member_id">
                <div class="form-group">
                    <label for="editMemberName">Full Name</label>
                    <input type="text" id="editMemberName" name="editMemberName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editMemberPosition">Position</label>
                    <input type="text" id="editMemberPosition" name="editMemberPosition" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editMemberBio">Bio/Description</label>
                    <textarea id="editMemberBio" name="editMemberBio" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editMemberImage">Profile Image (Leave empty to keep current image)</label>
                    <input type="file" id="editMemberImage" name="editMemberImage" class="form-control" accept="image/*">
                    <div id="currentMemberImage" style="margin-top: 10px; display: none;">
                        <p>Current Image:</p>
                        <img id="memberImagePreview" src="" alt="Current Team Member Image" style="max-width: 150px; max-height: 150px; border-radius: 50%;">
                    </div>
                </div>
                <div class="form-group">
                    <label for="editMemberFacebook">Facebook URL (Optional)</label>
                    <input type="url" id="editMemberFacebook" name="editMemberFacebook" class="form-control" placeholder="https://facebook.com/username">
                </div>
                <div class="form-group">
                    <label for="editMemberTwitter">Twitter URL (Optional)</label>
                    <input type="url" id="editMemberTwitter" name="editMemberTwitter" class="form-control" placeholder="https://twitter.com/username">
                </div>
                <div class="form-group">
                    <label for="editMemberInstagram">Instagram URL (Optional)</label>
                    <input type="url" id="editMemberInstagram" name="editMemberInstagram" class="form-control" placeholder="https://instagram.com/username">
                </div>
                <div class="form-group">
                    <label for="editMemberLinkedin">LinkedIn URL (Optional)</label>
                    <input type="url" id="editMemberLinkedin" name="editMemberLinkedin" class="form-control" placeholder="https://linkedin.com/in/username">
                </div>
                <input type="hidden" name="update_team_member" value="1">
                <button type="submit" class="form-submit">Update Team Member</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Team Member Confirmation Modal -->
    <div id="deleteTeamMemberModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteTeamMemberModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the team member: <span id="deleteTeamMemberName"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('deleteTeamMemberModal')">Cancel</button>
                <form id="deleteTeamMemberForm" method="POST">
                    <input type="hidden" id="deleteTeamMemberId" name="delete_member_id">
                    <input type="hidden" name="delete_team_member" value="1">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Users Modal -->
    <div id="usersModal" class="modal">
        <div class="modal-content users-modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-users" style="margin-right: 10px; color: var(--primary-color);"></i>Registered Users</h2>
                <span class="close-btn" onclick="closeModal('usersModal')">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (count($users) > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td>@<?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php 
                                            $created_date = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';
                                            echo $created_date;
                                        ?>
                                    </td>
                                    <td>
                                        <a href="user_messages.php?user_id=<?php echo $user['user_id']; ?>" class="view-btn user-action-btn">
                                            <i class="fas fa-comments"></i> Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px;">
                        <i class="fas fa-user-slash" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p style="font-size: 18px; color: #666;">No registered users found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Function to check table structure (can be called on page load)
        function checkPackageDetailsTable() {
            // Simple request to test table structure
            fetch('get_package_details.php?id=1')
                .then(response => response.json())
                .catch(error => {
                    console.log('Pre-checking package details structure failed, but that\'s fine');
                });
        }
        
        // Check if vehicle tables exist
        function checkVehicleTables() {
            // Simple request to test if vehicle sub-tables exist
            fetch('check_vehicle_tables.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.tables_exist) {
                        if (confirm('Vehicle detail tables need to be created. Would you like to set them up now?')) {
                            window.location.href = 'vehicle_tables_setup.php';
                        }
                    }
                })
                .catch(error => {
                    console.log('Error checking vehicle tables');
                });
        }
        
        // Call this on page load to pre-check the table structure
        document.addEventListener('DOMContentLoaded', function() {
            checkPackageDetailsTable();
            checkVehicleTables();
            
            // Setup dropdown menu
            var dropdown = document.querySelector('.dropdown-btn');
            dropdown.addEventListener("click", function() {
                this.classList.toggle("active");
                var dropdownContent = document.querySelector(".dropdown-container");
                if (dropdownContent.style.display === "block") {
                    dropdownContent.style.display = "none";
                } else {
                    dropdownContent.style.display = "block";
                }
            });
            
            // Mobile navigation toggle
            const mobileNavToggle = document.getElementById('mobileNavToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileNavToggle && sidebar) {
                mobileNavToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    this.innerHTML = sidebar.classList.contains('active') ? 
                        '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const windowWidth = window.innerWidth;
                if (windowWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && event.target !== mobileNavToggle) {
                        sidebar.classList.remove('active');
                        if (mobileNavToggle) {
                            mobileNavToggle.innerHTML = '<i class="fas fa-bars"></i>';
                        }
                    }
                }
            });
        });

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

        // Vehicle operations
        function editVehicle(vehicleId) {
            // Find the vehicle data from PHP array
            <?php if (!empty($vehicles)): ?>
            const vehicles = <?php echo json_encode($vehicles); ?>;
            const vehicle = vehicles.find(v => v.vehicle_id == vehicleId);
            
            if (vehicle) {
                document.getElementById('vehicle_id').value = vehicle.vehicle_id;
                document.getElementById('editVehicleType').value = vehicle.type;
                document.getElementById('editVehicleName').value = vehicle.name;
                document.getElementById('editVehicleDescription').value = vehicle.description;
                document.getElementById('editVehicleCapacity').value = vehicle.capacity;
                document.getElementById('editVehiclePrice').value = vehicle.price_per_day;
                document.getElementById('editVehicleAvailable').checked = vehicle.available == 1;
                
                // Show current image if available
                if (vehicle.image) {
                    document.getElementById('currentVehicleImage').style.display = 'block';
                    document.getElementById('vehicleImagePreview').src = '../images/' + vehicle.image;
                } else {
                    document.getElementById('currentVehicleImage').style.display = 'none';
                }
                
                openModal('editVehicleModal');
            }
            <?php endif; ?>
        }

        function deleteVehicle(vehicleId, vehicleName) {
            document.getElementById('deleteVehicleId').value = vehicleId;
            document.getElementById('deleteVehicleName').textContent = vehicleName;
            openModal('deleteVehicleModal');
        }
        
        // Team Member operations
        function editTeamMember(memberId) {
            // Find the team member data from PHP array
            <?php if (!empty($team_members)): ?>
            const teamMembers = <?php echo json_encode($team_members); ?>;
            const member = teamMembers.find(m => m.id == memberId);
            
            if (member) {
                document.getElementById('member_id').value = member.id;
                document.getElementById('editMemberName').value = member.name;
                document.getElementById('editMemberPosition').value = member.position;
                document.getElementById('editMemberBio').value = member.bio || 'Experienced travel professional with a passion for creating unforgettable adventures. Expert in Sri Lanka tourism and committed to exceptional customer service.';
                document.getElementById('editMemberFacebook').value = member.facebook || '';
                document.getElementById('editMemberTwitter').value = member.twitter || '';
                document.getElementById('editMemberInstagram').value = member.instagram || '';
                document.getElementById('editMemberLinkedin').value = member.linkedin || '';
                
                // Show current image if available
                if (member.image) {
                    document.getElementById('currentMemberImage').style.display = 'block';
                    document.getElementById('memberImagePreview').src = '../images/' + member.image;
                } else {
                    document.getElementById('currentMemberImage').style.display = 'none';
                }
                
                openModal('editTeamMemberModal');
            }
            <?php endif; ?>
        }

        function deleteTeamMember(memberId, memberName) {
            document.getElementById('deleteTeamMemberId').value = memberId;
            document.getElementById('deleteTeamMemberName').textContent = memberName;
            openModal('deleteTeamMemberModal');
        }

        // Package operations
        function viewPackage(packageId) {
            openModal('viewPackageModal');
            
            // Show loading message
            document.getElementById('packageDetails').innerHTML = '<div class="loading">Loading package details...</div>';
            
            // AJAX call to get package details
            fetch('get_package_details.php?id=' + packageId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    // Debug the raw response
                    return response.text().then(text => {
                        try {
                            // Try to parse as JSON
                            return JSON.parse(text);
                        } catch (e) {
                            // If parsing fails, log the raw response and throw error
                            console.error('Invalid JSON response:', text);
                            throw new Error('Invalid JSON response from server. See console for details.');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('packageDetails').innerHTML = data.html;
                    } else {
                        // If error contains "detail_value", try the fallback
                        if (data.message && data.message.includes('detail_value')) {
                            console.log('Trying fallback due to detail_value error');
                            tryFallbackView(packageId);
                        } else {
                            document.getElementById('packageDetails').innerHTML = '<p class="error">Error: ' + data.message + '</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching package details:', error);
                    // Try the fallback on error
                    tryFallbackView(packageId);
                });
        }
        
        function tryFallbackView(packageId) {
            // Show loading message
            document.getElementById('packageDetails').innerHTML = '<div class="loading">Trying alternative method...</div>';
            
            fetch('get_package_details_fallback.php?id=' + packageId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('packageDetails').innerHTML = data.html;
                    } else {
                        document.getElementById('packageDetails').innerHTML = '<p class="error">Error: ' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching fallback package details:', error);
                    document.getElementById('packageDetails').innerHTML = 
                        '<p class="error">Error: ' + error.message + '</p>' +
                        '<p>Please run the <a href="create_package_details.php" target="_blank">database setup</a> to fix this issue.</p>';
                });
        }

        function editPackage(packageId) {
            // Redirect to edit page
            window.location.href = 'edit_package.php?id=' + packageId;
        }

        function deletePackage(packageId, packageName) {
            document.getElementById('deletePackageId').value = packageId;
            document.getElementById('deletePackageName').textContent = packageName;
            openModal('deleteConfirmModal');
        }

        // Show success message if present in URL
        window.onload = function() {
            // This function has been replaced by the DOMContentLoaded event listener below
            // to avoid duplicate functionality
        }
        
        // Reviews filtering functionality
        function initReviewFilters() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const reviewRows = document.querySelectorAll('.review-row');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    
                    // Show/hide rows based on filter
                    reviewRows.forEach(row => {
                        if (filter === 'all') {
                            row.style.display = '';
                        } else {
                            if (row.classList.contains(filter)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                });
            });
        }
        
        // View full review functionality
        function initViewFullReview() {
            const viewLinks = document.querySelectorAll('.view-full-review');
            
            viewLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const reviewText = this.getAttribute('data-review');
                    
                    // Create and show modal with full review
                    const modal = document.createElement('div');
                    modal.className = 'modal';
                    modal.style.display = 'block';
                    
                    modal.innerHTML = `
                        <div class="modal-content" style="max-width: 600px;">
                            <div class="modal-header">
                                <h2>Full Review</h2>
                                <span class="close-btn" onclick="this.parentNode.parentNode.parentNode.remove()">&times;</span>
                            </div>
                            <div class="modal-body" style="padding: 20px;">
                                <p style="line-height: 1.6;">${reviewText}</p>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Close when clicking outside
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            this.remove();
                        }
                    });
                });
            });
        }
    </script>

    <!-- jQuery for general functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom Alert Container -->
    <div class="custom-alert-overlay" id="customAlertOverlay">
        <div class="custom-alert" id="customAlert">
            <div class="alert-header" id="alertHeader">
                <div class="alert-icon" id="alertIcon">
                    <i class="fas fa-check"></i>
                </div>
                <h3 id="alertTitle">Success</h3>
            </div>
            <div class="alert-body" id="alertMessage">
                Operation completed successfully!
            </div>
            <div class="alert-footer">
                <button class="alert-btn alert-btn-primary" id="alertOkBtn">OK</button>
            </div>
        </div>
    </div>

    <!-- Custom Alert JavaScript -->
    <script>
        // Custom alert function
        function showCustomAlert(type, title, message) {
            const overlay = document.getElementById('customAlertOverlay');
            const alert = document.getElementById('customAlert');
            const alertHeader = document.getElementById('alertHeader');
            const alertIcon = document.getElementById('alertIcon');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');
            const alertOkBtn = document.getElementById('alertOkBtn');
            
            // Reset classes
            alert.className = 'custom-alert';
            
            // Set type-specific styles
            let iconClass = 'fas fa-check';
            
            switch(type) {
                case 'success':
                    alert.classList.add('alert-success');
                    iconClass = 'fas fa-check';
                    break;
                case 'warning':
                    alert.classList.add('alert-warning');
                    iconClass = 'fas fa-exclamation-triangle';
                    break;
                case 'danger':
                    alert.classList.add('alert-danger');
                    iconClass = 'fas fa-times';
                    break;
                case 'info':
                    alert.classList.add('alert-info');
                    iconClass = 'fas fa-info';
                    break;
            }
            
            // Set content
            alertIcon.innerHTML = `<i class="${iconClass}"></i>`;
            alertTitle.textContent = title;
            alertMessage.textContent = message;
            
            // Show alert
            overlay.classList.add('active');
            
            // Close alert on button click
            alertOkBtn.onclick = function() {
                overlay.classList.remove('active');
            };
            
            // Close alert when clicking on overlay
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            };
        }
        
        // Prevent click event propagation on the alert itself
        document.getElementById('customAlert').addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Review alerts
        function showReviewApprovedAlert() {
            showCustomAlert('success', 'Review Approved', 'The review has been successfully approved and will now be visible on the website.');
        }
        
        function showReviewRejectedAlert() {
            showCustomAlert('warning', 'Review Rejected', 'The review has been rejected and will not be displayed on the website.');
        }
        
        function showReviewDeletedAlert() {
            showCustomAlert('danger', 'Review Deleted', 'The review has been permanently deleted from the database.');
        }
        
        // Replace window.onload with better event listener
        document.addEventListener('DOMContentLoaded', function() {
            // Check for success messages in URL
            const urlParams = new URLSearchParams(window.location.search);
            const successParam = urlParams.get('success');
            const dbFixParam = urlParams.get('db_fix');
            const viewPackageParam = urlParams.get('view_package');
            const errorParam = urlParams.get('error');
            
            // Show custom alerts for review operations
            if (successParam === 'review_approved') {
                showReviewApprovedAlert();
            } else if (successParam === 'review_rejected') {
                showReviewRejectedAlert();
            } else if (successParam === 'review_deleted') {
                showReviewDeletedAlert();
            } else if (successParam === 'package_added') {
                showCustomAlert('success', 'Package Added', 'Tour package added successfully!');
            } else if (successParam === 'one_day_tour_added') {
                showCustomAlert('success', 'Tour Added', 'One Day Tour package added successfully!');
            } else if (successParam === 'special_tour_added') {
                showCustomAlert('success', 'Tour Added', 'Special Tour package added successfully!');
            } else if (successParam === 'package_updated') {
                showCustomAlert('success', 'Package Updated', 'Package updated successfully!');
            } else if (successParam === 'package_deleted') {
                showCustomAlert('danger', 'Package Deleted', 'Package deleted successfully!');
            } else if (successParam === 'column_added') {
                showCustomAlert('success', 'Database Updated', 'Database structure updated successfully! Destination support added.');
            } else if (successParam === 'vehicle_added') {
                showCustomAlert('success', 'Vehicle Added', 'Vehicle added successfully!');
            } else if (successParam === 'vehicle_updated') {
                showCustomAlert('success', 'Vehicle Updated', 'Vehicle updated successfully!');
            } else if (successParam === 'vehicle_deleted') {
                showCustomAlert('danger', 'Vehicle Deleted', 'Vehicle deleted successfully!');
            } else if (successParam === 'team_member_added') {
                showCustomAlert('success', 'Team Member Added', 'Team member added successfully!');
            } else if (successParam === 'team_member_updated') {
                showCustomAlert('success', 'Team Member Updated', 'Team member updated successfully!');
            } else if (successParam === 'team_member_deleted') {
                showCustomAlert('danger', 'Team Member Deleted', 'Team member deleted successfully!');
            }
            
            if (errorParam === 'column_failed') {
                showCustomAlert('danger', 'Database Error', 'Error updating database structure. Please contact the administrator.');
            } else if (errorParam === 'invalid_type') {
                showCustomAlert('danger', 'Error', 'Error: Invalid tour type selected.');
            } else if (errorParam === 'type_not_found') {
                showCustomAlert('danger', 'Error', 'Error: Tour type not found.');
            }
            
            // Handle database fix messages
            if (dbFixParam === 'success') {
                showCustomAlert('success', 'Database Setup Complete', 'Database setup completed successfully! Package details should now display correctly.');
            } else if (dbFixParam === 'error') {
                showCustomAlert('danger', 'Database Error', 'There was an issue with the database setup. Please contact the administrator.');
            }
            
            // Auto-open package view if specified
            if (viewPackageParam && !isNaN(viewPackageParam)) {
                viewPackage(parseInt(viewPackageParam));
            }
            
            // Initialize review filter functionality
            initReviewFilters();
            
            // Initialize view full review functionality
            initViewFullReview();
            
            // Check for new messages every 30 seconds
            setInterval(function() {
                // Use fetch to check for new messages without reloading the page
                fetch('message_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_unread_count'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const unreadCount = parseInt(data.data);
                        
                        // Update header notification badge
                        const messageBadge = document.querySelector('.message-badge');
                        if (messageBadge) {
                            if (unreadCount > 0) {
                                messageBadge.textContent = unreadCount;
                                messageBadge.style.display = 'flex';
                            } else {
                                messageBadge.style.display = 'none';
                            }
                        }
                        
                        // Update sidebar notification badge
                        const sidebarBadge = document.querySelector('.sidebar-menu .badge');
                        if (sidebarBadge) {
                            if (unreadCount > 0) {
                                sidebarBadge.textContent = unreadCount;
                                sidebarBadge.style.display = 'inline-flex';
                            } else {
                                sidebarBadge.style.display = 'none';
                            }
                        }
                        
                        // Optional: Play notification sound if new messages since last check
                        if (window.lastUnreadCount !== undefined && unreadCount > window.lastUnreadCount) {
                            // You could add a sound notification here
                            console.log('New messages received!');
                        }
                        
                        // Store current count for next comparison
                        window.lastUnreadCount = unreadCount;
                    }
                })
                .catch(error => console.error('Error checking messages:', error));
                
                // Check for new contact messages
                fetch('contact_messages_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_unread_count'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const unreadContactCount = parseInt(data.data);
                        
                        // Update contact messages notification badge in sidebar
                        const contactBadge = document.querySelector('.sidebar-menu li a[href="contact_messages.php"] .badge');
                        const contactMenuItem = document.querySelector('.sidebar-menu li a[href="contact_messages.php"]');
                        
                        if (unreadContactCount > 0) {
                            // Create badge if it doesn't exist
                            if (!contactBadge) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'badge';
                                newBadge.style.backgroundColor = 'rgb(255, 0, 0)';
                                newBadge.style.color = 'white';
                                newBadge.textContent = unreadContactCount;
                                contactMenuItem.appendChild(newBadge);
                            } else {
                                contactBadge.textContent = unreadContactCount;
                                contactBadge.style.display = 'inline-flex';
                            }
                            
                            // Optional: Play notification sound if new contact messages since last check
                            if (window.lastContactCount !== undefined && unreadContactCount > window.lastContactCount) {
                                console.log('NewContact Messages received!');
                            }
                        } else if (contactBadge) {
                            contactBadge.style.display = 'none';
                        }
                        
                        // Store current count for next comparison
                        window.lastContactCount = unreadContactCount;
                    }
                })
                .catch(error => console.error('Error checking contact messages:', error));
            }, 30000); // Check every 30 seconds
            
            // Initial check on page load
            fetch('message_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_unread_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.lastUnreadCount = parseInt(data.data);
                }
            })
            .catch(error => console.error('Error on initial message check:', error));
            
            // Initial check for contact messages
            fetch('contact_messages_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_unread_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.lastContactCount = parseInt(data.data);
                }
            })
            .catch(error => console.error('Error on initial contact message check:', error));
        });
    </script>
</body>
</html>
