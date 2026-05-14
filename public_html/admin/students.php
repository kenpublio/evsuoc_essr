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

// Handle student status updates
$message = '';
$error = '';

// Toggle student active status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    $conn = getDB();
    
    // Get current status
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if ($student) {
        $new_status = $student['is_active'] ? 0 : 1;
        $update = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $update->bind_param("ii", $new_status, $student_id);
        
        if ($update->execute()) {
            $message = $new_status ? 'Student account activated' : 'Student account deactivated';
            // Log action
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $action = $new_status ? 'activate_student' : 'deactivate_student';
            $conn->query("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES ($user_id, '$action', '$ip', '$agent')");
        } else {
            $error = 'Failed to update student status';
        }
    }
}

// Reset student password
if (isset($_GET['reset_password']) && isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    $conn = getDB();
    
    // Generate random password
    $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'student'");
    $update->bind_param("si", $hashed_password, $student_id);
    
    if ($update->execute()) {
        $message = "Password reset successfully. New password: $new_password";
        // Log action
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $conn->query("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES ($user_id, 'reset_student_password', '$ip', '$agent')");
    } else {
        $error = 'Failed to reset password';
    }
}

// Delete student (only if no evaluations)
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    $conn = getDB();
    
    // Check if student has evaluations
    $check = $conn->prepare("SELECT COUNT(*) as count FROM responses WHERE user_id = ?");
    $check->bind_param("i", $student_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = 'Cannot delete student who has submitted evaluations. You can deactivate the account instead.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            $message = 'Student deleted successfully';
            // Log action
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $conn->query("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES ($user_id, 'delete_student', '$ip', '$agent')");
        } else {
            $error = 'Failed to delete student';
        }
    }
}

// Search and pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$conn = getDB();
$where_clause = "WHERE role = 'student'";

if (!empty($search)) {
    $search_term = '%' . $conn->real_escape_string($search) . '%';
    $where_clause .= " AND (username LIKE '$search_term' OR fullname LIKE '$search_term' OR email LIKE '$search_term' OR student_id LIKE '$search_term')";
}

if ($status_filter === 'active') {
    $where_clause .= " AND is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_clause .= " AND is_active = 0";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_result = $conn->query($count_query);
$total_students = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_students / $limit);

// Get students
$query = "SELECT id, username, fullname, email, student_id, is_active, created_at, last_login 
          FROM users 
          $where_clause 
          ORDER BY created_at DESC 
          LIMIT $offset, $limit";
$students = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get evaluation stats for each student
foreach ($students as &$student) {
    $eval_query = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(submitted_at)) as eval_count,
               AVG(rating) as avg_rating,
               MAX(submitted_at) as last_eval
        FROM responses 
        WHERE user_id = ?
    ");
    $eval_query->bind_param("i", $student['id']);
    $eval_query->execute();
    $stats = $eval_query->get_result()->fetch_assoc();
    
    $student['evaluation_count'] = $stats['eval_count'] ?? 0;
    $student['average_rating'] = $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : 'N/A';
    $student['last_evaluation'] = $stats['last_eval'] ?? 'Never';
}

// Get overall stats
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_students,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_students
    FROM users 
    WHERE role = 'student'
");
$overall_stats = $stats_query->fetch_assoc();

$total_evaluations_query = $conn->query("SELECT COUNT(DISTINCT user_id) as evaluated FROM responses");
$evaluated_count = $total_evaluations_query->fetch_assoc()['evaluated'];

