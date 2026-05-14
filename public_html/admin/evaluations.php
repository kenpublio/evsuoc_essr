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

// Get database connection
$conn = getDB();

// Get Registrar office
$registrar = $functions->getRegistrarOffice();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$selected_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Get all services for filter dropdown
$services = [];
$service_query = "SELECT id, name FROM service_types WHERE is_active = 1 ORDER BY name";
$service_result = $conn->query($service_query);
if ($service_result) {
    while ($row = $service_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = "";

if ($selected_service_id > 0) {
    $where_conditions[] = "r.service_type_id = ?";
    $params[] = $selected_service_id;
    $types .= "i";
}

if (!empty($search)) {
    $where_conditions[] = "(u.fullname LIKE ? OR u.student_id LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(r.submitted_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(r.submitted_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Rating filter on average
if ($rating_filter > 0) {
    $where_conditions[] = "AVG(r.rating) >= ?";
    $params[] = $rating_filter - 0.5;
    $types .= "d";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total unique submissions
$count_query = "
    SELECT COUNT(DISTINCT CONCAT(r.user_id, '-', DATE(r.submitted_at), '-', IFNULL(r.service_type_id, 0))) as total
    FROM responses r
    JOIN users u ON r.user_id = u.id
    $where_clause
";

if (!empty($params) && strpos($types, 'd') === false) {
    // For count query without rating filter
    $count_types = str_replace('d', '', $types);
    if (!empty($count_types)) {
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param($count_types, ...$params);
        $count_stmt->execute();
        $total_evaluations = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $count_result = $conn->query($count_query);
        $total_evaluations = $count_result->fetch_assoc()['total'];
    }
} else {
    $count_result = $conn->query($count_query);
    $total_evaluations = $count_result->fetch_assoc()['total'];
}

// Get evaluations - ONE row per submission
$query = "
    SELECT 
        MIN(r.id) as id,
        r.user_id,
        r.service_type_id,
        ROUND(AVG(r.rating), 1) as rating,
        MAX(r.answer) as answer,
        MAX(r.submitted_at) as submitted_at,
        u.username,
        u.fullname,
        u.student_id,
        u.email,
        COALESCE(st.name, 'General') as service_name
    FROM responses r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN service_types st ON r.service_type_id = st.id
    $where_clause
    GROUP BY CONCAT(r.user_id, '-', DATE(r.submitted_at), '-', IFNULL(r.service_type_id, 0))
    ORDER BY submitted_at DESC
    LIMIT ? OFFSET ?
";

$query_params = $params;
$query_params[] = $limit;
$query_params[] = $offset;
$query_types = $types . "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($query_types, ...$query_params);
$stmt->execute();
$evaluations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_pages = ceil($total_evaluations / $limit);

// Get statistics for summary cards
$stats = [];
$stats_query = "SELECT COUNT(DISTINCT CONCAT(user_id, '-', DATE(submitted_at), '-', IFNULL(service_type_id, 0))) as total_evaluations, ROUND(AVG(rating), 1) as average_rating FROM responses";
$stats_result = $conn->query($stats_query);
if ($stats_result) {
    $stats_data = $stats_result->fetch_assoc();
    $stats['total_evaluations'] = $stats_data['total_evaluations'] ?? 0;
    $stats['average_rating'] = $stats_data['average_rating'] ?? 0;
}

// Today's evaluations
$today_query = "SELECT COUNT(DISTINCT CONCAT(user_id, '-', DATE(submitted_at), '-', IFNULL(service_type_id, 0))) as count FROM responses WHERE DATE(submitted_at) = CURDATE()";
$today_result = $conn->query($today_query);
$stats['evaluations_today'] = $today_result->fetch_assoc()['count'] ?? 0;

// This week's evaluations
$week_query = "SELECT COUNT(DISTINCT CONCAT(user_id, '-', DATE(submitted_at), '-', IFNULL(service_type_id, 0))) as count FROM responses WHERE YEARWEEK(submitted_at) = YEARWEEK(CURDATE())";
$week_result = $conn->query($week_query);
$stats['evaluations_week'] = $week_result->fetch_assoc()['count'] ?? 0;

$page_title = 'Registrar Evaluations - EVSU';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--evsu-gray);
            min-height: 100vh;
        }

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
        }

        .user-actions a:hover {
            opacity: 1;
            color: var(--evsu-gold);
        }

        .main-container {
            display: flex;
            max-width: 1400px;
            margin: 20px auto;
            gap: 20px;
            padding: 0 20px;
        }

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
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
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
        }

        .nav-icon {
            width: 24px;
            margin-right: 12px;
        }

        .content {
            flex: 1;
        }

        .content-header {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h2 {
            color: var(--evsu-dark);
            font-size: 1.5rem;
        }

        .content-header h2 i {
            color: var(--evsu-red);
            margin-right: 10px;
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
            border: none;
            cursor: pointer;
        }

        .btn-success {
            background: var(--success-green);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--evsu-red);
        }

        .stat-content h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--evsu-dark);
        }

        .stat-content p {
            color: #777;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .filter-card h3 {
            color: var(--evsu-dark);
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .filter-card h3 i {
            color: var(--evsu-red);
            margin-right: 8px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 0.8rem;
            color: #666;
        }

        .form-control {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--evsu-red);
        }

        .btn-primary {
            background: var(--evsu-red);
            color: white;
        }

        .btn-outline {
            background: white;
            border: 1px solid #ddd;
            color: #666;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h3 {
            color: var(--evsu-dark);
            font-size: 1.1rem;
        }

        .card-header h3 i {
            color: var(--evsu-red);
            margin-right: 8px;
        }

        .badge {
            background: var(--evsu-gray);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #666;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
            font-size: 0.85rem;
            color: #555;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
            color: #666;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .rating-stars {
            display: inline-flex;
            gap: 2px;
        }

        .rating-stars i {
            color: #ffd700;
        }

        .rating-stars i.empty {
            color: #ddd;
        }

        .alert-info {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            color: #666;
            text-decoration: none;
        }

        .page-link.active {
            background: var(--evsu-red);
            color: white;
            border-color: var(--evsu-red);
        }


        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                position: static;
            }
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        /* Table styling */
.table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
}

