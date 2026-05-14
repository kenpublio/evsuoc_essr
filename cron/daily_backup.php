<?php
define('ROOT_PATH', dirname(__DIR__));

// Include configuration
require_once ROOT_PATH . '../includes/config.php';

// ============================================
// BACKUP CONFIGURATION
// ============================================
$backup_dir = ROOT_PATH . '/backups/';
$max_backups = 30; // Keep last 30 days of backups
$date_format = 'Y-m-d_H-i-s';

// Create backup directory if not exists
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    chmod($backup_dir, 0755);
}

// ============================================
// GENERATE BACKUP FILENAME
// ============================================
$timestamp = date($date_format);
$filename = $backup_dir . 'backup_' . $timestamp . '.sql';
$log_file = $backup_dir . 'backup_log.txt';

// ============================================
// CREATE BACKUP
// ============================================
$command = sprintf(
    'mysqldump --host=%s --user=%s --password=%s %s > %s 2>> %s',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($filename),
    escapeshellarg($log_file)
);

exec($command, $output, $return_var);

// ============================================
// LOG THE RESULT
// ============================================
$log_entry = date('Y-m-d H:i:s') . ' - ';

if ($return_var === 0 && file_exists($filename) && filesize($filename) > 0) {
    $filesize = round(filesize($filename) / 1024 / 1024, 2); // Size in MB
    $log_entry .= "SUCCESS: Backup created: " . basename($filename) . " ($filesize MB)";
    
    // Delete old backups (keep only last $max_backups)
    $backup_files = glob($backup_dir . 'backup_*.sql');
    usort($backup_files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    $deleted_count = 0;
    while (count($backup_files) > $max_backups) {
        $file = array_shift($backup_files);
        if (unlink($file)) {
            $deleted_count++;
        }
    }
    
    if ($deleted_count > 0) {
        $log_entry .= " | Deleted $deleted_count old backup(s)";
    }
    
    // ============================================
    // SEND SUCCESS NOTIFICATION (Optional)
    // ============================================
    $subject = "EVSU Backup Success - " . date('Y-m-d');
    $message = "Database backup completed successfully.\n\n";
    $message .= "Backup File: " . basename($filename) . "\n";
    $message .= "File Size: $filesize MB\n";
    $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    // Uncomment to enable email notifications
    // mail('admin@evsu.edu.ph', $subject, $message);
    
} else {
    $log_entry .= "FAILED: Return code: $return_var - " . implode(' ', $output);
    
    // ============================================
    // SEND FAILURE NOTIFICATION
    // ============================================
    $subject = "EVSU Backup FAILED - " . date('Y-m-d');
    $message = "Database backup FAILED!\n\n";
    $message .= "Error Code: $return_var\n";
    $message .= "Output: " . implode("\n", $output) . "\n";
    $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    // Send email notification
    mail('admin@evsu.edu.ph', $subject, $message);
    
    // Also log to system
    error_log("EVSU Backup Failed: $return_var - " . implode(' ', $output));
}

// ============================================
// WRITE TO LOG FILE
// ============================================
file_put_contents($log_file, $log_entry . "\n", FILE_APPEND);

// ============================================
// LOG TO DATABASE (US-14)
// ============================================
try {
    $conn = getDB();
    $action = $return_var === 0 ? 'daily_backup_success' : 'daily_backup_failed';
    $ip = '127.0.0.1';
    $agent = 'Cron Job';
    
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (NULL, ?, ?, ?)");
    $stmt->bind_param("sss", $action, $ip, $agent);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Failed to log backup to database: " . $e->getMessage());
}

// ============================================
// CREATE BACKUP INFO FILE
// ============================================
if ($return_var === 0 && file_exists($filename)) {
    $info_file = $backup_dir . 'backup_info.json';
    $backups = [];
    
    if (file_exists($info_file)) {
        $backups = json_decode(file_get_contents($info_file), true) ?: [];
    }
    
    $backups[] = [
        'file' => basename($filename),
        'date' => date('Y-m-d H:i:s'),
        'size' => filesize($filename),
        'status' => 'success'
    ];
    
    // Keep only last 30 entries in info file
    $backups = array_slice($backups, -30);
    
    file_put_contents($info_file, json_encode($backups, JSON_PRETTY_PRINT));
}

echo $log_entry . "\n";
exit($return_var);
?>