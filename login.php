<?php
// Include database configuration
require_once 'admin/config.php';

// Start the session if one doesn't exist already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Initialize variables
$username = '';
$email = '';
$fullname = '';
$phone = '';
$address = '';
$errors = [];
$form_type = isset($_GET['form']) ? $_GET['form'] : 'login';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which form was submitted
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'login') {
            // Login form processing
            // Get form data and sanitize
            $username = sanitize_input($_POST['username']);
            $password = $_POST['password'];
            
            // Form validation
            if (empty($username)) {
                $errors[] = "Username is required";
            }
            
            if (empty($password)) {
                $errors[] = "Password is required";
            }
            
            // Check if table exists
            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
            if (mysqli_num_rows($check_table) == 0) {
                $errors[] = "User database not found. Please register first.";
            }
            
            // If no errors, proceed with login
            if (empty($errors)) {
                $query = "SELECT * FROM users WHERE username = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) === 1) {
                    $user = mysqli_fetch_assoc($result);
                    
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        
                        // Redirect to home page
                        header("Location: index.php");
                        exit;
                    } else {
                        $errors[] = "Invalid password";
                    }
                } else {
                    $errors[] = "User not found";
                }
            }
        } else if ($_POST['action'] == 'register') {
            // Registration form processing
            // Get form data and sanitize
            $username = sanitize_input($_POST['username']);
            $email = sanitize_input($_POST['email']);
            $fullname = sanitize_input($_POST['fullname']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Form validation
            if (empty($username)) {
                $errors[] = "Username is required";
            }
            
            if (empty($email)) {
                $errors[] = "Email is required";
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            
            if (empty($fullname)) {
                $errors[] = "Full name is required";
            }
            
            if (empty($password)) {
                $errors[] = "Password is required";
            }
            
            if ($password !== $confirm_password) {
                $errors[] = "Passwords do not match";
            }
            
            // Check if username already exists
            $check_query = "SELECT * FROM users WHERE username = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $username);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Username already exists";
            }
            
            // Check if email already exists
            $check_query = "SELECT * FROM users WHERE email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Email already exists";
            }
            
            // If no errors, proceed with registration
            if (empty($errors)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $query = "INSERT INTO users (username, email, full_name, phone, address, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $fullname, $phone, $address, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Set success message and redirect to login
                    $_SESSION['registration_success'] = "Registration successful! Please log in.";
                    header("Location: login.php");
                    exit;
                } else {
                    $errors[] = "Registration failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adventure Travel.lk - Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(-45deg, #76b852, #176c65, #8DC26F, #176c65);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Animated leaves */
        .leaves {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .leaf {
            position: absolute;
            display: block;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0 70% 0 70%;
            transform: rotate(45deg);
        }

        .leaf:nth-child(1) {
            left: 20%;
            width: 80px;
            height: 80px;
            animation: leaf-fall 15s linear infinite, leaf-sway 4s ease-in-out infinite alternate;
            animation-delay: 0s;
        }

        .leaf:nth-child(2) {
            left: 70%;
            width: 60px;
            height: 60px;
            animation: leaf-fall 18s linear infinite, leaf-sway 5s ease-in-out infinite alternate;
            animation-delay: 1s;
        }

        .leaf:nth-child(3) {
            left: 10%;
            width: 100px;
            height: 100px;
            animation: leaf-fall 20s linear infinite, leaf-sway 6s ease-in-out infinite alternate;
            animation-delay: 2s;
        }

        .leaf:nth-child(4) {
            left: 50%;
            width: 40px;
            height: 40px;
            animation: leaf-fall 14s linear infinite, leaf-sway 4s ease-in-out infinite alternate;
            animation-delay: 0s;
        }

        .leaf:nth-child(5) {
            left: 85%;
            width: 70px;
            height: 70px;
            animation: leaf-fall 17s linear infinite, leaf-sway 5s ease-in-out infinite alternate;
            animation-delay: 1.5s;
        }

        .leaf:nth-child(6) {
            left: 30%;
            width: 60px;
            height: 60px;
            animation: leaf-fall 16s linear infinite, leaf-sway 4.5s ease-in-out infinite alternate;
            animation-delay: 3s;
        }

        @keyframes leaf-fall {
            0% {
                top: -10%;
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                top: 110%;
                opacity: 0;
            }
        }

        @keyframes leaf-sway {
            0% {
                margin-left: 0px;
                transform: rotate(45deg);
            }
            100% {
                margin-left: 100px;
                transform: rotate(90deg);
            }
        }

        /* Sun rays effect */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: radial-gradient(circle at 70% 20%, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 60%);
            animation: sun-rays 10s ease-in-out infinite;
        }

        @keyframes sun-rays {
            0%, 100% {
                opacity: 0.7;
                transform: scale(1);
            }
            50% {
                opacity: 0.9;
                transform: scale(1.1);
            }
        }

        .container {
            position: relative;
            z-index: 1;
        }
        
        .account-container {
            max-width: 450px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 2px solid rgb(0, 0, 0);
            backdrop-filter: blur(5px);
            transform: translateY(0);
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }

        .account-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .account-header {
            background: linear-gradient(135deg, rgb(23, 108, 101), rgb(101, 255, 193));
            color: #fff;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .account-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shine-header 3s infinite;
        }
        
        @keyframes shine-header {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .account-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .account-header p {
            font-size: 1rem;
            margin-bottom: 0;
            opacity: 0.9;
        }
        
        .account-form {
            padding: 25px;
        }
        
        .form-control {
            padding: 0.6rem 0.75rem;
            font-size: 0.95rem;
            border-radius: 8px;
            border: 1px solid rgba(23, 108, 101, 0.2);
            background-color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: rgb(23, 108, 101);
            box-shadow: 0 0 0 0.25rem rgba(23, 108, 101, 0.15);
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, rgb(23, 108, 101), rgb(0, 255, 204));
            border: none;
            padding: 0.6rem 0.75rem;
            font-size: 1rem;
            border-radius: 30px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgb(0, 255, 204), rgb(23, 108, 101));
            z-index: -1;
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
        
        .alert {
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.95rem;
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            border: 1px solid rgba(25, 135, 84, 0.2);
        }
        
        .account-footer {
            padding: 15px 25px;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.95rem;
            background-color: rgba(255, 255, 255, 0.5);
        }
        
        .form-check {
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .form-label {
            font-size: 0.95rem;
            margin-bottom: 0.35rem;
            font-weight: 500;
            color: rgb(23, 108, 101);
        }
        
        .mb-3 {
            margin-bottom: 1.25rem !important;
        }
        
        .text-center {
            font-size: 0.95rem;
        }
        
        .btn-home {
            background: rgba(0, 255, 204, 0.2);
            border: 2px solid rgb(23, 108, 101);
            color: rgb(23, 108, 101);
            font-weight: 600;
            border-radius: 30px;
            padding: 0.5rem 1.25rem;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            background-color: rgb(0, 255, 204);
            border-color: rgb(23, 108, 101);
            color: rgb(23, 108, 101);
            transform: translateY(-2px);
        }
        
        a {
            color: rgb(23, 108, 101);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        a:hover {
            color: rgb(0, 255, 204);
            text-decoration: underline;
        }
        
        /* Form switcher */
        .form-switcher {
            display: flex;
            border-radius: 30px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 2px solid rgb(23, 108, 101);
            position: relative;
        }
        
        .switch-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1;
            background: none;
            border: none;
        }
        
        .switch-btn.active {
            color: white;
        }
        
        .slider {
            position: absolute;
            width: 50%;
            height: 100%;
            top: 0;
            background: linear-gradient(135deg, rgb(23, 108, 101), rgb(0, 255, 204));
            border-radius: 30px;
            transition: all 0.3s ease-in-out;
        }
        
        .slider.login {
            left: 0;
        }
        
        .slider.register {
            left: 50%;
        }
        
        #login-form, #register-form {
            display: none;
            animation: fadeIn 0.5s forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="leaves">
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
    </div>
    <div class="container">
        <div class="account-container">
            <div class="account-header">
                <h1>Adventure Travel.lk</h1>
                <p>Access your adventure account</p>
            </div>
            
            <div class="account-form">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['registration_success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['registration_success']; ?>
                        <?php unset($_SESSION['registration_success']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-switcher">
                    <div class="slider <?php echo $form_type === 'login' ? 'login' : 'register'; ?>"></div>
                    <button type="button" class="switch-btn <?php echo $form_type === 'login' ? 'active' : ''; ?>" id="login-switch">Login</button>
                    <button type="button" class="switch-btn <?php echo $form_type === 'register' ? 'active' : ''; ?>" id="register-switch">Register</button>
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="login-form" style="<?php echo $form_type === 'login' ? 'display:block;' : ''; ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                    
                    <div class="text-center mt-3">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </div>
                </form>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="register-form" style="<?php echo $form_type === 'register' ? 'display:block;' : ''; ?>">
                    <input type="hidden" name="action" value="register">
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reg-username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="reg-username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reg-password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="reg-password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm-password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-home">Back to Home</a>
                </div>
            </div>
            
            <div class="account-footer">
                &copy; <?php echo date('Y'); ?> Adventure Travel.lk - All rights reserved
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginSwitch = document.getElementById('login-switch');
            const registerSwitch = document.getElementById('register-switch');
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const slider = document.querySelector('.slider');
            
            loginSwitch.addEventListener('click', function() {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                slider.classList.remove('register');
                slider.classList.add('login');
                loginSwitch.classList.add('active');
                registerSwitch.classList.remove('active');
                // Update URL without refreshing page
                history.pushState(null, null, '?form=login');
            });
            
            registerSwitch.addEventListener('click', function() {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                slider.classList.remove('login');
                slider.classList.add('register');
                registerSwitch.classList.add('active');
                loginSwitch.classList.remove('active');
                // Update URL without refreshing page
                history.pushState(null, null, '?form=register');
            });
            
            // Show correct form on page load
            if ('<?php echo $form_type; ?>' === 'register') {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                slider.classList.remove('login');
                slider.classList.add('register');
                registerSwitch.classList.add('active');
                loginSwitch.classList.remove('active');
            } else {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                slider.classList.remove('register');
                slider.classList.add('login');
                loginSwitch.classList.add('active');
                registerSwitch.classList.remove('active');
            }
        });
    </script>
</body>
</html>
