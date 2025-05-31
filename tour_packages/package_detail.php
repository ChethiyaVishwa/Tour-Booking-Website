<?php
// Include database configuration
require_once '../admin/config.php';

// Start the session if one doesn't exist already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Check if package ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: tour_packages.php");
    exit;
}

$package_id = intval($_GET['id']);

// Get package information
$query = "SELECT p.*, pt.type_name 
          FROM packages p 
          JOIN package_types pt ON p.type_id = pt.type_id 
          WHERE p.package_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $package_id);
mysqli_stmt_execute($stmt);
$package_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($package_result) == 0) {
    header("Location: tour_packages.php");
    exit;
}

$package = mysqli_fetch_assoc($package_result);

// Get package details (itinerary)
$details_query = "SELECT pd.*, d.name as destination_name, d.description as destination_description, 
                d.image as destination_image, h.name as hotel_name, h.star_rating, h.price_per_night
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

// Determine which page to return to based on package type
$back_page = "";
switch ($package['type_id']) {
    case 1:
        $back_page = "tour_packages.php";
        break;
    case 2:
        $back_page = "../one_day_tour_packages/one_day_tour.php";
        break;
    case 3:
        $back_page = "../special_tour_packages/special_tour.php";
        break;
    default:
        $back_page = "../index.php";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($package['name']); ?> - Adventure Travel.lk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --bg-color: #f0f2f5;
            --card-bg: #fff;
            --text-color: #333;
            --header-bg: rgb(0, 255, 204);
            --border-color: rgb(23, 15, 132);
            --card-shadow: rgba(0, 0, 0, 0.1);
            --footer-bg: #222;
            --footer-text: #fff;
            --bg-alt-color: #f8f9fa;
            --border-alt-color: #eee;
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(0, 204, 163);
            --dark-color: #f0f0f0;
            --light-color: #222;
            --bg-color: #121212;
            --card-bg: #2d2d2d;
            --text-color: #f0f0f0;
            --header-bg: rgb(20, 170, 145);
            --border-color: rgb(23, 15, 132);
            --card-shadow: rgba(0, 0, 0, 0.5);
            --footer-bg: #111;
            --footer-text: #ddd;
            --bg-alt-color: #1e1e1e;
            --border-alt-color: #444;
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
            background: rgb(23, 108, 101);
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
            border: 2px solid rgb(23, 108, 101);
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
            color: var(--text-color);
            opacity: 0.7;
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
            color: rgb(23, 108, 101);
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

        /* Mobile responsive styles for dropdown */
        @media (max-width: 991px) {
            .user-dropdown {
                margin: 15px 30px;
                display: inline-block;
            }
            
            .navbar .user-dropdown {
                position: relative;
                z-index: 10;
            }
            
            .navbar .user-dropdown .profile-btn {
                width: 45px;
                height: 45px;
            }
            
            .profile-btn {
                width: 35px;
                height: 35px;
            }
            
            .profile-dropdown {
                position: absolute;
                width: 230px;
                right: 0;
                top: calc(100% + 5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                opacity: 0;
                visibility: hidden;
                transform: scale(0.95);
                pointer-events: none;
            }
            
            .user-dropdown.active .profile-dropdown {
                opacity: 1;
                visibility: visible;
                transform: scale(1);
                pointer-events: auto;
            }
            
            .navbar.active .user-dropdown {
                width: 100%;
                margin: 10px 0;
            }
            
            .navbar.active .profile-btn {
                margin-left: 30px;
            }
            
            .navbar.active .profile-dropdown {
                width: calc(100% - 60px);
                margin-left: 30px;
                margin-right: 30px;
                right: auto;
                position: relative;
                top: 10px;
            }
            
            .navbar.active .user-dropdown.active {
                z-index: 1002;
            }
            
            .menu-section {
                padding: 10px;
            }
            
            .user-brief {
                padding-bottom: 10px;
            }
            
            .user-brief .avatar {
                width: 35px;
                height: 35px;
            }
            
            .menu-section .menu-item {
                padding: 10px 0;
                font-size: 0.95rem;
            }
        }

        /* Small Mobile Devices */
        @media (max-width: 576px) {
            .user-dropdown {
                margin: 12px 20px;
            }
            
            .profile-btn {
                width: 32px;
                height: 32px;
            }
            
            .profile-dropdown {
                width: 210px;
            }
            
            .navbar.active .profile-dropdown {
                width: calc(100% - 40px);
                margin-left: 20px;
                margin-right: 20px;
            }
            
            .navbar.active .profile-btn {
                margin-left: 20px;
            }
            
            .menu-section {
                padding: 8px;
            }
            
            .user-brief {
                padding-bottom: 8px;
                margin-bottom: 5px;
            }
            
            .user-brief .avatar {
                width: 30px;
                height: 30px;
                margin-right: 8px;
            }
            
            .user-brief .name {
                font-size: 0.85rem;
            }
            
            .user-brief .username {
                font-size: 0.75rem;
            }
            
            .menu-section .menu-item {
                padding: 8px 0;
                font-size: 0.9rem;
            }
            
            .menu-item .icon {
                width: 24px;
                font-size: 0.9rem;
            }
            
            .account-actions a {
                padding: 8px 0;
                font-size: 0.8rem;
            }
        }

        /* Package Details Styles with dark mode support */
        .package-header {
            background-size: cover;
            background-position: center;
            color: #fff;
            padding: 100px 0;
            position: relative;
            margin-top: 70px;
            margin-bottom: 30px;
        }

        .package-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .package-header-content {
            position: relative;
            z-index: 1;
        }

        .package-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .package-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .package-meta span {
            font-size: 1.1rem;
        }

        .package-price {
            font-size: 1.3rem;
            font-weight: bold;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #124e48;
        }

        .package-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .package-description {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--card-shadow);
            margin-bottom: 30px;
            color: var(--text-color);
            transition: background-color 0.5s ease, color 0.5s ease, box-shadow 0.5s ease;
        }

        .package-description h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .package-description p {
            margin-bottom: 15px;
            line-height: 1.7;
            color: var(--text-color);
        }

        .itinerary {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--card-shadow);
            transition: background-color 0.5s ease, color 0.5s ease, box-shadow 0.5s ease;
        }

        .itinerary h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .day-item {
            margin-bottom: 30px;
            border-left: 3px solid var(--primary-color);
            padding-left: 20px;
            position: relative;
        }

        .day-item::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 0;
            width: 15px;
            height: 15px;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .day-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--text-color);
            font-size: 1.2rem;
        }

        /* Add styling for topic labels */
        .day-item strong, 
        .destination-info h4, 
        .hotel-info h4,
        .day-item h3 {
            color: var(--primary-color);
            font-weight: bold;
        }

        .day-description {
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .destination-image {
            margin: 15px 0;
            width: 100%;
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
        }

        .destination-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Increase destination image height on larger screens */
        @media (min-width: 992px) {
            .destination-image {
                height: 300px;
            }
        }

        @media (min-width: 1200px) {
            .destination-image {
                height: 350px;
            }
        }

        .hotel-info {
            background-color: var(--bg-alt-color);
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .hotel-info h4 {
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .hotel-rating {
            color: #f8b400;
            margin-bottom: 10px;
        }

        .hotel-image {
            margin-top: 10px;
            margin-bottom: 10px;
            max-width: 200px; /* Limit the width of the image container */
        }

        .hotel-image img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }

        .sidebar {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--card-shadow);
            transition: background-color 0.5s ease, color 0.5s ease, box-shadow 0.5s ease;
        }

        .sidebar h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--card-shadow);
        }

        .includes-excludes {
            margin-bottom: 30px;
        }

        .includes-excludes h3 {
            margin-top: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .includes-list, .excludes-list {
            list-style: none;
            margin-bottom: 25px;
        }

        .includes-list li, .excludes-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--card-shadow);
            color: var(--text-color);
        }

        .includes-list li::before {
            content: '✓';
            color: #28a745;
            margin-right: 10px;
            font-weight: bold;
        }

        .excludes-list li::before {
            content: '✗';
            color: #dc3545;
            margin-right: 10px;
            font-weight: bold;
        }

        .highlights {
            margin-bottom: 30px;
        }

        .highlight-item {
            margin-bottom: 15px;
        }

        .highlight-item h4 {
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .highlight-item p {
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .highlight-item h4 i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .book-btn {
            display: block;
            width: 100%;
            padding: 15px 0;
            background-color: var(--primary-color);
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .book-btn:hover {
            background-color: #124e48;
        }

        /* Footer */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0;
            text-align: center;
            transition: background-color 0.5s ease, color 0.5s ease;
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

        /* Sri Lanka Map Styles */
        .sri-lanka-map {
            margin-top: 30px;
            position: relative;
        }
        
        #map {
            height: 350px;
            width: 100%;
            border-radius: 8px;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            border: 2px solid var(--primary-color);
            overflow: hidden;
            min-height: 350px; /* Ensure minimum height */
            position: relative;
            z-index: 1; /* Ensure map is behind search results */
        }
        
        /* Ensure map keeps proper dimensions on smaller screens */
        @media (max-width: 768px) {
            #map {
                height: 350px;
                min-height: 350px;
            }
        }
        
        @media (max-width: 576px) {
            #map {
                height: 350px;
                min-height: 350px;
            }
        }
        
        /* Customize Leaflet popup style */
        .leaflet-popup-content-wrapper {
            border-radius: 5px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .leaflet-popup-content {
            margin: 10px 12px;
            line-height: 1.5;
        }
        
        .leaflet-popup-content strong {
            color: var(--primary-color);
            display: block;
            font-size: 16px;
            margin-bottom: 3px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .package-content {
                grid-template-columns: 1fr;
            }
        }

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
                border-top: 1px solid var(--card-shadow);
                padding: 0;
                clip-path: polygon(0 0, 100% 0, 100% 0, 0 0);
                transition: 0.5s ease;
                display: block;
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

            .login-btn {
                margin: 15px 30px;
                display: inline-block;
            }

            .package-title {
                font-size: 2rem;
            }

            .package-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .package-header {
                margin-top: 60px;
            }
        }

        /* Map Search Styles */
        .map-search-container {
            margin-bottom: 15px;
            position: relative;
            z-index: 1500; /* Very high z-index to ensure everything appears on top */
        }
        
        /* Additional styles for mobile to prevent zoom */
        @media (max-width: 576px) {
            #destination-search {
                font-size: 16px !important; /* iOS won't zoom if font size is at least 16px */
                transform: scale(1); /* Helps prevent zoom on some Android devices */
                transform-origin: left top;
                touch-action: manipulation; /* Prevents browser manipulation */
            }
            
            .search-input-wrapper {
                transform: translateZ(0); /* Force GPU acceleration */
            }
        }
        
        .search-input-wrapper {
            position: relative;
            display: flex;
            width: 100%;
            border-radius: 30px;
            box-shadow: 0 2px 10px var(--card-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border-alt-color);
            background: var(--card-bg);
        }
        
        .search-input-wrapper:focus-within {
            box-shadow: 0 4px 15px rgba(23, 108, 101, 0.15);
            border-color: var(--primary-color);
        }
        
        .search-input-wrapper.active {
            border-color: var(--primary-color);
        }
        
        .search-input-wrapper.focused {
            box-shadow: 0 6px 20px rgba(23, 108, 101, 0.2);
        }
        
        #destination-search {
            width: 100%;
            padding: 12px 40px 12px 45px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background-color: var(--card-bg);
            color: var(--text-color);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            touch-action: manipulation; /* Prevents browser manipulation */
        }
        
        #destination-search:focus {
            background-color: var(--card-bg);
        }
        
        /* Dark mode specific styles for search input */
        .dark-mode #destination-search {
            color: var(--text-color);
            background-color: var(--card-bg);
        }
        
        .dark-mode #destination-search:focus {
            background-color: var(--card-bg);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 18px;
            z-index: 2;
        }
        
        #clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            width: 24px;
            height: 24px;
            display: none; /* Initially hidden */
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        #clear-search:hover {
            background-color: #f0f0f0;
            color: #666;
        }
        
        .dark-mode #clear-search:hover {
            background-color: #333;
            color: #ccc;
        }
        
        #search-results {
            display: none;
            position: absolute;
            width: 100%;
            max-height: 220px;
            overflow-y: auto;
            background: var(--card-bg);
            border-radius: 12px;
            z-index: 1001;
            box-shadow: 0 4px 15px var(--card-shadow);
            margin-top: 8px;
            border: 1px solid var(--border-alt-color);
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            pointer-events: auto;
        }
        
        #search-results.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .search-result-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-alt-color);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            color: var(--text-color);
            position: relative;
            z-index: 1002;
        }
        
        .search-result-item:hover {
            background-color: var(--bg-alt-color);
            padding-left: 20px;
        }
        
        .search-result-item strong {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        /* Scrollbar styling for search results */
        #search-results::-webkit-scrollbar-track {
            background: var(--bg-alt-color);
            border-radius: 10px;
        }
        
        #search-results::-webkit-scrollbar-thumb {
            background: var(--border-alt-color);
            border-radius: 10px;
        }

        /* Theme toggle button - Stylish switch design */
        .theme-toggle {
            position: fixed;
            left: 20px;
            top: 180px; /* Positioned below header */
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
                top: 170px;
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
                top: 150px;
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
                top: 130px;
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
            
            #destination-search {
                font-size: 16px !important; /* iOS won't zoom if font size is at least 16px */
                transform: scale(1); /* Helps prevent zoom on some Android devices */
                transform-origin: left top;
                touch-action: manipulation; /* Prevents browser manipulation */
            }
            
            .search-input-wrapper {
                transform: translateZ(0); /* Force GPU acceleration */
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
    </style>
</head>
<body>
    <header class="header">
        <a href="../index.php" class="logo">
            <img src="../images/logo-4.png" alt="Adventure Travel.lk Logo">
        </a>

        <div class="menu-toggle">☰</div>

        <nav class="navbar">
            <a href="../index.php">Home</a>
            <a href="tour_packages.php">Tour Packages</a>
            <a href="../one_day_tour_packages/one_day_tour.php">One Day Tours</a>
            <a href="../special_tour_packages/special_tour.php">Special Tours</a>
            <a href="../index.php#vehicle-hire">Vehicle Hire</a>
            <a href="../destinations/destinations.php">Destinations</a>
            <a href="../contact_us.php">Contact Us</a>
            <a href="../about_us/about_us.php">About Us</a>
        </nav>
    </header>

    <!-- Theme toggle button with improved toggle switch design -->
    <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
        <div class="toggle-icons">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
        </div>
        <div class="toggle-handle"></div>
    </button>

    <section class="package-header" style="background-image: url('../images/<?php echo htmlspecialchars($package['image']); ?>');">
        <div class="container">
            <div class="package-header-content">
                <h1 class="package-title"><?php echo htmlspecialchars($package['name']); ?></h1>
                <div class="package-meta">
                    <span><strong>Type:</strong> <?php echo htmlspecialchars($package['type_name']); ?></span>
                    <span><strong>Duration:</strong> <?php echo htmlspecialchars($package['duration']); ?></span>
                    <span class="package-price"><strong>Price:</strong> $<?php echo number_format($package['price'], 2); ?> per person</span>
                </div>
                <a href="<?php echo $back_page; ?>" class="back-btn">Back to Packages</a>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="package-content">
            <div class="main-content">
                <div class="package-description">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
                </div>

                <?php if (!empty($package_details)): ?>
                <div class="itinerary">
                    <h2>
                        <?php if ($package['type_id'] == 1): ?>
                            Itinerary
                        <?php elseif ($package['type_id'] == 2): ?>
                            Tour Details
                        <?php else: ?>
                            Tour Plan
                        <?php endif; ?>
                    </h2>
                    
                    <?php foreach ($package_details as $detail): ?>
                    <div class="day-item">
                        <?php if ($detail['day_number']): ?>
                            <h3 class="day-title">Day <?php echo $detail['day_number']; ?>: <?php echo htmlspecialchars($detail['title']); ?></h3>
                        <?php else: ?>
                            <h3 class="day-title"><?php echo htmlspecialchars($detail['title']); ?></h3>
                        <?php endif; ?>
                        
                        <p class="day-description"><?php echo nl2br(htmlspecialchars($detail['description'])); ?></p>
                        
                        <?php if (!empty($detail['destination_name'])): ?>
                            <div class="destination-info">
                                <h4>Destination: <?php echo htmlspecialchars($detail['destination_name']); ?></h4>
                                <p><?php echo htmlspecialchars($detail['destination_description']); ?></p>
                                
                                <?php if (!empty($detail['destination_image'])): ?>
                                    <?php if (!empty($detail['image'])): ?>
                            <div class="destination-image">
                                <img src="../images/<?php echo htmlspecialchars($detail['image']); ?>" alt="<?php echo htmlspecialchars($detail['title']); ?>">
                            </div>
                        <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($detail['hotel_name'])): ?>
                            <div class="hotel-info">
                                <h4>Accommodation: <?php echo htmlspecialchars($detail['hotel_name']); ?></h4>
                                <div class="hotel-rating">
                                    <?php for ($i = 0; $i < $detail['star_rating']; $i++): ?>
                                        ★
                                    <?php endfor; ?>
                                </div>
                                <?php if (!empty($detail['hotel_id'])): ?>
                                <?php 
                                    // Get hotel image
                                    $hotel_query = "SELECT image FROM hotels WHERE hotel_id = ?";
                                    $hotel_stmt = mysqli_prepare($conn, $hotel_query);
                                    mysqli_stmt_bind_param($hotel_stmt, "i", $detail['hotel_id']);
                                    mysqli_stmt_execute($hotel_stmt);
                                    $hotel_result = mysqli_stmt_get_result($hotel_stmt);
                                    $hotel_data = mysqli_fetch_assoc($hotel_result);
                                    
                                    if ($hotel_data && !empty($hotel_data['image'])):
                                ?>
                                <div class="hotel-image">
                                    <img src="../images/<?php echo htmlspecialchars($hotel_data['image']); ?>" alt="<?php echo htmlspecialchars($detail['hotel_name']); ?>">
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                <p><strong>Rate:</strong> $<?php echo number_format($detail['price_per_night'], 2); ?> per night</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($detail['meal_plan'])): ?>
                            <p><strong>Meals:</strong> <?php echo htmlspecialchars($detail['meal_plan']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($detail['activities'])): ?>
                            <p><strong>Activities:</strong> <?php echo htmlspecialchars($detail['activities']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($detail['transport_type'])): ?>
                            <p><strong>Transport:</strong> <?php echo htmlspecialchars($detail['transport_type']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar">
                <?php if (!empty($highlights)): ?>
                <div class="highlights">
                    <h3>Highlights</h3>
                    <?php foreach ($highlights as $highlight): ?>
                    <div class="highlight-item">
                        <h4>
                            <?php if (!empty($highlight['icon'])): ?>
                                <i class="fa <?php echo htmlspecialchars($highlight['icon']); ?>"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($highlight['title']); ?>
                        </h4>
                        <p><?php echo htmlspecialchars($highlight['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="includes-excludes">
                    <?php if (!empty($includes)): ?>
                    <h3>What's Included</h3>
                    <ul class="includes-list">
                        <?php foreach ($includes as $include): ?>
                            <li><?php echo htmlspecialchars($include['item_description']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($excludes)): ?>
                    <h3>What's Not Included</h3>
                    <ul class="excludes-list">
                        <?php foreach ($excludes as $exclude): ?>
                            <li><?php echo htmlspecialchars($exclude['item_description']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                
                <a href="../contact_us.php" class="book-btn">Contact Us</a>
                
                <!-- Sri Lanka Map -->
                <div class="sri-lanka-map">
                    <h3>Tour Location</h3>
                    <!-- Add destination search bar -->
                    <div class="map-search-container">
                        <div class="search-input-wrapper">
                            <span class="search-icon"><i class="fa fa-search"></i></span>
                            <input type="text" id="destination-search" placeholder="Search for a destination...">
                            <button id="clear-search">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div id="search-results"></div>
                    </div>
            
                    <div id="map"></div>
                </div>
            </div>
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
            
            menuToggle.addEventListener('click', function() {
                navbar.classList.toggle('active');
            });
            
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
                    e.stopPropagation();
                    this.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    userDropdown.classList.remove('active');
                });
            }
            
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
            
            // Theme toggle functionality
            const themeToggle = document.getElementById('theme-toggle');
            
            // Check for saved theme preference or use device preference
            const savedTheme = localStorage.getItem('theme');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDarkScheme.matches)) {
                document.body.classList.add('dark-mode');
            }
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                
                // Save preference to localStorage
                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });
        });
    </script>
    
    <!-- Leaflet Map CSS and JavaScript -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the map centered on Sri Lanka
            const center = [7.8731, 80.7718]; // Sri Lanka center coordinates
            const map = L.map('map').setView(center, 8);
            
            // Define multiple tile layers for different map styles
            const outdoorsLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            });
            
            const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                maxZoom: 18
            });
            
            const topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 17,
                attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
            });
            
            // Start with the outdoors layer
            outdoorsLayer.addTo(map);
            
            // Create a layer control and add it to the map
            const baseLayers = {
                "Outdoor Map": outdoorsLayer,
                "Satellite": satelliteLayer,
                "Topographic": topoLayer
            };
            
            L.control.layers(baseLayers, null, {collapsed: false}).addTo(map);
            
            // Custom marker icon for destinations
            const destinationIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            
            // Custom marker icon for the tour center
            const tourIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            
            // Flag to track if we added any markers
            let markersAdded = false;
            let markers = [];
            
            // Skip all the complex destination parsing, just add a central marker for the package
            const packageMarker = L.marker(center, {icon: tourIcon})
                .addTo(map)
                .bindPopup("<strong><?php echo htmlspecialchars($package['name']); ?></strong><br>Sri Lanka Tour");
            
            // Define common Sri Lanka destinations for search
            const sriLankaDestinations = [
                {name: 'Sri Lanka', coords: [7.8731, 80.7718]},
                {name: 'Airport', coords: [7.175133, 79.888633]},
                {name: 'Colombo', coords: [6.938861, 79.854201]},
                {name: 'Udawalawe National Park', coords: [6.474, 80.8987]},
                {name: 'Kandy', coords: [7.293121, 80.635036]},
                {name: 'Galle', coords: [6.032814, 80.214955]},
                {name: 'Nuwara Eliya', coords: [7.012402, 80.757161]},
                {name: 'Sigiriya', coords: [7.949809, 80.746347]},
                {name: 'Anuradhapura', coords: [8.334985, 80.41061]},
                {name: 'Polonnaruwa', coords: [7.996234, 81.049172]},
                {name: 'Trincomalee', coords: [8.576425, 81.234495]},
                {name: 'Jaffna', coords: [9.665093, 80.009303]},
                {name: 'Ella', coords: [6.873606, 81.048993]},
                {name: 'Arugam Bay', coords: [6.846623, 81.830553]},
                {name: 'Dambulla', coords: [7.874203, 80.651092]},
                {name: 'Unawatuna', coords: [6.020177, 80.247484]},
                {name: 'Kurunegala', coords: [7.4763, 80.3577]},
                {name: 'Mirissa', coords: [5.949363, 80.455813]},
                {name: 'Bentota', coords: [6.382282, 80.116523]},
                {name: 'Yala National Park', coords: [6.58333, 81.55]},
                {name: 'Hikkaduwa', coords: [6.140753, 80.102818]},
                {name: 'Pinnawala', coords: [7.300434, 80.386298]},
                {name: 'Kalpitiya', coords: [8.236806, 79.766151]},
                {name: 'Habarana', coords: [8.039888, 80.7555]},
                {name: 'Kataragama', coords: [6.413559, 81.332442]},
                {name: 'Minneriya', coords: [8.039355, 80.905633]},
                {name: 'Negombo', coords: [7.209428, 79.833117]},
                {name: 'Kitulgala', coords: [6.93333, 80.53333]},
                
            ];
            
            // Implement search functionality
            const searchInput = document.getElementById('destination-search');
            const searchResults = document.getElementById('search-results');
            const clearSearchBtn = document.getElementById('clear-search');
            const searchWrapper = document.querySelector('.search-input-wrapper');
            let searchMarker = null; // To store the current search marker
            
            // Show clear button when there's text in the search field
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                // Toggle clear button visibility
                if (searchTerm.length > 0) {
                    clearSearchBtn.style.display = 'flex';
                    searchWrapper.classList.add('active');
                } else {
                    clearSearchBtn.style.display = 'none';
                    searchWrapper.classList.remove('active');
                }
                
                // Clear results
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
                searchResults.classList.remove('visible');
                
                if (searchTerm.length < 2) {
                    return; // Require at least 2 characters
                }
                
                // Find matching destinations
                const matches = sriLankaDestinations.filter(dest => 
                    dest.name.toLowerCase().includes(searchTerm)
                );
                
                // Display matching destinations in dropdown
                if (matches.length > 0) {
                    searchResults.style.display = 'block';
                    
                    // Force reflow to ensure animation works
                    searchResults.offsetWidth;
                    
                    setTimeout(() => {
                        searchResults.classList.add('visible');
                    }, 10);
                    
                    matches.forEach(destination => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'search-result-item';
                        
                        // Highlight the matching part of the text
                        const index = destination.name.toLowerCase().indexOf(searchTerm);
                        const name = destination.name;
                        
                        if (index >= 0) {
                            resultItem.innerHTML = 
                                name.substring(0, index) + 
                                '<strong>' + name.substring(index, index + searchTerm.length) + '</strong>' +
                                name.substring(index + searchTerm.length);
                        } else {
                            resultItem.textContent = name;
                        }
                        
                        // Click event
                        resultItem.addEventListener('click', function() {
                            searchInput.value = destination.name;
                            hideSearchResults();
                            clearSearchBtn.style.display = 'flex';
                            showDestinationOnMap(destination);
                        });
                        
                        searchResults.appendChild(resultItem);
                    });
                }
            });
            
            // Function to hide search results with animation
            function hideSearchResults() {
                searchResults.classList.remove('visible');
                setTimeout(() => {
                    searchResults.style.display = 'none';
                }, 300);
            }
            
            // Add focus/blur effects for search input
            searchInput.addEventListener('focus', function() {
                searchWrapper.classList.add('focused');
                const searchTerm = this.value.toLowerCase();
                
                // Re-show results if there's text and matches
                if (searchTerm.length >= 2) {
                    // Trigger the input event to refresh results
                    this.dispatchEvent(new Event('input'));
                }
            });
            
            searchInput.addEventListener('blur', function() {
                setTimeout(() => {
                    searchWrapper.classList.remove('focused');
                    // Don't hide results immediately to allow for clicking on them
                }, 200);
            });
            
            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchResults.contains(e.target) && e.target !== searchInput) {
                    hideSearchResults();
                }
            });
            
            // Clear search button functionality
            clearSearchBtn.addEventListener('click', function() {
                // Clear search input
                searchInput.value = '';
                hideSearchResults();
                this.style.display = 'none';
                searchWrapper.classList.remove('active');
                
                // Remove search marker if it exists
                if (searchMarker) {
                    map.removeLayer(searchMarker);
                    searchMarker = null;
                }
                
                // Reset map view to center of Sri Lanka
                map.setView(center, 8);
            });
            
            // Function to show a destination on the map
            function showDestinationOnMap(destination) {
                // Remove previous search marker if it exists
                if (searchMarker) {
                    map.removeLayer(searchMarker);
                }
                
                // Create a destination icon
                const destinationIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: "<div style='background-color:#c30b82;' class='marker-pin'></div><i class='material-icons'>place</i>",
                    iconSize: [30, 42],
                    iconAnchor: [15, 42]
                });
                
                // Add the destination to the map
                searchMarker = L.marker(destination.coords, {icon: destinationIcon})
                    .addTo(map)
                    .bindPopup("<strong>" + destination.name + "</strong>")
                    .openPopup();
                
                // Center map on the found destination
                map.setView(destination.coords, 10);
            }
            
            // Handle direct searches on Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const searchTerm = this.value.toLowerCase();
                    const matches = sriLankaDestinations.filter(dest => 
                        dest.name.toLowerCase().includes(searchTerm)
                    );
                    
                    if (matches.length > 0) {
                        showDestinationOnMap(matches[0]);
                        searchResults.style.display = 'none';
                    }
                }
            });
            
            // Setup popular destination buttons
            document.querySelectorAll('.destination-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const destinationName = this.getAttribute('data-name');
                    const destination = sriLankaDestinations.find(d => d.name === destinationName);
                    
                    if (destination) {
                        searchInput.value = destination.name;
                        showDestinationOnMap(destination);
                    }
                });
                
                // Add hover effect
                btn.addEventListener('mouseover', function() {
                    this.style.backgroundColor = '#e0e0e0';
                });
                btn.addEventListener('mouseout', function() {
                    this.style.backgroundColor = '#f5f5f5';
                });
            });
            
            // Add Sri Lanka outline
            fetch('https://raw.githubusercontent.com/datasets/geo-countries/master/data/countries.geojson')
                .then(response => response.json())
                .then(data => {
                    const sriLankaFeature = data.features.find(feature => feature.properties.ADMIN === "Sri Lanka");
                    if (sriLankaFeature) {
                        L.geoJSON(sriLankaFeature, {
                            style: {
                    color: 'var(--primary-color)',
                                weight: 2,
                    opacity: 0.7,
                                fillColor: 'var(--secondary-color)',
                                fillOpacity: 0.2
                            }
                }).addTo(map);
                    }
                })
                .catch(error => console.error('Error loading Sri Lanka outline:', error));
            
            // Add scale control
            L.control.scale({imperial: false}).addTo(map);
        });
    </script>
</body>
</html> 