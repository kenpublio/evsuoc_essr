<?php
require_once 'config.php';

class Email {
    private $conn;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        // Email configuration (update these with your SMTP settings)
        $this->smtp_host = 'smtp.gmail.com'; // or your SMTP server
        $this->smtp_port = 587;
        $this->smtp_username = 'noreply@evsu.edu.ph'; // your email
        $this->smtp_password = 'your_password'; // your email password or app password
        $this->from_email = 'noreply@evsu.edu.ph';
        $this->from_name = 'EVSU Evaluation System';
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($user_id, $email, $username, $token) {
        $verification_link = BASE_URL . "verify_email.php?token=" . $token;
        $expiry_time = "24 hours";
        
        $subject = "Verify Your Email - EVSU Evaluation System";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #DD0303; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                .button { display: inline-block; padding: 12px 30px; background: #DD0303; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                .logo { text-align: center; margin-bottom: 20px; }
                .logo img { max-width: 150px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>EVSU Evaluation System</h2>
                </div>
                <div class='content'>
                    <div class='logo'>
                        <h3 style='color: #DD0303;'>EASTERN VISAYAS STATE UNIVERSITY</h3>
                    </div>
                    
                    <h3>Hello {$username},</h3>
                    
                    <p>Thank you for registering with the EVSU Evaluation System. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$verification_link}' class='button'>Verify Email Address</a>
                    </div>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='background: #f0f0f0; padding: 10px; border-radius: 3px; word-break: break-all;'>{$verification_link}</p>
                    
                    <p><strong>Important:</strong> This verification link will expire in {$expiry_time}. If you did not create an account with EVSU Evaluation System, please ignore this email.</p>
                    
                    <p>If you're having trouble clicking the button, copy and paste the URL above into your web browser.</p>
                    
                    <div class='footer'>
                        <p>This is an automated message from the EVSU Evaluation System. Please do not reply to this email.</p>
                        <p>If you need assistance, please contact the ICT Office at <a href='mailto:ict@evsu.edu.ph'>ict@evsu.edu.ph</a></p>
                        <p>&copy; " . date('Y') . " Eastern Visayas State University. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user_id, $email, $subject, $message, 'verification');
    }
    
    /**
     * Send welcome email after verification
     */
    public function sendWelcomeEmail($user_id, $email, $username) {
        $login_link = BASE_URL . "index.php";
        
        $subject = "Welcome to EVSU Evaluation System";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2ecc71; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                .button { display: inline-block; padding: 12px 30px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🎉 Welcome to EVSU Evaluation System!</h2>
                </div>
                <div class='content'>
                    <h3>Hello {$username},</h3>
                    
                    <p>Your email has been successfully verified and your account is now active!</p>
                    
                    <p>You can now login to the EVSU Evaluation System using your credentials:</p>
                    
                    <div style='background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin: 20px 0;'>
                        <p><strong>Login URL:</strong> <a href='{$login_link}'>{$login_link}</a></p>
                    </div>
                    
                    <h4>What you can do:</h4>
                    <ul>
                        <li>Evaluate different offices on campus</li>
                        <li>View your evaluation history</li>
                        <li>Provide feedback for service improvement</li>
                        <li>Track your submitted evaluations</li>
                    </ul>
                    
                    <p>We're excited to have you on board and look forward to your valuable feedback to help improve our campus services.</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$login_link}' class='button'>Login to Your Account</a>
                    </div>
                    
                    <div class='footer'>
                        <p>Thank you for being part of the EVSU community!</p>
                        <p>The EVSU Evaluation System Team</p>
                        <p>&copy; " . date('Y') . " Eastern Visayas State University</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user_id, $email, $subject, $message, 'welcome');
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($user_id, $email, $username, $token) {
        $reset_link = BASE_URL . "reset_password.php?token=" . $token;
        $expiry_time = "1 hour";
        
        $subject = "Password Reset Request - EVSU Evaluation System";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                .button { display: inline-block; padding: 12px 30px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔒 Password Reset Request</h2>
                </div>
                <div class='content'>
                    <h3>Hello {$username},</h3>
                    
                    <p>We received a request to reset your password for the EVSU Evaluation System account.</p>
                    
                    <p>To reset your password, click the button below:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Reset Password</a>
                    </div>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='background: #f0f0f0; padding: 10px; border-radius: 3px; word-break: break-all;'>{$reset_link}</p>
                    
                    <div class='warning'>
                        <p><strong>⚠️ Important:</strong></p>
                        <ul>
                            <li>This link will expire in {$expiry_time}</li>
                            <li>If you didn't request a password reset, please ignore this email</li>
                            <li>Your password will not change until you create a new one</li>
                        </ul>
                    </div>
                    
                    <p>If you're having trouble clicking the button, copy and paste the URL above into your web browser.</p>
                    
                    <div class='footer'>
                        <p>This is an automated security message from EVSU Evaluation System.</p>
                        <p>For security reasons, please do not share this email with anyone.</p>
                        <p>&copy; " . date('Y') . " Eastern Visayas State University</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user_id, $email, $subject, $message, 'password_reset');
    }
    
    /**
     * Generic email sending function
     */
    private function sendEmail($user_id, $to, $subject, $message, $email_type) {
        // Log email attempt
        $log_id = $this->logEmailAttempt($user_id, $to, $email_type);
        
        try {
            // For production, use PHPMailer or similar library
            // For now, we'll use the basic mail() function
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
            $headers .= "Reply-To: {$this->from_email}" . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Send email using mail() function
            if (mail($to, $subject, $message, $headers)) {
                // Update log to success
                $this->updateEmailLog($log_id, 'sent');
                
                // For testing, also create a local copy
                if (ENVIRONMENT === 'development') {
                    $this->saveEmailToFile($to, $subject, $message);
                }
                
                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                $this->updateEmailLog($log_id, 'failed', 'mail() function failed');
                return ['success' => false, 'message' => 'Failed to send email'];
            }
            
        } catch (Exception $e) {
            $this->updateEmailLog($log_id, 'failed', $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Log email attempt
     */
    private function logEmailAttempt($user_id, $to, $email_type) {
        $stmt = $this->conn->prepare("INSERT INTO email_logs (user_id, email_type, recipient_email, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iss", $user_id, $email_type, $to);
        $stmt->execute();
        return $stmt->insert_id;
    }
    
    /**
     * Update email log
     */
    private function updateEmailLog($log_id, $status, $error_message = null) {
        $stmt = $this->conn->prepare("UPDATE email_logs SET status = ?, error_message = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $error_message, $log_id);
        $stmt->execute();
    }
    
    /**
     * Save email to file for testing
     */
    private function saveEmailToFile($to, $subject, $message) {
        $dir = __DIR__ . '/../email_logs/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $filename = $dir . 'email_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.html';
        $content = "To: {$to}\nSubject: {$subject}\n\n{$message}";
        file_put_contents($filename, $content);
    }
    
    /**
     * Generate secure token
     */
    public function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Save token to database
     */
    public function saveToken($user_id, $token, $type = 'verification', $expiry_hours = 24) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));
        
        // First, invalidate any existing tokens of same type
        $stmt = $this->conn->prepare("UPDATE email_tokens SET used = TRUE WHERE user_id = ? AND token_type = ? AND used = FALSE");
        $stmt->bind_param("is", $user_id, $type);
        $stmt->execute();
        
        // Save new token
        $stmt = $this->conn->prepare("INSERT INTO email_tokens (user_id, token, token_type, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $token, $type, $expires_at);
        
        return $stmt->execute();
    }
    
    /**
     * Validate token
     */
    public function validateToken($token, $type = 'verification') {
        $stmt = $this->conn->prepare("
            SELECT et.*, u.email, u.username 
            FROM email_tokens et
            JOIN users u ON et.user_id = u.id
            WHERE et.token = ? AND et.token_type = ? AND et.used = FALSE AND et.expires_at > NOW()
        ");
        $stmt->bind_param("ss", $token, $type);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result;
    }
    
    /**
     * Mark token as used
     */
    public function markTokenUsed($token) {
        $stmt = $this->conn->prepare("UPDATE email_tokens SET used = TRUE WHERE token = ?");
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }
}
?>