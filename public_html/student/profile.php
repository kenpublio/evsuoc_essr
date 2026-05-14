<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require student login
requireLogin();
if (!hasRole('student')) {
    header("Location: ../login.php");
    exit();
}

// Initialize Functions class
$functions = new Functions();
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get database connection
$conn = getDB();

$success = '';
$error = '';

// ============================================
// HANDLE PROFILE UPDATE
// ============================================

// Update Full Name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $fullname = trim($_POST['fullname']);
    
    if (empty($fullname)) {
        $error = "Full name cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->bind_param("si", $fullname, $user_id);
        if ($stmt->execute()) {
            $success = "Name updated successfully!";
            $_SESSION['fullname'] = $fullname;
            $user['fullname'] = $fullname;
        } else {
            $error = "Failed to update name.";
        }
        $stmt->close();
    }
}

// Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill all password fields.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to update password.";
            }
            $update_stmt->close();
        } else {
            $error = "Current password is incorrect.";
        }
        $stmt->close();
    }
}

// Update Profile Picture
// Update Profile Picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($file['size'] > $max_size) {
            $error = "Image size must be less than 2MB.";
        } else {
            // Create uploads directory if not exists
            $upload_dir = '../uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old profile picture if exists
                if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
                    unlink($upload_dir . $user['profile_picture']);
                }
                
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $filename, $user_id);
                if ($stmt->execute()) {
                    $success = "Profile picture updated successfully!";
                    // Update the user array
                    $user['profile_picture'] = $filename;
                } else {
                    $error = "Failed to update profile picture.";
                }
                $stmt->close();
            } else {
                $error = "Failed to upload image.";
            }
        }
    } else {
        $error = "Please select an image to upload.";
    }
}

// Remove Profile Picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    $upload_dir = '../uploads/profile_pictures/';
    if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
        unlink($upload_dir . $user['profile_picture']);
    }
    
    $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $success = "Profile picture removed successfully!";
        $user['profile_picture'] = null;
    } else {
        $error = "Failed to remove profile picture.";
    }
    $stmt->close();
}

$page_title = 'My Profile - EVSU Evaluation';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --evsu-red: #8B0000;
            --evsu-gold: #FFD700;
            --evsu-dark: #1a1a1a;
            --evsu-gray: #f5f5f5;
            --success-green: #28a745;
            --warning-orange: #fd7e14;
            --info-blue: #17a2b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Header */
        .evsu-header {
            background: var(--evsu-red);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            height: 60px;
            width: auto;
        }

        .title-section h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .title-section .subtitle {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Main Container */
        .main-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--evsu-red) 0%, #b71c1c 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .profile-header h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .profile-header p {
            opacity: 0.9;
        }

        /* Profile Picture Section */
        .profile-picture-section {
            text-align: center;
            margin-top: -50px;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            overflow: hidden;
            border: 4px solid white;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar .no-image {
            font-size: 3rem;
            color: var(--evsu-red);
        }

        .profile-picture-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        /* Form Sections */
        .form-section {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .form-section h3 {
            color: var(--evsu-dark);
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3 i {
            color: var(--evsu-red);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--evsu-red);
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }

        .form-control:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--evsu-red) 0%, #b71c1c 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 0, 0, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-outline {
            background: white;
            border: 2px solid var(--evsu-red);
            color: var(--evsu-red);
        }

        .btn-outline:hover {
            background: var(--evsu-red);
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.8rem;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: white;
            opacity: 0.8;
            font-size: 0.9rem;
            margin-top: 40px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <header class="evsu-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../images/EVSU_Official_Logo.png" alt="EVSU Logo" onerror="this.src='https://via.placeholder.com/60x60?text=EVSU'">
                <div class="title-section">
                    <h1>EVSU - Ormoc Campus</h1>
                    <div class="subtitle">Student Profile</div>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="profile-card">
            <div class="profile-header">
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                <p>Manage your personal information and account settings</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin: 20px 30px 0 30px;">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin: 20px 30px 0 30px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <!-- Profile Picture Section -->
            <div class="profile-picture-section">
                <div class="profile-avatar">
                    <?php if (!empty($user['profile_picture']) && file_exists('../uploads/profile_pictures/' . $user['profile_picture'])): ?>
                        <img src="../uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-picture-actions">
                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('profile_picture').click();">
                            <i class="fas fa-camera"></i> Change Photo
                        </button>
                        <input type="hidden" name="update_picture" value="1">
                    </form>
                    
                    <?php if (!empty($user['profile_picture'])): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove your profile picture?');">
                            <button type="submit" name="remove_picture" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <p style="font-size: 0.7rem; color: #999; margin-top: 8px;">JPG, PNG or GIF. Max 2MB</p>
            </div>

            <!-- Student Information (Read Only) -->
            <div class="form-section">
                <h3><i class="fas fa-id-card"></i> Student Information</h3>
                <div class="form-group">
                    <label class="form-label">Student ID</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
            </div>

            <!-- Update Full Name -->
            <div class="form-section">
                <h3><i class="fas fa-user-edit"></i> Edit Full Name</h3>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" name="update_name" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Name
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="form-section">
                <h3><i class="fas fa-key"></i> Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <small style="color: #999;">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p><i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System | All Rights Reserved</p>
    </footer>

    <script>
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) alert.remove();
                }, 500);
            });
        }, 3000);
    </script>
</body>
</html>