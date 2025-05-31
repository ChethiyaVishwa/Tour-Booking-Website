<?php
// Include database configuration
require_once 'config.php';

// Check if admin is logged in
require_admin_login();

// Function to read SQL file
function read_sql_file($file_path) {
    $sql = file_get_contents($file_path);
    if ($sql === false) {
        return ['error' => 'Could not read SQL file'];
    }
    
    // Split into separate queries
    $queries = explode(';', $sql);
    $valid_queries = [];
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $valid_queries[] = $query;
        }
    }
    
    return $valid_queries;
}

// Check if policies table already exists
$check_table = "SHOW TABLES LIKE 'policies'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) > 0) {
    $message = "Policy tables already exist in the database.";
    $success = true;
} else {
    // Execute SQL from policy_tables.sql
    $sql_file = '../about_us/policy_tables.sql';
    $queries = read_sql_file($sql_file);
    
    if (isset($queries['error'])) {
        $message = $queries['error'];
        $success = false;
    } else {
        $error = false;
        
        // Execute each query
        foreach ($queries as $query) {
            if (!mysqli_query($conn, $query)) {
                $error = true;
                $message = "Error executing query: " . mysqli_error($conn);
                break;
            }
        }
        
        if (!$error) {
            $message = "Policy tables installed successfully.";
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Policy Tables - Adventure Travel.lk Admin</title>
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
            --danger-color: #dc3545;
            --success-color: #28a745;
        }

        body {
            background-color: rgb(68, 202, 148);
            padding: 20px;
            color: var(--dark-color);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
        }

        .result {
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .action-links {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #145a55;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Install Policy Tables</h1>
        
        <div class="result <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        
        <div class="action-links">
            <a href="manage_policies.php" class="btn">Manage Policies</a>
            <a href="admin.php" class="btn">Return to Dashboard</a>
        </div>
    </div>
</body>
</html> 