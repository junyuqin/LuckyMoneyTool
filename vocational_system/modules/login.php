<?php
/**
 * 登录页面
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

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitizeInput($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    $loginType = $_POST['login_type'] ?? 'password';
    $verificationCode = $_POST['verification_code'] ?? '';
    
    if (empty($identifier)) {
        $error = '请输入手机号或用户名';
    } elseif (empty($password) && $loginType === 'password') {
        $error = '请输入密码';
    } elseif ($loginType === 'code' && empty($verificationCode)) {
        $error = '请输入验证码';
    } else {
        if ($loginType === 'password') {
            // 密码登录
            $user = authenticateUser($identifier, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                
                if ($rememberMe) {
                    createSession($user['id'], true);
                }
                
                logSystemAction($user['id'], 'login', '用户登录成功');
                
                header('Location: index.php');
                exit;
            } else {
                // 检查用户是否存在
                $field = validatePhone($identifier) ? 'phone' : 'username';
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE $field = :identifier");
                $stmt->execute([':identifier' => $identifier]);
                $result = $stmt->fetch();
                
                if ($result['count'] === 0) {
                    $error = '该用户名或手机号未注册';
                } else {
                    $error = '密码错误，请重新输入';
                }
            }
        } else {
            // 验证码登录
            if (!isPhoneRegistered($identifier)) {
                $error = '该手机号未注册';
            } elseif (!verifyCode($identifier, $verificationCode, 'login')) {
                $error = '验证码错误，请重新输入';
            } else {
                $user = getUserByPhone($identifier);
                
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    
                    if ($rememberMe) {
                        createSession($user['id'], true);
                    }
                    
                    logSystemAction($user['id'], 'login', '用户验证码登录成功');
                    
                    header('Location: index.php');
                    exit;
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
    <title>登录 - 中职学生技能成长档案系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-tabs {
            margin-bottom: 20px;
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
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        .login-type-selector {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-graduation-cap"></i> 中职学生技能成长档案系统</h2>
                <p>欢迎回来，请登录您的账户</p>
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
                
                <div class="login-tabs tabs">
                    <div class="tab-item active" data-tab="password-login">
                        <i class="fas fa-key"></i> 密码登录
                    </div>
                    <div class="tab-item" data-tab="code-login">
                        <i class="fas fa-sms"></i> 验证码登录
                    </div>
                </div>
                
                <form method="POST" id="loginForm">
                    <!-- 密码登录 -->
                    <div class="tab-content active" id="password-login">
                        <input type="hidden" name="login_type" value="password">
                        
                        <div class="form-group">
                            <label for="identifier">
                                <i class="fas fa-user"></i> 用户名/手机号
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="identifier" 
                                name="identifier" 
                                placeholder="请输入用户名或手机号"
                                required
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
                                placeholder="请输入密码"
                                minlength="8"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input 
                                    type="checkbox" 
                                    id="remember_me_password" 
                                    name="remember_me"
                                >
                                <label for="remember_me_password">记住我（7 天内免登录）</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-sign-in-alt"></i> 登录
                            </button>
                        </div>
                        
                        <div class="forgot-password">
                            <a href="modules/reset_password.php">忘记密码？</a>
                        </div>
                    </div>
                    
                    <!-- 验证码登录 -->
                    <div class="tab-content" id="code-login">
                        <input type="hidden" name="login_type" value="code">
                        
                        <div class="form-group">
                            <label for="phone_code">
                                <i class="fas fa-mobile-alt"></i> 手机号
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone_code" 
                                name="identifier" 
                                placeholder="请输入 11 位手机号"
                                maxlength="11"
                                pattern="[0-9]{11}"
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
                            <div class="checkbox-group">
                                <input 
                                    type="checkbox" 
                                    id="remember_me_code" 
                                    name="remember_me"
                                >
                                <label for="remember_me_code">记住我（7 天内免登录）</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-sign-in-alt"></i> 登录
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="auth-footer">
                还没有账户？<a href="modules/register.php">立即注册</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // 标签页切换
        document.querySelectorAll('.tab-item').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // 获取验证码
        let countdown = 0;
        const getCodeBtn = document.getElementById('getCodeBtn');
        const phoneInput = document.getElementById('phone_code');
        
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
                body: 'phone=' + encodeURIComponent(phone) + '&type=login'
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
        
        // 手机号验证
        function validatePhone(phone) {
            const pattern = /^1[3-9]\d{9}$/;
            return pattern.test(phone);
        }
    </script>
</body>
</html>
