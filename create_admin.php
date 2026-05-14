<?php
require_once 'includes/config.php';

// Update admin password
$new_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = ? WHERE username = 'admin'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $new_password);

if ($stmt->execute()) {
    echo "Admin password reset successfully!<br>";
    echo "New password: admin123";
} else {
    echo "Error updating password: " . $conn->error;
}

$stmt->close();
$conn->close();
?>