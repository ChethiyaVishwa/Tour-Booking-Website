<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Table Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .action-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #176c65;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Adventure Travel.lk Admin System Check</h1>
    
<?php
// Include the configuration file to get database connection
require_once 'config.php';

// Check if the admins table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
if (mysqli_num_rows($table_check) > 0) {
    echo "<p class='success'>✓ The 'admins' table exists in the database.</p>";
    
    // Check if there are any admin records
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
    $count_result = mysqli_fetch_assoc($count_query);
    echo "<p>There are " . $count_result['count'] . " admin accounts in the database.</p>";
    
    // Display all admin accounts (except passwords)
    $admins_query = mysqli_query($conn, "SELECT admin_id, username, email, full_name, created_at, last_login FROM admins");
    
    if (mysqli_num_rows($admins_query) > 0) {
        echo "<h3>Admin Accounts:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Created</th><th>Last Login</th></tr>";
        
        while ($admin = mysqli_fetch_assoc($admins_query)) {
            echo "<tr>";
            echo "<td>" . $admin['admin_id'] . "</td>";
            echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "<td>" . $admin['created_at'] . "</td>";
            echo "<td>" . ($admin['last_login'] ? $admin['last_login'] : 'Never') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No admin accounts found in the table.</p>";
    }
} else {
    echo "<p class='error'>✗ The 'admins' table does not exist in the database.</p>";
    
    // Create the admins table
    echo "<h3>Creating the admins table:</h3>";
    
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
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p class='success'>✓ Created the 'admins' table successfully.</p>";
        
        // Insert default admin user
        $default_username = 'admin';
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $default_email = 'admin@adventuretravel.com';
        $default_fullname = 'Administrator';
        
        $insert_query = "INSERT INTO admins (username, password, email, full_name) 
                        VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssss", $default_username, $default_password, $default_email, $default_fullname);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p class='success'>✓ Created default admin user.</p>";
            echo "<p>Default admin login: <br>Username: admin<br>Password: admin123</p>";
        } else {
            echo "<p class='error'>✗ Failed to create default admin user: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Failed to create the 'admins' table: " . mysqli_error($conn) . "</p>";
    }
}

// Test the admin login function
echo "<h3>Testing the admin login function:</h3>";

// Reset the admin login session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Try to verify password of the admin user
$test_username = 'admin';
$test_password = 'admin123';

$login_query = "SELECT admin_id, username, password FROM admins WHERE username = ?";
$login_stmt = mysqli_prepare($conn, $login_query);
mysqli_stmt_bind_param($login_stmt, "s", $test_username);
mysqli_stmt_execute($login_stmt);
$login_result = mysqli_stmt_get_result($login_stmt);

if (mysqli_num_rows($login_result) == 1) {
    $admin = mysqli_fetch_assoc($login_result);
    echo "<p>Found admin user with username: " . htmlspecialchars($admin['username']) . "</p>";
    
    // Verify password
    if (password_verify($test_password, $admin['password'])) {
        echo "<p class='success'>✓ Password verification successful!</p>";
        echo "<p>The login credentials are correct. You should be able to log in with:<br>Username: admin<br>Password: admin123</p>";
    } else {
        echo "<p class='error'>✗ Password verification failed.</p>";
        echo "<p>The stored password hash does not match 'admin123'.</p>";
        
        // Update the password
        echo "<h4>Updating the admin password:</h4>";
        $new_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $update_query = "UPDATE admins SET password = ? WHERE username = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $new_password_hash, $test_username);
        
        if (mysqli_stmt_execute($update_stmt)) {
            echo "<p class='success'>✓ Password updated successfully!</p>";
            echo "<p>Please try logging in again with:<br>Username: admin<br>Password: admin123</p>";
        } else {
            echo "<p class='error'>✗ Failed to update password: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Admin user 'admin' not found.</p>";
    
    // Create the admin user
    echo "<h4>Creating the admin user:</h4>";
    $default_username = 'admin';
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $default_email = 'admin@adventuretravel.com';
    $default_fullname = 'Administrator';
    
    $insert_query = "INSERT INTO admins (username, password, email, full_name) 
                    VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "ssss", $default_username, $default_password, $default_email, $default_fullname);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p class='success'>✓ Created admin user successfully!</p>";
        echo "<p>Please try logging in with:<br>Username: admin<br>Password: admin123</p>";
    } else {
        echo "<p class='error'>✗ Failed to create admin user: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='admin_login.php' class='action-btn'>Go to Admin Login</a></p>";
?>
</body>
</html> 