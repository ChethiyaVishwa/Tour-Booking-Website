<?php
    // Database connection
    require_once 'admin/config.php';
    
    // Fetch vehicles from database
    $vehicles_query = "SELECT * FROM vehicles WHERE available = 1 ORDER BY vehicle_id DESC LIMIT 6";
    $vehicles_result = mysqli_query($conn, $vehicles_query);
    $vehicles = [];
    if ($vehicles_result) {
        while ($vehicle = mysqli_fetch_assoc($vehicles_result)) {
            $vehicles[] = $vehicle;
        }
    }
    
    // Check if team_members table exists, create if not
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
        
        mysqli_query($conn, $create_table);
    }
    
    // Fetch team members from database
    $team_members_query = "SELECT * FROM team_members ORDER BY id ASC";
    $team_members_result = mysqli_query($conn, $team_members_query);
    $team_members = [];
    if ($team_members_result) {
        while ($member = mysqli_fetch_assoc($team_members_result)) {
            $team_members[] = $member;
        }
    }
    
    // Fetch approved reviews from database
    $reviews_query = "SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 8";
    $reviews_result = mysqli_query($conn, $reviews_query);
    $approved_reviews = [];
    if ($reviews_result) {
        while ($review = mysqli_fetch_assoc($reviews_result)) {
            $approved_reviews[] = $review;
        }
    }

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
        header("Location: login.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Adventure Travel.lk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
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
            --text-color: #333;
            --bg-color: #f0f2f5;
            --bg-alt-color: #ffffff;
            --card-bg: #fff;
            --card-bg-rgb: 255, 255, 255;
            --header-bg: rgb(0, 255, 204);
            --footer-bg: #333;
            --footer-text: #fff;
            --card-shadow: rgba(0, 0, 0, 0.1);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: rgb(23, 108, 101);
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(4, 60, 48);
            --dark-color: #f0f0f0;
            --light-color: #222;
            --text-color: #f0f0f0;
            --bg-color: #121212;
            --bg-alt-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --card-bg-rgb: 45, 45, 45;
            --header-bg: rgb(20, 170, 145);
            --footer-bg: #1a1a1a;
            --footer-text: #ddd;
            --card-shadow: rgba(0, 0, 0, 0.5);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: rgb(0, 179, 143);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.5s ease, color 0.5s ease;
        }

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
            font-weight: 700; /* or use 'bold' */
            transition: color 0.3s ease;
        }


        .navbar a:hover {
            color:rgb(255, 0, 0);
        }
        
        /* Glass-morphism User Dropdown Menu Style */
        .user-dropdown {
            position: relative;
            display: inline-block;
            margin-left: 25px;
        }
        
        .profile-btn {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #00c6fb 0%, #005bea 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .profile-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(0, 123, 255, 0.4);
        }
        
        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 280px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 12px;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.95);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 1000;
            overflow: hidden;
        }
        
        .user-dropdown.active .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }
        
        .profile-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            background: linear-gradient(to right,rgb(255, 0, 0) 0%,rgb(60, 0, 0) 100%);
        }
        
        .profile-avatar {
            width: 70px;
            height: 70px;
            margin: 0 auto 12px;
            border-radius: 18px;
            background: linear-gradient(135deg,rgb(15, 107, 84) 0%,rgb(0, 234, 191) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header h4 {
            margin: 0;
            color: #fff;
            font-size: 18px;
            font-weight: 600;
        }
        
        .profile-header p {
            margin: 5px 0 0;
            color: #999;
            font-size: 14px;
            font-weight: 400;
        }
        
        .menu-section {
            padding: 15px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .menu-item:hover {
            background-color: rgba(0, 0, 0, 0.05);
            transform: translateX(5px);
        }
        
        .menu-item .icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            margin-right: 12px;
            font-size: 18px;
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .menu-item:nth-child(1) .icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        
        .menu-item:nth-child(2) .icon {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }
        
        .menu-item:nth-child(3) .icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }
        
        .menu-item.logout {
            color: #e74c3c;
        }
        
        .menu-item.logout .icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .menu-item.logout:hover {
            background-color: rgba(231, 76, 60, 0.05);
        }
        
        .account-actions {
            display: flex;
            padding: 0 15px 15px;
        }
        
        .account-actions a {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            margin: 0 5px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.03);
            color: #666;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .account-actions a:hover {
            background: rgba(0, 0, 0, 0.06);
            color: #333;
            transform: translateY(-3px);
        }
        
        /* Dark mode adjustments */
        .dark-mode .profile-dropdown {
            background: rgba(30, 30, 30, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .dark-mode .profile-header {
            background: linear-gradient(to right,rgb(255, 0, 0) 0%,rgb(60, 0, 0) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .dark-mode .profile-header h4 {
            color: #fff;
        }
        
        .dark-mode .profile-header p {
            color: #aaa;
        }
        
        .dark-mode .menu-item {
            color: #ccc;
        }
        
        .dark-mode .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .dark-mode .account-actions a {
            background: rgba(255, 255, 255, 0.05);
            color: #aaa;
        }
        
        .dark-mode .account-actions a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        /* Compact Responsive Media Queries */
        /* Large devices (desktops, less than 1200px) */
        @media (max-width: 1199.98px) {
            .profile-dropdown {
                right: -15px;
            }
            
            .profile-avatar {
                width: 60px;
                height: 60px;
                margin: 0 auto 8px;
            }
            
            .profile-header {
                padding: 15px;
            }
            
            .menu-item {
                padding: 8px 15px;
                margin-bottom: 5px;
            }
        }
        
        /* Medium devices (tablets, less than 992px) */
        @media (max-width: 991.98px) {
            .user-dropdown {
                margin: 10px 30px;
            }
            
            .profile-dropdown {
                width: 250px;
                position: absolute;
                right: 0;
                top: calc(100% + 10px);
            }
            
            .profile-avatar {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-bottom: 5px;
            }
            
            .profile-header {
                padding: 12px;
            }
            
            .menu-section {
                padding: 10px;
            }
            
            .navbar.active .user-dropdown {
                display: block;
                width: 100%;
            }
            
            .navbar.active .profile-dropdown {
                width: calc(100% - 30px);
                margin: 5px 15px;
                position: relative;
                top: 0;
                right: 0;
                background: var(--card-bg);
                z-index: 1002;
                opacity: 0;
                visibility: hidden;
                transform: scale(0.95);
            }
            
            .navbar.active .user-dropdown.active .profile-dropdown {
                opacity: 1;
                visibility: visible;
                transform: scale(1);
            }
        }
        
        /* Small devices (landscape phones, less than 768px) */
@media (max-width: 767.98px) {
    .profile-dropdown {
        width: 240px;
    }
    
    .profile-btn {
        width: 110px;
        height: 38px;
        font-size: 14px;
    }
    
    .profile-btn .avatar {
        width: 26px;
        height: 26px;
        font-size: 14px;
    }
    
    .profile-btn .user-name {
        max-width: 50px;
    }
            
            .menu-item {
                padding: 7px 12px;
                margin-bottom: 3px;
            }
            
            .menu-item .icon {
                width: 28px;
                height: 28px;
                font-size: 14px;
                margin-right: 8px;
            }
            
            .profile-header h4 {
                font-size: 15px;
            }
            
            .profile-header p {
                font-size: 11px;
                margin-top: 2px;
            }
            
            .account-actions {
                padding: 0 10px 10px;
            }
            
            .account-actions a {
                padding: 6px;
                font-size: 11px;
            }
            
            /* Ensure mobile dropdown menu displays properly */
            .navbar.active .user-dropdown {
                width: 100%;
                margin: 0;
                padding: 10px 0;
            }
            
            .navbar.active .profile-btn {
                margin-left: 30px;
            }
        }
        
        /* Extra small devices (portrait phones, less than 576px) */
@media (max-width: 575.98px) {
    .user-dropdown {
        margin: 8px 15px;
    }
    
    .profile-btn {
        width: 100px;
        height: 35px;
        font-size: 13px;
        padding: 0 8px;
    }
    
    .profile-btn .avatar {
        width: 24px;
        height: 24px;
        font-size: 12px;
        margin-right: 6px;
    }
    
    .profile-btn .user-name {
        max-width: 45px;
    }
    
    .profile-btn .dropdown-icon {
        font-size: 10px;
    }
            
            .profile-dropdown {
                width: 220px;
            }
            
            .profile-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin-bottom: 5px;
                border-radius: 12px;
            }
            
            .menu-section {
                padding: 8px;
            }
            
            .menu-item {
                padding: 6px 10px;
                margin-bottom: 2px;
                font-size: 13px;
            }
            
            .menu-item .icon {
                width: 25px;
                height: 25px;
                font-size: 12px;
                margin-right: 7px;
            }
            
            .account-actions {
                padding: 0 8px 8px;
            }
            
            .account-actions a {
                padding: 5px;
                font-size: 10px;
            }
            
            /* Full-width dropdown when in collapsed navbar */
            .navbar.active .profile-dropdown {
                width: calc(100% - 20px);
                margin: 5px 10px;
            }
            
            /* Fix for mobile dropdown display */
            .navbar.active .user-dropdown .profile-dropdown {
                display: none;
            }
            
            .navbar.active .user-dropdown.active .profile-dropdown {
                display: block;
                position: relative;
                top: 10px;
                right: auto;
                left: auto;
                z-index: 1002;
            }
        }
        
        /* Fix for very small screens */
        @media (max-width: 359.98px) {
            .profile-dropdown {
                width: 190px;
            }
            
            .profile-header {
                padding: 10px;
            }
            
            .profile-avatar {
                width: 35px;
                height: 35px;
                font-size: 14px;
                margin-bottom: 3px;
                border-radius: 10px;
            }
            
            .menu-item {
                padding: 5px 8px;
                font-size: 12px;
            }
            
            .menu-item .icon {
                width: 22px;
                height: 22px;
                font-size: 11px;
                margin-right: 6px;
            }
            
            .profile-header h4 {
                font-size: 13px;
            }
            
            .profile-header p {
                font-size: 10px;
            }
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

        /* Enhanced Login Button with Shine Animation */
        .login-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            z-index: 1;
            /* Adding shine border effect */
            border: 2px solid transparent;
            background-clip: padding-box;
            animation: shine-border 3s linear infinite;
        }

        @keyframes shine-border {
            0% {
                border-color: rgba(255, 255, 255, 0.1);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
            25% {
                border-color: rgba(255, 255, 255, 0.8);
                box-shadow: 0 4px 20px rgba(255, 255, 255, 0.5), 0 0 15px var(--secondary-color);
            }
            50% {
                border-color: rgba(255, 255, 255, 0.1);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
            75% {
                border-color: rgba(255, 255, 255, 0.8);
                box-shadow: 0 4px 20px rgba(255, 255, 255, 0.5), 0 0 15px var(--secondary-color);
            }
            100% {
                border-color: rgba(255, 255, 255, 0.1);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
        }

        /* Create a shine sweep effect */
        .login-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0) 40%,
                rgba(255, 255, 255, 0.6) 50%,
                rgba(255, 255, 255, 0) 60%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(45deg);
            pointer-events: none;
            z-index: 2;
            animation: shine-sweep 4s linear infinite;
        }

        @keyframes shine-sweep {
            0% {
                transform: rotate(45deg) translateX(-150%);
            }
            100% {
                transform: rotate(45deg) translateX(150%);
            }
        }

        .login-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            z-index: -1;
            transition: opacity 0.3s ease;
            opacity: 0;
            border-radius: 30px;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15), 0 0 20px var(--secondary-color);
            color: #fff;
            animation: shine-border 1.5s linear infinite;
        }
        
        .login-btn:hover::after {
            animation: shine-sweep 2s linear infinite;
        }
        
        .login-btn:hover:before {
            opacity: 1;
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: var(--text-color);
            transition: color 0.3s ease;
        }

        /* Home section styles */
        .home {
            min-height: 110vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        .slide:nth-child(1) {
            background-image:url('images/home-bg-1.png');
        }

        .slide:nth-child(2) {
            background-image:url('images/home-bg-2.png');
        }

        .slide:nth-child(3) {
            background-image:url('images/home-bg-3.png');
        }

        .home-content {
            text-align: center;
            position: relative;
            z-index: 10;
            max-width: 800px;
            padding: 0 20px;
        }

        .content-box span {
            font-size: 4rem;
            text-shadow: 2px 2px 5px rgb(0, 0, 0);
            margin-bottom: 20px;
            color: #000a1a;
            text-transform: uppercase;
            font-weight: 700;
            
        }

        .content-box {
            display: none;
            animation: fadeIn 1s ease-in-out;
        }

        .content-box.active {
            display: block;
        }

        .content-box .btn {
            z-index: 10;
            margin-top: 1rem;
            display: inline-block;
            border: 0.09rem solid rgb(0, 22, 26);
            border-radius: 0.7rem;
            color:rgb(255, 255, 255);
            cursor: pointer;
            background: rgba(0, 0, 0, 0.36);
            font-size: 1rem;
            padding: 0.5rem 1.5rem;
            position: relative;
            transform: scale(1);
            transition: transform 0.3s ease;
            text-decoration: none;
        }
        .content-box .btn:hover {
            background:rgb(0, 255, 204);
            color: black;
            transform: scale(1.1);
        }

        /* Social links styling for home section */
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.47);
            border-radius: 10px;
            color: #fff;
            font-size: 22px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.74);
        }

        .social-link:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgb(0, 255, 204);
            transform: translateY(100%);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: -1;
        }

        .social-link:hover {
            color: #000;
            transform: translateY(-8px) rotate(8deg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .social-link:hover:before {
            transform: translateY(0);
        }

        .social-link i {
            position: relative;
            z-index: 2;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .home-content h1 {
            font-size: 4rem;
            color: #666;
            text-shadow: 2px 2px 5px rgb(0, 0, 0);
            margin-bottom: 20px;
        }

        .home-content p {
            font-size: 1.2rem;
            color: #f5f5f5;
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgb(0, 0, 0);
        }

        .slider-controls {
            position: absolute;
            bottom: 30px;
            display: flex;
            justify-content: center;
            width: 100%;
            z-index: 10;
        }

        .slider-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .slider-dot.active {
            background: #fff;
        }

        /* Responsive design */
        @media (max-width: 991px) {
            .header {
                padding: 15px 20px;
            }
            
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
                display: block; /* Change from flex to block for mobile view */
            }
            
            .navbar.active {
                clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
            }
            
            .navbar a {
                display: block;
                margin: 15px 0;
                padding: 12px 30px;
                font-size: 18px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .login-btn {
                margin: 15px 30px;
                display: inline-block;
                padding: 8px 20px;
                font-size: 14px;
                width: auto;
                max-width: 120px;
                text-align: center;
            }
            
            /* Adjust user dropdown for mobile */
            .user-dropdown {
                margin: 15px 30px;
            }
            
            .profile-btn {
                width: 120px;
                max-width: 120px;
                height: 38px;
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
            }

            .home-content h1 {
                font-size: 2.5rem;
            }

            .home-content p {
                font-size: 1rem;
            }

            .home-content span{
                font-size: 3rem;
            }
        }

        /* Packages Section Styles */
        .packages {
            padding: 6rem 2rem;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #555;
            position: relative;
        }

        .section-title span {
            color: rgb(23, 108, 101);
        }

        .packages-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .package-card {
            width: 350px;
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .package-card:hover .card-image img {
            transform: scale(1.1);
        }

        
        .card-content {
            padding: 20px;
        }

        .card-content h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .card-content p {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .card-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-icon {
            font-style: normal;
            font-size: 1.2rem;
        }

        .feature span {
            font-size: 0.9rem;
            color: #666;
        }

        .card-btn {
            display: block;
            text-align: center;
            background: rgb(23, 108, 101);
            color: white;
            padding: 12px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s, transform 0.3s, border 0.3s;
        }

        .card-btn:hover {
            background: rgb(0, 255, 204);
            color: rgb(62, 62, 62);
            transform: scale(1.05);
            border: 2px solid rgb(23, 108, 101);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .packages {
                padding: 4rem 1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .package-card {
                width: 100%;
                max-width: 400px;
            }
        }

        /* Vehicle Hire Section Styles */
        .vehicle-hire {
            padding: 6rem 2rem;
            background-color: var(--bg-alt-color);
            color: var(--text-color);
        }

        .vehicles-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .vehicle-card {
            width: 350px;
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .vehicle-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .vehicle-card .card-image {
            height: 220px;
            overflow: hidden;
        }

        .vehicle-card .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .vehicle-card:hover .card-image img {
            transform: scale(1.1);
        }

        /* Rest of the styling for vehicles can reuse the package card styling */
        .vehicle-card .card-content h3 {
            color: rgb(23, 108, 101);
        }
        
        /* Responsive adjustment for vehicle section */
        @media (max-width: 768px) {
            .vehicle-hire {
                padding: 4rem 1rem;
            }
            
            .vehicle-card {
                width: 100%;
                max-width: 400px;
            }
        }

        /* Destinations Section Styles */
        .destinations {
            padding: 6rem 2rem;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .destinations-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .destination-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 320px;
            transition: transform 0.3s ease;
        }

        .destination-card:hover {
            transform: translateY(-8px);
        }

        .destination-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .destination-card:hover img {
            transform: scale(1.1);
        }

        .destination-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .destination-card:hover .destination-content {
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
        }

        .destination-content h3 {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .destination-content p {
            font-size: 0.9rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .destination-btn {
            display: inline-block;
            background: rgb(23, 108, 101);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background 0.3s;
        }

        .destination-btn:hover {
            background: rgb(18, 88, 82);
            color: rgb(255, 255, 255);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .destinations {
                padding: 4rem 1rem;
            }
            
            .destinations-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .destination-card {
                height: 280px;
            }
        }

        .explore-more-container {
            text-align: center;
            margin-top: 2.5rem;
        }

        .explore-more-btn {
            display: inline-block;
            background: rgb(23, 108, 101);
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .explore-more-btn:hover {
            background: rgb(18, 88, 82);
            color: rgb(255, 255, 255);
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        /* Reviews Section Styles */
        .reviews {
            padding: 6rem 2rem;
            background-color: var(--bg-alt-color);
            color: var(--text-color);
        }
        
        .reviews-container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
        }
        
        .reviews-slider {
            position: relative;
            height: 320px;
            overflow: hidden;
        }
        
        .review-card {
            position: absolute;
                top: 0;
            left: 0;
            width: 100%;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px var(--card-shadow);
            opacity: 0;
            transition: all 0.5s ease;
            transform: translateX(50px);
            display: none;
        }
        
        .review-card.active {
            opacity: 1;
            transform: translateX(0);
            display: block;
        }
        
        .user-info {
                display: flex;
                align-items: center;
            margin-bottom: 20px;
        }
        
        .user-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            border: 3px solid rgb(23, 108, 101);
        }
        
        .user-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details h3 {
            margin: 0 0 5px;
            font-size: 1.2rem;
            color: var(--text-color);
        }
        
        .rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .star.half {
            position: relative;
            display: inline-block;
        }
        
        .star.half:after {
            content: "★";
            position: absolute;
            left: 0;
            top: 0;
            width: 50%;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        .review-text {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 20px;
            font-style: italic;
        }
        
        .tour-type {
            display: inline-block;
            background-color: rgb(23, 108, 101);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .review-date {
            font-size: 0.8rem;
            color: #888;
            margin-top: 10px;
            text-align: right;
        }
        
        .slider-controls {
            display: flex;
                justify-content: center;
            align-items: center;
            margin-top: 30px;
        }
        
        .prev-btn, .next-btn {
            background-color: rgb(23, 108, 101);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .prev-btn:hover, .next-btn:hover {
            background-color: rgb(18, 88, 82);
            transform: translateY(-3px);
        }
        
        .dots-container {
            display: flex;
            margin: 0 15px;
        }
        
        .dot {
            width: 12px;
            height: 12px;
            background-color: #ccc;
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .dot.active {
            background-color: rgb(23, 108, 101);
        }
        
        .review-cta {
            text-align: center;
            margin-top: 50px;
        }
        
        .review-cta p {
            margin-bottom: 15px;
            color: #666;
        }
        
        .review-btn {
            display: inline-block;
            background: rgb(23, 108, 101);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .review-btn:hover {
            background: rgb(18, 88, 82);
            color: rgb(255, 255, 255);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .reviews {
                padding: 4rem 1rem;
            }
            
            .reviews-slider {
                height: 380px;
            }
            
            .review-card {
                padding: 20px;
            }
        }
        
        /* Review Modal Styles */
        .review-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            overflow-y: auto;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .review-modal.active {
            display: block;
            opacity: 1;
        }
        
        .review-modal-content {
            position: relative;
            background-color: var(--card-bg);
            color: var(--text-color);
            margin: 20px auto;
            width: 95%;
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 20px 15px;
            transform: translateY(-30px);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(var(--primary-color), 0.1);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @media (min-width: 768px) {
            .review-modal-content {
                padding: 30px;
                margin: 50px auto;
                width: 90%;
            }
        }
        
        .review-modal.active .review-modal-content {
            transform: translateY(0);
        }
        
        .close-review-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            color: var(--text-color);
            opacity: 0.7;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.05);
            z-index: 10;
        }
        
        @media (min-width: 768px) {
            .close-review-modal {
                top: 15px;
                right: 20px;
                font-size: 28px;
                background-color: transparent;
            }
        }
        
        .close-review-modal:hover {
            background-color: rgba(0, 0, 0, 0.1);
            opacity: 1;
            transform: rotate(90deg);
        }
        
        .review-modal h3 {
            color: var(--primary-color);
            font-size: clamp(1.2rem, 5vw, 1.5rem);
            margin-bottom: 15px;
            text-align: center;
            font-weight: 700;
            padding-top: 10px;
        }
        
        @media (min-width: 768px) {
            .review-modal h3 {
                margin-bottom: 25px;
                font-size: 24px;
                padding-top: 0;
            }
        }
        
        /* Form action buttons */
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-actions button {
            padding: 12px 0;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
            .form-actions button {
                font-size: 1rem;
                padding: 12px 20px;
            }
        }
        
        .cancel-review {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid rgba(0, 0, 0, 0.2);
        }
        
        .cancel-review:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .submit-review {
            background: linear-gradient(90deg, #4776E6 0%, #8E54E9 100%);
            color: white;
            border: none;
        }
        
        .submit-review:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Footer Styles */
        .footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding-top: 3rem;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 3rem;
        }
        
        .footer h3 {
            color: #fff;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .footer h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: rgb(23, 108, 101);
        }
        
        .footer-about p {
            line-height: 1.6;
            margin-bottom: 1.5rem;
            color: #ccc;
        }
        
        .social-icons {
            display: flex;
            gap: 1rem;
        }
        
        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgb(23, 108, 101);
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            background: #fff;
            color: rgb(23, 108, 101);
            transform: translateY(-3px);
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-links a:hover {
            color: rgb(101, 255, 193);
            transform: translateX(5px);
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .contact-icon {
            font-style: normal;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .contact-item p {
            color: #ccc;
            line-height: 1.4;
        }
        
        .newsletter-form {
            display: flex;
            margin-top: 1.5rem;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        
        .newsletter-form button {
            background: rgb(23, 108, 101);
            color: white;
            border: none;
            padding: 0 1rem;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background: rgb(18, 88, 82);
        }
        
        .footer-bottom {
            background: #111;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .copyright p {
            margin: 0;
            color: #888;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .footer-bottom-links a {
            color: #888;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-bottom-links a:hover {
            color: #fff;
        }
        
        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .footer-bottom-links {
                justify-content: center;
            }
        }

        /* User profile dropdown - Modern minimalist style */
        .user-dropdown {
            display: inline-block;
            position: relative;
            margin-left: 25px;
            z-index: 1002; /* Ensure dropdown is above other elements */
        }

        .profile-btn {
            width: 120px;
            height: 40px;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 10px;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .profile-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 12px;
        }

        .profile-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
        }
        
        .profile-btn:hover:before {
            opacity: 1;
        }
        
        .profile-btn .avatar {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }
        
        .profile-btn .user-name {
            position: relative;
            z-index: 2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 65px;
        }
        
        .profile-btn .dropdown-icon {
            margin-left: auto;
            position: relative;
            z-index: 2;
            font-size: 12px;
            opacity: 0.8;
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
            transition: all 0.3s ease;
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
            color: rgb(23, 108, 101);
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
    .navbar.active {
        background-color: var(--bg-color);
        padding-bottom: 15px;
        z-index: 1000;
        overflow-y: auto;
        max-height: 85vh;
    }
    
    .user-dropdown {
        margin: 15px 30px;
        display: inline-block;
    }

    .navbar .user-dropdown .profile-btn {
        width: 140px;
        height: 40px;
        border-radius: 10px;
        padding: 0 10px;
        justify-content: flex-start;
    }
    
    .navbar .user-dropdown .profile-btn .user-name {
        max-width: 80px;
        font-size: 14px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    .navbar .user-dropdown .profile-btn .avatar {
        width: 30px;
        height: 30px;
        font-size: 16px;
        min-width: 30px;
        margin-right: 8px;
    }
    
    .navbar .user-dropdown .profile-btn .dropdown-icon {
        margin-left: 5px;
    }
    
    .navbar .user-dropdown .profile-btn .avatar {
        width: 35px;
        height: 35px;
        font-size: 18px;
    }
    
    .navbar .user-dropdown .profile-btn .user-name {
        max-width: 110px;
        font-size: 16px;
    }
    
    .navbar .user-dropdown .profile-btn .avatar {
        width: 35px;
        height: 35px;
        font-size: 18px;
    }

    .navbar .user-dropdown {
        position: relative;
        z-index: 10;
    }
    
    .profile-btn {
        width: 120px;
        height: 40px;
    }
            
            .profile-dropdown {
                position: absolute;
                width: 230px;
                right: 0;
                top: calc(100% + 5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .user-dropdown.active .profile-dropdown {
                opacity: 1;
                visibility: visible;
                transform: scale(1);
            }
            
            .navbar.active .user-dropdown {
                display: block;
                width: 100%;
                text-align: left;
            }
            
            .navbar.active .profile-btn {
                margin-left: 30px;
            }
            
            .navbar.active .profile-dropdown {
                width: calc(100% - 60px);
                margin-left: 30px;
                margin-right: 30px;
                right: auto;
                left: 0;
                position: absolute;
                top: 100%;
            }
            
            .navbar.active .user-dropdown.active {
                z-index: 1002;
            }
        }

        /* Small Mobile Devices */
        @media (max-width: 576px) {
            .user-dropdown {
                margin: 10px 20px;
            }
            
            .profile-btn {
                width: 100px;
                height: 35px;
            }
            
            .profile-dropdown {
                width: 210px;
            }
            
            .navbar .user-dropdown .profile-btn {
                width: 120px;
                height: 35px;
            }
            
            .navbar .user-dropdown .profile-btn .user-name {
                max-width: 60px;
                font-size: 13px;
            }
            
            .navbar .user-dropdown .profile-btn .avatar {
                width: 25px;
                height: 25px;
                font-size: 14px;
                min-width: 25px;
                margin-right: 6px;
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

        /* Extra Small Mobile Devices */
        @media (max-width: 375px) {
            .navbar .user-dropdown .profile-btn {
                width: 100px;
                height: 32px;
            }
            
            .navbar .user-dropdown .profile-btn .user-name {
                max-width: 45px;
                font-size: 12px;
            }
            
            .navbar .user-dropdown .profile-btn .avatar {
                width: 22px;
                height: 22px;
                font-size: 12px;
                min-width: 22px;
                margin-right: 5px;
            }
        }

        .chat-btn-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
        }
        
        .chat-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgb(23, 108, 101);
            color: white;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            font-size: 24px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .chat-btn:hover {
            background-color: rgb(18, 87, 82);
            transform: scale(1.05);
        }
        
        .chat-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            font-size: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .chat-box {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 450px;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            z-index: 998;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgb(23, 108, 101);
            color: white;
            padding: 15px;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .close-chat {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: var(--bg-alt-color);
        }
        
        .chat-welcome {
            background-color: #e9eff1;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
        }
        
        .chat-welcome p {
            margin: 0;
            color: #555;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .message.user {
            align-items: flex-end;
        }
        
        .message.admin {
            align-items: flex-start;
        }
        
        .message-content {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .user .message-content {
            background-color: rgb(101, 255, 193);
            color: #333;
            border-bottom-right-radius: 0;
        }
        
        .admin .message-content {
            background-color: #e0e0e0;
            color: #333;
            border-bottom-left-radius: 0;
        }
        
        .message-time {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        .chat-input {
            display: flex;
            padding: 10px;
            background-color: white;
            border-top: 1px solid #eee;
        }
        
        .chat-input textarea {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 15px;
            resize: none;
            height: 40px;
            font-size: 16px; /* Increased font size to prevent zoom */
            outline: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            touch-action: manipulation; /* Prevent browser manipulation */
        }
        
        .chat-input button {
            margin-left: 10px;
            background-color: rgb(23, 108, 101);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .chat-input button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .chat-input button:not(:disabled):hover {
            background-color: rgb(18, 87, 82);
        }
        
        @media (max-width: 576px) {
            .chat-box {
                width: 90%;
                right: 5%;
                left: 5%;
            }
            
            /* Fix for mobile zoom issues */
            .chat-input textarea {
                font-size: 16px !important; /* iOS won't zoom if font size is at least 16px */
                transform: scale(1); /* Helps prevent zoom on some Android devices */
                transform-origin: left top;
                touch-action: manipulation; /* Prevents browser manipulation */
            }
        }

        /* New styles for reply, edit, delete functionality */
        .message {
            position: relative;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .message .message-actions {
            display: none;
            position: absolute;
            right: 5px;
            top: -20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            padding: 3px 8px;
        }

        .admin .message-actions {
            left: 5px;
            right: auto;
        }

        .message:hover .message-actions {
            display: flex;
        }

        .action-btn {
            background: none;
            border: none;
            font-size: 12px;
            color: #555;
            margin: 0 3px;
            cursor: pointer;
            padding: 2px;
            transition: color 0.2s;
        }

        .action-btn:hover {
            color: rgb(23, 108, 101);
        }

        .action-btn.delete-btn:hover {
            color: #dc3545;
        }

        .reply-preview, .edit-preview {
            display: flex;
            background-color: rgba(23, 108, 101, 0.1);
            padding: 8px 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            align-items: center;
        }

        .reply-content, .edit-content {
            flex: 1;
            overflow: hidden;
            padding-left: 10px;
            border-left: 2px solid rgb(23, 108, 101);
        }

        .reply-content p, .edit-content p {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            color: #666;
        }

        .cancel-action {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 0 5px;
        }

        .cancel-action:hover {
            color: #dc3545;
        }

        .replied-message {
            margin-bottom: 5px;
            font-size: 12px;
            background-color: rgba(23, 108, 101, 0.1);
            padding: 5px 8px;
            border-radius: 8px;
            border-left: 2px solid rgb(23, 108, 101);
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 85%;
        }

        .message-edited {
            font-style: italic;
            margin-left: 5px;
            font-size: 10px;
            color: #999;
        }

        /* Adjust the existing message containers to accommodate actions */
        .message-content {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
            position: relative;
        }

        .message-time {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }

        /* Additional media queries for smaller screens */
        @media (max-width: 576px) {
            .login-btn {
                margin: 10px 20px;
                padding: 6px 15px;
                font-size: 13px;
                max-width: 100px;
                text-align: center;
                justify-content: center;
                display: flex;
                align-items: center;
            }
            
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
            
            /* Adjust user dropdown for small mobile */
            .user-dropdown {
                margin: 10px 20px;
            }
            
            .profile-btn {
                width: 100px;
                max-width: 100px;
                height: 35px;
                font-size: 13px;
            }
            
            .profile-btn .avatar {
                width: 25px;
                height: 25px;
                font-size: 12px;
                margin-right: 5px;
            }
            
            .profile-btn .user-name {
                max-width: 60px;
            }
        }
        
        @media (max-width: 375px) {
            .login-btn {
                margin: 8px 15px;
                padding: 5px 10px;
                font-size: 12px;
                max-width: 80px;
                min-width: 70px;
            }
            
            .navbar a {
                padding: 8px 15px;
                font-size: 16px;
                margin: 8px 0;
            }
            
            /* Adjust user dropdown for extra small mobile */
            .user-dropdown {
                margin: 8px 15px;
            }
            
            .profile-btn {
                width: 80px;
                max-width: 80px;
                height: 32px;
                font-size: 12px;
            }
            
            .profile-btn .avatar {
                width: 22px;
                height: 22px;
                font-size: 11px;
                margin-right: 4px;
            }
            
            .profile-btn .user-name {
                max-width: 45px;
            }
        }

        /* Packages Section Styles */
        
            /* Team Section Styles - Modern 3D Version */
    .team {
        padding: 8rem 2rem 10rem;
        background: var(--bg-color);
        position: relative;
        overflow: hidden;
        color: var(--text-color);
    }
    
    /* Background animation removed */
    
    .section-title {
        position: relative;
        z-index: 1;
    }
    
    .team-container {
        position: relative;
        z-index: 1;
        max-width: 1200px;
        margin: 2rem auto 0;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 2.5rem;
        perspective: 1000px;
        justify-content: center;
    }
    
    .team-member {
        position: relative;
        height: 380px;
        border-radius: 20px;
        overflow: visible;
        transform-style: preserve-3d;
        transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        margin-bottom: 10px;
    }
    
    .team-member:hover {
        transform: rotateY(180deg);
    }
    
    .member-front,
    .member-back {
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        border-radius: 20px;
        overflow: hidden;
    }
    
    .member-front {
        background: var(--card-bg);
        transform-style: preserve-3d;
        box-shadow: 
            0 15px 35px rgba(0, 0, 0, 0.1),
            0 3px 10px rgba(0, 0, 0, 0.07),
            0 1px 3px rgba(0, 0, 0, 0.05),
            0 1px 2px rgba(23, 108, 101, 0.15),
            inset 0 2px 0 rgba(255, 255, 255, 0.5),
            inset 0 -3px 0 rgba(0, 0, 0, 0.05);
    }
    
    .member-back {
        background: linear-gradient(145deg, rgba(23, 108, 101, 0.8), rgba(101, 255, 193, 0.8));
        transform: rotateY(180deg);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 1.2rem;
        color: white;
        text-align: center;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .member-image {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    
    .member-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
        transition: transform 0.5s ease;
    }
    
    .member-front::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 150px;
        background: linear-gradient(to top, 
            var(--card-bg) 30%, 
            rgba(var(--card-bg-rgb, 255, 255, 255), 0.9) 60%,
            rgba(var(--card-bg-rgb, 255, 255, 255), 0.1) 100%);
    }
    
    .member-info-front {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 1rem 1.5rem;
        text-align: center;
        z-index: 2;
    }
    
    .member-info-front h3 {
        margin: 0;
        font-size: 1.3rem;
        color: var(--text-color);
        font-weight: 700;
        margin-bottom: 0.4rem;
    }
    
    .card-flip-hint {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(23, 108, 101, 0.7);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        opacity: 0.7;
        transition: opacity 0.3s, transform 0.3s;
        z-index: 10;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(23, 108, 101, 0.7);
        }
        70% {
            transform: scale(1.1);
            box-shadow: 0 0 0 10px rgba(23, 108, 101, 0);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(23, 108, 101, 0);
        }
    }
    
    .team-member:hover .card-flip-hint {
        opacity: 0;
    }
    
    .position-front {
        display: inline-block;
        color: rgb(23, 108, 101);
        font-weight: 600;
        font-size: 0.85rem;
        padding: 4px 12px;
        background-color: rgba(23, 108, 101, 0.1);
        border-radius: 16px;
        margin-bottom: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .member-bio {
        margin-bottom: 1rem;
        font-size: 0.85rem;
        line-height: 1.4;
        max-height: 120px;
        overflow-y: auto;
        padding-right: 5px;
    }
    
    .member-bio::-webkit-scrollbar {
        width: 5px;
    }
    
    .member-bio::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    
    .member-bio::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }
    
    .member-bio::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
    
    .member-social {
        position: relative;
        display: flex;
        justify-content: center;
        margin-top: 15px;
        gap: 10px;
        width: 100%;
    }
    
    .member-social:before {
        content: '';
        position: absolute;
        left: 15%;
        right: 15%;
        height: 2px;
        top: -15px;
        background: linear-gradient(to right, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0));
    }
    
    .member-social a {
        --size: 32px;
        position: relative;
        width: var(--size);
        height: var(--size);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        font-size: 0.9rem;
        overflow: visible;
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: 1;
    }
    
    .member-social a:before,
    .member-social a:after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 8px;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: -1;
    }
    
    .member-social a:before {
        background: var(--icon-bg, linear-gradient(45deg, #333, #555));
        opacity: 0.85;
        transform-origin: center bottom;
        box-shadow: 
            0 4px 8px rgba(0, 0, 0, 0.2),
            0 0 0 1px rgba(255, 255, 255, 0.08);
    }
    
    .member-social a:after {
        content: '';
        background: radial-gradient(circle at 50% 30%, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 60%);
        opacity: 0;
    }
    
    .member-social a:hover {
        transform: translateY(-4px) scale(1.1);
        z-index: 5;
    }
    
    .member-social a:hover:before {
        transform: perspective(400px) rotateX(5deg) scale(1.05);
        box-shadow: 
            0 8px 16px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.12);
    }
    
    .member-social a:hover:after {
        opacity: 1;
    }
    
    .member-social a i {
        position: relative;
        z-index: 2;
        filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.2));
        transition: transform 0.3s ease;
    }
    
    .member-social a:hover i {
        transform: scale(1.2);
        filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.3));
    }
    
    /* Platform-specific styling */
    .member-social a.facebook {
        --icon-bg: linear-gradient(145deg, #1877f2, #0d65d9);
    }
    
    .member-social a.twitter {
        --icon-bg: linear-gradient(145deg, #1da1f2, #0c85d0);
    }
    
    .member-social a.instagram {
        --icon-bg: linear-gradient(145deg, #833ab4, #fd1d1d, #fcb045);
    }
    
    .member-social a.linkedin {
        --icon-bg: linear-gradient(145deg, #0077b5, #00669c);
    }
    
    /* Interactive hover effect that affects neighbors */
    .member-social:hover a:not(:hover) {
        transform: scale(0.92);
        opacity: 0.7;
    }
    
    /* Staggered entrance animation */
    @keyframes slideUp {
        0% { transform: translateY(10px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    
    .member-social a {
        animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        opacity: 0;
    }
    
    .member-social a:nth-child(1) { animation-delay: 0.1s; }
    .member-social a:nth-child(2) { animation-delay: 0.2s; }
    .member-social a:nth-child(3) { animation-delay: 0.3s; }
    .member-social a:nth-child(4) { animation-delay: 0.4s; }
    
    /* Shine effect */
    .member-social a:before {
        background-size: 200% 200%;
    }
    
    .member-social a:hover:before {
        animation: shine 1.5s linear infinite;
    }
    
    @keyframes shine {
        0% { background-position: 0% 0%; }
        25% { background-position: 100% 0%; }
        50% { background-position: 100% 100%; }
        75% { background-position: 0% 100%; }
        100% { background-position: 0% 0%; }
    }
    
    /* For mobile devices without hover capability */
    @media (hover: none) and (max-width: 768px) {
        .team-member {
            height: auto;
        }
        
        .member-front, 
        .member-back {
            position: relative;
            backface-visibility: visible;
        }
        
        .team-member:hover {
            transform: none;
        }
        
        .member-back {
            transform: none;
            margin-top: 1rem;
            height: auto;
        }
        
        .member-front::after {
            display: none;
        }
        
        .member-image {
            position: relative;
            height: 220px;
        }
        
        .member-info-front {
            position: relative;
            padding: 0.8rem;
        }
        
        .card-flip-hint {
            display: none;
        }
    }
    
    /* Responsive design for team section */
    @media (max-width: 992px) {
        .team-container {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 2rem;
            padding: 0 1rem;
        }
        
        .team-member {
            height: 320px;
        }
    }
    
    @media (max-width: 768px) {
        .team {
            padding: 5rem 1rem 7rem;
        }
        
        .team-member {
            height: 300px;
        }
        
        .team-container {
            gap: 2rem;
        }
        
        .member-front::after {
            height: 130px;
        }
    }
    
    @media (max-width: 576px) {
        .team-container {
            grid-template-columns: 1fr;
            max-width: 260px;
            margin: 0 auto;
        }
        
        .team-member {
            height: 320px;
        }
        
        .member-info-front h3 {
            font-size: 1.1rem;
        }
        
        .position-front {
            font-size: 0.75rem;
            padding: 3px 10px;
        }
        
        .member-front::after {
            height: 120px;
        }
        
        .member-bio {
            max-height: 100px;
            margin-bottom: 0.8rem;
        }
    }

    /* Review Form Section */
    .review-form-section {
        background: #f8f9fa;
        padding: 40px 15px;
        border-top: 1px solid #eee;
    }
    
    .review-form-container {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        padding: 25px 20px;
        max-width: 800px;
        margin: 0 auto;
        width: 100%;
    }
    
    .review-form-container h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #343a40;
        font-size: clamp(1.5rem, 4vw, 2rem);
    }
    
    .review-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    @media (min-width: 768px) {
        .review-form-section {
            padding: 60px 30px;
        }
        
        .review-form-container {
            padding: 30px;
        }
        
        .review-form {
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    }
    
    .form-group {
        margin-bottom: 20px;
        position: relative;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-color);
        font-size: 0.95rem;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        background-color: rgba(var(--card-bg-rgb), 0.8);
        color: var(--text-color);
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .dark-mode .form-group input,
    .dark-mode .form-group select,
    .dark-mode .form-group textarea {
        border-color: rgba(255, 255, 255, 0.1);
        background-color: rgba(45, 45, 45, 0.8);
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-color), 0.1);
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    /* Rating star styles */
    .rating-select {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 15px 10px;
        border-radius: 8px;
        background: linear-gradient(145deg, #f0f0f0, #ffffff);
        box-shadow: 5px 5px 10px #d9d9d9, -5px -5px 10px #ffffff;
        margin-top: 10px;
        position: relative;
        overflow: hidden;
    }
    
    @media (min-width: 768px) {
        .rating-select {
            padding: 20px;
        }
    }
    
    .stars-container {
        display: flex;
        justify-content: space-between;
        width: 100%;
        position: relative;
        margin-bottom: 5px;
    }
    
    .rating-scale {
        display: flex;
        justify-content: space-between;
        width: 100%;
        padding: 0 2%;
        margin-bottom: 10px;
    }
    
    .scale-point {
        font-size: 10px;
        color: #777;
        width: 20%;
        text-align: center;
    }
    
    @media (min-width: 768px) {
        .scale-point {
            font-size: 12px;
        }
    }
    
    .rating-slider {
        width: 100%;
        height: 8px;
        border-radius: 4px;
        background: #e0e0e0;
        margin-bottom: 5px;
        position: relative;
    }
    
    .rating-progress {
        position: absolute;
        height: 100%;
        width: 0%;
        left: 0;
        top: 0;
        background: linear-gradient(90deg, #4776E6 0%, #8E54E9 100%);
        border-radius: 4px;
        transition: width 0.3s ease;
        animation: progressPulse 2s infinite;
    }
    
    .rating-star {
        cursor: pointer;
        position: relative;
        font-size: 26px;
        width: 20%;
        text-align: center;
        z-index: 2;
        transition: all 0.3s ease;
        color: transparent;
        -webkit-background-clip: text;
        background-clip: text;
        background-image: linear-gradient(45deg, #ccc, #ddd);
    }
    
    @media (min-width: 768px) {
        .rating-star {
            font-size: 32px;
        }
    }
    
    /* Active star styles */
    .rating-star.fas, 
    .rating-star.selected {
        background-image: linear-gradient(45deg, #4776E6, #8E54E9);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    /* Rating value tooltip */
    .rating-value {
        position: absolute;
        top: -20px;
        left: 0;
        background: linear-gradient(90deg, #4776E6 0%, #8E54E9 100%);
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        transform: translateX(0%);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 3;
    }
    
    @media (min-width: 768px) {
        .rating-value {
            top: -25px;
            padding: 2px 8px;
            font-size: 14px;
        }
    }
    
    .rating-select:hover .rating-value {
        opacity: 1;
    }
    
    /* Hover effects for stars */
    .rating-star:hover {
        transform: translateY(-5px);
    }
    
    /* Visual indicator for rating selection */
    .rating-select:after {
        content: "Select your rating";
        position: absolute;
        bottom: 0;
        right: 0;
        padding: 5px 8px;
        font-size: 10px;
        color: #777;
        opacity: 0.8;
    }
    
    @media (min-width: 768px) {
        .rating-select:after {
            padding: 5px 10px;
            font-size: 12px;
        }
    }
    
    /* Animation for the progress bar */
    @keyframes progressPulse {
        0% { opacity: 0.7; }
        50% { opacity: 1; }
        100% { opacity: 0.7; }
    }
    
    /* File upload styles */
    .review-upload {
        position: relative;
    }
    
    .review-upload input[type="file"] {
        background-color: transparent;
        padding: 10px 0;
        border: none;
    }
    
    .upload-preview {
        margin-top: 10px;
        max-width: 150px;
        max-height: 150px;
        border-radius: 10px;
        overflow: hidden;
        display: none;
    }
    
    @media (min-width: 768px) {
        .upload-preview {
            max-width: 200px;
            max-height: 200px;
        }
    }
    
    .upload-preview img {
        width: 100%;
        height: auto;
        object-fit: cover;
    }
    </style>
</head>
<body>
    <header class="header">
        <a href="#" class="logo">
            <img src="images/logo-4.png" alt="Adventure Travel.lks Logo">
        </a >

        <div class="menu-toggle">☰</div>

        <nav class="navbar">
            <a href="#home">Home</a>
            <a href="#packages">Packages</a>
            <a href="#vehicle-hire">Vehicle Hire</a>
            <a href="#destinations">Destinations</a>
            <a href="#review">Reviews</a>
            <a href="contact_us.php">Contact Us</a>
            <a href="about_us/about_us.php">About Us</a>
            <?php if ($is_logged_in): ?>
                <div class="user-dropdown">
                    <div class="profile-btn">
                        <?php 
                        // Get first letters of names for initials
                        $initials = '';
                        $name_parts = explode(' ', $user_name);
                        foreach ($name_parts as $part) {
                            if (!empty($part)) {
                                $initials .= strtoupper(substr($part, 0, 1));
                                if (strlen($initials) >= 2) break;
                            }
                        }
                        
                        // Get first name for display
                        $first_name = !empty($name_parts[0]) ? $name_parts[0] : $user_name;
                        ?>
                        <div class="avatar"><?php echo $initials; ?></div>
                        <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                        <div class="dropdown-icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="profile-dropdown">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php echo $initials; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($user_name); ?></h4>
                            <p>@<?php echo htmlspecialchars($username); ?></p>
                        </div>
                        <div class="menu-section">
                            <a href="profile.php" class="menu-item">
                                <span class="icon">👤</span>
                                My Profile
                            </a>
                            <a href="settings.php" class="menu-item">
                                <span class="icon">⚙️</span>
                                Settings
                            </a>
                            <a href="?logout=1" class="menu-item logout">
                                <span class="icon">🚪</span>
                                Log Out
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
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

    <section class="home" id="home">
        <div class="slide active"></div>
        <div class="slide"></div>
        <div class="slide"></div>
        
        <div class="home-content">
            <div class="content-box active" data-slide="1">
                <span>Never Stop</span>
                <h1>Exploring</h1>
                <p>"Dream big and chase your passions. Life is a journey filled with opportunities waiting to be explored."</p>
                <a href="#packages" class="btn">Get Started</a>
                <div class="social-links">
                    <a href="https://wa.me/+94718628992" class="social-link"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/share/15N56JcC6e/?mibextid=wwXIfr" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/adventure_travel.lk?igsh=eDkyNXk4cW1lamlw" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="mailto:adventuretravel.lk@gmail.com" class="social-link"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            
            <div class="content-box" data-slide="2">
                <span>Make Tour</span>
                <h1>Amazing</h1>
                <p>"Make your tour amazing by embracing spontaneity and connecting with locals. Plan enough to stay organized but leave room for surprises!"</p>
                <a href="#packages" class="btn">Get Started</a>
                <div class="social-links">
                    <a href="https://wa.me/+94718628992" class="social-link"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/share/15N56JcC6e/?mibextid=wwXIfr" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/adventure_travel.lk?igsh=eDkyNXk4cW1lamlw" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="mailto:adventuretravel.lk@gmail.com" class="social-link"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            
            <div class="content-box" data-slide="3">
                <span>Explore the</span>
                <h1>New World</h1>
                <p>"Explore the new world with an open heart and curious mind. Every journey is a chance to discover unseen landscapes."</p>               
                <a href="#packages" class="btn">Get Started</a> 
                <div class="social-links">
                    <a href="https://wa.me/+94718628992" class="social-link"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/share/15N56JcC6e/?mibextid=wwXIfr" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/adventure_travel.lk?igsh=eDkyNXk4cW1lamlw" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="mailto:adventuretravel.lk@gmail.com" class="social-link"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
        </div>
        
        <div class="slider-controls">
            <div class="slider-dot active" data-slide="1"></div>
            <div class="slider-dot" data-slide="2"></div>
            <div class="slider-dot" data-slide="3"></div>
        </div>
    </section>

    <!-- Packages Section -->
    <section class="packages" id="packages">
        <h2 class="section-title">Our <span>Packages</span></h2>
        
        <div class="packages-container">
            <!-- Tour Packages -->
            <div class="package-card">
                <div class="card-image">
                    <img src="images/tour-1.png" alt="Tour Package">
                    
                </div>
                <div class="card-content">
                    <h3>Tour Packages</h3>
                    <p>Make your dream holiday come true with Adventure Travel.lks Sri Lanka tour packages.</p>
                    <div class="card-features">
                        <div class="feature">
                            <i class="feature-icon">🏨</i>
                            <span>Luxury Hotels</span>
                        </div>
                        <div class="feature">
                            <i class="feature-icon">🍽️</i>
                            <span>Meals Included</span>
                        </div>
                        <div class="feature">
                            <i class="feature-icon">🚐</i>
                            <span>Transportation</span>
                        </div>
                    </div>
                    <a href="tour_packages/tour_packages.php" class="card-btn">View Packages</a>
                </div>
            </div>
            
            <!-- One Day Tour Packages -->
            <div class="package-card">
                <div class="card-image">
                    <img src="images/tour-2.png" alt="One Day Tour Package">
                    
                </div>
                <div class="card-content">
                    <h3>One Day Tour Packages</h3>
                    <p>Discover Sri Lanka in a day with our variety of one-day tour packages.</p>
                    <div class="card-features">
                        <div class="feature">
                            <i class="feature-icon">🥗</i>
                            <span>Lunch Included</span>
                        </div>
                        <div class="feature">
                            <i class="feature-icon">🚍</i>
                            <span>Pickup Service</span>
                        </div>
                        <div class="feature">
                            <i class="feature-icon">📷</i>
                            <span>Photo Spots</span>
                        </div>
                    </div>
                    <a href="one_day_tour_packages/one_day_tour.php" class="card-btn">View Packages</a>
                </div>
            </div>
            
            <!-- Special Tour Packages -->
            <div class="package-card">
                <div class="card-image">
                    <img src="images/tour-3.png" alt="Special Tour Package">

                </div>
                <div class="card-content">
                    <h3>Special Tour Packages</h3>
                    <p>Golden beaches, ancient temples, and lush tea plantations await you.</p>
                    <div class="card-features">
                        <div class="feature">
                            <i class="feature-icon">🏆</i>
                            <span>VIP Access</span>
                        </div>
                        <div class="feature">
                            <i class="feature-icon">🎁</i>
                            <span>Special Perks</span>
                        </div>
                        <div class="feature">
                            <i class="feature-icon">👨‍👩‍👧‍👦</i>
                            <span>Small Groups</span>
                        </div>
                    </div>
                    <a href="special_tour_packages/special_tour.php" class="card-btn">View Packages</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Vehicle Hire Section -->
    <section class="vehicle-hire" id="vehicle-hire">
        <h2 class="section-title">Vehicle <span>Hire</span></h2>
        
        <div class="vehicles-container">
            <?php if (!empty($vehicles)): ?>
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="vehicle-card">
                        <div class="card-image">
                            <img src="images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($vehicle['description'], 0, 100)) . '...'; ?></p>
                            <div class="card-features">
                                <div class="feature">
                                    <i class="feature-icon">🚘</i>
                                    <span><?php echo htmlspecialchars($vehicle['type']); ?></span>
                                </div>
                                <div class="feature">
                                    <i class="feature-icon">👨‍👩‍👧‍👦</i>
                                    <span>Maximum Capacity: <?php echo $vehicle['capacity']; ?> persons</span>
                                </div>
                                <div class="feature">
                                    <i class="feature-icon">💰</i>
                                    <span>$<?php echo number_format($vehicle['price_per_day'], 2); ?> per day (150km included) </span>
                                </div>
                            </div>
                            <a href="vehicles/vehicle_details.php?id=<?php echo $vehicle['vehicle_id']; ?>" class="card-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">Sorry, no vehicles are available at the moment. Please check back later or contact us for alternatives.</div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Destinations Section -->
    <section class="destinations" id="destinations">
        <h2 class="section-title">Popular <span>Destinations</span></h2>
        
        <div class="destinations-container">
            <!-- Anuradhapura -->
            <div class="destination-card">
                <img src="destinations/destination-1.jpg" alt="Anuradhapura">
                <div class="destination-content">
                    <h3>Anuradhapura</h3>
                    <p>Ancient city with sacred Buddhist sites and ruins dating back over 2,000 years.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
            
            <!-- Colombo -->
            <div class="destination-card">
                <img src="destinations/destination-2.jpg" alt="Colombo">
                <div class="destination-content">
                    <h3>Colombo</h3>
                    <p>The vibrant capital city with colonial buildings, museums, and bustling markets.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
            
            <!-- Galle Fort -->
            <div class="destination-card">
                <img src="destinations/destination-3.jpg" alt="Galle Fort">
                <div class="destination-content">
                    <h3>Galle</h3>
                    <p>Historic fort with Dutch colonial architecture, boutiques, and ocean views.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
            
            <!-- Hatton -->
            <div class="destination-card">
                <img src="destinations/destination-4.png" alt="Hatton">
                <div class="destination-content">
                    <h3>Hatton</h3>
                    <p>Scenic hill country with lush tea plantations and spectacular mountain views.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
            
            <!-- Horton Plains -->
            <div class="destination-card">
                <img src="destinations/destination-5.jpg" alt="Horton Plains">
                <div class="destination-content">
                    <h3>Horton Plains</h3>
                    <p>National park with unique cloud forests, wildlife, and the famous World's End viewpoint.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
            
            <!-- Kandy -->
            <div class="destination-card">
                <img src="destinations/destination-6.jpg" alt="Kandy">
                <div class="destination-content">
                    <h3>Kandy</h3>
                    <p>Cultural capital and home to the Temple of the Sacred Tooth Relic.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
            
            <!-- Hikkaduwa -->
            <div class="destination-card">
                <img src="destinations/destination-7.jpg" alt="Hikkaduwa">
                <div class="destination-content">
                    <h3>Hikkaduwa</h3>
                    <p>Beach resort town known for coral reefs, surfing, and vibrant nightlife.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>

            <!-- Kithulgala -->
            <div class="destination-card">
                <img src="destinations/destination-8.jpg" alt="Kithulgala">
                <div class="destination-content">
                    <h3>Kithulgala</h3>
                    <p>Adventure hub ideal for white water rafting, jungle treks, and bird watching.</p>
                    <a href="destinations/destinations.php" class="destination-btn">Explore</a>
                </div>
            </div>
        </div>
        
        <div class="explore-more-container">
            <a href="destinations/destinations.php" class="explore-more-btn">Explore More Destinations</a>
        </div>
    </section>

    <!-- Reviews Section -->
    <section class="reviews" id="review">
        <h2 class="section-title">Customer <span>Reviews</span></h2>
        
        <div class="reviews-container">
            <div class="reviews-slider">
                <?php if (count($approved_reviews) > 0): ?>
                    <?php foreach ($approved_reviews as $index => $review): ?>
                        <div class="review-card <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="user-info">
                                <div class="user-img">
                                    <?php if (!empty($review['photo'])): ?>
                                        <img src="images/<?php echo htmlspecialchars($review['photo']); ?>" alt="User Review Photo">
                                    <?php else: ?>
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($review['name']); ?>&background=random" alt="User">
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <h3><?php echo htmlspecialchars($review['name']); ?></h3>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="star">★</i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <p class="review-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                            <div class="tour-type"><?php echo htmlspecialchars($review['tour_type']); ?></div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default review if no approved reviews exist -->
                    <div class="review-card active">
                        <div class="user-info">
                            <div class="user-img">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
                            </div>
                            <div class="user-details">
                                <h3>David Thompson</h3>
                                <div class="rating">
                                    <i class="star">★</i>
                                    <i class="star">★</i>
                                    <i class="star">★</i>
                                    <i class="star">★</i>
                                    <i class="star">★</i>
                                </div>
                            </div>
                        </div>
                        <p class="review-text">"Our tour to Kandy and the cultural triangle was exceptional! The guide was knowledgeable and the accommodations were perfect. Highly recommend Adventure Travel.lk for anyone looking to explore Sri Lanka."</p>
                        <div class="tour-type">Cultural Tour Package</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="slider-controls">
                <div class="prev-btn" onclick="prevReview()">❮</div>
                <div class="dots-container">
                    <?php 
                    $total_reviews = count($approved_reviews) > 0 ? count($approved_reviews) : 1;
                    for ($i = 0; $i < $total_reviews; $i++): 
                    ?>
                        <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="showReview(<?php echo $i; ?>)"></span>
                    <?php endfor; ?>
                </div>
                <div class="next-btn" onclick="nextReview()">❯</div>
            </div>
        </div>
        
                    <div class="review-cta">
                <p>Share your experience with us and help others plan their adventure</p>
                <a href="#" class="review-btn" id="open-review-modal">Write a Review</a>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 'review_submitted'): ?>
                <div class="review-message success" style="margin-top: 20px; padding: 10px 20px; background-color: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; border-radius: 4px;">
                    <p style="margin: 0; color: #28a745; font-weight: bold;">Thank you for your review! It has been submitted for approval.</p>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="review-message error" style="margin-top: 20px; padding: 10px 20px; background-color: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545; border-radius: 4px;">
                    <p style="margin: 0; color: #dc3545; font-weight: bold;">
                        <?php 
                            $error = $_GET['error'];
                            switch($error) {
                                case 'review_fields_required':
                                    echo 'Please fill in all required fields.';
                                    break;
                                case 'invalid_email':
                                    echo 'Please enter a valid email address.';
                                    break;
                                case 'invalid_rating':
                                    echo 'Please select a rating between 1 and 5 stars.';
                                    break;
                                case 'invalid_file_type':
                                    echo 'Please upload only JPEG, PNG, or GIF images.';
                                    break;
                                case 'review_submission_failed':
                                    echo 'There was an error submitting your review. Please try again later.';
                                    break;
                                default:
                                    echo 'An error occurred. Please try again.';
                            }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        
        <!-- Review Modal -->
        <div id="review-modal" class="review-modal">
            <div class="review-modal-content">
                <span class="close-review-modal">&times;</span>
                <h3>Write Your Review</h3>
                <p style="margin-bottom: 20px; color: #666; font-size: 0.9rem;">Fields marked with <span style="color: #dc3545;">*</span> are required</p>
                <form id="review-form" method="post" action="submit_review.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="review-name">Your Name <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="review-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="review-email">Email <span style="color: #dc3545;">*</span></label>
                        <input type="email" id="review-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="review-tour-type">Tour Type <span style="color: #dc3545;">*</span></label>
                        <select id="review-tour-type" name="tour_type" required>
                            <option value="">Select Tour Type</option>
                            <option value="Tour Package">Tour Package</option>
                            <option value="One Day Tour Package">One Day Tour Package</option>
                            <option value="Special Tour Package">Special Tour Package</option>
                            <option value="Vehicle Hire">Vehicle Hire</option>
                            <option value="Custom Experience">Custom Experience</option>
                            <option value="other">Other (Specify)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="custom-tour-type-container" style="display: none;">
                        <label for="custom-tour-type">Specify Tour Type <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="custom-tour-type" name="custom_tour_type" placeholder="Enter your tour type">
                    </div>
                    <div class="form-group">
                        <label for="review-rating">Rating <span style="color: #dc3545;">*</span></label>
                        <div class="rating-select">
                            <div class="rating-value">0</div>
                            <div class="stars-container">
                                <i class="rating-star far fa-star" data-rating="1" title="Poor"></i>
                                <i class="rating-star far fa-star" data-rating="2" title="Fair"></i>
                                <i class="rating-star far fa-star" data-rating="3" title="Good"></i>
                                <i class="rating-star far fa-star" data-rating="4" title="Very Good"></i>
                                <i class="rating-star far fa-star" data-rating="5" title="Excellent"></i>
                            </div>
                            <div class="rating-slider">
                                <div class="rating-progress"></div>
                            </div>
                            <div class="rating-scale">
                                <div class="scale-point">Poor</div>
                                <div class="scale-point">Fair</div>
                                <div class="scale-point">Good</div>
                                <div class="scale-point">Very Good</div>
                                <div class="scale-point">Excellent</div>
                            </div>
                            <input type="hidden" id="review-rating" name="rating" value="0" required>
                        </div>
                        <div id="rating-error" class="form-error" style="display: none; color: #dc3545; margin-top: 15px; font-size: 0.9rem; text-align: center;">Please select a rating by clicking the stars above</div>
                    </div>
                    <div class="form-group">
                        <label for="review-text">Your Review <span style="color: #dc3545;">*</span></label>
                        <textarea id="review-text" name="review" rows="5" required></textarea>
                    </div>
                    <div class="form-group review-upload">
                        <label for="review-photo">Upload Your Photo</label>
                        <input type="file" id="review-photo" name="photo" accept="image/*">
                        <div class="upload-preview"></div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-review">Cancel</button>
                        <button type="submit" class="submit-review">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team" id="team">
        <h2 class="section-title">Our <span>Team</span></h2>
        
        <div class="team-container">
            <?php if (!empty($team_members)): ?>
                <?php foreach ($team_members as $member): ?>
                    <div class="team-member">
                        <div class="member-front">
                            <div class="card-flip-hint"><i class="fas fa-sync-alt"></i></div>
                            <div class="member-image">
                                <img src="images/<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                            </div>
                            <div class="member-info-front">
                                <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                                <p class="position-front"><?php echo htmlspecialchars($member['position']); ?></p>
                            </div>
                        </div>
                        <div class="member-back">
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p class="member-bio"><?php echo htmlspecialchars($member['bio'] ?? 'Experienced travel professional with a passion for creating unforgettable adventures. Expert in Sri Lanka tourism and committed to exceptional customer service.'); ?></p>
                            <div class="member-social">
                                <?php if (!empty($member['facebook'])): ?>
                                    <a href="<?php echo htmlspecialchars($member['facebook']); ?>" target="_blank" class="facebook" aria-label="Facebook Profile"><i class="fab fa-facebook-f"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($member['twitter'])): ?>
                                    <a href="<?php echo htmlspecialchars($member['twitter']); ?>" target="_blank" class="twitter" aria-label="Twitter Profile"><i class="fab fa-twitter"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($member['instagram'])): ?>
                                    <a href="<?php echo htmlspecialchars($member['instagram']); ?>" target="_blank" class="instagram" aria-label="Instagram Profile"><i class="fab fa-instagram"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($member['linkedin'])): ?>
                                    <a href="<?php echo htmlspecialchars($member['linkedin']); ?>" target="_blank" class="linkedin" aria-label="LinkedIn Profile"><i class="fab fa-linkedin-in"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <p>No team members found</p>    
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Function to auto-refresh reviews section every 5 minutes
        function setupReviewRefresh() {
            setInterval(function() {
                // Fetch updated reviews without refreshing the whole page
                fetch('fetch_reviews.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Replace reviews with new content
                            document.querySelector('.reviews-slider').innerHTML = data.html;
                            
                            // Update dots container
                            document.querySelector('.dots-container').innerHTML = data.dots_html;
                            
                            // Reset slider state
                            currentReview = 0;
                            
                            // Reset review cards
                            const reviewCards = document.querySelectorAll('.review-card');
                            reviewCards.forEach((card, index) => {
                                card.classList.remove('active');
                                if (index === 0) card.classList.add('active');
                            });
                        }
                    })
                    .catch(error => console.error('Error refreshing reviews:', error));
            }, 300000); // 5 minutes
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize review refresh
            setupReviewRefresh();
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
                // Handle dropdown toggle for both mobile and desktop
                const profileBtn = userDropdown.querySelector('.profile-btn');
                
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking elsewhere
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target) && userDropdown.classList.contains('active')) {
                        userDropdown.classList.remove('active');
                    }
                });
                
                // Prevent dropdown toggle from closing navbar in mobile view
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
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
        });
        
        // Image slider with content
        const slides = document.querySelectorAll('.slide');
        const contentBoxes = document.querySelectorAll('.content-box');
        const dots = document.querySelectorAll('.slider-dot');
        let currentSlide = 1;
        
        function showSlide(slideNumber) {
            // Hide all slides and content
            slides.forEach(slide => slide.classList.remove('active'));
            contentBoxes.forEach(content => content.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[slideNumber - 1].classList.add('active');
            contentBoxes[slideNumber - 1].classList.add('active');
            dots[slideNumber - 1].classList.add('active');
            currentSlide = slideNumber;
        }
        
        // Add click event to dots
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const slideNumber = parseInt(dot.getAttribute('data-slide'));
                showSlide(slideNumber);
            });
        });
        
        // Auto slide change
        setInterval(() => {
            currentSlide = currentSlide < 3 ? currentSlide + 1 : 1;
            showSlide(currentSlide);
        }, 5000);

        // Reviews slider functionality
        let currentReview = 0;
        const reviews = document.querySelectorAll('.review-card');
        const reviewDots = document.querySelectorAll('.dots-container .dot');
        
        function showReview(index) {
            reviews.forEach(review => review.classList.remove('active'));
            reviewDots.forEach(dot => dot.classList.remove('active'));
            
            reviews[index].classList.add('active');
            reviewDots[index].classList.add('active');
            currentReview = index;
        }
        
        function nextReview() {
            currentReview = (currentReview + 1) % reviews.length;
            showReview(currentReview);
        }
        
        function prevReview() {
            currentReview = (currentReview - 1 + reviews.length) % reviews.length;
            showReview(currentReview);
        }
        
        // Auto change reviews every 8 seconds
        setInterval(nextReview, 8000);
        
        // Review Modal Functionality
        const reviewModal = document.getElementById('review-modal');
        const openReviewModalBtn = document.getElementById('open-review-modal');
        const closeReviewModalBtn = document.querySelector('.close-review-modal');
        const cancelReviewBtn = document.querySelector('.cancel-review');
        const reviewForm = document.getElementById('review-form');
        const ratingStars = document.querySelectorAll('.rating-star');
        const ratingInput = document.getElementById('review-rating');
        const reviewPhotoInput = document.getElementById('review-photo');
        const uploadPreview = document.querySelector('.upload-preview');
        
        // Open review modal
        openReviewModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            reviewModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        // Handle custom tour type visibility
        const tourTypeSelect = document.getElementById('review-tour-type');
        const customTourTypeContainer = document.getElementById('custom-tour-type-container');
        const customTourTypeInput = document.getElementById('custom-tour-type');
        
        tourTypeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customTourTypeContainer.style.display = 'block';
                customTourTypeInput.setAttribute('required', 'required');
            } else {
                customTourTypeContainer.style.display = 'none';
                customTourTypeInput.removeAttribute('required');
            }
        });
        
        // Close review modal
        function closeReviewModal() {
            reviewModal.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => {
                reviewForm.reset();
                resetRatingStars();
                uploadPreview.style.display = 'none';
                uploadPreview.innerHTML = '';
            }, 300);
        }
        
        closeReviewModalBtn.addEventListener('click', closeReviewModal);
        cancelReviewBtn.addEventListener('click', closeReviewModal);
        
        // Close modal when clicking outside of it
        reviewModal.addEventListener('click', function(e) {
            if (e.target === reviewModal) {
                closeReviewModal();
            }
        });
        
        // Modern star rating interaction with slider
        const ratingLabels = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        const ratingValue = document.querySelector('.rating-value');
        const ratingProgress = document.querySelector('.rating-progress');
        const ratingSelectDiv = document.querySelector('.rating-select');
        let ratingSelected = false;
        
        // Update progress bar and value tooltip position
        function updateProgressBar(rating) {
            const progressPercentage = (rating / 5) * 100;
            ratingProgress.style.width = `${progressPercentage}%`;
            
            // Position tooltip
            if (rating > 0) {
                ratingValue.textContent = rating;
                ratingValue.style.left = `${progressPercentage}%`;
                ratingValue.style.transform = 'translateX(-50%)';
                ratingValue.style.opacity = '1';
            } else {
                ratingValue.style.opacity = '0';
            }
        }
        
        ratingStars.forEach((star, index) => {
            // Mouse hover effect to preview rating
            star.addEventListener('mouseover', function() {
                const hoverRating = parseInt(this.dataset.rating);
                
                // Update tooltip and progress bar
                updateProgressBar(hoverRating);
                
                // Reset visual state first
                ratingStars.forEach(s => {
                    s.classList.remove('fas', 'hover');
                    s.classList.add('far');
                });
                
                // Fill stars up to hovered rating
                for (let i = 0; i < ratingStars.length; i++) {
                    if (i < hoverRating) {
                        ratingStars[i].classList.remove('far');
                        ratingStars[i].classList.add('fas', 'hover');
                    }
                }
            });
            
            // Mouse leave effect to restore selected rating
            star.addEventListener('mouseleave', function() {
                const selectedRating = parseInt(ratingInput.value);
                
                // Restore selected rating if any
                updateStarsDisplay(selectedRating);
                updateProgressBar(selectedRating);
            });
            
            // Click to select rating with nice transition
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                ratingSelected = true;
                
                // Visual feedback for selection
                this.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-5px)';
                }, 200);
                
                // Hide any error message
                document.getElementById('rating-error').style.display = 'none';
                
                // Update visuals
                updateStarsDisplay(rating);
                updateProgressBar(rating);
                
                // Show selection confirmation
                ratingSelectDiv.classList.add('rating-selected');
            });
        });
        
        // Add rating-selected class styles
        const style = document.createElement('style');
        style.textContent = `
            .rating-selected {
                box-shadow: 5px 5px 10px #d9d9d9, -5px -5px 10px #ffffff, 0 0 0 2px rgba(71, 118, 230, 0.2);
            }
            .rating-selected:after {
                content: "✓ Rating confirmed" !important;
                color: #4776E6 !important;
                font-weight: bold;
            }
            .rating-selected .rating-progress {
                animation: none !important;
                opacity: 1 !important;
            }
        `;
        document.head.appendChild(style);
        
        function resetRatingStars() {
            ratingStars.forEach(star => {
                star.classList.remove('fas', 'selected', 'hover');
                star.classList.add('far');
            });
            ratingInput.value = 0;
            ratingSelected = false;
            updateProgressBar(0);
            ratingSelectDiv.classList.remove('rating-selected');
        }
        
        function updateStarsDisplay(rating) {
            // Reset all stars first
            ratingStars.forEach(star => {
                star.classList.remove('fas', 'selected', 'hover');
                star.classList.add('far');
            });
            
            // Fill stars up to selected rating
            for (let i = 0; i < ratingStars.length; i++) {
                if (i < rating) {
                    ratingStars[i].classList.remove('far');
                    ratingStars[i].classList.add('fas', 'selected');
                }
            }
        }
        
        // Handle image upload preview
        reviewPhotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    uploadPreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    uploadPreview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                uploadPreview.style.display = 'none';
                uploadPreview.innerHTML = '';
            }
        });
        
        // Form submission
        reviewForm.addEventListener('submit', function(e) {
            // Check if rating is selected
            if (ratingInput.value === '0') {
                e.preventDefault();
                // Show the rating error message instead of an alert
                document.getElementById('rating-error').style.display = 'block';
                // Highlight the rating section with a shake animation
                const ratingSelect = document.querySelector('.rating-select');
                ratingSelect.style.animation = 'none';
                setTimeout(() => {
                    ratingSelect.style.animation = 'shake 0.5s';
                }, 10);
                // Scroll to the rating section
                ratingSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            } else {
                // Hide error message if rating is selected
                document.getElementById('rating-error').style.display = 'none';
            }
            
            // If using AJAX submission instead of form action, uncomment this
            /*
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for your review!');
                    closeReviewModal();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
            */
        });
    </script>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-about">
                <h3>Adventure Travel.lk</h3>
                <p>Explore Sri Lanka's breathtaking destinations with our premium tour packages and travel services. We offer unforgettable experiences with professional guides and comfortable transportation.</p>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="social-icon-img">📱</i></a>
                    <a href="#" class="social-icon"><i class="social-icon-img">📘</i></a>
                    <a href="#" class="social-icon"><i class="social-icon-img">📸</i></a>
                    <a href="#" class="social-icon"><i class="social-icon-img">▶️</i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#packages">Packages</a></li>
                    <li><a href="#vehicle-hire">Vehicle Hire</a></li>
                    <li><a href="#destinations">Destinations</a></li>
                    <li><a href="#review">Reviews</a></li>
                    <li><a href="#team">Our Team</a></li>
                    <li><a href="contact_us.php">Contact Us</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="contact-icon">📍</i>
                        <p>Narammala, Kurunegala, Sri Lanka</p>
                    </div>
                    <div class="contact-item">
                        <i class="contact-icon">📞</i>
                        <p>+94 71 862 8992</p>
                    </div>
                    <div class="contact-item">
                        <i class="contact-icon">📱</i>
                        <p>+94 77 123 4567</p>
                    </div>
                    <div class="contact-item">
                        <i class="contact-icon">✉️</i>
                        <p>adventuretravel.lk@gmail.com</p>
                    </div>
                </div>
            </div>
            
    
        </div>
        
        <div class="footer-bottom">
            <div class="copyright">
                <p>&copy; 2025 Adventure Travel.lk. All Rights Reserved.</p>
            </div>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">FAQ</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Chat Interface -->
    <?php if ($is_logged_in): ?>
    <!-- Chat Button -->
    <div class="chat-btn-container">
        <button id="chat-btn" class="chat-btn">
            <i class="fas fa-comments"></i>
            <span class="chat-notification" id="chat-notification" style="display: none;"></span>
        </button>
    </div>
    
    <!-- Chat Box -->
    <div class="chat-box" id="chat-box">
        <div class="chat-header">
            <h3>Chat with Support</h3>
            <button id="close-chat" class="close-chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-messages" id="chat-messages">
            <div class="chat-welcome">
                <p>Welcome to Adventure Travel.lk Chat Support! How can we help you today?</p>
            </div>
            <!-- Messages will be loaded here -->
        </div>
        <div class="chat-input">
            <textarea id="chat-message" placeholder="Type your message here..." style="font-size: 16px;" inputmode="text"></textarea>
            <button id="send-message" disabled>
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <!-- Reply container - hidden by default -->
        <div id="reply-container" style="display: none;" class="reply-preview">
            <div class="reply-content">
                <p id="reply-text"></p>
            </div>
            <button id="cancel-reply" class="cancel-action">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <!-- Edit container - hidden by default -->
        <div id="edit-container" style="display: none;" class="edit-preview">
            <div class="edit-content">
                <p id="edit-text"></p>
            </div>
            <button id="cancel-edit" class="cancel-action">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            const userId = <?php echo $is_logged_in ? $_SESSION['user_id'] : 'null'; ?>;
            let chatBox = $('#chat-box');
            let chatBtn = $('#chat-btn');
            let closeChat = $('#close-chat');
            let chatMessages = $('#chat-messages');
            let chatMessage = $('#chat-message');
            let sendMessage = $('#send-message');
            let chatNotification = $('#chat-notification');
            
            // Reply and edit functionality variables
            let replyContainer = $('#reply-container');
            let replyText = $('#reply-text');
            let cancelReply = $('#cancel-reply');
            let editContainer = $('#edit-container');
            let editText = $('#edit-text');
            let cancelEdit = $('#cancel-edit');
            let replyingTo = null;
            let editingMessageId = null;
            
            // Show debug info (for development)
            console.log("User ID:", userId);
            console.log("Base URL:", window.location.protocol + '//' + window.location.host);
            
            // Toggle chat box
            chatBtn.on('click', function() {
                chatBox.css('display', 'flex');
                loadMessages();
                // Mark messages as read directly when opening chat
                markMessagesAsRead();
            });
            
            // Close chat box
            closeChat.on('click', function() {
                chatBox.hide();
            });
            
            // Enable/disable send button based on message content
            chatMessage.on('input', function() {
                sendMessage.prop('disabled', $(this).val().trim() === '');
            });
            
            // Send message on enter key
            chatMessage.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    if (!sendMessage.prop('disabled')) {
                        sendMessageToServer();
                    }
                }
            });
            
            // Send message on button click
            sendMessage.on('click', function() {
                sendMessageToServer();
            });
            
            // Cancel reply
            cancelReply.on('click', function() {
                replyingTo = null;
                replyContainer.hide();
            });
            
            // Cancel edit
            cancelEdit.on('click', function() {
                editingMessageId = null;
                editContainer.hide();
                chatMessage.val('');
            });
            
            // Handle message action clicks (reply, edit, delete)
            $(document).on('click', '.action-btn', function() {
                const action = $(this).data('action');
                const messageId = $(this).closest('.message').data('id');
                const messageContent = $(this).closest('.message').find('.message-content').text();
                
                if (action === 'reply') {
                    // Set up reply mode
                    replyingTo = messageId;
                    replyText.text(messageContent.substring(0, 50) + (messageContent.length > 50 ? '...' : ''));
                    replyContainer.show();
                    editContainer.hide();
                    chatMessage.focus();
                    
                } else if (action === 'edit') {
                    // Set up edit mode - only if it's the user's own message
                    editingMessageId = messageId;
                    editText.text('Editing message');
                    editContainer.show();
                    replyContainer.hide();
                    chatMessage.val(messageContent).focus();
                    
                } else if (action === 'delete') {
                    // Custom styled confirmation dialog
                    showDeleteConfirmation(messageId);
                }
            });
            
            // Function to show a custom delete confirmation dialog
            function showDeleteConfirmation(messageId) {
                // Create confirmation overlay if it doesn't exist
                if ($('#delete-confirmation-overlay').length === 0) {
                    const confirmationHTML = `
                        <div id="delete-confirmation-overlay" style="
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.7);
                            backdrop-filter: blur(4px);
                            display: flex;
                            align-items: flex-end;
                            justify-content: center;
                            z-index: 9999;
                            opacity: 0;
                            visibility: hidden;
                            transition: opacity 0.3s ease, visibility 0.3s ease;
                        ">
                            <div id="delete-confirmation-dialog" style="
                                background: linear-gradient(145deg, #ff5252, #ff1744);
                                color: white;
                                border-radius: 20px 20px 0 0;
                                padding: 25px;
                                width: 100%;
                                max-width: 500px;
                                box-shadow: 0 -5px 30px rgba(255, 23, 68, 0.4);
                                transform: translateY(100%);
                                transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                                margin-bottom: 0;
                            ">
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    margin-bottom: 15px;
                                ">
                                    <div style="
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 50px;
                                        height: 50px;
                                        background-color: rgba(255, 255, 255, 0.2);
                                        border-radius: 50%;
                                        margin-right: 15px;
                                    ">
                                        <i class="fas fa-trash-alt" style="font-size: 20px;"></i>
                                    </div>
                                    <div>
                                        <h3 style="margin: 0; font-size: 20px; font-weight: 600;">Delete Message</h3>
                                        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">This will permanently remove the message</p>
                                    </div>
                                </div>

                                <div style="
                                    background-color: rgba(255, 255, 255, 0.1);
                                    border-left: 4px solid rgba(255, 255, 255, 0.3);
                                    padding: 15px;
                                    border-radius: 0 10px 10px 0;
                                    margin: 20px 0;
                                ">
                                    <p style="margin: 0; font-size: 15px;">Are you sure you want to delete this message? This action cannot be undone.</p>
                                </div>

                                <div style="
                                    display: flex;
                                    justify-content: flex-end;
                                    gap: 15px;
                                    margin-top: 20px;
                                ">
                                    <button id="cancel-delete" style="
                                        padding: 12px 20px;
                                        border-radius: 50px;
                                        border: 1px solid rgba(255, 255, 255, 0.3);
                                        background: transparent;
                                        color: white;
                                        cursor: pointer;
                                        font-size: 15px;
                                        font-weight: 500;
                                        transition: all 0.2s ease;
                                    ">Cancel</button>
                                    <button id="confirm-delete" style="
                                        padding: 12px 25px;
                                        border-radius: 50px;
                                        border: none;
                                        background-color: white;
                                        color: #ff1744;
                                        cursor: pointer;
                                        font-size: 15px;
                                        font-weight: 600;
                                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                                        transition: all 0.2s ease;
                                    ">Delete</button>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(confirmationHTML);
                    
                    // Event handlers for the confirmation dialog
                    $('#cancel-delete').on('click', function() {
                        hideDeleteConfirm();
                    });
                    
                    $('#cancel-delete').on('mouseover', function() {
                        $(this).css({
                            'background': 'rgba(255, 255, 255, 0.1)',
                            'transform': 'translateY(-2px)'
                        });
                    }).on('mouseout', function() {
                        $(this).css({
                            'background': 'transparent',
                            'transform': 'translateY(0)'
                        });
                    });
                    
                    $('#confirm-delete').on('mouseover', function() {
                        $(this).css({
                            'transform': 'translateY(-2px)',
                            'box-shadow': '0 6px 15px rgba(0, 0, 0, 0.3)'
                        });
                    }).on('mouseout', function() {
                        $(this).css({
                            'transform': 'translateY(0)',
                            'box-shadow': '0 4px 10px rgba(0, 0, 0, 0.2)'
                        });
                    });
                    
                    // Close on click outside
                    $('#delete-confirmation-overlay').on('click', function(e) {
                        if (e.target === this) {
                            hideDeleteConfirm();
                        }
                    });
                    
                    // Close on ESC key
                    $(document).on('keydown', function(e) {
                        if (e.key === 'Escape' && $('#delete-confirmation-overlay').css('visibility') === 'visible') {
                            hideDeleteConfirm();
                        }
                    });
                }
                
                // Update confirmation button to use current messageId
                $('#confirm-delete').off('click').on('click', function() {
                    hideDeleteConfirm();
                    deleteMessage(messageId);
                });
                
                // Show the confirmation dialog
                const overlay = $('#delete-confirmation-overlay');
                const dialog = $('#delete-confirmation-dialog');
                
                overlay.css({
                    'visibility': 'visible',
                    'opacity': '1'
                });
                
                setTimeout(() => {
                    dialog.css('transform', 'translateY(0)');
                }, 50);
            }
            
            // Function to hide delete confirmation
            function hideDeleteConfirm() {
                const overlay = $('#delete-confirmation-overlay');
                const dialog = $('#delete-confirmation-dialog');
                
                dialog.css('transform', 'translateY(100%)');
                
                setTimeout(() => {
                    overlay.css({
                        'opacity': '0',
                        'visibility': 'hidden'
                    });
                }, 300);
            }
            
            // Function to send message
            function sendMessageToServer() {
                let message = chatMessage.val().trim();
                if (message === '') return;
                
                // Clear input
                chatMessage.val('');
                sendMessage.prop('disabled', true);
                
                // Add temporary message to chat (optimistic UI update)
                const tempId = 'temp-' + Date.now();
                let tempMessageHtml = '';
                
                if (replyingTo) {
                    // This is a reply message
                    const repliedToContent = $('#message-' + replyingTo).find('.message-content').text();
                    tempMessageHtml = `
                        <div id="${tempId}" class="message user" style="animation: fadeIn 0.3s;">
                            <div class="replied-message">↩️ ${escapeHtml(repliedToContent.substring(0, 50) + (repliedToContent.length > 50 ? '...' : ''))}</div>
                            <div class="message-content">${escapeHtml(message)}</div>
                            <div class="message-time">Sending...</div>
                        </div>
                    `;
                } else if (editingMessageId) {
                    // This is an edit - replace the existing message content
                    $(`#message-${editingMessageId} .message-content`).text(message);
                    $(`#message-${editingMessageId} .message-time`).html(`Updating... <span class="message-edited">(edited)</span>`);
                    editContainer.hide();
                    const messageIdToEdit = editingMessageId;
                    editingMessageId = null;
                    return updateMessage(messageIdToEdit, message);
                } else {
                    // Regular message
                    tempMessageHtml = `
                        <div id="${tempId}" class="message user" style="animation: fadeIn 0.3s;">
                            <div class="message-content">${escapeHtml(message)}</div>
                            <div class="message-time">Sending...</div>
                        </div>
                    `;
                }
                
                chatMessages.append(tempMessageHtml);
                scrollToBottom();
                
                // Hide reply container after sending
                if (replyingTo) {
                    replyContainer.hide();
                }
                
                // Try with relative URL first (for localhost)
                const relativeUrl = 'admin/message_ajax.php';
                // Get the base URL for fallback
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const chatAjaxUrl = baseUrl + '/Adventure_travels/admin/message_ajax.php';
                
                console.log("Sending message to:", relativeUrl);
                console.log("Message data:", {
                    action: 'send_message',
                    user_id: userId,
                    message: message,
                    is_admin: 0,
                    reply_to: replyingTo
                });
                
                // Send message to server using relative URL first
                $.ajax({
                    url: relativeUrl,
                    type: 'POST',
                    data: {
                        action: 'send_message',
                        user_id: userId,
                        message: message,
                        is_admin: 0,
                        reply_to: replyingTo // New field for reply functionality
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Send message response:", response);
                        
                        // Remove temporary message
                        $(`#${tempId}`).remove();
                        
                        if (response.success) {
                            // Add confirmed message to chat
                            addMessage(response.data, true);
                            scrollToBottom();
                            // Reset reply state
                            replyingTo = null;
                        } else {
                            const errorHtml = `
                                <div class="message admin" style="animation: fadeIn 0.3s;">
                                    <div class="message-content">Message could not be sent: ${response.message}</div>
                                    <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            `;
                            chatMessages.append(errorHtml);
                            console.error("Failed to send message:", response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Chat error with relative URL:", status, error);
                        
                        // Try with absolute URL
                        console.log("Trying with absolute URL:", chatAjaxUrl);
                        
                        $.ajax({
                            url: chatAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'send_message',
                                user_id: userId,
                                message: message,
                                is_admin: 0,
                                reply_to: replyingTo
                            },
                            dataType: 'json',
                            success: function(response) {
                                console.log("Send message response (absolute URL):", response);
                                
                                // Remove temporary message
                                $(`#${tempId}`).remove();
                                
                                if (response.success) {
                                    // Add confirmed message to chat
                                    addMessage(response.data, true);
                                    scrollToBottom();
                                    // Reset reply state
                                    replyingTo = null;
                                } else {
                                    const errorHtml = `
                                        <div class="message admin" style="animation: fadeIn 0.3s;">
                                            <div class="message-content">Message could not be sent: ${response.message}</div>
                                            <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                        </div>
                                    `;
                                    chatMessages.append(errorHtml);
                                    console.error("Failed to send message:", response.message);
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("Chat error with absolute URL:", textStatus, errorThrown);
                                console.log("Response headers:", jqXHR.getAllResponseHeaders());
                                
                                // Remove temporary message
                                $(`#${tempId}`).remove();
                                
                                const errorHtml = `
                                    <div class="message admin" style="animation: fadeIn 0.3s;">
                                        <div class="message-content">Cannot connect to server. Please check your internet connection.</div>
                                        <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                    </div>
                                `;
                                chatMessages.append(errorHtml);
                                
                                // Re-enable input for retry
                                chatMessage.val(message);
                                sendMessage.prop('disabled', false);
                            }
                        });
                    }
                });
            }
            
            // Function to update (edit) a message
            function updateMessage(messageId, newContent) {
                const relativeUrl = 'admin/message_ajax.php';
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const chatAjaxUrl = baseUrl + '/Adventure_travels/admin/message_ajax.php';
                
                $.ajax({
                    url: relativeUrl,
                    type: 'POST',
                    data: {
                        action: 'edit_message',
                        message_id: messageId,
                        message: newContent
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Edit message response:", response);
                        
                        if (response.success) {
                            // Update the message with edited content and indicator
                            $(`#message-${messageId} .message-content`).text(newContent);
                            
                            // Add "edited" indicator if not already there
                            if ($(`#message-${messageId} .message-edited`).length === 0) {
                                $(`#message-${messageId} .message-time`).append('<span class="message-edited">(edited)</span>');
                            }
                        } else {
                            // Show specific error message
                            if (response.message.includes('10 minutes')) {
                                alert('You can only edit messages within 10 minutes of sending them.');
                            } else {
                                alert('Failed to update message: ' + response.message);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Edit message error:", status, error);
                        
                        // Try with absolute URL
                        $.ajax({
                            url: chatAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'edit_message',
                                message_id: messageId,
                                message: newContent
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Update the message with edited content and indicator
                                    $(`#message-${messageId} .message-content`).text(newContent);
                                    
                                    // Add "edited" indicator if not already there
                                    if ($(`#message-${messageId} .message-edited`).length === 0) {
                                        $(`#message-${messageId} .message-time`).append('<span class="message-edited">(edited)</span>');
                                    }
                                } else {
                                    // Show specific error message
                                    if (response.message.includes('10 minutes')) {
                                        alert('You can only edit messages within 10 minutes of sending them.');
                                    } else {
                                        alert('Failed to update message: ' + response.message);
                                    }
                                }
                            },
                            error: function() {
                                alert('Failed to update message. Please try again.');
                            }
                        });
                    }
                });
            }
            
            // Function to delete a message
            function deleteMessage(messageId) {
                const relativeUrl = 'admin/message_ajax.php';
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const chatAjaxUrl = baseUrl + '/Adventure_travels/admin/message_ajax.php';
                
                $.ajax({
                    url: relativeUrl,
                    type: 'POST',
                    data: {
                        action: 'delete_message',
                        message_id: messageId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Delete message response:", response);
                        
                        if (response.success) {
                            // Remove the message from the UI
                            $(`#message-${messageId}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            // Show specific error message
                            if (response.message.includes('10 minutes')) {
                                alert('You can only delete messages within 10 minutes of sending them.');
                            } else {
                                alert('Failed to delete message: ' + response.message);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Delete message error:", status, error);
                        
                        // Try with absolute URL
                        $.ajax({
                            url: chatAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'delete_message',
                                message_id: messageId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Remove the message from the UI
                                    $(`#message-${messageId}`).fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                } else {
                                    // Show specific error message
                                    if (response.message.includes('10 minutes')) {
                                        alert('You can only delete messages within 10 minutes of sending them.');
                                    } else {
                                        alert('Failed to delete message: ' + response.message);
                                    }
                                }
                            },
                            error: function() {
                                alert('Failed to delete message. Please try again.');
                            }
                        });
                    }
                });
            }
            
            // Function to load messages
            function loadMessages() {
                // Try with relative URL first (for localhost)
                const relativeUrl = 'admin/message_ajax.php';
                // Get the base URL for fallback
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const chatAjaxUrl = baseUrl + '/Adventure_travels/admin/message_ajax.php';
                
                console.log("Loading messages from:", relativeUrl);
                
                // Show loading indicator
                const loadingId = 'loading-' + Date.now();
                const loadingHtml = `
                    <div id="${loadingId}" class="message admin" style="animation: fadeIn 0.3s;">
                        <div class="message-content">Loading messages...</div>
                        <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                `;
                chatMessages.append(loadingHtml);
                
                $.ajax({
                    url: relativeUrl,
                    type: 'POST',
                    data: {
                        action: 'get_messages',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Load messages response:", response);
                        
                        // Remove loading indicator
                        $(`#${loadingId}`).remove();
                        
                        if (response.success) {
                            // Clear messages except welcome
                            chatMessages.find('.message').remove();
                            
                            // Add all messages
                            if (response.data && response.data.length > 0) {
                                response.data.forEach(function(message) {
                                    addMessage(message);
                                });
                                
                                // Mark messages as read after loading them
                                markMessagesAsRead();
                            } else {
                                const noMessagesHtml = `
                                    <div class="message admin">
                                        <div class="message-content">No messages yet. Start a conversation!</div>
                                        <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                    </div>
                                `;
                                chatMessages.append(noMessagesHtml);
                            }
                            
                            scrollToBottom();
                        } else {
                            const errorHtml = `
                                <div class="message admin">
                                    <div class="message-content">Error loading messages: ${response.message}</div>
                                    <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            `;
                            chatMessages.append(errorHtml);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Load messages error with relative URL:", status, error);
                        
                        // Try with absolute URL
                        console.log("Trying with absolute URL:", chatAjaxUrl);
                        
                        $.ajax({
                            url: chatAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'get_messages',
                                user_id: userId
                            },
                            dataType: 'json',
                            success: function(response) {
                                console.log("Load messages response (absolute URL):", response);
                                
                                // Remove loading indicator
                                $(`#${loadingId}`).remove();
                                
                                if (response.success) {
                                    // Clear messages except welcome
                                    chatMessages.find('.message').remove();
                                    
                                    // Add all messages
                                    if (response.data && response.data.length > 0) {
                                        response.data.forEach(function(message) {
                                            addMessage(message);
                                        });
                                        
                                        // Mark messages as read after loading them
                                        markMessagesAsRead();
                                    } else {
                                        const noMessagesHtml = `
                                            <div class="message admin">
                                                <div class="message-content">No messages yet. Start a conversation!</div>
                                                <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                            </div>
                                        `;
                                        chatMessages.append(noMessagesHtml);
                                    }
                                    
                                    scrollToBottom();
                                } else {
                                    const errorHtml = `
                                        <div class="message admin">
                                            <div class="message-content">Error loading messages: ${response.message}</div>
                                            <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                        </div>
                                    `;
                                    chatMessages.append(errorHtml);
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("Load messages error with absolute URL:", textStatus, errorThrown);
                                console.log("Response headers:", jqXHR.getAllResponseHeaders());
                                
                                // Remove loading indicator
                                $(`#${loadingId}`).remove();
                                
                                // Show error message
                                const errorHtml = `
                                    <div class="message admin">
                                        <div class="message-content">Cannot connect to server. Please check your internet connection.</div>
                                        <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                    </div>
                                `;
                                chatMessages.append(errorHtml);
                            }
                        });
                    }
                });
            }

            // Function to mark messages as read
            function markMessagesAsRead() {
                const relativeUrl = 'admin/message_ajax.php';
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const chatAjaxUrl = baseUrl + '/Adventure_travels/admin/message_ajax.php';
                
                $.ajax({
                    url: relativeUrl,
                    type: 'POST',
                    data: {
                        action: 'mark_as_read',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Messages marked as read:", response);
                        // Hide notification after marking messages as read
                        chatNotification.hide();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error marking messages as read:", status, error);
                        
                        // Try with absolute URL as fallback
                        $.ajax({
                            url: chatAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'mark_as_read',
                                user_id: userId
                            },
                            dataType: 'json',
                            success: function(response) {
                                console.log("Messages marked as read (absolute URL):", response);
                                // Hide notification after marking messages as read
                                chatNotification.hide();
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("Error marking messages as read with absolute URL:", textStatus, errorThrown);
                            }
                        });
                    }
                });
            }

            // Function to check for new messages periodically
            function checkUnreadMessages() {
                // Try with relative URL first (for localhost)
                const relativeUrl = 'admin/message_ajax.php';
                // Get the base URL for fallback
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const chatAjaxUrl = baseUrl + '/Adventure_travels/admin/message_ajax.php';
                
                // Don't check for unread messages if chat is open
                // This prevents notification flickering when user has chat open
                if (chatBox.is(':visible')) {
                    return;
                }
                
                console.log("Checking unread messages");
                
                $.ajax({
                    url: relativeUrl,
                    type: 'POST',
                    data: {
                        action: 'get_unread_count',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Unread count response:", response);
                        
                        if (response.success && response.data > 0) {
                            // Show notification with count
                            chatNotification.text(response.data).show();
                            
                            // Play notification sound
                            if (typeof notificationSound !== 'undefined' && notificationSound) {
                                notificationSound.play().catch(function(error) {
                                    console.log("Sound play prevented:", error);
                                });
                            }
                        } else {
                            // No unread messages, hide notification
                            chatNotification.hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error checking unread count with relative URL:", status, error);
                        
                        // Try with absolute URL as fallback
                        $.ajax({
                            url: chatAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'get_unread_count',
                                user_id: userId
                            },
                            dataType: 'json',
                            success: function(response) {
                                console.log("Unread count response (absolute URL):", response);
                                
                                if (response.success && response.data > 0) {
                                    // Show notification with count
                                    chatNotification.text(response.data).show();
                                    
                                    // Play notification sound
                                    if (typeof notificationSound !== 'undefined' && notificationSound) {
                                        notificationSound.play().catch(function(error) {
                                            console.log("Sound play prevented:", error);
                                        });
                                    }
                                } else {
                                    // No unread messages, hide notification
                                    chatNotification.hide();
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("Error checking unread count with absolute URL:", textStatus, errorThrown);
                                // Silent fail on this one - no need to bother user
                            }
                        });
                    }
                });
            }
            
            // Create notification sound
            let notificationSound;
            try {
                notificationSound = new Audio('data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tAwAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAASAAAeMwAUFBQUFCIiIiIiIjAwMDAwMD4+Pj4+PkxMTExMTFpaWlpaWmdnZ2dnZ3V1dXV1dYODg4ODg5GRkZGRkZ+fn5+fn62tra2trbq6urq6usLCwsLCwtDQ0NDQ0NjY2NjY2Obm5ubm5vT09PT09P////////8AAAAATGF2YzU4LjEzAAAAAAAAAAAAAAAAJAV2Z2AAAAsAAAB4AJ5qJAkAAAAAAAAAAAAAAAAAAAAA//tANSAAAAAGcAAAAwAAA0gAAADOYAAAAwBAA0gAAADIAAAAKVRyYWNrIDEAAAAACgAAA0gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//vAwAAAAdsATQAAAAQAAA5gAAABAAABpAAAACAAADSAAAAETEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVU=');
            } catch (e) {
                console.log("Audio notification not supported:", e);
            }
            
            // Ensure chat notification is hidden initially
            $(document).ready(function() {
                chatNotification.hide();
            });
            
            // Check for new messages every 10 seconds
            setInterval(checkUnreadMessages, 10000);
            
            // Initial check for unread messages
            checkUnreadMessages();
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Function to add a message to the chat
            function addMessage(message, isNew = false) {
                const isUser = message.is_admin == 0;
                const messageClass = isUser ? 'user' : 'admin';
                const date = new Date(message.created_at);
                const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const messageId = message.message_id;
                
                // Check if edited
                const editedMarkup = message.edited ? '<span class="message-edited">(edited)</span>' : '';
                
                // Check if this is a reply to another message
                let replyMarkup = '';
                if (message.reply_to && message.replied_to_content) {
                    replyMarkup = `
                        <div class="replied-message">↩️ ${escapeHtml(message.replied_to_content.substring(0, 50) + (message.replied_to_content.length > 50 ? '...' : ''))}</div>
                    `;
                }
                
                // Check if message is less than 10 minutes old to show edit/delete buttons
                const messageTime = new Date(message.created_at).getTime();
                const currentTime = new Date().getTime();
                const timeDiffMinutes = (currentTime - messageTime) / 1000 / 60;
                const canModify = timeDiffMinutes <= 10;
                
                // Only show action buttons for user's messages and only show edit/delete if within time limit
                const actionButtons = isUser ? `
                    <div class="message-actions">
                        <button class="action-btn reply-btn" data-action="reply" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                        ${canModify ? `
                        <button class="action-btn edit-btn" data-action="edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" data-action="delete" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        ` : ''}
                    </div>
                ` : `
                    <div class="message-actions">
                        <button class="action-btn reply-btn" data-action="reply" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
                `;
                
                const messageHtml = `
                    <div id="message-${messageId}" data-id="${messageId}" class="message ${messageClass}" ${isNew ? 'style="animation: fadeIn 0.3s;"' : ''}>
                        ${actionButtons}
                        ${replyMarkup}
                        <div class="message-content">${escapeHtml(message.message)}</div>
                        <div class="message-time">${formattedTime} ${editedMarkup}</div>
                    </div>
                `;
                
                chatMessages.append(messageHtml);
            }
            
            // Function to scroll to bottom of chat
            function scrollToBottom() {
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
