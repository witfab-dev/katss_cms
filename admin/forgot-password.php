<?php
session_start();
$error = '';
$success = '';

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($_POST['action'] === 'send_code') {
            // Step 1: Send verification code
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                $error = "Please enter your email address";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address";
            } else {
                // Check if email exists
                $stmt = $db->prepare("SELECT id, username, full_name FROM admin_users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Generate 6-digit verification code
                    $code = sprintf("%06d", mt_rand(0, 999999));
                    
                    // Store code in session (or database)
                    $_SESSION['reset_code'] = $code;
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_code_time'] = time();
                    $_SESSION['reset_code_attempts'] = 0;
                    
                    // Send email with verification code
                    $to = $email;
                    $subject = "Password Reset Code - KATSS Admin Panel";
                    
                    // Email headers
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: KATSS Admin <noreply@katss.ac.rw>\r\n";
                    $headers .= "Reply-To: admin@katss.ac.rw\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    
                    // Email body
                    $message = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: #002147; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                            .code { font-size: 32px; font-weight: bold; color: #002147; text-align: center; 
                                    letter-spacing: 10px; padding: 20px; background: #e0e7ff; 
                                    border-radius: 10px; margin: 20px 0; }
                            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                            .warning { color: #d9534f; font-size: 13px; margin-top: 20px; padding: 10px; 
                                      background: #fcf8e3; border-left: 4px solid #f0ad4e; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>KATSS Admin Panel</h2>
                                <p>Password Reset Request</p>
                            </div>
                            <div class="content">
                                <p>Dear <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                                <p>We received a request to reset the password for your KATSS admin account. Use the verification code below to proceed:</p>
                                
                                <div class="code">' . $code . '</div>
                                
                                <p>This code will expire in <strong>15 minutes</strong>.</p>
                                <p>If you did not request a password reset, please ignore this email or contact the system administrator immediately.</p>
                                
                                <div class="warning">
                                    <strong>⚠ Security Warning:</strong> Never share this code with anyone. 
                                    KATSS staff will never ask for your verification code.
                                </div>
                            </div>
                            <div class="footer">
                                <p>This is an automated message from KATSS Admin Panel</p>
                                <p>&copy; ' . date('Y') . ' KATSS. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>';
                    
                    // Send email
                    $mailSent = mail($to, $subject, $message, $headers);
                    
                    // For development: if mail() fails, still show the code
                    if ($mailSent) {
                        $success = "Verification code has been sent to your email. Please check your inbox and spam folder.";
                    } else {
                        // In production, you'd want to handle this differently
                        $success = "Verification code has been generated. For development: Your code is <strong>" . $code . "</strong>";
                        // Store for development display
                        $_SESSION['dev_code'] = $code;
                    }
                    
                    $_SESSION['reset_step'] = 'verify_code';
                    
                } else {
                    // Don't reveal if email exists or not for security
                    $success = "If this email exists in our system, a verification code has been sent.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again later.";
        error_log("Forgot password error: " . $e->getMessage());
    }
}

