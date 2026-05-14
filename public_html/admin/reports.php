<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require admin access
requireRole('admin');

// Initialize Functions class
$functions = new Functions();

// Get current user
$user_id = $_SESSION['user_id'];
$user = $functions->getUserById($user_id);

// Get Registrar office
$registrar = $functions->getRegistrarOffice();
$office_id = $registrar['id'];

// Fetch available services
$services = $functions->getServicesByOfficeId($office_id);

// Handle report generation
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
$service_filter = isset($_GET['service']) ? $_GET['service'] : 'all';

$conn = getDB();

$services = [];
$service_query = "SELECT id, name FROM service_types WHERE is_active = 1 ORDER BY name";
$service_result = $conn->query($service_query);
if ($service_result) {
    while ($row = $service_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get report data based on type
$report_data = [];
$summary_stats = [];

if ($report_type === 'monthly') {
    $year_month = explode('-', $month);
    $year = $year_month[0];
    $month_num = $year_month[1];
    
    // Get evaluations for the selected month
    $query = "
        SELECT r.*, u.fullname, u.username, u.student_id, 
               DATE(r.submitted_at) as eval_date,
               HOUR(r.submitted_at) as eval_hour
        FROM responses r
        JOIN users u ON r.user_id = u.id
        WHERE r.office_id = ? 
        AND YEAR(r.submitted_at) = ? 
        AND MONTH(r.submitted_at) = ?
        ORDER BY r.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $office_id, $year, $month_num);
    $stmt->execute();
    $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get summary stats for the month
    $stats_query = "
        SELECT 
            COUNT(DISTINCT r.user_id) as total_respondents,
            COUNT(r.id) as total_responses,
            AVG(r.rating) as avg_rating,
            COUNT(DISTINCT DATE(r.submitted_at)) as active_days,
            MAX(r.rating) as max_rating,
            MIN(r.rating) as min_rating
        FROM responses r
        WHERE r.office_id = ? 
        AND YEAR(r.submitted_at) = ? 
        AND MONTH(r.submitted_at) = ?
    ";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("iii", $office_id, $year, $month_num);
    $stmt->execute();
    $summary_stats = $stmt->get_result()->fetch_assoc();
    
} elseif ($report_type === 'custom') {
    // Get evaluations for custom date range
    $query = "
        SELECT r.*, u.fullname, u.username, u.student_id,
               DATE(r.submitted_at) as eval_date
        FROM responses r
        JOIN users u ON r.user_id = u.id
        WHERE r.office_id = ? 
        AND DATE(r.submitted_at) BETWEEN ? AND ?
        ORDER BY r.submitted_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $office_id, $date_from, $date_to);
    $stmt->execute();
    $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get summary stats for date range
    $stats_query = "
        SELECT 
            COUNT(DISTINCT r.user_id) as total_respondents,
            COUNT(r.id) as total_responses,
            AVG(r.rating) as avg_rating,
            COUNT(DISTINCT DATE(r.submitted_at)) as active_days,
            MAX(r.rating) as max_rating,
            MIN(r.rating) as min_rating
        FROM responses r
        WHERE r.office_id = ? 
        AND DATE(r.submitted_at) BETWEEN ? AND ?
    ";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("iss", $office_id, $date_from, $date_to);
    $stmt->execute();
    $summary_stats = $stmt->get_result()->fetch_assoc();
    
} else {
    // Daily/Weekly reports
    $query = "
        SELECT r.*, u.fullname, u.username, u.student_id,
               DATE(r.submitted_at) as eval_date
        FROM responses r
        JOIN users u ON r.user_id = u.id
        WHERE r.office_id = ? 
        ORDER BY r.submitted_at DESC
        LIMIT 1000
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Overall stats
    $stats_query = "
        SELECT 
            COUNT(DISTINCT r.user_id) as total_respondents,
            COUNT(r.id) as total_responses,
            AVG(r.rating) as avg_rating,
            COUNT(DISTINCT DATE(r.submitted_at)) as active_days,
            MAX(r.rating) as max_rating,
            MIN(r.rating) as min_rating,
            MAX(r.submitted_at) as last_evaluation
        FROM responses r
        WHERE r.office_id = ?
    ";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $summary_stats = $stmt->get_result()->fetch_assoc();
}

