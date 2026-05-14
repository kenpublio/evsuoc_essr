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
// INITIALIZE SETTINGS TABLE
// ============================================

// Create settings table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS system_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        description TEXT,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Insert default settings if table is empty
$check = $conn->query("SELECT COUNT(*) as count FROM system_settings")->fetch_assoc();
if ($check['count'] == 0) {
    $default_settings = [
        ['system_name', 'EVSU Registrar Evaluation System', 'text', 'System name displayed in header'],
        ['system_version', '2.0.0', 'text', 'Current system version'],
        ['items_per_page', '20', 'number', 'Number of items to display per page'],
        ['allow_registration', '1', 'boolean', 'Allow new student registration'],
        ['require_email_verification', '0', 'boolean', 'Require email verification for new accounts'],
        ['session_timeout', '3600', 'number', 'Session timeout in seconds'],
        ['max_login_attempts', '5', 'number', 'Maximum failed login attempts before lockout'],
        ['lockout_time', '900', 'number', 'Account lockout time in seconds'],
        ['backup_retention_days', '30', 'number', 'Number of days to keep backups'],
        ['maintenance_mode', '0', 'boolean', 'Put system in maintenance mode'],
        ['maintenance_message', 'System under maintenance. Please check back later.', 'text', 'Message to display during maintenance'],
        ['contact_email', 'admin@evsu.edu.ph', 'text', 'System administrator contact email'],
        ['school_year', date('Y') . '-' . (date('Y')+1), 'text', 'Current school year'],
        ['semester', '2nd Semester', 'text', 'Current semester'],
        ['evaluation_reminder_days', '7', 'number', 'Days before evaluation deadline to send reminders']
    ];
    
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($default_settings as $setting) {
        $stmt->bind_param("ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
        $stmt->execute();
    }
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Update each setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remove 'setting_' prefix
                $setting_value = $conn->real_escape_string($value);
                
                $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->bind_param("sis", $setting_value, $user_id, $setting_key);
                $stmt->execute();
            }
        }
        
        $message = "System settings updated successfully";
        
        // Log action
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $action = 'update_system_settings';
        $log_stmt->bind_param("isss", $user_id, $action, $ip, $agent);
        $log_stmt->execute();
    }
    
    if (isset($_POST['clear_cache'])) {
        // Clear system cache
        $cache_dir = dirname(__DIR__) . '/cache/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $message = "System cache cleared successfully";
        }
    }
    
    if (isset($_POST['reset_defaults'])) {
        // Reset to default settings
        $conn->query("DELETE FROM system_settings");
        
        $default_settings = [
            ['system_name', 'EVSU Registrar Evaluation System', 'text', 'System name displayed in header'],
            ['system_version', '2.0.0', 'text', 'Current system version'],
            ['items_per_page', '20', 'number', 'Number of items to display per page'],
            ['allow_registration', '1', 'boolean', 'Allow new student registration'],
            ['require_email_verification', '0', 'boolean', 'Require email verification for new accounts'],
            ['session_timeout', '3600', 'number', 'Session timeout in seconds'],
            ['max_login_attempts', '5', 'number', 'Maximum failed login attempts before lockout'],
            ['lockout_time', '900', 'number', 'Account lockout time in seconds'],
            ['backup_retention_days', '30', 'number', 'Number of days to keep backups'],
            ['maintenance_mode', '0', 'boolean', 'Put system in maintenance mode'],
            ['maintenance_message', 'System under maintenance. Please check back later.', 'text', 'Message to display during maintenance'],
            ['contact_email', 'admin@evsu.edu.ph', 'text', 'System administrator contact email'],
            ['school_year', date('Y') . '-' . (date('Y')+1), 'text', 'Current school year'],
            ['semester', '2nd Semester', 'text', 'Current semester'],
            ['evaluation_reminder_days', '7', 'number', 'Days before evaluation deadline to send reminders']
        ];
        
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
        foreach ($default_settings as $setting) {
            $stmt->bind_param("ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
            $stmt->execute();
        }
        
        $message = "System settings reset to defaults";
    }
}

// ============================================
// GET CURRENT SETTINGS
// ============================================

$settings = [];
$result = $conn->query("SELECT * FROM system_settings ORDER BY setting_key");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

// Get system info
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $conn->server_info,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'current_time' => date('Y-m-d H:i:s'),
    'memory_limit' => ini_get('memory_limit'),
    'max_upload_size' => ini_get('upload_max_filesize'),
    'max_post_size' => ini_get('post_max_size')
];