// Check if we're in verify code step
$resetStep = $_SESSION['reset_step'] ?? 'request';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - KATSS Admin</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #002147 0%, #004080 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .forgot-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 40px;
            color: #002147;
        }
        
        .forgot-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .forgot-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .forgot-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        
        .input-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .input-icon input:focus {
            outline: none;
            border-color: #002147;
            box-shadow: 0 0 0 3px rgba(0,33,71,0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #002147 0%, #004080 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,33,71,0.3);
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .verification-code-input {
            text-align: center;
            letter-spacing: 20px;
            font-size: 24px !important;
            font-weight: bold;
            padding-left: 15px !important;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #002147;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            color: #666;
        }
        
        .step.active {
            background: #002147;
            color: white;
        }
        
        .step.completed {
            background: #16a34a;
            color: white;
        }
        
        .step-line {
            width: 40px;
            height: 2px;
            background: #e0e0e0;
            align-self: center;
        }
        
        .step-line.active {
            background: #002147;
        }
        
        .timer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .timer.warning {
            color: #dc2626;
            font-weight: bold;
        }
        
        .resend-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .resend-link a {
            color: #002147;
            text-decoration: none;
            font-size: 14px;
        }
        
        .resend-link a:hover {
            text-decoration: underline;
        }

        #codeInputs {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        #codeInputs input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.3s;
        }

        #codeInputs input:focus {
            border-color: #002147;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="forgot-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1>Forgot Password</h1>
            <p>KATSS Admin Panel</p>
        </div>
        
        <div class="forgot-body">
            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step-line <?php echo $resetStep === 'verify_code' ? 'active' : ''; ?>"></div>
                <div class="step <?php echo $resetStep === 'verify_code' ? 'active' : ''; ?>">2</div>
                <div class="step-line"></div>
                <div class="step">3</div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($resetStep === 'request'): ?>
            <!-- Step 1: Enter Email -->
            <form method="POST" id="emailForm">
                <input type="hidden" name="action" value="send_code">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope-fill"></i>
                        <input type="email" name="email" placeholder="Enter your email address" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="sendCodeBtn">
                    <i class="bi bi-send-fill"></i> Send Verification Code
                </button>
            </form>
            
            <?php elseif ($resetStep === 'verify_code'): ?>
            <!-- Step 2: Enter Verification Code -->
            <form method="POST" action="reset-password.php" id="codeForm">
                <input type="hidden" name="action" value="verify_code">
                <div class="form-group">
                    <label>Verification Code</label>
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
                    </p>
                    <div id="codeInputs">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]" autofocus>
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                    </div>
                    <input type="hidden" name="verification_code" id="fullCode">
                </div>
                
                <div class="timer" id="timer">Code expires in: <span id="timeLeft">15:00</span></div>
                
                <button type="submit" class="btn btn-primary" id="verifyCodeBtn" style="margin-top: 15px;">
                    <i class="bi bi-check-circle-fill"></i> Verify Code
                </button>
                
                <div class="resend-link">
                    <a href="forgot-password.php?resend=1">Resend Code</a>
                </div>
            </form>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="index.php"><i class="bi bi-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <script>
    // Code input handling
    document.addEventListener('DOMContentLoaded', function() {
        const codeInputs = document.querySelectorAll('.code-input');
        
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value && index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }
                
                // Combine all inputs into hidden field
                updateFullCode();
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const digits = pastedText.replace(/[^0-9]/g, '').substring(0, 6);
                
                digits.split('').forEach((digit, i) => {
                    if (codeInputs[i]) {
                        codeInputs[i].value = digit;
                    }
                });
                
                updateFullCode();
                if (digits.length === 6) {
                    codeInputs[5].focus();
                }
            });
        });
        
        function updateFullCode() {
            const code = Array.from(codeInputs).map(input => input.value).join('');
            document.getElementById('fullCode').value = code;
            
            // Auto-submit when 6 digits are entered
            if (code.length === 6) {
                document.getElementById('verifyCodeBtn').focus();
            }
        }
        
        // Timer countdown
        <?php if ($resetStep === 'verify_code'): ?>
        let timeLeft = 15 * 60; // 15 minutes
        const timeLeftEl = document.getElementById('timeLeft');
        const timerEl = document.getElementById('timer');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timeLeftEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 60) {
                timerEl.classList.add('warning');
            }
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerEl.textContent = 'Code has expired. Please request a new one.';
                timerEl.classList.add('warning');
                document.getElementById('verifyCodeBtn').disabled = true;
            }
            
            timeLeft--;
        }
        
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        // Auto-fill code for development
        <?php if (isset($_SESSION['dev_code'])): ?>
        const devCode = '<?php echo $_SESSION['dev_code']; ?>';
        devCode.split('').forEach((digit, i) => {
            if (codeInputs[i]) codeInputs[i].value = digit;
        });
        updateFullCode();
        <?php endif; ?>
    });
    </script>
</body>
</html>