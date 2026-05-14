<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require admin access
requireRole('admin');

$functions = new Functions();
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$backup_dir = dirname(__DIR__) . '/backups/';
$message = '';
$error = '';

// Handle restore request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_file'])) {
    $backup_file = basename($_POST['backup_file']);
    $full_path = $backup_dir . $backup_file;
    
    if (file_exists($full_path)) {
        // Confirm restore (this is dangerous!)
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            // Restore database
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_NAME),
                escapeshellarg($full_path)
            );
            
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $message = "Database restored successfully from: $backup_file";
                
                // Log the restore
                $conn = getDB();
                $ip = $_SERVER['REMOTE_ADDR'];
                $agent = $_SERVER['HTTP_USER_AGENT'];
                $conn->query("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES ($user_id, 'restore_backup', '$ip', '$agent')");
            } else {
                $error = "Restore failed: " . implode("\n", $output);
            }
        } else {
            $error = "Please confirm restore operation";
        }
    } else {
        $error = "Backup file not found";
    }
}

// Get list of backups
$backups = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . 'backup_*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'file' => basename($file),
            'size' => round(filesize($file) / 1024 / 1024, 2) . ' MB',
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

$page_title = 'Restore Backup - Registrar Evaluation';
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
        :root { --evsu-red: #8B0000; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        
        .header {
            background: var(--evsu-red);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-primary {
            background: var(--evsu-red);
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-database"></i> Restore Backup</h1>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($user['fullname'] ?? $user['username']); ?></span>
            <a href="../logout.php" style="color: var(--evsu-gold); margin-left: 20px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2 style="margin-bottom: 20px; color: var(--evsu-red);">
                <i class="fas fa-history"></i> Available Backups
            </h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>WARNING:</strong> Restoring a backup will OVERWRITE your current database. 
                All data added after the backup date will be lost. This action cannot be undone!
            </div>
            
            <?php if (empty($backups)): ?>
                <p>No backup files found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Backup File</th>
                            <th>Date</th>
                            <th>Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo $backup['file']; ?></td>
                                <td><?php echo $backup['date']; ?></td>
                                <td><?php echo $backup['size']; ?></td>
                                <td>
                                    <button class="btn btn-danger" onclick="showRestoreModal('<?php echo $backup['file']; ?>')">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="download_backup.php" class="btn btn-primary" style="float: right;">
                    <i class="fas fa-download"></i> Download Latest
                </a>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc3545; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px;">Confirm Restore</h3>
            <p id="modalMessage" style="margin-bottom: 20px;">Are you sure you want to restore this backup?</p>
            
            <form method="POST" id="restoreForm">
                <input type="hidden" name="backup_file" id="backupFile" value="">
                <input type="hidden" name="confirm" value="yes">
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="hideModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check"></i> Yes, Restore
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> EVSU Registrar Evaluation System | Backup Location: <?php echo $backup_dir; ?></p>
    </div>

    <script>
        function showRestoreModal(filename) {
            document.getElementById('backupFile').value = filename;
            document.getElementById('modalMessage').innerHTML = 'Are you sure you want to restore: <strong>' + filename + '</strong>?';
            document.getElementById('restoreModal').style.display = 'block';
        }
        
        function hideModal() {
            document.getElementById('restoreModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('restoreModal');
            if (event.target == modal) {
                hideModal();
            }
        }
    </script>
</body>
</html>