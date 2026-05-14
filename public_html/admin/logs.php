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

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$conn = getDB();

// Build WHERE clause
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($action_filter)) {
    $where_conditions[] = "l.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if ($user_filter > 0) {
    $where_conditions[] = "l.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(l.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(l.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM user_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE $where_clause
";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_logs = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $limit);

// Get logs with user details
$query = "
    SELECT l.*, 
           u.username, 
           u.fullname, 
           u.role,
           u.student_id
    FROM user_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE $where_clause
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique actions for filter dropdown
$actions_query = "SELECT DISTINCT action FROM user_logs ORDER BY action";
$actions_result = $conn->query($actions_query);
$actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Get users for filter dropdown
$users_query = "SELECT id, username, fullname, role FROM users WHERE role IN ('admin', 'registrar') ORDER BY username";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_logs,
        COUNT(DISTINCT user_id) as active_users,
        MAX(created_at) as last_activity,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_logs,
        SUM(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as week_logs
    FROM user_logs
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get action breakdown
$breakdown_query = "
    SELECT action, COUNT(*) as count 
    FROM user_logs 
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 10
";
$breakdown = $conn->query($breakdown_query)->fetch_all(MYSQLI_ASSOC);

// Clear logs (admin only - with confirmation)
if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === 'yes') {
    if (isset($_POST['confirm_clear']) && $_POST['confirm_clear'] === 'CONFIRM') {
        $conn->query("DELETE FROM user_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $message = "Logs older than 30 days have been cleared.";
        
        // Log this action
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $conn->query("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES ($user_id, 'clear_logs', '$ip', '$agent')");
    } else {
        $error = "Please type CONFIRM to clear old logs.";
    }
}

// Page title
$page_title = 'Activity Logs - Registrar Evaluation';
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
            --log-login: #28a745;
            --log-logout: #dc3545;
            --log-evaluation: #17a2b8;
            --log-question: #fd7e14;
            --log-student: #6f42c1;
            --log-report: #20c997;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: var(--warning-orange);
            color: white;
        }

        .btn-warning:hover {
            background: #e66b00;
        }

        .btn-refresh {
            background: var(--info-blue);
            color: white;
        }

        .btn-refresh:hover {
            background: #138496;
        }

        /* Stats Cards */
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
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
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

        .stat-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--evsu-dark);
            line-height: 1.2;
        }

        .stat-content p {
            color: #777;
            font-size: 0.8rem;
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            font-weight: 500;
        }

        .form-control {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--evsu-red);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Action Breakdown */
        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .breakdown-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 3px solid var(--evsu-red);
        }

        .breakdown-item .action {
            font-size: 0.9rem;
            color: #666;
        }

        .breakdown-item .count {
            font-weight: 700;
            color: var(--evsu-red);
        }

        /* Logs Table */
        .logs-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .table-header h3 {
            color: var(--evsu-dark);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .table-header h3 i {
            color: var(--evsu-red);
            margin-right: 8px;
        }

        .table-header .badge {
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #666;
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
            white-space: nowrap;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            color: #666;
            font-size: 0.9rem;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Action Badges */
        .action-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .action-login { background: #d4edda; color: #155724; }
        .action-logout { background: #f8d7da; color: #721c24; }
        .action-evaluation { background: #d1ecf1; color: #0c5460; }
        .action-question { background: #fff3cd; color: #856404; }
        .action-student { background: #e2d5f2; color: #563d7c; }
        .action-report { background: #d1f0e5; color: #0b5e42; }
        .action-default { background: #e9ecef; color: #495057; }

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin { background: #dc3545; color: white; }
        .role-registrar { background: #fd7e14; color: white; }
        .role-student { background: #28a745; color: white; }

        /* IP Address */
        .ip-address {
            font-family: monospace;
            font-size: 0.85rem;
            color: #666;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: #f8f9fa;
            border-color: #999;
        }

        .page-link.active {
            background: var(--evsu-red);
            color: white;
            border-color: var(--evsu-red);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 400px;
            width: 90%;
            padding: 30px;
            text-align: center;
        }

        .modal-content i {
            font-size: 3rem;
            color: var(--warning-orange);
            margin-bottom: 20px;
        }

        .modal-content h3 {
            margin-bottom: 10px;
        }

        .modal-content p {
            margin-bottom: 20px;
            color: #666;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
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
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .action-badge {
                white-space: normal;
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
                    <div class="subtitle">Activity Logs Monitor</div>
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
            <a href="reports.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                <span>Reports</span>
            </a>
            <a href="logs.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-history"></i></span>
                <span>Activity Logs</span>
            </a>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Content Header -->
            <div class="content-header">
                <h2>
                    <i class="fas fa-history"></i>
                    System Activity Logs
                </h2>
                <div class="header-actions">
                    <a href="logs.php" class="btn btn-refresh">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </a>
                    <button class="btn btn-warning" onclick="showClearModal()">
                        <i class="fas fa-trash-alt"></i> Clear Old Logs
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($message)): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_logs'] ?? 0); ?></h3>
                        <p>Total Logs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['active_users'] ?? 0); ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['today_logs'] ?? 0); ?></h3>
                        <p>Today's Logs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['week_logs'] ?? 0); ?></h3>
                        <p>This Week</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['last_activity'] ? date('H:i', strtotime($stats['last_activity'])) : 'N/A'; ?></h3>
                        <p>Last Activity</p>
                    </div>
                </div>
            </div>


            <!-- Filter Section -->
            <div class="filter-card">
                <h3>
                    <i class="fas fa-filter"></i>
                    Filter Logs
                </h3>
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label>Action Type</label>
                        <select name="action" class="form-control">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $a): ?>
                                <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $action_filter === $a ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>User</label>
                        <select name="user_id" class="form-control">
                            <option value="0">All Users</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $user_filter == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['fullname'] ?? $u['username']); ?> (<?php echo $u['role']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>

                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="logs.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="logs-card">
                <div class="table-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Activity Log Entries
                        <span style="font-size: 0.8rem; color: #999; margin-left: 10px;">
                            Showing <?php echo count($logs); ?> of <?php echo number_format($total_logs); ?>
                        </span>
                    </h3>
                    <span class="badge">
                        <i class="fas fa-clock"></i> Last 50 entries
                    </span>
                </div>

                <?php if (!empty($logs)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <i class="far fa-clock" style="margin-right: 5px; color: #999;"></i>
                                        <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                        <br>
                                        <small style="color: #999;"><?php echo date('h:i:s A', strtotime($log['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['fullname'] ?? $log['username'] ?? 'System'); ?></strong>
                                        <?php if (!empty($log['student_id'])): ?>
                                            <br><small style="color: #999;">ID: <?php echo htmlspecialchars($log['student_id']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $role_class = 'role-default';
                                        $role_text = $log['role'] ?? 'unknown';
                                        if ($role_text === 'admin') $role_class = 'role-admin';
                                        elseif ($role_text === 'registrar') $role_class = 'role-registrar';
                                        elseif ($role_text === 'student') $role_class = 'role-student';
                                        ?>
                                        <span class="role-badge <?php echo $role_class; ?>">
                                            <?php echo ucfirst($role_text); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $action_class = 'action-default';
                                        $action = $log['action'] ?? 'unknown';
                                        
                                        if (strpos($action, 'login') !== false) $action_class = 'action-login';
                                        elseif (strpos($action, 'logout') !== false) $action_class = 'action-logout';
                                        elseif (strpos($action, 'evaluation') !== false) $action_class = 'action-evaluation';
                                        elseif (strpos($action, 'question') !== false) $action_class = 'action-question';
                                        elseif (strpos($action, 'student') !== false || strpos($action, 'user') !== false) $action_class = 'action-student';
                                        elseif (strpos($action, 'report') !== false) $action_class = 'action-report';
                                        ?>
                                        <span class="action-badge <?php echo $action_class; ?>">
                                            <?php echo htmlspecialchars($action); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ip-address">
                                            <i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; font-size: 0.8rem; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                             title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                            <i class="fas fa-desktop"></i> 
                                            <?php 
                                            $ua = $log['user_agent'] ?? 'Unknown';
                                            if (strlen($ua) > 40) {
                                                echo substr($ua, 0, 40) . '...';
                                            } else {
                                                echo htmlspecialchars($ua);
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php 
                            $query_params = [];
                            if (!empty($action_filter)) $query_params['action'] = $action_filter;
                            if ($user_filter > 0) $query_params['user_id'] = $user_filter;
                            if (!empty($date_from)) $query_params['date_from'] = $date_from;
                            if (!empty($date_to)) $query_params['date_to'] = $date_to;
                            $query_string = http_build_query($query_params);
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&<?php echo $query_string; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="page-link active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&<?php echo $query_string; ?>" class="page-link"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>&<?php echo $query_string; ?>" class="page-link">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No activity logs found</p>
                        <?php if (!empty($action_filter) || $user_filter > 0 || !empty($date_from) || !empty($date_to)): ?>
                            <p style="font-size: 0.9rem; margin-top: 10px;">
                                Try clearing your filters.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Log Information -->
            <div style="display: flex; gap: 10px; justify-content: space-between; margin-top: 20px; background: white; padding: 15px; border-radius: 8px;">
                <div>
                    <i class="fas fa-info-circle" style="color: var(--info-blue);"></i>
                    <strong style="margin-left: 5px;">Log Retention:</strong> Logs are kept for 30 days
                </div>
                <div>
                    <i class="fas fa-database" style="color: var(--success-green);"></i>
                    <strong style="margin-left: 5px;">Total Storage:</strong> <?php echo number_format($total_logs); ?> entries
                </div>
            </div>
        </main>
    </div>

    <!-- Clear Logs Modal -->
    <div id="clearModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Clear Old Logs</h3>
            <p>This will delete all logs older than 30 days. Type <strong>CONFIRM</strong> to proceed.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="clear_logs" value="yes">
                <input type="text" name="confirm_clear" placeholder="Type CONFIRM" autocomplete="off">
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideClearModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Clear Logs</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div>
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="../logout.php" onclick="return confirm('Logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <div class="copyright">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System
        </div>
    </footer>

    <script>
        // Modal functions
        function showClearModal() {
            document.getElementById('clearModal').classList.add('show');
        }
        
        function hideClearModal() {
            document.getElementById('clearModal').classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('clearModal');
            if (event.target === modal) {
                hideClearModal();
            }
        }
        
        // Auto-refresh every 60 seconds (optional)
        // setTimeout(function() {
        //     location.reload();
        // }, 60000);
    </script>
</body>
</html>