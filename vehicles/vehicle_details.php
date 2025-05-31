<?php
    // Database connection
    require_once '../admin/config.php';
    
    // Get vehicle ID from URL
    $vehicle_id = 0;
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $vehicle_id = intval($_GET['id']);
    } else {
        header("Location: ../index.php#vehicle-hire");
        exit;
    }
    
    // Get vehicle information
    $vehicle_query = "SELECT * FROM vehicles WHERE vehicle_id = ?";
    $vehicle_stmt = mysqli_prepare($conn, $vehicle_query);
    mysqli_stmt_bind_param($vehicle_stmt, "i", $vehicle_id);
    mysqli_stmt_execute($vehicle_stmt);
    $vehicle_result = mysqli_stmt_get_result($vehicle_stmt);
    
    if (mysqli_num_rows($vehicle_result) == 0 || !$vehicle = mysqli_fetch_assoc($vehicle_result)) {
        header("Location: ../index.php#vehicle-hire");
        exit;
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

    // Start the session if one doesn't exist already
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    $is_logged_in = isset($_SESSION['user_id']);
    $user_name = $is_logged_in ? $_SESSION['full_name'] : '';
    $username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($vehicle['name']); ?> - Adventure Travel.lk</title>
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
            --text-color: #333;
            --bg-color: #fff;
            --bg-alt-color: #f8f9fa;
            --card-bg: white;
            --header-bg: rgb(0, 255, 204);
            --footer-bg: #222;
            --footer-text: #fff;
            --card-shadow: rgba(0, 0, 0, 0.1);
            --border-color: rgb(23, 15, 132);
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(0, 204, 163);
            --dark-color: #f0f0f0;
            --light-color: #222;
            --text-color: #f0f0f0;
            --bg-color: #121212;
            --card-bg: #2d2d2d;
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
            padding-top: 80px;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .container {
            max-width: 1400px;
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

        .navbar {
            display: flex;
            align-items: center;
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
            color: rgb(255, 0, 0);
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: var(--text-color);
            transition: color 0.3s ease;
            z-index: 1001;
        }

        /* Vehicle Details Styles - Split Layout */
        .vehicle-details-section {
            margin: 40px 0;
            padding: 0;
        }

        .vehicle-details-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
            background-color: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px var(--card-shadow);
        }

        .vehicle-image-column {
            position: relative;
        }

        .vehicle-main-image {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 350px;
            overflow: hidden;
            cursor: zoom-in;
        }

        .vehicle-main-image img {
            width: 100%;
            height: 100%;
            min-height: 400px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        
        /* Larger screen sizes - increase image height */
        @media (min-width: 1200px) {
            .vehicle-main-image {
                min-height: 500px;
            }
            
            .vehicle-main-image img {
                min-height: 550px;
            }
        }
        
        /* Extra large screens */
        @media (min-width: 1600px) {
            .vehicle-main-image {
                min-height: 600px;
            }
            
            .vehicle-main-image img {
                min-height: 650px;
            }
        }

        .vehicle-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .vehicle-badge.available {
            background-color: #28a745;
        }

        .vehicle-badge.unavailable {
            background-color: #dc3545;
        }

        .image-thumbnails {
            display: flex;
            gap: 10px;
            padding: 15px;
            background-color: var(--card-bg);
            position: absolute;
            bottom: 0;
            width: 100%;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        .image-thumbnails::-webkit-scrollbar {
            height: 6px;
        }

        .image-thumbnails::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }

        .image-thumbnails::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.3);
            border-radius: 3px;
        }

        .thumb {
            min-width: 80px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
            opacity: 0.7;
            border: 2px solid transparent;
        }

        .thumb.active {
            opacity: 1;
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        .thumb:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vehicle-info-column {
            padding: 30px;
            background-color: var(--card-bg);
            display: flex;
            flex-direction: column;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link i {
            margin-right: 8px;
        }

        .back-link:hover {
            color: var(--secondary-color);
            transform: translateX(-5px);
        }

        .vehicle-name {
            font-size: 2.2rem;
            color: var(--text-color);
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .vehicle-type {
            display: inline-block;
            font-size: 1rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 500;
        }

        .vehicle-type i {
            margin-right: 8px;
        }

        .price-tag {
            margin-bottom: 25px;
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
        }

        .price-tag .amount {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .price-tag .period {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .specs-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--card-shadow);
            padding-bottom: 25px;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .spec-icon {
            min-width: 40px;
            height: 40px;
            background-color: rgba(23, 108, 101, 0.1);
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .spec-info h4 {
            margin: 0;
            font-size: 0.85rem;
            color: #777;
            font-weight: 500;
        }

        .spec-info p {
            margin: 4px 0 0 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .vehicle-description {
            margin-bottom: 25px;
        }

        .vehicle-description h3 {
            font-size: 1.2rem;
            color: var(--text-color);
            margin: 0 0 15px 0;
        }

        .vehicle-description p {
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            opacity: 0.9;
        }

        .vehicle-actions {
            margin-top: 25px;
        }

        .btn-view-all {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid var(--primary-color);
        }

        .btn-view-all:hover {
            background-color: transparent;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Responsive styles for split layout */
        @media (max-width: 991px) {
            .vehicle-details-wrapper {
                grid-template-columns: 1fr;
            }
            
            .vehicle-main-image img {
                min-height: 350px;
            }
            
            .vehicle-name {
                font-size: 1.8rem;
            }
            
            .price-tag .amount {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 768px) {
            .vehicle-main-image img {
                min-height: 300px;
            }
            
            .vehicle-badge {
                top: 15px;
                right: 15px;
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .image-thumbnails {
                padding: 10px;
            }
            
            .thumb {
                min-width: 70px;
                height: 50px;
            }
            
            .vehicle-info-column {
                padding: 20px;
            }
            
            .vehicle-name {
                font-size: 1.6rem;
            }
            
            .specs-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .vehicle-main-image img {
                min-height: 250px;
            }
            
            .vehicle-info-column {
                padding: 15px;
            }
            
            .thumb {
                min-width: 60px;
                height: 45px;
            }
            
            .specs-container {
                grid-template-columns: 1fr;
            }
            
            .vehicle-name {
                font-size: 1.4rem;
            }
            
            .price-tag {
                margin-bottom: 20px;
                padding: 6px 12px;
            }
            
            .price-tag .amount {
                font-size: 1.2rem;
            }
        }

        /* Vehicle Details Styles */
        .vehicle-header {
            background-color: var(--bg-alt-color);
            padding: 40px 0;
            margin-bottom: 40px;
            position: relative;
        }

        .vehicle-details-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 1000px;
            margin: 0 auto;
        }

        .vehicle-details-content {
            padding: 30px;
        }

        .vehicle-title {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            line-height: 1.2;
        }

        .vehicle-type-badge {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .vehicle-type-badge i {
            margin-right: 5px;
        }

        .vehicle-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .vehicle-meta-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .meta-icon {
            background-color: rgba(23, 108, 101, 0.1);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .meta-details {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .price-value {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .status-available {
            color: #28a745;
        }

        .status-unavailable {
            color: #dc3545;
        }

        .vehicle-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #145a55;
            transform: translateY(-3px);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 11px 25px;
            border-radius: 30px;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }

        /* Gallery Styles */
        .gallery-section {
            margin: 40px 0;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
        }

        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .thumb {
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .thumb:hover {
            transform: scale(1.05);
        }

        .thumb:hover img {
            filter: brightness(1.1);
        }

        /* Details Styles */
        .description {
            line-height: 1.8;
            margin-bottom: 30px;
            color: #555;
        }

        .features-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .feature-box {
            background-color: var(--bg-alt-color);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px var(--card-shadow);
            transition: all 0.3s ease;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .feature-box h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-box h3 i {
            font-size: 1.3rem;
        }

        .feature-box p {
            line-height: 1.6;
            color: #666;
        }

        .feature-price {
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 5px;
            background-color: rgba(23, 108, 101, 0.1);
            display: inline-block;
        }

        /* Includes/Excludes Styles */
        .includes-section {
            margin: 40px 0;
            display: block;
        }

        .includes-box {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px var(--card-shadow);
            max-width: 800px;
            margin: 0 auto;
        }

        .includes-box h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .includes-box ul {
            list-style: none;
            padding: 0;
        }

        .includes-box li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: baseline;
            gap: 10px;
            color: var(--primary-color);
        }

        .includes-box li:last-child {
            border-bottom: none;
        }

        .includes-box li i {
            color: #28a745;
        }

        .excludes-box li i {
            color: #dc3545;
        }

        /* Testimonials Section */
        .testimonials-section {
            margin: 40px 0;
        }

        .testimonial {
            background-color: var(--bg-alt-color);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px var(--card-shadow);
            margin-bottom: 20px;
        }

        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .testimonial-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .testimonial-name {
            margin: 0;
            color: var(--primary-color);
        }

        .testimonial-date {
            color: #777;
            font-size: 0.9rem;
        }

        .testimonial-text {
            color: #555;
            line-height: 1.6;
            font-style: italic;
            position: relative;
        }

        .testimonial-text::before {
            content: '"';
            font-size: 3rem;
            position: absolute;
            top: -20px;
            left: -10px;
            color: rgba(23, 108, 101, 0.1);
            font-family: Georgia, serif;
        }

        /* Responsive Styles */
        @media (max-width: 991px) {
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
                z-index: 999;
                display: block; /* Change from flex to block for mobile */
            }
            
            .navbar.active {
                clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
            }
            
            .navbar a {
                display: block;
                margin: 15px 0;
                padding: 15px 30px;
                font-size: 20px;
            }

            .main-image {
                height: 450px;
            }

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
            
            .features-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .footer-section {
                min-width: 250px;
            }

            .tour-type-card {
                max-width: calc(50% - 10px);
            }

            .vehicle-meta-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .vehicle-title {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 768px) {
            /* Required navbar styles for proper mobile display */
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
            
            .vehicle-header {
                padding: 20px 0;
            }
            
            .vehicle-details-card {
                border-radius: 15px;
            }
            
            .vehicle-details-content {
                padding: 20px;
            }
            
            .vehicle-title {
                font-size: 1.8rem;
                margin-bottom: 12px;
            }
            
            .vehicle-type-badge {
                padding: 4px 12px;
                font-size: 0.8rem;
                margin-bottom: 20px;
            }
            
            .meta-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .meta-value {
                font-size: 1rem;
            }

            .main-image {
                height: 300px;
            }
            
            .gallery-thumbs {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            
            .thumb {
                height: 80px;
            }

            .features-container {
                grid-template-columns: 1fr;
            }
            
            .back-to-home {
                top: 25px;
                left: 15px;
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            
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
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-section {
                margin-bottom: 30px;
            }
            
            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .tour-type-card {
                min-width: 100%;
                max-width: 100%;
            }
            
            .tour-type-header h3 {
                font-size: 1.1rem;
            }
            
            .tour-type-image img {
                height: 180px;
            }
            
            .price-value {
                font-size: 1.2rem;
            }
            
            /* Ribbon mobile adjustments */
            .ribbon-container {
                width: 70px;
                height: 70px;
            }
            
            .ribbon {
                top: 15px;
                right: -20px;
                padding: 3px 16px;
                font-size: 0.6rem;
                width: 90px;
            }
        }
        
        @media (max-width: 576px) {
            .vehicle-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .vehicle-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-primary, .btn-outline {
                width: 100%;
                text-align: center;
            }
            
            .main-image {
                height: 250px;
            }
            
            .gallery-thumbs {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 10px;
            }
            
            .thumb {
                height: 70px;
            }
            
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
            
            .vehicle-header {
                padding: 15px 0;
                margin-bottom: 20px;
            }
            
            .section-title {
                font-size: 1.5rem;
                margin-bottom: 15px;
            }

            .vehicle-meta-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .vehicle-meta-item {
                gap: 12px;
            }
            
            .meta-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            
            .vehicle-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .vehicle-details-content {
                padding: 15px;
            }
            
            .tour-type-image img {
                height: 160px;
            }
            
            /* Ribbon small-screen adjustments */
            .ribbon-container {
                width: 65px;
                height: 65px;
            }
            
            .ribbon {
                top: 12px;
                right: -20px;
                padding: 2px 12px;
                font-size: 0.55rem;
                width: 85px;
            }
        }
        
        /* Fix for very small screens */
        @media (max-width: 360px) {
            .gallery-thumbs {
                grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
                gap: 8px;
            }
            
            .thumb {
                height: 60px;
            }
            
            .vehicle-title {
                font-size: 1.7rem;
            }
            
            .vehicle-price {
                font-size: 1.3rem;
            }
            
            .feature-box {
                padding: 15px;
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

        /* Footer */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            text-align: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section {
            flex: 1;
            min-width: 300px;
            margin-bottom: 20px;
            text-align: center;
            padding: 0 15px;
        }

        .footer-section.about-section,
        .footer-section.links-section,
        .footer-section.contact-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-section h3 {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: inline-block;
            position: relative;
            font-weight: 600;
        }

        .footer-section p, .footer-section ul {
            color: #bbb;
            max-width: 80%;
            margin: 0 auto;
        }

        .footer-section ul {
            list-style: none;
            padding-left: 0;
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
        
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
            }
            
            .footer-section {
                margin-bottom: 40px;
                min-width: 100%;
            }

            .footer-section:nth-child(2):before,
            .footer-section:nth-child(3):before {
                content: '';
                width: 80px;
                height: 1px;
                background-color: rgba(255, 255, 255, 0.1);
                position: absolute;
                top: -20px;
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-section:last-child {
                margin-bottom: 20px;
            }
            
            .footer-section h3 {
                color: var(--secondary-color);
                margin-bottom: 15px;
                display: block;
                text-align: center;
            }

            .footer-section p, .footer-section ul {
                max-width: 90%;
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
        
        /* Back-to-home button styles */
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

        /* Vehicle Tour Types - New Horizontal Card Style */
        .tour-types-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
            max-width: 1300px;
            margin-left: auto;
            margin-right: auto;
            justify-content: center; /* Center cards when fewer than 3 */
        }

        .tour-type-card {
            flex: 0 0 calc(33.333% - 14px); /* Fixed width, exactly 3 per row */
            min-width: 250px;
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            background-color: var(--card-bg);
            box-shadow: 0 5px 15px var(--card-shadow);
            position: relative;
            display: flex;
            flex-direction: column; /* Arrange content vertically */
        }
        
        .tour-type-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px var(--card-shadow);
        }
        
        @media (min-width: 1400px) {
            .tour-types-container {
                justify-content: flex-start; /* Align left when screen is large enough */
            }
            
            .tour-type-card {
                flex: 0 0 calc(33.333% - 14px); /* Keep 3 per row even on large screens */
            }
        }
        
        @media (max-width: 1200px) {
            .tour-type-card {
                flex: 0 0 calc(33.333% - 14px);
            }
        }
        
        @media (max-width: 991px) {
            .tour-type-card {
                flex: 0 0 calc(50% - 10px); /* 2 per row on tablets */
            }
        }
        
        @media (max-width: 576px) {
            .tour-type-card {
                flex: 0 0 100%; /* 1 per row on mobile */
                min-width: 100%;
            }
        }
        
        .tour-type-header {
            padding: 12px 15px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tour-type-header i {
            font-size: 1.3rem;
        }
        
        .tour-type-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
        }
        
        .tour-type-content {
            padding: 15px 20px;
            color: var(--text-color);
            flex: 1; /* Allow content to grow and fill available space */
            display: flex;
            flex-direction: column;
        }

        .tour-type-content p {
            margin-bottom: 0;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .tour-type-image {
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 8px var(--card-shadow);
        }

        .tour-type-image img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: transform 0.5s ease;
            display: block;
        }

        .tour-type-card:hover .tour-type-image img {
            transform: scale(1.05);
        }
        
        .tour-type-price {
            background-color: rgba(23, 108, 101, 0.1);
            padding: 12px 15px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(23, 108, 101, 0.2);
        }
        
        .price-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--primary-color);
        }
        
        .price-value {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--primary-color);
        }
        
        /* Enhanced ribbon design */
        .ribbon-container {
            position: absolute;
            top: 0;
            right: 0;
            overflow: hidden;
            width: 80px;
            height: 80px;
            z-index: 1;
        }
        
        .ribbon {
            position: absolute;
            top: 18px;
            right: -20px;
            transform: rotate(45deg);
            background: linear-gradient(45deg, #ff6b6b, #ff3838);
            color: white;
            padding: 4px 22px;
            font-size: 0.65rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            width: 100px;
            text-align: center;
        }
        
        /* Desktop/larger screen styles for ribbon */
        @media (min-width: 992px) {
            .ribbon-container {
                width: 85px;
                height: 85px;
            }
            
            .ribbon {
                top: 15px;
                right: -22px;
                width: 110px;
                font-size: 0.7rem;
                padding: 5px 20px;
            }
        }
        
        /* Fix for ribbon on medium-sized screens */
        @media (min-width: 1200px) {
            .ribbon-container {
                width: 90px;
                height: 90px;
            }
            
            .ribbon {
                top: 17px;
                right: -25px;
                width: 115px;
            }
        }
        
        .ribbon:before, .ribbon:after {
            content: '';
            position: absolute;
            border-style: solid;
        }
        
        .ribbon:before {
            top: 0;
            left: 0;
            border-width: 0 0 5px 5px;
            border-color: transparent transparent rgba(0,0,0,0.2) transparent;
        }
        
        .ribbon:after {
            bottom: 0;
            right: 0;
            border-width: 5px 5px 0 0;
            border-color: transparent rgba(0,0,0,0.2) transparent transparent;
        }
        
        .ribbon-new {
            background: linear-gradient(45deg, #ff6b6b, #ff3838);
        }

        .ribbon-special {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }
        
        .ribbon-discount {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
        }

        /* Contact Button Styles */
        .tour-type-contact {
            padding: 0;
            margin-top: auto; /* Push to bottom of card */
            text-align: center;
            position: relative;
        }

        .contact-btn {
            display: block;
            width: 100%;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            padding: 12px 0;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .contact-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
            z-index: -1;
        }

        .contact-btn:hover::before {
            left: 100%;
        }

        .contact-btn:hover {
            background-color: #1a252f;
            color: white;
        }

        .contact-btn i {
            margin-right: 8px;
            font-size: 1.1em;
        }

        /* Image Lightbox/Zoom */
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .lightbox.active {
            opacity: 1;
            visibility: visible;
        }
        
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .lightbox.active .lightbox-content {
            transform: scale(1);
        }
        
        .lightbox-image {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            z-index: 10000;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .lightbox-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 2rem;
            cursor: pointer;
            z-index: 10000;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .lightbox-prev {
            left: 20px;
        }
        
        .lightbox-next {
            right: 20px;
        }
        
        .lightbox-nav:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Zoom functionality */
        .zoom-container {
            position: relative;
            overflow: hidden;
        }
        
        .zoomed {
            cursor: zoom-out;
            transition: transform 0.3s ease;
        }

        /* Matching heights on desktop */
        @media (min-width: 992px) {
            .vehicle-details-wrapper {
                min-height: 500px;
                align-items: stretch;
            }
            
            .vehicle-image-column {
                height: 100%;
            }
            
            .vehicle-info-column {
                height: 100%;
                justify-content: space-between;
                overflow-y: auto;
                max-height: 550px;
            }
            
            .vehicle-description {
                flex-grow: 1;
                overflow-y: auto;
                padding-right: 10px;
            }
            
            .vehicle-description::-webkit-scrollbar {
                width: 5px;
            }
            
            .vehicle-description::-webkit-scrollbar-track {
                background: rgba(0,0,0,0.05);
                border-radius: 3px;
            }
            
            .vehicle-description::-webkit-scrollbar-thumb {
                background: rgba(0,0,0,0.2);
                border-radius: 3px;
            }
        }
        
        /* Larger screen sizes - increase box height together */
        @media (min-width: 1200px) {
            .vehicle-details-wrapper {
                min-height: 550px;
            }
            
            .vehicle-info-column {
                max-height: 600px;
            }
        }
        
        /* Extra large screens */
        @media (min-width: 1600px) {
            .vehicle-details-wrapper {
                min-height: 650px;
            }
            
            .vehicle-info-column {
                max-height: 700px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
            <a href="../destinations/destinations.php">Destinations</a>
            <a href="../contact_us.php">Contact Us</a>
            <a href="../about_us/about_us.php">About Us</a>
        </nav>
    </header>

    <!-- Theme toggle button with toggle switch design -->
    <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
        <div class="toggle-icons">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
        </div>
        <div class="toggle-handle"></div>
    </button>

    <!-- Vehicle Details Content -->
    <div class="container">
        <!-- Vehicle Details Section - Split Layout -->
        <div class="vehicle-details-section">
            <div class="vehicle-details-wrapper">
                <div class="vehicle-image-column">
                    <div class="vehicle-main-image">
                        <img src="../images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" id="main-image">
                        <div class="vehicle-badge <?php echo $vehicle['available'] ? 'available' : 'unavailable'; ?>">
                            <i class="fas <?php echo $vehicle['available'] ? 'fa-check' : 'fa-times'; ?>"></i>
                            <?php echo $vehicle['available'] ? 'Available Now' : 'Unavailable'; ?>
                        </div>
                    </div>
                    <?php if (!empty($sub_images)): ?>
                        <div class="image-thumbnails">
                            <div class="thumb active" data-src="../images/<?php echo htmlspecialchars($vehicle['image']); ?>" data-alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                                <img src="../images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                            </div>
                            <?php foreach($sub_images as $image): ?>
                                <div class="thumb" data-src="../images/<?php echo htmlspecialchars($image['image']); ?>" data-alt="<?php echo htmlspecialchars($image['title']); ?>">
                                    <img src="../images/<?php echo htmlspecialchars($image['image']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="vehicle-info-column">
                    <h1 class="vehicle-name"><?php echo htmlspecialchars($vehicle['name']); ?></h1>
                    <div class="vehicle-type">
                        <i class="fas fa-car"></i> <?php echo htmlspecialchars($vehicle['type']); ?>
                    </div>
                    <div class="price-tag">
                        <span class="amount">$<?php echo number_format($vehicle['price_per_day'], 2); ?></span>
                        <span class="period">/day</span>
                    </div>
                    <div class="specs-container">
                        <div class="spec-item">
                            <div class="spec-icon"><i class="fas fa-users"></i></div>
                            <div class="spec-info">
                                <h4>Capacity</h4>
                                <p><?php echo $vehicle['capacity']; ?> persons</p>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon"><i class="fas fa-road"></i></div>
                            <div class="spec-info">
                                <h4>Distance</h4>
                                <p>150km included per day</p>
                            </div>
                        </div>
                    </div>
                    <div class="vehicle-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($vehicle['description'])); ?></p>
                    </div>
                    <div class="vehicle-actions">
                        <a href="../index.php#vehicle-hire" class="btn-view-all">View All Vehicles</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Tour Types -->
        <?php if (!empty($sub_details)): ?>
            <h2 class="section-title">Vehicle Tour Types</h2>
            <div class="tour-types-container">
                <?php foreach($sub_details as $detail): ?>
                    <div class="tour-type-card">
                        <div class="tour-type-header">
                            <i class="<?php echo htmlspecialchars($detail['icon']); ?>"></i>
                            <h3><?php echo htmlspecialchars($detail['header']); ?></h3>
                        </div>
                        <div class="tour-type-content">
                            <?php if(isset($detail['image']) && !empty($detail['image'])): ?>
                            <div class="tour-type-image">
                                <img src="../images/<?php echo htmlspecialchars($detail['image']); ?>" alt="<?php echo htmlspecialchars($detail['header']); ?>">
                            </div>
                            <?php endif; ?>
                            <p><?php echo nl2br(htmlspecialchars($detail['content'])); ?></p>
                        </div>
                        <?php if(isset($detail['price']) && $detail['price'] > 0): ?>
                        <div class="tour-type-price">
                            <span class="price-label">Price:</span>
                            <span class="price-value">$<?php echo number_format($detail['price'], 2); ?></span>
                        </div>
                        
                        <?php 
                        // Determine if this tour should have a ribbon and which type
                        $hasRibbon = false;
                        $ribbonType = '';
                        $ribbonText = '';
                        
                        // If order number is low (new tours are usually added first)
                        if ($detail['order_num'] <= 1) {
                            $hasRibbon = true;
                            $ribbonType = 'ribbon-new';
                            $ribbonText = 'New';
                        } 
                        // For tours with special pricing
                        elseif ($detail['price'] < 50 && $detail['price'] > 0) {
                            $hasRibbon = true;
                            $ribbonType = 'ribbon-discount';
                            $ribbonText = 'Special';
                        }
                        // Custom ribbon for a specific tour header (e.g., "Premium Tour")
                        elseif (stripos($detail['header'], 'premium') !== false) {
                            $hasRibbon = true;  
                            $ribbonType = 'ribbon-special';
                            $ribbonText = 'Premium';
                        }
                        
                        if ($hasRibbon):
                        ?>
                        <div class="ribbon-container">
                            <div class="ribbon <?php echo $ribbonType; ?>"><?php echo $ribbonText; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Contact Us Button -->
                        <div class="tour-type-contact">
                            <a href="../contact_us.php?inquiry=<?php echo urlencode($vehicle['name'] . ' - ' . $detail['header']); ?>" class="contact-btn">
                                <i class="fas fa-paper-plane"></i> INQUIRE NOW
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Includes & Excludes -->
        <div class="includes-section">
            <!-- Included Features -->
            <div class="includes-box">
                <h3><i class="fas fa-check-circle"></i> What's Included</h3>
                <ul>
                    <?php if (!empty($includes)): ?>
                        <?php foreach($includes as $include): ?>
                            <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($include['feature_description']); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><i class="fas fa-info-circle"></i> Contact us for included features</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about-section">
                    <h3>About Us</h3>
                    <p>Adventure Travel.lk is a premier travel agency specializing in adventure tours and memorable experiences across Sri Lanka.</p>
                </div>
                <div class="footer-section links-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../tour_packages/tour_packages.php">Tour Packages</a></li>
                        <li><a href="../one_day_tour_packages/one_day_tour.php">One Day Tours</a></li>
                        <li><a href="../special_tour_packages/special_tour.php">Special Tours</a></li>
                    </ul>
                </div>
                <div class="footer-section contact-section">
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

    <!-- Lightbox for image zoom -->
    <div class="lightbox" id="imageLightbox">
        <div class="lightbox-content">
            <img src="" alt="" class="lightbox-image" id="lightboxImage">
        </div>
        <div class="lightbox-close" id="lightboxClose">
            <i class="fas fa-times"></i>
        </div>
        <div class="lightbox-nav lightbox-prev" id="lightboxPrev">
            <i class="fas fa-chevron-left"></i>
        </div>
        <div class="lightbox-nav lightbox-next" id="lightboxNext">
            <i class="fas fa-chevron-right"></i>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

            // Image gallery thumbnails
            const thumbnails = document.querySelectorAll('.thumb');
            const mainImage = document.getElementById('main-image');
            let currentImageIndex = 0;
            const imageUrls = [];
            const imageTitles = [];
            
            // Collect all image sources and titles
            if (thumbnails.length > 0) {
                thumbnails.forEach((thumb, index) => {
                    const src = thumb.getAttribute('data-src');
                    const alt = thumb.getAttribute('data-alt');
                    imageUrls.push(src);
                    imageTitles.push(alt);
                });
            } else if (mainImage) {
                // If no thumbnails, at least use the main image
                imageUrls.push(mainImage.src);
                imageTitles.push(mainImage.alt);
            }
            
            if (thumbnails.length > 0 && mainImage) {
                thumbnails.forEach((thumb, index) => {
                    thumb.addEventListener('click', function() {
                        // Update main image
                        const imgSrc = this.getAttribute('data-src');
                        const imgAlt = this.getAttribute('data-alt');
                        
                        if (imgSrc && imgAlt) {
                            mainImage.src = imgSrc;
                            mainImage.alt = imgAlt;
                            currentImageIndex = index;
                            
                            // Update active thumbnail
                            thumbnails.forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                        }
                    });
                });
            }
            
            // Lightbox functionality
            const lightbox = document.getElementById('imageLightbox');
            const lightboxImage = document.getElementById('lightboxImage');
            const lightboxClose = document.getElementById('lightboxClose');
            const lightboxPrev = document.getElementById('lightboxPrev');
            const lightboxNext = document.getElementById('lightboxNext');
            
            // Open lightbox when clicking on main image
            if (mainImage && lightbox && lightboxImage) {
                mainImage.addEventListener('click', function() {
                    lightboxImage.src = this.src;
                    lightboxImage.alt = this.alt;
                    lightbox.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling when lightbox is open
                });
                
                // Close lightbox
                lightboxClose.addEventListener('click', function() {
                    lightbox.classList.remove('active');
                    document.body.style.overflow = ''; // Re-enable scrolling
                });
                
                // Close lightbox on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                        lightbox.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
                
                // Navigate through images
                lightboxPrev.addEventListener('click', function() {
                    if (imageUrls.length <= 1) return;
                    
                    currentImageIndex = (currentImageIndex - 1 + imageUrls.length) % imageUrls.length;
                    lightboxImage.src = imageUrls[currentImageIndex];
                    lightboxImage.alt = imageTitles[currentImageIndex] || '';
                });
                
                lightboxNext.addEventListener('click', function() {
                    if (imageUrls.length <= 1) return;
                    
                    currentImageIndex = (currentImageIndex + 1) % imageUrls.length;
                    lightboxImage.src = imageUrls[currentImageIndex];
                    lightboxImage.alt = imageTitles[currentImageIndex] || '';
                });
                
                // Keyboard navigation
                document.addEventListener('keydown', function(e) {
                    if (!lightbox.classList.contains('active')) return;
                    
                    if (e.key === 'ArrowLeft') {
                        lightboxPrev.click();
                    } else if (e.key === 'ArrowRight') {
                        lightboxNext.click();
                    }
                });
                
                // Image zoom in lightbox
                let isZoomed = false;
                let scale = 1;
                
                lightboxImage.addEventListener('click', function(e) {
                    if (!isZoomed) {
                        // Zoom in
                        scale = 2.5;
                        this.style.transform = `scale(${scale})`;
                        this.classList.add('zoomed');
                        isZoomed = true;
                    } else {
                        // Zoom out
                        scale = 1;
                        this.style.transform = 'scale(1)';
                        this.classList.remove('zoomed');
                        isZoomed = false;
                    }
                });
            }
        });
    </script>
</body>
</html>
