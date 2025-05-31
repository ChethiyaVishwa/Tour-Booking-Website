<?php
// AJAX handler for chat functionality
require_once 'config.php';
require_once 'chat_functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log access to this file
error_log("message_ajax.php accessed with REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Allow cross-origin requests (CORS) for InfinityFree hosting
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure request is AJAX and return as JSON
header('Content-Type: application/json');

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'data' => null
];

// Check if action is specified
if (!isset($_POST['action'])) {
    $response['message'] = 'No action specified';
    error_log("No action specified in request");
    echo json_encode($response);
    exit;
}

$action = $_POST['action'];

// Log the action for debugging
error_log("Chat AJAX request: $action");

// Handle different actions
switch ($action) {
    case 'get_messages':
        // Requires user_id parameter
        if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
            $response['message'] = 'Invalid user ID';
            error_log("Invalid user ID for get_messages");
            break;
        }
        
        $user_id = (int)$_POST['user_id'];
        error_log("Getting messages for user ID: $user_id");
        
        // Check permissions - temporarily disabled for debugging
        $is_admin = isset($_SESSION['admin_id']);
        $is_user = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id;
        
        // Log session info
        error_log("Session info - admin_id: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'not set'));
        error_log("Session info - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
        
        // For now, allow all requests for debugging
        // if (!$is_admin && !$is_user) {
        //     $response['message'] = 'Unauthorized access';
        //     error_log("Unauthorized attempt to access messages for user ID: $user_id");
        //     break;
        // }
        
        try {
            // Mark messages as read
            mark_as_read($user_id, $is_admin);
            
            // Get messages
            $messages = get_user_messages($user_id);
            $response['success'] = true;
            $response['message'] = 'Messages retrieved successfully';
            $response['data'] = $messages;
            $response['count'] = count($messages);
            error_log("Retrieved " . count($messages) . " messages for user ID: $user_id");
        } catch (Exception $e) {
            $response['message'] = 'Error retrieving messages: ' . $e->getMessage();
            error_log("Error in get_messages: " . $e->getMessage());
        }
        break;
    
    case 'send_message':
        // Requires user_id and message parameters
        if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id']) || !isset($_POST['message']) || empty($_POST['message'])) {
            $response['message'] = 'Invalid user ID or message';
            error_log("Invalid user ID or message for send_message");
            break;
        }
        
        $user_id = (int)$_POST['user_id'];
        $message = sanitize_input($_POST['message']);
        error_log("Sending message to user ID: $user_id, message: $message");
        
        // Check permissions - temporarily disabled for debugging
        $is_admin = isset($_SESSION['admin_id']);
        $is_user = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id;
        
        // Log session info
        error_log("Session info - admin_id: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'not set'));
        error_log("Session info - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
        
        // For now, allow all requests for debugging
        // if (!$is_admin && !$is_user) {
        //     $response['message'] = 'Unauthorized access';
        //     error_log("Unauthorized attempt to send message to user ID: $user_id");
        //     break;
        // }
        
        // Use the provided is_admin parameter if available, otherwise determine it based on session
        $is_admin_param = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : ($is_admin ? 1 : 0);
        error_log("is_admin_param: $is_admin_param");
        
        // Check if this is a reply to another message
        $reply_to = isset($_POST['reply_to']) ? (int)$_POST['reply_to'] : null;
        
        try {
            // Add message with reply_to information if provided
            $success = add_message($user_id, $message, $is_admin_param, $reply_to);
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Message sent successfully';
                
                // Return the newly added message
                $messages = get_user_messages($user_id);
                $lastMessage = end($messages);
                $response['data'] = $lastMessage;
                error_log("Message sent successfully to user ID: $user_id, message_id: " . $lastMessage['message_id']);
            } else {
                $response['message'] = 'Failed to send message';
                error_log("Failed to send message to user ID: $user_id");
            }
        } catch (Exception $e) {
            $response['message'] = 'Error sending message: ' . $e->getMessage();
            error_log("Error in send_message: " . $e->getMessage());
        }
        break;
    
    case 'edit_message':
        // Requires message_id and message parameters
        if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id']) || !isset($_POST['message']) || empty($_POST['message'])) {
            $response['message'] = 'Invalid message ID or content';
            error_log("Invalid message ID or content for edit_message");
            break;
        }
        
        $message_id = (int)$_POST['message_id'];
        $message = sanitize_input($_POST['message']);
        error_log("Editing message ID: $message_id, new content: $message");
        
        try {
            // Check message age before attempting to edit
            $message_info = get_message_by_id($message_id);
            if ($message_info) {
                $message_time = strtotime($message_info['created_at']);
                $current_time = time();
                $time_diff_minutes = ($current_time - $message_time) / 60;
                
                // Check if user is trying to edit a message that's more than 10 minutes old
                if ($time_diff_minutes > 10 && !isset($_SESSION['admin_id'])) {
                    $response['message'] = 'Cannot edit messages older than 10 minutes';
                    error_log("Edit rejected - message is older than 10 minutes: $time_diff_minutes minutes");
                    break;
                }
            }
            
            // Edit message
            $success = edit_message($message_id, $message);
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Message edited successfully';
                error_log("Message edited successfully, ID: $message_id");
                
                // Return the edited message
                $edited_message = get_message_by_id($message_id);
                $response['data'] = $edited_message;
            } else {
                $response['message'] = 'Failed to edit message: Messages can only be edited within 10 minutes';
                error_log("Failed to edit message ID: $message_id");
            }
        } catch (Exception $e) {
            $response['message'] = 'Error editing message: ' . $e->getMessage();
            error_log("Error in edit_message: " . $e->getMessage());
        }
        break;
        
    case 'delete_message':
        // Requires message_id parameter
        if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
            $response['message'] = 'Invalid message ID';
            error_log("Invalid message ID for delete_message");
            break;
        }
        
        $message_id = (int)$_POST['message_id'];
        error_log("Deleting message ID: $message_id");
        
        try {
            // Check message age before attempting to delete
            $message_info = get_message_by_id($message_id);
            if ($message_info) {
                $message_time = strtotime($message_info['created_at']);
                $current_time = time();
                $time_diff_minutes = ($current_time - $message_time) / 60;
                
                // Check if user is trying to delete a message that's more than 10 minutes old
                if ($time_diff_minutes > 10 && !isset($_SESSION['admin_id'])) {
                    $response['message'] = 'Cannot delete messages older than 10 minutes';
                    error_log("Delete rejected - message is older than 10 minutes: $time_diff_minutes minutes");
                    break;
                }
            }
            
            // Delete message
            $success = delete_message($message_id);
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Message deleted successfully';
                error_log("Message deleted successfully, ID: $message_id");
            } else {
                $response['message'] = 'Failed to delete message: Messages can only be deleted within 10 minutes';
                error_log("Failed to delete message ID: $message_id");
            }
        } catch (Exception $e) {
            $response['message'] = 'Error deleting message: ' . $e->getMessage();
            error_log("Error in delete_message: " . $e->getMessage());
        }
        break;
    
    case 'get_unread_count':
        try {
            // For user
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $unread_count = get_unread_count($user_id);
                
                $response['success'] = true;
                $response['message'] = 'Unread count retrieved successfully';
                $response['data'] = $unread_count;
                error_log("User $user_id has $unread_count unread messages");
            } 
            // For admin
            else if (isset($_SESSION['admin_id'])) {
                $unread_count = get_unread_count(0, true);
                
                $response['success'] = true;
                $response['message'] = 'Unread count retrieved successfully';
                $response['data'] = $unread_count;
                error_log("Admin has $unread_count total unread messages");
            } else {
                $response['message'] = 'Unauthorized access';
                error_log("Unauthorized attempt to get unread count");
            }
        } catch (Exception $e) {
            $response['message'] = 'Error getting unread count: ' . $e->getMessage();
            error_log("Error in get_unread_count: " . $e->getMessage());
        }
        break;
    
    case 'get_active_sessions':
        // Admin only
        if (!isset($_SESSION['admin_id'])) {
            $response['message'] = 'Unauthorized access';
            error_log("Unauthorized attempt to get active sessions");
            break;
        }
        
        $sessions = get_active_chat_sessions();
        
        $response['success'] = true;
        $response['message'] = 'Active sessions retrieved successfully';
        $response['data'] = $sessions;
        $response['count'] = count($sessions);
        error_log("Retrieved " . count($sessions) . " active chat sessions");
        break;
    
    case 'close_session':
        // Admin only
        if (!isset($_SESSION['admin_id'])) {
            $response['message'] = 'Unauthorized access';
            error_log("Unauthorized attempt to close chat session");
            break;
        }
        
        if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
            $response['message'] = 'Invalid user ID';
            break;
        }
        
        $user_id = (int)$_POST['user_id'];
        $success = close_chat_session($user_id);
        
        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Chat session closed successfully';
            error_log("Chat session closed for user ID: $user_id");
        } else {
            $response['message'] = 'Failed to close chat session';
            error_log("Failed to close chat session for user ID: $user_id");
        }
        break;
    
    case 'mark_as_read':
        // Mark messages as read if user_id is provided
        if (isset($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            mark_as_read($user_id, true);
            $response = [
                'success' => true,
                'message' => 'Messages marked as read',
                'data' => null
            ];
        } else {
            $response['message'] = 'Missing user_id parameter';
        }
        break;
    
    default:
        $response['message'] = 'Unknown action: ' . $action;
        error_log("Unknown chat action requested: $action");
        break;
}

// Return JSON response
echo json_encode($response);
exit; 