<?php
// Include database configuration
require_once 'config.php';

// Check if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit;
}

// Initialize variables
$login_error = "";
$username = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password before verification
    
    // Check if fields are empty
    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        // Query to find the admin
        $query = "SELECT admin_id, username, password, email, full_name FROM admins WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        // Check if admin exists
        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                
                // Update last login time
                $update_query = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "i", $admin['admin_id']);
                mysqli_stmt_execute($update_stmt);
                
                // Redirect to admin dashboard
                header("Location: admin.php");
                exit;
            } else {
                $login_error = "Invalid username or password.";
            }
        } else {
            $login_error = "Invalid username or password.";
        }
    }
}

// Check if admins table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
if (mysqli_num_rows($table_check) == 0) {
    // Create the admins table
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `admins` (
      `admin_id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(255) NOT NULL,
      `email` varchar(100) NOT NULL,
      `full_name` varchar(100) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_login` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`admin_id`),
      UNIQUE KEY `username` (`username`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $create_table_query);
}

// Check if there are any admins
$admin_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
$admin_count = mysqli_fetch_assoc($admin_count_query)['count'];

if ($admin_count == 0) {
    // Create default admin account
    $default_username = 'admin';
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $default_email = 'admin@adventuretravel.com';
    $default_fullname = 'Administrator';
    
    $insert_query = "INSERT INTO admins (username, password, email, full_name) 
                    VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "ssss", $default_username, $default_password, $default_email, $default_fullname);
    mysqli_stmt_execute($stmt);
    
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $success_message = "Default admin user created. Username: admin, Password: admin123";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Adventure Travel.lk</title>
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
            --danger-color: #dc3545;
            --success-color: #28a745;
        }

        body {
            background-color: rgb(0, 255, 204);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .login-container {
            background-color: #fff;
            border-radius: 8px;
            border: 2px solid rgb(23, 108, 101);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .login-form .form-group {
            margin-bottom: 1.25rem;
        }

        .login-form label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: bold;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .login-form input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .login-form input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .login-form button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-form button:hover {
            background-color: #145a55;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.9rem;
            margin-top: 1rem;
            text-align: center;
        }

        .success-message {
            color: var(--success-color);
            font-size: 0.9rem;
            margin-top: 1rem;
            text-align: center;
        }

        .back-to-site {
            text-align: center;
            margin-top: 1.25rem;
        }

        .back-to-site a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .back-to-site a:hover {
            text-decoration: underline;
        }
        
        .debug-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #999;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }
            
            .login-container {
                padding: 1.5rem;
                max-width: 100%;
            }
            
            .login-header h1 {
                font-size: 1.3rem;
            }
            
            .login-header p {
                font-size: 0.8rem;
            }
            
            .login-form input, 
            .login-form button {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-height: 600px) {
            body {
                align-items: flex-start;
                padding-top: 2rem;
            }
            
            .login-container {
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Enter your credentials to access the admin dashboard</p>
        </div>
        
        <?php if (!empty($login_error)): ?>
            <div class="error-message">
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div class="back-to-site">
            <a href="../index.php">Back to website</a>
        </div>
        
        
    </div>
</body>
</html>
