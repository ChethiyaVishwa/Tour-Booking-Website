<?php
// Include necessary files
require_once 'config.php';
require_once 'chat_functions.php';

// Check if admin is logged in
require_admin_login();

// Handle email notification form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email_notification'])) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $email_subject = isset($_POST['email_subject']) ? sanitize_input($_POST['email_subject']) : '';
    $email_message = isset($_POST['email_message']) ? sanitize_input($_POST['email_message']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    
    // If email is not provided in the form, try to get it from the database
    if (empty($email) && $user_id > 0) {
        $user_query = "SELECT email FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $user_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $user_email);
            
            if (mysqli_stmt_fetch($stmt)) {
                $email = $user_email;
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate inputs
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($email_subject)) {
        $errors[] = "Email subject is required";
    }
    
    if (empty($email_message)) {
        $errors[] = "Email message is required";
    }
    
    // If no errors, send the email
    if (empty($errors)) {
        // Add greeting to the message
        $full_message = "Dear User,\n\n" . $email_message . "\n\nBest regards,\nAdventure Travel.lk Team";
        
        // Send the email
        $result = send_email_notification($email, $email_subject, $full_message);
        
        if ($result) {
            // Log the email notification in the chat
            $admin_message = "📧 Email notification sent: " . $email_subject;
            add_message($user_id, $admin_message, true);
            
            // Redirect back with success message
            header("Location: user_messages.php?user_id=$user_id&email_sent=success");
            exit;
        } else {
            // Redirect back with error message
            header("Location: user_messages.php?user_id=$user_id&email_sent=error");
            exit;
        }
    } else {
        // Redirect back with validation errors
        $error_string = implode(", ", $errors);
        header("Location: user_messages.php?user_id=$user_id&email_error=" . urlencode($error_string));
        exit;
    }
} else {
    // Redirect to messages page if accessed directly
    header("Location: user_messages.php");
    exit;
}
?> 