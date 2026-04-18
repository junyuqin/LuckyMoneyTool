<?php
/**
 * 注册页面
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

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $verificationCode = $_POST['verification_code'] ?? '';
    
    // 验证手机号
    if (empty($phone)) {
        $error = '请输入手机号';
    } elseif (!validatePhone($phone)) {
        $error = '请输入有效的 11 位手机号码';
    } elseif (isPhoneRegistered($phone)) {
        $error = '该手机号已被注册';
    } elseif (empty($verificationCode)) {
        $error = '请输入验证码';
    } elseif (!verifyCode($phone, $verificationCode, 'register')) {
        $error = '验证码错误或已过期';
    } elseif (empty($password)) {
        $error = '请输入密码';
    } elseif (strlen($password) < 8) {
        $error = '密码长度不能少于 8 位';
    } elseif ($password === $phone) {
        $error = '密码不能与手机号相同';
    } elseif (strpos($password, ' ') !== false) {
        $error = '密码不能包含空格';
    } elseif ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } else {
        // 验证密码强度
        $strengthResult = validatePasswordStrength($password);
        if (!$strengthResult['valid']) {
            $error = $strengthResult['message'];
        } else {
            // 生成用户名（如果未提供）
            if (empty($username)) {
                $username = generateRandomUsername();
            }
            
            // 检查用户名是否已存在
            if (isUsernameExists($username)) {
                $error = '该用户名已被使用';
            } else {
                // 创建用户
                $userId = createUser($username, $phone, $password);
                
                if ($userId) {
                    logSystemAction($userId, 'register', '用户注册成功');
                    
                    // 设置 session
                    $_SESSION['user_id'] = $userId;
                    
                    header('Location: index.php?registered=1');
                    exit;
                } else {
                    $error = '注册失败，请稍后重试';
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
    <title>注册 - 中职学生技能成长档案系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .password-requirements {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
            padding: 10px;
            background: var(--light-color);
            border-radius: 4px;
        }
        .password-requirements ul {
            margin: 5px 0 0 20px;
        }
        .password-requirements li {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-user-plus"></i> 用户注册</h2>
                <p>创建您的账户，开始技能成长之旅</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-mobile-alt"></i> 手机号
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="phone" 
                            name="phone" 
                            placeholder="请输入 11 位手机号"
                            maxlength="11"
                            pattern="[0-9]{11}"
                            required
                        >
                        <div class="invalid-feedback" id="phoneError"></div>
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
                        <label for="username">
                            <i class="fas fa-user"></i> 用户名
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            placeholder="请输入用户名（可选，留空将自动生成）"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> 密码
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="请设置不少于 8 位的密码"
                            minlength="8"
                            required
                        >
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-strength-text" id="strengthText"></div>
                        <div class="password-requirements">
                            <strong>密码要求：</strong>
                            <ul>
                                <li>长度不少于 8 位字符</li>
                                <li>建议包含大小写字母、数字及特殊符号</li>
                                <li>不能与手机号或验证码相同</li>
                                <li>不能包含空格</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> 确认密码
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="请再次输入密码"
                            minlength="8"
                            required
                        >
                        <div class="invalid-feedback" id="confirmPasswordError"></div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-user-plus"></i> 注册
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="auth-footer">
                已有账户？<a href="modules/login.php">立即登录</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        let countdown = 0;
        const getCodeBtn = document.getElementById('getCodeBtn');
        const phoneInput = document.getElementById('phone');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        // 获取验证码
        getCodeBtn.addEventListener('click', function() {
            const phone = phoneInput.value.trim();
            
            if (!validatePhone(phone)) {
                alert('请输入正确的 11 位手机号');
                return;
            }
            
            if (countdown > 0) {
                return;
            }
            
            // 发送获取验证码请求
            fetch('api/send_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'phone=' + encodeURIComponent(phone) + '&type=register'
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
        
        // 确认密码验证
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
        
        // 手机号验证
        phoneInput.addEventListener('blur', function() {
            const phone = this.value.trim();
            const phoneError = document.getElementById('phoneError');
            
            if (phone.length > 0 && !validatePhone(phone)) {
                this.classList.add('is-invalid');
                phoneError.textContent = '请输入有效的 11 位手机号码';
            } else {
                this.classList.remove('is-invalid');
                phoneError.textContent = '';
            }
        });
        
        function validatePhone(phone) {
            const pattern = /^1[3-9]\d{9}$/;
            return pattern.test(phone);
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
        
        // 表单提交验证
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('两次输入的密码不一致');
                confirmPasswordInput.focus();
                return false;
            }
            
            const strength = checkPasswordStrength(password);
            if (strength.score <= 2) {
                if (!confirm('密码强度较弱，是否继续注册？')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>
