<?php
// Simple admin page to view and respond to user messages
require_once 'config.php';
require_once 'chat_functions.php';

// Check if admin is logged in
require_admin_login();

// Process message actions (edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'edit_message' && isset($_POST['message_id']) && isset($_POST['message'])) {
            $message_id = (int)$_POST['message_id'];
            $new_message = sanitize_input($_POST['message']);
            
            if (edit_message($message_id, $new_message)) {
                // Success - message will be updated on page reload
                $_SESSION['message_success'] = 'Message edited successfully';
            } else {
                $_SESSION['message_error'] = 'Failed to edit message';
            }
            
            // Redirect back to the conversation
            if (isset($_POST['user_id'])) {
                header("Location: user_messages.php?user_id=" . (int)$_POST['user_id']);
            } else {
                // Try to get user_id from the message
                $message_info = get_message_by_id($message_id);
                if ($message_info) {
                    header("Location: user_messages.php?user_id=" . $message_info['user_id']);
                } else {
                    header("Location: user_messages.php");
                }
            }
            exit;
        }
        
        if ($action === 'delete_message' && isset($_POST['message_id'])) {
            $message_id = (int)$_POST['message_id'];
            
            // Get user_id before deleting the message for redirect
            $message_info = get_message_by_id($message_id);
            $user_id = $message_info ? $message_info['user_id'] : null;
            
            if (delete_message($message_id)) {
                // Success
                $_SESSION['message_success'] = 'Message deleted successfully';
            } else {
                $_SESSION['message_error'] = 'Failed to delete message';
            }
            
            // Redirect back to the conversation
            if ($user_id) {
                header("Location: user_messages.php?user_id=" . $user_id);
            } else {
                header("Location: user_messages.php");
            }
            exit;
        }
    }
}

// Get all users who have sent messages
$users_query = "SELECT DISTINCT user_id FROM chat_messages ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);

$users = [];
if ($users_result) {
    while ($row = mysqli_fetch_assoc($users_result)) {
        $user_id = $row['user_id'];
        
        // Get user details
        $user_query = "SELECT * FROM users WHERE user_id = $user_id";
        $user_result = mysqli_query($conn, $user_query);
        
        // Get unread message count for this user
        $unread_query = "SELECT COUNT(*) as unread_count FROM chat_messages 
                         WHERE user_id = $user_id AND is_admin = 0 AND is_read = 0";
        $unread_result = mysqli_query($conn, $unread_query);
        $unread_count = 0;
        
        if ($unread_result && $unread_row = mysqli_fetch_assoc($unread_result)) {
            $unread_count = $unread_row['unread_count'];
        }
        
        if ($user_result && mysqli_num_rows($user_result) > 0) {
            $user_data = mysqli_fetch_assoc($user_result);
            $user_data['unread_count'] = $unread_count;
            $users[] = $user_data;
        } else {
            // Fallback for unknown users
            $users[] = [
                'user_id' => $user_id,
                'full_name' => 'User #' . $user_id,
                'username' => 'user' . $user_id,
                'unread_count' => $unread_count
            ];
        }
    }
}

// Sort users by unread message count (users with unread messages first)
usort($users, function($a, $b) {
    if ($a['unread_count'] != $b['unread_count']) {
        return $b['unread_count'] - $a['unread_count']; // Higher unread count first
    }
    return 0; // Keep original order for users with same unread count
});

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $user_id = (int)$_POST['user_id'];
    $message = sanitize_input($_POST['message']);
    $reply_to = isset($_POST['reply_to']) && !empty($_POST['reply_to']) ? (int)$_POST['reply_to'] : null;
    
    if (!empty($message)) {
        add_message($user_id, $message, true, $reply_to);
    }
    
    // Redirect to prevent form resubmission
    header("Location: user_messages.php?user_id=$user_id");
    exit;
}

