<?php
// Script to fix auto-increment issue in chat_sessions table
require_once 'config.php';

echo "Starting chat_sessions auto-increment fix...<br>";

// First get current data
$select_query = "SELECT * FROM chat_sessions ORDER BY session_id";
$result = mysqli_query($conn, $select_query);

if (!$result) {
    die("Error fetching sessions: " . mysqli_error($conn));
}

$sessions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sessions[] = $row;
}

echo "Found " . count($sessions) . " existing chat sessions<br>";

// Drop and recreate the table to reset auto-increment
$drop_table = "DROP TABLE IF EXISTS chat_sessions";
if (!mysqli_query($conn, $drop_table)) {
    die("Error dropping table: " . mysqli_error($conn));
}

echo "Table dropped successfully<br>";

// Recreate with auto_increment starting at 10
$create_table = "CREATE TABLE chat_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id),
    KEY (status),
    KEY (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=10";

if (!mysqli_query($conn, $create_table)) {
    die("Error recreating table: " . mysqli_error($conn));
}

echo "Table recreated successfully with AUTO_INCREMENT=10<br>";

// Reinsert the existing data with higher IDs
if (count($sessions) > 0) {
    foreach ($sessions as $session) {
        $user_id = (int)$session['user_id'];
        $status = mysqli_real_escape_string($conn, $session['status']);
        $created_at = mysqli_real_escape_string($conn, $session['created_at']);
        $updated_at = mysqli_real_escape_string($conn, $session['updated_at']);
        
        $insert = "INSERT INTO chat_sessions (user_id, status, created_at, updated_at) 
                   VALUES ($user_id, '$status', '$created_at', '$updated_at')";
        
        if (!mysqli_query($conn, $insert)) {
            echo "Error reinserting session for user $user_id: " . mysqli_error($conn) . "<br>";
        }
    }
    
    echo "Sessions reinserted successfully<br>";
} else {
    echo "No existing sessions to reinsert<br>";
}

// Verify the fix
$verify_query = "SHOW TABLE STATUS LIKE 'chat_sessions'";
$verify_result = mysqli_query($conn, $verify_query);

if ($verify_result && $row = mysqli_fetch_assoc($verify_result)) {
    echo "Current AUTO_INCREMENT value: " . $row['Auto_increment'] . "<br>";
} else {
    echo "Error verifying AUTO_INCREMENT: " . mysqli_error($conn) . "<br>";
}

echo "Fix completed successfully!";
?> 