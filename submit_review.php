<?php
// Include database configuration
require_once 'admin/config.php';

// Make sure reviews table exists
$check_table = "SHOW TABLES LIKE 'reviews'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    // Create reviews table
    $create_table = "CREATE TABLE reviews (
        review_id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        tour_type VARCHAR(100) NOT NULL,
        rating INT(1) NOT NULL,
        review_text TEXT NOT NULL,
        photo VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (review_id),
        KEY status_index (status),
        KEY rating_index (rating)
    )";
    
    mysqli_query($conn, $create_table);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Handle tour type (check if "other" was selected)
    if ($_POST['tour_type'] === 'other' && isset($_POST['custom_tour_type']) && !empty($_POST['custom_tour_type'])) {
        $tour_type = mysqli_real_escape_string($conn, $_POST['custom_tour_type']);
    } else {
        $tour_type = mysqli_real_escape_string($conn, $_POST['tour_type']);
    }
    
    $rating = intval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    
    // Check if all required fields are provided
    if (empty($name) || empty($email) || empty($tour_type) || $rating <= 0 || empty($review)) {
        // Redirect back with error
        header("Location: index.php?error=review_fields_required#review");
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=invalid_email#review");
        exit;
    }
    
    // Validate rating (1-5)
    if ($rating < 1 || $rating > 5) {
        header("Location: index.php?error=invalid_rating#review");
        exit;
    }
    
    // Handle photo upload if provided
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['photo']['type'];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            header("Location: index.php?error=invalid_file_type#review");
            exit;
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'review_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $upload_path = 'images/' . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            $photo = $new_filename;
        } else {
            // Failed to move file, but we'll continue without the photo
            $photo = '';
        }
    }
    
    // Insert review into database
    $insert_query = "INSERT INTO reviews (name, email, tour_type, rating, review_text, photo, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "sssiss", $name, $email, $tour_type, $rating, $review, $photo);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        // Success - redirect with success message
        header("Location: index.php?success=review_submitted#review");
        exit;
    } else {
        // Error - redirect with error message
        header("Location: index.php?error=review_submission_failed#review");
        exit;
    }
} else {
    // Not a POST request, redirect to home
    header("Location: index.php");
    exit;
} 