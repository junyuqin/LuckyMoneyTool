<?php
/**
 * 用户注册页面
 * 中职专业课程知识图谱系统
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// 如果已登录，跳转到首页
if (isLoggedIn()) {
    redirectTo('index.php');
}

$errors = [];
$successMessage = '';
$step = 1; // 1: 输入手机号, 2: 输入验证码, 3: 设置密码

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_code') {
        // 第一步：发送验证码
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            $errors[] = "请输入手机号码";
        } elseif (!validatePhone($phone)) {
            $errors[] = "请输入有效的中国大陆手机号码";
        } elseif (isPhoneRegistered($phone)) {
            $errors[] = "该手机号已被注册";
        } else {
            // 发送验证码
            $code = sendVerificationCode($phone, 'register');
            $successMessage = "验证码已发送：" . $code . "（测试用，实际场景应通过短信发送）";
            $step = 2;
            $_SESSION['register_phone'] = $phone;
        }
    } elseif ($action === 'verify_code') {
        // 第二步：验证验证码
        $phone = $_SESSION['register_phone'] ?? '';
        $code = trim($_POST['code'] ?? '');
        
        if (empty($phone)) {
            $errors[] = "请先输入手机号码";
            $step = 1;
        } elseif (empty($code)) {
            $errors[] = "请输入验证码";
        } else {
            $result = verifyCode($phone, $code, 'register');
            if ($result['valid']) {
                $_SESSION['register_verified'] = true;
                $step = 3;
            } else {
                $errors[] = $result['message'];
            }
        }
    } elseif ($action === 'complete_register') {
        // 第三步：完成注册
        $phone = $_SESSION['register_phone'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // 验证
        if (empty($phone) || !($_SESSION['register_verified'] ?? false)) {
            $errors[] = "请先完成手机验证";
            $step = 1;
        } elseif (empty($username)) {
            $errors[] = "请输入用户名";
        } elseif (isUsernameExists($username)) {
            $errors[] = "该用户名已被使用";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "用户名长度应在3-50个字符之间";
        } elseif (empty($password)) {
            $errors[] = "请输入密码";
        } else {
            // 验证密码
            $pwdValidation = validatePassword($password);
            if (!$pwdValidation['valid']) {
                $errors = array_merge($errors, $pwdValidation['errors']);
            }
            
            // 检查密码是否与手机号或验证码相同
            if ($password === $phone) {
                $errors[] = "密码不能与手机号相同";
            }
            
            // 检查两次密码是否一致
            if ($password !== $confirmPassword) {
                $errors[] = "两次输入的密码不一致";
            }
            
            if (empty($errors)) {
                // 创建用户
                $db = getDBConnection();
                $stmt = $db->prepare("
                    INSERT INTO users (username, phone, password, created_at, updated_at) 
                    VALUES (:username, :phone, :password, datetime('now'), datetime('now'))
                ");
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                $stmt->bindValue(':password', hashPassword($password), SQLITE3_TEXT);
                
                if ($stmt->execute()) {
                    $successMessage = "注册成功！即将跳转到登录页面...";
                    
                    // 清理会话
                    unset($_SESSION['register_phone']);
                    unset($_SESSION['register_verified']);
                    
                    // 延迟跳转
                    header("Refresh: 2; url=login.php");
                } else {
                    $errors[] = "注册失败，请稍后重试";
                }
            }
        }
    }
}

// 获取当前步骤的手机号
$currentPhone = $_SESSION['register_phone'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - 中职专业课程知识图谱系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .register-header p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .error-message {
            background: #fee;
            color: #c00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
        }
        .success-message {
            background: #efe;
            color: #080;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #080;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f0f0f0;
            margin: 0 5px;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        .step.active {
            background: #4CAF50;
            color: white;
        }
        .step.completed {
            background: #4CAF50;
            color: white;
        }
        .password-strength {
            margin-top: 8px;
            padding: 8px;
            border-radius: 5px;
            font-size: 12px;
        }
        .input-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>用户注册</h1>
            <p>中职专业课程知识图谱系统</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escapeHtml($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo escapeHtml($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                1. 验证手机号
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                2. 输入验证码
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                3. 设置密码
            </div>
        </div>
        
        <form method="POST" action="">
            <?php if ($step === 1): ?>
                <div class="form-group">
                    <label for="phone">手机号码</label>
                    <input type="text" id="phone" name="phone" required 
                           placeholder="请输入11位手机号码" 
                           pattern="1[3-9]\d{9}"
                           value="<?php echo escapeHtml($currentPhone); ?>">
                    <div class="input-hint">
                        请输入有效的中国大陆手机号码，系统将发送验证码进行验证
                    </div>
                </div>
                <button type="submit" name="action" value="send_code" class="btn">
                    获取验证码
                </button>
                
            <?php elseif ($step === 2): ?>
                <div class="form-group">
                    <label for="code">验证码</label>
                    <input type="text" id="code" name="code" required 
                           placeholder="请输入6位验证码"
                           maxlength="6"
                           pattern="\d{6}">
                    <div class="input-hint">
                        验证码已发送至 <?php echo escapeHtml($currentPhone); ?>，有效期5分钟
                    </div>
                </div>
                <button type="submit" name="action" value="verify_code" class="btn">
                    验证并继续
                </button>
                <button type="submit" name="action" value="send_code" class="btn btn-secondary" style="margin-top: 10px;">
                    重新获取验证码
                </button>
                
            <?php elseif ($step === 3): ?>
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="请设置用户名（3-50个字符）"
                           minlength="3"
                           maxlength="50">
                    <div class="input-hint">
                        用户名将作为您的唯一标识，用于登录系统
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="请设置密码（至少8位）"
                           minlength="8"
                           oninput="checkPasswordStrength(this.value)">
                    <div class="input-hint">
                        密码不能少于8位，建议包含大小写字母、数字及特殊符号
                    </div>
                    <div id="passwordStrength" class="password-strength" style="display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="请再次输入密码"
                           oninput="checkPasswordMatch()">
                    <div class="input-hint">
                        请确保两次输入的密码一致
                    </div>
                    <div id="passwordMatch" style="display: none; margin-top: 5px; font-size: 12px;"></div>
                </div>
                
                <input type="hidden" name="action" value="complete_register">
                <button type="submit" class="btn">完成注册</button>
            <?php endif; ?>
        </form>
        
        <div class="back-link">
            <a href="login.php">已有账号？立即登录</a>
        </div>
    </div>
    
    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            if (!password) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 8) strength += 20;
            if (password.length >= 12) strength += 10;
            if (/[a-z]/.test(password)) strength += 15;
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 25;
            
            let level, color;
            if (strength >= 80) {
                level = '强';
                color = '#28a745';
            } else if (strength >= 50) {
                level = '中';
                color = '#ffc107';
            } else {
                level = '弱';
                color = '#dc3545';
            }
            
            strengthDiv.style.display = 'block';
            strengthDiv.style.background = color + '22';
            strengthDiv.style.color = color;
            strengthDiv.style.border = '1px solid ' + color;
            strengthDiv.textContent = '密码强度：' + level;
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!confirmPassword) {
                matchDiv.style.display = 'none';
                return;
            }
            
            matchDiv.style.display = 'block';
            if (password === confirmPassword) {
                matchDiv.style.color = '#28a745';
                matchDiv.textContent = '✓ 密码一致';
            } else {
                matchDiv.style.color = '#dc3545';
                matchDiv.textContent = '✗ 密码不一致';
            }
        }
    </script>
</body>
</html>
