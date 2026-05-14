<?php
session_start();
require_once 'includes/config.php';

$error = '';
$success = '';

// Get code and state from URL
$code = isset($_GET['code']) ? $_GET['code'] : '';
$state = isset($_GET['state']) ? $_GET['state'] : '';

if (empty($code) || empty($state)) {
    header("Location: forgot_password.php?error=invalid_request");
    exit();
}

// Verify state matches
if (!isset($_SESSION['expected_state']) || $_SESSION['expected_state'] !== $state) {
    header("Location: forgot_password.php?error=invalid_state");
    exit();
}

// Get user info from session
$email = $_SESSION['reset_email'] ?? '';
$user_id = $_SESSION['reset_user_id'] ?? 0;

if (empty($email) || empty($user_id)) {
    header("Location: forgot_password.php?error=session_expired");
    exit();
}

// Store in session for password reset form
$_SESSION['reset_allowed'] = true;
$_SESSION['reset_user_id'] = $user_id;
$_SESSION['reset_email'] = $email;

// Redirect to reset password form
header("Location: reset_password_form.php");
exit();
?>