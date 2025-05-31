<?php
// Include database configuration if not already included
if (!function_exists('sanitize_input')) {
    require_once 'config.php';
}

// Function to get all packages of a specific type
function get_packages_by_type($type_id) {
    global $conn;
    $query = "SELECT p.*, pt.type_name FROM packages p 
              JOIN package_types pt ON p.type_id = pt.type_id 
              WHERE p.type_id = ? 
              ORDER BY p.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $type_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $packages = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $packages[] = $row;
    }
    
    return $packages;
}

// Function to get a single package with all details
function get_package_details($package_id) {
    global $conn;
    
    // Use prepared statement to prevent SQL injection
    $sql = "SELECT p.*, pt.type_name, GROUP_CONCAT(d.name SEPARATOR ', ') as destinations
            FROM packages p
            LEFT JOIN package_types pt ON p.type_id = pt.type_id
            LEFT JOIN package_destinations pd ON p.package_id = pd.package_id
            LEFT JOIN destinations d ON pd.destination_id = d.destination_id
            WHERE p.package_id = ?
            GROUP BY p.package_id";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $package_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        return false;
    }
}

// Function to add a new package
function add_package($type_id, $name, $description, $price, $duration, $image) {
    global $conn;
    $query = "INSERT INTO packages (type_id, name, description, price, duration, image) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issdss", $type_id, $name, $description, $price, $duration, $image);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    } else {
        return false;
    }
}

// Function to update a package
function update_package($package_id, $name, $description, $price, $duration, $image = null) {
    global $conn;
    
    if ($image) {
        $query = "UPDATE packages SET name = ?, description = ?, price = ?, duration = ?, image = ? WHERE package_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdssi", $name, $description, $price, $duration, $image, $package_id);
    } else {
        $query = "UPDATE packages SET name = ?, description = ?, price = ?, duration = ? WHERE package_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdsi", $name, $description, $price, $duration, $package_id);
    }
    
    return mysqli_stmt_execute($stmt);
}

// Function to delete a package
function delete_package($package_id) {
    global $conn;
    $query = "DELETE FROM packages WHERE package_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $package_id);
    
    return mysqli_stmt_execute($stmt);
}

// Function to add package details
function add_package_detail($package_id, $detail_type, $detail_value, $detail_order = 0) {
    global $conn;
    $query = "INSERT INTO package_details (package_id, detail_type, detail_value, detail_order) 
              VALUES (?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issi", $package_id, $detail_type, $detail_value, $detail_order);
    
    return mysqli_stmt_execute($stmt);
}

// Function to link package to destinations
function link_package_to_destinations($package_id, $destination_ids) {
    global $conn;
    
    // First delete any existing links
    $delete_query = "DELETE FROM package_destinations WHERE package_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $package_id);
    mysqli_stmt_execute($delete_stmt);
    
    // Then add new links
    $success = true;
    foreach ($destination_ids as $destination_id) {
        $insert_query = "INSERT INTO package_destinations (package_id, destination_id) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ii", $package_id, $destination_id);
        if (!mysqli_stmt_execute($insert_stmt)) {
            $success = false;
        }
    }
    
    return $success;
}

// Function to get dashboard statistics
function get_dashboard_stats() {
    global $conn;
    
    // Initialize default values
    $stats = [
        'total_packages' => 0,
        'active_bookings' => 0,
        'registered_users' => 0,
        'total_revenue' => 0
    ];
    
    // Check if packages table exists
    $check_packages = mysqli_query($conn, "SHOW TABLES LIKE 'packages'");
    if (mysqli_num_rows($check_packages) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM packages");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['total_packages'] = $row['total'];
        }
    }
    
    // Check if bookings table exists
    $check_bookings = mysqli_query($conn, "SHOW TABLES LIKE 'bookings'");
    if (mysqli_num_rows($check_bookings) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed' OR status = 'pending'");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['active_bookings'] = $row['total'];
        }
        
        $result = mysqli_query($conn, "SELECT COALESCE(SUM(total_price), 0) AS revenue FROM bookings WHERE status = 'completed'");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['total_revenue'] = $row['revenue'];
        }
    }
    
    // Check if users table exists
    $check_users = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($check_users) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE is_admin = FALSE");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['registered_users'] = $row['total'];
        }
    }
    
    return $stats;
}

// Function to get all destinations
function get_all_destinations() {
    global $conn;
    $query = "SELECT * FROM destinations ORDER BY name";
    
    $result = mysqli_query($conn, $query);
    
    $destinations = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $destinations[] = $row;
    }
    
    return $destinations;
}
?> 