<?php
require_once 'config.php';

$backup_dir = __DIR__ . '/../backups/';

// Create backup directory if not exists
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Backup filename with timestamp
$filename = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$error_log = $backup_dir . 'backup_error.log';

// Database configuration
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$db = DB_NAME;

// Command to backup database
$command = "mysqldump --host=$host --user=$user --password=$pass $db > $filename 2>> $error_log";

// Execute backup
exec($command, $output, $return_var);

// Log the result
$log_file = $backup_dir . 'backup_log.txt';
$log_entry = date('Y-m-d H:i:s') . " - ";

if ($return_var === 0) {
    $log_entry .= "SUCCESS: Backup created: " . basename($filename);
    
    // Keep only last 30 backups
    $files = glob($backup_dir . 'backup_*.sql');
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    while (count($files) > 30) {
        $file = array_shift($files);
        unlink($file);
        $log_entry .= " - Deleted old backup: " . basename($file);
    }
} else {
    $log_entry .= "FAILED: Return code: $return_var";
    
    // Send notification to admin (US-15)
    $admin_email = "admin@evsu.edu.ph";
    $subject = "Database Backup Failed - Registrar System";
    $message = "Database backup failed on " . date('Y-m-d H:i:s') . "\n\nError code: $return_var\n\nCheck $error_log for details.";
    mail($admin_email, $subject, $message);
}

// Write log
file_put_contents($log_file, $log_entry . "\n", FILE_APPEND);

// Also log to database (US-14)
$conn = getDB();
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
$conn->query("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (NULL, 'daily_backup_" . ($return_var === 0 ? 'success' : 'failed') . "', '$ip', '$agent')");

echo $log_entry . "\n";
?>