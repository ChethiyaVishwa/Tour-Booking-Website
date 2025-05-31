<?php
// Include database configuration and helper functions
require_once 'config.php';

// Check if admin is logged in
require_admin_login();

// Start the session if one doesn't exist already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle message actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $success = false;
    $action_type = '';
    
    // Mark message as read
    if (isset($_POST['mark_read'])) {
        $message_id = intval($_POST['message_id']);
        $update_query = "UPDATE contact_messages SET is_read = 1, read_at = NOW() WHERE message_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $message_id);
        $success = mysqli_stmt_execute($stmt);
        $action_type = 'mark_read';
    }
    
    // Mark message as responded
    if (isset($_POST['mark_responded'])) {
        $message_id = intval($_POST['message_id']);
        $update_query = "UPDATE contact_messages SET is_responded = 1, responded_at = NOW() WHERE message_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $message_id);
        $success = mysqli_stmt_execute($stmt);
        $action_type = 'mark_responded';
    }
    
    // Delete message
    if (isset($_POST['delete_message'])) {
        $message_id = intval($_POST['message_id']);
        $delete_query = "DELETE FROM contact_messages WHERE message_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $message_id);
        $success = mysqli_stmt_execute($stmt);
        $action_type = 'delete';
    }
    
    // Store action result in session and redirect
    if ($action_type) {
        $_SESSION['message_action'] = [
            'type' => $action_type,
            'success' => $success
        ];
        
        // Redirect to the same page to prevent form resubmission
        header("Location: contact_messages.php");
        exit;
    }
}

// Get unread messages count
$unread_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_row = mysqli_fetch_assoc($unread_result);
$unread_count = $unread_row['count'];

