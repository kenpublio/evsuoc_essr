<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require student login
requireLogin();
if (!hasRole('student')) {
    header("Location: ../login.php");
    exit();
}

// Initialize Functions class
$functions = new Functions();
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get Registrar office (single office only)
$registrar = $functions->getRegistrarOffice();
$office_id = $registrar['id'];

// Get database connection
$conn = getDB();

// ============================================
// CREATE/CHECK REQUIRED TABLES AND COLUMNS
// ============================================

try {
    // Create service_types table if it doesn't exist (US-02)
    $conn->query("
        CREATE TABLE IF NOT EXISTS service_types (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default service types if table is empty
    $check = $conn->query("SELECT COUNT(*) as count FROM service_types");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['count'] == 0) {
            $conn->query("INSERT INTO service_types (name, description) VALUES 
                ('TOR', 'Transcript of Records Request'),
                ('Certification', 'Certification of Grades/Enrollment'),
                ('Enrollment', 'Enrollment Processing'),
                ('Diploma', 'Diploma Request'),
                ('Authentication', 'Document Authentication')");
        }
    }

    // Add service_type_id column to responses if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM responses LIKE 'service_type_id'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE responses ADD COLUMN service_type_id INT AFTER office_id");
    }

    // Create survey_availability table if it doesn't exist (US-12)
    $conn->query("
        CREATE TABLE IF NOT EXISTS survey_availability (
            id INT PRIMARY KEY AUTO_INCREMENT,
            is_active BOOLEAN DEFAULT FALSE,
            start_date DATE,
            end_date DATE,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Insert default survey settings if none exist
    $check = $conn->query("SELECT COUNT(*) as count FROM survey_availability");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['count'] == 0) {
            $conn->query("INSERT INTO survey_availability (is_active, start_date, end_date) VALUES (1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))");
        }
    }

} catch (Exception $e) {
    error_log("Database setup error: " . $e->getMessage());
}

// ============================================
// GET DATA FOR DISPLAY
// ============================================

// Get active service types
$services = [];
try {
    $result = $conn->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching services: " . $e->getMessage());
    // Use default services if query fails
    $services = [
        ['id' => 1, 'name' => 'TOR', 'description' => 'Transcript of Records'],
        ['id' => 2, 'name' => 'Certification', 'description' => 'Certification of Grades'],
        ['id' => 3, 'name' => 'Enrollment', 'description' => 'Enrollment Processing'],
        ['id' => 4, 'name' => 'Diploma', 'description' => 'Diploma Request'],
        ['id' => 5, 'name' => 'Authentication', 'description' => 'Document Authentication']
    ];
}

// Check if survey is active (US-12)
$survey_active = true;
try {
    $settings = $conn->query("SELECT * FROM survey_availability ORDER BY id DESC LIMIT 1");
    if ($settings && $settings->num_rows > 0) {
        $setting = $settings->fetch_assoc();
        $today = date('Y-m-d');
        $survey_active = $setting['is_active'] && $today >= $setting['start_date'] && $today <= $setting['end_date'];
    }
} catch (Exception $e) {
    error_log("Error checking survey availability: " . $e->getMessage());
}

// Check if user already evaluated today
$already_evaluated = $functions->hasUserEvaluatedToday($user_id, $office_id);

// Get survey questions for Registrar - ONLY ONCE
$questions = $functions->getRegistrarQuestions();

