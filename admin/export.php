<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require admin access
requireRole('admin');

// Get database connection
$conn = getDB();

// Get service filter from URL
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Get service name
$service_name = 'All Services';
if ($service_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM service_types WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $service_name = $row['name'];
    }
}

// Build query
if ($service_id > 0) {
    $query = "
        SELECT 
            u.fullname,
            u.student_id,
            u.email,
            st.name as service_name,
            r.rating,
            r.answer as comment,
            r.submitted_at
        FROM responses r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN service_types st ON r.service_type_id = st.id
        WHERE r.service_type_id = ?
        ORDER BY r.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $service_id);
} else {
    $query = "
        SELECT 
            u.fullname,
            u.student_id,
            u.email,
            COALESCE(st.name, 'General') as service_name,
            r.rating,
            r.answer as comment,
            r.submitted_at
        FROM responses r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN service_types st ON r.service_type_id = st.id
        ORDER BY r.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="evaluations_' . date('Y-m-d') . '_' . str_replace(' ', '_', $service_name) . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Student Name',
    'Student ID',
    'Email',
    'Service Type',
    'Rating',
    'Comment',
    'Date Submitted'
]);

// Add data rows
foreach ($results as $row) {
    fputcsv($output, [
        $row['fullname'],
        $row['student_id'],
        $row['email'],
        $row['service_name'],
        $row['rating'] . '/5',
        $row['comment'] ?? '',
        date('Y-m-d H:i:s', strtotime($row['submitted_at']))
    ]);
}

fclose($output);
exit();
?>