<?php
// Script to set up chat functionality database tables
require_once 'config.php';

// Function to check if tables exist
function checkTablesExist() {
    global $conn;
    
    $tables = ['chat_messages', 'chat_sessions'];
    $existing = 0;
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $existing++;
        }
    }
    
    return $existing === count($tables);
}

// Check if tables already exist
if (checkTablesExist()) {
    echo '<div style="padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px;">
            <h3>Chat tables already exist</h3>
            <p>The chat_messages and chat_sessions tables are already set up in your database.</p>
            <a href="admin.php" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background-color: #155724; color: white; text-decoration: none; border-radius: 4px;">Return to Admin Dashboard</a>
          </div>';
    exit;
}

// Execute SQL from the chat_tables.sql file
$sql = file_get_contents('chat_tables.sql');

// Split statements by delimiter
if (empty($sql)) {
    echo '<div style="padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;">
            <h3>Error</h3>
            <p>Could not read the SQL file. Please make sure chat_tables.sql exists in the admin directory.</p>
            <a href="admin.php" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background-color: #721c24; color: white; text-decoration: none; border-radius: 4px;">Return to Admin Dashboard</a>
          </div>';
    exit;
}

// Create chat_messages table
$messages_table = "CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    KEY (user_id),
    KEY (is_admin),
    KEY (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$messages_result = mysqli_query($conn, $messages_table);

// Create chat_sessions table
$sessions_table = "CREATE TABLE IF NOT EXISTS chat_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id),
    KEY (status),
    KEY (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sessions_result = mysqli_query($conn, $sessions_table);

// Create stored procedure
$procedure = "DROP PROCEDURE IF EXISTS mark_messages_read;
DELIMITER //
CREATE PROCEDURE mark_messages_read(IN p_user_id INT, IN p_is_admin TINYINT)
BEGIN
    UPDATE chat_messages 
    SET is_read = 1 
    WHERE user_id = p_user_id AND is_admin != p_is_admin;
END //
DELIMITER ;";

$procedure_result = true;
$procedure_parts = explode('DELIMITER //', $procedure);
if (count($procedure_parts) > 1) {
    $proc_body = explode('DELIMITER ;', $procedure_parts[1])[0];
    $drop_result = mysqli_query($conn, "DROP PROCEDURE IF EXISTS mark_messages_read");
    $procedure_result = mysqli_query($conn, "CREATE PROCEDURE mark_messages_read(IN p_user_id INT, IN p_is_admin TINYINT)
    BEGIN
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE user_id = p_user_id AND is_admin != p_is_admin;
    END");
}

// Check if all operations succeeded
if ($messages_result && $sessions_result && $procedure_result) {
    echo '<div style="padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px;">
            <h3>Chat Setup Complete</h3>
            <p>Chat tables and procedures have been successfully created in your database.</p>
            <a href="admin.php" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background-color: #155724; color: white; text-decoration: none; border-radius: 4px;">Return to Admin Dashboard</a>
          </div>';
} else {
    echo '<div style="padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;">
            <h3>Setup Failed</h3>
            <p>There was an error creating the chat tables in your database.</p>
            <p>Error: ' . mysqli_error($conn) . '</p>
            <a href="admin.php" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background-color: #721c24; color: white; text-decoration: none; border-radius: 4px;">Return to Admin Dashboard</a>
          </div>';
}
?> 