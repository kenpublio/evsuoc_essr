<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

echo "<h3>Login Debug Information</h3>";

// Test credentials
$test_username = 'admin';
$test_password = 'admin123';

// Create auth object
$auth = new Auth();

// Check if session is working
echo "<h4>Session Status:</h4>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

// Manually check if admin user exists
echo "<h4>Checking Database for User:</h4>";
$stmt = $conn->prepare("SELECT id, username, password, role, email_verified, is_active FROM users WHERE username = ?");
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "✓ User found in database:<br>";
    echo "- ID: " . $row['id'] . "<br>";
    echo "- Username: " . $row['username'] . "<br>";
    echo "- Role: " . $row['role'] . "<br>";
    echo "- Email Verified: " . ($row['email_verified'] ? 'Yes' : 'No') . "<br>";
    echo "- Is Active: " . ($row['is_active'] ? 'Yes' : 'No') . "<br>";
    echo "- Password Hash: " . $row['password'] . "<br>";
    
    // Test password verification
    echo "<h4>Password Verification Test:</h4>";
    if (password_verify($test_password, $row['password'])) {
        echo "✓ Password verification SUCCESSFUL<br>";
    } else {
        echo "✗ Password verification FAILED<br>";
        
        // Check what hash we get
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "New hash for 'admin123': " . $new_hash . "<br>";
        echo "Compare manually: " . (password_verify($test_password, $new_hash) ? 'MATCHES' : 'DOES NOT MATCH') . "<br>";
    }
} else {
    echo "✗ User NOT found in database!<br>";
}

// Test login method directly
echo "<h4>Testing Auth->login() method:</h4>";
if ($auth->login($test_username, $test_password)) {
    echo "✓ Login method returned TRUE<br>";
    
    // Check session
    
    // Test isLoggedIn
    echo "<h4>Testing isLoggedIn():</h4>";
    if ($auth->isLoggedIn()) {
        echo "✓ isLoggedIn returns TRUE<br>";
    } else {
        echo "✗ isLoggedIn returns FALSE<br>";
    }
} else {
    echo "✗ Login method returned FALSE<br>";
}

// Check all users in database
echo "<h4>All Users in Database:</h4>";
$result = $conn->query("SELECT id, username, role, email, is_active FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Active</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>