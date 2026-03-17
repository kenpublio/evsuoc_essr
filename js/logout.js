// js/logout.js - Enhanced logout functionality
document.addEventListener('DOMContentLoaded', function() {
    // Attach logout confirmation to all logout links
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
                return false;
            }
            
            // Show loading indicator
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
            this.style.pointerEvents = 'none';
            
            // Allow navigation to proceed
            return true;
        });
    });
    
    // Auto-logout after inactivity (30 minutes)
    let inactivityTime = function() {
        let time;
        
        // Reset timer on user activity
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
        document.onclick = resetTimer;
        document.onscroll = resetTimer;
        document.onmousedown = resetTimer;
        document.onmouseup = resetTimer;
        
        function logout() {
            // Check if user is on admin page
            if (window.location.pathname.includes('admin/')) {
                showLogoutNotification();
            }
        }
        
        function resetTimer() {
            clearTimeout(time);
            time = setTimeout(logout, 30 * 60 * 1000); // 30 minutes
        }
        
        resetTimer();
    };
    
    // Start inactivity timer
    inactivityTime();
    
    // Show logout notification
    function showLogoutNotification() {
        // Check if user is still active
        fetch('../includes/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.active) {
                    // Create notification
                    const notification = document.createElement('div');
                    notification.className = 'logout-notification';
                    notification.innerHTML = `
                        <div class="notification-content">
                            <i class="fas fa-clock"></i>
                            <h3>Session Timeout Warning</h3>
                            <p>Your session will expire in 5 minutes due to inactivity.</p>
                            <div class="notification-buttons">
                                <button class="btn btn-primary" id="extendSession">
                                    <i class="fas fa-redo"></i> Stay Logged In
                                </button>
                                <button class="btn btn-secondary" id="logoutNow">
                                    <i class="fas fa-sign-out-alt"></i> Logout Now
                                </button>
                            </div>
                        </div>
                    `;
                    
                    // Add styles
                    notification.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.8);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 9999;
                    `;
                    
                    const content = notification.querySelector('.notification-content');
                    content.style.cssText = `
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                        max-width: 400px;
                        text-align: center;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    `;
                    
                    document.body.appendChild(notification);
                    
                    // Add event listeners
                    document.getElementById('extendSession').addEventListener('click', function() {
                        fetch('../includes/extend_session.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    notification.remove();
                                    resetTimer();
                                }
                            });
                    });
                    
                    document.getElementById('logoutNow').addEventListener('click', function() {
                        window.location.href = 'logout.php';
                    });
                }
            });
    }
});

// Global logout function (also add this to logout.js or keep in script.js)
function logoutUser(redirect_url = 'index.php') {
    if (confirm('Are you sure you want to logout?')) {
        // Show loading
        document.body.innerHTML += `
            <div id="logoutOverlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
            ">
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    text-align: center;
                ">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--evsu-red); margin-bottom: 20px;"></i>
                    <h3>Logging out...</h3>
                    <p>Please wait while we secure your session.</p>
                </div>
            </div>
        `;
        
        // Perform logout
        setTimeout(() => {
            window.location.href = redirect_url;
        }, 1000);
        
        return true;
    }
    return false;
}