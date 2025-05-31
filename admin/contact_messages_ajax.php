<?php
// Include database configuration
require_once 'config.php';

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Handle different AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_unread_count':
            // Get count of unread contact messages
            $query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
            $result = mysqli_query($conn, $query);
            
            if ($result && $row = mysqli_fetch_assoc($result)) {
                echo json_encode(['success' => true, 'data' => $row['count']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error querying database']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 