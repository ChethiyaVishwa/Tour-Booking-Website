<?php
// Include database configuration and helper functions
require_once 'config.php';
require_once 'package_functions.php';

// Check if admin is logged in
require_admin_login();

// Initialize variables
$success_message = '';
$error_message = '';

// Handle notifications from URL parameters
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'policy_updated':
            $success_message = "Policy has been successfully updated.";
            break;
        case 'section_added':
            $success_message = "Section has been successfully added.";
            break;
        case 'section_updated':
            $success_message = "Section has been successfully updated.";
            break;
        case 'section_deleted':
            $success_message = "Section has been successfully deleted.";
            break;
    }
}

if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update policy
    if (isset($_POST['update_policy'])) {
        $policy_id = intval($_POST['policy_id']);
        $title = sanitize_input($_POST['title']);
        $content = sanitize_input($_POST['content']);
        
        $update_query = "UPDATE policies SET title = ?, content = ? WHERE policy_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssi", $title, $content, $policy_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: manage_policies.php?policy_id=$policy_id&success=policy_updated");
            exit;
        } else {
            $error_message = "Error updating policy: " . mysqli_error($conn);
        }
    }
    
    // Add policy section
    if (isset($_POST['add_section'])) {
        $policy_id = intval($_POST['policy_id']);
        $title = sanitize_input($_POST['section_title']);
        $content = sanitize_input($_POST['section_content']);
        
        // Get the highest display order for the policy
        $order_query = "SELECT MAX(display_order) as max_order FROM policy_sections WHERE policy_id = ?";
        $order_stmt = mysqli_prepare($conn, $order_query);
        mysqli_stmt_bind_param($order_stmt, "i", $policy_id);
        mysqli_stmt_execute($order_stmt);
        $order_result = mysqli_stmt_get_result($order_stmt);
        $order_row = mysqli_fetch_assoc($order_result);
        $display_order = ($order_row['max_order'] !== null) ? $order_row['max_order'] + 1 : 1;
        
        $insert_query = "INSERT INTO policy_sections (policy_id, title, content, display_order) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "issi", $policy_id, $title, $content, $display_order);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            header("Location: manage_policies.php?policy_id=$policy_id&success=section_added");
            exit;
        } else {
            $error_message = "Error adding section: " . mysqli_error($conn);
        }
    }
    
    // Update policy section
    if (isset($_POST['update_section'])) {
        $section_id = intval($_POST['section_id']);
        $policy_id = intval($_POST['policy_id']); // Make sure to add this hidden field
        $title = sanitize_input($_POST['section_title']);
        $content = sanitize_input($_POST['section_content']);
        $display_order = intval($_POST['display_order']);
        
        $update_query = "UPDATE policy_sections SET title = ?, content = ?, display_order = ? WHERE section_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssii", $title, $content, $display_order, $section_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: manage_policies.php?policy_id=$policy_id&success=section_updated");
            exit;
        } else {
            $error_message = "Error updating section: " . mysqli_error($conn);
        }
    }
    
    // Delete policy section
    if (isset($_POST['delete_section'])) {
        $section_id = intval($_POST['section_id']);
        $policy_id = intval($_POST['policy_id']); // Make sure to add this hidden field
        
        $delete_query = "DELETE FROM policy_sections WHERE section_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $section_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            header("Location: manage_policies.php?policy_id=$policy_id&success=section_deleted");
            exit;
        } else {
            $error_message = "Error deleting section: " . mysqli_error($conn);
        }
    }
}

// Get all policies
$policies_query = "SELECT * FROM policies";
$policies_result = mysqli_query($conn, $policies_query);
$policies = [];
while ($policy = mysqli_fetch_assoc($policies_result)) {
    $policies[] = $policy;
}

// Get active policy
$active_policy_id = isset($_GET['policy_id']) ? intval($_GET['policy_id']) : (isset($policies[0]) ? $policies[0]['policy_id'] : 0);
$active_policy = null;
$policy_sections = [];

