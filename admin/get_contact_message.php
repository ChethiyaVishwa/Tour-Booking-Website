<?php
// Include database configuration
require_once 'config.php';

// Check if admin is logged in
require_admin_login();

// Initialize response array
$response = [
    'success' => false,
    'message' => 'No message ID provided'
];

// Check if message ID is provided
if (isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    
    // Get message details
    $query = "SELECT * FROM contact_messages WHERE message_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response = [
            'success' => true,
            'message' => $row
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Message not found'
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>