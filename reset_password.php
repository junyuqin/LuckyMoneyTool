<?php
/**
 * 中职技能竞赛训练辅助系统 - 重置密码页面
 */
require_once __DIR__ . '/includes/config.php';

// 初始化数据库
checkAndInitializeDatabase();

session_start();

$errors = [];
$success = '';
$step = $_POST['step'] ?? $_GET['step'] ?? 1;
$formData = [];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // 第一步：验证手机号
        $formData['phone'] = trim($_POST['phone'] ?? '');
        
        if (empty($formData['phone'])) {
            $errors[] = '请输入手机号';
        } elseif (!validatePhone($formData['phone'])) {
            $errors[] = '请输入有效的 11 位手机号';
        } elseif (!isPhoneRegistered($formData['phone'])) {
            $errors[] = '该手机号未注册';
        } else {
            // 手机号验证通过，进入下一步
            $step = 2;
        }
    } elseif ($step == 2) {
        // 第二步：验证验证码
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['code'] = trim($_POST['code'] ?? '');
        
        if (empty($formData['phone']) || !validatePhone($formData['phone'])) {
            $errors[] = '手机号无效';
        } elseif (empty($formData['code'])) {
            $errors[] = '请输入验证码';
        } elseif (!verifyCode($formData['phone'], $formData['code'], 'reset_password')) {
            $errors[] = '验证码错误或已过期';
        } else {
            // 验证码验证通过，进入下一步
            $step = 3;
        }
    } elseif ($step == 3) {
        // 第三步：设置新密码
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['code'] = trim($_POST['code'] ?? '');
        $formData['password'] = $_POST['password'] ?? '';
        $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
        
        // 再次验证手机号和验证码
        if (empty($formData['phone']) || !validatePhone($formData['phone'])) {
            $errors[] = '手机号无效';
        } elseif (empty($formData['code'])) {
            $errors[] = '验证码无效';
        } elseif (!verifyCode($formData['phone'], $formData['code'], 'reset_password')) {
            $errors[] = '验证码已过期，请重新获取';
        }
        
        // 验证密码
        if (empty($formData['password'])) {
            $errors[] = '请输入新密码';
        } elseif (strlen($formData['password']) < 8) {
            $errors[] = '密码长度不能少于 8 位';
        } elseif (strpos($formData['password'], ' ') !== false) {
            $errors[] = '密码不能包含空格';
        }
        
        // 验证确认密码
        if (empty($formData['confirm_password'])) {
            $errors[] = '请再次输入密码';
        } elseif ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = '密码和确认密码不一致，请重新输入';
        }
        
        // 如果没有错误，更新密码
        if (empty($errors)) {
            $db = getDBConnection();
            
            try {
                // 查询用户
                $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$formData['phone']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // 更新密码
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET password_hash = ?, updated_at = datetime('now')
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        password_hash($formData['password'], PASSWORD_DEFAULT),
                        $user['id']
                    ]);
                    
                    // 记录日志
                    logSystemAction($user['id'], 'reset_password', '密码重置成功');
                    
                    $success = '密码重置成功！即将跳转到登录页面...';
                    
                    // 清空表单数据
                    $formData = [];
                    $step = 4;
                    
                    // 3 秒后跳转到登录页面
                    header('refresh:3;url=login.php');
                } else {
                    $errors[] = '用户不存在';
                }
            } catch (PDOException $e) {
                $errors[] = '密码重置失败，请稍后重试';
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
    <title>重置密码 - 中职技能竞赛训练辅助系统</title>
    <link rel="stylesheet" href="assets/css/style.css.php">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">🔐</div>
                <h1 class="auth-title">重置密码</h1>
                <p class="auth-subtitle">找回您的账户密码</p>
            </div>
            
            <!-- 进度指示器 -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 30px; position: relative;">
                <div style="position: absolute; top: 15px; left: 0; right: 0; height: 2px; background: #e0e0e0; z-index: 1;"></div>
                <div style="position: absolute; top: 15px; left: 0; height: 2px; background: var(--primary-color); z-index: 2; transition: width 0.3s;" 
                     id="progressBar" 
                     style="width: <?php echo ($step - 1) * 50; ?>%"></div>
                
                <?php 
                $steps = ['验证手机号', '验证验证码', '设置新密码', '完成'];
                for ($i = 1; $i <= 3; $i++): 
                ?>
                    <div style="position: relative; z-index: 3; text-align: center; background: #fff; padding: 0 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; 
                                    background: <?php echo $i <= $step ? 'var(--primary-color)' : '#e0e0e0'; ?>;
                                    color: <?php echo $i <= $step ? '#fff' : '#999'; ?>;
                                    display: flex; align-items: center; justify-content: center;
                                    margin: 0 auto 5px; font-weight: bold; font-size: 14px;">
                            <?php echo $i < $step ? '✓' : $i; ?>
                        </div>
                        <div style="font-size: 12px; color: <?php echo $i <= $step ? 'var(--primary-color)' : '#999'; ?>;">
                            <?php echo $steps[$i - 1]; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo e($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="step" value="<?php echo $step; ?>">
                
                <?php if ($step == 1 || $step == 2 || $step == 3): ?>
                    <input type="hidden" name="phone" value="<?php echo e($formData['phone'] ?? ''); ?>">
                <?php endif; ?>
                
                <?php if ($step == 2 || $step == 3): ?>
                    <input type="hidden" name="code" value="<?php echo e($formData['code'] ?? ''); ?>">
                <?php endif; ?>
                
                <!-- 步骤 1：验证手机号 -->
                <?php if ($step == 1): ?>
                    <div class="form-group">
                        <label class="form-label">
                            注册手机号
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            name="phone" 
                            class="form-control" 
                            placeholder="请输入注册时使用的手机号"
                            maxlength="11"
                            value="<?php echo e($formData['phone'] ?? ''); ?>"
                            required
                            pattern="^1[3-9]\d{9}$"
                            autofocus
                        >
                        <div class="form-hint">请输入您注册时使用的 11 位手机号</div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">下一步</button>
                    </div>
                
                <!-- 步骤 2：验证验证码 -->
                <?php elseif ($step == 2): ?>
                    <div class="alert alert-info">
                        我们已向手机号 <strong><?php echo e(substr_replace($formData['phone'], '****', 3, 4)); ?></strong> 
                        发送了验证码，请输入验证码以继续。
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
                                value="<?php echo e($formData['code'] ?? ''); ?>"
                                required
                                autofocus
                            >
                            <button type="button" class="btn btn-outline verification-btn" id="sendCodeBtn">
                                获取验证码
                            </button>
                        </div>
                        <div class="form-hint">验证码有效期为 5 分钟</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="btn-group" style="justify-content: space-between;">
                            <a href="?step=1" class="btn btn-outline">上一步</a>
                            <button type="submit" class="btn btn-primary">下一步</button>
                        </div>
                    </div>
                
                <!-- 步骤 3：设置新密码 -->
                <?php elseif ($step == 3): ?>
                    <div class="alert alert-success">
                        验证码验证通过！现在您可以设置新的密码了。
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            新密码
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-control" 
                            placeholder="请设置不少于 8 位的新密码"
                            minlength="8"
                            required
                            autofocus
                        >
                        
                        <!-- 密码强度指示器 -->
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">密码强度：未输入</div>
                        </div>
                        
                        <div class="form-hint">
                            建议包含大小写字母、数字及特殊符号，以提升安全性
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
                            placeholder="请再次输入新密码"
                            required
                        >
                        <div class="form-hint">请确保两次输入的密码一致</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="btn-group" style="justify-content: space-between;">
                            <a href="?step=2&phone=<?php echo urlencode($formData['phone']); ?>" class="btn btn-outline">上一步</a>
                            <button type="submit" class="btn btn-primary">提交</button>
                        </div>
                    </div>
                
                <!-- 步骤 4：完成 -->
                <?php elseif ($step == 4): ?>
                    <div style="text-align: center; padding: 30px 0;">
                        <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                        <h3 style="color: var(--secondary-color); margin-bottom: 15px;">密码重置成功！</h3>
                        <p style="color: #666; margin-bottom: 20px;">
                            您的密码已成功重置，可以使用新密码登录系统了。
                        </p>
                        <a href="login.php" class="btn btn-primary">返回登录</a>
                    </div>
                <?php endif; ?>
            </form>
            
            <?php if ($step < 4): ?>
                <div class="auth-footer" style="margin-top: 25px;">
                    <a href="login.php">返回登录</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // 更新进度条
        const step = <?php echo $step; ?>;
        const progressBar = document.getElementById('progressBar');
        if (progressBar && step <= 3) {
            progressBar.style.width = ((step - 1) * 50) + '%';
        }
        
        // 密码强度检测
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        if (passwordInput) {
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
        }
        
        // 确认密码验证
        const confirmPasswordInput = document.getElementById('confirmPassword');
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.classList.add('error');
                    this.classList.remove('success');
                } else {
                    this.classList.remove('error');
                    this.classList.add('success');
                }
            });
        }
        
        // 发送验证码
        document.getElementById('sendCodeBtn')?.addEventListener('click', function() {
            const phone = document.querySelector('input[name="phone"]').value;
            
            if (!phone) {
                alert('请先输入手机号');
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.textContent = '发送中...';
            
            // 模拟发送验证码
            setTimeout(() => {
                const mockCode = Math.floor(100000 + Math.random() * 900000);
                alert('验证码已发送（模拟）：' + mockCode);
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
    </script>
</body>
</html>
