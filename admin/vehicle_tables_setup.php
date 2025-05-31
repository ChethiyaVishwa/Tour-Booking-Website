<?php
// Include database configuration
require_once 'config.php';

// Check if admin is logged in
require_admin_login();

// Create vehicle_sub_images table
$sql_sub_images = "CREATE TABLE IF NOT EXISTS vehicle_sub_images (
    sub_image_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
)";

// Create vehicle_sub_details table
$sql_sub_details = "CREATE TABLE IF NOT EXISTS vehicle_sub_details (
    detail_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT(11) NOT NULL,
    header VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    icon VARCHAR(100),
    order_num INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
)";

// Create vehicle_features table (includes/excludes)
$sql_features = "CREATE TABLE IF NOT EXISTS vehicle_features (
    feature_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT(11) NOT NULL,
    feature_description TEXT NOT NULL,
    is_included TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
)";

// Execute the queries
$success = true;
$error_message = '';

if (!mysqli_query($conn, $sql_sub_images)) {
    $success = false;
    $error_message .= "Error creating vehicle_sub_images table: " . mysqli_error($conn) . "<br>";
}

if (!mysqli_query($conn, $sql_sub_details)) {
    $success = false;
    $error_message .= "Error creating vehicle_sub_details table: " . mysqli_error($conn) . "<br>";
}

if (!mysqli_query($conn, $sql_features)) {
    $success = false;
    $error_message .= "Error creating vehicle_features table: " . mysqli_error($conn) . "<br>";
}

// Display result
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Tables Setup - Adventure Travel.lk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #176c65;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #176c65;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #124d47;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Vehicle Tables Setup</h1>
        
        <?php if ($success): ?>
            <div class="success">
                <p><strong>Success!</strong> All vehicle detail tables have been created successfully.</p>
            </div>
        <?php else: ?>
            <div class="error">
                <p><strong>Error!</strong> There was a problem creating the vehicle detail tables.</p>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <a href="admin.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html> 