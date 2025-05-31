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

// Initialize variables
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
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

// Process form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get and sanitize form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    
    // Validate form data
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists (excluding current user)
    $check_query = "SELECT * FROM users WHERE email = ? AND user_id != ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "Email already exists for another account";
    }
    
    // If no errors, update user data
    if (empty($errors)) {
        $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssi", $full_name, $email, $phone, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Update session data
            $_SESSION['full_name'] = $full_name;
            
            // Refresh user data
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile: " . mysqli_error($conn);
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Process form submission for password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    // Verify current password
    if (empty($errors) && !password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Password changed successfully!";
        } else {
            $error_message = "Error changing password: " . mysqli_error($conn);
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Title for page
$page_title = "Account Settings";
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
            --muted-text: #6c757d;
            --tab-inactive: #495057;
            --input-bg: #fff;
            --input-border: #ced4da;
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
            --card-shadow: rgba(0, 0, 0, 0.5);
            --border-color: rgb(23, 15, 132);
            --border-alt-color: rgb(0, 179, 143);
            --muted-text: #adb5bd;
            --tab-inactive: #adb5bd;
            --input-bg: #333;
            --input-border: #555;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
            padding-top: 80px;
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
            transition: background 0.5s ease, border-color 0.5s ease;
        }
        
        .logo img {
            height: 50px;
        }
        
        .back-btn {
            background: var(--primary-color);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: var(--secondary-color);
            color: var(--text-color);
        }
        
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--card-shadow);
            margin-bottom: 25px;
            background-color: var(--card-bg);
            transition: background-color 0.5s ease, box-shadow 0.5s ease;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 15px 20px;
            font-weight: bold;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--text-color);
            transition: background-color 0.5s ease, color 0.5s ease, border-color 0.5s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(23, 108, 101, 0.25);
            background-color: var(--input-bg);
            color: var(--text-color);
        }
        
        .form-control:disabled {
            background-color: var(--bg-color);
            color: var(--muted-text);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .nav-tabs {
            border-bottom: 2px solid var(--border-alt-color);
            margin-bottom: 20px;
        }
        
        .nav-tabs .nav-link {
            color: var(--tab-inactive);
            border: none;
            padding: 15px 20px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .form-floating label {
            padding-left: 15px;
        }
        
        .form-floating .form-control {
            height: calc(3.5rem + 2px);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
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
        
        .profile-title h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .profile-title p {
            color: var(--muted-text);
            margin-bottom: 0;
        }
        
        .text-muted {
            color: var(--muted-text) !important;
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
            .settings-container {
                padding: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .nav-tabs .nav-link {
                padding: 10px 15px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
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
    </style>
</head>
<body>
    <header class="header">
        <a href="#" class="logo">
            <img src="images/logo-4.png" alt="Adventure Travel.lk Logo">
        </a>
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </header>

    <!-- Theme toggle button -->
    <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
        <div class="toggle-icons">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
        </div>
        <div class="toggle-handle"></div>
    </button>

    <div class="settings-container">
        <div class="profile-header">
            <div class="profile-image">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=176c65&color=fff" alt="Profile">
            </div>
            <div class="profile-title">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                    <i class="fas fa-user"></i> Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                    <i class="fas fa-lock"></i> Security
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabContent">
            <!-- Profile Settings Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="card">
                    <div class="card-header">
                        Profile Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Security Settings Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="card">
                    <div class="card-header">
                        Change Password
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
</body>
</html> 