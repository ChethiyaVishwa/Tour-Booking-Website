<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';

// Turn off all error reporting to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Check if admin is logged in
require_admin_login();

// Set JSON header
header('Content-Type: application/json');

// Check if ID parameter exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid package ID']);
    exit;
}

$package_id = (int)$_GET['id'];
try {
    $package = get_package_details($package_id);

    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    // Get all package details from the database
    global $conn;
    $details_query = "SELECT * FROM package_details WHERE package_id = ?";
    $stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($stmt, "i", $package_id);
    mysqli_stmt_execute($stmt);
    $details_result = mysqli_stmt_get_result($stmt);
    
    $package_details = [];
    while ($row = mysqli_fetch_assoc($details_result)) {
        $package_details[] = $row;
    }

    // Determine which table structure we're using based on package data
    $using_formatted_data = is_string($package['inclusions'] ?? '') && strpos($package['inclusions'] ?? '', '|') !== false;

    // Format destinations from package data (if available)
    $destinations = $package['destinations'] ? $package['destinations'] : '';
    
    // Check if we have destinations from package_details table
    $destination_names_from_details = '';
    foreach ($package_details as $detail) {
        if (isset($detail['destination_names']) && !empty($detail['destination_names'])) {
            $destination_names_from_details = $detail['destination_names'];
            break;
        }
    }
    
    // Use package_details destinations if available, otherwise use package destinations
    $destinations = !empty($destination_names_from_details) ? $destination_names_from_details : $destinations;
    
    // Default to "None" if no destinations are set
    $destinations = !empty($destinations) ? $destinations : 'None';

    // Format inclusions, exclusions, itinerary, and additional images
    if ($using_formatted_data) {
        // For standard structure (with detail_type and detail_value)
        $inclusions = $package['inclusions'] ? explode('|', $package['inclusions']) : [];
        $exclusions = $package['exclusions'] ? explode('|', $package['exclusions']) : [];
        $itinerary = $package['itinerary'] ? explode('|', $package['itinerary']) : [];
        $additional_images = $package['images'] ? explode('|', $package['images']) : [];
    } else {
        // For custom structure with direct table fields
        // Initialize arrays for our view sections
        $inclusions = [];
        $exclusions = [];
        $itinerary = [];
        $additional_images = [];
        
        // Add any non-empty fields from the details table
        if (isset($package['activities']) && !empty($package['activities'])) {
            $inclusions[] = "Activities: " . $package['activities'];
        }
        if (isset($package['meal_plan']) && !empty($package['meal_plan'])) {
            $inclusions[] = "Meal Plan: " . $package['meal_plan'];
        }
        if (isset($package['transport_type']) && !empty($package['transport_type'])) {
            $inclusions[] = "Transport Type: " . $package['transport_type'];
        }
        
        // Add description to itinerary if available
        if (isset($package['detail_description']) && !empty($package['detail_description'])) {
            $itinerary[] = $package['detail_description'];
        }
        
        // Add image to additional images if available
        if (isset($package['detail_image']) && !empty($package['detail_image']) && $package['detail_image'] != $package['image']) {
            $additional_images[] = $package['detail_image'];
        }
    }

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
        </div>';

    // If we have package details from our separate query, display them
    if (!empty($package_details)) {
        $html .= '
        <div class="package-section">
            <h4>Package Details</h4>
            <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                <thead>
                    <tr style="background-color:#f8f8f8; border-bottom:2px solid #ddd;">
                        <th style="text-align:left; padding:10px;">Day</th>
                        <th style="text-align:left; padding:10px;">Title</th>
                        <th style="text-align:left; padding:10px;">Description</th>
                        <th style="text-align:left; padding:10px;">Meal Plan</th>
                        <th style="text-align:left; padding:10px;">Activities</th>
                        <th style="text-align:left; padding:10px;">Transport</th>
                    </tr>
                </thead>
                <tbody>';
        
        // Sort details by day number if available
        if (!empty($package_details[0]['day_number'])) {
            usort($package_details, function($a, $b) {
                return $a['day_number'] <=> $b['day_number'];
            });
        }
        
        foreach ($package_details as $detail) {
            $html .= '
            <tr style="border-bottom:1px solid #eee;">
                <td style="text-align:left; padding:8px;">' . (isset($detail['day_number']) ? htmlspecialchars($detail['day_number']) : '-') . '</td>
                <td style="text-align:left; padding:8px;">' . (isset($detail['title']) ? htmlspecialchars($detail['title']) : '-') . '</td>
                <td style="text-align:left; padding:8px;">' . (isset($detail['description']) ? htmlspecialchars($detail['description']) : '-') . '</td>
                <td style="text-align:left; padding:8px;">' . (isset($detail['meal_plan']) ? htmlspecialchars($detail['meal_plan']) : '-') . '</td>
                <td style="text-align:left; padding:8px;">' . (isset($detail['activities']) ? htmlspecialchars($detail['activities']) : '-') . '</td>
                <td style="text-align:left; padding:8px;">' . (isset($detail['transport_type']) ? htmlspecialchars($detail['transport_type']) : '-') . '</td>
            </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
        
        // Display additional images from package details if available
        $detail_images = array_filter(array_column($package_details, 'image'));
        if (!empty($detail_images)) {
            $html .= '
            <div class="package-section">
                <h4>Additional Images</h4>
                <div class="gallery" style="display:flex; flex-wrap:wrap; gap:10px;">';
            foreach ($detail_images as $image) {
                if (!empty($image)) {
                    $html .= '<div class="gallery-item" style="width:200px; height:150px; overflow:hidden;">
                        <img src="../images/' . htmlspecialchars($image) . '" alt="Gallery Image" style="width:100%; height:100%; object-fit:cover;">
                    </div>';
                }
            }
            $html .= '
                </div>
            </div>';
        }
    }

    // Add inclusions if available
    if (!empty($inclusions)) {
        $html .= '
        <div class="package-section">
            <h4>Inclusions</h4>
            <ul>';
        foreach ($inclusions as $inclusion) {
            if (!empty(trim($inclusion))) {
                $html .= '<li>' . htmlspecialchars($inclusion) . '</li>';
            }
        }
        $html .= '
            </ul>
        </div>';
    }

    // Add exclusions if available
    if (!empty($exclusions)) {
        $html .= '
        <div class="package-section">
            <h4>Exclusions</h4>
            <ul>';
        foreach ($exclusions as $exclusion) {
            if (!empty(trim($exclusion))) {
                $html .= '<li>' . htmlspecialchars($exclusion) . '</li>';
            }
        }
        $html .= '
            </ul>
        </div>';
    }

    // Add itinerary if available
    if (!empty($itinerary)) {
        $html .= '
        <div class="package-section">
            <h4>Itinerary</h4>
            <ol>';
        foreach ($itinerary as $day) {
            if (!empty(trim($day))) {
                $html .= '<li>' . htmlspecialchars($day) . '</li>';
            }
        }
        $html .= '
            </ol>
        </div>';
    }
    
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