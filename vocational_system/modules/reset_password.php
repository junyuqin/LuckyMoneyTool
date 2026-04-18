<?php
/**
 * 重置密码页面
 * 中职学生技能成长档案系统
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 如果已登录，跳转到首页
if (getCurrentUser()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 1;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // 第一步：验证手机号
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            $error = '请输入手机号';
        } elseif (!validatePhone($phone)) {
            $error = '请输入有效的 11 位手机号码';
        } elseif (!isPhoneRegistered($phone)) {
            $error = '该手机号未注册';
        } else {
            // 手机号验证通过，进入第二步
            $_SESSION['reset_phone'] = $phone;
            $step = 2;
        }
    } elseif ($step == 2) {
        // 第二步：验证验证码
        $phone = $_SESSION['reset_phone'] ?? '';
        $verificationCode = $_POST['verification_code'] ?? '';
        
        if (empty($phone)) {
            $error = '请先输入手机号';
            $step = 1;
        } elseif (empty($verificationCode)) {
            $error = '请输入验证码';
        } elseif (!verifyCode($phone, $verificationCode, 'reset_password')) {
            $error = '验证码错误或已过期';
        } else {
            // 验证码验证通过，进入第三步
            $step = 3;
        }
    } elseif ($step == 3) {
        // 第三步：设置新密码
        $phone = $_SESSION['reset_phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($phone)) {
            $error = '会话已过期，请重新开始';
            $step = 1;
        } elseif (empty($password)) {
            $error = '请输入新密码';
        } elseif (strlen($password) < 8) {
            $error = '密码长度不能少于 8 位';
        } elseif ($password !== $confirmPassword) {
            $error = '两次输入的密码不一致';
        } else {
            // 验证密码强度
            $strengthResult = validatePasswordStrength($password);
            if (!$strengthResult['valid']) {
                $error = $strengthResult['message'];
            } else {
                // 重置密码
                if (resetPassword($phone, $password)) {
                    $user = getUserByPhone($phone);
                    logSystemAction($user['id'], 'reset_password', '用户重置密码成功');
                    
                    // 清除会话
                    unset($_SESSION['reset_phone']);
                    
                    $success = '密码重置成功！即将跳转到登录页面...';
                    $step = 4;
                    
                    header('refresh:3;url=login.php');
                } else {
                    $error = '密码重置失败，请稍后重试';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码 - 中职学生技能成长档案系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-color);
            z-index: 1;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--border-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .step-item.active .step-number {
            background: var(--primary-color);
        }
        .step-item.completed .step-number {
            background: var(--success-color);
        }
        .step-text {
            font-size: 14px;
            color: var(--text-muted);
        }
        .step-item.active .step-text {
            color: var(--primary-color);
            font-weight: 500;
        }
        .verification-code-group {
            display: flex;
            gap: 10px;
        }
        .verification-code-group input {
            flex: 1;
        }
        .get-code-btn {
            padding: 10px 15px;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-key"></i> 重置密码</h2>
                <p>按照步骤重置您的账户密码</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <!-- 步骤指示器 -->
                <div class="step-indicator">
                    <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                        <div class="step-number">
                            <?php if ($step > 1): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                1
                            <?php endif; ?>
                        </div>
                        <span class="step-text">验证手机号</span>
                    </div>
                    <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                        <div class="step-number">
                            <?php if ($step > 2): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                2
                            <?php endif; ?>
                        </div>
                        <span class="step-text">验证验证码</span>
                    </div>
                    <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <span class="step-text">设置新密码</span>
                    </div>
                </div>
                
                <form method="POST" id="resetForm">
                    <!-- 第一步：验证手机号 -->
                    <?php if ($step == 1): ?>
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-mobile-alt"></i> 注册手机号
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone" 
                                name="phone" 
                                placeholder="请输入注册时使用的手机号"
                                maxlength="11"
                                pattern="[0-9]{11}"
                                required
                                autofocus
                            >
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-arrow-right"></i> 下一步
                            </button>
                        </div>
                    
                    <!-- 第二步：验证验证码 -->
                    <?php elseif ($step == 2): ?>
                        <div class="form-group">
                            <label for="phone_display">
                                <i class="fas fa-mobile-alt"></i> 手机号
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="phone_display" 
                                value="<?php echo htmlspecialchars($_SESSION['reset_phone'] ?? ''); ?>"
                                readonly
                                style="background: var(--light-color);"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="verification_code">
                                <i class="fas fa-shield-alt"></i> 验证码
                                <span class="required">*</span>
                            </label>
                            <div class="verification-code-group">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="verification_code" 
                                    name="verification_code" 
                                    placeholder="请输入验证码"
                                    maxlength="6"
                                    pattern="[0-9]{4,6}"
                                    required
                                    autofocus
                                >
                                <button 
                                    type="button" 
                                    class="btn btn-outline get-code-btn" 
                                    id="getCodeBtn"
                                >
                                    获取验证码
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-arrow-right"></i> 下一步
                            </button>
                        </div>
                    
                    <!-- 第三步：设置新密码 -->
                    <?php elseif ($step == 3): ?>
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> 新密码
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="请设置不少于 8 位的新密码"
                                minlength="8"
                                required
                                autofocus
                            >
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-strength-text" id="strengthText"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> 确认新密码
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="请再次输入新密码"
                                minlength="8"
                                required
                            >
                            <div class="invalid-feedback" id="confirmPasswordError"></div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-check"></i> 提交
                            </button>
                        </div>
                    
                    <!-- 第四步：成功 -->
                    <?php elseif ($step == 4): ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle" style="font-size: 60px; color: var(--success-color); margin-bottom: 20px;"></i>
                            <h3>密码重置成功！</h3>
                            <p class="text-muted">正在跳转到登录页面...</p>
                            <a href="login.php" class="btn btn-primary mt-20">
                                <i class="fas fa-sign-in-alt"></i> 立即登录
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="auth-footer">
                <a href="login.php"><i class="fas fa-arrow-left"></i> 返回登录</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        let countdown = 0;
        const getCodeBtn = document.getElementById('getCodeBtn');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        // 获取验证码
        if (getCodeBtn) {
            getCodeBtn.addEventListener('click', function() {
                const phone = '<?php echo $_SESSION['reset_phone'] ?? ''; ?>';
                
                if (!phone) {
                    alert('请先输入手机号');
                    return;
                }
                
                if (countdown > 0) {
                    return;
                }
                
                fetch('api/send_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'phone=' + encodeURIComponent(phone) + '&type=reset_password'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('验证码已发送，请注意查收短信');
                        startCountdown();
                    } else {
                        alert(data.message || '发送失败，请重试');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('发送失败，请重试');
                });
            });
        }
        
        function startCountdown() {
            countdown = 60;
            getCodeBtn.disabled = true;
            getCodeBtn.textContent = countdown + '秒后重发';
            
            const interval = setInterval(() => {
                countdown--;
                
                if (countdown <= 0) {
                    clearInterval(interval);
                    getCodeBtn.disabled = false;
                    getCodeBtn.textContent = '获取验证码';
                } else {
                    getCodeBtn.textContent = countdown + '秒后重发';
                }
            }, 1000);
        }
        
        // 密码强度检测
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length === 0) {
                    strengthBar.className = 'password-strength-bar';
                    strengthText.textContent = '';
                    return;
                }
                
                const strength = checkPasswordStrength(password);
                
                if (strength.score <= 2) {
                    strengthBar.className = 'password-strength-bar weak';
                    strengthText.textContent = '密码强度：弱 - ' + strength.message;
                    strengthText.style.color = 'var(--danger-color)';
                } else if (strength.score <= 4) {
                    strengthBar.className = 'password-strength-bar medium';
                    strengthText.textContent = '密码强度：中 - ' + strength.message;
                    strengthText.style.color = 'var(--warning-color)';
                } else {
                    strengthBar.className = 'password-strength-bar strong';
                    strengthText.textContent = '密码强度：强 - ' + strength.message;
                    strengthText.style.color = 'var(--success-color)';
                }
            });
        }
        
        // 确认密码验证
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword.length === 0) {
                    this.classList.remove('is-invalid', 'is-valid');
                    return;
                }
                
                if (password === confirmPassword) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    document.getElementById('confirmPasswordError').textContent = '';
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('confirmPasswordError').textContent = '两次输入的密码不一致';
                }
            });
        }
        
        function checkPasswordStrength(password) {
            let score = 0;
            const length = password.length;
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasDigit = /[0-9]/.test(password);
            const hasSpecial = /[^a-zA-Z0-9]/.test(password);
            
            if (length >= 8) score++;
            if (length >= 12) score++;
            if (length >= 16) score++;
            if (hasLower) score++;
            if (hasUpper) score++;
            if (hasDigit) score++;
            if (hasSpecial) score++;
            
            let message = '';
            if (score <= 2) {
                message = '建议增加长度和字符类型';
            } else if (score <= 4) {
                message = '密码强度中等，建议继续增强';
            } else {
                message = '密码强度很好';
            }
            
            return { score, message };
        }
    </script>
</body>
</html>
