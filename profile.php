<?php
// Include database configuration
require_once 'admin/config.php';

// Start the session if one doesn't exist already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Get user information
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
} else {
    // If user not found, redirect to login
    header("Location: login.php");
    exit;
}

// Initialize bookings_result as null
$bookings_result = null;
$has_bookings_table = false;

// Check if bookings table exists
$table_check_query = "SHOW TABLES LIKE 'bookings'";
$table_check_result = mysqli_query($conn, $table_check_query);
if (mysqli_num_rows($table_check_result) > 0) {
    $has_bookings_table = true;
    
    // Get user bookings
    $bookings_query = "SELECT b.*, p.package_name, p.price, p.image 
                      FROM bookings b 
                      JOIN packages p ON b.package_id = p.package_id 
                      WHERE b.user_id = ? 
                      ORDER BY b.booking_date DESC";
    $bookings_stmt = mysqli_prepare($conn, $bookings_query);
    mysqli_stmt_bind_param($bookings_stmt, "i", $user_id);
    mysqli_stmt_execute($bookings_stmt);
    $bookings_result = mysqli_stmt_get_result($bookings_stmt);
}

// Page title
$page_title = "User Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Adventure Travel.lk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: rgb(23, 108, 101);
            --secondary-color: rgb(101, 255, 193);
            --text-color: #333;
            --bg-color: #f8f9fa;
            --card-bg: #fff;
            --header-bg: rgb(0, 255, 204);
            --footer-bg: #222;
            --footer-text: #fff;
            --card-shadow: rgba(0, 0, 0, 0.08);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: rgb(23, 108, 101);
            --muted-text: #666;
            --section-title: rgb(23, 15, 132);
        }

        .dark-mode {
            --primary-color: rgb(20, 170, 145);
            --secondary-color: rgb(0, 204, 163);
            --text-color: #f0f0f0;
            --bg-color: #121212;
            --card-bg: #2d2d2d;
            --header-bg: rgb(20, 170, 145);
            --footer-bg: #111;
            --footer-text: #ddd;
            --card-shadow: rgba(0, 0, 0, 0.3);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: rgb(0, 179, 143);
            --muted-text: #aaa;
            --section-title: rgb(0, 204, 163);
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
            padding-top: 80px;
            color: var(--text-color);
            transition: background-color 0.5s ease, color 0.5s ease;
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
        
        /* Theme toggle button - Stylish switch design */
        .theme-toggle {
            position: fixed;
            left: 20px;
            top: 180px; /* Positioned lower down */
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
        
        .profile-section {
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--card-shadow);
            padding: 30px;
            margin-bottom: 30px;
            transition: background-color 0.5s ease, box-shadow 0.5s ease;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            border: 3px solid var(--primary-color);
            transition: border-color 0.5s ease;
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-details h2 {
            margin: 0 0 5px;
            color: var(--section-title);
            transition: color 0.5s ease;
        }
        
        .profile-details p {
            margin: 0 0 5px;
            color: var(--muted-text);
            transition: color 0.5s ease;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .profile-actions a {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .profile-actions a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .booking-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--card-shadow);
            margin-bottom: 20px;
            transition: transform 0.3s, background-color 0.5s ease, box-shadow 0.5s ease;
            background-color: var(--card-bg);
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
        }
        
        .booking-card .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        
        .booking-card .card-body {
            padding: 20px;
        }
        
        .booking-card .card-title {
            color: var(--section-title);
            font-weight: 600;
            margin-bottom: 15px;
            transition: color 0.5s ease;
        }
        
        .booking-card .booking-info {
            background-color: var(--bg-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: background-color 0.5s ease;
        }
        
        .booking-card .booking-info p {
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-color);
            transition: color 0.5s ease;
        }
        
        .booking-card .booking-info p strong {
            color: var(--primary-color);
            transition: color 0.5s ease;
        }
        
        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .dark-mode .status-pending {
            background-color: rgba(255, 243, 205, 0.3);
            color: #ffd24d;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .dark-mode .status-confirmed {
            background-color: rgba(212, 237, 218, 0.3);
            color: #28a745;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .dark-mode .status-cancelled {
            background-color: rgba(248, 215, 218, 0.3);
            color: #dc3545;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .dark-mode .status-completed {
            background-color: rgba(204, 229, 255, 0.3);
            color: #0d6efd;
        }
        
        .no-bookings {
            text-align: center;
            padding: 50px 0;
        }
        
        .no-bookings i {
            font-size: 5rem;
            color: var(--card-shadow);
            margin-bottom: 20px;
            transition: color 0.5s ease;
        }
        
        .no-bookings h3 {
            color: var(--muted-text);
            margin-bottom: 15px;
            transition: color 0.5s ease;
        }
        
        .no-bookings p {
            color: var(--text-color);
            transition: color 0.5s ease;
        }
        
        .no-bookings a {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            margin-top: 15px;
        }
        
        .no-bookings a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-3px);
        }
        
        .section-title {
            color: var(--section-title);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
            transition: color 0.5s ease;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
            transition: background-color 0.5s ease;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .profile-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">
            <img src="images/logo-4.png" alt="Adventure Travel.lk Logo">
        </a>
        
        <div class="menu-toggle">☰</div>
        
        <nav class="navbar">
            <a href="index.php">Home</a>
            <a href="tour_packages/tour_packages.php">Tour Packages</a>
            <a href="one_day_tour_packages/one_day_tour.php">One Day Tours</a>
            <a href="special_tour_packages/special_tour.php">Special Tours</a>
            <a href="index.php#vehicle-hire">Vehicle Hire</a>
            <a href="destinations/destinations.php">Destinations</a>
            <a href="contact_us.php">Contact Us</a>
            <a href="about_us/about_us.php">About Us</a>
        </nav>
    </header>

    <!-- Theme toggle button -->
    <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
        <div class="toggle-icons">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
        </div>
        <div class="toggle-handle"></div>
    </button>

    <div class="container">
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=176c65&color=fff" alt="Profile">
                </div>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if(!empty($user['phone'])): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                    <div class="profile-actions">
                        <a href="settings.php"><i class="fas fa-cog"></i> Edit Profile</a>
                        <a href="javascript:void(0);" onclick="if(confirm('Are you sure you want to log out?')) window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html> 