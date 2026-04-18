<?php
/**
 * 中职技能竞赛训练辅助系统 - 用户登录页面
 */
require_once __DIR__ . '/includes/config.php';

// 初始化数据库
checkAndInitializeDatabase();

// 如果已登录，跳转到首页
session_start();
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

// 处理记住我功能
if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        redirect('index.php');
    }
}

$errors = [];
$success = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'password';
    
    if ($loginType === 'password') {
        // 用户名/手机号 + 密码登录
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // 验证输入
        if (empty($identifier)) {
            $errors[] = '请输入用户名或手机号';
        }
        if (empty($password)) {
            $errors[] = '请输入密码';
        }
        
        if (empty($errors)) {
            $db = getDBConnection();
            
            // 查询用户
            $stmt = $db->prepare("
                SELECT * FROM users 
                WHERE (username = :identifier OR phone = :identifier) 
                AND is_active = 1
            ");
            $stmt->execute([':identifier' => $identifier]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $errors[] = '该用户名或手机号未注册';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $errors[] = '密码错误，请重新输入';
                // 记录登录失败次数（实际应用中应实现账户锁定机制）
            } else {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 更新最后登录时间
                $db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?")
                   ->execute([$user['id']]);
                
                // 处理记住我
                if ($remember) {
                    $rememberToken = bin2hex(random_bytes(32));
                    $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?")
                       ->execute([$rememberToken, $user['id']]);
                    setcookie('remember_token', $rememberToken, time() + 7 * 24 * 60 * 60, '/');
                }
                
                // 记录日志
                logSystemAction($user['id'], 'login', '用户登录成功');
                
                redirect('index.php');
            }
        }
    } else {
        // 手机号 + 验证码登录
        $phone = trim($_POST['phone'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $remember = isset($_POST['remember']);
        
        // 验证输入
        if (empty($phone)) {
            $errors[] = '请输入手机号';
        } elseif (!validatePhone($phone)) {
            $errors[] = '请输入有效的手机号';
        }
        if (empty($code)) {
            $errors[] = '请输入验证码';
        }
        
        if (empty($errors)) {
            $db = getDBConnection();
            
            // 查询用户
            $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $errors[] = '该手机号未注册';
            } elseif (!verifyCode($phone, $code, 'login')) {
                $errors[] = '验证码错误，请重新输入';
            } else {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 更新最后登录时间
                $db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?")
                   ->execute([$user['id']]);
                
                // 处理记住我
                if ($remember) {
                    $rememberToken = bin2hex(random_bytes(32));
                    $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?")
                       ->execute([$rememberToken, $user['id']]);
                    setcookie('remember_token', $rememberToken, time() + 7 * 24 * 60 * 60, '/');
                }
                
                // 记录日志
                logSystemAction($user['id'], 'login', '验证码登录成功');
                
                redirect('index.php');
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
    <title>用户登录 - 中职技能竞赛训练辅助系统</title>
    <link rel="stylesheet" href="assets/css/style.css.php">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">📚</div>
                <h1 class="auth-title">用户登录</h1>
                <p class="auth-subtitle">欢迎回到中职技能竞赛训练辅助系统</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo e($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <!-- 登录方式切换 -->
            <div class="tabs">
                <ul class="tab-list">
                    <li class="tab-item">
                        <a href="#" class="tab-link active" data-tab="password-login">密码登录</a>
                    </li>
                    <li class="tab-item">
                        <a href="#" class="tab-link" data-tab="code-login">验证码登录</a>
                    </li>
                </ul>
            </div>
            
            <!-- 密码登录表单 -->
            <form id="password-login" class="tab-content active" method="POST" action="">
                <input type="hidden" name="login_type" value="password">
                
                <div class="form-group">
                    <label class="form-label">
                        用户名或手机号
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="identifier" 
                        class="form-control" 
                        placeholder="请输入用户名或手机号"
                        value="<?php echo e($_POST['identifier'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        密码
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="请输入密码"
                        required
                    >
                    <div class="form-hint">密码不少于 8 位，建议包含大小写字母、数字和特殊符号</div>
                </div>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="remember" value="1">
                        <span>记住我（7 天）</span>
                    </label>
                    <a href="reset_password.php" style="color: var(--primary-color); text-decoration: none;">忘记密码？</a>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">登录</button>
                </div>
            </form>
            
            <!-- 验证码登录表单 -->
            <form id="code-login" class="tab-content" method="POST" action="">
                <input type="hidden" name="login_type" value="code">
                
                <div class="form-group">
                    <label class="form-label">
                        手机号
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="tel" 
                        name="phone" 
                        class="form-control" 
                        placeholder="请输入 11 位手机号"
                        maxlength="11"
                        value="<?php echo e($_POST['phone'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        验证码
                        <span class="required">*</span>
                    </label>
                    <div class="verification-input-group">
                        <input 
                            type="text" 
                            name="code" 
                            class="form-control" 
                            placeholder="请输入验证码"
                            maxlength="6"
                            required
                        >
                        <button type="button" class="btn btn-outline verification-btn" id="sendCodeBtn">
                            获取验证码
                        </button>
                    </div>
                    <div class="form-hint">验证码有效期为 5 分钟</div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="remember" value="1">
                        <span>记住我（7 天）</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">登录</button>
                </div>
            </form>
            
            <div class="auth-divider">
                <span>还没有账号？</span>
            </div>
            
            <div class="auth-footer">
                <a href="register.php">立即注册</a>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 13px; color: #666;">
                <strong>测试账号：</strong><br>
                管理员：admin / admin123456<br>
                学生：student1 / student123
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // 标签页切换
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // 移除所有激活状态
                document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // 添加激活状态
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // 发送验证码
        document.getElementById('sendCodeBtn')?.addEventListener('click', function() {
            const phoneInput = document.querySelector('#code-login input[name="phone"]');
            const phone = phoneInput.value.trim();
            
            if (!phone) {
                alert('请先输入手机号');
                phoneInput.focus();
                return;
            }
            
            if (!/^1[3-9]\d{9}$/.test(phone)) {
                alert('请输入有效的手机号');
                phoneInput.focus();
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.textContent = '发送中...';
            
            // 模拟发送验证码
            setTimeout(() => {
                alert('验证码已发送（模拟）：123456');
                let countdown = 60;
                const timer = setInterval(() => {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(timer);
                        btn.disabled = false;
                        btn.textContent = '获取验证码';
                    } else {
                        btn.textContent = `${countdown}秒后重试`;
                    }
                }, 1000);
            }, 1000);
        });
    </script>
</body>
</html>
