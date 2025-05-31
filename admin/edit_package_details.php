<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';

// Check if admin is logged in
require_admin_login();

// Initialize variables
$success_message = "";
$error_message = "";
$package_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$package = [];
$details = [];

// Check if package exists
if ($package_id > 0) {
    $query = "SELECT * FROM packages WHERE package_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $package_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $package = mysqli_fetch_assoc($result);
    
    // Get existing package details if any
    $details_query = "SELECT * FROM package_details WHERE package_id = ?";
    $details_stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($details_stmt, "i", $package_id);
    mysqli_stmt_execute($details_stmt);
    $details_result = mysqli_stmt_get_result($details_stmt);
    $details = mysqli_fetch_assoc($details_result);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_details'])) {
    // Get form data
    $title = sanitize_input($_POST['title']);
    $day_number = sanitize_input($_POST['day_number']);
    $description = sanitize_input($_POST['description']);
    $meal_plan = sanitize_input($_POST['meal_plan']);
    $activities = sanitize_input($_POST['activities']);
    $transport_type = sanitize_input($_POST['transport_type']);
    $hotel_id = sanitize_input($_POST['hotel_id']);
    $destination_id = sanitize_input($_POST['destination_id']);
    
    // Handle image upload if provided
    $image = isset($details['image']) ? $details['image'] : '';
    if (isset($_FILES['detail_image']) && $_FILES['detail_image']['size'] > 0) {
        $upload_result = upload_file($_FILES['detail_image']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
        } else {
            $error_message = "Image upload failed: " . $upload_result['error'];
        }
    }
    
    // Check if details already exist for this package
    $check_query = "SELECT detail_id FROM package_details WHERE package_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $package_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing details
        $details_row = mysqli_fetch_assoc($check_result);
        $detail_id = $details_row['detail_id'];
        
        $update_query = "UPDATE package_details SET 
                        title = ?, 
                        day_number = ?, 
                        description = ?, 
                        meal_plan = ?, 
                        activities = ?, 
                        transport_type = ?, 
                        hotel_id = ?, 
                        destination_id = ?, 
                        image = ? 
                        WHERE detail_id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param(
            $update_stmt, 
            "sissssisi", 
            $title, 
            $day_number, 
            $description, 
            $meal_plan, 
            $activities, 
            $transport_type, 
            $hotel_id, 
            $destination_id, 
            $image, 
            $detail_id
        );
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Package details updated successfully!";
        } else {
            $error_message = "Error updating package details: " . mysqli_error($conn);
        }
    } else {
        // Insert new details
        $insert_query = "INSERT INTO package_details (
                        package_id, 
                        title, 
                        day_number, 
                        description, 
                        meal_plan, 
                        activities, 
                        transport_type, 
                        hotel_id, 
                        destination_id, 
                        image
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param(
            $insert_stmt, 
            "isissssis", 
            $package_id, 
            $title, 
            $day_number, 
            $description, 
            $meal_plan, 
            $activities, 
            $transport_type, 
            $hotel_id, 
            $destination_id, 
            $image
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success_message = "Package details added successfully!";
        } else {
            $error_message = "Error adding package details: " . mysqli_error($conn);
        }
    }
    
    // Refresh details data
    $details_query = "SELECT * FROM package_details WHERE package_id = ?";
    $details_stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($details_stmt, "i", $package_id);
    mysqli_stmt_execute($details_stmt);
    $details_result = mysqli_stmt_get_result($details_stmt);
    $details = mysqli_fetch_assoc($details_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Package Details - Adventure Travel.lk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        :root {
            --primary-color: rgb(23, 108, 101);
            --secondary-color: rgb(101, 255, 193);
            --dark-color: #333;
            --light-color: #f4f4f4;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }

        body {
            background-color: #f0f2f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .package-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .package-info h2 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .package-meta {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            color: #666;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            text-decoration: none;
            margin-right: 10px;
        }

        .btn:hover {
            background-color: #145a55;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Package Details</h1>
        
        <?php if (!empty($package)): ?>
            <div class="package-info">
                <h2><?php echo htmlspecialchars($package['name']); ?></h2>
                <p><?php echo htmlspecialchars($package['description']); ?></p>
                <div class="package-meta">
                    <span>Price: $<?php echo number_format($package['price'], 2); ?></span>
                    <span>Duration: <?php echo htmlspecialchars($package['duration']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $package_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo isset($details['title']) ? htmlspecialchars($details['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="day_number">Day Number</label>
                    <input type="number" id="day_number" name="day_number" class="form-control" value="<?php echo isset($details['day_number']) ? htmlspecialchars($details['day_number']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control"><?php echo isset($details['description']) ? htmlspecialchars($details['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="meal_plan">Meal Plan</label>
                    <input type="text" id="meal_plan" name="meal_plan" class="form-control" value="<?php echo isset($details['meal_plan']) ? htmlspecialchars($details['meal_plan']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="activities">Activities</label>
                    <textarea id="activities" name="activities" class="form-control"><?php echo isset($details['activities']) ? htmlspecialchars($details['activities']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="transport_type">Transport Type</label>
                    <input type="text" id="transport_type" name="transport_type" class="form-control" value="<?php echo isset($details['transport_type']) ? htmlspecialchars($details['transport_type']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="hotel_id">Hotel ID</label>
                    <input type="number" id="hotel_id" name="hotel_id" class="form-control" value="<?php echo isset($details['hotel_id']) ? htmlspecialchars($details['hotel_id']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="destination_id">Destination ID</label>
                    <input type="number" id="destination_id" name="destination_id" class="form-control" value="<?php echo isset($details['destination_id']) ? htmlspecialchars($details['destination_id']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="detail_image">Additional Image</label>
                    <?php if (isset($details['image']) && !empty($details['image'])): ?>
                        <p>Current image: <?php echo htmlspecialchars($details['image']); ?></p>
                    <?php endif; ?>
                    <input type="file" id="detail_image" name="detail_image" class="form-control" accept="image/*">
                </div>
                
                <div class="actions">
                    <button type="submit" name="save_details" class="btn">Save Details</button>
                    <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </form>
        <?php else: ?>
            <div class="message error">Package not found. Please select a valid package.</div>
            <div class="actions">
                <a href="admin.php" class="btn">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 