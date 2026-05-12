<?php
session_start();
require_once '../includes/config.php';

$error = '';

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password!";
    } else {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                if ($user['is_active'] == 1) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'admin';
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Your account is deactivated. Please contact system administrator.";
                }
            } else {
                $error = "Invalid username or password! Access denied.";
            }
        } else {
            $error = "Invalid username or password! Access denied.";
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-OCC - Admin Portal Login</title>
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
            display: flex;
            flex-direction: column;
        }
        
        .evsu-header {
            background: linear-gradient(to right, #8B0000, #A52A2A);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-container img {
            height: 60px;
            width: auto;
       
         }
        .logo-container h1 {
            font-size: 1.8rem;
            margin: 0;
            color: white;
        }
        
        .subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 15px;
        }
        
        /* Main Container */
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 20px;
        }
        
        /* Auth Card */
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
        /* Form Container */
        .form-container {
            padding: 35px;
        }
        
        .form-title {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.4rem;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px;
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
        
        /* Password Container */
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
        }
        
        /* Alerts */
        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            animation: fadeIn 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Buttons */
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, #8B0000, #A52A2A);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #A52A2A, #8B0000);
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(139, 0, 0, 0.25);
        }
        
        /* Links */
        .form-link {
            color: #8B0000;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .form-link:hover {
            color: #A52A2A;
            text-decoration: underline;
        }
        
        /* Footer Links */
        .form-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* System Status Banner */
        .system-status {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .system-status i {
            color: #2196f3;
            font-size: 1.2rem;
        }
        
        /* Admin Notice */
        .admin-notice {
            background: #fce4ec;
            border-left: 4px solid #e91e63;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-notice i {
            color: #e91e63;
            font-size: 1.2rem;
        }
        
        /* Terms Notice */
        .terms-notice {
            background-color: #f8f9fa;
            border-left: 4px solid #8B0000;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #555;
        }
        
        .terms-notice i {
            color: #8B0000;
            margin-right: 8px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px 0;
            border-top: 1px solid #eee;
            background: #f8f9fa;
            width: 100%;
        }
        
        .footer-links {
            margin-bottom: 15px;
        }
        
        .footer-links a {
            color: #555;
            text-decoration: none;
            margin: 0 10px;
            font-size: 0.9rem;
        }
        
        .footer-links a:hover {
            color: #8B0000;
            text-decoration: underline;
        }
        
        .secure-login {
            display: block;
            margin-top: 10px;
            color: #777;
            font-size: 0.8rem;
        }
        
        .secure-login i {
            color: #28a745;
            margin-right: 5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .auth-card {
                max-width: 100%;
                margin: 10px;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .form-control {
                padding: 12px 15px;
            }
            
            .btn {
                padding: 14px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="evsu-header">
        <div class="header-container">
            <div class="logo-container">
                <img src="../images/EVSU_Official_Logo.png" alt="EVSU Logo" onerror="this.src='https://via.placeholder.com/60x60?text=EVSU'">
                <div>
                    <h1>EVSU-OCC <span class="admin-badge"><i class="fas fa-user-shield"></i> Admin Portal</span></h1>
                    <div class="subtitle">Evaluation System Administrator Access</div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="auth-card">
            <!-- Admin Notice -->
            <div class="admin-notice">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <strong>Administrator Access Only</strong><br>
                    <small>This portal is for authorized administrators only</small>
                </div>
            </div>

            
            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Admin Login Form -->
                <form method="POST" action="">
                    <h2 class="form-title">Admin Login</h2>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Enter your admin username" autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="adminPassword" class="form-label">Password</label>
                        <div class="password-container">
                            <input type="password" id="adminPassword" name="password" class="form-control" required 
                                   placeholder="Enter your password" autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('adminPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </button>
                    
                    <!-- Terms Notice -->
                    <div class="terms-notice">
                        <small class="secure-login">
                            <i class="fas fa-shield-alt"></i> Secure Admin Access | v2.0
                        </small>
                    </div>
                    
                    <div class="form-footer">
                        <a href="../index.php" class="form-link">
                            <i class="fas fa-arrow-left"></i> Back to Student Portal
                        </a>
                        <div style="margin-top: 10px;">
                            <i class="fas fa-lock"></i> Authorized personnel only
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p style="font-size: 0.85rem; color: #777;">
            © <?php echo date('Y'); ?> Eastern Visayas State University. All rights reserved.
        </p>
    </div>

    <script>
        // Password visibility toggle
        function togglePassword(passwordId, button) {
            const passwordField = document.getElementById(passwordId);
            const icon = button.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Clear alerts when user starts typing
        document.querySelectorAll('input').forEach(function(input) {
            input.addEventListener('input', function() {
                var alert = document.querySelector('.alert');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(function() { 
                        if (alert.parentNode) alert.remove(); 
                    }, 300);
                }
            });
        });
        
        // Auto focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            var usernameField = document.getElementById('username');
            if (usernameField) usernameField.focus();
        });
    </script>
</body>
</html>