<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require staff or admin access
requireLogin();
if (!hasRole('admin') && !hasRole('registrar') && !hasRole('registrar_head')) {
    header("Location: ../admin/login.php");
    exit();
}

$functions = new Functions();
$registrar = $functions->getRegistrarOffice();
$office_id = $registrar['id'];

$conn = getDB();

// Get parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$service_filter = isset($_GET['service']) ? (int)$_GET['service'] : 0;

// Parse month
$year = (int)substr($month, 0, 4);
$month_num = (int)substr($month, 5, 2);

// Build query - NO student names/IDs (US-10)
$query = "
    SELECT 
        DATE(r.submitted_at) as date,
        st.name as service,
        r.question_text,
        r.rating,
        r.answer as comments
    FROM responses r
    LEFT JOIN service_types st ON r.service_type_id = st.id
    WHERE r.office_id = ? 
    AND YEAR(r.submitted_at) = ? 
    AND MONTH(r.submitted_at) = ?
";

$params = [$office_id, $year, $month_num];
$types = "iii";

if ($service_filter > 0) {
    $query .= " AND r.service_type_id = ?";
    $params[] = $service_filter;
    $types .= "i";
}

$query .= " ORDER BY r.submitted_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary stats
$stats_query = "
    SELECT 
        COUNT(*) as total_responses,
        AVG(rating) as avg_rating,
        COUNT(DISTINCT service_type_id) as total_services
    FROM responses r
    WHERE r.office_id = ? 
    AND YEAR(r.submitted_at) = ? 
    AND MONTH(r.submitted_at) = ?
";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("iii", $office_id, $year, $month_num);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Export based on format
if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registrar_report_' . $month . '.csv"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
    
    // Write summary
    fputcsv($output, ['REGISTRAR OFFICE - MONTHLY REPORT']);
    fputcsv($output, ['Month:', date('F Y', strtotime($month))]);
    fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Total Responses:', $stats['total_responses'] ?? 0]);
    fputcsv($output, ['Average Rating:', number_format($stats['avg_rating'] ?? 0, 2)]);
    fputcsv($output, ['Services Rated:', $stats['total_services'] ?? 0]);
    fputcsv($output, []);
    
    // Write headers
    fputcsv($output, ['Date', 'Service', 'Question', 'Rating', 'Comments']);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['date'],
            $row['service'] ?? 'Unknown',
            $row['question_text'],
            $row['rating'] ?? '',
            $row['comments'] ?? ''
        ]);
    }
    
    fclose($output);
    
} elseif ($format === 'excel') {
    // Excel XML format (simplified)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="registrar_report_' . $month . '.xls"');
    
    echo "<?xml version=\"1.0\"?>\n";
    echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
    echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
    echo " <Worksheet ss:Name=\"Report\">\n";
    echo "  <Table>\n";
    
    // Headers
    echo "   <Row>\n";
    echo "    <Cell><Data ss:Type=\"String\">Date</Data></Cell>\n";
    echo "    <Cell><Data ss:Type=\"String\">Service</Data></Cell>\n";
    echo "    <Cell><Data ss:Type=\"String\">Question</Data></Cell>\n";
    echo "    <Cell><Data ss:Type=\"String\">Rating</Data></Cell>\n";
    echo "    <Cell><Data ss:Type=\"String\">Comments</Data></Cell>\n";
    echo "   </Row>\n";
    
    // Data rows
    foreach ($data as $row) {
        echo "   <Row>\n";
        echo "    <Cell><Data ss:Type=\"String\">" . $row['date'] . "</Data></Cell>\n";
        echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['service'] ?? 'Unknown') . "</Data></Cell>\n";
        echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['question_text']) . "</Data></Cell>\n";
        echo "    <Cell><Data ss:Type=\"Number\">" . ($row['rating'] ?? '0') . "</Data></Cell>\n";
        echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['comments'] ?? '') . "</Data></Cell>\n";
        echo "   </Row>\n";
    }
    
    echo "  </Table>\n";
    echo " </Worksheet>\n";
    echo "</Workbook>\n";
}

exit();