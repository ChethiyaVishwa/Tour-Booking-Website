<?php
// Update script for chat_messages table to add reply and edit functionality

// Include database configuration
require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to run SQL queries safely
function run_query($conn, $query, $description) {
    echo "<p>Attempting to $description...</p>";
    
    if (mysqli_query($conn, $query)) {
        echo "<p style='color:green'>Success: $description</p>";
        return true;
    } else {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
        return false;
    }
}

echo "<html><head><title>Update Chat Database</title>";
echo "<style>
    body { 
        font-family: Arial, sans-serif; 
        line-height: 1.6; 
        margin: 20px; 
        padding: 20px; 
        background-color: #f5f5f5;
    }
    h1 { color: #333; }
    .container { 
        max-width: 800px; 
        margin: 0 auto; 
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .success { color: green; }
    .error { color: red; }
    .code { 
        background-color: #f4f4f4; 
        padding: 10px; 
        border-left: 3px solid #ccc;
        font-family: monospace;
        white-space: pre-wrap;
    }
</style>";
echo "</head><body><div class='container'>";
echo "<h1>Adventure Travel.lk - Chat Database Update</h1>";

// Check connection
if (mysqli_connect_errno()) {
    echo "<p class='error'>Failed to connect to MySQL: " . mysqli_connect_error() . "</p>";
    exit();
}

echo "<p>Connected to database successfully.</p>";

// Check if reply_to column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM chat_messages LIKE 'reply_to'");
$column_exists = mysqli_num_rows($result) > 0;

if (!$column_exists) {
    // Add reply_to column
    $query = "ALTER TABLE chat_messages 
              ADD COLUMN reply_to INT NULL";
    
    run_query($conn, $query, "add reply_to column");
    
    // Add foreign key constraint
    $query = "ALTER TABLE chat_messages
              ADD CONSTRAINT fk_reply_to 
              FOREIGN KEY (reply_to) 
              REFERENCES chat_messages(message_id) 
              ON DELETE SET NULL";
    
    run_query($conn, $query, "add foreign key constraint for reply_to");
} else {
    echo "<p>The reply_to column already exists.</p>";
}

// Check if edited column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM chat_messages LIKE 'edited'");
$column_exists = mysqli_num_rows($result) > 0;

if (!$column_exists) {
    // Add edited column
    $query = "ALTER TABLE chat_messages 
              ADD COLUMN edited TINYINT(1) NOT NULL DEFAULT 0";
    
    run_query($conn, $query, "add edited column");
} else {
    echo "<p>The edited column already exists.</p>";
}

// Check if updated_at column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM chat_messages LIKE 'updated_at'");
$column_exists = mysqli_num_rows($result) > 0;

if (!$column_exists) {
    // Add updated_at column
    $query = "ALTER TABLE chat_messages 
              ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL
              ON UPDATE CURRENT_TIMESTAMP";
    
    run_query($conn, $query, "add updated_at column");
} else {
    echo "<p>The updated_at column already exists.</p>";
}

echo "<h2>Instructions</h2>";
echo "<p>The database has been updated to support the following new chat features:</p>";
echo "<ul>
    <li>Reply to messages</li>
    <li>Edit messages</li>
    <li>Delete messages</li>
</ul>";

echo "<h2>Required SQL Changes</h2>";
echo "<div class='code'>-- SQL changes applied:
ALTER TABLE chat_messages 
ADD COLUMN reply_to INT NULL;

ALTER TABLE chat_messages
ADD CONSTRAINT fk_reply_to 
    FOREIGN KEY (reply_to) 
    REFERENCES chat_messages(message_id) 
    ON DELETE SET NULL;

ALTER TABLE chat_messages
ADD COLUMN edited TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE chat_messages
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL
    ON UPDATE CURRENT_TIMESTAMP;
</div>";

echo "<h2>Next Steps</h2>";
echo "<p>The database has been updated successfully. You can now use the new chat features:</p>";
echo "<ol>
    <li>Reply to messages by clicking the reply icon</li>
    <li>Edit your messages by clicking the edit icon</li>
    <li>Delete messages by clicking the delete icon</li>
</ol>";

echo "<p><a href='../index.php'>Return to homepage</a></p>";

// Close connection
mysqli_close($conn);

echo "</div></body></html>";
?> 