<?php
require_once '../includes/config.php';

// Ensure $conn is defined and initialized
if (!isset($conn)) {
    die("Database connection not established. Please check the config file.");
}
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

// Check if user is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: ../admin/login.php");
    exit();
}

// Initialize variables at the very beginning
$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Replace ?? with ternary operators
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = 'student'; // Force role to student only
    
    // Validation
    $errors = [];
    
    // Common validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($student_id)) $errors[] = "Student ID is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            // Check if username exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Username already exists";
            } else {
                // Check for duplicate student_id or email
                $check2_stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
                $check2_stmt->bind_param("ss", $student_id, $email);
                $check2_stmt->execute();
                
                if ($check2_stmt->get_result()->num_rows > 0) {
                    $error = "Student ID or Email already exists";
                    $check2_stmt->close();
                } else {
                    $check2_stmt->close();
                    createUser($conn, $username, $password, $fullname, $student_id, $email, $role, $success, $error);
                }
            }
            $check_stmt->close();
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

function createUser($conn, $username, $password, $fullname, $student_id, $email, $role, &$success, &$error) {
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, student_id, email, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $username, $hashed_password, $fullname, $student_id, $email, $role);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Failed to create user: " . $conn->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - EVSU Evaluation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: #8B0000;
            color: white;
            padding: 15px 20px;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            height: 50px;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Title Box Styling */
        .title-box {
            background: linear-gradient(135deg, #8B0000 0%, #6b0000 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid #FFC107;
            box-shadow: 0 3px 15px rgba(139, 0, 0, 0.2);
            text-align: center;
        }
        
        .title-box h1 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .title-box h1 i {
            color: #FFC107;
            font-size: 32px;
        }
        
        .title-box .subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .required::after {
            content: " *";
            color: red;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }
        
        /* Student Fields Section */
        .student-fields {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #FFC107;
            border-top: 1px solid #e0e0e0;
        }
        
        .student-fields h3 {
            color: #8B0000;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8B0000 0%, #6b0000 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 0, 0, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #6b0000 0%, #8B0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(139, 0, 0, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(108, 117, 125, 0.3);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid transparent;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .info-badge {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1565c0;
        }
        
        .info-badge i {
            font-size: 24px;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            color: #8B0000;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <img src="../images/EVSU_Official_Logo.png" alt="EVSU Logo">
                <div>
                    <h2>EVSU-OCC</h2>
                    <p style="font-size: 14px; opacity: 0.9;">Administrator Panel</p>
                </div>
            </div>
            <div>
                <a href="index.php" style="color: white; text-decoration: none; margin-right: 15px; padding: 8px 15px; border-radius: 4px; background: rgba(255,255,255,0.1);">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="title-box">
                <h1>
                    <i class="fas fa-user-graduate"></i> Add New Student
                </h1>
                <div class="subtitle">Create a new student account for the evaluation system</div>
            </div>
            
            <!-- Info Badge -->
            <div class="info-badge">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Student Only Registration</strong><br>
                    This form is for creating student accounts only. All new users will have student role by default.
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <div>
                        <strong>Success!</strong> Student account has been created successfully.
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <div>
                        <strong>Error!</strong> <?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="userForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3><i class="fas fa-user-circle"></i> Account Information</h3>
                    
                    <div class="form-group">
                        <label for="username" class="required">Username</label>
                        <input type="text" name="username" id="username" required 
                               placeholder="Enter unique username">
                        <div style="font-size: 14px; color: #666; margin-top: 8px;">
                            <i class="fas fa-key"></i> This will be used for login
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="required">Password</label>
                        <input type="password" name="password" id="password" required 
                               placeholder="Enter password (minimum 8 characters)">
                        <div style="font-size: 14px; color: #666; margin-top: 8px;">
                            <i class="fas fa-lock"></i> Minimum 8 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="fullname" class="required">Full Name</label>
                        <input type="text" name="fullname" id="fullname" required 
                               placeholder="Enter student's complete name">
                    </div>
                </div>
                
                <!-- Student Information -->
                <div class="student-fields">
                    <h3>
                        <i class="fas fa-user-graduate"></i> Student Information
                    </h3>
                    
                    <div class="form-group">
                        <label for="student_id" class="required">Student ID Number</label>
                        <input type="text" name="student_id" id="student_id" required
                               placeholder="e.g., 2023-12345">
                        <div style="font-size: 14px; color: #666; margin-top: 8px;">
                            <i class="fas fa-id-card"></i> Student's official identification number
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="required">Email Address</label>
                        <input type="email" name="email" id="email" required
                               placeholder="student@evsu.edu.ph">
                        <div style="font-size: 14px; color: #666; margin-top: 8px;">
                            <i class="fas fa-envelope"></i> Student's official email address
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create Student Account
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Students List
                    </a>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        function resetForm() {
            document.getElementById('userForm').reset();
        }
        
        // Auto-hide success message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.transition = 'opacity 0.5s';
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        if (successAlert.parentNode) successAlert.remove();
                    }, 500);
                }, 5000);
            }
            
            // Set default student ID format hint
            const studentIdField = document.getElementById('student_id');
            if (studentIdField) {
                studentIdField.addEventListener('input', function() {
                    // Optional: Add custom validation for student ID format
                    const value = this.value;
                    if (value && !/^\d{4}-\d{5}$/.test(value)) {
                        this.style.borderColor = '#ffc107';
                    } else {
                        this.style.borderColor = '#ddd';
                    }
                });
            }
        });
    </script>
</body>
</html>