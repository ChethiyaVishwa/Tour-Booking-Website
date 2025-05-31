<?php
// Start the session if one doesn't exist already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration from admin folder
require_once '../admin/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['full_name'] : '';
$username = $is_logged_in ? $_SESSION['username'] : '';

// Handle logout
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../login.php");
    exit;
}

// Get destination ID from url
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: destinations.php");
    exit;
}

$destination_id = intval($_GET['id']);

// Check if a specific hotel is selected
$selected_hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : null;
$selected_hotel = null;
$hotel_sub_images = [];
$hotel_sub_details = [];

// If a hotel is selected, get its details and sub items
if ($selected_hotel_id) {
    $hotel_query = "SELECT h.*, d.name as destination_name 
                   FROM hotels h 
                   LEFT JOIN destinations d ON h.destination_id = d.destination_id 
                   WHERE h.hotel_id = ?";
    $hotel_stmt = mysqli_prepare($conn, $hotel_query);
    mysqli_stmt_bind_param($hotel_stmt, "i", $selected_hotel_id);
    mysqli_stmt_execute($hotel_stmt);
    $hotel_result = mysqli_stmt_get_result($hotel_stmt);
    
    if (mysqli_num_rows($hotel_result) > 0) {
        $selected_hotel = mysqli_fetch_assoc($hotel_result);
        
        // Get hotel sub-images
        $sub_images_query = "SELECT * FROM hotel_sub_images WHERE hotel_id = ? ORDER BY created_at DESC";
        $sub_images_stmt = mysqli_prepare($conn, $sub_images_query);
        mysqli_stmt_bind_param($sub_images_stmt, "i", $selected_hotel_id);
        mysqli_stmt_execute($sub_images_stmt);
        $sub_images_result = mysqli_stmt_get_result($sub_images_stmt);
        
        while ($sub_image = mysqli_fetch_assoc($sub_images_result)) {
            $hotel_sub_images[] = $sub_image;
        }
        
        // Get hotel sub-details
        $sub_details_query = "SELECT * FROM hotel_sub_details WHERE hotel_id = ? ORDER BY order_num ASC";
        $sub_details_stmt = mysqli_prepare($conn, $sub_details_query);
        mysqli_stmt_bind_param($sub_details_stmt, "i", $selected_hotel_id);
        mysqli_stmt_execute($sub_details_stmt);
        $sub_details_result = mysqli_stmt_get_result($sub_details_stmt);
        
        while ($sub_detail = mysqli_fetch_assoc($sub_details_result)) {
            $hotel_sub_details[] = $sub_detail;
        }
    }
}

// Query to fetch destination details
$query = "SELECT * FROM destinations WHERE destination_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $destination_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if destination exists
if (mysqli_num_rows($result) == 0) {
    header("Location: destinations.php");
    exit;
}

$destination = mysqli_fetch_assoc($result);