// Page title
$page_title = 'Manage Students - Registrar Evaluation';
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--evsu-red);
        }

        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--evsu-dark);
            line-height: 1.2;
        }

        .stat-content p {
            color: #777;
            font-size: 0.9rem;
        }

        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .filter-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-width: 150px;
        }

        /* Table Card */
        .table-card {
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

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            color: #666;
            font-size: 0.9rem;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .rating-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: #e3f2fd;
            color: #0c5460;
            font-size: 0.8rem;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
        }

        .action-btn i {
            font-size: 0.8rem;
        }

        .action-btn.view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .action-btn.view:hover {
            background: #bbdefb;
        }

        .action-btn.toggle {
            background: #fff3cd;
            color: #856404;
        }

        .action-btn.toggle:hover {
            background: #ffe69c;
        }

        .action-btn.reset {
            background: #d1ecf1;
            color: #0c5460;
        }

        .action-btn.reset:hover {
            background: #bee5eb;
        }

        .action-btn.delete {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn.delete:hover {
            background: #f5c6cb;
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
            max-width: 500px;
            width: 90%;
            padding: 30px;
            text-align: center;
        }

        .modal-content i {
            font-size: 4rem;
            color: var(--warning-orange);
            margin-bottom: 20px;
        }

        .modal-content h3 {
            margin-bottom: 15px;
            color: var(--evsu-dark);
        }

        .modal-content p {
            margin-bottom: 25px;
            color: #666;
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
                flex-direction: column;
                align-items: stretch;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .action-group {
                flex-direction: column;
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
                    <div class="subtitle">Student Management</div>
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
            <a href="students.php" class="nav-item active">
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

        <!-- Main Content -->
        <main class="content">
            <!-- Content Header -->
            <div class="content-header">
                <h2>
                    <i class="fas fa-user-graduate"></i>
                    Manage Students
                </h2>
                <div class="header-actions">
                    <a href="add_student.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Add New Student
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($overall_stats['total_students'] ?? 0); ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle" style="color: var(--success-green);"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($overall_stats['active_students'] ?? 0); ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ban" style="color: #dc3545;"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($overall_stats['inactive_students'] ?? 0); ?></h3>
                        <p>Inactive Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($evaluated_count ?? 0); ?></h3>
                        <p>Have Evaluated</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <form method="GET" action="" class="filter-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by name, ID, or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <select name="status" class="filter-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Students</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <?php if (!empty($search) || $status_filter !== 'all'): ?>
                        <a href="students.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Students Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Student List
                        <span style="font-size: 0.8rem; color: #999; margin-left: 10px;">
                            Showing <?php echo count($students); ?> of <?php echo number_format($total_students); ?>
                        </span>
                    </h3>
                </div>

                <?php if (!empty($students)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Evaluations</th>
                                <th>Avg Rating</th>
                                <th>Last Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>#<?php echo $student['id']; ?></td>
                                    <td>
                                        <span class="rating-badge">
                                            <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['fullname'] ?? $student['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" style="color: #666; text-decoration: none;">
                                            <i class="far fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $student['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <i class="fas fa-<?php echo $student['is_active'] ? 'check-circle' : 'minus-circle'; ?>"></i>
                                            <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="rating-badge">
                                            <?php echo $student['evaluation_count']; ?> time<?php echo $student['evaluation_count'] != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['average_rating'] !== 'N/A'): ?>
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <span style="color: #ffd700;">★</span>
                                                <strong><?php echo $student['average_rating']; ?></strong>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['last_evaluation'] !== 'Never'): ?>
                                            <span title="<?php echo date('F j, Y, g:i a', strtotime($student['last_evaluation'])); ?>">
                                                <?php echo date('M d, Y', strtotime($student['last_evaluation'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="student_view.php?id=<?php echo $student['id']; ?>" class="action-btn view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?toggle_status=1&id=<?php echo $student['id']; ?>" 
                                               class="action-btn toggle" 
                                               onclick="return confirm('Toggle active status for this student?')"
                                               title="<?php echo $student['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-<?php echo $student['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="?reset_password=1&id=<?php echo $student['id']; ?>" 
                                               class="action-btn reset" 
                                               onclick="return confirm('Reset password for this student? The new password will be shown.')"
                                               title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <?php if ($student['evaluation_count'] == 0): ?>
                                                <a href="?delete=1&id=<?php echo $student['id']; ?>" 
                                                   class="action-btn delete" 
                                                   onclick="return confirm('Are you sure you want to delete this student? This cannot be undone.')"
                                                   title="Delete Student">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="page-link active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>" 
                                       class="page-link"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>" class="page-link">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <p>No students found</p>
                        <?php if (!empty($search)): ?>
                            <p style="font-size: 0.9rem; margin-top: 10px;">
                                Try adjusting your search criteria.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <a href="export_students.php" class="btn btn-outline">
                    <i class="fas fa-download"></i> Export Student List
                </a>
                <a href="send_notification.php" class="btn btn-outline">
                    <i class="fas fa-bell"></i> Send Notification
                </a>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div>
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="../logout.php" onclick="return confirm('Logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <div class="copyright">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System
        </div>
    </footer>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>