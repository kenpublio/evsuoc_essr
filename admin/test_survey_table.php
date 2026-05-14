<?php
require_once '../includes/config.php';

$conn = getDB();

echo "<h2>Survey Availability Table Check</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'survey_availability'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Table 'survey_availability' exists</p>";
    
    // Show table structure
    $result = $conn->query("DESCRIBE survey_availability");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show current data
    $result = $conn->query("SELECT * FROM survey_availability");
    echo "<h3>Current Data:</h3>";
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        while ($field = $result->fetch_field()) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in table</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Table 'survey_availability' does NOT exist</p>";
    
    // Create table
    $conn->query("
        CREATE TABLE survey_availability (
            id INT PRIMARY KEY AUTO_INCREMENT,
            is_active BOOLEAN DEFAULT FALSE,
            start_date DATE,
            end_date DATE,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p style='color: green;'>✓ Table created successfully</p>";
}

// Test query that was failing
echo "<h3>Testing Original Query:</h3>";
try {
    $result = $conn->query("SELECT * FROM survey_availability ORDER BY id DESC LIMIT 1");
    echo "<p style='color: green;'>✓ Query executed successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query failed: " . $e->getMessage() . "</p>";
}

$conn->close();
?>