// Get rating distribution
$rating_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($report_data as $row) {
    if (isset($row['rating'])) {
        $rating_distribution[(int)$row['rating']]++;
    }
}

// Get daily trends
$daily_trends = [];
$daily_counts = [];
foreach ($report_data as $row) {
    $date = date('Y-m-d', strtotime($row['submitted_at']));
    if (!isset($daily_counts[$date])) {
        $daily_counts[$date] = 0;
    }
    $daily_counts[$date]++;
}
foreach ($daily_counts as $date => $count) {
    $daily_trends[] = ['date' => $date, 'count' => $count];
}
// Sort by date
usort($daily_trends, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// Get question-wise analysis
$question_analysis = [];
$question_query = "
    SELECT r.question_text, 
           COUNT(*) as response_count,
           AVG(r.rating) as avg_rating,
           MIN(r.rating) as min_rating,
           MAX(r.rating) as max_rating
    FROM responses r
    WHERE r.office_id = ? 
    GROUP BY r.question_text
    ORDER BY avg_rating DESC
";
$stmt = $conn->prepare($question_query);
$stmt->bind_param("i", $office_id);
$stmt->execute();
$question_analysis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get top students by rating
$top_students_query = "
    SELECT u.fullname, u.username, u.student_id,
           COUNT(r.id) as eval_count,
           AVG(r.rating) as avg_rating
    FROM responses r
    JOIN users u ON r.user_id = u.id
    WHERE r.office_id = ?
    GROUP BY u.id
    HAVING eval_count >= 5
    ORDER BY avg_rating DESC
    LIMIT 10
";
$stmt = $conn->prepare($top_students_query);
$stmt->bind_param("i", $office_id);
$stmt->execute();
$top_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent comments/feedback
$comments_query = "
    SELECT r.*, u.fullname, u.username, u.student_id
    FROM responses r
    JOIN users u ON r.user_id = u.id
    WHERE r.office_id = ? 
    AND (r.answer IS NOT NULL AND r.answer != '')
    ORDER BY r.submitted_at DESC
    LIMIT 20
";
$stmt = $conn->prepare($comments_query);
$stmt->bind_param("i", $office_id);
$stmt->execute();
$recent_comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportToCSV($report_data, $summary_stats, $question_analysis);
}

// Export function
function exportToCSV($data, $stats, $questions) {
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registrar_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
    
    // Write summary section
    fputcsv($output, ['REGISTRAR OFFICE - EVALUATION REPORT']);
    fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Total Respondents', $stats['total_respondents'] ?? 0]);
    fputcsv($output, ['Total Responses', $stats['total_responses'] ?? 0]);
    fputcsv($output, ['Average Rating', number_format($stats['avg_rating'] ?? 0, 2)]);
    fputcsv($output, ['Highest Rating', $stats['max_rating'] ?? 0]);
    fputcsv($output, ['Lowest Rating', $stats['min_rating'] ?? 0]);
    fputcsv($output, ['Active Days', $stats['active_days'] ?? 0]);
    fputcsv($output, []);
    
    // Question analysis
    fputcsv($output, ['QUESTION ANALYSIS']);
    fputcsv($output, ['Question', 'Responses', 'Average Rating', 'Min', 'Max']);
    foreach ($questions as $q) {
        fputcsv($output, [
            $q['question_text'],
            $q['response_count'],
            number_format($q['avg_rating'], 2),
            $q['min_rating'],
            $q['max_rating']
        ]);
    }
    fputcsv($output, []);
    
    // Detailed data
    fputcsv($output, ['DETAILED EVALUATIONS']);
    fputcsv($output, ['Date', 'Student', 'Student ID', 'Question', 'Rating', 'Comment']);
    foreach ($data as $row) {
        fputcsv($output, [
            date('Y-m-d H:i', strtotime($row['submitted_at'])),
            $row['fullname'] ?? $row['username'],
            $row['student_id'] ?? 'N/A',
            $row['question_text'] ?? 'N/A',
            $row['rating'] ?? '',
            $row['answer'] ?? ''
        ]);
    }
    
    fclose($output);
    exit();
}

