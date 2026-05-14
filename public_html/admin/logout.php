<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Ensure $conn is defined
if (!isset($conn)) {
    die('Database connection not established.');
}

// Log logout if admin was logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $user_id = $_SESSION['user_id'];
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    
    // Log the logout action
    try {
        $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, 'admin_logout', ?, ?)");
        $stmt->bind_param("iss", $user_id, $ip, $agent);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Ignore logging errors
    }
}

// Clear all session data
$_SESSION = array();
session_unset();
session_destroy();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to admin login page
header('Location: login.php?logout=success');
exit();
?>