<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout if student was logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    $user_id = $_SESSION['user_id'];
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    
    // Log the logout action
    try {
        $conn = getDB();
        $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'student_logout', ?)");
        $stmt->bind_param("is", $user_id, $ip);
        $stmt->execute();
        $stmt->close();
        $conn->close();
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

// Redirect to student login page
header('Location: ../login.php?logout=success');
exit();
?>