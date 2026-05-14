<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require admin access
requireRole('admin');

$functions = new Functions();
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$conn = getDB();

// ============================================
// CREATE SURVEY AVAILABILITY TABLE IF NOT EXISTS
// ============================================
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

// Check if table was created successfully and has the id column
$table_check = $conn->query("SHOW TABLES LIKE 'survey_availability'");
if ($table_check->num_rows == 0) {
    // Table doesn't exist, create it with proper structure
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
}

// Verify the id column exists
$column_check = $conn->query("SHOW COLUMNS FROM survey_availability LIKE 'id'");
if ($column_check->num_rows == 0) {
    // If id column doesn't exist, recreate the table
    $conn->query("DROP TABLE IF EXISTS survey_availability");
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
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 year'));
    
    // Clear old settings and insert new
    $conn->query("DELETE FROM survey_availability");
    
    $stmt = $conn->prepare("INSERT INTO survey_availability (is_active, start_date, end_date, updated_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $is_active, $start_date, $end_date, $user_id);
    
    if ($stmt->execute()) {
        $message = "Survey settings updated successfully";
        
        // Log action (US-14)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $action = 'update_survey_settings';
        $log_stmt->bind_param("isss", $user_id, $action, $ip, $agent);
        $log_stmt->execute();
    } else {
        $error = "Failed to update settings: " . $conn->error;
    }
}

// ============================================
// GET CURRENT SETTINGS - FIXED: Added error handling
// ============================================
$settings = ['is_active' => 1, 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+1 year'))];

try {
    $result = $conn->query("SELECT * FROM survey_availability ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
    } else {
        // Insert default settings if none exist
        $conn->query("INSERT INTO survey_availability (is_active, start_date, end_date) VALUES (1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))");
        $settings = [
            'is_active' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 year'))
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching survey settings: " . $e->getMessage());
    // Use default settings
    $settings = [
        'is_active' => 1,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+1 year'))
    ];
}

$page_title = 'Survey Settings - Registrar Evaluation';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --evsu-red: #8B0000; --evsu-gold: #FFD700; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .header {
            background: var(--evsu-red);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card h2 {
            color: var(--evsu-red);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--evsu-red);
            outline: none;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--evsu-red);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .status-text {
            font-size: 1.2rem;
            font-weight: bold;
            margin-left: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: var(--evsu-red);
            color: white;
        }
        
        .btn-primary:hover {
            background: #a52a2a;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            color: white;
        }
        
        .footer a {
            color: var(--evsu-gold);
            text-decoration: none;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-building"></i> Registrar Evaluation System</h1>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($user['fullname'] ?? $user['username']); ?></span>
            <a href="../logout.php" style="color: var(--evsu-gold); margin-left: 20px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="card">
            <h2><i class="fas fa-toggle-on"></i> Survey Availability Settings</h2>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Survey Status</label>
                    <div style="display: flex; align-items: center;">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" <?php echo $settings['is_active'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="status-text" style="color: <?php echo $settings['is_active'] ? '#28a745' : '#dc3545'; ?>">
                            <?php echo $settings['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                        </span>
                    </div>
                    <small style="color: #666;">When inactive, students cannot access the evaluation form</small>
                </div>
                
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $settings['start_date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $settings['end_date']; ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Current Status:</strong> Survey is 
                <span style="color: <?php echo $settings['is_active'] ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                    <?php echo $settings['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                </span><br>
                <strong>Period:</strong> <?php echo date('F j, Y', strtotime($settings['start_date'])); ?> to 
                <?php echo date('F j, Y', strtotime($settings['end_date'])); ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> EVSU Registrar Evaluation System</p>
        </div>
    </div>

    <script>
        document.querySelector('input[name="is_active"]').addEventListener('change', function() {
            const statusText = document.querySelector('.status-text');
            if (this.checked) {
                statusText.textContent = 'ACTIVE';
                statusText.style.color = '#28a745';
            } else {
                statusText.textContent = 'INACTIVE';
                statusText.style.color = '#dc3545';
            }
        });
    </script>
</body>
</html>