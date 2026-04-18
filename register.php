<?php
/**
 * 中职技能竞赛训练辅助系统 - 用户注册页面
 */
require_once __DIR__ . '/includes/config.php';

// 初始化数据库
checkAndInitializeDatabase();

session_start();

$errors = [];
$success = '';
$formData = [];

// 处理注册表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'phone' => trim($_POST['phone'] ?? ''),
        'code' => trim($_POST['code'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // 验证手机号
    if (empty($formData['phone'])) {
        $errors[] = '请输入手机号';
    } elseif (!validatePhone($formData['phone'])) {
        $errors[] = '请输入有效的 11 位手机号';
    } elseif (isPhoneRegistered($formData['phone'])) {
        $errors[] = '该手机号已被注册';
    }
    
    // 验证验证码
    if (empty($formData['code'])) {
        $errors[] = '请输入验证码';
    } elseif (!verifyCode($formData['phone'], $formData['code'], 'register')) {
        $errors[] = '验证码错误或已过期';
    }
    
    // 验证密码
    if (empty($formData['password'])) {
        $errors[] = '请输入密码';
    } elseif (strlen($formData['password']) < 8) {
        $errors[] = '密码长度不能少于 8 位';
    } elseif (strpos($formData['password'], ' ') !== false) {
        $errors[] = '密码不能包含空格';
    }
    
    // 检查密码是否与手机号或验证码相同
    if (!empty($formData['password'])) {
        if ($formData['password'] === $formData['phone']) {
            $errors[] = '密码不能与手机号相同';
        }
        if ($formData['password'] === $formData['code']) {
            $errors[] = '密码不能与验证码相同';
        }
    }
    
    // 验证确认密码
    if (empty($formData['confirm_password'])) {
        $errors[] = '请再次输入密码';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = '两次输入的密码不一致';
    }
    
    // 如果没有错误，执行注册
    if (empty($errors)) {
        $db = getDBConnection();
        
        try {
            // 生成用户名（默认为手机号）
            $username = 'user_' . substr($formData['phone'], -4);
            
            // 插入新用户
            $stmt = $db->prepare("
                INSERT INTO users (username, phone, password_hash, role)
                VALUES (:username, :phone, :password_hash, 'student')
            ");
            $stmt->execute([
                ':username' => $username,
                ':phone' => $formData['phone'],
                ':password_hash' => password_hash($formData['password'], PASSWORD_DEFAULT)
            ]);
            
            $userId = $db->lastInsertId();
            
            // 初始化学习进度
            $subjects = ['math', 'english', 'programming'];
            foreach ($subjects as $subject) {
                $db->prepare("
                    INSERT INTO learning_progress (user_id, subject)
                    VALUES (?, ?)
                ")->execute([$userId, $subject]);
            }
            
            // 记录日志
            logSystemAction($userId, 'register', '用户注册成功');
            
            $success = '注册成功！即将跳转到登录页面...';
            
            // 清空表单数据
            $formData = [];
            
            // 3 秒后跳转到登录页面
            header('refresh:3;url=login.php');
            
        } catch (PDOException $e) {
            $errors[] = '注册失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - 中职技能竞赛训练辅助系统</title>
    <link rel="stylesheet" href="assets/css/style.css.php">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">📝</div>
                <h1 class="auth-title">用户注册</h1>
                <p class="auth-subtitle">创建您的中职技能竞赛训练辅助系统账号</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo e($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label class="form-label">
                        手机号
                        <span class="required">*</span>
                    </label>
                    <div class="verification-input-group">
                        <input 
                            type="tel" 
                            name="phone" 
                            id="phone"
                            class="form-control" 
                            placeholder="请输入 11 位手机号"
                            maxlength="11"
                            value="<?php echo e($formData['phone'] ?? ''); ?>"
                            required
                            pattern="^1[3-9]\d{9}$"
                        >
                        <button type="button" class="btn btn-outline verification-btn" id="sendCodeBtn">
                            获取验证码
                        </button>
                    </div>
                    <div class="form-hint">系统将向该手机号发送一次性验证码</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        验证码
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="code" 
                        class="form-control" 
                        placeholder="请输入收到的验证码"
                        maxlength="6"
                        value="<?php echo e($formData['code'] ?? ''); ?>"
                        required
                    >
                    <div class="form-hint">验证码有效期为 5 分钟</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        设置密码
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="form-control" 
                        placeholder="请设置不少于 8 位的密码"
                        minlength="8"
                        required
                    >
                    
                    <!-- 密码强度指示器 -->
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText">密码强度：未输入</div>
                    </div>
                    
                    <div class="form-hint">
                        建议包含大小写字母、数字及特殊符号<br>
                        密码不能与手机号或验证码相同，且不能包含空格
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        确认密码
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        id="confirmPassword"
                        class="form-control" 
                        placeholder="请再次输入密码"
                        required
                    >
                    <div class="form-hint">请确保两次输入的密码一致</div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 13px;">
                        <input type="checkbox" required style="margin-top: 3px;">
                        <span>我已阅读并同意《用户服务协议》和《隐私政策》，承诺提供的信息真实有效</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">立即注册</button>
                </div>
            </form>
            
            <div class="auth-divider">
                <span>已有账号？</span>
            </div>
            
            <div class="auth-footer">
                <a href="login.php">立即登录</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // 密码强度检测
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let level = '弱';
            let levelClass = 'weak';
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength >= 5) {
                level = '强';
                levelClass = 'strong';
            } else if (strength >= 3) {
                level = '中';
                levelClass = 'medium';
            }
            
            strengthFill.className = 'strength-fill ' + levelClass;
            strengthText.textContent = '密码强度：' + level;
            strengthText.style.color = levelClass === 'strong' ? '#2ecc71' : 
                                       levelClass === 'medium' ? '#f39c12' : '#e74c3c';
        });
        
        // 确认密码验证
        const confirmPasswordInput = document.getElementById('confirmPassword');
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.classList.add('error');
                this.classList.remove('success');
            } else {
                this.classList.remove('error');
                this.classList.add('success');
            }
        });
        
        // 发送验证码
        document.getElementById('sendCodeBtn').addEventListener('click', function() {
            const phoneInput = document.getElementById('phone');
            const phone = phoneInput.value.trim();
            
            if (!phone) {
                alert('请先输入手机号');
                phoneInput.focus();
                return;
            }
            
            if (!/^1[3-9]\d{9}$/.test(phone)) {
                alert('请输入有效的 11 位手机号');
                phoneInput.focus();
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.textContent = '发送中...';
            
            // 模拟发送验证码
            setTimeout(() => {
                // 在实际应用中，这里会调用后端 API 发送验证码
                const mockCode = Math.floor(100000 + Math.random() * 900000);
                alert('验证码已发送（模拟）：' + mockCode + '\n请在 5 分钟内输入');
                
                // 填充测试验证码（开发环境）
                document.querySelector('input[name="code"]').value = mockCode;
                
                let countdown = 60;
                const timer = setInterval(() => {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(timer);
                        btn.disabled = false;
                        btn.textContent = '获取验证码';
                    } else {
                        btn.textContent = countdown + '秒后重试';
                    }
                }, 1000);
            }, 1000);
        });
        
        // 表单提交验证
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('两次输入的密码不一致，请重新输入');
                confirmPassword.focus();
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('密码长度不能少于 8 位');
                password.focus();
                return false;
            }
            
            if (password.includes(' ')) {
                e.preventDefault();
                alert('密码不能包含空格');
                password.focus();
                return false;
            }
        });
        
        // 手机号格式化
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
