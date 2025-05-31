<?php
    // Start output buffering to prevent "headers already sent" errors
    ob_start();
    
    // Database connection
    require_once 'admin/config.php';
    
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
    <title>Contact Us - Adventure Travel.lk</title>
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
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .navbar a:hover {
            color: rgb(255, 0, 0);
        }

        /* Contact Page Specific Styles */
        .contact-section {
            padding: 120px 0 80px;
            background-color: var(--bg-alt-color);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-header h1 {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .contact-header p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            color: var(--text-color);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .back-btn i {
            margin-right: 10px;
        }
        
        .back-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            color: black;
        }

        .contact-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 50px;
        }

        .contact-info {
            flex: 1;
            min-width: 300px;
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--card-shadow);
            padding: 30px;
        }

        .contact-info h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
        }

        .contact-info h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 50px;
            height: 3px;
            background: var(--secondary-color);
        }

        .info-item {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .info-details h4 {
            margin: 0 0 5px;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .info-details p {
            margin: 0;
            color: var(--text-color);
            line-height: 1.5;
        }

        .contact-form {
            flex: 1;
            min-width: 300px;
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--card-shadow);
            padding: 30px;
        }

        .contact-form h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
        }

        .contact-form h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 50px;
            height: 3px;
            background: var(--secondary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 16px; /* Fixed 16px font size to prevent zoom */
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            touch-action: manipulation; /* Prevents browser manipulation */
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(23, 108, 101, 0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(23, 108, 101, 0.3);
        }

        .social-links {
            text-align: center;
            margin-top: 50px;
        }

        .social-links h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .social-links h3::after {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -8px;
            width: 50px;
            height: 3px;
            background: var(--secondary-color);
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .social-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.5rem;
            box-shadow: 0 5px 15px var(--card-shadow);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-icon:hover {
            transform: translateY(-5px);
            color: white;
        }

        .whatsapp:hover {
            background-color: #25D366;
        }

        .facebook:hover {
            background-color: #3b5998;
        }

        .instagram:hover {
            background-color: #E1306C;
        }

        .email:hover {
            background-color: #D44638;
        }

        .map-container {
            margin-top: 50px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--card-shadow);
        }

        .map-container h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .map-wrapper {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Theme toggle button - Stylish switch design */
        .theme-toggle {
            position: fixed;
            left: 20px;
            top: 180px; /* Positioned further down */
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

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: var(--text-color);
            transition: color 0.3s ease;
        }

        /* Footer Styles */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0;
            margin-top: 80px;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            background-color: transparent;
        }

        .footer-section.about-section,
        .footer-section.links-section,
        .footer-section.contact-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: transparent;
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
            color: var(--footer-text);
            transition: border-color 0.5s ease, color 0.5s ease;
        }

        /* Responsive Styles */
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

            .contact-content {
                flex-direction: column;
            }
            
            .contact-info, .contact-form {
                width: 100%;
                margin-bottom: 30px;
            }
            
            .social-icons {
                gap: 15px;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-section {
                margin-bottom: 40px;
                min-width: 100%;
                background-color: transparent;
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
            
            .contact-header h1 {
                font-size: 2rem;
            }
            
            .contact-header p {
                font-size: 1rem;
            }
            
            /* Prevent zoom on mobile form inputs */
            .form-control {
                font-size: 16px !important; /* iOS won't zoom if font size is at least 16px */
                transform: scale(1); /* Helps prevent zoom on some Android devices */
                transform-origin: left top;
                touch-action: manipulation; /* Prevents browser manipulation */
            }
            
            .social-icons {
                gap: 15px;
            }
            
            .social-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
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

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-container">
            <div class="contact-header">
                <h1>Get In Touch</h1>
                <p>Have a question or need assistance with your travel plans? We're here to help you plan your perfect adventure.</p>
                <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Previous Page</a>
            </div>

            <div class="contact-content">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-details">
                            <h4>Our Location</h4>
                            <p>Narammala, Kurunegala, Sri Lanka</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="info-details">
                            <h4>Phone Number</h4>
                            <p>+94 71 862 8992</p>
                            <p>+94 77 123 4567</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-details">
                            <h4>Email Address</h4>
                            <p>adventuretravel.lk@gmail.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-details">
                            <h4>Working Hours</h4>
                            <p>Monday - Friday: 9am - 6pm</p>
                            <p>Saturday: 9am - 2pm</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <h3>Send us a Message</h3>
                    <?php
                    // Process form submission
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_submit'])) {
                        $name = mysqli_real_escape_string($conn, $_POST['name']);
                        $email = mysqli_real_escape_string($conn, $_POST['email']);
                        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
                        $message = mysqli_real_escape_string($conn, $_POST['message']);
                        
                        // Insert into database
                        $insert_query = "INSERT INTO contact_messages (name, email, subject, message, ip_address, submitted_at) 
                                        VALUES ('$name', '$email', '$subject', '$message', '{$_SERVER['REMOTE_ADDR']}', NOW())";
                        
                        if (mysqli_query($conn, $insert_query)) {
                            // Store success message in session and redirect
                            $_SESSION['contact_message'] = 'success';
                            header("Location: contact_us.php");
                            exit;
                        } else {
                            // Store error message in session and redirect
                            $_SESSION['contact_message'] = 'error';
                            header("Location: contact_us.php");
                            exit;
                        }
                    }
                    
                    // Display messages from session if they exist
                    if (isset($_SESSION['contact_message'])) {
                        if ($_SESSION['contact_message'] == 'success') {
                            echo '<div class="alert alert-success mb-3">Your message has been sent! We\'ll get back to you soon.</div>';
                        } else {
                            echo '<div class="alert alert-danger mb-3">Sorry, there was an error sending your message. Please try again later.</div>';
                        }
                        // Clear the message from session to prevent showing it again on future page loads
                        unset($_SESSION['contact_message']);
                    }
                    
                    /*
                    -- SQL Query to create the contact_messages table:
                    
                    CREATE TABLE `contact_messages` (
                      `message_id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `email` varchar(100) NOT NULL,
                      `subject` varchar(255) DEFAULT NULL,
                      `message` text NOT NULL,
                      `ip_address` varchar(45) DEFAULT NULL,
                      `is_read` tinyint(1) NOT NULL DEFAULT 0,
                      `is_responded` tinyint(1) NOT NULL DEFAULT 0,
                      `submitted_at` datetime NOT NULL,
                      `read_at` datetime DEFAULT NULL,
                      `responded_at` datetime DEFAULT NULL,
                      PRIMARY KEY (`message_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                    */
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="subject" class="form-control" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <textarea name="message" class="form-control" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" name="contact_submit" class="btn-submit">Send Message</button>
                    </form>
                </div>
            </div>

            <div class="social-links">
                <h3>Connect With Us</h3>
                <p>Follow us on social media for the latest travel updates, offers and adventure inspiration.</p>
                <div class="social-icons">
                    <a href="https://wa.me/+94718628992" class="social-icon whatsapp" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="https://www.facebook.com/share/15N56JcC6e/?mibextid=wwXIfr" class="social-icon facebook" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/adventure_travel.lk?igsh=eDkyNXk4cW1lamlw" class="social-icon instagram" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="mailto:adventuretravel.lk@gmail.com" class="social-icon email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="map-container">
                <h3>Find Us On The Map</h3>
                <div class="map-wrapper">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126755.15232321359!2d80.3064801716797!3d7.484586899999992!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae33598ed3c1049%3A0xf1c8efa3f5e3f603!2sNarammala!5e0!3m2!1sen!2slk!4v1655555555555!5m2!1sen!2slk" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="tour_packages/tour_packages.php">Tour Packages</a></li>
                        <li><a href="one_day_tour_packages/one_day_tour.php">One Day Tours</a></li>
                        <li><a href="special_tour_packages/special_tour.php">Special Tours</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // User dropdown functionality
            $('.profile-btn').on('click', function(e) {
                e.stopPropagation();
                $('.user-dropdown').toggleClass('active');
            });
            
            // Close dropdown when clicking elsewhere
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.user-dropdown').length) {
                    $('.user-dropdown').removeClass('active');
                }
            });
            
            // Menu toggle functionality
            $('.menu-toggle').on('click', function() {
                $('.navbar').toggleClass('active');
            });
            
            // Close navigation when a nav link is clicked
            $('.navbar a').on('click', function() {
                $('.navbar').removeClass('active');
            });
            
            // Header show/hide on scroll
            let lastScrollTop = 0;
            $(window).scroll(function() {
                let scrollTop = $(this).scrollTop();
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    $('.header').addClass('hide');
                } else {
                    $('.header').removeClass('hide');
                }
                lastScrollTop = scrollTop;
            });
            
            // Theme toggle functionality
            $('#theme-toggle').on('click', function() {
                $('body').toggleClass('dark-mode');
                
                // Save preference to localStorage
                if ($('body').hasClass('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Check for saved theme preference or use device preference
            const savedTheme = localStorage.getItem('theme');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDarkScheme.matches)) {
                $('body').addClass('dark-mode');
            }
        });
    </script>
</body>
</html>
<?php
// Flush the output buffer and send content to browser
ob_end_flush();
?>
