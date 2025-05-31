<?php
// Include database configuration
require_once 'config.php';
require_once 'chat_functions.php';

// Check if admin is logged in
require_admin_login();

// Get unread messages count for admin dashboard
$unread_messages_count = get_unread_count(0, true);

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new admin
    if (isset($_POST['add_admin'])) {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $full_name = sanitize_input($_POST['full_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            $error_message = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            // Check if username already exists
            $check_query = "SELECT COUNT(*) as count FROM admins WHERE username = ? OR email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $existing = mysqli_fetch_assoc($check_result)['count'];
            
            if ($existing > 0) {
                $error_message = "Username or email already exists.";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new admin
                $insert_query = "INSERT INTO admins (username, password, email, full_name) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $password_hash, $email, $full_name);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_message = "New admin account created successfully.";
                } else {
                    $error_message = "Error creating admin account: " . mysqli_error($conn);
                }
            }
        }
    }
    
    // Delete admin
    if (isset($_POST['delete_admin'])) {
        $admin_id = intval($_POST['admin_id']);
        
        // Cannot delete your own account
        if ($admin_id == $_SESSION['admin_id']) {
            $error_message = "You cannot delete your own account.";
        } else {
            $delete_query = "DELETE FROM admins WHERE admin_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $admin_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $success_message = "Admin account deleted successfully.";
            } else {
                $error_message = "Error deleting admin account: " . mysqli_error($conn);
            }
        }
    }
}

// Get all admin accounts
$admins_query = "SELECT * FROM admins ORDER BY username";
$admins_result = mysqli_query($conn, $admins_query);
$admins = [];
while ($admin = mysqli_fetch_assoc($admins_result)) {
    $admins[] = $admin;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Adventure Travel.lk</title>
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

        .admin-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: var(--primary-color);
            color: #fff;
            font-weight: 600;
        }

        table tr:hover {
            background-color: rgba(101, 255, 193, 0.1);
        }

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

        .add-btn, .form-submit, .btn-primary {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .add-btn:hover, .form-submit:hover, .btn-primary:hover {
            background-color: #145a55;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: #fff;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Modal Styles */
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
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            color: var(--primary-color);
            margin: 0;
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

        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
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
                    <li><a href="manage_admins.php" class="active"><span><i class="fas fa-users-cog"></i> Manage Admins</span></a></li>
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
                <h1>Manage Admin Accounts</h1>
                <a href="admin.php" class="add-btn">Back to Dashboard</a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <!-- Add Admin Section -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>Add New Admin</h2>
                    <button class="add-btn" onclick="openModal('addAdminModal')">Add New Admin</button>
                </div>

                <div class="admin-list">
                    <?php if (!empty($admins)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo $admin['admin_id']; ?></td>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                                if (!empty($admin['last_login'])) {
                                                    echo date('M d, Y H:i', strtotime($admin['last_login']));
                                                } else {
                                                    echo 'Never';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                                                <button class="btn-danger" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">Delete</button>
                                            <?php else: ?>
                                                <span style="color: #666;">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No admin accounts found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div id="addAdminModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Admin</h2>
                <span class="close-btn" onclick="closeModal('addAdminModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" name="add_admin" class="form-submit">Add Admin</button>
            </form>
        </div>
    </div>

    <!-- Delete Admin Confirmation Modal -->
    <div id="deleteAdminModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteAdminModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the admin: <span id="deleteAdminName"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('deleteAdminModal')">Cancel</button>
                <form id="deleteAdminForm" method="POST">
                    <input type="hidden" id="deleteAdminId" name="admin_id">
                    <input type="hidden" name="delete_admin" value="1">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }

        // Delete admin confirmation
        function deleteAdmin(adminId, username) {
            document.getElementById('deleteAdminId').value = adminId;
            document.getElementById('deleteAdminName').textContent = username;
            openModal('deleteAdminModal');
        }
    </script>
</body>
</html> 