$page_title = 'System Settings - Registrar Evaluation';
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --evsu-red: #8B0000; --evsu-gold: #FFD700; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; }
        
        .header {
            background: var(--evsu-red);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--evsu-red);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            position: relative;
        }
        
        .tab.active {
            color: var(--evsu-red);
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--evsu-red);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .card h2 {
            color: var(--evsu-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .card h2 i {
            color: var(--evsu-red);
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .setting-item {
            margin-bottom: 20px;
        }
        
        .setting-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .setting-item label i {
            color: var(--evsu-red);
            width: 20px;
            margin-right: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--evsu-red);
            outline: none;
        }
        
        .setting-description {
            font-size: 0.8rem;
            color: #999;
            margin-top: 5px;
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--evsu-red);
            color: white;
        }
        
        .btn-primary:hover {
            background: #a52a2a;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #666;
        }
        
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #dee2e6;
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
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('general')">General</button>
            <button class="tab" onclick="showTab('security')">Security</button>
            <button class="tab" onclick="showTab('system')">System Info</button>
            <button class="tab" onclick="showTab('maintenance')">Maintenance</button>
        </div>
        
        <!-- General Settings Tab -->
        <div id="tab-general" class="tab-content active">
            <div class="card">
                <h2><i class="fas fa-cog"></i> General Settings</h2>
                <form method="POST">
                    <div class="settings-grid">
                        <div class="setting-item">
                            <label><i class="fas fa-font"></i> System Name</label>
                            <input type="text" name="setting_system_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['system_name']['setting_value'] ?? 'EVSU Registrar Evaluation System'); ?>">
                            <div class="setting-description"><?php echo $settings['system_name']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-tag"></i> System Version</label>
                            <input type="text" name="setting_system_version" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['system_version']['setting_value'] ?? '2.0.0'); ?>">
                            <div class="setting-description"><?php echo $settings['system_version']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-list"></i> Items Per Page</label>
                            <input type="number" name="setting_items_per_page" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['items_per_page']['setting_value'] ?? '20'); ?>">
                            <div class="setting-description"><?php echo $settings['items_per_page']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-calendar"></i> School Year</label>
                            <input type="text" name="setting_school_year" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['school_year']['setting_value'] ?? date('Y') . '-' . (date('Y')+1)); ?>">
                            <div class="setting-description"><?php echo $settings['school_year']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-calendar-alt"></i> Semester</label>
                            <select name="setting_semester" class="form-control">
                                <option value="1st Semester" <?php echo ($settings['semester']['setting_value'] ?? '') == '1st Semester' ? 'selected' : ''; ?>>1st Semester</option>
                                <option value="2nd Semester" <?php echo ($settings['semester']['setting_value'] ?? '') == '2nd Semester' ? 'selected' : ''; ?>>2nd Semester</option>
                                <option value="Summer" <?php echo ($settings['semester']['setting_value'] ?? '') == 'Summer' ? 'selected' : ''; ?>>Summer</option>
                            </select>
                            <div class="setting-description"><?php echo $settings['semester']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-envelope"></i> Contact Email</label>
                            <input type="email" name="setting_contact_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['contact_email']['setting_value'] ?? 'admin@evsu.edu.ph'); ?>">
                            <div class="setting-description"><?php echo $settings['contact_email']['description'] ?? ''; ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="setting-item">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <span><i class="fas fa-user-plus"></i> Allow Student Registration</span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="setting_allow_registration" value="1" 
                                    <?php echo ($settings['allow_registration']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                        <div class="setting-description"><?php echo $settings['allow_registration']['description'] ?? ''; ?></div>
                    </div>
                    
                    <div class="setting-item">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <span><i class="fas fa-envelope"></i> Require Email Verification</span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="setting_require_email_verification" value="1" 
                                    <?php echo ($settings['require_email_verification']['setting_value'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                        <div class="setting-description"><?php echo $settings['require_email_verification']['description'] ?? ''; ?></div>
                    </div>
                    
                    <button type="submit" name="save_settings" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-save"></i> Save General Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Security Settings Tab -->
        <div id="tab-security" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                <form method="POST">
                    <div class="settings-grid">
                        <div class="setting-item">
                            <label><i class="fas fa-clock"></i> Session Timeout (seconds)</label>
                            <input type="number" name="setting_session_timeout" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['session_timeout']['setting_value'] ?? '3600'); ?>">
                            <div class="setting-description"><?php echo $settings['session_timeout']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-exclamation-triangle"></i> Max Login Attempts</label>
                            <input type="number" name="setting_max_login_attempts" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['max_login_attempts']['setting_value'] ?? '5'); ?>">
                            <div class="setting-description"><?php echo $settings['max_login_attempts']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-lock"></i> Lockout Time (seconds)</label>
                            <input type="number" name="setting_lockout_time" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['lockout_time']['setting_value'] ?? '900'); ?>">
                            <div class="setting-description"><?php echo $settings['lockout_time']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-database"></i> Backup Retention (days)</label>
                            <input type="number" name="setting_backup_retention_days" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['backup_retention_days']['setting_value'] ?? '30'); ?>">
                            <div class="setting-description"><?php echo $settings['backup_retention_days']['description'] ?? ''; ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <label><i class="fas fa-bell"></i> Evaluation Reminder (days)</label>
                            <input type="number" name="setting_evaluation_reminder_days" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['evaluation_reminder_days']['setting_value'] ?? '7'); ?>">
                            <div class="setting-description"><?php echo $settings['evaluation_reminder_days']['description'] ?? ''; ?></div>
                        </div>
                    </div>
                    
                    <button type="submit" name="save_settings" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-save"></i> Save Security Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- System Info Tab -->
        <div id="tab-system" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> System Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?php echo $system_info['php_version']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">MySQL Version</div>
                        <div class="info-value"><?php echo $system_info['mysql_version']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Server Software</div>
                        <div class="info-value"><?php echo $system_info['server_software']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Document Root</div>
                        <div class="info-value"><?php echo $system_info['document_root']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Current Time</div>
                        <div class="info-value"><?php echo $system_info['current_time']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Memory Limit</div>
                        <div class="info-value"><?php echo $system_info['memory_limit']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Max Upload Size</div>
                        <div class="info-value"><?php echo $system_info['max_upload_size']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Max Post Size</div>
                        <div class="info-value"><?php echo $system_info['max_post_size']; ?></div>
                    </div>
                </div>
                
                <hr>
                
                <h3 style="margin: 20px 0;">Database Tables</h3>
                <?php
                $tables = $conn->query("SHOW TABLES");
                $table_count = $tables->num_rows;
                ?>
                <p>Total Tables: <strong><?php echo $table_count; ?></strong></p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    <?php while ($table = $tables->fetch_array()): ?>
                        <span style="background: #f0f0f0; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem;">
                            <?php echo $table[0]; ?>
                        </span>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        
        <!-- Maintenance Tab -->
        <div id="tab-maintenance" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-tools"></i> Maintenance</h2>
                
                <form method="POST">
                    <div class="setting-item">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <span><i class="fas fa-exclamation-triangle"></i> Maintenance Mode</span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="setting_maintenance_mode" value="1" 
                                    <?php echo ($settings['maintenance_mode']['setting_value'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                        <div class="setting-description"><?php echo $settings['maintenance_mode']['description'] ?? ''; ?></div>
                    </div>
                    
                    <div class="setting-item">
                        <label><i class="fas fa-comment"></i> Maintenance Message</label>
                        <textarea name="setting_maintenance_message" class="form-control" rows="3"><?php echo htmlspecialchars($settings['maintenance_message']['setting_value'] ?? 'System under maintenance. Please check back later.'); ?></textarea>
                        <div class="setting-description"><?php echo $settings['maintenance_message']['description'] ?? ''; ?></div>
                    </div>
                    
                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Maintenance Settings
                    </button>
                </form>
                
                <hr>
                
                <h3 style="margin: 20px 0;">System Actions</h3>
                
                <div class="action-buttons">
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear the system cache?');">
                        <button type="submit" name="clear_cache" class="btn btn-warning">
                            <i class="fas fa-broom"></i> Clear Cache
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.');">
                        <button type="submit" name="reset_defaults" class="btn btn-danger">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </form>
                    
                    <a href="test_survey_table.php" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-vial"></i> Run System Tests
                    </a>
                    
                    <a href="../cron/daily_backup.php" class="btn btn-secondary" onclick="return confirm('Run manual backup now?');">
                        <i class="fas fa-database"></i> Manual Backup
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> EVSU Registrar Evaluation System | Version <?php echo $settings['system_version']['setting_value'] ?? '2.0.0'; ?></p>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Handle checkbox toggles for boolean settings
        document.querySelectorAll('input[type="checkbox"][name^="setting_"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Ensure checkbox value is submitted correctly
                if (!this.checked) {
                    // Create hidden input with value 0
                    let hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = this.name;
                    hidden.value = '0';
                    this.parentNode.appendChild(hidden);
                }
            });
        });
    </script>
</body>
</html>