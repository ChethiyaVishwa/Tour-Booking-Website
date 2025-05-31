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

// Get all one day tour packages (type_id = 2)
$query = "SELECT p.*, pt.type_name 
          FROM packages p 
          JOIN package_types pt ON p.type_id = pt.type_id 
          WHERE p.type_id = 2
          ORDER BY p.featured DESC, p.created_at DESC";
$result = mysqli_query($conn, $query);

// Get featured packages for the hero section
$featured_query = "SELECT p.* 
                  FROM packages p 
                  WHERE p.type_id = 2 AND p.featured = 1
                  ORDER BY p.created_at DESC
                  LIMIT 1";
$featured_result = mysqli_query($conn, $featured_query);
$featured_package = mysqli_fetch_assoc($featured_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>One Day Tours - Adventure Travel.lk</title>
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
            --bg-color: #f0f2f5;
            --bg-alt-color: #f8f9fa;
            --card-bg: #fff;
            --text-color: #333;
            --header-bg: rgb(0, 255, 204);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: #eee;
            --card-shadow: rgba(0, 0, 0, 0.1);
            --footer-bg: #222;
            --footer-text: #fff;
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(0, 204, 163);
            --dark-color: #f0f0f0;
            --light-color: #222;
            --bg-color: #121212;
            --bg-alt-color: #1e1e1e;
            --card-bg: #2d2d2d;
            --text-color: #f0f0f0;
            --header-bg: rgb(20, 170, 145);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: #333;
            --card-shadow: rgba(0, 0, 0, 0.5);
            --footer-bg: #111;
            --footer-text: #ddd;
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
                pointer-events: none; /* Ensures dropdown isn't clickable when hidden */
            }
            
            .user-dropdown.active .profile-dropdown {
                opacity: 1;
                visibility: visible;
                transform: scale(1);
                pointer-events: auto; /* Makes dropdown clickable when visible */
            }
            
            /* Special handling when mobile menu is active */
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
                right: auto; /* Remove right positioning when in active mobile menu */
                position: relative; /* Change to relative positioning */
                top: 10px; /* Small spacing from the button */
            }
            
            /* Fix z-index issue when in mobile menu */
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

        /* Hero Section */
        .hero {
            background-size: cover;
            background-position: center;
            color: #fff;
            text-align: center;
            padding: 100px 0;
            position: relative;
            margin-top: 70px; /* Add margin for fixed header */
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #124e48;
        }

        /* Package Section */
        .section {
            padding: 60px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-size: 2rem;
            color: var(--primary-color);
            position: relative;
            padding-bottom: 15px;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--secondary-color);
        }

        .packages {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .package-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--card-shadow);
            transition: transform 0.3s ease, background-color 0.5s ease, box-shadow 0.5s ease;
        }

        .package-card:hover {
            transform: translateY(-10px);
        }

        .package-image {
            height: 200px;
            overflow: hidden;
        }

        .package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .package-card:hover .package-image img {
            transform: scale(1.1);
        }

        .package-details {
            padding: 20px;
        }

        .package-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .package-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .package-price {
            font-weight: bold;
            color: var(--dark-color);
        }

        .package-desc {
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .view-btn {
            display: block;
            width: 100%;
            padding: 10px;
            text-align: center;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .view-btn:hover {
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

        /* Sri Lanka Map Section Styles */
        #sri-lanka-map-section {
            background-color: var(--bg-color);
            padding: 60px 0;
            margin-top: 30px;
            transition: background-color 0.5s ease;
        }
        
        #map {
            transition: all 0.3s ease;
        }
        
        #map:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }
        
        /* Leaflet custom styles */
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
        
        .leaflet-control-layers {
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
                border-top: 1px solid var(--card-shadow);
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

            .login-btn {
                margin: 15px 30px;
                display: inline-block;
            }

            .packages {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }
            
            .hero {
                margin-top: 60px;
            }
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

        /* Add back-to-home button styles */
        .back-to-home {
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
        .back-to-home:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .back-to-home i {
            margin-right: 6px;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .back-to-home {
                top: 25px;
                left: 15px;
                padding: 6px 12px;
                font-size: 0.85rem;
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
            <a href="../tour_packages/tour_packages.php">Tour Packages</a>
            <a href="one_day_tour.php">One Day Tours</a>
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

    <?php if ($featured_package): ?>
    <section class="hero" style="background-image: url('../images/<?php echo htmlspecialchars($featured_package['image']); ?>');">
        <a href="../index.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <div class="hero-content">
            <h1>Experience Sri Lanka in a Day</h1>
            <p>Exciting one-day excursions packed with adventure and culture</p>
            <a href="#one-day-tours" class="btn">View Tours</a>
        </div>
    </section>
    <?php else: ?>
    <section class="hero" style="background-image: url('../images/tour-3.png');">
        <a href="../index.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <div class="hero-content">
            <h1>Experience Sri Lanka in a Day</h1>
            <p>Exciting one-day excursions packed with adventure and culture</p>
            <a href="#one-day-tours" class="btn">View Tours</a>
        </div>
    </section>
    <?php endif; ?>

    <section id="one-day-tours" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Our One Day Tours</h2>
                <p>Discover Sri Lanka's wonders with our exciting single-day tours</p>
            </div>

            <div class="packages">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($package = mysqli_fetch_assoc($result)) {
                ?>
                <div class="package-card">
                    <div class="package-image">
                        <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>">
                    </div>
                    <div class="package-details">
                        <h3 class="package-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                        <div class="package-meta">
                            <span class="package-duration"><?php echo htmlspecialchars($package['duration']); ?></span>
                            <span class="package-price">$<?php echo number_format($package['price'], 2); ?></span>
                        </div>
                        <p class="package-desc"><?php echo htmlspecialchars(substr($package['description'], 0, 100)) . '...'; ?></p>
                        <a href="package_detail.php?id=<?php echo $package['package_id']; ?>" class="view-btn">View Details</a>
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo '<p class="no-packages">No one day tours available at the moment.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Sri Lanka Map Section -->
    <section id="sri-lanka-map-section" class="section">
        <div class="container">
            <div class="section-title">
                <h2>One Day Tour Locations</h2>
                <p>Interactive map of our one day tour destinations across beautiful Sri Lanka</p>
            </div>
            <div id="map" style="height: 500px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); border: 2px solid var(--primary-color);"></div>
        </div>
    </section>

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
                
                // Close dropdown when clicking elsewhere
                document.addEventListener('click', function() {
                    if (userDropdown.classList.contains('active')) {
                        userDropdown.classList.remove('active');
                    }
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
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            
            // Add destinations manually for one day tour packages
            const destinations = [
                {name: 'Colombo', coords: [6.9271, 79.8612], description: 'The commercial capital and largest city of Sri Lanka'},
                {name: 'Airport', coords: [7.175133, 79.888633], description: 'Bandaranaike International Airport, the main gateway to Sri Lanka'},
                {name: 'Udawalawe National Park', coords: [6.474, 80.8987], description: 'Famous for its large elephant herds and diverse wildlife including leopards and various bird species'},
                {name: 'Kandy', coords: [7.293121, 80.635036], description: 'The cultural capital of Sri Lanka, known for the sacred Temple of the Tooth Relic and Peradeniya Botanical Gardens'},
                {name: 'Galle', coords: [6.032814, 80.214955], description: 'Historic fort city with well-preserved Dutch colonial architecture and a UNESCO World Heritage site'},
                {name: 'Nuwara Eliya', coords: [7.012402, 80.757161], description: 'Known as "Little England" with its cool climate, tea plantations, and colonial architecture'},
                {name: 'Sigiriya', coords: [7.949809, 80.746347], description: 'Ancient rock fortress with spectacular frescoes, landscaped gardens, and panoramic views'},
                {name: 'Anuradhapura', coords: [8.334985, 80.41061], description: 'Ancient sacred city with well-preserved ruins, stupas, and the sacred Sri Maha Bodhi tree'},
                {name: 'Polonnaruwa', coords: [7.996234, 81.049172], description: 'Medieval capital with well-preserved ruins, impressive stupas, and ancient irrigation systems'},
                {name: 'Trincomalee', coords: [8.576425, 81.234495], description: 'Coastal city with pristine beaches, natural harbors, and the sacred Koneswaram temple'},
                {name: 'Jaffna', coords: [9.665093, 80.009303], description: 'Northern city with unique Tamil culture, historic temples, and Dutch colonial heritage'},
                {name: 'Ella', coords: [6.873606, 81.048993], description: 'Mountain village with stunning landscapes, hiking trails, and the famous Nine Arch Bridge'},
                {name: 'Arugam Bay', coords: [6.846623, 81.830553], description: 'World-renowned surfing destination with beautiful beaches and laid-back atmosphere'},
                {name: 'Dambulla', coords: [7.874203, 80.651092], description: 'Home to the magnificent Golden Temple and ancient cave temples with Buddhist murals and statues'},
                {name: 'Unawatuna', coords: [6.020177, 80.247484], description: 'Picturesque beach destination with turquoise waters, coral reefs, and vibrant nightlife'},
                {name: 'Kurunegala', coords: [7.4763, 80.3577], description: 'Central city surrounded by large rock formations and ancient temples'},
                {name: 'Mirissa', coords: [5.949363, 80.455813], description: 'Popular beach town known for whale watching, surfing, and relaxed coastal atmosphere'},
                {name: 'Bentota', coords: [6.382282, 80.116523], description: 'Resort town with golden beaches, water sports, and the Bentota River for boat safaris'},
                {name: 'Yala National Park', coords: [6.58333, 81.55], description: 'Renowned for having the highest leopard density in the world and diverse ecosystems'},
                {name: 'Hikkaduwa', coords: [6.140753, 80.102818], description: 'Beach resort with coral reefs, marine turtles, and vibrant beach culture'},
                {name: 'Pinnawala', coords: [7.300434, 80.386298], description: 'Home to the famous Elephant Orphanage where visitors can observe and interact with elephants'},
                {name: 'Kalpitiya', coords: [8.236806, 79.766151], description: 'Peninsula known for dolphin watching, kitesurfing, and beautiful lagoon landscapes'},
                {name: 'Habarana', coords: [8.039888, 80.7555], description: 'Gateway to the Cultural Triangle with easy access to Sigiriya, Polonnaruwa, and wildlife parks'},
                {name: 'Kataragama', coords: [6.413559, 81.332442], description: 'Sacred pilgrimage town with multicultural religious significance and annual festivals'},
                {name: 'Minneriya', coords: [8.039355, 80.905633], description: 'National park famous for "The Gathering" - one of the largest Asian elephant assemblies in the world'},
                {name: 'Negombo', coords: [7.209428, 79.833117], description: 'Beach town close to the airport with Dutch canals, fishing industry, and resort atmosphere'},
                {name: 'Kitulgala', coords: [6.93333, 80.53333], description: 'A small town in the Central Province of Sri Lanka, known for its natural beauty and scenic landscapes.'},
            ];
            
            // Add markers for all destinations
            destinations.forEach(dest => {
                L.marker(dest.coords, {icon: destinationIcon})
                    .addTo(map)
                    .bindPopup(`<strong>${dest.name}</strong><br>${dest.description}`);
            });
            
            // Add scale control
            L.control.scale({imperial: false}).addTo(map);
        });
    </script>

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
                        <li><a href="../index.php#home">Home</a></li>
                        <li><a href="../tour_packages/tour_packages.php">Tour Packages</a></li>
                        <li><a href="one_day_tour.php">One Day Tours</a></li>
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
</body>
</html>
