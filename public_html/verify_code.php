<?php
require_once 'includes/config.php';

header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$code = isset($_POST['code']) ? trim($_POST['code']) : '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Verification code is required']);
    exit();
}

if (!isset($_SESSION['verification_code']) || !isset($_SESSION['code_expires'])) {
    echo json_encode(['success' => false, 'message' => 'No verification code found. Please request a new code.']);
    exit();
}

if (time() > $_SESSION['code_expires']) {
    unset($_SESSION['verification_code']);
    unset($_SESSION['code_expires']);
    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new code.']);
    exit();
}

if ($_SESSION['verification_code'] !== $code) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
    exit();
}

// Clear the code after successful verification
unset($_SESSION['verification_code']);
unset($_SESSION['code_expires']);

echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
?>