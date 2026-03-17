<?php
// config/email_config.php

// Development settings
define('DEVELOPMENT_MODE', true);

// Email settings for development
define('DEV_EMAIL_PATH', __DIR__ . '/../emails/');
define('DEV_EMAIL_VIEWER', 'email_viewer.php');

// SMTP settings for production
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'noreply@evsu.edu.ph');
define('SMTP_FROM_NAME', 'EVSU-OCC Evaluation System');