// Page title
$page_title = 'Reports & Analytics - Registrar Evaluation';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --evsu-red: #8B0000;
            --evsu-gold: #FFD700;
            --evsu-dark: #1a1a1a;
            --evsu-gray: #f5f5f5;
            --success-green: #28a745;
            --warning-orange: #fd7e14;
            --info-blue: #17a2b8;
            --chart-1: #8B0000;
            --chart-2: #FFD700;
            --chart-3: #17a2b8;
            --chart-4: #28a745;
            --chart-5: #fd7e14;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--evsu-gray);
            min-height: 100vh;
        }

        /* Header */
        .evsu-header {
            background: linear-gradient(135deg, var(--evsu-red) 0%, #B22222 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .evsu-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--evsu-red);
            font-weight: bold;
        }

        .title-section h1 {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .title-section .subtitle {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--evsu-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--evsu-red);
            font-weight: bold;
        }

        .user-details {
            line-height: 1.4;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .user-actions {
            display: flex;
            gap: 10px;
            margin-top: 3px;
        }

        .user-actions a {
            color: white;
            text-decoration: none;
            font-size: 0.75rem;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .user-actions a:hover {
            opacity: 1;
            color: var(--evsu-gold);
        }

        /* Layout */
        .main-container {
            display: flex;
            max-width: 1400px;
            margin: 20px auto;
            gap: 20px;
            padding: 0 20px;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px 0;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar-header {
            padding: 0 20px 15px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 15px;
        }

        .sidebar-header h3 {
            color: var(--evsu-red);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-header h3 i {
            margin-right: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #fff5f5;
            color: var(--evsu-red);
            border-left-color: var(--evsu-red);
        }

        .nav-item.active {
            background: #fff0f0;
            color: var(--evsu-red);
            border-left-color: var(--evsu-red);
            font-weight: 500;
        }

        .nav-icon {
            width: 24px;
            margin-right: 12px;
            font-size: 1.1rem;
            text-align: center;
        }

        /* Main Content */
        .content {
            flex: 1;
        }

        .content-header {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h2 {
            color: var(--evsu-dark);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .content-header h2 i {
            color: var(--evsu-red);
            margin-right: 10px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--evsu-red);
            color: white;
        }

        .btn-primary:hover {
            background: #6b0000;
        }

        .btn-success {
            background: var(--success-green);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-outline {
            background: white;
            border: 1px solid #ddd;
            color: #666;
        }

        .btn-outline:hover {
            background: #f8f9fa;
        }

        .btn-warning {
            background: var(--warning-orange);
            color: white;
        }

        .btn-warning:hover {
            background: #e66b00;
        }

        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-card h3 {
            color: var(--evsu-dark);
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-card h3 i {
            color: var(--evsu-red);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 150px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--evsu-red);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--evsu-red);
            line-height: 1.2;
        }

        .stat-card .label {
            color: #777;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        /* Chart Cards */
        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
        }

        .chart-card h3 {
            color: var(--evsu-dark);
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-card h3 i {
            color: var(--evsu-red);
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Tables */
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .table-card h3 {
            color: var(--evsu-dark);
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-card h3 i {
            color: var(--evsu-red);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            color: #666;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Rating Stars */
        .rating-stars {
            display: inline-flex;
            gap: 2px;
        }

        .rating-stars i {
            color: #ffd700;
            font-size: 0.9rem;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Comment Box */
        .comment-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-style: italic;
            color: #555;
            max-width: 300px;
        }

        /* Footer */
        .footer {
            background: var(--evsu-dark);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 30px;
        }

        .footer a {
            color: #ddd;
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.9rem;
        }

        .footer a:hover {
            color: var(--evsu-gold);
        }

        .copyright {
            font-size: 0.85rem;
            color: #777;
            margin-top: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="evsu-header">
        <div class="header-container">
            <div class="logo-section">
                <div class="evsu-logo">
                    <i class="fas fa-university"></i>
                </div>
                <div class="title-section">
                    <h1>EVSU - Registrar's Office</h1>
                    <div class="subtitle">Reports & Analytics</div>
                </div>
            </div>

            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['fullname'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name">
                        <?php echo htmlspecialchars($user['fullname'] ?? $user['username'] ?? 'Admin'); ?>
                    </div>
                    <div class="user-role">Administrator</div>
                    <div class="user-actions">
                        <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
                        <span>|</span>
                        <a href="../logout.php" onclick="return confirm('Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-regular fa-building"></i> REGISTRAR MENU</h3>
            </div>

            <a href="index.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="evaluations.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                <span>All Evaluations</span>
            </a>
            <a href="questions.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-question-circle"></i></span>
                <span>Survey Questions</span>
            </a>
            <a href="students.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-user-graduate"></i></span>
                <span>Student List</span>
            </a>
            <a href="reports.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                <span>Reports</span>
            </a>
            <a href="logs.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-history"></i></span>
                <span>Activity Logs</span>
            </a>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Content Header -->
            <div class="content-header">
                <h2>
                    <i class="fas fa-chart-bar"></i>
                    Reports & Analytics - Registrar's Office
                </h2>
                <div class="header-actions">
    <!-- Service Type Selector -->
    <div class="service-selector-group" style="display: flex; gap: 10px; align-items: center;">
        <select id="serviceTypeSelect" class="form-control" style="width: auto; min-width: 150px; padding: 8px 12px; border-radius: 8px; border: 1px solid #ddd;">
            <option value="0">All Services</option>
            <?php foreach ($services as $service): ?>
                <option value="<?php echo $service['id']; ?>">
                    <?php echo htmlspecialchars($service['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <a href="#" id="exportBtn" class="btn btn-success">
            <i class="fas fa-download"></i> Export CSV
        </a>
        
        <a href="#" id="printBtn" class="btn btn-outline" target="_blank">
            <i class="fas fa-print"></i> Print
        </a>
    </div>
</div>

<script>
// Get elements
const serviceSelect = document.getElementById('serviceTypeSelect');
const exportBtn = document.getElementById('exportBtn');
const printBtn = document.getElementById('printBtn');

// Get current URL parameters
const urlParams = new URLSearchParams(window.location.search);
const reportType = urlParams.get('type') || '<?php echo $report_type; ?>';
const month = urlParams.get('month') || '<?php echo $month; ?>';
const dateFrom = urlParams.get('date_from') || '<?php echo $date_from; ?>';
const dateTo = urlParams.get('date_to') || '<?php echo $date_to; ?>';

// Update links function
function updateLinks() {
    const selectedService = serviceSelect.value;
    
    // Update export link
    exportBtn.href = `?export=csv&type=${reportType}&month=${month}&date_from=${dateFrom}&date_to=${dateTo}&service_id=${selectedService}`;
    
    // Update print link
    printBtn.href = `print_report.php?type=${reportType}&month=${month}&date_from=${dateFrom}&date_to=${dateTo}&service_id=${selectedService}`;
}

// Add event listener
serviceSelect.addEventListener('change', updateLinks);

// Initialize on page load
updateLinks();
</script>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <h3>
                    <i class="fas fa-filter"></i>
                    Generate Report
                </h3>
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="type" class="form-control" onchange="this.form.submit()">
                            <option value="overall" <?php echo $report_type === 'overall' ? 'selected' : ''; ?>>Overall Report</option>
                            <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                            <option value="custom" <?php echo $report_type === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                        </select>
                    </div>

                    <?php if ($report_type === 'monthly'): ?>
                    <div class="form-group">
                        <label>Select Month</label>
                        <input type="month" name="month" class="form-control" value="<?php echo $month; ?>" onchange="this.form.submit()">
                    </div>
                    <?php endif; ?>

                    <?php if ($report_type === 'custom'): ?>
                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="value"><?php echo number_format($summary_stats['total_respondents'] ?? 0); ?></div>
                    <div class="label">Total Respondents</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?php echo number_format($summary_stats['total_responses'] ?? 0); ?></div>
                    <div class="label">Total Responses</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?php echo number_format($summary_stats['avg_rating'] ?? 0, 1); ?></div>
                    <div class="label">Average Rating</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?php echo $summary_stats['active_days'] ?? 0; ?></div>
                    <div class="label">Active Days</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?php echo $summary_stats['max_rating'] ?? 0; ?></div>
                    <div class="label">Highest Rating</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?php echo $summary_stats['min_rating'] ?? 0; ?></div>
                    <div class="label">Lowest Rating</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="chart-row">
                <!-- Rating Distribution Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Rating Distribution</h3>
                    <div class="chart-container">
                        <canvas id="ratingChart"></canvas>
                    </div>
                </div>

                <!-- Daily Trends Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Daily Evaluation Trends</h3>
                    <div class="chart-container">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Question Analysis Table -->
            <div class="table-card">
                <h3><i class="fas fa-question-circle"></i> Question-wise Analysis</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Responses</th>
                            <th>Average Rating</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($question_analysis as $q): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                                <td><?php echo number_format($q['response_count']); ?></td>
                                <td>
                                    <strong><?php echo number_format($q['avg_rating'], 1); ?></strong>
                                    <div class="rating-stars" style="margin-top: 5px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= round($q['avg_rating'])): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td><?php echo $q['min_rating']; ?></td>
                                <td><?php echo $q['max_rating']; ?></td>
                                <td>
                                    <?php 
                                    $percentage = ($q['avg_rating'] / 5) * 100;
                                    if ($percentage >= 80): ?>
                                        <span class="badge badge-success">Excellent</span>
                                    <?php elseif ($percentage >= 60): ?>
                                        <span class="badge badge-info">Good</span>
                                    <?php elseif ($percentage >= 40): ?>
                                        <span class="badge badge-warning">Average</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #f8d7da; color: #721c24;">Needs Improvement</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Students -->
            <?php if (!empty($top_students)): ?>
            <div class="table-card">
                <h3><i class="fas fa-trophy"></i> Top Performing Students (5+ evaluations)</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Evaluations</th>
                            <th>Average Rating</th>
                            <th>Stars</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['fullname'] ?? $s['username']); ?></td>
                                <td><?php echo htmlspecialchars($s['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo $s['eval_count']; ?></td>
                                <td><strong><?php echo number_format($s['avg_rating'], 1); ?></strong></td>
                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= round($s['avg_rating'])): ?>
                                                <i class="fas fa-star" style="color: #ffd700;"></i>
                                            <?php else: ?>
                                                <i class="far fa-star" style="color: #ddd;"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Recent Comments -->
            <?php if (!empty($recent_comments)): ?>
            <div class="table-card">
                <h3><i class="fas fa-comment"></i> Recent Feedback & Comments</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Comment</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_comments as $c): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($c['submitted_at'])); ?></td>
                                <td><?php echo htmlspecialchars($c['fullname'] ?? $c['username']); ?></td>
                                <td>
                                    <div class="comment-box">
                                        "<?php echo htmlspecialchars($c['answer']); ?>"
                                    </div>
                                </td>
                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $c['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Detailed Evaluations Table -->
            <div class="table-card">
                <h3><i class="fas fa-list"></i> Detailed Evaluations</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Question</th>
                            <th>Rating</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $display_count = 0;
                        foreach ($report_data as $row): 
                            if ($display_count++ >= 50) break; // Show last 50 only
                        ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                <td><?php echo htmlspecialchars($row['fullname'] ?? $row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['question_text'] ?? '', 0, 50)) . '...'; ?></td>
                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= ($row['rating'] ?? 0)): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['answer'])): ?>
                                        <span class="badge badge-info">With comment</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #e9ecef;">No comment</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($report_data) > 50): ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="evaluations.php" class="btn btn-outline">View All Evaluations</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div>
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="../logout.php" onclick="return confirm('Logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <div class="copyright">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System
        </div>
    </footer>

    <script>
        // Rating Distribution Chart
        const ratingCtx = document.getElementById('ratingChart').getContext('2d');
        new Chart(ratingCtx, {
            type: 'pie',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    data: [
                        <?php echo $rating_distribution[1]; ?>,
                        <?php echo $rating_distribution[2]; ?>,
                        <?php echo $rating_distribution[3]; ?>,
                        <?php echo $rating_distribution[4]; ?>,
                        <?php echo $rating_distribution[5]; ?>
                    ],
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#17a2b8',
                        '#28a745'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Daily Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($daily_trends as $t): ?> '<?php echo date('M d', strtotime($t['date'])); ?>', <?php endforeach; ?>],
                datasets: [{
                    label: 'Number of Evaluations',
                    data: [<?php foreach ($daily_trends as $t): ?> <?php echo $t['count']; ?>, <?php endforeach; ?>],
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139, 0, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>