if ($active_policy_id > 0) {
    // Get active policy details
    $policy_query = "SELECT * FROM policies WHERE policy_id = ?";
    $policy_stmt = mysqli_prepare($conn, $policy_query);
    mysqli_stmt_bind_param($policy_stmt, "i", $active_policy_id);
    mysqli_stmt_execute($policy_stmt);
    $policy_result = mysqli_stmt_get_result($policy_stmt);
    $active_policy = mysqli_fetch_assoc($policy_result);
    
    // Get policy sections
    $sections_query = "SELECT * FROM policy_sections WHERE policy_id = ? ORDER BY display_order ASC";
    $sections_stmt = mysqli_prepare($conn, $sections_query);
    mysqli_stmt_bind_param($sections_stmt, "i", $active_policy_id);
    mysqli_stmt_execute($sections_stmt);
    $sections_result = mysqli_stmt_get_result($sections_stmt);
    while ($section = mysqli_fetch_assoc($sections_result)) {
        $policy_sections[] = $section;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Policies - Adventure Travel.lk Admin</title>
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
            background-color:rgb(4, 39, 37);
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

        .header h1 {
            color: var(--dark-color);
            font-size: 1.5rem;
        }

        .logout-btn {
            padding: 5px 15px;
            background-color: var(--danger-color);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        /* Policies Management */
        .policy-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            display: flex;
            gap: 20px;
        }

        .policy-sidebar {
            width: 250px;
            flex-shrink: 0;
            border-right: 1px solid #eee;
            padding-right: 20px;
        }

        .policy-selector {
            list-style: none;
        }

        .policy-selector li {
            margin-bottom: 10px;
        }

        .policy-selector a {
            display: block;
            padding: 10px 15px;
            background-color: #f8f9fa;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .policy-selector a.active, .policy-selector a:hover {
            background-color: var(--primary-color);
            color: #fff;
        }

        .policy-content {
            flex: 1;
        }

        .policy-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .policy-header h2 {
            color: var(--primary-color);
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
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
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #145a55;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            z-index: 9999;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease, fadeOut 0.5s ease 4.5s forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-close {
            float: right;
            font-size: 1.2rem;
            font-weight: bold;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: 0.5;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            margin-left: 10px;
        }

        .alert-close:hover {
            opacity: 0.75;
        }

        /* Sections Management */
        .section-container {
            margin-top: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-list {
            margin-top: 20px;
        }

        .section-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            position: relative;
        }

        .section-item h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .section-item p {
            color: #666;
            margin-bottom: 15px;
        }

        .section-actions {
            display: flex;
            gap: 10px;
        }

        .section-order {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary-color);
            color: #fff;
            min-width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 70%;
            max-width: 800px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: var(--primary-color);
        }

        .close-btn {
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--danger-color);
        }

        /* Responsive design */
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }

            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .policy-container {
                flex-direction: column;
            }

            .policy-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
                padding-right: 0;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }

            .policy-selector {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .policy-selector li {
                margin-bottom: 0;
            }

            .modal-content {
                width: 90%;
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
                    <li><a href="manage_policies.php" class="active"><span><i class="fas fa-file-alt"></i> Manage Policies</span></a></li>
                    <li><a href="manage_admins.php"><span><i class="fas fa-users-cog"></i> Manage Admins</span></a></li>
                    <li><a href="user_messages.php" style="color: #fff; background-color:rgb(0, 0, 0);"><span><i class="fas fa-comment-dots"></i> User Messages</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Policies</h1>
                <a href="admin.php" class="btn btn-primary">Back to Dashboard</a>
            </div>

            <?php if (!empty($success_message)): ?>
            <div id="success-alert" class="alert alert-success">
                <button type="button" class="alert-close" onclick="closeAlert('success-alert')">&times;</button>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div id="error-alert" class="alert alert-danger">
                <button type="button" class="alert-close" onclick="closeAlert('error-alert')">&times;</button>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <div class="policy-container">
                <div class="policy-sidebar">
                    <h3>Select Policy</h3>
                    <ul class="policy-selector">
                        <?php foreach ($policies as $policy): ?>
                            <li>
                                <a href="?policy_id=<?php echo $policy['policy_id']; ?>" class="<?php echo ($policy['policy_id'] == $active_policy_id) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($policy['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="policy-content">
                    <?php if ($active_policy): ?>
                    <div class="policy-header">
                        <h2><?php echo htmlspecialchars($active_policy['title']); ?></h2>
                    </div>

                    <form method="POST" action="manage_policies.php?policy_id=<?php echo $active_policy_id; ?>">
                        <div class="form-group">
                            <label for="title">Policy Title</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($active_policy['title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="content">General Content</label>
                            <textarea id="content" name="content" class="form-control"><?php echo htmlspecialchars($active_policy['content']); ?></textarea>
                        </div>
                        <input type="hidden" name="policy_id" value="<?php echo $active_policy_id; ?>">
                        <button type="submit" name="update_policy" class="btn btn-primary">Update Policy</button>
                    </form>

                    <div class="section-container">
                        <div class="section-header">
                            <h3>Policy Sections</h3>
                            <button class="btn btn-primary" onclick="openModal('addSectionModal')">Add New Section</button>
                        </div>

                        <div class="section-list">
                            <?php if (count($policy_sections) > 0): ?>
                                <?php foreach ($policy_sections as $section): ?>
                                    <div class="section-item">
                                        <span class="section-order"><?php echo $section['display_order']; ?></span>
                                        <h3><?php echo htmlspecialchars($section['title']); ?></h3>
                                        <p><?php echo nl2br(htmlspecialchars($section['content'])); ?></p>
                                        <div class="section-actions">
                                            <button class="btn btn-primary" onclick="editSection(<?php echo $section['section_id']; ?>, '<?php echo addslashes($section['title']); ?>', '<?php echo addslashes($section['content']); ?>', <?php echo $section['display_order']; ?>)">Edit</button>
                                            <button class="btn btn-danger" onclick="deleteSection(<?php echo $section['section_id']; ?>, '<?php echo addslashes($section['title']); ?>')">Delete</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No sections found. Add your first section using the "Add New Section" button.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <p>Please select a policy to manage.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Section Modal -->
    <div id="addSectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Policy Section</h2>
                <span class="close-btn" onclick="closeModal('addSectionModal')">&times;</span>
            </div>
            <form method="POST" action="manage_policies.php?policy_id=<?php echo $active_policy_id; ?>">
                <div class="form-group">
                    <label for="section_title">Section Title</label>
                    <input type="text" id="section_title" name="section_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="section_content">Section Content</label>
                    <textarea id="section_content" name="section_content" class="form-control" required></textarea>
                </div>
                <input type="hidden" name="policy_id" value="<?php echo $active_policy_id; ?>">
                <button type="submit" name="add_section" class="btn btn-primary">Add Section</button>
            </form>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div id="editSectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Policy Section</h2>
                <span class="close-btn" onclick="closeModal('editSectionModal')">&times;</span>
            </div>
            <form method="POST" action="manage_policies.php?policy_id=<?php echo $active_policy_id; ?>">
                <div class="form-group">
                    <label for="edit_section_title">Section Title</label>
                    <input type="text" id="edit_section_title" name="section_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_section_content">Section Content</label>
                    <textarea id="edit_section_content" name="section_content" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_display_order">Display Order</label>
                    <input type="number" id="edit_display_order" name="display_order" class="form-control" min="1" required>
                </div>
                <input type="hidden" id="edit_section_id" name="section_id" value="">
                <input type="hidden" name="policy_id" value="<?php echo $active_policy_id; ?>">
                <button type="submit" name="update_section" class="btn btn-primary">Update Section</button>
            </form>
        </div>
    </div>

    <!-- Delete Section Modal -->
    <div id="deleteSectionModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteSectionModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the section: <span id="deleteSectionTitle"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('deleteSectionModal')">Cancel</button>
                <form method="POST" action="manage_policies.php?policy_id=<?php echo $active_policy_id; ?>">
                    <input type="hidden" id="delete_section_id" name="section_id" value="">
                    <input type="hidden" name="policy_id" value="<?php echo $active_policy_id; ?>">
                    <button type="submit" name="delete_section" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
            document.body.style.overflow = "hidden"; // Prevent scrolling when modal is open
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
            document.body.style.overflow = ""; // Restore scrolling
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
                document.body.style.overflow = ""; // Restore scrolling
            }
        }

        // Edit section
        function editSection(sectionId, title, content, order) {
            document.getElementById('edit_section_id').value = sectionId;
            document.getElementById('edit_section_title').value = title;
            document.getElementById('edit_section_content').value = content;
            document.getElementById('edit_display_order').value = order;
            openModal('editSectionModal');
        }

        // Delete section
        function deleteSection(sectionId, title) {
            document.getElementById('delete_section_id').value = sectionId;
            document.getElementById('deleteSectionTitle').textContent = title;
            openModal('deleteSectionModal');
        }

        // Close alert
        function closeAlert(alertId) {
            document.getElementById(alertId).style.display = 'none';
        }

        // Auto-hide alerts after 5 seconds
        window.addEventListener('DOMContentLoaded', function() {
            // Set timeout to hide alerts
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    // Use fadeOut animation that's defined in CSS
                    if (alert) {
                        setTimeout(function() {
                            if (alert.parentNode) {
                                alert.parentNode.removeChild(alert);
                            }
                        }, 5000); // Remove after animation completes
                    }
                });
            }, 5000); // Start fade after 5 seconds
        });
    </script>
</body>
</html> 