// Get all contact messages
$messages_query = "SELECT * FROM contact_messages ORDER BY submitted_at DESC";
$messages_result = mysqli_query($conn, $messages_query);
$messages = [];
if ($messages_result) {
    while ($message = mysqli_fetch_assoc($messages_result)) {
        $messages[] = $message;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Dashboard</title>
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
            --warning-color: #ffc107;
            --info-color: #17a2b8;
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

        .sidebar-menu a i {
            margin-right: 10px;
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

        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .admin-user span {
            font-weight: bold;
        }

        .logout-btn {
            margin-left: 15px;
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

        /* Messages Section */
        .messages-section {
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

        .section-header h2 {
            color: var(--primary-color);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }

        .badge-primary {
            background-color: var(--primary-color);
        }

        .badge-danger {
            background-color: var(--danger-color);
        }

        .badge-warning {
            background-color: var(--warning-color);
        }

        .badge-success {
            background-color: var(--success-color);
        }

        .messages-table {
            width: 100%;
            border-collapse: collapse;
        }

        .messages-table th, .messages-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .messages-table th {
            background-color: var(--light-color);
            font-weight: bold;
            color: var(--dark-color);
        }

        .messages-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .messages-table tr.unread {
            background-color: rgba(23, 108, 101, 0.1);
            font-weight: bold;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .view-btn {
            background-color: var(--info-color);
            color: #fff;
        }

        .view-btn:hover {
            background-color: #138496;
        }

        .respond-btn {
            background-color: var(--primary-color);
            color: #fff;
        }

        .respond-btn:hover {
            background-color: #145a55;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: #fff;
        }

        .delete-btn:hover {
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
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 70%;
            max-width: 800px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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

        .empty-state {
            text-align: center;
            padding: 40px 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: #666;
        }

        /* Animation for highlighting buttons */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsive Styles */
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
                text-align: center;
            }

            .sidebar-header h2, .sidebar-menu a span {
                display: none;
            }

            .sidebar-menu a i {
                margin-right: 0;
                font-size: 1.3rem;
            }

            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .admin-user {
                width: 100%;
                justify-content: space-between;
            }

            .modal-content {
                width: 95%;
                margin: 2% auto;
            }

            .messages-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
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
                    <li><a href="admin.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="admin.php#tour-packages-section"><i class="fas fa-suitcase"></i> <span>Tour Packages</span></a></li>
                    <li><a href="admin.php#one-day-tour-packages-section"><i class="fas fa-clock"></i> <span>One Day Tours</span></a></li>
                    <li><a href="admin.php#special-tour-packages-section"><i class="fas fa-star"></i> <span>Special Tours</span></a></li>
                    <li><a href="admin.php#vehicles-section"><i class="fas fa-car"></i> <span>Vehicles</span></a></li>
                    <li><a href="user_messages.php"><i class="fas fa-comment-dots"></i> <span>Chat Messages</span></a></li>
                    <li><a href="contact_messages.php" class="active"><i class="fas fa-envelope"></i> <span>Contact Messages</span></a></li>
                    <li><a href="admin.php?logout=1"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Contact Form Messages</h1>
                <div class="admin-user">
                    <?php if ($unread_count > 0): ?>
                    <span class="badge badge-danger"><?php echo $unread_count; ?> Unread Messages</span>
                    <?php endif; ?>
                    <div style="display: flex; align-items: center;">
                        <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Admin">
                        <span style="margin: 0 10px;"><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin User'; ?></span>
                        <a href="admin.php?logout=1" class="logout-btn">Logout</a>
                    </div>
                </div>
            </div>

            <?php
            // Display action success/error messages
            if (isset($_SESSION['message_action'])) {
                $action = $_SESSION['message_action'];
                $action_name = '';
                $icon_class = '';
                
                switch ($action['type']) {
                    case 'mark_read':
                        $action_name = 'marked as read';
                        $icon_class = 'fa-check-circle';
                        break;
                    case 'mark_responded':
                        $action_name = 'marked as responded';
                        $icon_class = 'fa-reply';
                        break;
                    case 'delete':
                        $action_name = 'deleted';
                        $icon_class = 'fa-trash';
                        break;
                }
                
                if ($action['success']) {
                    echo '<div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; display: flex; align-items: center; gap: 10px;">';
                    echo '<i class="fas '.$icon_class.'" style="font-size: 1.5rem;"></i>';
                    echo '<span>Message successfully '.$action_name.'.</span>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; display: flex; align-items: center; gap: 10px;">';
                    echo '<i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>';
                    echo '<span>Error: Unable to '.$action['type'].' message.</span>';
                    echo '</div>';
                }
                
                // Clear the message from session
                unset($_SESSION['message_action']);
            }
            ?>

            <!-- Messages Section -->
            <div class="messages-section">
                <div class="section-header">
                    <h2><i class="fas fa-envelope"></i> Contact Messages</h2>
                </div>

                <?php if (count($messages) > 0): ?>
                <div class="table-responsive">
                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="15%">Name</th>
                                <th width="15%">Email</th>
                                <th width="15%">Subject</th>
                                <th width="20%">Date</th>
                                <th width="10%">Status</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                            <tr class="<?php echo $message['is_read'] ? '' : 'unread'; ?>">
                                <td><?php echo $message['message_id']; ?></td>
                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                <td><?php echo htmlspecialchars($message['subject'] ?: 'No Subject'); ?></td>
                                <td><?php echo date('M d, Y - h:i A', strtotime($message['submitted_at'])); ?></td>
                                <td>
                                    <?php if (!$message['is_read']): ?>
                                    <span class="badge badge-danger">Unread</span>
                                    <?php elseif (!$message['is_responded']): ?>
                                    <span class="badge badge-warning">Not Responded</span>
                                    <?php else: ?>
                                    <span class="badge badge-success">Responded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewMessage(<?php echo $message['message_id']; ?>)">View</button>
                                    <?php if (!$message['is_responded']): ?>
                                    <button class="action-btn respond-btn" onclick="openEmailClient('<?php echo htmlspecialchars($message['email']); ?>', '<?php echo htmlspecialchars($message['subject'] ?: 'Re: Your message to Adventure Travel'); ?>')">Respond</button>
                                    <?php endif; ?>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $message['message_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No contact messages yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Message Modal -->
    <div id="viewMessageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Message Details</h2>
                <span class="close-btn" onclick="closeModal('viewMessageModal')">&times;</span>
            </div>
            <div id="messageDetails">
                <!-- Message details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer" style="margin-top: 20px; display: flex; justify-content: space-between;">
                <form id="markReadForm" method="POST">
                    <input type="hidden" id="markReadMessageId" name="message_id">
                    <input type="hidden" name="mark_read" value="1">
                    <button type="submit" class="action-btn view-btn">Mark as Read</button>
                </form>
                <form id="markRespondedForm" method="POST">
                    <input type="hidden" id="markRespondedMessageId" name="message_id">
                    <input type="hidden" name="mark_responded" value="1">
                    <button type="submit" class="action-btn respond-btn">Mark as Responded</button>
                </form>
                <div>
                    <button class="action-btn delete-btn" onclick="closeModal('viewMessageModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <span class="close-btn" onclick="closeModal('deleteConfirmModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this message?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="action-btn view-btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <form id="deleteMessageForm" method="POST">
                    <input type="hidden" id="deleteMessageId" name="message_id">
                    <input type="hidden" name="delete_message" value="1">
                    <button type="submit" class="action-btn delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Response Confirmation Modal -->
    <div id="responseConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Mark as Responded</h2>
                <span class="close-btn" onclick="closeModal('responseConfirmModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-right: 15px;"></i>
                    <div>
                        <p style="font-size: 1.1rem; margin-bottom: 5px;">Email client has been opened.</p>
                        <p>Would you like to mark this message as responded?</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button class="action-btn view-btn" style="background: #ccc; margin-right: 10px;" onclick="closeModal('responseConfirmModal')">Not Yet</button>
                <form id="responseConfirmForm" method="POST">
                    <input type="hidden" id="responseMessageId" name="message_id">
                    <input type="hidden" name="mark_responded" value="1">
                    <button type="submit" class="action-btn respond-btn">Mark as Responded</button>
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

        // View message details
        function viewMessage(messageId) {
            openModal('viewMessageModal');
            
            // Set message ID for the mark read and mark responded forms
            document.getElementById('markReadMessageId').value = messageId;
            document.getElementById('markRespondedMessageId').value = messageId;
            
            // Get message details
            fetch('get_contact_message.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('messageDetails').innerHTML = `
                            <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                                <p><strong>From:</strong> ${data.message.name} &lt;${data.message.email}&gt;</p>
                                <p><strong>Subject:</strong> ${data.message.subject || 'No Subject'}</p>
                                <p><strong>Date:</strong> ${new Date(data.message.submitted_at).toLocaleString()}</p>
                                <p><strong>IP Address:</strong> ${data.message.ip_address || 'Not recorded'}</p>
                            </div>
                            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; white-space: pre-wrap;">
                                ${data.message.message}
                            </div>
                        `;
                        
                        // Show/hide Mark as Read button based on current status
                        const markReadForm = document.getElementById('markReadForm');
                        if (data.message.is_read == 1) {
                            markReadForm.style.display = 'none';
                        } else {
                            markReadForm.style.display = 'block';
                        }
                        
                        // Show/hide Mark as Responded button based on current status
                        const markRespondedForm = document.getElementById('markRespondedForm');
                        if (data.message.is_responded == 1) {
                            markRespondedForm.style.display = 'none';
                        } else {
                            markRespondedForm.style.display = 'block';
                        }
                        
                        // Add status indicator in the modal
                        let statusHtml = '';
                        if (!data.message.is_read) {
                            statusHtml = '<span class="badge badge-danger">Unread</span>';
                        } else if (!data.message.is_responded) {
                            statusHtml = '<span class="badge badge-warning">Not Responded</span>';
                        } else {
                            statusHtml = '<span class="badge badge-success">Responded</span>';
                        }
                        
                        // Insert status before message content
                        const statusElement = document.createElement('div');
                        statusElement.style.marginBottom = '15px';
                        statusElement.innerHTML = '<p><strong>Status:</strong> ' + statusHtml + '</p>';
                        document.getElementById('messageDetails').insertBefore(statusElement, document.getElementById('messageDetails').firstChild);
                        
                        // Update read status if unread
                        if (!data.message.is_read) {
                            // Instead of auto-submitting, just highlight the button
                            const markReadBtn = document.querySelector('#markReadForm button');
                            markReadBtn.style.backgroundColor = '#28a745';
                            markReadBtn.style.animation = 'pulse 1.5s infinite';
                        }
                    } else {
                        document.getElementById('messageDetails').innerHTML = '<p class="error">Error: ' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching message details:', error);
                    document.getElementById('messageDetails').innerHTML = '<p class="error">Error: Could not load message details.</p>';
                });
        }

        // Confirm delete message
        function confirmDelete(messageId) {
            document.getElementById('deleteMessageId').value = messageId;
            openModal('deleteConfirmModal');
        }

        // Open email client to respond
        function openEmailClient(email, subject) {
            // Store the message ID for the response confirmation
            document.getElementById('responseMessageId').value = email;
            
            // Open email client
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}`;
            
            // Show the response confirmation modal after a short delay
            // to ensure the email client has time to open
            setTimeout(() => {
                openModal('responseConfirmModal');
            }, 500);
        }
    </script>
</body>
</html> 