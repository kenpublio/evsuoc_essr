<?php
/**
 * Session management helper
 */
function checkSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}
?>