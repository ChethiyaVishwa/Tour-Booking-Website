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

// Check if search query exists
$search_query = '';
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
}

// Query to fetch all destinations with their details
if(!empty($search_query)) {
    $query = "SELECT * FROM destinations WHERE name LIKE '%$search_query%' ORDER BY name";
} else {
    $query = "SELECT * FROM destinations ORDER BY name";
}

$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Explore More Destinations | Adventure Travel.lk</title>
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
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(0, 204, 163);
            --text-color: #f0f0f0;
            --bg-color: #121212;
            --bg-alt-color: #1e1e1e;
            --card-bg: #2d2d2d;
            --header-bg: rgb(20, 170, 145);
            --footer-bg: #222;
            --footer-text: #ddd;
            --card-shadow: rgba(0, 0, 0, 0.5);
            --border-color: rgb(23, 15, 132);
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
            font-weight: 700; /* or use 'bold' */
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
            border-top: 1px solid #eee;
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

        .account-actions a:first-child {
            border-right: 1px solid #eee;
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

        /* Destination Styles */
        .hero-section {
            background-size: cover;
            background-position: center;
            color: #fff;
            padding: 100px 0;
            position: relative;
            margin-top: 70px; /* Add margin for fixed header */
            margin-bottom: 30px;
        }

        .hero-section::before {
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
            text-align: center;
        }

        .hero-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .hero-subtitle {
            font-size: 1rem;
            margin-bottom: 20px;
        }

        /* Hero Search Styles */
        .hero-search-container {
            max-width: 500px;
            margin: 30px auto 0;
        }

        .hero-search-form {
            width: 100%;
        }

        .search-input-group {
            display: flex;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .search-input-group:focus-within {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            background: #fff;
        }

        .hero-search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 14px 20px;
            font-size: 16px; /* Fixed 16px font size to prevent zoom */
            color: #333;
            outline: none;
            width: 100%;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            touch-action: manipulation; /* Prevents browser manipulation */
        }

        .hero-search-input::placeholder {
            color: #888;
        }

        .hero-search-button {
            background: var(--primary-color);
            border: none;
            color: white;
            width: 50px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-search-button:hover {
            background: #155c56;
        }

        /* Search Results Banner */
        .search-results-banner {
            background: linear-gradient(to right, rgba(23, 108, 101, 0.9), rgba(23, 108, 101, 0.8));
            color: white;
            padding: 12px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
        }

        .search-results-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(101, 255, 193, 0.7);
        }

        .search-results-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .search-info i {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .reset-button {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .reset-button:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        /* Destinations Grid Styles */
        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .destination-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px var(--card-shadow);
        }

        .destination-image {
            height: 200px;
            overflow: hidden;
        }

        .destination-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .destination-card:hover .destination-image img {
            transform: scale(1.1);
        }

        .destination-content {
            padding: 20px;
        }

        .destination-name {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .destination-description {
            color: var(--text-color);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        /* No Results Styling */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--card-shadow);
            margin: 40px auto;
            max-width: 500px;
        }
        
        .no-results i {
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-results h3 {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        
        .no-results p {
            color: var(--text-color);
            margin-bottom: 20px;
        }
        
        .reset-search-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .reset-search-btn:hover {
            background-color: #155c56;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .view-details-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            flex: 1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        
        .view-details-btn:hover {
            background-color: #155c56;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .view-details-btn i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }
        
        .view-details-btn:hover i {
            transform: translateX(3px);
        }

        .cta-section {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 50px 0;
            margin-top: 40px;
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
        }

        .cta-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Footer */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0;
            text-align: center;
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
        @media (max-width: 992px) {
            .destinations-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .destinations-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }
            
            .hero-search-container {
                max-width: 90%;
                margin: 20px auto 0;
            }
            
            .hero-search-input {
                padding: 12px 15px;
                font-size: 0.95rem;
            }
            
            .search-results-banner {
                padding: 10px 0;
            }
            
            .search-info {
                font-size: 0.85rem;
            }
            
            .reset-button {
                font-size: 0.8rem;
                padding: 5px 12px;
            }
        }
        
        @media (max-width: 576px) {
            .hero-search-container {
                max-width: 100%;
            }
            
            .hero-search-input {
                padding: 10px 15px;
                font-size: 16px !important; /* iOS won't zoom if font size is at least 16px */
                transform: scale(1); /* Helps prevent zoom on some Android devices */
                transform-origin: left top;
                touch-action: manipulation; /* Prevents browser manipulation */
            }
            
            .hero-search-button {
                width: 40px;
            }
            
            .search-input-group {
                transform: translateZ(0); /* Force GPU acceleration */
            }
            
            .search-results-content {
                flex-direction: column;
                gap: 10px;
                padding: 5px 0;
            }
            
            .search-info {
                width: 100%;
                justify-content: center;
            }
            
            .reset-button {
                width: 100%;
                text-align: center;
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
                top: 15px;
                left: 15px;
                padding: 6px 12px;
                font-size: 0.85rem;
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
    <section class="hero-section" style="background-image: url('../images/home-bg-1.png');">
        <a href="../index.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Explore Destinations</h1>
                <p class="hero-subtitle">Discover amazing places in Sri Lanka</p>
                
                <!-- Inline Search Bar -->
                <div class="hero-search-container">
                    <form action="" method="GET" class="hero-search-form">
                        <div class="search-input-group">
                            <input 
                                type="text" 
                                name="search" 
                                placeholder="Search destinations..." 
                                value="<?php echo htmlspecialchars($search_query); ?>"
                                class="hero-search-input"
                            >
                            <button type="submit" class="hero-search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php if(!empty($search_query)): ?>
    <div class="search-results-banner">
        <div class="container">
            <div class="search-results-content">
                <div class="search-info">
                    <i class="fas fa-search"></i>
                    <span>
                        <?php 
                            $count = mysqli_num_rows($result);
                            echo "$count " . ($count == 1 ? "result" : "results") . " found for \"" . htmlspecialchars($search_query) . "\"";
                        ?>
                    </span>
                </div>
                <a href="destinations.php" class="reset-button">Clear search</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Destinations Section -->
    <section class="py-16">
        <div class="container">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div class="destinations-grid">
                    <?php while ($destination = mysqli_fetch_assoc($result)): ?>
                        <div class="destination-card">
                            <div class="destination-image">
                                <a href="destination_detail.php?id=<?php echo $destination['destination_id']; ?>">
                                    <img 
                                        src="<?php echo htmlspecialchars($destination['image']); ?>" 
                                        alt="<?php echo htmlspecialchars($destination['name']); ?>"
                                    >
                                </a>
                            </div>
                            <div class="destination-content">
                                <h3 class="destination-name"><?php echo htmlspecialchars($destination['name']); ?></h3>
                                <p class="destination-description"><?php echo htmlspecialchars($destination['description']); ?></p>
                                
                                <div class="button-group">
                                    <a href="destination_detail.php?id=<?php echo $destination['destination_id']; ?>" class="view-details-btn">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search fa-3x"></i>
                    <h3>No destinations found</h3>
                    <p>Try a different search term or browse all destinations</p>
                    <a href="destinations.php" class="reset-search-btn">View All Destinations</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Ready to Explore These Amazing Destinations?</h2>
            <p class="cta-subtitle">Book your adventure today and experience the beauty of Sri Lanka</p>
            <a href="../tour_packages/tour_packages.php" class="cta-button">View Tour Packages</a>
        </div>
    </section>

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
        });
    </script>
</body>
</html> 