.table th, 
.table td {
    padding: 12px 15px;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #e9ecef;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

/* Fix for inline elements */
.table td {
    vertical-align: middle;
}

/* Rating stars container */
.rating-stars {
    display: inline-flex;
    gap: 3px;
    vertical-align: middle;
}

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
        .service-export-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border-left: 4px solid var(--evsu-red);
}

.service-export-card h3 {
    color: var(--evsu-dark);
    font-size: 1rem;
    margin-bottom: 10px;
}

.service-export-card h3 i {
    color: var(--evsu-red);
    margin-right: 8px;
}

.service-export-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.service-export-btn {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 40px;
    padding: 8px 20px;
    text-decoration: none;
    color: #666;
    font-size: 0.9rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.service-export-btn:hover {
    border-color: var(--evsu-red);
    background: #fff5f5;
    color: var(--evsu-red);
    transform: translateY(-2px);
}
    </style>
</head>
<body>
    <header class="evsu-header">
        <div class="header-container">
            <div class="logo-section">
                <div class="evsu-logo">
                    <i class="fas fa-university"></i>
                </div>
                <div class="title-section">
                    <h1>EVSU - Registrar's Office</h1>
                    <div class="subtitle">Evaluation System Admin</div>
                </div>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['fullname'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['fullname'] ?? 'Admin'); ?></div>
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

    <div class="main-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-building"></i> REGISTRAR MENU</h3>
            </div>
            <a href="index.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="evaluations.php" class="nav-item active">
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
            <a href="reports.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                <span>Reports</span>
            </a>
            <a href="logs.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-history"></i></span>
                <span>Activity Logs</span>
            </a>
        </aside>

        <main class="content">
    <!-- Export Section -->
    <div class="service-export-card">
        <h3><i class="fas fa-download"></i> Export Evaluations to CSV</h3>
        <p style="color: #666; margin-bottom: 15px;">Select a service type to export:</p>
        
        <form method="GET" action="export.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label>Select Service Type</label>
                <select name="service_id" class="form-control" required>
                    <option value="0">All Services</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['id']; ?>">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </form>
    </div>

    <div class="content-header">
        <h2><i class="fas fa-clipboard-list"></i> Registrar Evaluations</h2>
    </div>


            <!-- Stats Summary -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-content">
                        <h4><?php echo number_format($stats['total_evaluations'] ?? 0); ?></h4>
                        <p>Total Evaluations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-content">
                        <h4><?php echo $stats['average_rating'] ?? '0.0'; ?></h4>
                        <p>Average Rating</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-content">
                        <h4><?php echo number_format($stats['evaluations_today'] ?? 0); ?></h4>
                        <p>Today</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                    <div class="stat-content">
                        <h4><?php echo number_format($stats['evaluations_week'] ?? 0); ?></h4>
                        <p>This Week</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <h3><i class="fas fa-filter"></i> Filter Evaluations</h3>
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label>Service Type</label>
                        <select name="service_id" class="form-control">
                            <option value="0">All Services</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo ($selected_service_id == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search Student</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, ID, or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating" class="form-control">
                            <option value="0">All Ratings</option>
                            <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 Stars</option>
                            <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 Stars</option>
                            <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 Star</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                        <a href="evaluations.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
                    </div>
                </form>
            </div>

           <!-- Evaluations Table -->
<div class="card">
    <div class="card-header">
        <h3>
            <i class="fas fa-list"></i>
            Evaluation Records
            <?php if ($selected_service_id > 0): ?>
                <span style="font-size: 12px; background: var(--evsu-red); color: white; padding: 3px 10px; border-radius: 20px; margin-left: 10px;">
                    <?php 
                        $service_name = '';
                        foreach ($services as $s) {
                            if ($s['id'] == $selected_service_id) {
                                $service_name = $s['name'];
                                break;
                            }
                        }
                        echo htmlspecialchars($service_name);
                    ?>
                </span>
            <?php endif; ?>
        </h3>
        <span class="badge">Total: <?php echo number_format($total_evaluations); ?> entries</span>
    </div>

    <?php if (!empty($evaluations)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Service</th>
                        <th>Rating</th>
                        <th>Date & Time</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluations as $eval): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($eval['fullname'] ?? $eval['username'] ?? 'Unknown'); ?></strong>
                                <?php if (!empty($eval['student_id'])): ?>
                                    <br><small style="color: #999;">ID: <?php echo htmlspecialchars($eval['student_id']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="padding: 5px 12px; background: #e8f5e9; color: #2e7d32; border-radius: 20px; font-size: 12px; display: inline-block; white-space: nowrap;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($eval['service_name'] ?? 'General'); ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <div class="rating-stars" style="display: inline-flex; gap: 2px;">
                                    <?php 
                                    $rating = isset($eval['rating']) ? (float)$eval['rating'] : 0;
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= round($rating)):
                                    ?>
                                        <i class="fas fa-star" style="color: #ffc107;"></i>
                                    <?php else: ?>
                                        <i class="far fa-star" style="color: #ddd;"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <strong style="margin-left: 5px;"><?php echo $rating; ?>/5</strong>
                            </td>
                            <td style="white-space: nowrap;">
                                <i class="far fa-calendar-alt" style="margin-right: 5px; color: #999;"></i>
                                <?php echo date('M d, Y', strtotime($eval['submitted_at'] ?? 'now')); ?>
                                <br>
                                <small style="color: #999;">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('h:i A', strtotime($eval['submitted_at'] ?? 'now')); ?>
                                </small>
                            </td>
                            <td>
                                <?php if (!empty($eval['answer'])): ?>
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 4px 12px; border-radius: 20px; font-size: 12px; display: inline-block; white-space: nowrap;">
                                        <i class="fas fa-check-circle"></i> With feedback
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; font-size: 12px; display: inline-block; white-space: nowrap;">
                                        <i class="fas fa-minus-circle"></i> No feedback
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&service_id=<?php echo $selected_service_id; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&rating=<?php echo $rating_filter; ?>" class="page-link">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="page-link active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&service_id=<?php echo $selected_service_id; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&rating=<?php echo $rating_filter; ?>" class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&service_id=<?php echo $selected_service_id; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&rating=<?php echo $rating_filter; ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            No evaluations found.
        </div>
    <?php endif; ?>
</div>
        </main>
    </div>



<footer class="footer">
        <div>
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="questions.php"><i class="fas fa-question-circle"></i> Questions</a>
            <a href="../logout.php" onclick="return confirm('Logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <div class="copyright">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System
        </div>
    </footer>

</body>
</html>