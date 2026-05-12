<?php
require_once 'includes/config.php';

echo "EVSU Evaluation System - Admin Setup\n";
echo "====================================\n\n";


$result = $conn->query("SELECT id FROM users WHERE username = 'admin'");
if ($result->num_rows > 0) {
    echo "Admin user already exists!\n";
    echo "To reset password, run: php reset_password.php\n";
    exit;
}

echo "Creating admin user...\n";

$username = 'admin';
$password = '12345'; 
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@evsu.edu.ph';
$student_id = 'ADMIN001';
$fullname = 'System Administrator';

$stmt = $conn->prepare("INSERT INTO users (username, password, email, role, student_id, fullname) VALUES (?, ?, ?, 'admin', ?, ?)");
$stmt->bind_param("sssss", $username, $hashed_password, $email, $student_id, $fullname);

if ($stmt->execute()) {
    echo "\n✅ Admin user created successfully!\n";
    echo "====================================\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Email: $email\n";
    echo "\n⚠️ IMPORTANT: Change this password immediately after login!\n";
} else {
    echo "\n❌ Error creating admin: " . $stmt->error . "\n";
}
?>