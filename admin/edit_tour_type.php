<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';
require_once 'chat_functions.php';

// Check if admin is logged in
require_admin_login();

// Get unread messages count for admin dashboard
$unread_messages_count = get_unread_count(0, true);

// Check if tour type ID is provided
if (!isset($_GET['type']) || !is_numeric($_GET['type'])) {
    header("Location: admin.php?error=invalid_type");
    exit;
}

$type_id = (int)$_GET['type'];

// Get package type information
$query = "SELECT * FROM package_types WHERE type_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $type_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: admin.php?error=type_not_found");
    exit;
}

$type_info = mysqli_fetch_assoc($result);
$type_name = $type_info['type_name'];

// Get all packages of this type
$packages = get_packages_by_type($type_id);

// Get destinations for each package
function get_package_destinations($package_id) {
    global $conn;
    
    // First try to get from package_details table
    $detail_query = "SELECT destination_names FROM package_details WHERE package_id = ? LIMIT 1";
    $detail_stmt = mysqli_prepare($conn, $detail_query);
    mysqli_stmt_bind_param($detail_stmt, "i", $package_id);
    mysqli_stmt_execute($detail_stmt);
    $detail_result = mysqli_stmt_get_result($detail_stmt);
    
    if (mysqli_num_rows($detail_result) > 0) {
        $row = mysqli_fetch_assoc($detail_result);
        if (!empty($row['destination_names'])) {
            return $row['destination_names'];
        }
    }
    
    // If not found in package_details, try from destinations table through package_destinations
    $dest_query = "SELECT GROUP_CONCAT(d.name SEPARATOR ', ') as destinations 
                  FROM package_destinations pd 
                  JOIN destinations d ON pd.destination_id = d.destination_id 
                  WHERE pd.package_id = ?";
    $dest_stmt = mysqli_prepare($conn, $dest_query);
    mysqli_stmt_bind_param($dest_stmt, "i", $package_id);
    mysqli_stmt_execute($dest_stmt);
    $dest_result = mysqli_stmt_get_result($dest_stmt);
    
    if (mysqli_num_rows($dest_result) > 0) {
        $row = mysqli_fetch_assoc($dest_result);
        return $row['destinations'];
    }
    
    return '';
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle single package update
    if (isset($_POST['single_update']) && isset($_POST['package_id'])) {
        $id = sanitize_input($_POST['package_id']);
        $name = sanitize_input($_POST['package_name']);
        $price = sanitize_input($_POST['package_price']);
        $duration = sanitize_input($_POST['package_duration']);
        $destinations = isset($_POST['package_destinations']) ? sanitize_input($_POST['package_destinations']) : '';
        $description = isset($_POST['package_description']) ? sanitize_input($_POST['package_description']) : '';
        
        // Update package basic info
        $update_package = "UPDATE packages SET name = ?, price = ?, duration = ?, description = ? WHERE package_id = ?";
        $update_pkg_stmt = mysqli_prepare($conn, $update_package);
        mysqli_stmt_bind_param($update_pkg_stmt, "sdssi", $name, $price, $duration, $description, $id);
        
        $pkg_updated = mysqli_stmt_execute($update_pkg_stmt);
        
        // Handle image upload if provided
        if (isset($_FILES['package_image']) && isset($_FILES['package_image']['name']) && !empty($_FILES['package_image']['name'])) {
            $file_name = $_FILES['package_image']['name'];
            $file_tmp = $_FILES['package_image']['tmp_name'];
            $file_size = $_FILES['package_image']['size'];
            $file_error = $_FILES['package_image']['error'];
            
            // Only process if there's no error
            if ($file_error === 0) {
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed)) {
                    // Create unique filename
                    $new_file_name = 'package_' . $id . '_' . time() . '.' . $file_ext;
                    $upload_path = '../images/' . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Update package image in database
                        $update_image = "UPDATE packages SET image = ? WHERE package_id = ?";
                        $update_img_stmt = mysqli_prepare($conn, $update_image);
                        mysqli_stmt_bind_param($update_img_stmt, "si", $new_file_name, $id);
                        mysqli_stmt_execute($update_img_stmt);
                    }
                }
            }
        }
        
        // Update destinations if provided
        if (!empty($destinations)) {
            // Check if destination_names column exists
            $check_column = "SHOW COLUMNS FROM package_details LIKE 'destination_names'";
            $column_result = mysqli_query($conn, $check_column);
            $column_exists = mysqli_num_rows($column_result) > 0;
            
            // Check if record exists
            $check_record = "SELECT * FROM package_details WHERE package_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_record);
            mysqli_stmt_bind_param($check_stmt, "i", $id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $record_exists = mysqli_num_rows($check_result) > 0;
            
            if ($column_exists) {
                if ($record_exists) {
                    // Update existing record
                    $update_dest = "UPDATE package_details SET destination_names = ? WHERE package_id = ?";
                    $update_dest_stmt = mysqli_prepare($conn, $update_dest);
                    mysqli_stmt_bind_param($update_dest_stmt, "si", $destinations, $id);
                    mysqli_stmt_execute($update_dest_stmt);
                } else {
                    // Insert new record
                    $insert_dest = "INSERT INTO package_details (package_id, destination_names) VALUES (?, ?)";
                    $insert_dest_stmt = mysqli_prepare($conn, $insert_dest);
                    mysqli_stmt_bind_param($insert_dest_stmt, "is", $id, $destinations);
                    mysqli_stmt_execute($insert_dest_stmt);
                }
            }
        }
        
        if ($pkg_updated) {
            $success_message = "Package '" . htmlspecialchars($name) . "' updated successfully!";
            // Refresh the package list
            $packages = get_packages_by_type($type_id);
        } else {
            $error_message = "Error updating package.";
        }
    }
    
    // Handle batch update of packages
    if (isset($_POST['batch_update'])) {
        $package_ids = $_POST['package_id'] ?? [];
        $package_names = $_POST['package_name'] ?? [];
        $package_prices = $_POST['package_price'] ?? [];
        $package_durations = $_POST['package_duration'] ?? [];
        $package_destinations = $_POST['package_destinations'] ?? [];
        $package_descriptions = $_POST['package_description'] ?? [];
        
        $total_updated = 0;
        
        for ($i = 0; $i < count($package_ids); $i++) {
            if (!isset($package_ids[$i]) || !isset($package_names[$i]) || !isset($package_prices[$i]) || !isset($package_durations[$i])) {
                continue;
            }
            
            $id = sanitize_input($package_ids[$i]);
            $name = sanitize_input($package_names[$i]);
            $price = sanitize_input($package_prices[$i]);
            $duration = sanitize_input($package_durations[$i]);
            $destinations = isset($package_destinations[$i]) ? sanitize_input($package_destinations[$i]) : '';
            $description = isset($package_descriptions[$i]) ? sanitize_input($package_descriptions[$i]) : '';
            
            // Update package basic info
            $update_package = "UPDATE packages SET name = ?, price = ?, duration = ?, description = ? WHERE package_id = ?";
            $update_pkg_stmt = mysqli_prepare($conn, $update_package);
            mysqli_stmt_bind_param($update_pkg_stmt, "sdssi", $name, $price, $duration, $description, $id);
            
            $pkg_updated = mysqli_stmt_execute($update_pkg_stmt);
            
            // Handle image upload if provided
            if (isset($_FILES['package_image']) && isset($_FILES['package_image']['name'][$i]) && !empty($_FILES['package_image']['name'][$i])) {
                $file_name = $_FILES['package_image']['name'][$i];
                $file_tmp = $_FILES['package_image']['tmp_name'][$i];
                $file_size = $_FILES['package_image']['size'][$i];
                $file_error = $_FILES['package_image']['error'][$i];
                
                // Only process if there's no error
                if ($file_error === 0) {
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed)) {
                        // Create unique filename
                        $new_file_name = 'package_' . $id . '_' . time() . '.' . $file_ext;
                        $upload_path = '../images/' . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            // Update package image in database
                            $update_image = "UPDATE packages SET image = ? WHERE package_id = ?";
                            $update_img_stmt = mysqli_prepare($conn, $update_image);
                            mysqli_stmt_bind_param($update_img_stmt, "si", $new_file_name, $id);
                            mysqli_stmt_execute($update_img_stmt);
                        }
                    }
                }
            }
            
            // Update destinations if provided
            if (!empty($destinations)) {
                // Check if destination_names column exists
                $check_column = "SHOW COLUMNS FROM package_details LIKE 'destination_names'";
                $column_result = mysqli_query($conn, $check_column);
                $column_exists = mysqli_num_rows($column_result) > 0;
                
                // Check if record exists
                $check_record = "SELECT * FROM package_details WHERE package_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_record);
                mysqli_stmt_bind_param($check_stmt, "i", $id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $record_exists = mysqli_num_rows($check_result) > 0;
                
                if ($column_exists) {
                    if ($record_exists) {
                        // Update existing record
                        $update_dest = "UPDATE package_details SET destination_names = ? WHERE package_id = ?";
                        $update_dest_stmt = mysqli_prepare($conn, $update_dest);
                        mysqli_stmt_bind_param($update_dest_stmt, "si", $destinations, $id);
                        mysqli_stmt_execute($update_dest_stmt);
                    } else {
                        // Insert new record
                        $insert_dest = "INSERT INTO package_details (package_id, destination_names) VALUES (?, ?)";
                        $insert_dest_stmt = mysqli_prepare($conn, $insert_dest);
                        mysqli_stmt_bind_param($insert_dest_stmt, "is", $id, $destinations);
                        mysqli_stmt_execute($insert_dest_stmt);
                    }
                }
            }
            
            if ($pkg_updated) {
                $total_updated++;
            }
        }
        
        if ($total_updated > 0) {
            $success_message = "$total_updated packages updated successfully!";
            // Refresh the package list
            $packages = get_packages_by_type($type_id);
        } else {
            $error_message = "No packages were updated.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($type_name); ?> - Adventure Travel.lk Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background-color: rgb(68, 202, 148);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: rgb(4, 39, 37);
            color: #fff;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 60px;
            width: 130px;
            margin-right: 10px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            padding: 5px;
            background-color: rgba(255, 255, 255, 0.74);
            transition: all 0.3s ease;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a.active, .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--secondary-color);
            border-left: 4px solid var(--secondary-color);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .back-btn:hover {
            background-color: #145a55;
        }

        .content-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            font-weight: bold;
        }

        .submit-btn:hover {
            background-color: #145a55;
            transform: translateY(-2px);
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .packages-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .packages-table th, .packages-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        .packages-table th {
            background-color: var(--primary-color);
            color: #fff;
            font-weight: bold;
        }

        .packages-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .packages-table tr:hover {
            background-color: #f5f5f5;
        }

        .edit-link {
            padding: 5px 10px;
            background-color: #f0ad4e;
            color: #fff;
            border: none;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
            margin-bottom: 5px;
        }

        .view-link {
            padding: 5px 10px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        
        .image-upload-wrapper {
            margin-top: 8px;
        }
        
        .custom-file-upload {
            border: 1px solid #ddd;
            display: inline-block;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            background-color: #f8f9fa;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .custom-file-upload:hover {
            background-color: #e9ecef;
        }
        
        .update-btn {
            padding: 5px 10px;
            background-color: var(--success-color);
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
            display: block;
            width: 100%;
            text-align: center;
        }
        
        .update-btn:hover {
            background-color: #218838;
        }
        
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        
        /* Column widths */
        .packages-table th:nth-child(1), .packages-table td:nth-child(1) { width: 140px; }
        .packages-table th:nth-child(2), .packages-table td:nth-child(2) { width: 15%; }
        .packages-table th:nth-child(3), .packages-table td:nth-child(3) { width: 80px; }
        .packages-table th:nth-child(4), .packages-table td:nth-child(4) { width: 100px; }
        .packages-table th:nth-child(5), .packages-table td:nth-child(5) { width: 20%; }
        .packages-table th:nth-child(6), .packages-table td:nth-child(6) { width: 25%; }
        .packages-table th:nth-child(7), .packages-table td:nth-child(7) { width: 110px; }

        /* Responsive design */
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2, .sidebar-menu a span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo-4.png" alt="Adventure Travel.lk Logo">
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="admin.php"><span><i class="fas fa-home"></i> Dashboard</span></a></li>
                    <li><a href="manage_destinations.php"><span><i class="fas fa-map-marker-alt"></i> Manage Destinations</span></a></li>
                    <li><a href="manage_hotels.php"><span><i class="fas fa-hotel"></i> Manage Hotels</span></a></li>
                    <li><a href="manage_policies.php"><span><i class="fas fa-file-alt"></i> Manage Policies</span></a></li>
                    <li><a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Manage Admins</span></a></li>
                    <li><a href="user_messages.php" style="color: #fff; background-color:rgb(0, 0, 0);"><span><i class="fas fa-comment-dots"></i> User Messages</span>
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="badge" style="background-color: white; color: #dc3545; padding: 2px 6px; border-radius: 50%;"><?php echo $unread_messages_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
        <div class="header">
            <h1>Manage <?php echo htmlspecialchars($type_name); ?></h1>
            <a href="admin.php" class="back-btn">Back to Dashboard</a>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
            <div class="content-container">
        <!-- Edit Packages in Batch -->
        <h2>Edit Packages</h2>
        
        <?php if (count($packages) > 0): ?>
            <!-- Batch update form -->
            <form method="POST" enctype="multipart/form-data">
                <table class="packages-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Destinations</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                            <?php $package_destinations = get_package_destinations($package['package_id']); ?>
                            <tr>
                                <td>
                                    <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                    <input type="hidden" name="package_id[]" value="<?php echo $package['package_id']; ?>">
                                    <div class="image-upload-wrapper">
                                        <label class="custom-file-upload">
                                            <input type="file" name="package_image[<?php echo $package['package_id']; ?>]" accept="image/*" style="display: none;">
                                            Change Image
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="package_name[]" class="form-control" value="<?php echo htmlspecialchars($package['name']); ?>">
                                </td>
                                <td>
                                    <input type="number" name="package_price[]" class="form-control" value="<?php echo $package['price']; ?>" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="text" name="package_duration[]" class="form-control" value="<?php echo htmlspecialchars($package['duration']); ?>">
                                </td>
                                <td>
                                    <input type="text" name="package_destinations[]" class="form-control" value="<?php echo htmlspecialchars($package_destinations); ?>" placeholder="e.g. Paris, London, Rome">
                                </td>
                                <td>
                                    <textarea name="package_description[]" class="form-control" rows="3" style="resize: vertical;"><?php echo htmlspecialchars($package['description']); ?></textarea>
                                </td>
                                <td>
                                    <button type="button" class="update-btn" onclick="submitSingleForm(<?php echo $package['package_id']; ?>)">Update</button>
                                    <a href="edit_package.php?id=<?php echo $package['package_id']; ?>" class="edit-link">Detailed Edit</a>
                                    <a href="#" onclick="viewPackage(<?php echo $package['package_id']; ?>); return false;" class="view-link">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="submit" name="batch_update" class="submit-btn">Update All Packages</button>
                </div>
            </form>
            
            <!-- Hidden form for individual package updates -->
            <form id="single-update-form" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="hidden" id="single_package_id" name="package_id">
                <input type="hidden" id="single_package_name" name="package_name">
                <input type="hidden" id="single_package_price" name="package_price">
                <input type="hidden" id="single_package_duration" name="package_duration">
                <input type="hidden" id="single_package_destinations" name="package_destinations">
                <input type="hidden" id="single_package_description" name="package_description">
                <input type="file" id="single_package_image" name="package_image" accept="image/*">
                <input type="hidden" name="single_update" value="1">
            </form>
        <?php else: ?>
            <p>No packages found in this category.</p>
        <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function viewPackage(packageId) {
            window.open('admin.php?view_package=' + packageId, '_blank');
        }
        
        function submitSingleForm(packageId) {
            // Get the row elements for this package
            const row = document.querySelector(`input[name="package_id[]"][value="${packageId}"]`).closest('tr');
            
            // Get all form values from the row
            const name = row.querySelector(`input[name="package_name[]"]`).value;
            const price = row.querySelector(`input[name="package_price[]"]`).value;
            const duration = row.querySelector(`input[name="package_duration[]"]`).value;
            const destinations = row.querySelector(`input[name="package_destinations[]"]`).value;
            const description = row.querySelector(`textarea[name="package_description[]"]`).value;
            const imageInput = row.querySelector(`input[type="file"]`);
            
            // Set values in the hidden form
            document.getElementById('single_package_id').value = packageId;
            document.getElementById('single_package_name').value = name;
            document.getElementById('single_package_price').value = price;
            document.getElementById('single_package_duration').value = duration;
            document.getElementById('single_package_destinations').value = destinations;
            document.getElementById('single_package_description').value = description;
            
            // Handle the file if selected
            if (imageInput.files.length > 0) {
                const fileInput = document.getElementById('single_package_image');
                
                // Clear existing files
                fileInput.value = '';
                
                // Create a DataTransfer object to set the File
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(imageInput.files[0]);
                fileInput.files = dataTransfer.files;
            }
            
            // Submit the form
            document.getElementById('single-update-form').submit();
        }
    </script>
</body>
</html> 