// TEMPORARY DEBUG - Remove after fixing
error_log("Number of questions loaded: " . count($questions));
foreach ($questions as $index => $q) {
    error_log("Question " . ($index+1) . ": " . ($q['question_text'] ?? $q));
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_evaluated && $survey_active) {
    
    // Get service type
    $service_type = isset($_POST['service_type']) ? (int)$_POST['service_type'] : 0;
    
    // Validate service type
    if ($service_type === 0) {
        $error = "Please select a service type.";
    } else {
        $ratings = [];
        $answers = [];
        $report = isset($_POST['report']) ? trim($_POST['report']) : '';
        
        // Collect ratings from form
        foreach ($questions as $index => $q) {
            $rating_key = 'rating_' . ($index + 1);
            if (isset($_POST[$rating_key]) && !empty($_POST[$rating_key])) {
                $ratings[$index] = (int)$_POST[$rating_key];
            }
            
            $answer_key = 'answer_' . ($index + 1);
            if (isset($_POST[$answer_key]) && !empty($_POST[$answer_key])) {
                $answers[$index] = trim($_POST[$answer_key]);
            }
        }
        
        // Validate all questions are answered
        if (count($ratings) !== count($questions)) {
            $error = "Please rate all " . count($questions) . " questions before submitting.";
        } else {
            // Submit evaluation with service type
            $result = $functions->submitRegistrarEvaluationWithService($user_id, $service_type, $ratings, $answers, $report);
            
            if ($result['success']) {
                $success = true;
                $message = $result['message'];
                
                // Log the service type used
                $service_name = "";
                foreach ($services as $s) {
                    if ($s['id'] == $service_type) {
                        $service_name = $s['name'];
                        break;
                    }
                }
                
                // Store service type in session for display
                $_SESSION['last_service'] = $service_name;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Page title
$page_title = 'Evaluate Registrar\'s Office - EVSU';
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
        /* Keep all your existing styles here */
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
        .evsu-header {
            background: var(--evsu-red);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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

        .header-actions a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .header-actions a:hover {
            background: rgba(255,255,255,0.1);
        }

        .header-actions a i {
            margin-right: 5px;
        }

        /* Main Layout */
        .main-container {
            display: flex;
            max-width: 1400px;
            margin: 30px auto;
            gap: 25px;
            padding: 0 20px;
        }

        /* Sidebar */
        .sidebar {
            width: 320px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 25px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .rating-guide {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .rating-guide h3 {
            color: var(--evsu-red);
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rating-guide h3 i {
            color: #ffd700;
        }

        .rating-scale-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 8px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .rating-scale-item .stars {
            color: #ffd700;
            font-size: 1.1rem;
            letter-spacing: 2px;
            min-width: 100px;
        }

        .rating-scale-item .desc {
            color: #666;
            font-size: 0.9rem;
        }

        .rating-scale-item .desc strong {
            color: var(--evsu-red);
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }

        .info-card h4 {
            color: var(--evsu-dark);
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
        }

        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
            color: var(--evsu-dark);
            font-size: 1.1rem;
        }

        .info-value i {
            color: var(--evsu-red);
            margin-right: 5px;
        }

        /* Main Content */
        .content {
            flex: 1;
        }

        .content-header {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 25px;
            margin-bottom: 25px;
        }

        .content-header h2 {
            color: var(--evsu-dark);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .content-header h2 i {
            color: var(--evsu-red);
            margin-right: 10px;
        }

        .office-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--evsu-red) 0%, #b71c1c 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 40px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .office-badge i {
            margin-right: 5px;
        }

        .question-count {
            background: var(--info-blue);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card h3 {
            color: var(--evsu-dark);
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card h3 i {
            color: var(--evsu-red);
        }

        /* Alerts */
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
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

        .alert i {
            font-size: 2rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--evsu-red);
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }

        select.form-control {
            background-color: white;
            cursor: pointer;
        }

        select.form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .required-star {
            color: #dc3545;
            margin-left: 3px;
        }

        /* Service Type Card */
        .service-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--evsu-red);
            margin-bottom: 25px;
        }

        .service-card h3 i {
            color: var(--evsu-red);
        }

        .service-description {
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }

        /* Progress Bar */
        .progress-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -30px;
            width: 60px;
            height: 2px;
            background: #ddd;
            transform: translateY(-50%);
        }

        .step.active .step-circle {
            background: var(--evsu-red);
            color: white;
            border-color: var(--evsu-red);
        }

        .step.completed .step-circle {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .step-label {
            font-size: 0.8rem;
            color: #666;
        }

        .step.active .step-label {
            color: var(--evsu-red);
            font-weight: 600;
        }

        .progress-bar {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--evsu-red), #ff6b6b);
            transition: width 0.3s;
        }

        /* Questions */
        .questions-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .question-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--evsu-red);
            transition: all 0.3s;
        }

        .question-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .question-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .question-number {
            width: 40px;
            height: 40px;
            background: var(--evsu-red);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .question-text {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--evsu-dark);
            line-height: 1.5;
        }

        /* Star Rating */
        .rating-container {
            margin-bottom: 15px;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffd700;
            transform: scale(1.1);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.8rem;
            color: #999;
        }

        .selected-rating {
            margin-top: 10px;
            padding: 8px 15px;
            background: #e3f2fd;
            border-radius: 8px;
            display: inline-block;
            font-size: 0.9rem;
            color: #1976d2;
        }

        .selected-rating i {
            margin-right: 5px;
        }

        /* Comment Section */
        .comment-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }

        .comment-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
        }

        .comment-section label i {
            color: var(--evsu-red);
            margin-right: 5px;
        }

        /* Suggestions Card */
        .suggestions-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .suggestions-card h3 {
            color: var(--evsu-dark);
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .suggestions-card h3 i {
            color: var(--evsu-red);
        }

        /* Submit Section */
        .submit-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: white;
        }

        .submit-section h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .submit-section p {
            opacity: 0.9;
            margin-bottom: 25px;
        }

        .btn {
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: white;
            color: var(--evsu-red);
        }

        .btn-primary:hover {
            background: var(--evsu-gold);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            backdrop-filter: blur(5px);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Thank You Page */
        .thank-you-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 50px;
            text-align: center;
        }

        .thank-you-card i {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }

        .thank-you-card h2 {
            color: var(--evsu-dark);
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .thank-you-card p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .service-badge {
            background: var(--evsu-red);
            color: white;
            padding: 10px 25px;
            border-radius: 40px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: white;
            opacity: 0.8;
            font-size: 0.9rem;
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
            
            .step:not(:last-child)::after {
                display: none;
            }
            
            .question-header {
                flex-direction: column;
                text-align: center;
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
                    <h1>EVSU - Ormoc Campus</h1>
                    <div class="subtitle">Registrar's Office Evaluation</div>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="rating-guide">
                <h3><i class="fas fa-star"></i> Rating Guide</h3>
                <div class="rating-scale-item">
                    <div class="stars">★★★★★</div>
                    <div class="desc"><strong>5 - Excellent</strong><br><span style="font-size: 0.8rem;">Exceptional service</span></div>
                </div>
                <div class="rating-scale-item">
                    <div class="stars">★★★★☆</div>
                    <div class="desc"><strong>4 - Good</strong><br><span style="font-size: 0.8rem;">Above average</span></div>
                </div>
                <div class="rating-scale-item">
                    <div class="stars">★★★☆☆</div>
                    <div class="desc"><strong>3 - Fair</strong><br><span style="font-size: 0.8rem;">Met expectations</span></div>
                </div>
                <div class="rating-scale-item">
                    <div class="stars">★★☆☆☆</div>
                    <div class="desc"><strong>2 - Poor</strong><br><span style="font-size: 0.8rem;">Below average</span></div>
                </div>
                <div class="rating-scale-item">
                    <div class="stars">★☆☆☆☆</div>
                    <div class="desc"><strong>1 - Very Poor</strong><br><span style="font-size: 0.8rem;">Needs improvement</span></div>
                </div>
            </div>

            <div class="info-card">
                <h4>Evaluation Information</h4>
                <div class="info-item">
                    <div class="info-label">Office</div>
                    <div class="info-value">
                        <i class="fas fa-building"></i> Registrar's Office
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Evaluator</div>
                    <div class="info-value">
                        <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($user['fullname'] ?? $user['username']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Student ID</div>
                    <div class="info-value">
                        <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date</div>
                    <div class="info-value">
                        <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Questions</div>
                    <div class="info-value">
                        <i class="fas fa-question-circle"></i> <?php echo count($questions); ?> items
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Content Header -->
            <div class="content-header">
                <h2>
                    <i class="fas fa-clipboard-check"></i>
                    Registrar's Office Evaluation Form
                </h2>
                <p>Please rate your experience with the Registrar's Office services</p>
                <div class="office-badge">
                    <i class="fas fa-building"></i> Registrar's Office
                    <span class="question-count"><?php echo count($questions); ?> questions</span>
                </div>
            </div>

            <?php if ($already_evaluated): ?>
                <!-- Already Evaluated Message -->
                <div class="alert alert-info">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>You've Already Evaluated Today</strong><br>
                        Thank you for your feedback! You have already submitted an evaluation for the Registrar's Office today. You can submit another evaluation tomorrow.
                    </div>
                </div>

                <div class="thank-you-card">
                    <i class="fas fa-clipboard-check"></i>
                    <h2>Thank You!</h2>
                    <p>Your feedback helps us improve our services. You've successfully evaluated the Registrar's Office today.</p>
                    <?php if (isset($_SESSION['last_service'])): ?>
                        <div class="service-badge">
                            <i class="fas fa-tag"></i> Service: <?php echo $_SESSION['last_service']; ?>
                        </div>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Return to Dashboard
                    </a>
                </div>

            <?php elseif (isset($success) && $success): ?>
                <!-- Success Message -->
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Evaluation Submitted Successfully!</strong><br>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>

                <div class="thank-you-card">
                    <i class="fas fa-check-circle"></i>
                    <h2>Thank You!</h2>
                    <p>Your feedback is valuable and will help us improve the Registrar's Office services.</p>
                    <?php if (isset($_SESSION['last_service'])): ?>
                        <div class="service-badge">
                            <i class="fas fa-tag"></i> Service: <?php echo $_SESSION['last_service']; ?>
                            <?php unset($_SESSION['last_service']); ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top: 20px;">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Return to Dashboard
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Evaluation Form -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Submission Error</strong><br>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Survey Inactive Warning -->
                <?php if (!$survey_active): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Survey is Currently Inactive</strong><br>
                            The evaluation survey is not active at this time. Please check back during the survey period.
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="evaluationForm">
                    
                    <!-- ========== SERVICE TYPE SELECTION (US-02) ========== -->
                    <div class="card service-card">
                        <h3>
                            <i class="fas fa-tag"></i> 
                            Select Service Type
                            <span style="color: #dc3545; font-size: 0.9rem; margin-left: 10px;">*Required</span>
                        </h3>
                        <p style="color: #666; margin-bottom: 15px;">Please select the specific Registrar service you availed:</p>
                        
                        <select name="service_type" id="serviceType" class="form-control" required <?php echo !$survey_active ? 'disabled' : ''; ?>>
                            <option value="">-- Select a Service --</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?php echo $s['id']; ?>">
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <?php if (!$survey_active): ?>
                            <p style="margin-top: 10px; color: #dc3545; font-size: 0.9rem;">
                                <i class="fas fa-exclamation-circle"></i> Service selection is disabled because the survey is inactive.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Progress Tracker -->
                    <div class="progress-container">
                        <div class="progress-steps">
                            <div class="step active" id="step1">
                                <div class="step-circle">1</div>
                                <div class="step-label">Select Service</div>
                            </div>
                            <div class="step" id="step2">
                                <div class="step-circle">2</div>
                                <div class="step-label">Rate Questions</div>
                            </div>
                            <div class="step" id="step3">
                                <div class="step-circle">3</div>
                                <div class="step-label">Comments</div>
                            </div>
                            <div class="step" id="step4">
                                <div class="step-circle">4</div>
                                <div class="step-label">Submit</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill" style="width: 25%"></div>
                        </div>
                    </div>

                    <!-- Questions Section - FIXED: Now it's a single container that shows/hides -->
                    <div id="questionsSection" class="questions-section" style="display: none;">
                        <div class="questions-container">
                            <h3 style="margin-bottom: 20px; color: var(--evsu-dark);">
                                <i class="fas fa-star" style="color: var(--evsu-red);"></i>
                                Service Quality Rating
                                <span style="font-size: 0.8rem; color: #999; margin-left: 10px;">All questions required</span>
                            </h3>

                            <?php foreach ($questions as $index => $q): 
                                $num = $index + 1;
                                $question_text = $q['question_text'] ?? $q;
                            ?>
                                <div class="question-item" id="question-<?php echo $num; ?>">
                                    <div class="question-header">
                                        <div class="question-number"><?php echo $num; ?></div>
                                        <div class="question-text">
                                            <?php echo htmlspecialchars($question_text); ?>
                                            <span class="required-star">*</span>
                                        </div>
                                    </div>

                                    <div class="rating-container">
                                        <div class="star-rating" data-question="<?php echo $num; ?>">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" 
                                                       id="rating_<?php echo $num; ?>_<?php echo $i; ?>" 
                                                       name="rating_<?php echo $num; ?>" 
                                                       value="<?php echo $i; ?>"
                                                       onchange="updateRating(<?php echo $num; ?>, <?php echo $i; ?>)">
                                                <label for="rating_<?php echo $num; ?>_<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-labels">
                                            <span>1 - Very Poor</span>
                                            <span>5 - Excellent</span>
                                        </div>
                                        <div class="selected-rating" id="selected_<?php echo $num; ?>" style="display: none;">
                                            <i class="fas fa-check-circle"></i>
                                            Selected: <span id="rating_value_<?php echo $num; ?>">0</span>/5
                                        </div>
                                    </div>

                                    <!-- Optional Comment per Question -->
                                    <div class="comment-section">
                                        <label for="answer_<?php echo $num; ?>">
                                            <i class="fas fa-comment"></i> Additional Comments (Optional)
                                        </label>
                                        <textarea class="form-control" 
                                                  id="answer_<?php echo $num; ?>" 
                                                  name="answer_<?php echo $num; ?>" 
                                                  rows="2"
                                                  placeholder="Any specific feedback for this question?"></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Overall Suggestions -->
                        <div class="suggestions-card">
                            <h3><i class="fas fa-lightbulb"></i> Overall Suggestions & Recommendations</h3>
                            <p style="color: #666; margin-bottom: 15px;">Your feedback helps us improve. Please share any additional comments about your experience with the Registrar's Office.</p>
                            <textarea name="report" class="form-control" rows="4" 
                                      placeholder="Share your suggestions, comments, or concerns here..."></textarea>
                        </div>

                        <!-- Submit Section -->
                        <div class="submit-section">
                            <h3><i class="fas fa-shield-alt"></i> Confidential & Anonymous</h3>
                            <p>Your responses are confidential and will be used only for service improvement.</p>
                            
                            <button type="submit" class="btn btn-primary" id="submitBtn" <?php echo !$survey_active ? 'disabled' : ''; ?>>
                                <i class="fas fa-paper-plane"></i> Submit Evaluation
                            </button>
                            
                            <p style="margin-top: 15px; font-size: 0.8rem;">
                                <i class="fas fa-info-circle"></i> 
                                By submitting, you confirm that all ratings are accurate.
                            </p>
                        </div>
                    </div>
                </form>

                <script>
                    // Track answered questions
                    const totalQuestions = <?php echo count($questions); ?>;
                    let answeredQuestions = new Array(totalQuestions).fill(false);

                    // Service type selection handler - FIXED: Now properly shows/hides without duplication
                    document.getElementById('serviceType')?.addEventListener('change', function() {
                        const questionsSection = document.getElementById('questionsSection');
                        
                        if (this.value) {
                            // Show questions section
                            questionsSection.style.display = 'block';
                            
                            // Update progress
                            document.getElementById('step1').classList.add('completed');
                            document.getElementById('step2').classList.add('active');
                            updateProgress();
                            
                            // Scroll to questions
                            setTimeout(() => {
                                questionsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }, 100);
                        } else {
                            questionsSection.style.display = 'none';
                        }
                    });

                    function updateRating(questionNum, rating) {
                        // Show selected rating
                        const selectedDiv = document.getElementById('selected_' + questionNum);
                        const ratingSpan = document.getElementById('rating_value_' + questionNum);
                        
                        if (selectedDiv && ratingSpan) {
                            ratingSpan.textContent = rating;
                            selectedDiv.style.display = 'inline-block';
                        }
                        
                        // Mark as answered
                        answeredQuestions[questionNum - 1] = true;
                        
                        // Update progress
                        updateProgress();
                        
                        // Remove error highlight if any
                        const questionDiv = document.getElementById('question-' + questionNum);
                        if (questionDiv) {
                            questionDiv.style.borderLeftColor = 'var(--evsu-red)';
                        }
                    }

                    function updateProgress() {
                        const answered = answeredQuestions.filter(v => v).length;
                        const serviceSelected = document.getElementById('serviceType')?.value ? 1 : 0;
                        
                        let percentage = 25; // Base for step 1
                        
                        if (serviceSelected) {
                            percentage = 25 + (answered / totalQuestions) * 50;
                        }
                        
                        // Update progress bar
                        const progressFill = document.getElementById('progressFill');
                        if (progressFill) {
                            progressFill.style.width = Math.min(percentage, 100) + '%';
                        }
                        
                        // Update steps
                        const step1 = document.getElementById('step1');
                        const step2 = document.getElementById('step2');
                        const step3 = document.getElementById('step3');
                        const step4 = document.getElementById('step4');
                        
                        if (serviceSelected) {
                            step1.classList.add('completed');
                            
                            if (answered === totalQuestions) {
                                step2.classList.add('completed');
                                step3.classList.add('active');
                                step4.classList.add('active');
                            } else if (answered > 0) {
                                step2.classList.add('active');
                                step3.classList.remove('active');
                                step4.classList.remove('active');
                            }
                        }
                    }

                    // Form validation
                    document.getElementById('evaluationForm')?.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Check service type selected
                        const serviceType = document.getElementById('serviceType');
                        if (!serviceType || !serviceType.value) {
                            alert('Please select a service type before submitting.');
                            serviceType?.focus();
                            return;
                        }
                        
                        // Check all questions answered
                        let allAnswered = true;
                        let firstUnanswered = null;
                        
                        for (let i = 1; i <= totalQuestions; i++) {
                            const rating = document.querySelector('input[name="rating_' + i + '"]:checked');
                            if (!rating) {
                                allAnswered = false;
                                if (!firstUnanswered) firstUnanswered = i;
                                
                                // Highlight unanswered question
                                const questionDiv = document.getElementById('question-' + i);
                                if (questionDiv) {
                                    questionDiv.style.borderLeftColor = '#dc3545';
                                    questionDiv.style.animation = 'pulse 0.5s';
                                }
                            }
                        }
                        
                        if (!allAnswered) {
                            alert('Please answer all ' + totalQuestions + ' questions before submitting.');
                            
                            // Scroll to first unanswered
                            if (firstUnanswered) {
                                document.getElementById('question-' + firstUnanswered).scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }
                            return;
                        }
                        
                        // Disable submit button
                        const submitBtn = document.getElementById('submitBtn');
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                        
                        // Submit form
                        this.submit();
                    });

                    // Add pulse animation
                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes pulse {
                            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
                            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
                            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
                        }
                    `;
                    document.head.appendChild(style);

                    // Ensure questions only show once on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        const questionsSection = document.getElementById('questionsSection');
                        if (questionsSection) {
                            questionsSection.style.display = 'none';
                        }
                    });
                </script>
            <?php endif; ?>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p><i class="fas fa-copyright"></i> <?php echo date('Y'); ?> EVSU Registrar Evaluation System | All Rights Reserved</p>
    </footer>
</body>
</html>