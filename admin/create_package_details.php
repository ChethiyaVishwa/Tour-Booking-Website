<?php
// Include database configuration
require_once 'config.php';

// Turn off all error reporting for clean output
error_reporting(0);
ini_set('display_errors', 0);

// Check if auto-fix is requested or this is a direct access
$auto_fix = isset($_GET['auto_fix']) && $_GET['auto_fix'] == '1';
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;

// Initialize status variables
$success = false;
$message = '';
$table_created = false;
$columns_fixed = false;
$data_added = false;

// Check if the package_details table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'package_details'");
$table_exists = mysqli_num_rows($table_check) > 0;

if (!$table_exists) {
    // Create the package_details table
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `package_details` (
      `detail_id` INT AUTO_INCREMENT PRIMARY KEY,
      `package_id` INT NOT NULL,
      `detail_type` ENUM('image', 'feature', 'itinerary', 'inclusion', 'exclusion') NOT NULL,
      `detail_value` TEXT NOT NULL,
      `detail_order` INT DEFAULT 0,
      FOREIGN KEY (`package_id`) REFERENCES `packages`(`package_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $table_created = mysqli_query($conn, $create_table_query);
    
    // If table creation succeeded, add sample data
    if ($table_created) {
        $message .= "Created the 'package_details' table successfully.\n";
        
        // Get all existing packages to add details for
        $packages_query = mysqli_query($conn, "SELECT package_id FROM packages");
        while ($package = mysqli_fetch_assoc($packages_query)) {
            $pid = $package['package_id'];
            
            // Insert basic details for each package
            $sample_data_query = "
            INSERT INTO package_details (package_id, detail_type, detail_value, detail_order) VALUES
            ($pid, 'inclusion', 'Accommodation', 1),
            ($pid, 'inclusion', 'Transportation', 2),
            ($pid, 'inclusion', 'Guide service', 3),
            ($pid, 'exclusion', 'Personal expenses', 1),
            ($pid, 'exclusion', 'Travel insurance', 2),
            ($pid, 'itinerary', 'Day 1: Departure and arrival', 1),
            ($pid, 'itinerary', 'Day 2: Sightseeing tour', 2);";
            
            if (mysqli_query($conn, $sample_data_query)) {
                $data_added = true;
            }
        }
        
        if ($data_added) {
            $message .= "Added basic details for all packages.\n";
            $success = true;
        }
    } else {
        $message .= "Failed to create the 'package_details' table: " . mysqli_error($conn) . "\n";
    }
} else {
    $message .= "The 'package_details' table already exists.\n";
    
    // Check if the table has the required columns
    $columns_query = mysqli_query($conn, "SHOW COLUMNS FROM package_details");
    $column_names = [];
    while ($column = mysqli_fetch_assoc($columns_query)) {
        $column_names[] = $column['Field'];
    }
    
    // Check if all required columns exist
    $required_columns = ['detail_id', 'package_id', 'detail_type', 'detail_value', 'detail_order'];
    $missing_columns = array_diff($required_columns, $column_names);
    
    if (empty($missing_columns)) {
        $message .= "All required columns exist in the table.\n";
        $columns_fixed = true;
    } else {
        // Add missing columns
        foreach ($missing_columns as $column) {
            $column_def = "";
            switch ($column) {
                case 'detail_id':
                    $column_def = "ADD `detail_id` INT AUTO_INCREMENT PRIMARY KEY";
                    break;
                case 'package_id':
                    $column_def = "ADD `package_id` INT NOT NULL, ADD FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE";
                    break;
                case 'detail_type':
                    $column_def = "ADD `detail_type` ENUM('image', 'feature', 'itinerary', 'inclusion', 'exclusion') NOT NULL";
                    break;
                case 'detail_value':
                    $column_def = "ADD `detail_value` TEXT NOT NULL";
                    break;
                case 'detail_order':
                    $column_def = "ADD `detail_order` INT DEFAULT 0";
                    break;
            }
            
            if (!empty($column_def)) {
                $alter_query = "ALTER TABLE package_details $column_def";
                if (mysqli_query($conn, $alter_query)) {
                    $message .= "Added column '$column' to the table.\n";
                    $columns_fixed = true;
                } else {
                    $message .= "Failed to add column '$column': " . mysqli_error($conn) . "\n";
                }
            }
        }
    }
    
    // Check if there are any records in the table
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM package_details");
    $count_result = mysqli_fetch_assoc($count_query);
    $record_count = $count_result['count'];
    
    if ($record_count == 0) {
        $message .= "The table is empty. Adding sample data...\n";
        
        // Get all existing packages to add details for
        $packages_query = mysqli_query($conn, "SELECT package_id FROM packages");
        while ($package = mysqli_fetch_assoc($packages_query)) {
            $pid = $package['package_id'];
            
            // Insert basic details for each package
            $sample_data_query = "
            INSERT INTO package_details (package_id, detail_type, detail_value, detail_order) VALUES
            ($pid, 'inclusion', 'Accommodation', 1),
            ($pid, 'inclusion', 'Transportation', 2),
            ($pid, 'inclusion', 'Guide service', 3),
            ($pid, 'exclusion', 'Personal expenses', 1),
            ($pid, 'exclusion', 'Travel insurance', 2),
            ($pid, 'itinerary', 'Day 1: Departure and arrival', 1),
            ($pid, 'itinerary', 'Day 2: Sightseeing tour', 2);";
            
            if (mysqli_query($conn, $sample_data_query)) {
                $data_added = true;
            }
        }
        
        if ($data_added) {
            $message .= "Added basic details for all packages.\n";
        }
    } else {
        $message .= "The table already has $record_count records.\n";
        $data_added = true;
    }
}

// Set success flag if all checks pass
$success = ($table_exists || $table_created) && ($columns_fixed || empty($missing_columns)) && $data_added;

// If this is an auto-fix, redirect back to admin with status
if ($auto_fix) {
    $redirect_url = 'admin.php?db_fix=' . ($success ? 'success' : 'error');
    
    // If a specific package ID was provided, redirect to view that package
    if ($package_id) {
        $redirect_url .= '&view_package=' . $package_id;
    }
    
    header("Location: $redirect_url");
    exit;
}

// Set proper content type for browser display if this is a direct access
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Details Table Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        h1, h2 {
            color: #176c65;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #176c65;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        .auto-fix {
            background-color: #28a745;
        }
        .info-box {
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Package Details Table Setup</h1>
    
    <div class="info-box">
        <p>This tool checks and fixes the database structure needed for the package details view.</p>
        <p>Current status: <span class="<?php echo $success ? 'success' : 'error'; ?>"><?php echo $success ? 'All checks passed' : 'Issues detected'; ?></span></p>
        
        <?php if (!$success): ?>
        <p>Click the button below to automatically fix all issues:</p>
        <a href="create_package_details.php?auto_fix=1" class="btn auto-fix">Fix Issues Automatically</a>
        <?php else: ?>
        <p class="success">✓ Database is properly set up. Package details should now display correctly.</p>
        <?php endif; ?>
    </div>
    
    <h2>Diagnostic Results</h2>
    <pre><?php echo $message; ?></pre>
    
    <h2>Next Steps</h2>
    <p>
        Now that the package_details table has been checked, you can:
    </p>
    <ul>
        <li><a href="admin.php" class="btn">Return to Admin Dashboard</a></li>
        <li>Try viewing package details again to see if the error is resolved</li>
    </ul>
    
    <h2>Database Connectivity Information</h2>
    <pre>
Host: <?php echo $host; ?>
Database: <?php echo $database; ?>
Connection Status: <?php echo mysqli_ping($conn) ? 'Connected' : 'Not Connected'; ?>
    </pre>
</body>
</html> 