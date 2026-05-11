<?php
session_start();
$error = '';
$success = '';

// Check if user is in reset process
if (!isset($_SESSION['reset_step']) || !isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit();
}

// Handle code verification and password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($_POST['action'] === 'verify_code') {
            // Verify code
            $enteredCode = trim($_POST['verification_code'] ?? '');
            
            // Check attempts
            $_SESSION['reset_code_attempts'] = ($_SESSION['reset_code_attempts'] ?? 0) + 1;
            
            if ($_SESSION['reset_code_attempts'] > 5) {
                $error = "Too many attempts. Please request a new code.";
                session_destroy();
            } elseif (empty($enteredCode)) {
                $error = "Please enter the verification code";
            } elseif (strlen($enteredCode) !== 6 || !is_numeric($enteredCode)) {
                $error = "Invalid code format. Please enter 6 digits.";
            } elseif (time() - $_SESSION['reset_code_time'] > 900) { // 15 minutes
                $error = "Code has expired. Please request a new one.";
                session_destroy();
            } elseif ($enteredCode !== $_SESSION['reset_code']) {
                $remaining = 5 - $_SESSION['reset_code_attempts'];
                $error = "Invalid code. $remaining attempts remaining.";
            } else {
                // Code verified successfully
                $_SESSION['reset_step'] = 'set_password';
                $_SESSION['code_verified'] = true;
                $success = "Code verified! Please set your new password.";
            }
        } elseif ($_POST['action'] === 'reset_password') {
            // Check if code was verified
            if (!isset($_SESSION['code_verified']) || $_SESSION['code_verified'] !== true) {
                header('Location: forgot-password.php');
                exit();
            }
            
            // Set new password
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword)) {
                $error = "Please enter a new password";
            } elseif (strlen($newPassword) < 8) {
                $error = "Password must be at least 8 characters";
            } elseif (!preg_match('/[A-Z]/', $newPassword)) {
                $error = "Password must contain at least one uppercase letter";
            } elseif (!preg_match('/[a-z]/', $newPassword)) {
                $error = "Password must contain at least one lowercase letter";
            } elseif (!preg_match('/[0-9]/', $newPassword)) {
                $error = "Password must contain at least one number";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "Passwords do not match";
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['reset_user_id']]);
                
                // Clear reset session
                $resetEmail = $_SESSION['reset_email'];
                session_destroy();
                session_start();
                $_SESSION['password_reset_success'] = true;
                
                header('Location: index.php');
                exit();
            }
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again.";
        error_log("Reset password error: " . $e->getMessage());
    }
}

$resetStep = $_SESSION['reset_step'] ?? 'verify_code';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - KATSS Admin</title>
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
        
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #002147 0%, #004080 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .reset-logo {
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
        
        .reset-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .reset-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .reset-body {
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
        
        .password-requirements {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .password-requirements li {
            padding: 5px 0;
            color: #666;
        }
        
        .password-requirements li i {
            margin-right: 10px;
            color: #d1d5db;
        }
        
        .password-requirements li.valid i {
            color: #16a34a;
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
        
        .step-line.completed {
            background: #16a34a;
        }
        
        .step-line.active {
            background: #002147;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 0;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-logo">
                <i class="bi bi-shield-check"></i>
            </div>
            <h1>Reset Password</h1>
            <p>
                <?php if ($resetStep === 'verify_code'): ?>
                    Enter verification code
                <?php else: ?>
                    Set new password
                <?php endif; ?>
            </p>
        </div>
        
        <div class="reset-body">
            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step completed">1</div>
                <div class="step-line <?php echo $resetStep === 'set_password' ? 'completed' : 'active'; ?>"></div>
                <div class="step <?php echo $resetStep === 'verify_code' ? 'active' : 'completed'; ?>">2</div>
                <div class="step-line <?php echo $resetStep === 'set_password' ? 'active' : ''; ?>"></div>
                <div class="step <?php echo $resetStep === 'set_password' ? 'active' : ''; ?>">3</div>
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
            
            <?php if ($resetStep === 'verify_code'): ?>
            <!-- Verify Code Form -->
            <form method="POST" id="verifyForm">
                <input type="hidden" name="action" value="verify_code">
                <div class="form-group">
                    <label>Verification Code</label>
                    <div id="codeInputs" style="display: flex; gap: 10px; justify-content: center;">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]" autofocus>
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                        <input type="text" maxlength="1" class="code-input" inputmode="numeric" pattern="[0-9]">
                    </div>
                    <input type="hidden" name="verification_code" id="fullCode">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill"></i> Verify Code
                </button>
            </form>
            
            <?php elseif ($resetStep === 'set_password'): ?>
            <!-- Set New Password Form -->
            <form method="POST" id="passwordForm">
                <input type="hidden" name="action" value="reset_password">
                
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li id="req-length"><i class="bi bi-circle"></i> At least 8 characters</li>
                        <li id="req-uppercase"><i class="bi bi-circle"></i> One uppercase letter (A-Z)</li>
                        <li id="req-lowercase"><i class="bi bi-circle"></i> One lowercase letter (a-z)</li>
                        <li id="req-number"><i class="bi bi-circle"></i> One number (0-9)</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="new_password" id="newPassword" 
                               placeholder="Enter new password" required
                               oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="confirm_password" id="confirmPassword" 
                               placeholder="Confirm new password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    <small id="passwordMatch" style="color: #dc2626; display: none;">
                        <i class="bi bi-exclamation-circle"></i> Passwords do not match
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-shield-check"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Code input handling
    const codeInputs = document.querySelectorAll('.code-input');
    
    codeInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }
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
                if (codeInputs[i]) codeInputs[i].value = digit;
            });
            updateFullCode();
        });
    });
    
    function updateFullCode() {
        const code = Array.from(codeInputs).map(input => input.value).join('');
        const fullCodeInput = document.getElementById('fullCode');
        if (fullCodeInput) fullCodeInput.value = code;
    }
    
    // Password strength checker
    function checkPasswordStrength(password) {
        const hasLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        
        updateRequirement('req-length', hasLength);
        updateRequirement('req-uppercase', hasUpper);
        updateRequirement('req-lowercase', hasLower);
        updateRequirement('req-number', hasNumber);
        
        checkPasswordMatch();
    }
    
    function updateRequirement(id, valid) {
        const element = document.getElementById(id);
        if (element) {
            if (valid) {
                element.classList.add('valid');
                element.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                element.classList.remove('valid');
                element.querySelector('i').className = 'bi bi-circle';
            }
        }
    }
    
    function checkPasswordMatch() {
        const newPass = document.getElementById('newPassword').value;
        const confirmPass = document.getElementById('confirmPassword').value;
        const matchMsg = document.getElementById('passwordMatch');
        
        if (matchMsg) {
            if (confirmPass && newPass !== confirmPass) {
                matchMsg.style.display = 'block';
            } else {
                matchMsg.style.display = 'none';
            }
        }
    }
    
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash-fill';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye-fill';
        }
    }
    
    // Add event listener for password matching
    document.getElementById('confirmPassword')?.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>