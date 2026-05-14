<?php
require_once 'includes/config.php';

header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit();
}

$conn = getDB();
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit();
}

$verification_code = sprintf("%06d", mt_rand(1, 999999));
$_SESSION['verification_code'] = $verification_code;
$_SESSION['verification_email'] = $email;
$_SESSION['code_expires'] = time() + 600;

// DEVELOPMENT MODE - Show code directly
echo json_encode([
    'success' => true, 
    'message' => 'Verification code generated (Development Mode)',
    'dev_code' => $verification_code
]);
?>