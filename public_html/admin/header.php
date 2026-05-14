<?php
// admin/header.php - Common header for admin pages
if (!isset($_SESSION)) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$functions = new Functions();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$user = $auth->getCurrentUser();
$user_details = $functions->getUserById($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EVSU Evaluation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/script.js"></script>
    <script src="../js/logout.js"></script>
</head>
<body>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="logout-modal">
        <div class="logout-content">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2 class="logout-title">Confirm Logout</h2>
            <p class="logout-message">
                Are you sure you want to logout from the admin panel?<br>
                You will need to login again to access admin features.
            </p>
            <div class="logout-progress">
                <div class="logout-progress-bar" id="logoutProgressBar"></div>
            </div>
            <div class="logout-buttons">
                <button class="logout-btn-cancel" onclick="hideLogoutModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="logout-btn-confirm" onclick="performLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Session Timeout Modal -->
    <div id="sessionTimeoutModal" class="session-timeout-modal">
        <div class="session-timeout-content">
            <div class="session-timeout-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h2 class="session-timeout-title">Session Timeout Warning</h2>
            <p class="session-timeout-message">
                Your admin session will expire in <span id="timeoutSeconds">300</span> seconds due to inactivity.<br>
                Please choose an action to continue.
            </p>
            <div class="session-timeout-countdown" id="timeoutCountdown">05:00</div>
            <div class="session-timeout-buttons">
                <button class="btn btn-primary" onclick="extendSession()">
                    <i class="fas fa-redo"></i> Stay Logged In
                </button>
                <button class="btn btn-secondary" onclick="logoutNow()">
                    <i class="fas fa-sign-out-alt"></i> Logout Now
                </button>
            </div>
        </div>
    </div>

    <!-- Admin Header -->
    <div class="evsu-header">
        <div class="header-container">
            <div class="logo-container">
                <img src="../images/evsu-logo.png" alt="EVSU Logo">
                <div class="brand-text">
                    <div class="university-name">EASTERN VISAYAS STATE UNIVERSITY</div>
                    <div class="portal-name">EVSU EVALUATION SYSTEM</div>
                    <div class="subtitle">Administrator Panel</div>
                </div>
            </div>
            
            <div class="admin-user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <div class="user-name">
                    <strong><?php 
$displayName = 'Administrator';
if (isset($user_details['fullname']) && !empty($user_details['fullname'])) {
    $displayName = htmlspecialchars($user_details['fullname']);
} elseif (isset($user['username']) && !empty($user['username'])) {
    $displayName = htmlspecialchars($user['username']);
}
echo $displayName;
?></strong>
                        <span class="user-role">(Admin)</span>
                    </div>
                    <div class="user-actions">
                        <a href="profile.php" class="user-action-link">
                            <i class="fas fa-user-cog"></i> Profile
                        </a>
                        <span class="separator">|</span>
                        <a href="settings.php" class="user-action-link">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <span class="separator">|</span>
                        <a href="javascript:void(0)" class="user-action-link logout-link" onclick="showLogoutModal()">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logout modal functions
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const progressBar = document.getElementById('logoutProgressBar');
            
            modal.classList.add('active');
            
            // Start progress bar animation
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.width = '100%';
            }, 10);
            
            // Auto logout after 30 seconds
            setTimeout(() => {
                if (modal.classList.contains('active')) {
                    performLogout();
                }
            }, 30000);
        }
        
        function hideLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }
        
        function performLogout() {
            // Show loading
            document.body.innerHTML += `
                <div id="logoutLoading" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 999999;
                ">
                    <div style="text-align: center; color: white;">
                        <i class="fas fa-spinner fa-spin fa-4x" style="margin-bottom: 20px;"></i>
                        <h2>Logging out...</h2>
                        <p>Securing your session and redirecting to login page.</p>
                    </div>
                </div>
            `;
            
            // Redirect to logout page
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1500);
        }
        
        // Session timeout functions
        let timeoutTimer;
        let secondsLeft = 300; // 5 minutes
        
        function startTimeoutTimer() {
            clearInterval(timeoutTimer);
            secondsLeft = 300;
            updateTimeoutDisplay();
            
            timeoutTimer = setInterval(() => {
                secondsLeft--;
                updateTimeoutDisplay();
                
                if (secondsLeft <= 0) {
                    clearInterval(timeoutTimer);
                    logoutNow();
                }
                
                // Show warning when 2 minutes left
                if (secondsLeft === 120) {
                    showSessionTimeoutModal();
                }
            }, 1000);
        }
        
        function updateTimeoutDisplay() {
            const minutes = Math.floor(secondsLeft / 60);
            const seconds = secondsLeft % 60;
            document.getElementById('timeoutCountdown').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('timeoutSeconds').textContent = secondsLeft;
        }
        
        function showSessionTimeoutModal() {
            document.getElementById('sessionTimeoutModal').style.display = 'flex';
            startTimeoutTimer();
        }
        
        function extendSession() {
            fetch('../includes/extend_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('sessionTimeoutModal').style.display = 'none';
                        secondsLeft = 300;
                        startTimeoutTimer();
                    }
                });
        }
        
        function logoutNow() {
            window.location.href = 'logout.php';
        }
        
        // Reset timer on user activity
        document.addEventListener('mousemove', startTimeoutTimer);
        document.addEventListener('keypress', startTimeoutTimer);
        document.addEventListener('click', startTimeoutTimer);
        
        // Start timer on page load
        document.addEventListener('DOMContentLoaded', startTimeoutTimer);
    </script>
</body>
</html>