<?php
// Include the configuration file to get database connection
require_once 'config.php';

// Get the SQL file content
$sql_file = file_get_contents('create_admins_table.sql');

// Split the SQL file into individual queries
$queries = explode(';', $sql_file);

// Execute each query
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!mysqli_query($conn, $query)) {
            echo "Error executing query: " . mysqli_error($conn) . "<br>";
            echo "Query was: " . $query . "<br><br>";
            $success = false;
        }
    }
}

if ($success) {
    echo "Admins table created successfully! <br>";
    echo "Default admin login: <br>";
    echo "Username: admin <br>";
    echo "Password: admin123 <br>";
} else {
    echo "There were errors while setting up the admins table.";
}

// Check if admins table exists and has data
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<br>Number of admin accounts: " . $row['count'];
} else {
    echo "<br>Could not check admin table: " . mysqli_error($conn);
}
?> 