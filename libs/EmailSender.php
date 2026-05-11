<?php
/**
 * Email Sender Class using PHPMailer
 * Handles all email sending with proper error handling
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php'; // If using Composer
// require_once __DIR__ . '/PHPMailer/PHPMailer.php'; // If manual install
// require_once __DIR__ . '/PHPMailer/SMTP.php';
// require_once __DIR__ . '/PHPMailer/Exception.php';

require_once __DIR__ . '/../config/email_config.php';

class EmailSender {
    private $mail;
    private $debug = false;
    
    public function __construct($debug = false) {
        $this->mail = new PHPMailer(true);
        $this->debug = $debug;
        $this->setupSMTP();
    }
    
    /**
     * Configure SMTP settings
     */
    private function setupSMTP() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = SMTP_HOST;
            $this->mail->SMTPAuth   = SMTP_AUTH;
            $this->mail->Username   = SMTP_USERNAME;
            $this->mail->Password   = SMTP_PASSWORD;
            $this->mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = SMTP_PORT;
            
            // Debug settings
            $this->mail->SMTPDebug  = $this->debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
            
            // Default sender
            $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $this->mail->addReplyTo(MAIL_REPLY_TO, MAIL_REPLY_TO_NAME);
            
            // Character set
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Encoding = 'base64';
            
        } catch (Exception $e) {
            error_log("Email setup error: " . $e->getMessage());
        }
    }
    
    /**
     * Send password reset code
     */
    public function sendPasswordResetCode($toEmail, $toName, $code, $username) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);
            
            $this->mail->isHTML(true);
            $this->mail->Subject = "🔐 Password Reset Code - KATSS Admin Panel";
            
            $this->mail->Body = $this->getResetCodeTemplate($toName, $code, $username);
            $this->mail->AltBody = $this->getResetCodePlainText($toName, $code, $username);
            
            if ($this->debug) {
                // In debug mode, save to file instead of sending
                $this->saveToFile($toEmail, 'Password Reset Code', $this->mail->Body);
                return true;
            }
            
            $this->mail->send();
            
            // Log successful send
            $this->logEmail($toEmail, 'password_reset_code', 'success');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send reset code to {$toEmail}: " . $e->getMessage());
            
            // Log failed attempt
            $this->logEmail($toEmail, 'password_reset_code', 'failed', $e->getMessage());
            
            // Fallback to PHP mail() if SMTP fails
            if (!$this->debug) {
                return $this->fallbackMail($toEmail, $this->mail->Subject, $this->mail->Body);
            }
            
            return false;
        }
    }
    
    /**
     * Send password change confirmation
     */
    public function sendPasswordChangedConfirmation($toEmail, $toName) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);
            
            $this->mail->isHTML(true);
            $this->mail->Subject = "✅ Password Changed Successfully - KATSS Admin Panel";
            
            $this->mail->Body = $this->getPasswordChangedTemplate($toName);
            $this->mail->AltBody = $this->getPasswordChangedPlainText($toName);
            
            $this->mail->send();
            
            $this->logEmail($toEmail, 'password_changed', 'success');
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send confirmation to {$toEmail}: " . $e->getMessage());
            $this->logEmail($toEmail, 'password_changed', 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send login notification for new device/location
     */
    public function sendLoginNotification($toEmail, $toName, $ipAddress, $userAgent, $location = 'Unknown') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);
            
            $this->mail->isHTML(true);
            $this->mail->Subject = "🔔 New Login Detected - KATSS Admin Panel";
            
            $this->mail->Body = $this->getLoginNotificationTemplate($toName, $ipAddress, $userAgent, $location);
            $this->mail->AltBody = $this->getLoginNotificationPlainText($toName, $ipAddress, $userAgent, $location);
            
            $this->mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send login notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get HTML template for reset code email
     */
    private function getResetCodeTemplate($name, $code, $username) {
        $year = date('Y');
        $expiryTime = '15 minutes';
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f4f4f4;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }
                .email-header {
                    background: linear-gradient(135deg, #002147 0%, #004080 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                }
                .email-header p {
                    margin: 10px 0 0;
                    opacity: 0.9;
                    font-size: 14px;
                }
                .email-body {
                    padding: 30px;
                }
                .greeting {
                    font-size: 16px;
                    color: #333;
                    margin-bottom: 20px;
                }
                .code-container {
                    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
                    padding: 20px;
                    border-radius: 10px;
                    text-align: center;
                    margin: 20px 0;
                }
                .code {
                    font-size: 36px;
                    font-weight: bold;
                    color: #002147;
                    letter-spacing: 15px;
                    margin: 0;
                }
                .code-label {
                    font-size: 12px;
                    color: #666;
                    margin-top: 10px;
                }
                .info-box {
                    background: #f9fafb;
                    border-left: 4px solid #002147;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .info-box p {
                    margin: 5px 0;
                    font-size: 14px;
                    color: #666;
                }
                .warning-box {
                    background: #fef3c7;
                    border-left: 4px solid #f59e0b;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .warning-box p {
                    margin: 5px 0;
                    font-size: 13px;
                    color: #92400e;
                }
                .email-footer {
                    background: #f9fafb;
                    padding: 20px;
                    text-align: center;
                    border-top: 1px solid #e5e7eb;
                }
                .email-footer p {
                    font-size: 12px;
                    color: #999;
                    margin: 5px 0;
                }
                .social-links {
                    margin-top: 10px;
                }
                .social-links a {
                    color: #002147;
                    text-decoration: none;
                    margin: 0 10px;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <h1>🔐 Password Reset</h1>
                    <p>KATSS Admin Panel</p>
                </div>
                
                <div class="email-body">
                    <p class="greeting">Dear <strong>{$name}</strong>,</p>
                    
                    <p>We received a request to reset the password for your KATSS admin account (<strong>{$username}</strong>).</p>
                    
                    <p>Use the verification code below to complete the password reset process:</p>
                    
                    <div class="code-container">
                        <div class="code">{$code}</div>
                        <div class="code-label">Verification Code</div>
                    </div>
                    
                    <div class="info-box">
                        <p><strong>📧 Account:</strong> {$username}</p>
                        <p><strong>⏰ Expires:</strong> {$expiryTime} from now</p>
                        <p><strong>🕒 Requested:</strong> {$this->getCurrentTime()}</p>
                    </div>
                    
                    <div class="warning-box">
                        <p><strong>⚠️ Security Warning:</strong></p>
                        <p>• Never share this code with anyone</p>
                        <p>• KATSS staff will never ask for your verification code</p>
                        <p>• If you didn't request this, please contact support immediately</p>
                        <p>• The code can only be used once</p>
                    </div>
                    
                    <p>If you did not request a password reset, please ignore this email or contact the system administrator at <a href="mailto:{MAIL_REPLY_TO}">{MAIL_REPLY_TO}</a>.</p>
                </div>
                
                <div class="email-footer">
                    <p>This is an automated message from KATSS Admin Panel</p>
                    <p>Please do not reply to this email</p>
                    <div class="social-links">
                        <a href="#">Privacy Policy</a> |
                        <a href="#">Terms of Service</a> |
                        <a href="#">Contact Support</a>
                    </div>
                    <p style="margin-top: 10px;">&copy; {$year} KATSS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
HTML;
    }
    
    /**
     * Get HTML template for password changed confirmation
     */
    private function getPasswordChangedTemplate($name) {
        $year = date('Y');
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f4f4f4;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }
                .email-header {
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .email-body {
                    padding: 30px;
                }
                .success-icon {
                    text-align: center;
                    font-size: 48px;
                    margin: 20px 0;
                }
                .email-footer {
                    background: #f9fafb;
                    padding: 20px;
                    text-align: center;
                    border-top: 1px solid #e5e7eb;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <h1>✅ Password Changed</h1>
                    <p>KATSS Admin Panel</p>
                </div>
                
                <div class="email-body">
                    <div class="success-icon">🔒</div>
                    <p>Dear <strong>{$name}</strong>,</p>
                    <p>Your password has been changed successfully.</p>
                    <p>If you did not make this change, please contact support immediately at <a href="mailto:{MAIL_REPLY_TO}">{MAIL_REPLY_TO}</a>.</p>
                    <p>Time of change: <strong>{$this->getCurrentTime()}</strong></p>
                </div>
                
                <div class="email-footer">
                    <p>&copy; {$year} KATSS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
HTML;
    }
    
    /**
     * Get HTML template for login notification
     */
    private function getLoginNotificationTemplate($name, $ip, $userAgent, $location) {
        $year = date('Y');
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f4f4f4;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }
                .email-header {
                    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .email-body {
                    padding: 30px;
                }
                .login-details {
                    background: #f9fafb;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .login-details p {
                    margin: 5px 0;
                    font-size: 14px;
                }
                .email-footer {
                    background: #f9fafb;
                    padding: 20px;
                    text-align: center;
                    border-top: 1px solid #e5e7eb;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <h1>🔔 New Login</h1>
                    <p>KATSS Admin Panel</p>
                </div>
                
                <div class="email-body">
                    <p>Dear <strong>{$name}</strong>,</p>
                    <p>A new login to your KATSS admin account was detected.</p>
                    
                    <div class="login-details">
                        <p><strong>🌍 IP Address:</strong> {$ip}</p>
                        <p><strong>📍 Location:</strong> {$location}</p>
                        <p><strong>🖥 Browser:</strong> {$userAgent}</p>
                        <p><strong>🕒 Time:</strong> {$this->getCurrentTime()}</p>
                    </div>
                    
                    <p>If this was you, you can ignore this email. If you don't recognize this activity, please change your password immediately.</p>
                </div>
                
                <div class="email-footer">
                    <p>&copy; {$year} KATSS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
HTML;
    }
    
    /**
     * Plain text versions for email clients
     */
    private function getResetCodePlainText($name, $code, $username) {
        return "Dear {$name},\n\n"
             . "We received a request to reset your KATSS admin password.\n\n"
             . "Your verification code is: {$code}\n\n"
             . "Username: {$username}\n"
             . "Code expires in 15 minutes.\n\n"
             . "If you didn't request this, please ignore this email.\n"
             . "KATSS Admin Panel";
    }
    
    private function getPasswordChangedPlainText($name) {
        return "Dear {$name},\n\n"
             . "Your KATSS admin password has been changed successfully.\n\n"
             . "If you didn't make this change, contact support immediately.\n\n"
             . "KATSS Admin Panel";
    }
    
    private function getLoginNotificationPlainText($name, $ip, $userAgent, $location) {
        return "Dear {$name},\n\n"
             . "New login detected on your KATSS admin account.\n\n"
             . "IP Address: {$ip}\n"
             . "Location: {$location}\n"
             . "Browser: {$userAgent}\n\n"
             . "If this wasn't you, change your password immediately.\n\n"
             . "KATSS Admin Panel";
    }
    
    /**
     * Fallback to PHP mail() function
     */
    private function fallbackMail($to, $subject, $message) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: " . MAIL_REPLY_TO_NAME . " <" . MAIL_REPLY_TO . ">\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    /**
     * Save email to file for debugging
     */
    private function saveToFile($to, $subject, $content) {
        $logDir = __DIR__ . '/../logs/emails/';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $filename = $logDir . date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-zA-Z0-9@._-]/', '_', $to) . '.html';
        file_put_contents($filename, $content);
        error_log("Email saved to: " . $filename);
    }
    
    /**
     * Log email to database or file
     */
    private function logEmail($to, $type, $status, $error = '') {
        $logMessage = date('Y-m-d H:i:s') . " | {$type} | {$to} | {$status}";
        if ($error) {
            $logMessage .= " | Error: {$error}";
        }
        
        error_log($logMessage);
        
        // Optional: Log to database
        // $this->saveToDatabase($to, $type, $status, $error);
    }
    
    /**
     * Get current time formatted
     */
    private function getCurrentTime() {
        return date('F j, Y \a\t g:i A');
    }
}