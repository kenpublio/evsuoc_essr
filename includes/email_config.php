<?php
// includes/email_config.php

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Use App Password, not regular password
define('SMTP_FROM_EMAIL', 'noreply@evsu.edu.ph');
define('SMTP_FROM_NAME', 'EVSU-OCC Evaluation System');

// Use App Password from Google:
// 1. Go to https://myaccount.google.com/security
// 2. Enable 2-Step Verification
// 3. Go to App Passwords
// 4. Generate app password for "Mail"
// 5. Use that 16-character password here