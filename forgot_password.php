<?php
session_start();
require_once 'includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Check if we're receiving POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("POST Data: " . print_r($_POST, true));
}

// Check if connection is valid
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check your config.");
}

// MojoAuth Configuration
$mojoauth_client_id = "test-35495dd3-d963-43a1-ac05-d2437f6cf419";
$mojoauth_authorization_endpoint = "https://evsu-occ-evaluation-system-b70646.auth.mojoauth.com/oauth/authorize?env=test";

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email - use multiple methods for compatibility
    $email = '';
    
    // Method 1: Direct from $_POST (simplest)
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        error_log("Got email from \$_POST['email']: $email");
    }
    // Method 2: Using filter_input with FILTER_SANITIZE_EMAIL
    elseif (($email_input = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)) !== null) {
        $email = trim($email_input);
        error_log("Got email from filter_input: $email");
    }
    
    if (empty($email)) {
        $error = "Please enter your email address!";
        error_log("Error: Email is empty");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
        error_log("Error: Invalid email format: $email");
    } else {
        error_log("Processing email: $email");
        
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id, username, fullname FROM users WHERE email = ?");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
            error_log("Prepare error: " . $conn->error);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "No account found with that email address!";
                error_log("Error: No user found with email: $email");
            } else {
                $user = $result->fetch_assoc();
                error_log("User found: ID={$user['id']}, Username={$user['username']}");
                
                // Generate tokens
                $reset_token = bin2hex(random_bytes(32));
                $state_token = bin2hex(random_bytes(16));
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Ensure table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'password_reset_requests'");
                if ($table_check->num_rows === 0) {
                    // Create table
                    $create_sql = "CREATE TABLE password_reset_requests (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        token VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        user_id INT NOT NULL,
                        state_token VARCHAR(255) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        used TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    
                    if ($conn->query($create_sql)) {
                        error_log("Created password_reset_requests table");
                    } else {
                        $error = "Failed to create table: " . $conn->error;
                        error_log("Create table error: " . $conn->error);
                    }
                }
                
                if (empty($error)) {
                    // Insert reset request
                    $sql = "INSERT INTO password_reset_requests (token, email, user_id, state_token, expires_at) 
                            VALUES (?, ?, ?, ?, ?)";
                    
                    $stmt2 = $conn->prepare($sql);
                    if (!$stmt2) {
                        $error = "Prepare error: " . $conn->error;
                        error_log("Insert prepare error: " . $conn->error);
                    } else {
                        $stmt2->bind_param("ssiss", $reset_token, $email, $user['id'], $state_token, $expires_at);
                        
                        if ($stmt2->execute()) {
                            error_log("Successfully inserted reset request");
                            
                            // Calculate redirect URI
                            $redirect_uri = "http://localhost/vian/mojoauth_reset_callback.php";
                            
                            // Combine state token and reset token for verification
                            $combined_state = $state_token . ':' . $reset_token;
                            
                            // Store in session for verification in callback
                            $_SESSION['reset_email'] = $email;
                            $_SESSION['reset_user_id'] = $user['id'];
                            $_SESSION['expected_state'] = $combined_state;
                            
                            // Build authorization URL
                            $auth_url = $mojoauth_authorization_endpoint;
                            $auth_url .= "&client_id=" . urlencode($mojoauth_client_id);
                            $auth_url .= "&redirect_uri=" . urlencode($redirect_uri);
                            $auth_url .= "&response_type=code";
                            $auth_url .= "&scope=openid%20email%20profile";
                            $auth_url .= "&state=" . urlencode($combined_state);
                            $auth_url .= "&login_hint=" . urlencode($email);
                            
                            error_log("Redirecting to MojoAuth: " . $auth_url);
                            
                            // Redirect to MojoAuth
                            header("Location: " . $auth_url);
                            exit();
                            
                        } else {
                            $error = "Failed to process your request: " . $stmt2->error;
                            error_log("Execute error: " . $stmt2->error);
                        }
                        $stmt2->close();
                    }
                }
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
    <title>EVSU-OCC - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; flex-direction: column; }
        .evsu-header { background: linear-gradient(to right, #8B0000, #A52A2A); color: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .logo-container { display: flex; align-items: center; gap: 15px; }
        .logo-container img { height: 60px; width: auto; }
        .logo-container h1 { font-size: 1.8rem; margin: 0; color: white; }
        .subtitle { font-size: 0.9rem; opacity: 0.9; margin-top: 5px; }
        .main-container { display: flex; justify-content: center; align-items: center; flex: 1; padding: 20px; }
        .reset-card { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); width: 100%; max-width: 500px; padding: 40px; animation: fadeIn 0.5s ease; }
        .reset-header { text-align: center; margin-bottom: 30px; }
        .reset-icon { font-size: 3.5rem; color: #8B0000; margin-bottom: 15px; }
        .reset-title { color: #333; margin-bottom: 10px; font-size: 1.8rem; }
        .reset-subtitle { color: #666; font-size: 0.95rem; line-height: 1.5; }
        .form-group { margin-bottom: 25px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 14px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: all 0.3s; background: #fafafa; }
        .form-control:focus { outline: none; border-color: #8B0000; background: white; box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 0.95rem; animation: fadeIn 0.5s ease; display: flex; align-items: center; gap: 12px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn { width: 100%; padding: 16px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-primary { background: linear-gradient(to right, #8B0000, #A52A2A); color: white; }
        .btn-primary:hover { background: linear-gradient(to right, #A52A2A, #8B0000); transform: translateY(-2px); box-shadow: 0 7px 20px rgba(139, 0, 0, 0.25); }
        .steps { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0; border-left: 4px solid #28a745; }
        .steps h4 { color: #155724; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .step { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
        .step:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .step-number { width: 30px; height: 30px; background: #8B0000; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem; }
        .step-text { flex: 1; color: #666; font-size: 0.9rem; }
        .back-link { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; }
        .back-link a { color: #8B0000; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: color 0.3s; padding: 10px 20px; border-radius: 5px; background: #f8f9fa; }
        .back-link a:hover { color: #A52A2A; background: #e9ecef; text-decoration: none; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .reset-card { max-width: 100%; margin: 10px; padding: 30px; } .reset-title { font-size: 1.6rem; } .form-control { padding: 12px 15px; } .btn { padding: 14px; font-size: 1rem; } }
        @media (max-width: 480px) { .reset-card { padding: 20px; } .reset-title { font-size: 1.4rem; } .reset-icon { font-size: 3rem; } }
    </style>
</head>
<body>
    <div class="evsu-header">
        <div class="header-container">
            <div class="logo-container">
                <img src="images/EVSU_Official_Logo.png" alt="EVSU Logo" onerror="this.src='https://via.placeholder.com/60x60?text=EVSU'">
                <div>
                    <h1>EVSU-OCC</h1>
                    <div class="subtitle">Evaluation Survey System - Reset Password</div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="reset-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="reset-title">Reset Your Password</h2>
                <p class="reset-subtitle">
                    Enter your email to authenticate and set a new password
                </p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           placeholder="Enter your registered email address"
                           autocomplete="email">
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Continue to Reset Password
                </button>
            </form>
            
            <div class="steps">
                <h4><i class="fas fa-list-ol"></i> How it works:</h4>
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">Enter your email address above</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">You'll be redirected to MojoAuth for secure authentication</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">After authentication, you'll return to set a new password</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-text">Enter and confirm your new password</div>
                </div>
            </div>
            
            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const email = document.getElementById('email').value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (!email) {
                        e.preventDefault();
                        showError('Please enter your email address');
                        return false;
                    }
                    
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        showError('Please enter a valid email address');
                        return false;
                    }
                    
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                    submitBtn.disabled = true;
                    return true;
                });
            }
            
            function showError(message) {
                const existingAlert = document.querySelector('.alert');
                if (existingAlert) existingAlert.remove();
                
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> <div>${message}</div>`;
                
                const header = document.querySelector('.reset-header');
                header.parentNode.insertBefore(alertDiv, header.nextSibling);
                alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>