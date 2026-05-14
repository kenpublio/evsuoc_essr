<?php
session_start();
require_once 'includes/config.php';

// Check if user is allowed to reset password
if (!isset($_SESSION['reset_allowed']) || $_SESSION['reset_allowed'] !== true) {
    header("Location: forgot_password.php?error=unauthorized");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $conn = getDB();
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = "Password reset successfully! You can now login with your new password.";
            // Clear reset session
            unset($_SESSION['reset_allowed']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - EVSU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .reset-card { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); width: 100%; max-width: 450px; padding: 40px; animation: fadeIn 0.5s ease; }
        .reset-header { text-align: center; margin-bottom: 30px; }
        .reset-icon { font-size: 3rem; color: #8B0000; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: #8B0000; }
        .btn { width: 100%; padding: 14px; background: linear-gradient(to right, #8B0000, #A52A2A); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="reset-header">
            <div class="reset-icon"><i class="fas fa-lock"></i></div>
            <h2>Set New Password</h2>
            <p>Create a new password for your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <a href="login.php" class="btn" style="text-align: center; text-decoration: none; display: block; margin-top: 15px;">Go to Login</a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>