<?php
// Include database configuration and helper functions
require_once 'config.php';

// Check if admin is logged in
require_admin_login();

// Turn off all error reporting to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

// Check if ID parameter exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid package ID']);
    exit;
}

$package_id = (int)$_GET['id'];

try {
    // Get basic package information without using package_details table
    $query = "SELECT p.*, pt.type_name,
                GROUP_CONCAT(DISTINCT d.name SEPARATOR ', ') AS destinations
              FROM packages p
              JOIN package_types pt ON p.type_id = pt.type_id
              LEFT JOIN package_destinations pd ON p.package_id = pd.package_id
              LEFT JOIN destinations d ON pd.destination_id = d.destination_id
              WHERE p.package_id = ?
              GROUP BY p.package_id";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $package_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $package = mysqli_fetch_assoc($result);

    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    // Format destinations
    $destinations = $package['destinations'] ? $package['destinations'] : 'None';

    // Generate HTML for package details
    $html = '
    <div class="package-view">
        <div class="package-view-header">
            <div class="package-view-image">
                <img src="../images/' . htmlspecialchars($package['image']) . '" alt="' . htmlspecialchars($package['name']) . '">
            </div>
            <div class="package-view-info">
                <h3>' . htmlspecialchars($package['name']) . '</h3>
                <div class="package-meta">
                    <span class="price">$' . number_format($package['price'], 2) . '</span>
                    <span class="duration">' . htmlspecialchars($package['duration']) . '</span>
                    <span class="type">' . htmlspecialchars($package['type_name']) . '</span>
                </div>
                <p>' . htmlspecialchars($package['description']) . '</p>
                <div class="destinations">
                    <strong>Destinations:</strong> ' . htmlspecialchars($destinations) . '
                </div>
            </div>
        </div>
        <div class="package-section">
            <h4>Database Issue Detected</h4>
            <p>The package details cannot be displayed completely because the required database table structure is missing or incomplete.</p>
            <p><a href="create_package_details.php?auto_fix=1&package_id=' . $package_id . '" class="action-btn">Click here to fix automatically</a></p>
            <p>Basic package information is displayed above.</p>
        </div>
        <div class="package-view-actions">
            <a href="edit_package.php?id=' . $package_id . '" class="edit-btn">Edit Package</a>
        </div>
    </div>';

    // Return success response with HTML
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving package details: ' . $e->getMessage()
    ]);
}
?> 