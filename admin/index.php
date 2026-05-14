<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require admin access
requireRole('admin');

// Initialize Functions class
$functions = new Functions();

// Get current user
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get dashboard statistics using the Functions class
$stats = $functions->getAdminStats();

// Get system summary
$system_summary = $functions->getSystemSummary();

// Check if backup directory exists and count backups
$backup_dir = dirname(__DIR__) . '/backups/';
$backup_count = 0;
$latest_backup = 'Never';
if (is_dir($backup_dir)) {
    $backups = glob($backup_dir . 'backup_*.sql');
    $backup_count = count($backups);
    if ($backup_count > 0) {
        // Sort by modified time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $latest_backup = date('Y-m-d H:i', filemtime($backups[0]));
    }
}

// Get database connection
$conn = getDB();

// Get selected service from URL
$selected_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Fetch all services for the filter dropdown
$services = [];
$query_services = "SELECT id, name FROM service_types WHERE is_active = 1 ORDER BY name";
$result = $conn->query($query_services);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get recent evaluations based on selected service - GROUP BY to remove duplicates
if ($selected_service_id > 0) {
    $query = "
        SELECT 
            r.id,
            r.user_id,
            r.service_type_id,
            r.rating,
            r.answer,
            r.submitted_at,
            u.username,
            u.fullname,
            u.student_id,
            st.name as service_name
        FROM responses r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN service_types st ON r.service_type_id = st.id
        WHERE r.service_type_id = ?
        GROUP BY r.user_id, DATE(r.submitted_at), r.service_type_id
        ORDER BY r.submitted_at DESC
        LIMIT 20
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_service_id);
} else {
    $query = "
        SELECT 
            r.id,
            r.user_id,
            r.service_type_id,
            r.rating,
            r.answer,
            r.submitted_at,
            u.username,
            u.fullname,
            u.student_id,
            st.name as service_name
        FROM responses r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN service_types st ON r.service_type_id = st.id
        GROUP BY r.user_id, DATE(r.submitted_at), r.service_type_id
        ORDER BY r.submitted_at DESC
        LIMIT 20
    ";
    $stmt = $conn->prepare($query);
}
$stmt->execute();
$recent_evaluations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Page title
$page_title = 'Admin Dashboard - EVSU Evaluation System';
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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        :root {
            --evsu-red: #8B0000;
            --evsu-yellow: #FFD700;
            --evsu-orange: #FF8C00;
            --evsu-blue: #0047AB;
            --evsu-green: #2E7D32;
            --evsu-gray: #f5f5f5;
            --evsu-dark: #333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--evsu-gray);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .evsu-header {
            background: var(--evsu-red);
            color: white;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-container img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        
        .logo-container h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .logo-container .subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 2px;
        }
        
        /* User Info */
        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.1);
            padding: 8px 20px 8px 15px;
            border-radius: 40px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--evsu-yellow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--evsu-red);
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
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
        
        .user-action-link {
            color: white;
            text-decoration: none;
            font-size: 0.75rem;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .user-action-link:hover {
            opacity: 1;
            color: var(--evsu-yellow);
        }
        
        .separator {
            opacity: 0.3;
        }
        
        /* Layout */
        .main-layout {
            display: flex;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 20px;
            gap: 20px;
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
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-section-title {
            padding: 0 20px;
            margin-bottom: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: #999;
            text-transform: uppercase;
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
            background: #f8f9fa;
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
        
        /* NEW: Badge for new features */
        .nav-badge {
            background: var(--evsu-red);
            color: white;
            font-size: 0.6rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
            text-transform: uppercase;
        }
        
        /* Main Content */
        .main-content {
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
        
        .date-badge {
            background: var(--evsu-gray);
            padding: 8px 15px;
            border-radius: 40px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .date-badge i {
            margin-right: 5px;
            color: var(--evsu-red);
        }
        
        /* Quick Action Buttons - NEW */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--evsu-dark);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139, 0, 0, 0.15);
            border-color: var(--evsu-red);
        }
        
        .quick-action-card i {
            font-size: 2rem;
            color: var(--evsu-red);
            margin-bottom: 10px;
        }
        
        .quick-action-card h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .quick-action-card p {
            font-size: 0.8rem;
            color: #999;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .stat-icon i {
            background: linear-gradient(135deg, var(--evsu-red), #B22222);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--evsu-dark);
            line-height: 1.2;
        }
        
        .stat-label {
            color: #777;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-change {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .stat-change.positive {
            color: #2ecc71;
        }
        
        .stat-change.negative {
            color: #e74c3c;
        }
        .logout-link {
    color: white;
    text-decoration: none;
}

.logout-link:hover {
    color: var(--evsu-gold);
}
        
        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--evsu-gray);
        }
        
        .card-header h3 {
            color: var(--evsu-dark);
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .card-header h3 i {
            color: var(--evsu-red);
            margin-right: 10px;
        }
        
        .card-header .badge {
            background: var(--evsu-gray);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
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
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #666;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table th i {
            margin-right: 5px;
            color: var(--evsu-red);
            font-size: 0.8rem;
        }
        
        /* Rating Stars */
        .rating-stars {
            display: inline-flex;
            gap: 2px;
            margin-right: 5px;
        }
        
        .rating-stars i {
            color: #ffd700;
            font-size: 0.9rem;
        }
        
        .rating-stars i.empty {
            color: #ddd;
        }
        
        /* Badges */
        .badge-success {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #0d47a1;
            border-left: 4px solid #2196f3;
        }
        
        .alert i {
            font-size: 1.1rem;
        }
        
        /* NEW: Backup Status */
        .backup-status {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .backup-status i {
            font-size: 2rem;
            color: var(--evsu-green);
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-info h4 {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .backup-info p {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Footer */
        .footer {
            background: var(--evsu-dark);
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: auto;
        }
        
        .footer-links {
            margin-bottom: 15px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--evsu-yellow);
        }
        
        .footer-links i {
            margin-right: 5px;
        }
        
        .copyright {
            font-size: 0.85rem;
            color: #777;
        }
        
        .copyright i {
            margin-right: 5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-user-info {
                padding: 8px 12px;
            }
            
            .user-actions {
                flex-wrap: wrap;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }

        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="evsu-header">
        <div class="header-container">
            <div class="logo-container">
            <img src="../images/EVSU_Official_Logo.png" alt="EVSU Logo" onerror="this.src='https://via.placeholder.com/60x60?text=EVSU'">
                <div>
                    <h1>EVSU - Ormoc Campus</h1>
                    <div class="subtitle">Administrator Dashboard</div>
                </div>
            </div>
            
            <div class="admin-user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['fullname'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name">
                        <?php echo htmlspecialchars($user['fullname'] ?? $user['username'] ?? 'Administrator'); ?>
                    </div>
                    <div class="user-role">Administrator</div>
                    <div class="user-actions">
                        <a href="profile.php" class="user-action-link">
                            <i class="fas fa-user-cog"></i> Profile
                        </a>
                        <span class="separator">|</span>
                        <a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="main-layout">
        <!-- Sidebar Navigation - UPDATED with new links -->
        <aside class="sidebar">
            <div class="nav-section">
                <div class="nav-section-title">Main Navigation</div>
                <a href="index.php" class="nav-item active">
                    <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
                </a>
                <a href="questions.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span>Survey Questions</span>
                </a>
                <a href="students.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-users"></i></span>
                    <span>Manage Student</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Reports & Analytics</div>
                <!-- NEW: Monthly Reports Link -->
                <a href="monthly_reports.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                    <span>Monthly Reports</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                    <span>Custom Reports</span>
                </a>
                <a href="logs.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-history"></i></span>
                    <span>Activity Logs</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">System Settings</div>
                <!-- NEW: Survey Settings Link -->
                <a href="survey_settings.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-toggle-on"></i></span>
                    <span>Survey Settings</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <!-- NEW: Backup & Restore Links -->
                <a href="restore_backup.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-database"></i></span>
                    <span>Backup & Restore</span>
                    <span class="nav-badge">NEW</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-cog"></i></span>
                    <span>System Settings</span>
                </a>
            </div>
            
            
            <div class="nav-section">
                <div class="nav-section-title">System Info</div>
                <div style="padding: 0 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #999;">Version:</span>
                        <span style="color: var(--evsu-red); font-weight: 600;">2.0.0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #999;">Backups:</span>
                        <span class="badge-success" style="background: #e8f5e8; color: #2e7d32;">
                            <?php echo $backup_count; ?> files
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Content Header -->
            <div class="content-header">
                <h2>
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard Overview
                </h2>
                <div class="date-badge">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php displayFlashMessages(); ?>

            <!-- NEW: Quick Action Cards -->
            <div class="quick-actions">
                <a href="monthly_reports.php" class="quick-action-card">
                    <i class="fas fa-chart-bar"></i>
                    <h4>Monthly Reports</h4>
                    <p>Generate and export reports</p>
                </a>
                <a href="survey_settings.php" class="quick-action-card">
                    <i class="fas fa-toggle-on"></i>
                    <h4>Survey Settings</h4>
                    <p>Activate/deactivate surveys</p>
                </a>
                <a href="restore_backup.php" class="quick-action-card">
                    <i class="fas fa-database"></i>
                    <h4>Backup & Restore</h4>
                    <p><?php echo $backup_count; ?> backups available</p>
                </a>
                <a href="export_report.php?format=csv" class="quick-action-card">
                    <i class="fas fa-download"></i>
                    <h4>Export Data</h4>
                    <p>CSV format</p>
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card" style="animation-delay: 0.1s;">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_students'] ?? 0); ?></div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> Active Users
                        </div>
                    </div>
                </div>


                <div class="stat-card" style="animation-delay: 0.3s;">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_evaluations'] ?? 0); ?></div>
                        <div class="stat-label">Total Evaluations</div>
                        <div class="stat-change">
                            <i class="fas fa-calendar"></i> All time
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay: 0.4s;">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['average_rating'] ?? 0, 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                        <div class="stat-change">
                            <?php
                            $avg = $stats['average_rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++) {
                                $starClass = $i <= round($avg) ? 'fas fa-star' : 'far fa-star';
                                echo '<i class="' . $starClass . '" style="color: #ffd700;"></i> ';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row of Stats -->
            <div class="stats-grid">
                <div class="stat-card" style="animation-delay: 0.5s;">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_admins'] ?? 0); ?></div>
                        <div class="stat-label">Administrators</div>
                        <div class="stat-change positive">
                            <i class="fas fa-shield-alt"></i> System managers
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay: 0.6s;">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['active_today'] ?? 0); ?></div>
                        <div class="stat-label">Active Today</div>
                        <div class="stat-change">
                            <i class="fas fa-clock"></i> Last 24 hours
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay: 0.7s;">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['logs_today'] ?? 0); ?></div>
                        <div class="stat-label">Today's Activities</div>
                        <div class="stat-change">
                            <i class="fas fa-list"></i> System logs
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay: 0.8s;">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Top Performing Office</div>
                        <div class="stat-change">
                            Rating: <?php echo number_format($stats['top_office']['avg_rating'] ?? 0, 1); ?> ★
                        </div>
                    </div>
                </div>
            </div>

            <!-- NEW: Backup Status Card -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-database"></i>
                        Backup Status
                    </h3>
                    <span class="badge">
                        <i class="fas fa-clock"></i> Daily at 2:00 AM
                    </span>
                </div>
                <div class="backup-status">
                    <i class="fas fa-shield-alt"></i>
                    <div class="backup-info">
                        <h4>Backup System Status: <span class="badge-success" style="background: #d4edda;">Active</span></h4>
                        <p>Total Backups: <strong><?php echo $backup_count; ?></strong> | Latest Backup: <strong><?php echo $latest_backup; ?></strong></p>
                        <p>Automatic backups run daily. Last 30 backups are retained.</p>
                    </div>
                    <a href="restore_backup.php" class="btn btn-primary" style="background: var(--evsu-red); color: white; padding: 8px 16px; border-radius: 5px; text-decoration: none;">
                        <i></i> Manage
                    </a>
                </div>
            </div>

           <!-- Recent Evaluations Card -->
<div class="card">
    <div class="card-header">
        <h3>
            <i class="fas fa-history"></i>
            Recent Evaluations
            <?php if ($selected_service_id > 0): ?>
                <span style="font-size: 14px; background: var(--evsu-red); color: white; padding: 3px 10px; border-radius: 20px; margin-left: 10px;">
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
        <span class="badge">
            <i class="fas fa-sync"></i> Last 20 entries
        </span>
    </div>

    <?php if (!empty($recent_evaluations)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Student</th>
                        <th><i class="fas fa-tag"></i> Service</th>
                        <th><i class="fas fa-star"></i> Rating</th>
                        <th><i class="fas fa-calendar"></i> Date & Time</th>
                        <th><i class="fas fa-comment"></i> Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_evaluations as $eval): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($eval['fullname'] ?? $eval['username'] ?? 'Unknown'); ?></strong>
                                <?php if (!empty($eval['student_id'])): ?>
                                    <br><small style="color: #999;">ID: <?php echo htmlspecialchars($eval['student_id']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="padding: 5px 12px; background: #e8f5e9; color: #2e7d32; border-radius: 20px; font-size: 12px;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($eval['service_name'] ?? 'General'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="rating-stars" style="display: inline-flex; gap: 2px;">
                                    <?php
                                    $rating = intval($eval['rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $rating):
                                    ?>
                                        <i class="fas fa-star" style="color: #ffc107;"></i>
                                    <?php else: ?>
                                        <i class="far fa-star" style="color: #ddd;"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <strong style="margin-left: 5px;"><?php echo $rating; ?>/5</strong>
                            </td>
                            <td>
                                <i class="far fa-calendar-alt" style="margin-right: 5px; color: #999;"></i>
                                <?php echo date('M d, Y h:i A', strtotime($eval['submitted_at'] ?? 'now')); ?>
                            </td>
                            <td>
                                <?php if (!empty($eval['answer'])): ?>
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                        <i class="fas fa-check-circle"></i> With feedback
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                        <i class="fas fa-minus-circle"></i> No feedback
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($recent_evaluations) >= 20): ?>
            <div style="text-align: right; margin-top: 20px;">
                <a href="reports.php" class="btn-view-all" style="color: var(--evsu-red); text-decoration: none;">
                    View All Reports <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            No evaluations have been submitted yet for this service. Check back later for data.
        </div>
    <?php endif; ?>
</div>

            <!-- Quick Stats Summary -->
            <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-chart-pie"></i>
                            System Summary
                        </h3>
                    </div>
                    <div style="padding: 10px 0;">
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee;">
                            <span><i class="fas fa-check-circle" style="color: #2ecc71;"></i> Active Offices</span>
                            <span class="badge-success"><?php echo $system_summary['active_offices'] ?? 0; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee;">
                            <span><i class="fas fa-times-circle" style="color: #e74c3c;"></i> Inactive Offices</span>
                            <span class="badge-warning"><?php echo $system_summary['inactive_offices'] ?? 0; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee;">
                            <span><i class="fas fa-clipboard-list"></i> Survey Questions</span>
                            <span class="badge-info"><?php echo $system_summary['total_questions'] ?? 0; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                            <span><i class="fas fa-star" style="color: #ffd700;"></i> Overall Rating</span>
                            <span><strong><?php echo number_format($system_summary['overall_rating'] ?? 0, 1); ?></strong> / 5.0</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-calendar-week"></i>
                            This Week's Activity
                        </h3>
                    </div>
                    <div style="padding: 10px 0;">
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee;">
                            <span><i class="fas fa-user-plus"></i> New Students</span>
                            <span class="badge-success">+<?php echo $system_summary['new_students_week'] ?? 0; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee;">
                            <span><i class="fas fa-clipboard-check"></i> New Evaluations</span>
                            <span class="badge-info"><?php echo $system_summary['evaluations_week'] ?? 0; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                            <span><i class="fas fa-users"></i> Active Users</span>
                            <span class="badge-warning"><?php echo $system_summary['active_users_week'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-links">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="monthly_reports.php"><i class="fas fa-chart-bar"></i> Monthly Reports</a>
            <a href="survey_settings.php"><i class="fas fa-toggle-on"></i> Survey Settings</a>
            <a href="restore_backup.php"><i class="fas fa-database"></i> Backup</a>
            <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" style="color: white;">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
        </div>
        <div class="copyright">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System | 
            <span class="badge-success" style="background: #2e7d32; color: white; padding: 2px 8px;">Version 2.0</span>
        </div>
    </footer>

    <script>
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation delay to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>