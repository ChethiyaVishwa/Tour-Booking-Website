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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>About Us - Adventure Travel.lk</title>
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
            --card-bg: #fff;
            --header-bg: rgb(0, 255, 204);
            --footer-bg: #333;
            --footer-text: #fff;
            --card-shadow: rgba(0, 0, 0, 0.1);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: rgb(23, 108, 101);
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
        

        /* Theme toggle button - Stylish switch design */
        .theme-toggle {
            position: fixed;
            left: 20px;
            top: 180px; 
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

            .policy-buttons {
                flex-direction: column;
                align-items: center;
            }

            .policy-btn {
                width: 100%;
                max-width: 300px;
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

            .policy-buttons {
                flex-direction: column;
                align-items: center;
            }

            .policy-btn {
                width: 100%;
                max-width: 300px;
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

        /* About Us Content */
        .about-section {
            margin-top: 100px;
            padding: 40px 0;
        }

        .about-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .about-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .about-content {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--card-shadow);
            margin-bottom: 40px;
            transition: background-color 0.5s ease, box-shadow 0.5s ease;
        }

        .about-content p {
            margin-bottom: 20px;
            line-height: 1.7;
            color: var(--text-color);
            transition: color 0.5s ease;
        }

        .policy-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .policy-btn {
            padding: 15px 30px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
            text-align: center;
            min-width: 200px;
        }

        .policy-btn:hover {
            background-color: #124e48;
            transform: translateY(-3px);
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
            color: var(--footer-text);
            opacity: 0.8;
            transition: color 0.5s ease;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: var(--footer-text);
            opacity: 0.8;
            text-decoration: none;
            transition: color 0.3s ease, opacity 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: var(--secondary-color);
            opacity: 1;
        }

        .footer-bottom {
            border-top: 1px solid var(--border-alt-color);
            padding-top: 20px;
            margin-top: 20px;
            text-align: center;
            color: var(--footer-text);
            opacity: 0.7;
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
            <a href="../one_day_tour_packages/one_day_tour.php">One Day Tours</a>
            <a href="../special_tour_packages/special_tour.php">Special Tours</a>
            <a href="../index.php#vehicle-hire">Vehicle Hire</a>
            <a href="../destinations/destinations.php">Destinations</a>
            <a href="../contact_us.php">Contact Us</a>
            <a href="about_us.php">About Us</a>
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

    <section class="about-section">
        <div class="container">
            <div class="about-header">
                <h1>About Adventure Travel.lk</h1>
            </div>
            
            <div class="about-content">
                <p>Welcome to Adventure Travel.lk, your premier travel partner in Sri Lanka. We specialize in creating unforgettable travel experiences that showcase the beauty and culture of our island nation.</p>
                
                <p>With years of experience in the tourism industry, we have built a reputation for excellence in service and customer satisfaction. Our team of experienced professionals is dedicated to making your journey memorable and hassle-free.</p>
                
                <p>Whether you're looking for a relaxing beach holiday, an adventurous trek through the mountains, or a cultural exploration of ancient cities, we have the perfect package for you.</p>
                
                <div class="policy-buttons">
                    <a href="terms_conditions.php" class="policy-btn">Terms and Conditions</a>
                    <a href="privacy_policy.php" class="policy-btn">Privacy Policy</a>
                    <a href="refund_policy.php" class="policy-btn">Refund Policy</a>
                </div>
            </div>
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
            const scrollThreshold = 100;
            
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop <= 10) {
                    header.classList.remove('hide');
                    return;
                }
                
                if (Math.abs(lastScrollTop - scrollTop) <= scrollThreshold) return;
                
                if (scrollTop > lastScrollTop) {
                    header.classList.add('hide');
                } else {
                    header.classList.remove('hide');
                }
                
                lastScrollTop = scrollTop;
            });
        });
    </script>
</body>
</html>
