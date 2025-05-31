<?php
// Chat functions for Adventure Travel.lk
require_once 'config.php';

/**
 * Get all chat messages for a specific user
 * 
 * @param int $user_id User ID
 * @return array Array of chat messages
 */
function get_user_messages($user_id) {
    global $conn;
    
    // First, get all messages
    $query = "SELECT m1.*, 
              m2.message as replied_to_content 
              FROM chat_messages m1 
              LEFT JOIN chat_messages m2 ON m1.reply_to = m2.message_id 
              WHERE m1.user_id = ? 
              ORDER BY m1.created_at ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Prepare failed in get_user_messages: " . mysqli_error($conn));
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        error_log("Execute failed in get_user_messages: " . mysqli_stmt_error($stmt));
        return [];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $messages = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    } else {
        error_log("Get result failed in get_user_messages: " . mysqli_stmt_error($stmt));
    }
    
    return $messages;
}

/**
 * Get unread message count for a user or admin
 * 
 * @param int $user_id User ID (0 for admin to get all unread)
 * @param bool $is_admin Whether checking for admin
 * @return int Number of unread messages
 */
function get_unread_count($user_id, $is_admin = false) {
    global $conn;
    
    if ($is_admin && $user_id === 0) {
        // For admin dashboard - count of all unread messages from users
        $query = "SELECT COUNT(*) as count FROM chat_messages 
                  WHERE is_admin = 0 AND is_read = 0";
        $stmt = mysqli_prepare($conn, $query);
    } else if ($is_admin) {
        // For specific user view by admin
        $query = "SELECT COUNT(*) as count FROM chat_messages 
                  WHERE user_id = ? AND is_admin = 0 AND is_read = 0";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        // For user - count of unread admin messages
        $query = "SELECT COUNT(*) as count FROM chat_messages 
                  WHERE user_id = ? AND is_admin = 1 AND is_read = 0";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return (int)$row['count'];
}

/**
 * Add a new chat message
 * 
 * @param int $user_id User ID
 * @param string $message Message content
 * @param int|bool $is_admin Whether message is from admin
 * @param int|null $reply_to ID of the message being replied to
 * @return bool Success status
 */
function add_message($user_id, $message, $is_admin = 0, $reply_to = null) {
    global $conn;
    
    // Convert is_admin to integer (0 or 1)
    $is_admin_int = $is_admin ? 1 : 0;
    
    // Debug logging
    error_log("Adding message: user_id=$user_id, message=\"$message\", is_admin=$is_admin_int, reply_to=" . ($reply_to ? $reply_to : "null"));
    
    // Ensure there's an active chat session
    ensure_chat_session($user_id);
    
    if ($reply_to) {
        $query = "INSERT INTO chat_messages (user_id, message, is_admin, reply_to) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            error_log("Error preparing add_message statement: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "isii", $user_id, $message, $is_admin_int, $reply_to);
    } else {
        $query = "INSERT INTO chat_messages (user_id, message, is_admin) 
                  VALUES (?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            error_log("Error preparing add_message statement: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $message, $is_admin_int);
    }
    
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        error_log("Error executing add_message: " . mysqli_stmt_error($stmt));
    } else {
        error_log("Message added successfully: user_id=$user_id, is_admin=$is_admin_int");
    }
    
    return $result;
}

/**
 * Mark messages as read
 * 
 * @param int $user_id User ID
 * @param bool $is_admin Whether marking as admin
 * @return bool Success status
 */
function mark_as_read($user_id, $is_admin = false) {
    global $conn;
    
    $is_admin_int = $is_admin ? 1 : 0;
    
    // Instead of calling the stored procedure, we'll use a direct SQL update query
    if ($is_admin) {
        // If admin is marking messages as read, we mark all user messages as read
        $query = "UPDATE chat_messages SET is_read = 1 
                  WHERE user_id = ? AND is_admin = 0 AND is_read = 0";
    } else {
        // If user is marking messages as read, mark all admin messages as read
        $query = "UPDATE chat_messages SET is_read = 1 
                  WHERE user_id = ? AND is_admin = 1 AND is_read = 0";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Error preparing mark_as_read statement: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        error_log("Error executing mark_as_read: " . mysqli_stmt_error($stmt));
    }
    
    return $result;
}

/**
 * Fix auto-increment for chat_sessions table
 * This is a utility function to address issues with session_id auto-increment
 * 
 * @return void
 */
function fix_chat_sessions_auto_increment() {
    global $conn;
    
    // Get the maximum session_id
    $max_query = "SELECT MAX(session_id) as max_id FROM chat_sessions";
    $max_result = mysqli_query($conn, $max_query);
    $max_id = 1;
    
    if ($max_result && $row = mysqli_fetch_assoc($max_result)) {
        $max_id = (int)$row['max_id'];
        // If we're stuck at 0 or 1, set it higher to jump past the problematic values
        if ($max_id <= 1) {
            $max_id = 10;
        }
    }
    
    // Reset auto_increment to a value higher than current max
    $reset_query = "ALTER TABLE chat_sessions AUTO_INCREMENT = " . ($max_id + 1);
    $result = mysqli_query($conn, $reset_query);
    
    if (!$result) {
        error_log("Failed to fix auto_increment: " . mysqli_error($conn));
    } else {
        error_log("Auto_increment fixed, set to " . ($max_id + 1));
    }
}

/**
 * Ensure an active chat session exists for a user
 * 
 * @param int $user_id User ID
 * @return bool Success status
 */
function ensure_chat_session($user_id) {
    global $conn;
    
    // Fix auto-increment issue first
    fix_chat_sessions_auto_increment();
    
    // Check if session exists
    $check_query = "SELECT session_id FROM chat_sessions 
                    WHERE user_id = ? AND status = 'active'";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    $session_exists = mysqli_stmt_num_rows($check_stmt) > 0;
    
    // Close the statement before proceeding
    mysqli_stmt_close($check_stmt);
    
    if ($session_exists) {
        // Session exists, update timestamp
        $update_query = "UPDATE chat_sessions 
                         SET updated_at = CURRENT_TIMESTAMP 
                         WHERE user_id = ? AND status = 'active'";
        $update_stmt = mysqli_prepare($conn, $update_query);
        if (!$update_stmt) {
            error_log("Error preparing update statement: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($update_stmt, "i", $user_id);
        $result = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        return $result;
    } else {
        // Delete any existing sessions for this user (active or closed)
        $delete_query = "DELETE FROM chat_sessions WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        if ($delete_stmt) {
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
            
            // Fix auto-increment again after deletion
            fix_chat_sessions_auto_increment();
        }
        
        // Create new session with a simple insert
        $insert_query = "INSERT INTO chat_sessions (user_id, status) 
                         VALUES (?, 'active')";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        if (!$insert_stmt) {
            error_log("Error preparing insert statement: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($insert_stmt, "i", $user_id);
        $result = mysqli_stmt_execute($insert_stmt);
        
        if (!$result) {
            error_log("Error inserting chat session: " . mysqli_stmt_error($insert_stmt));
        }
        
        mysqli_stmt_close($insert_stmt);
        return $result;
    }
}

/**
 * Get all active chat sessions for admin view
 * 
 * @return array Array of chat sessions
 */
function get_active_chat_sessions() {
    global $conn;
    
    // First check if there are any messages at all
    $check_query = "SELECT COUNT(*) as count FROM chat_messages";
    $check_result = mysqli_query($conn, $check_query);
    if ($check_result) {
        $count_row = mysqli_fetch_assoc($check_result);
        if ($count_row['count'] == 0) {
            error_log("No chat messages found in the database");
            return [];
        }
    }
    
    // Get all distinct users who have sent messages
    $users_query = "SELECT DISTINCT cm.user_id, u.full_name, u.username,
                   (SELECT COUNT(*) FROM chat_messages 
                    WHERE user_id = cm.user_id AND is_admin = 0 AND is_read = 0) as unread_count,
                   (SELECT message FROM chat_messages 
                    WHERE user_id = cm.user_id 
                    ORDER BY created_at DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM chat_messages 
                    WHERE user_id = cm.user_id 
                    ORDER BY created_at DESC LIMIT 1) as last_activity
                   FROM chat_messages cm
                   JOIN users u ON cm.user_id = u.user_id
                   GROUP BY cm.user_id
                   ORDER BY last_activity DESC";
    
    $users_result = mysqli_query($conn, $users_query);
    if (!$users_result) {
        error_log("Error getting active chat users: " . mysqli_error($conn));
        
        // Try fallback query if the JOIN fails
        $fallback_query = "SELECT DISTINCT user_id FROM chat_messages ORDER BY created_at DESC";
        $fallback_result = mysqli_query($conn, $fallback_query);
        
        if (!$fallback_result) {
            error_log("Fallback query also failed: " . mysqli_error($conn));
            return [];
        }
        
        $sessions = [];
        while ($row = mysqli_fetch_assoc($fallback_result)) {
            $user_id = $row['user_id'];
            
            // Get user details
            $user_query = "SELECT * FROM users WHERE user_id = $user_id";
            $user_result = mysqli_query($conn, $user_query);
            
            if ($user_result && mysqli_num_rows($user_result) > 0) {
                $user_data = mysqli_fetch_assoc($user_result);
                
                // Get unread count
                $unread_query = "SELECT COUNT(*) as count FROM chat_messages 
                                 WHERE user_id = $user_id AND is_admin = 0 AND is_read = 0";
                $unread_result = mysqli_query($conn, $unread_query);
                $unread_count = 0;
                
                if ($unread_result) {
                    $unread_data = mysqli_fetch_assoc($unread_result);
                    $unread_count = $unread_data['count'];
                }
                
                // Get last message
                $message_query = "SELECT message, created_at FROM chat_messages 
                                  WHERE user_id = $user_id 
                                  ORDER BY created_at DESC LIMIT 1";
                $message_result = mysqli_query($conn, $message_query);
                $last_message = '';
                $last_activity = '';
                
                if ($message_result && mysqli_num_rows($message_result) > 0) {
                    $message_data = mysqli_fetch_assoc($message_result);
                    $last_message = $message_data['message'];
                    $last_activity = $message_data['created_at'];
                }
                
                $sessions[] = [
                    'user_id' => $user_id,
                    'full_name' => $user_data['full_name'] ?? 'Unknown User',
                    'username' => $user_data['username'] ?? 'unknown',
                    'unread_count' => $unread_count,
                    'last_message' => $last_message,
                    'updated_at' => $last_activity
                ];
            }
        }
        
        // Sort by most recent activity
        usort($sessions, function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
        
        return $sessions;
    }
    
    $sessions = [];
    
    while ($row = mysqli_fetch_assoc($users_result)) {
        $sessions[] = [
            'user_id' => $row['user_id'],
            'full_name' => $row['full_name'] ?? 'Unknown User',
            'username' => $row['username'] ?? 'unknown',
            'unread_count' => $row['unread_count'] ?? 0,
            'last_message' => $row['last_message'] ?? '',
            'updated_at' => $row['last_activity'] ?? date('Y-m-d H:i:s')
        ];
    }
    
    return $sessions;
}

/**
 * Close a chat session
 * 
 * @param int $user_id User ID
 * @return bool Success status
 */
function close_chat_session($user_id) {
    global $conn;
    
    $query = "UPDATE chat_sessions 
              SET status = 'closed' 
              WHERE user_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Error preparing close_chat_session statement: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        error_log("Error closing chat session: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Get recent active sessions with unread messages for admin dashboard
 * 
 * @param int $limit Number of sessions to return
 * @return array Array of recent chat sessions
 */
function get_recent_chat_sessions($limit = 5) {
    global $conn;
    
    $query = "SELECT cs.*, u.full_name, u.username,
              (SELECT COUNT(*) FROM chat_messages 
               WHERE user_id = cs.user_id AND is_admin = 0 AND is_read = 0) as unread_count
              FROM chat_sessions cs
              JOIN users u ON cs.user_id = u.user_id
              WHERE cs.status = 'active'
              AND EXISTS (SELECT 1 FROM chat_messages 
                         WHERE user_id = cs.user_id AND is_admin = 0 AND is_read = 0)
              ORDER BY cs.updated_at DESC
              LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $sessions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $sessions[] = $row;
    }
    
    return $sessions;
}

/**
 * Send email notification to user
 * 
 * @param string $email User's email address
 * @param string $subject Email subject
 * @param string $message Email message content
 * @return bool Success status
 */
function send_email_notification($email, $subject, $message) {
    // Set headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Adventure Travel <admin@adventuretravel.lk>" . "\r\n";
    
    // Get site URL
    $site_url = get_site_url();
    $login_url = $site_url . "/login.php";
    
    // Create HTML email message
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f9f9f9;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #ffffff;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #176c65;
                color: white;
                padding: 10px 20px;
                border-radius: 5px 5px 0 0;
                text-align: center;
            }
            .content {
                padding: 20px;
            }
            .footer {
                background-color: #f5f5f5;
                padding: 10px 20px;
                border-radius: 0 0 5px 5px;
                font-size: 12px;
                text-align: center;
                color: #666;
            }
            .login-prompt {
                background-color: #f0f8ff;
                color: #333333;
                padding: 20px;
                text-align: center;
                margin: 20px 0;
                border-radius: 5px;
                border-left: 4px solid #65ffc1;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .login-button {
                display: inline-block;
                background: linear-gradient(135deg, #176c65, #65ffc1);
                color: #ffffff;
                font-weight: bold;
                text-decoration: none;
                padding: 12px 30px;
                margin: 15px 0;
                border-radius: 50px;
                font-size: 16px;
                box-shadow: 0 4px 10px rgba(23, 108, 101, 0.3);
                transition: transform 0.3s ease;
            }
            .login-button:hover {
                background: linear-gradient(135deg, #155c55, #55edb1);
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(23, 108, 101, 0.4);
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Adventure Travel.lk</h2>
            </div>
            <div class='content'>
                " . nl2br(htmlspecialchars($message)) . "
                
                <div class='login-prompt'>
                    <p>Please login to your account to view the full conversation and reply.</p>
                    <a href='https://adventuretravels.wuaze.com' class='login-button'>Login Now</a>
                </div>
            </div>
            <div class='footer'>
                <p>This is an automated message from Adventure Travel.lk</p>
                <p>Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email
    $result = mail($email, $subject, $html_message, $headers);
    
    if (!$result) {
        error_log("Failed to send email notification to: $email");
    } else {
        error_log("Email notification sent successfully to: $email");
    }
    
    return $result;
}

/**
 * Get site URL (helper function for email links)
 *
 * @return string Site URL
 */
function get_site_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    
    // Get the directory path
    $path = dirname($_SERVER['PHP_SELF']);
    $path = str_replace('/admin', '', $path); // Remove /admin from path
    
    // Ensure path has trailing slash
    if (substr($path, -1) !== '/') {
        $path .= '/';
    }
    
    return $protocol . $domain . $path;
}

/**
 * Get a specific message by ID
 * 
 * @param int $message_id Message ID
 * @return array|null Message data or null if not found
 */
function get_message_by_id($message_id) {
    global $conn;
    
    $query = "SELECT * FROM chat_messages WHERE message_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Prepare failed in get_message_by_id: " . mysqli_error($conn));
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        error_log("Execute failed in get_message_by_id: " . mysqli_stmt_error($stmt));
        return null;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row;
    }
    
    return null;
}

/**
 * Edit an existing chat message
 * 
 * @param int $message_id Message ID
 * @param string $new_content New message content
 * @return bool Success status
 */
function edit_message($message_id, $new_content) {
    global $conn;
    
    // Verify the message exists first
    $message = get_message_by_id($message_id);
    if (!$message) {
        error_log("Message not found for editing: message_id=$message_id");
        return false;
    }
    
    // Check if the message is older than 10 minutes
    $message_time = strtotime($message['created_at']);
    $current_time = time();
    $time_diff_minutes = ($current_time - $message_time) / 60;
    
    if ($time_diff_minutes > 10 && !is_admin_user()) {
        error_log("Message edit rejected - older than 10 minutes: message_id=$message_id");
        return false;
    }
    
    $query = "UPDATE chat_messages 
              SET message = ?, edited = 1, updated_at = CURRENT_TIMESTAMP 
              WHERE message_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Prepare failed in edit_message: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "si", $new_content, $message_id);
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        error_log("Execute failed in edit_message: " . mysqli_stmt_error($stmt));
        return false;
    }
    
    return true;
}

/**
 * Delete a chat message
 * 
 * @param int $message_id Message ID
 * @return bool Success status
 */
function delete_message($message_id) {
    global $conn;
    
    // Verify the message exists first
    $message = get_message_by_id($message_id);
    if (!$message) {
        error_log("Message not found for deletion: message_id=$message_id");
        return false;
    }
    
    // Check if the message is older than 10 minutes
    $message_time = strtotime($message['created_at']);
    $current_time = time();
    $time_diff_minutes = ($current_time - $message_time) / 60;
    
    if ($time_diff_minutes > 10 && !is_admin_user()) {
        error_log("Message delete rejected - older than 10 minutes: message_id=$message_id");
        return false;
    }
    
    // First, update any messages that reply to this one to remove the reference
    $update_query = "UPDATE chat_messages SET reply_to = NULL WHERE reply_to = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "i", $message_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
    
    // Now delete the message
    $query = "DELETE FROM chat_messages WHERE message_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Prepare failed in delete_message: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        error_log("Execute failed in delete_message: " . mysqli_stmt_error($stmt));
        return false;
    }
    
    return true;
}

/**
 * Check if current user is an admin
 *
 * @return bool True if admin, false otherwise
 */
function is_admin_user() {
    // Check if this is an admin session
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        return true;
    }
    return false;
}
?> 