// Get specific user messages if user_id is provided
$selected_user = null;
$messages = [];
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $selected_user_id = (int)$_GET['user_id'];
    
    // Mark messages as read
    mark_as_read($selected_user_id, true);
    
    // Get messages
    $messages = get_user_messages($selected_user_id);
    
    // Find selected user info
    foreach ($users as $user) {
        if ($user['user_id'] == $selected_user_id) {
            $selected_user = $user;
            break;
        }
    }
    
    if (!$selected_user) {
        // Get user details directly
        $user_query = "SELECT * FROM users WHERE user_id = $selected_user_id";
        $user_result = mysqli_query($conn, $user_query);
        
        if ($user_result && mysqli_num_rows($user_result) > 0) {
            $selected_user = mysqli_fetch_assoc($user_result);
        } else {
            $selected_user = [
                'user_id' => $selected_user_id,
                'full_name' => 'User #' . $selected_user_id,
                'username' => 'user' . $selected_user_id
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Chats - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --primary-light: #a29bfe;
            --secondary-color: #00cec9;
            --dark-bg: #18191a;
            --dark-bg-lighter: #242526;
            --card-bg: #2a2b2d;
            --light-text: #f5f6fa;
            --gray-text: #dfe6e9;
            --border-color: #393a3b;
            --user-message: #0984e3;
            --admin-message: #6c5ce7;
            --hover-color: #3d3e40;
            --accent-color: #00cec9;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-bg);
            color: var(--light-text);
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: var(--dark-bg-lighter);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .page-title {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            letter-spacing: 0.5px;
            position: relative;
            padding-left: 15px;
        }
        
        .page-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 70%;
            background: var(--accent-color);
            border-radius: 2px;
        }
        
        .back-btn {
            background: var(--card-bg);
            color: var(--light-text);
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .back-btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
            color: white;
        }
        
        .chat-container {
            display: flex;
            height: calc(100vh - 70px);
            background: transparent;
            overflow: hidden;
        }
        
        .user-list {
            width: 320px;
            background-color: var(--dark-bg-lighter);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .user-list-header {
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            margin: 0.5rem;
            border-radius: 8px;
            background-color: var(--card-bg);
        }
        
        .user-item:hover {
            background-color: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-item.active {
            background-color: var(--hover-color);
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-username {
            font-size: 0.8rem;
            color: var(--gray-text);
            opacity: 0.8;
        }
        
        .chat-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--dark-bg);
        }
        
        .chat-header {
            padding: 1rem;
            background: var(--dark-bg-lighter);
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .chat-header-info {
            display: flex;
            flex-direction: column;
        }
        
        .chat-header-name {
            font-weight: 600;
            margin: 0;
            color: var(--light-text);
        }
        
        .chat-header-username {
            font-size: 0.8rem;
            opacity: 0.8;
            color: var(--gray-text);
        }
        
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background-color: var(--dark-bg);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+CjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0iIzE4MTkxYSI+PC9yZWN0Pgo8Y2lyY2xlIGN4PSIxMCIgY3k9IjEwIiByPSIwLjUiIGZpbGw9IiMyYTJiMmQiIG9wYWNpdHk9IjAuNCIvPgo8L3N2Zz4=');
            background-repeat: repeat;
        }
        
        .message-wrapper {
            display: flex;
            flex-direction: column;
            max-width: 75%;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-wrapper.user {
            align-self: flex-start;
        }
        
        .message-wrapper.admin {
            align-self: flex-end;
        }
        
        .message {
            padding: 0.75rem 1rem;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            color: white;
            position: relative;
            line-height: 1.4;
        }
        
        .message.user {
            background: linear-gradient(135deg, var(--user-message), #0072ff);
            border-bottom-left-radius: 5px;
        }
        
        .message.admin {
            background: linear-gradient(135deg, var(--admin-message), #8c7ae6);
            border-bottom-right-radius: 5px;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: var(--gray-text);
            margin-top: 0.3rem;
            align-self: flex-end;
        }
        
        .message-wrapper.user .message-time {
            align-self: flex-start;
        }
        
        .chat-input-container {
            padding: 1rem;
            background: var(--dark-bg-lighter);
            border-top: 1px solid var(--border-color);
        }
        
        .chat-input-form {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: var(--card-bg);
            border-radius: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .chat-input-form:focus-within {
            box-shadow: 0 3px 15px rgba(108, 92, 231, 0.2);
            border-color: var(--primary-light);
        }
        
        .chat-input {
            flex: 1;
            padding: 0.5rem;
            border: none;
            background: transparent;
            outline: none;
            color: var(--light-text);
            font-size: 0.95rem;
        }
        
        .chat-input::placeholder {
            color: var(--gray-text);
            opacity: 0.6;
        }
        
        .chat-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .chat-submit:hover {
            transform: scale(1.05) rotate(10deg);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
        }
        
        .no-chat-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-text);
            flex-direction: column;
            gap: 1.5rem;
            padding: 2rem;
            text-align: center;
            background-color: var(--dark-bg);
        }
        
        .no-chat-placeholder i {
            font-size: 5rem;
            color: var(--primary-color);
            opacity: 0.7;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .no-chat-placeholder p {
            font-size: 1.2rem;
            max-width: 400px;
            color: var(--light-text);
        }
        
        .no-chat-placeholder small {
            color: var(--gray-text);
            max-width: 350px;
            line-height: 1.5;
        }
        
        .empty-list-message {
            padding: 2rem;
            text-align: center;
            color: var(--gray-text);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .empty-list-message i {
            font-size: 3.5rem;
            color: var(--primary-color);
            opacity: 0.7;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.05); opacity: 0.9; }
            100% { transform: scale(1); opacity: 0.7; }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--dark-bg);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .chat-container {
                height: calc(100vh - 70px);
            }
            
            .user-list {
                width: 280px;
            }
            
            .message-wrapper {
                max-width: 85%;
            }
        }
        
        @media (max-width: 768px) {
            .user-list {
                width: 240px;
            }
            
            .message-wrapper {
                max-width: 90%;
            }
            
            .page-header {
                padding: 0.75rem 1rem;
            }
            
            .back-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }
        
        /* Mobile styles and sidebar toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.3);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-toggle:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 576px) {
            body {
                overflow-y: auto;
            }
            
            .page-header {
                padding: 0.7rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .back-btn {
                padding: 0.3rem 0.7rem;
                font-size: 0.8rem;
            }
            
            .chat-container {
                flex-direction: column;
                height: auto;
            }
            
            .user-list {
                position: fixed;
                left: -100%;
                top: 0;
                width: 80%;
                height: 100%;
                z-index: 1001;
                transition: all 0.3s ease;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
                overflow-y: auto;
            }
            
            .user-list.active {
                left: 0;
            }
            
            .chat-box {
                min-height: calc(100vh - 70px);
                width: 100%;
                display: flex;
                flex-direction: column;
            }
            
            .mobile-toggle {
                display: flex;
            }
            
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .mobile-overlay.active {
                display: block;
                opacity: 1;
            }
            
            .chat-messages {
                flex: 1;
                max-height: 50vh;
                overflow-y: auto;
            }
            
            .chat-input-container {
                position: relative;
                padding: 0.75rem;
            }
            
            .chat-input-form {
                padding: 0.5rem 1rem;
            }
            
            .chat-submit {
                width: 2.2rem;
                height: 2.2rem;
            }
            
            .email-notification-container {
                padding: 0.75rem;
                margin-bottom: 2rem;
            }
            
            .email-toggle-btn {
                padding: 0.6rem 1rem;
            }
            
            .email-form {
                padding: 1rem;
                max-height: 60vh;
            }
        }
        
        @media (max-width: 400px) {
            .page-title {
                font-size: 1.2rem;
            }
            
            .chat-header {
                padding: 0.75rem;
            }
            
            .user-avatar {
                width: 2rem;
                height: 2rem;
                font-size: 1rem;
            }
            
            .chat-header-name {
                font-size: 1rem;
            }
            
            .chat-messages {
                padding: 1rem;
                height: calc(100vh - 190px);
            }
            
            .message {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }
            
            .message-wrapper {
                max-width: 95%;
            }
        }
        
        .user-notification-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            position: absolute;
            right: 1rem;
            top: 1rem;
            box-shadow: 0 3px 8px rgba(255, 107, 107, 0.3);
            animation: pulse 1.5s infinite;
        }
        
        .user-notification-dot {
            position: absolute;
            right: 1rem;
            top: 1rem;
            width: 10px;
            height: 10px;
            background-color: #ff6b6b;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        .user-last-message {
            font-size: 0.75rem;
            color: var(--gray-text);
            margin-top: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            opacity: 0.8;
        }
        
        .message-time-indicator {
            color: var(--accent-color);
            font-size: 0.7rem;
            margin-right: 0.5rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        /* Email notification styles */
        .email-notification-container {
            padding: 1rem;
            background: var(--dark-bg-lighter);
            border-top: 1px solid var(--border-color);
            position: relative;
            z-index: 10;
        }
        
        .email-toggle-btn {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .email-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }
        
        .email-form {
            margin-top: 1rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .email-form .form-group {
            margin-bottom: 1rem;
        }
        
        .email-form label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light-text);
            font-weight: 500;
        }
        
        .email-subject, .email-message {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--dark-bg);
            color: var(--light-text);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .email-subject:focus, .email-message:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.2);
            outline: none;
        }
        
        .email-message {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .cancel-email-btn, .send-email-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .cancel-email-btn {
            background: var(--hover-color);
            color: var(--light-text);
            border: 1px solid var(--border-color);
        }
        
        .cancel-email-btn:hover {
            background: var(--border-color);
        }
        
        .send-email-btn {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
            border: none;
            font-weight: 500;
        }
        
        .send-email-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(243, 156, 18, 0.3);
        }
        
        /* Alert messages */
        .alert {
            padding: 1rem;
            margin: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            position: relative;
            animation: fadeInAlert 0.3s ease;
        }
        
        @keyframes fadeInAlert {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.2);
            border-left: 4px solid #27ae60;
            color: #2ecc71;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            color: #e74c3c;
        }
        
        .close-alert {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            color: inherit;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.2s ease;
        }
        
        .close-alert:hover {
            opacity: 1;
        }
        
        /* Message action buttons and reply/edit functionality */
        .message {
            position: relative;
            overflow: visible;
        }
        
        .message-actions {
            position: absolute;
            top: -25px;
            right: 10px;
            background: var(--dark-bg-lighter);
            border-radius: 20px;
            padding: 3px 8px;
            display: none;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 5;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.2s ease;
        }
        
        .user .message-actions {
            right: auto;
            left: 10px;
        }
        
        .message:hover .message-actions {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }
        
        .action-btn {
            background: none;
            border: none;
            color: var(--gray-text);
            cursor: pointer;
            font-size: 12px;
            padding: 3px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
        }
        
        .reply-btn:hover {
            color: var(--secondary-color);
        }
        
        .edit-btn:hover {
            color: var(--primary-color);
        }
        
        .delete-btn:hover {
            color: #e74c3c;
        }
        
        .reply-preview, .edit-preview {
            background: var(--card-bg);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            border-left: 3px solid var(--secondary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease;
        }
        
        .edit-preview {
            border-left-color: var(--primary-color);
        }
        
        .reply-content, .edit-content {
            flex: 1;
        }
        
        .reply-content p, .edit-content p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--gray-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cancel-reply-btn, .cancel-edit-btn {
            background: none;
            border: none;
            color: var(--gray-text);
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border-radius: 50%;
        }
        
        .cancel-reply-btn:hover, .cancel-edit-btn:hover {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }
        
        .replied-message {
            background: var(--card-bg);
            padding: 5px 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 0.75rem;
            color: var(--gray-text);
            max-width: 85%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            opacity: 0.8;
            border-left: 2px solid var(--secondary-color);
        }
        
        .edited-indicator {
            font-size: 0.65rem;
            opacity: 0.7;
            font-style: italic;
            margin-left: 3px;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1 class="page-title">User Messages</h1>
        <a href="admin.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <!-- Mobile overlay for sidebar -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <!-- Mobile toggle button -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-users"></i>
    </button>
    
    <div class="chat-container">
        <div class="user-list" id="userList">
            <div class="user-list-header">
                <i class="fas fa-users"></i> Active Conversations
            </div>
            
            <?php if (empty($users)): ?>
                <div class="empty-list-message">
                    <i class="fas fa-inbox"></i>
                    <p>No user messages yet</p>
                    <small>When users send messages, they will appear here</small>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-item <?php echo isset($selected_user) && $selected_user['user_id'] == $user['user_id'] ? 'active' : ''; ?>" 
                         onclick="window.location.href='user_messages.php?user_id=<?php echo $user['user_id']; ?>'">
                        <div class="user-name">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </div>
                        <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                        
                        <?php if (isset($user['unread_count']) && $user['unread_count'] > 0): ?>
                            <div class="user-notification-badge"><?php echo $user['unread_count']; ?></div>
                        <?php endif; ?>
                        
                        <?php
                            // Get the last message for this user
                            $last_message_query = "SELECT message, created_at FROM chat_messages 
                                                 WHERE user_id = {$user['user_id']} 
                                                 ORDER BY created_at DESC LIMIT 1";
                            $last_message_result = mysqli_query($conn, $last_message_query);
                            if ($last_message_result && $last_message = mysqli_fetch_assoc($last_message_result)):
                                $message_preview = substr(htmlspecialchars($last_message['message']), 0, 30);
                                if (strlen($last_message['message']) > 30) {
                                    $message_preview .= '...';
                                }
                                
                                $date = new DateTime($last_message['created_at']);
                                $now = new DateTime();
                                $time_diff = $now->diff($date);
                                
                                if ($time_diff->days == 0) {
                                    // Today, show time
                                    $time_display = $date->format('g:i a');
                                } elseif ($time_diff->days == 1) {
                                    // Yesterday
                                    $time_display = 'Yesterday';
                                } else {
                                    // Other days
                                    $time_display = $date->format('M j');
                                }
                        ?>
                            <div class="user-last-message">
                                <span class="message-time-indicator"><?php echo $time_display; ?></span> <?php echo $message_preview; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="chat-box">
            <?php if (isset($selected_user)): ?>
                <div class="chat-header">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($selected_user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="chat-header-info">
                        <h3 class="chat-header-name"><?php echo htmlspecialchars($selected_user['full_name']); ?></h3>
                        <span class="chat-header-username">@<?php echo htmlspecialchars($selected_user['username']); ?></span>
                    </div>
                </div>
                
                <!-- Status messages -->
                <?php if(isset($_SESSION['message_success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['message_success']); ?>
                        <button class="close-alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['message_success']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['message_error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['message_error']); ?>
                        <button class="close-alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['message_error']); ?>
                <?php endif; ?>
                
                <!-- Email notification status messages -->
                <?php if(isset($_GET['email_sent']) && $_GET['email_sent'] === 'success'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Email notification sent successfully!
                        <button class="close-alert">&times;</button>
                    </div>
                <?php elseif(isset($_GET['email_sent']) && $_GET['email_sent'] === 'error'): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> Failed to send email notification. Please try again.
                        <button class="close-alert">&times;</button>
                    </div>
                <?php elseif(isset($_GET['email_error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(urldecode($_GET['email_error'])); ?>
                        <button class="close-alert">&times;</button>
                    </div>
                <?php endif; ?>
                
                <div class="chat-messages">
                    <?php if (empty($messages)): ?>
                        <div class="empty-list-message">
                            <i class="fas fa-comments"></i>
                            <p>No messages yet</p>
                            <small>Start the conversation by sending a message below</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-wrapper <?php echo $message['is_admin'] == 1 ? 'admin' : 'user'; ?>" data-id="<?php echo $message['message_id']; ?>">
                                <?php if(isset($message['reply_to']) && !empty($message['replied_to_content'])): ?>
                                    <div class="replied-message">
                                        <i class="fas fa-reply"></i> <?php echo htmlspecialchars(substr($message['replied_to_content'], 0, 50) . (strlen($message['replied_to_content']) > 50 ? '...' : '')); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="message <?php echo $message['is_admin'] == 1 ? 'admin' : 'user'; ?>">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                    
                                    <!-- Action buttons for messages -->
                                    <div class="message-actions">
                                        <button class="action-btn reply-btn" title="Reply" data-action="reply">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <?php if($message['is_admin'] == 1): ?>
                                        <button class="action-btn edit-btn" title="Edit" data-action="edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete" data-action="delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <span class="message-time">
                                    <?php 
                                        $date = new DateTime($message['created_at']);
                                        echo $date->format('M j, g:i a'); 
                                        if($message['edited']): 
                                            echo ' <span class="edited-indicator">(edited)</span>'; 
                                        endif;
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input-container">
                    <!-- Reply preview container -->
                    <div class="reply-preview" id="replyPreview" style="display: none;">
                        <div class="reply-content">
                            <p id="replyText"></p>
                        </div>
                        <button id="cancelReply" class="cancel-reply-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Edit preview container -->
                    <div class="edit-preview" id="editPreview" style="display: none;">
                        <div class="edit-content">
                            <p id="editText">Editing message...</p>
                        </div>
                        <button id="cancelEdit" class="cancel-edit-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form class="chat-input-form" method="POST" id="messageForm" action="user_messages.php">
                        <input type="hidden" name="user_id" id="userId" value="<?php echo $selected_user['user_id']; ?>">
                        <input type="hidden" name="reply_to" id="replyTo" value="">
                        <input type="hidden" name="edit_id" id="editId" value="">
                        <input type="text" name="message" id="messageInput" class="chat-input" placeholder="Type your message..." autocomplete="off" required>
                        <button type="submit" name="send_message" class="chat-submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Email notification section -->
                <div class="email-notification-container">
                    <button class="email-toggle-btn" id="toggleEmailForm">
                        <i class="fas fa-envelope"></i> Send Email Notification
                    </button>
                    <div class="email-form" id="emailForm" style="display: none;">
                        <form method="POST" action="send_email_notification.php">
                            <input type="hidden" name="user_id" value="<?php echo $selected_user['user_id']; ?>">
                            <?php if(isset($selected_user['email'])): ?>
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($selected_user['email']); ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="emailSubject">Subject:</label>
                                <input type="text" name="email_subject" id="emailSubject" class="email-subject" required placeholder="Email subject...">
                            </div>
                            <div class="form-group">
                                <label for="emailMessage">Message:</label>
                                <textarea name="email_message" id="emailMessage" class="email-message" required placeholder="Type your email message..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-email-btn" id="cancelEmailBtn">Cancel</button>
                                <button type="submit" name="send_email_notification" class="send-email-btn">Send Email</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-chat-placeholder">
                    <i class="fas fa-comments"></i>
                    <p>Select a user from the list to view conversation</p>
                    <small>You can reply to user messages and provide support directly through this interface</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom of chat messages
        window.onload = function() {
            const chatMessages = document.querySelector('.chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Add focus to the message input if available
            const messageInput = document.querySelector('.chat-input');
            if (messageInput) {
                messageInput.focus();
            }
            
            // Add subtle hover effects to messages
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'transform 0.2s ease';
                });
                message.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Add functionality to close alert messages
            const closeAlertButtons = document.querySelectorAll('.close-alert');
            closeAlertButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const alert = this.parentElement;
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                });
            });
            
            // Mobile sidebar toggle functionality
            const mobileToggleBtn = document.getElementById('mobileToggle');
            const userList = document.getElementById('userList');
            const mobileOverlay = document.getElementById('mobileOverlay');
            
            if (mobileToggleBtn && userList && mobileOverlay) {
                mobileToggleBtn.addEventListener('click', function() {
                    userList.classList.toggle('active');
                    mobileOverlay.classList.toggle('active');
                    
                    if (userList.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                        mobileToggleBtn.innerHTML = '<i class="fas fa-times"></i>';
                    } else {
                        document.body.style.overflow = '';
                        mobileToggleBtn.innerHTML = '<i class="fas fa-users"></i>';
                    }
                });
                
                // Close sidebar when clicking on overlay
                mobileOverlay.addEventListener('click', function() {
                    userList.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                    mobileToggleBtn.innerHTML = '<i class="fas fa-users"></i>';
                });
            }
            
            // Initialize message actions (reply, edit, delete)
            initMessageActions();
        };
        
        // Email form toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleEmailFormBtn = document.getElementById('toggleEmailForm');
            const emailForm = document.getElementById('emailForm');
            const cancelEmailBtn = document.getElementById('cancelEmailBtn');
            
            if (toggleEmailFormBtn && emailForm) {
                toggleEmailFormBtn.addEventListener('click', function() {
                    if (emailForm.style.display === 'none') {
                        emailForm.style.display = 'block';
                        toggleEmailFormBtn.innerHTML = '<i class="fas fa-envelope-open"></i> Hide Email Form';
                        // Scroll to the form to ensure visibility
                        emailForm.scrollIntoView({behavior: 'smooth'});
                    } else {
                        emailForm.style.display = 'none';
                        toggleEmailFormBtn.innerHTML = '<i class="fas fa-envelope"></i> Send Email Notification';
                    }
                });
                
                if (cancelEmailBtn) {
                    cancelEmailBtn.addEventListener('click', function() {
                        emailForm.style.display = 'none';
                        toggleEmailFormBtn.innerHTML = '<i class="fas fa-envelope"></i> Send Email Notification';
                        // Reset form
                        const emailSubject = document.getElementById('emailSubject');
                        const emailMessage = document.getElementById('emailMessage');
                        if (emailSubject) emailSubject.value = '';
                        if (emailMessage) emailMessage.value = '';
                    });
                }
            }
        });
        
        // Initialize message action buttons (reply, edit, delete)
        function initMessageActions() {
            // Message form elements
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');
            const replyTo = document.getElementById('replyTo');
            const editId = document.getElementById('editId');
            
            // Preview containers
            const replyPreview = document.getElementById('replyPreview');
            const replyText = document.getElementById('replyText');
            const editPreview = document.getElementById('editPreview');
            
            // Cancel buttons
            const cancelReply = document.getElementById('cancelReply');
            const cancelEdit = document.getElementById('cancelEdit');
            
            // Handle reply mode
            cancelReply.addEventListener('click', function() {
                replyPreview.style.display = 'none';
                replyTo.value = '';
                messageInput.focus();
            });
            
            // Handle edit mode
            cancelEdit.addEventListener('click', function() {
                editPreview.style.display = 'none';
                editId.value = '';
                messageInput.value = '';
                messageInput.focus();
            });
            
            // Handle all message action buttons
            const actionButtons = document.querySelectorAll('.action-btn');
            
            actionButtons.forEach(btn => {
                btn.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const action = this.getAttribute('data-action');
                    const messageWrapper = this.closest('.message-wrapper');
                    const messageId = messageWrapper.getAttribute('data-id');
                    const messageContent = messageWrapper.querySelector('.message').textContent.trim();
                    
                    // Reset state
                    replyPreview.style.display = 'none';
                    editPreview.style.display = 'none';
                    replyTo.value = '';
                    editId.value = '';
                    
                    if (action === 'reply') {
                        // Set up reply mode
                        replyTo.value = messageId;
                        replyText.textContent = messageContent.substring(0, 50) + (messageContent.length > 50 ? '...' : '');
                        replyPreview.style.display = 'flex';
                        messageInput.focus();
                        
                    } else if (action === 'edit') {
                        // Set up edit mode
                        editId.value = messageId;
                        messageInput.value = messageContent;
                        editPreview.style.display = 'flex';
                        messageInput.focus();
                        
                    } else if (action === 'delete') {
                        // Show custom deletion confirmation dialog
                        showDeleteConfirmDialog(messageId);
                    }
                });
            });
            
            // Function to show a custom delete confirmation dialog
            function showDeleteConfirmDialog(messageId) {
                // Create dialog if it doesn't exist
                if (!document.getElementById('delete-confirm-dialog')) {
                    const dialogHTML = `
                        <div id="delete-confirm-overlay" style="
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.4);
                            backdrop-filter: blur(5px);
                            -webkit-backdrop-filter: blur(5px);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 9999;
                            opacity: 0;
                            visibility: hidden;
                            transition: all 0.3s ease;
                        ">
                            <div id="delete-confirm-dialog" style="
                                background: rgba(108, 92, 231, 0.9);
                                border-radius: 16px;
                                padding: 0;
                                width: 400px;
                                max-width: 90%;
                                box-shadow: 0 15px 35px rgba(108, 92, 231, 0.4);
                                transform: scale(0.9) rotate(-2deg);
                                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                                position: relative;
                                overflow: hidden;
                                border: 1px solid rgba(255, 255, 255, 0.2);
                            ">
                                <div style="
                                    position: absolute;
                                    top: -40px;
                                    right: -40px;
                                    width: 100px;
                                    height: 100px;
                                    background: rgba(231, 76, 60, 1);
                                    transform: rotate(45deg);
                                    z-index: -1;
                                "></div>
                                
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    padding: 20px 25px;
                                    background: rgba(0, 0, 0, 0.2);
                                    margin-bottom: 0;
                                    position: relative;
                                    overflow: hidden;
                                ">
                                    <div style="
                                        width: 36px;
                                        height: 36px;
                                        background: rgba(231, 76, 60, 0.9);
                                        color: white;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-right: 15px;
                                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                                    ">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <h3 style="
                                        margin: 0;
                                        color: white;
                                        font-size: 1.2rem;
                                    ">Confirm Deletion</h3>
                                </div>
                                
                                <div style="
                                    padding: 25px;
                                    color: white;
                                ">
                                    <div style="
                                        padding: 15px;
                                        background: rgba(255, 255, 255, 0.1);
                                        border-radius: 8px;
                                        margin-bottom: 20px;
                                        border-left: 3px solid rgba(231, 76, 60, 0.9);
                                    ">
                                        <div style="
                                            font-size: 1rem;
                                            margin-bottom: 8px;
                                            font-weight: 500;
                                        ">Are you sure you want to delete this message?</div>
                                        <div style="
                                            font-size: 0.85rem;
                                            opacity: 0.8;
                                        ">This action cannot be undone.</div>
                                    </div>
                                    
                                    <div style="
                                        display: flex;
                                        justify-content: flex-end;
                                        gap: 15px;
                                    ">
                                        <button id="delete-cancel-btn" style="
                                            padding: 10px 20px;
                                            background: rgba(255, 255, 255, 0.15);
                                            color: white;
                                            border: none;
                                            border-radius: 30px;
                                            cursor: pointer;
                                            transition: all 0.2s ease;
                                            font-size: 0.9rem;
                                        ">Cancel</button>
                                        
                                        <button id="delete-confirm-btn" style="
                                            padding: 10px 25px;
                                            background: rgba(231, 76, 60, 0.9);
                                            color: white;
                                            border: none;
                                            border-radius: 30px;
                                            cursor: pointer;
                                            transition: all 0.2s ease;
                                            font-size: 0.9rem;
                                            font-weight: 600;
                                            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
                                        ">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', dialogHTML);
                    
                    // Setup event listeners
                    document.getElementById('delete-cancel-btn').addEventListener('click', () => {
                        hideDeleteConfirmDialog();
                    });
                    
                    // Add hover effects
                    const cancelBtn = document.getElementById('delete-cancel-btn');
                    const confirmBtn = document.getElementById('delete-confirm-btn');
                    
                    cancelBtn.addEventListener('mouseover', () => {
                        cancelBtn.style.background = 'rgba(255, 255, 255, 0.25)';
                    });
                    
                    cancelBtn.addEventListener('mouseout', () => {
                        cancelBtn.style.background = 'rgba(255, 255, 255, 0.15)';
                    });
                    
                    confirmBtn.addEventListener('mouseover', () => {
                        confirmBtn.style.background = 'rgba(231, 76, 60, 1)';
                        confirmBtn.style.boxShadow = '0 6px 15px rgba(231, 76, 60, 0.4)';
                    });
                    
                    confirmBtn.addEventListener('mouseout', () => {
                        confirmBtn.style.background = 'rgba(231, 76, 60, 0.9)';
                        confirmBtn.style.boxShadow = '0 4px 10px rgba(231, 76, 60, 0.3)';
                    });
                    
                    // Close when clicking outside dialog
                    document.getElementById('delete-confirm-overlay').addEventListener('click', (e) => {
                        if (e.target === document.getElementById('delete-confirm-overlay')) {
                            hideDeleteConfirmDialog();
                        }
                    });
                    
                    // Close on ESC key
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && document.getElementById('delete-confirm-overlay').style.visibility === 'visible') {
                            hideDeleteConfirmDialog();
                        }
                    });
                }
                
                // Update the confirm button with current message ID
                const confirmBtn = document.getElementById('delete-confirm-btn');
                confirmBtn.onclick = () => {
                    hideDeleteConfirmDialog();
                    deleteMessage(messageId);
                };
                
                // Show the dialog with animation
                const overlay = document.getElementById('delete-confirm-overlay');
                const dialog = document.getElementById('delete-confirm-dialog');
                
                overlay.style.visibility = 'visible';
                overlay.style.opacity = '1';
                
                // Small delay for better animation
                setTimeout(() => {
                    dialog.style.transform = 'scale(1) rotate(0deg)';
                }, 30);
            }
            
            // Function to hide delete confirmation dialog
            function hideDeleteConfirmDialog() {
                const overlay = document.getElementById('delete-confirm-overlay');
                const dialog = document.getElementById('delete-confirm-dialog');
                
                dialog.style.transform = 'scale(0.9) rotate(2deg)';
                overlay.style.opacity = '0';
                
                setTimeout(() => {
                    overlay.style.visibility = 'hidden';
                }, 300);
            }
            
            // Handle form submission for replies and edits
            if (messageForm) {
                messageForm.addEventListener('submit', function(event) {
                    const isEditing = editId.value !== '';
                    
                    if (isEditing) {
                        event.preventDefault();
                        editMessage(editId.value, messageInput.value);
                        return false;
                    }
                    // For regular messages and replies, let the form submit normally
                });
            }
        }
        
        // Function to handle message deletion
        function deleteMessage(messageId) {
            // Create a form to submit the delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'delete_message';
            
            const messageIdInput = document.createElement('input');
            messageIdInput.name = 'message_id';
            messageIdInput.value = messageId;
            
            form.appendChild(actionInput);
            form.appendChild(messageIdInput);
            
            // Add form to the document and submit it
            document.body.appendChild(form);
            form.submit();
        }
        
        // Function to handle message editing
        function editMessage(messageId, newContent) {
            // Create a form to submit the edit request
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'edit_message';
            
            const messageIdInput = document.createElement('input');
            messageIdInput.name = 'message_id';
            messageIdInput.value = messageId;
            
            const contentInput = document.createElement('input');
            contentInput.name = 'message';
            contentInput.value = newContent;
            
            form.appendChild(actionInput);
            form.appendChild(messageIdInput);
            form.appendChild(contentInput);
            
            // Add form to the document and submit it
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html> 