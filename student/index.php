<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Initialize database connection
$conn = getDB();

// Require student login
requireLogin();
if (!hasRole('student')) {
    header("Location: ../login.php");
    exit();
}

// Initialize Functions class
$functions = new Functions();

// Get current user
$user_id = $_SESSION['user_id'];
// Get user with profile picture
$stmt = $conn->prepare("SELECT id, username, email, role, student_id, fullname, profile_picture, is_active, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get Registrar office (single office only)
$registrar = $functions->getRegistrarOffice();
$office_id = $registrar['id'];

// Check if user has evaluated today
$evaluated_today = $functions->hasUserEvaluatedToday($user_id, $office_id);

// Get user's evaluation history
$evaluation_history = $functions->getUserRegistrarEvaluations($user_id);

// Get evaluation statistics
$stats = [
    'total_evaluations' => count($evaluation_history),
    'last_evaluation' => !empty($evaluation_history) ? $evaluation_history[0]['submitted_at'] : null,
    'average_rating' => 0
];

// Calculate average rating if there are evaluations
if (!empty($evaluation_history)) {
    $total_rating = 0;
    $rating_count = 0;
    foreach ($evaluation_history as $eval) {
        if (isset($eval['rating'])) {
            $total_rating += $eval['rating'];
            $rating_count++;
        }
    }
    $stats['average_rating'] = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
}

// Get total questions count
$questions = $functions->getRegistrarQuestions();
$total_questions = count($questions);

// Get service types for selection
$services = [];
$conn = getDB();
try {
    $result = $conn->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
} catch (Exception $e) {
    // Default services if table doesn't exist
    $services = [
        ['id' => 1, 'name' => 'TOR', 'description' => 'Transcript of Records'],
        ['id' => 2, 'name' => 'Certification', 'description' => 'Certification of Grades'],
        ['id' => 3, 'name' => 'Enrollment', 'description' => 'Enrollment Processing'],
        ['id' => 4, 'name' => 'Diploma', 'description' => 'Diploma Request'],
        ['id' => 5, 'name' => 'Authentication', 'description' => 'Document Authentication']
    ];
}

// Page title
$page_title = 'Student Dashboard - Registrar Evaluation';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Header */


        .header-container {
            position: sticky;
            background: var(--evsu-red);
            color: white;
            padding: 1rem 3rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            position: sticky;
        }

    .header-container {
        position: sticky;
    max-width: 1800px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    position: sticky;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-section img {
    height: 60px;
    width: auto;
}

.title-section h1 {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
    color: white;
}

.title-section .subtitle {
    font-size: 0.8rem;
    opacity: 0.9;
    margin-top: 5px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    background: rgba(255,255,255,0.1);
    padding: 0.5rem 1.5rem;
    border-radius: 40px;
    transition: all 0.3s;
}

.user-info:hover {
    background: rgba(255,255,255,0.15);
}

.user-avatar-link {
    text-decoration: none;
    cursor: pointer;
}

.user-avatar-link:hover .user-avatar {
    transform: scale(1.05);
    transition: transform 0.3s;
}

.user-avatar {
    width: 45px;
    height: 45px;
    background: var(--evsu-gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--evsu-red);
    font-weight: bold;
    font-size: 1.2rem;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-details {
    line-height: 1.4;
}

.user-name {
    font-weight: 600;
    font-size: 1rem;
}

.profile-link {
    color: white;
    text-decoration: none;
}

.profile-link:hover {
    text-decoration: underline;
    color: var(--evsu-gold);
}

.user-role {
    font-size: 0.8rem;
    opacity: 0.8;
}

.logout-link {
    color: white;
    text-decoration: none;
    padding: 8px 20px;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.logout-link:hover {
    background: rgba(255,255,255,0.2);
    color: var(--evsu-gold);
}

/* Responsive */
@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .logo-section {
        flex-direction: column;
    }
    
    .header-right {
        flex-wrap: wrap;
        justify-content: center;
    }
}

        /* Main Layout */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Welcome Card */
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '📋';
            font-size: 8rem;
            position: absolute;
            right: 20px;
            bottom: -20px;
            opacity: 0.1;
        }

        .welcome-card h2 {
            font-size: 2rem;
            margin-bottom: 10px;
            position: relative;
        }

        .welcome-card p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            position: relative;
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
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .stat-icon i {
            background: linear-gradient(135deg, var(--evsu-red), #b71c1c);
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

        .stat-sub {
            font-size: 0.8rem;
            color: #999;
            margin-top: 5px;
        }

        /* Registrar Card */
        .registrar-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--evsu-red);
        }

        .registrar-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .registrar-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--evsu-red) 0%, #b71c1c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
        }

        .registrar-title h3 {
            font-size: 1.5rem;
            color: var(--evsu-dark);
            margin-bottom: 5px;
        }

        .registrar-title p {
            color: #666;
        }

        .registrar-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .registrar-stat {
            flex: 1;
            text-align: center;
        }

        .registrar-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--evsu-red);
        }

        .registrar-stat .label {
            font-size: 0.8rem;
            color: #666;
        }

        /* Service Selection Modal/Card */
        .service-selection-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--evsu-red);
            display: none;
        }

        .service-selection-card h3 {
            color: var(--evsu-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-selection-card h3 i {
            color: var(--evsu-red);
        }

        .service-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .service-btn {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .service-btn:hover {
            transform: translateY(-3px);
            border-color: var(--evsu-red);
            box-shadow: 0 5px 15px rgba(139, 0, 0, 0.2);
        }

        .service-icon {
            font-size: 2rem;
            color: var(--evsu-red);
            margin-bottom: 10px;
        }

        .service-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--evsu-dark);
            margin-bottom: 5px;
        }

        .service-desc {
            font-size: 0.7rem;
            color: #888;
        }

        .cancel-btn {
            margin-top: 20px;
            text-align: center;
        }

        .cancel-link {
            color: #999;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .cancel-link:hover {
            color: var(--evsu-red);
        }

        .evaluate-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--evsu-red) 0%, #b71c1c 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            margin-top: 15px;
        }

        .evaluate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(139, 0, 0, 0.4);
        }

        .evaluate-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .evaluate-btn i {
            margin-right: 10px;
        }

        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            color: var(--evsu-dark);
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card h3 i {
            color: var(--evsu-red);
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
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
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
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
            font-size: 0.9rem;
        }

        .rating-stars i.empty {
            color: #ddd;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
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
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: white;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Quick Tips */
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .tip-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .tip-item i {
            color: var(--evsu-red);
            font-size: 1.2rem;
        }

        .tip-item span {
            color: #666;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .registrar-header {
                flex-direction: column;
                text-align: center;
            }
            
            .registrar-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
        .user-avatar-link {
    text-decoration: none;
    cursor: pointer;
}


    </style>
</head>
<body>
    <header class="evsu-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../images/EVSU_Official_Logo.png" alt="EVSU Logo">
                <div class="title-section">
                    <h1>EVSU - Ormoc Campus</h1>
                    <div class="subtitle">Student Evaluation Portal</div>
                </div>
            </div>
            
            <div class="header-right">
                <div class="user-info">
                    <a href="profile.php" class="user-avatar-link">
                    <div class="user-avatar">
    <?php 
        if (!empty($user['profile_picture']) && file_exists('../uploads/profile_pictures/' . $user['profile_picture'])): 
    ?>
        <img src="../uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
    <?php else: ?>
        <?php echo strtoupper(substr($user['fullname'] ?? $user['username'] ?? 'S', 0, 1)); ?>
    <?php endif; ?>
</div>
                    </a>
                    <div class="user-details">
                        <div class="user-name">
                            <a href="profile.php" class="profile-link"><?php echo htmlspecialchars($user['fullname'] ?? $user['username']); ?></a>
                        </div>
                        <div class="user-role">
                            <i class="fas fa-id-card"></i> 
                            <?php echo htmlspecialchars($user['student_id'] ?? 'Student'); ?>
                        </div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2>
                <i class="fas fa-hand-peace"></i> 
                Hello, <?php echo htmlspecialchars(explode(' ', ($user['fullname'] ?? $user['username']))[0]); ?>!
            </h2>
            <p>Your feedback helps us improve the Registrar's Office services. Please take a moment to evaluate your experience.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['average_rating'] ?? '0.0'; ?></div>
                    <div class="stat-label">Average Rating</div>
                    <div class="stat-sub">Across all evaluations</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_evaluations']; ?></div>
                    <div class="stat-label">Total Evaluations</div>
                    <div class="stat-sub">Lifetime</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['last_evaluation'] ? date('M d', strtotime($stats['last_evaluation'])) : 'N/A'; ?></div>
                    <div class="stat-label">Last Evaluation</div>
                    <div class="stat-sub"><?php echo $stats['last_evaluation'] ? date('Y', strtotime($stats['last_evaluation'])) : ''; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_questions; ?></div>
                    <div class="stat-label">Survey Questions</div>
                    <div class="stat-sub">To rate</div>
                </div>
            </div>
        </div>

        <!-- Registrar Office Card -->
        <div class="registrar-card">
            <div class="registrar-header">
                <div class="registrar-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="registrar-title">
                    <h3>Registrar's Office</h3>
                    <p>Office of the University Registrar - Student Records, TOR, Certifications, and Enrollment</p>
                </div>
            </div>

            <div class="registrar-stats">
                <div class="registrar-stat">
                    <div class="value"><?php echo $total_questions; ?></div>
                    <div class="label">Questions to Answer</div>
                </div>
                <div class="registrar-stat">
                    <div class="value">~5 min</div>
                    <div class="label">Average Time</div>
                </div>
                <div class="registrar-stat">
                    <div class="value"><?php echo $evaluated_today ? 'Done' : 'Pending'; ?></div>
                    <div class="label">Today's Status</div>
                </div>
            </div>

            <?php if ($evaluated_today): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <div>
                        <strong>You've already evaluated today!</strong><br>
                        Thank you for your feedback. You can submit another evaluation tomorrow.
                    </div>
                </div>
                <a href="#" class="evaluate-btn" style="opacity: 0.7; cursor: not-allowed;" onclick="return false;">
                    <i class="fas fa-check-circle"></i> Already Evaluated Today
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" class="evaluate-btn" id="showServiceBtn">
                    <i class="fas fa-star"></i> Evaluate Registrar's Office Now
                </a>
            <?php endif; ?>
        </div>

        <!-- Service Selection Card (Hidden by default) -->
        <div class="service-selection-card" id="serviceSelectionCard">
            <h3>
                <i class="fas fa-tag"></i>
                Select Service Type
            </h3>
            <p style="color: #666; margin-bottom: 15px;">Please select the specific Registrar service you availed:</p>
            
            <div class="service-buttons">
    <?php foreach ($services as $service): ?>
        <a href="evaluate.php?service_id=<?php echo $service['id']; ?>" class="service-btn">
            <div class="service-icon">
                <?php 
                    $icon = 'fa-tag';
                    if ($service['name'] == 'TOR') $icon = 'fa-file-alt';
                    elseif ($service['name'] == 'Certification') $icon = 'fa-certificate';
                    elseif ($service['name'] == 'Enrollment') $icon = 'fa-user-plus';
                    elseif ($service['name'] == 'Diploma') $icon = 'fa-graduation-cap';
                    elseif ($service['name'] == 'Authentication') $icon = 'fa-stamp';
                ?>
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <div class="service-name"><?php echo htmlspecialchars($service['name'] ?? ''); ?></div>
            <div class="service-desc"><?php echo htmlspecialchars($service['description'] ?? ''); ?></div>
        </a>
    <?php endforeach; ?>
</div>
            
            <div class="cancel-btn">
                <a href="javascript:void(0)" class="cancel-link" id="cancelServiceBtn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </div>

    

        <!-- Quick Tips -->
        <div class="info-card">
            <h3>
                <i class="fas fa-lightbulb"></i>
                Quick Tips for Evaluation
            </h3>
            <div class="tips-grid">
                <div class="tip-item">
                    <i class="fas fa-star"></i>
                    <span>Rate honestly based on experience</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-comment"></i>
                    <span>Provide specific feedback</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-clock"></i>
                    <span>Evaluations take about 5 minutes</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>All responses are anonymous</span>
                </div>
            </div>
        </div>

        <!-- Important Notice -->
        <div class="info-card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3 style="color: #856404;">
                <i class="fas fa-info-circle"></i>
                Important Reminders
            </h3>
            <ul style="margin-left: 20px; color: #856404;">
                <li>You can only evaluate the Registrar's Office once per day</li>
                <li>All ratings are on a scale of 1 (Very Poor) to 5 (Excellent)</li>
                <li>Your feedback is confidential and will be used for service improvement</li>
                <li>Please evaluate based on your actual experience with the Registrar's Office</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System | 
            <i class="fas fa-building"></i> Registrar's Office | 
            <i class="fas fa-user-graduate"></i> Student Portal
        </p>
    </footer>

    <script>
        // Show/Hide Service Selection Card
        const showServiceBtn = document.getElementById('showServiceBtn');
        const serviceCard = document.getElementById('serviceSelectionCard');
        const cancelBtn = document.getElementById('cancelServiceBtn');

        if (showServiceBtn) {
            showServiceBtn.addEventListener('click', function() {
                serviceCard.style.display = 'block';
                serviceCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                serviceCard.style.display = 'none';
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>