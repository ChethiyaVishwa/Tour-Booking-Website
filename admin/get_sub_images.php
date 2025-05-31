<?php
// Include database configuration and helper functions
require_once 'config.php';
require_admin_login();

// Check if destination ID is provided
if (!isset($_GET['destination_id']) || empty($_GET['destination_id'])) {
    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;">No destination ID provided.</div>';
    exit;
}

$destination_id = intval($_GET['destination_id']);

// Get destination name
$name_query = "SELECT name FROM destinations WHERE destination_id = ?";
$name_stmt = mysqli_prepare($conn, $name_query);
mysqli_stmt_bind_param($name_stmt, "i", $destination_id);
mysqli_stmt_execute($name_stmt);
$name_result = mysqli_stmt_get_result($name_stmt);

if (mysqli_num_rows($name_result) == 0) {
    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;">Destination not found.</div>';
    exit;
}

$destination_name = mysqli_fetch_assoc($name_result)['name'];

// Get all sub-destinations
$sub_query = "SELECT * FROM destination_sub_images WHERE destination_id = ? ORDER BY name";
$sub_stmt = mysqli_prepare($conn, $sub_query);
mysqli_stmt_bind_param($sub_stmt, "i", $destination_id);
mysqli_stmt_execute($sub_stmt);
$sub_result = mysqli_stmt_get_result($sub_stmt);

if (mysqli_num_rows($sub_result) == 0) {
    echo '<div style="background-color: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0;">No sub-destinations found for ' . htmlspecialchars($destination_name) . '.</div>';
    exit;
}
?>

<h3 style="margin-bottom: 20px;"><?php echo htmlspecialchars($destination_name); ?> - Sub-Destinations</h3>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <?php while ($sub = mysqli_fetch_assoc($sub_result)): ?>
        <div class="destination-card" style="background-color:rgb(123, 255, 222); border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease;">
            <div class="destination-image" style="height: 180px; overflow: hidden;">
                <img src="../destinations/sub/<?php echo htmlspecialchars($sub['image']); ?>" 
                     alt="<?php echo htmlspecialchars($sub['name']); ?>"
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div style="padding: 15px;">
                <h3><?php echo htmlspecialchars($sub['name']); ?></h3>
                <p><?php echo substr(htmlspecialchars($sub['description']), 0, 100) . (strlen($sub['description']) > 100 ? '...' : ''); ?></p>
                <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                    <button class="edit-btn edit-sub-btn" style="background-color: #f0ad4e; color: #fff; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; font-size: 0.8rem; transition: all 0.3s ease;"
                            data-id="<?php echo $sub['sub_image_id']; ?>"
                            data-name="<?php echo htmlspecialchars($sub['name']); ?>"
                            data-description="<?php echo htmlspecialchars($sub['description']); ?>"
                            data-image="<?php echo htmlspecialchars($sub['image']); ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="delete-btn delete-sub-btn" style="background-color: #dc3545; color: #fff; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; font-size: 0.8rem; transition: all 0.3s ease;"
                            data-id="<?php echo $sub['sub_image_id']; ?>">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div> 