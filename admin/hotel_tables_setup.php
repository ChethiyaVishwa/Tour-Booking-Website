<?php
// Include database configuration
require_once 'config.php';

// Check if admin is logged in
require_admin_login();

// Create hotel_sub_images table
$create_sub_images_table = "
CREATE TABLE IF NOT EXISTS `hotel_sub_images` (
  `sub_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sub_image_id`),
  KEY `hotel_id` (`hotel_id`),
  CONSTRAINT `hotel_sub_images_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// Create hotel_sub_details table
$create_sub_details_table = "
CREATE TABLE IF NOT EXISTS `hotel_sub_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `header` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `order_num` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`detail_id`),
  KEY `hotel_id` (`hotel_id`),
  CONSTRAINT `hotel_sub_details_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// Execute queries
$success = true;
$messages = [];

if (!mysqli_query($conn, $create_sub_images_table)) {
    $success = false;
    $messages[] = "Error creating hotel_sub_images table: " . mysqli_error($conn);
}

if (!mysqli_query($conn, $create_sub_details_table)) {
    $success = false;
    $messages[] = "Error creating hotel_sub_details table: " . mysqli_error($conn);
}

// Show success or error messages
if ($success) {
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>✓ Tables created successfully!</h3>";
    echo "<p>The hotel_sub_images and hotel_sub_details tables have been created.</p>";
    echo "<p><a href='manage_hotels.php' style='color: #155724; text-decoration: underline;'>Go to Manage Hotels</a></p>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>✗ Error creating tables</h3>";
    echo "<ul>";
    foreach ($messages as $message) {
        echo "<li>$message</li>";
    }
    echo "</ul>";
    echo "<p><a href='admin.php' style='color: #721c24; text-decoration: underline;'>Go back to Admin Dashboard</a></p>";
    echo "</div>";
}
?> 