// Fetch sub-destinations
$sub_query = "SELECT * FROM destination_sub_images WHERE destination_id = ?";
$sub_stmt = mysqli_prepare($conn, $sub_query);
mysqli_stmt_bind_param($sub_stmt, "i", $destination_id);
mysqli_stmt_execute($sub_stmt);
$sub_result = mysqli_stmt_get_result($sub_stmt);
$sub_destinations = [];
while ($sub = mysqli_fetch_assoc($sub_result)) {
    $sub_destinations[] = $sub;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($destination['name']); ?> | Adventure Travel.lk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            --text-color: #333;
            --bg-color: #f0f2f5;
            --bg-alt-color: #f8f9fa;
            --card-bg: white;
            --header-bg: rgb(0, 255, 204);
            --footer-bg: #333;
            --footer-text: #fff;
            --card-shadow: rgba(0, 0, 0, 0.1);
            --border-color: rgb(23, 15, 132);
            --card-alt-bg: #f9f9f9;
            --border-alt-color: #eee;
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(0, 204, 163);
            --text-color: #f0f0f0;
            --bg-color: #121212;
            --bg-alt-color: #1e1e1e;
            --card-bg: #2d2d2d;
            --card-alt-bg: #252525;
            --header-bg: rgb(20, 170, 145);
            --footer-bg: #222;
            --footer-text: #ddd;
            --card-shadow: rgba(0, 0, 0, 0.5);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: #333;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--header-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 10px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            border-bottom: 3px solid var(--border-color);
            transition: transform 0.3s ease, background 0.5s ease, border-color 0.5s ease;
        }

        .header.hide {
            transform: translateY(-100%);
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 50px;
            margin-right: 10px;
        }

        .navbar a {
            font-size: 16px;
            color: var(--text-color);
            text-decoration: none;
            margin-left: 25px;
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .navbar a:hover {
            color:rgb(255, 0, 0);
        }
        
        .hotels-alert {
            font-size: 0.9rem;
            color: var(--primary-color);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .hotels-alert i {
            font-size: 1rem;
            color: var(--primary-color);
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: var(--text-color);
            transition: color 0.3s ease;
        }


        /* User profile dropdown - Modern minimalist style */
        .user-dropdown {
            display: inline-block;
            position: relative;
            margin-left: 25px;
        }

        .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.7);
            background: var(--primary-color);
        }

        .profile-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-dropdown {
            position: absolute;
            top: 120%;
            right: -10px;
            width: 240px;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transform-origin: top right;
            transform: scale(0.95);
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            z-index: 1001;
            overflow: hidden;
        }

        .profile-dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 22px;
            width: 12px;
            height: 12px;
            background: var(--card-bg);
            transform: rotate(45deg);
            box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.04);
        }

        .user-dropdown.active .profile-dropdown {
            transform: scale(1);
            opacity: 1;
            visibility: visible;
        }

        .menu-section {
            padding: 12px;
        }

        .user-brief {
            display: flex;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 8px;
        }

        .user-brief .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 12px;
            flex-shrink: 0;
            border: 2px solid var(--primary-color);
        }

        .user-brief .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-brief .user-meta {
            overflow: hidden;
        }

        .user-brief .name {
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-brief .username {
            color: #666;
            font-size: 0.8rem;
            margin: 0;
        }

        .menu-section .menu-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.2s;
        }

        .menu-section .menu-item:hover {
            color: var(--primary-color);
        }

        .menu-item .icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 1rem;
        }

        .menu-item.logout {
            color: #dc3545;
        }

        .menu-item.logout .icon {
            color: #dc3545;
        }

        .account-actions {
            background: var(--bg-alt-color);
            border-top: 1px solid var(--border-alt-color);
            display: flex;
        }

        .account-actions a {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.85rem;
            transition: background 0.2s;
        }

        .account-actions a:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .dark-mode .account-actions a:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .account-actions a:first-child {
            border-right: 1px solid var(--border-alt-color);
        }

        /* Destination Detail Styles */
        .hero-section {
            background-size: cover;
            background-position: center;
            height: 50vh;
            color: #fff;
            position: relative;
            margin-top: 70px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            padding: 50px 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .destination-title {
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .destination-subtitle {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .destination-back {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 2;
            background-color: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            padding: 8px 15px;
            border-radius: 25px;
            text-decoration: none;
            backdrop-filter: blur(5px);
            border: 1px solid var(--primary-color);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .destination-back:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .destination-back i {
            margin-right: 6px;
            font-size: 0.9rem;
        }

        .detail-content {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--card-shadow);
            padding: 40px;
            margin-top: -50px;
            position: relative;
            z-index: 2;
            margin-bottom: 40px;
        }

        .detail-section {
            margin-bottom: 40px;
        }

        .detail-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-alt-color);
        }

        .destination-description {
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .sub-destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .sub-destination-card {
            background-color: var(--card-alt-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .sub-destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px var(--card-shadow);
        }

        .sub-destination-image {
            height: 200px;
            overflow: hidden;
        }

        .sub-destination-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .sub-destination-card:hover .sub-destination-image img {
            transform: scale(1.1);
        }

        .sub-destination-content {
            padding: 20px;
        }

        .sub-destination-name {
            font-size: 1.3rem;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .sub-destination-description {
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Hotel Styles */
        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .hotel-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-alt-color);
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px var(--card-shadow);
        }

        .hotel-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .hotel-card:hover .hotel-image img {
            transform: scale(1.1);
        }

        .hotel-rating {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.6);
            color: gold;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .hotel-content {
            padding: 20px;
        }

        .hotel-name {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .hotel-description {
            color: var(--text-color);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .hotel-price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .cta-section {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 60px 0;
            margin-top: 40px;
            border-radius: 10px;
        }

        .cta-title {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .cta-subtitle {
            font-size: 1.1rem;
            margin-bottom: 25px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            display: inline-block;
            background-color: white;
            color: var(--primary-color);
            font-weight: bold;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .cta-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Related destinations */
        .related-destinations {
            margin-top: 60px;
        }

        /* Footer */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0;
            text-align: center;
            margin-top: 60px;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .footer-section {
            flex: 1;
            min-width: 300px;
            margin-bottom: 20px;
        }

        .footer-section h3 {
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .footer-section p, .footer-section ul {
            color: #bbb;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: var(--secondary-color);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 20px;
            text-align: center;
            color: #bbb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .navbar {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--card-bg);
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                padding: 0;
                clip-path: polygon(0 0, 100% 0, 100% 0, 0 0);
                transition: 0.5s ease;
                display: block; /* Added for proper mobile display */
            }
            
            .navbar.active {
                clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
                background-color: var(--bg-color);
                padding-bottom: 15px;
                z-index: 1000;
                overflow-y: auto;
                max-height: 85vh;
            }
            
            .navbar a {
                display: block;
                margin: 15px 0;
                padding: 15px 30px;
                font-size: 20px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .detail-content {
                padding: 25px;
            }

            .destination-title {
                font-size: 2.2rem;
            }

            .sub-destinations-grid {
                grid-template-columns: 1fr;
            }

            .hotels-grid {
                grid-template-columns: 1fr;
            }

            .hotel-image {
                height: 180px;
            }
            
            .hotel-detail-main {
                flex-direction: column;
            }
            
            .hotel-detail-image {
                height: 200px;
            }
            
            .back-to-hotels {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .back-to-hotels i {
                font-size: 0.8rem;
            }
            
            .hotel-detail-header h2 {
                font-size: 1.2rem;
                margin-top: 5px;
            }

            .destination-back {
                top: 15px;
                left: 15px;
                padding: 6px 12px;
                font-size: 0.85rem;
            }
        }

        /* Hotel Detail View Styles */
        .hotel-detail-view {
            margin-bottom: 30px;
        }

        .hotel-detail-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-to-hotels {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            text-decoration: none;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        .back-to-hotels:hover {
            background-color: #124d47;
            transform: translateX(-5px);
        }

        .back-to-hotels i {
            margin-right: 5px;
        }

        .hotel-detail-main {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--card-shadow);
        }

        .hotel-detail-image {
            flex: 1;
            min-width: 300px;
            height: 280px;
        }

        .hotel-detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hotel-detail-info {
            flex: 1;
            min-width: 300px;
            padding: 30px;
        }

        .hotel-detail-name {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .hotel-detail-rating {
            display: flex;
            margin-bottom: 10px;
            color: gold;
        }

        .hotel-detail-meta {
            display: flex;
            flex-wrap: wrap;
        }

        .hotel-detail-meta span {
            background-color: var(--bg-alt-color);
            padding: 5px 12px;
            border-radius: 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            color: var(--text-color);
        }

        .hotel-detail-meta span i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        .hotel-detail-description {
            line-height: 1.7;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .hotel-detail-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Hotel Sub Images */
        .hotel-sub-images {
            margin-bottom: 30px;
        }

        .hotel-sub-images-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .hotel-sub-images-title i {
            margin-right: 10px;
        }

        .hotel-sub-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .hotel-sub-image-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .hotel-sub-image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .hotel-sub-image {
            height: 180px;
            overflow: hidden;
        }

        .hotel-sub-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            cursor: pointer;
        }

        .hotel-sub-image-card:hover .hotel-sub-image img {
            transform: scale(1.1);
        }

        .hotel-sub-image-content {
            padding: 15px;
            background-color: var(--card-bg);
        }

        .hotel-sub-image-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-color);
            font-size: 1rem;
        }

        /* Hotel Sub Details */
        .hotel-sub-details {
            margin-bottom: 30px;
        }

        .hotel-sub-details-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .hotel-sub-details-title i {
            margin-right: 10px;
        }

        .hotel-sub-details-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .hotel-sub-detail-item {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 8px var(--card-shadow);
            display: flex;
            transition: transform 0.3s ease;
        }

        .hotel-sub-detail-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px var(--card-shadow);
        }

        .hotel-sub-detail-icon {
            margin-right: 15px;
            min-width: 40px;
            font-size: 1.5rem;
            color: var(--primary-color);
            text-align: center;
        }

        .hotel-sub-detail-content {
            flex: 1;
        }

        .hotel-sub-detail-header {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .hotel-sub-detail-text {
            color: var(--text-color);
        }

        /* Adding style for hotel-sub-image-description */
        .hotel-sub-image-description {
            color: var(--text-color);
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        /* Lightbox Styles */
        .lightbox-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            margin: auto;
            display: block;
            cursor: default;
        }
        
        .lightbox-content img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            display: block;
            margin: auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            border-radius: 5px;
        }
        
        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .lightbox-close:hover {
            color: var(--secondary-color);
        }

        /* Theme toggle button - Stylish switch design */
        .theme-toggle {
            position: fixed;
            left: 20px;
            top: 180px; /* Moved further down */
            z-index: 999;
            width: 60px;
            height: 30px;
            border-radius: 15px;
            background: linear-gradient(to right, #2c3e50, #4ca1af);
            border: none;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            padding: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .theme-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .theme-toggle {
            background: linear-gradient(to right, #4ca1af, #2c3e50);
        }

        .toggle-handle {
            position: absolute;
            left: 5px;
            width: 20px;
            height: 20px;
            background-color: #fff;
            border-radius: 50%;
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transform: translateX(0);
        }

        .dark-mode .toggle-handle {
            transform: translateX(30px);
            background-color: #222;
        }

        .toggle-icons {
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 7px;
            box-sizing: border-box;
            pointer-events: none;
        }

        .toggle-icons i {
            font-size: 12px;
            color: #fff;
            z-index: 1;
        }

        /* Media queries for responsive design */
        @media (max-width: 991px) {
            .theme-toggle {
                top: 170px; /* Moved further down */
                left: 20px;
                width: 54px;
                height: 28px;
                border-radius: 14px;
            }
            
            .toggle-handle {
                width: 18px;
                height: 18px;
                left: 5px;
            }
            
            .dark-mode .toggle-handle {
                transform: translateX(26px);
            }
        }

        @media (max-width: 768px) {
            .theme-toggle {
                top: 150px; /* Moved further down */
                left: 15px;
                width: 50px;
                height: 26px;
                border-radius: 13px;
            }
            
            .toggle-handle {
                width: 18px;
                height: 18px;
                left: 4px;
            }
            
            .dark-mode .toggle-handle {
                transform: translateX(24px);
            }
            
            .toggle-icons {
                padding: 0 6px;
            }
            
            .toggle-icons i {
                font-size: 10px;
            }
        }

        @media (max-width: 576px) {
            .theme-toggle {
                top: 130px; /* Moved further down */
                left: 10px;
                width: 46px;
                height: 24px;
                border-radius: 12px;
            }
            
            .toggle-handle {
                width: 16px;
                height: 16px;
                left: 4px;
            }
            
            .dark-mode .toggle-handle {
                transform: translateX(22px);
            }
            
            .toggle-icons {
                padding: 0 5px;
            }
            
            .toggle-icons i {
                font-size: 9px;
            }
            
            /* Improved mobile menu styles */
            .navbar a {
                padding: 10px 20px;
                font-size: 17px;
                margin: 10px 0;
            }
            
            /* Make menu items more compact for mobile */
            .navbar {
                max-height: 80vh;
                overflow-y: auto;
            }
        }

        /* When page is scrolled and header is hidden */
        @media (max-height: 500px) {
            .theme-toggle {
                top: 70px;
            }
        }

        /* For landscape orientation on mobile */
        @media (max-height: 450px) and (orientation: landscape) {
            .theme-toggle {
                top: 70px;
                left: 10px;
            }
        }
        
        /* Page Alert Message Styles */
        .page-alert-message {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(4px);
        }
        
        .page-alert-message.show {
            opacity: 1;
            visibility: visible;
        }
        
        .alert-content {
            background-color: var(--card-bg);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.4s ease;
        }
        
        .page-alert-message.show .alert-content {
            transform: translateY(0);
        }
        
        .alert-header {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-close {
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .alert-close:hover {
            transform: scale(1.2);
        }
        
        .alert-body {
            padding: 20px;
        }
        
        .alert-body p {
            margin: 0 0 15px;
            line-height: 1.6;
        }
        
        .alert-body p:last-child {
            margin-bottom: 0;
        }
        
        @media (max-width: 576px) {
            .alert-content {
                width: 95%;
            }
            
            .alert-header {
                padding: 12px 15px;
            }
            
            .alert-header h3 {
                font-size: 1.1rem;
            }
            
            .alert-body {
                padding: 15px;
            }
        }

        /* Add these new styles */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .show-hotels-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .show-hotels-btn:hover {
            background-color: #124d47;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .show-hotels-btn i {
            font-size: 1.1rem;
        }

        .hotels-grid {
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .show-hotels-btn {
                width: 100%;
                justify-content: center;
            }
            
            .hotels-alert {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="theme-toggle" aria-label="Toggle Dark Mode">
        <div class="toggle-icons">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
        </div>
        <div class="toggle-handle"></div>
    </button>
    
    <header class="header">
        <a href="../index.php" class="logo">
            <img src="../images/logo-4.png" alt="Adventure Travel.lk Logo">
        </a>

        <div class="menu-toggle">☰</div>

        <nav class="navbar">
            <a href="../index.php">Home</a>
            <a href="../tour_packages/tour_packages.php">Tour Packages</a>
            <a href="../one_day_tour_packages/one_day_tour.php">One Day Tours</a>
            <a href="../special_tour_packages/special_tour.php">Special Tours</a>
            <a href="../index.php#vehicle-hire">Vehicle Hire</a>
            <a href="destinations.php">Destinations</a>
            <a href="../contact_us.php">Contact Us</a>
            <a href="../about_us/about_us.php">About Us</a>
            
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section" style="background-image: url('<?php echo htmlspecialchars($destination['image']); ?>');">
        <div class="container">
            <a href="destinations.php" class="destination-back">
                <i class="fas fa-arrow-left"></i> Back to Destinations
            </a>
            <div class="hero-content">
                <h1 class="destination-title"><?php echo htmlspecialchars($destination['name']); ?></h1>
                <p class="destination-subtitle">Explore the beauty and wonders of this amazing destination</p>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="detail-content">
            <?php if ($selected_hotel): ?>
            <!-- Hotel Detailed View -->
            <div class="hotel-detail-view">
                <div class="hotel-detail-header">
                    <a href="destination_detail.php?id=<?php echo $destination_id; ?>" class="back-to-hotels" id="backToHotels">
                        <i class="fas fa-arrow-left"></i> Back to All Hotels
                    </a>
                    <h2>Hotel Details</h2>
                </div>
                
                <!-- Main Hotel Info -->
                <div class="hotel-detail-main">
                    <div class="hotel-detail-image">
                        <img src="../images/<?php echo htmlspecialchars($selected_hotel['image']); ?>" alt="<?php echo htmlspecialchars($selected_hotel['name']); ?>">
                    </div>
                    <div class="hotel-detail-info">
                        <h1 class="hotel-detail-name"><?php echo htmlspecialchars($selected_hotel['name']); ?></h1>
                        
                        <?php if ($selected_hotel['star_rating']): ?>
                        <div class="hotel-detail-rating">
                            <?php for($i = 0; $i < $selected_hotel['star_rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="hotel-detail-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($selected_hotel['destination_name']); ?></span>
                            <?php if (isset($selected_hotel['address']) && $selected_hotel['address']): ?>
                            <span><i class="fas fa-location-arrow"></i> <?php echo htmlspecialchars($selected_hotel['address']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hotel-detail-description">
                            <?php echo nl2br(htmlspecialchars($selected_hotel['description'])); ?>
                        </div>
                        
                        <?php if ($selected_hotel['price_per_night'] > 0): ?>
                        <div class="hotel-detail-price">
                            From $<?php echo number_format($selected_hotel['price_per_night'], 2); ?> per night
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Hotel Sub-Images -->
                <?php if (!empty($hotel_sub_images)): ?>
                <div class="hotel-sub-images">
                    <h3 class="hotel-sub-images-title"><i class="fas fa-images"></i> Hotel Gallery</h3>
                    <div class="hotel-sub-images-grid">
                        <?php foreach ($hotel_sub_images as $sub_image): ?>
                        <div class="hotel-sub-image-card">
                            <div class="hotel-sub-image">
                                <img src="../images/<?php echo htmlspecialchars($sub_image['image']); ?>" alt="<?php echo htmlspecialchars($sub_image['title']); ?>">
                            </div>
                            <?php if ($sub_image['title'] || $sub_image['description']): ?>
                            <div class="hotel-sub-image-content">
                                <?php if ($sub_image['title']): ?>
                                <div class="hotel-sub-image-title"><?php echo htmlspecialchars($sub_image['title']); ?></div>
                                <?php endif; ?>
                                <?php if ($sub_image['description']): ?>
                                <div class="hotel-sub-image-description"><?php echo htmlspecialchars($sub_image['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Hotel Sub-Details -->
                <?php if (!empty($hotel_sub_details)): ?>
                <div class="hotel-sub-details">
                    <h3 class="hotel-sub-details-title"><i class="fas fa-list-ul"></i> Hotel Features</h3>
                    <div class="hotel-sub-details-list">
                        <?php foreach ($hotel_sub_details as $sub_detail): ?>
                        <div class="hotel-sub-detail-item">
                            <div class="hotel-sub-detail-icon">
                                <i class="<?php echo htmlspecialchars($sub_detail['icon']); ?>"></i>
                            </div>
                            <div class="hotel-sub-detail-content">
                                <div class="hotel-sub-detail-header"><?php echo htmlspecialchars($sub_detail['header']); ?></div>
                                <div class="hotel-sub-detail-text"><?php echo htmlspecialchars($sub_detail['content']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php else: ?>
            <div class="detail-section">
                <h2 class="section-title">About <?php echo htmlspecialchars($destination['name']); ?></h2>
                <div class="destination-description">
                    <?php echo nl2br(htmlspecialchars($destination['description'])); ?>
                </div>
            </div>

            <?php if (!empty($sub_destinations)): ?>
            <div class="detail-section">
                <h2 class="section-title">Explore Sub Destinations</h2>
                <div class="sub-destinations-grid">
                    <?php foreach ($sub_destinations as $sub): ?>
                        <div class="sub-destination-card">
                            <div class="sub-destination-image">
                                <img src="sub/<?php echo htmlspecialchars($sub['image']); ?>" alt="<?php echo htmlspecialchars($sub['name']); ?>">
                            </div>
                            <div class="sub-destination-content">
                                <h3 class="sub-destination-name"><?php echo htmlspecialchars($sub['name']); ?></h3>
                                <p class="sub-destination-description"><?php echo htmlspecialchars($sub['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // Fetch hotels for this destination
            $hotels_query = "SELECT * FROM hotels WHERE destination_id = ?";
            $hotels_stmt = mysqli_prepare($conn, $hotels_query);
            mysqli_stmt_bind_param($hotels_stmt, "i", $destination_id);
            mysqli_stmt_execute($hotels_stmt);
            $hotels_result = mysqli_stmt_get_result($hotels_stmt);
            $hotels = [];
            while ($hotel = mysqli_fetch_assoc($hotels_result)) {
                $hotels[] = $hotel;
            }
            
            if (!empty($hotels)): 
            ?>
            <div class="detail-section">
                <div class="section-header">
                    <h2 class="section-title">Where to Stay in <?php echo htmlspecialchars($destination['name']); ?></h2>
                    <button id="showHotelsBtn" class="show-hotels-btn">
                        <i class="fas fa-hotel"></i> Show Hotels
                    </button>
                    <div class="hotels-alert">
                        <i class="fas fa-info-circle"></i> Click the button to view available hotels
                    </div>
                </div>
                <div id="hotelsSection" class="hotels-grid" style="display: none;">
                    <?php foreach ($hotels as $hotel): ?>
                        <a href="destination_detail.php?id=<?php echo $destination_id; ?>&hotel_id=<?php echo $hotel['hotel_id']; ?>" class="hotel-card">
                            <div class="hotel-image">
                                <img src="../images/<?php echo htmlspecialchars($hotel['image']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                                <?php if ($hotel['star_rating']): ?>
                                <div class="hotel-rating">
                                    <?php for($i = 0; $i < $hotel['star_rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="hotel-content">
                                <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                <p class="hotel-description"><?php echo htmlspecialchars($hotel['description']); ?></p>
                                <?php if ($hotel['price_per_night'] > 0): ?>
                                <p class="hotel-price">From $<?php echo number_format($hotel['price_per_night'], 2); ?> per night</p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="cta-section">
                <h2 class="cta-title">Want to Visit <?php echo htmlspecialchars($destination['name']); ?>?</h2>
                <p class="cta-subtitle">Book one of our tour packages that include visits to this amazing destination</p>
                <a href="../tour_packages/tour_packages.php" class="cta-button">View Tour Packages</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>Adventure Travel.lk is a premier travel agency specializing in adventure tours and memorable experiences across Sri Lanka.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../tour_packages/tour_packages.php">Tour Packages</a></li>
                        <li><a href="../one_day_tour_packages/one_day_tour.php">One Day Tours</a></li>
                        <li><a href="../special_tour_packages/special_tour.php">Special Tours</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>Email: adventuretravel.lk@gmail.com</li>
                        <li>Phone: +94 71 862 8992</li>
                        <li>Address: Narammala, Kurunegala, Sri Lanka</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Adventure Travel.lk. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for responsive navigation and theme handling
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle functionality
            const menuToggle = document.querySelector('.menu-toggle');
            const navbar = document.querySelector('.navbar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navbar.classList.toggle('active');
                });
            }
            
            // Close navigation when a nav link is clicked
            document.querySelectorAll('.navbar a').forEach(link => {
                link.addEventListener('click', () => {
                    navbar.classList.remove('active');
                });
            });
            
            // User dropdown menu functionality
            const userDropdown = document.querySelector('.user-dropdown');
            if (userDropdown) {
                userDropdown.addEventListener('click', function(e) {
                    this.classList.toggle('active');
                    e.stopPropagation();
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    if (userDropdown.classList.contains('active')) {
                        userDropdown.classList.remove('active');
                    }
                });
            }
            
            // Theme toggle functionality
            const themeToggle = document.getElementById('theme-toggle');
            
            // Check if user previously set a preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
            
            themeToggle.addEventListener('click', function() {
                // Toggle dark mode class on body
                document.body.classList.toggle('dark-mode');
                
                // Save user preference to localStorage
                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Hide navbar on scroll down, show on scroll up
            let lastScrollTop = 0;
            const header = document.querySelector('.header');
            const scrollThreshold = 100; // Minimum scroll before header hides
            
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Don't hide menu when at the very top of the page
                if (scrollTop <= 10) {
                    header.classList.remove('hide');
                    return;
                }
                
                // Only trigger hide/show after passing threshold to avoid flickering
                if (Math.abs(lastScrollTop - scrollTop) <= scrollThreshold) return;
                
                // Hide when scrolling down, show when scrolling up
                if (scrollTop > lastScrollTop) {
                    // Scrolling down
                    header.classList.add('hide');
                } else {
                    // Scrolling up
                    header.classList.remove('hide');
                }
                
                lastScrollTop = scrollTop;
            });
            
            // Hotel sub-image lightbox functionality
            const subImages = document.querySelectorAll('.hotel-sub-image img');
            
            subImages.forEach(img => {
                img.addEventListener('click', function() {
                    const modal = document.createElement('div');
                    modal.classList.add('lightbox-modal');
                    
                    const modalContent = document.createElement('div');
                    modalContent.classList.add('lightbox-content');
                    
                    const closeBtn = document.createElement('span');
                    closeBtn.classList.add('lightbox-close');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.addEventListener('click', () => {
                        document.body.removeChild(modal);
                    });
                    
                    const image = document.createElement('img');
                    image.src = this.src;
                    
                    modalContent.appendChild(closeBtn);
                    modalContent.appendChild(image);
                    modal.appendChild(modalContent);
                    document.body.appendChild(modal);
                    
                    // Close when clicking outside the image
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            document.body.removeChild(modal);
                        }
                    });
                });
            });

            // Hotels section toggle functionality
            const showHotelsBtn = document.getElementById('showHotelsBtn');
            const hotelsSection = document.getElementById('hotelsSection');
            const backToHotelsBtn = document.getElementById('backToHotels');
            
            function showHotels() {
                if (hotelsSection) {
                    hotelsSection.style.display = 'grid';
                    if (showHotelsBtn) {
                        showHotelsBtn.innerHTML = '<i class="fas fa-hotel"></i> Hide Hotels';
                    }
                }
            }
            
            if (showHotelsBtn && hotelsSection) {
                showHotelsBtn.addEventListener('click', function() {
                    const isHidden = hotelsSection.style.display === 'none';
                    hotelsSection.style.display = isHidden ? 'grid' : 'none';
                    showHotelsBtn.innerHTML = isHidden ? 
                        '<i class="fas fa-hotel"></i> Hide Hotels' : 
                        '<i class="fas fa-hotel"></i> Show Hotels';
                });
            }
            
            // Handle back to hotels button click
            if (backToHotelsBtn) {
                backToHotelsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    window.location.href = url + '#hotelsSection';
                    showHotels();
                });
            }
            
            // Check if we should show hotels section on page load
            if (window.location.hash === '#hotelsSection') {
                showHotels();
            }
        });
    </script>
    
    <?php if (!$selected_hotel && !empty($hotels)): ?>
    <!-- Page Alert Message - Only show when hotels are available -->
    <div id="pageAlertMessage" class="page-alert-message">
        <div class="alert-content">
            <div class="alert-header">
                <h3><i class="fas fa-info-circle"></i> Hotel Information</h3>
                <span class="alert-close">&times;</span>
            </div>
            <div class="alert-body">
                <p>Welcome to <?php echo htmlspecialchars($destination['name']); ?>!</p>
                <p>This destination has hotels available. Scroll down and click the "Show Hotels" button to view accommodation options.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Page alert message functionality
        document.addEventListener('DOMContentLoaded', function() {
            const pageAlert = document.getElementById('pageAlertMessage');
            if (pageAlert) {
                const closeBtn = document.querySelector('.alert-close');
                
                // Show alert after page loads with a slight delay
                setTimeout(function() {
                    pageAlert.classList.add('show');
                }, 800);
                
                // Close alert when close button is clicked
                closeBtn.addEventListener('click', function() {
                    pageAlert.classList.remove('show');
                    setTimeout(function() {
                        pageAlert.style.display = 'none';
                    }, 500);
                });
                
                // Also close alert when clicking outside
                pageAlert.addEventListener('click', function(e) {
                    if (e.target === pageAlert) {
                        pageAlert.classList.remove('show');
                        setTimeout(function() {
                            pageAlert.style.display = 'none';
                        }, 500);
                    }
                });
            }
        });
    </script>
</body>
</html> 