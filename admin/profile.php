<?php
// admin/profile.php
require_once '../includes/config.php';

// Ensure $conn is defined
if (!isset($conn)) {
    die("Database connection not established. Please check your configuration.");
}

// Check session status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: admin/index.php');
    exit();
}

// Include functions
require_once '../includes/functions.php';
$functions = new Functions();

// Get current user data
$user_id = $_SESSION['user_id'];
$user = $functions->getUserById($user_id);

// Initialize variables
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        
        if (empty($fullname)) {
            $error = "Full name is required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address!";
        } else {
            // Update user profile
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $fullname, $email, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $email;
                $user['fullname'] = $fullname;
                $user['email'] = $email;
                $success = "Profile updated successfully!";
            } else {
                $error = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required!";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match!";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect!";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters long!";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EVSU-OCC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(to right, #8B0000, #A52A2A);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Profile Layout */
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #8B0000, #A52A2A);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .profile-info {
            margin-top: 20px;
        }
        
        .profile-info p {
            margin: 10px 0;
            color: #666;
        }
        
        .profile-info strong {
            color: #333;
            display: inline-block;
            min-width: 100px;
        }
        
        /* Main Content */
        .profile-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: #8B0000;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #8B0000;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }
        
        /* Buttons */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(to right, #8B0000, #A52A2A);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #A52A2A, #8B0000);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 25px;
        }
        
        .tab {
            padding: 12px 25px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #666;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #8B0000;
            border-bottom: 2px solid #8B0000;
        }
        
        .tab:hover:not(.active) {
            color: #333;
            background: #f9f9f9;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Back Link */
        .back-link {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .back-link a {
            color: #8B0000;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <p>Manage your account information and settings</p>
            </div>
            <a href="../admin/index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?php echo htmlspecialchars(isset($user['fullname']) && !empty($user['fullname']) ? $user['fullname'] : 'User'); ?></h2>
                <p style="color: #8B0000; margin: 10px 0;">
    <i class="fas fa-shield-alt"></i> 
    <?php echo htmlspecialchars(ucfirst(isset($user['role']) ? $user['role'] : 'user')); ?>
</p>
                
                <div class="profile-info">
                    <p><strong><i class="fas fa-user"></i> Username:</strong> 
                       <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong> 
                       <?php echo htmlspecialchars($user['email']); ?></p>
                       <p><strong><i class="fas fa-id-card"></i> Student ID:</strong> 
                       <?php echo htmlspecialchars(isset($user['student_id']) && !empty($user['student_id']) ? $user['student_id'] : 'N/A'); ?></p>
                    <p><strong><i class="fas fa-calendar"></i> Joined:</strong> 
                       <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="logout.php" class="btn btn-danger" style="width: 100%;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="profile-content">
                <!-- Tabs -->
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('profile')">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </div>
                    <div class="tab" onclick="switchTab('password')">
                        <i class="fas fa-key"></i> Change Password
                    </div>
                </div>
                
                <!-- Edit Profile Tab -->
                <div id="profile-tab" class="tab-content active">
                    <h3 class="section-title"><i class="fas fa-user-edit"></i> Edit Profile Information</h3>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" 
       value="<?php echo htmlspecialchars(isset($user['fullname']) ? $user['fullname'] : ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username (Cannot be changed)</label>
                            <input type="text" id="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                Username cannot be changed for security reasons.
                            </small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Change Password Tab -->
                <div id="password-tab" class="tab-content">
                    <h3 class="section-title"><i class="fas fa-key"></i> Change Password</h3>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" required>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                Password must be at least 8 characters long.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <!-- Activity Log Tab -->
                <div id="activity-tab" class="tab-content">
                    <h3 class="section-title"><i class="fas fa-history"></i> Recent Activity</h3>
                    
                    <p>Your recent activity will appear here.</p>
                    <!-- You can add activity log functionality later -->
                </div>
                
                
</div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate clicked tab
            event.target.classList.add('active');
        }
        
        // Password strength indicator (optional enhancement)
        const newPassword = document.getElementById('new_password');
        if (newPassword) {
            newPassword.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                // You can add a visual strength indicator here
            });
        }
        
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            return strength;
        }
    </script>
</body>
</html>