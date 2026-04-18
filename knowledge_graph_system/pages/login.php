<?php
/**
 * 用户登录页面
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
$loginMethod = 'password'; // password 或 code

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginMethod = $_POST['login_method'] ?? 'password';
    
    if ($loginMethod === 'password') {
        // 用户名/手机号 + 密码登录
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        if (empty($identifier)) {
            $errors[] = "请输入用户名或手机号";
        } elseif (empty($password)) {
            $errors[] = "请输入密码";
        } else {
            $db = getDBConnection();
            
            // 查询用户
            $stmt = $db->prepare("
                SELECT id, username, phone, password, is_active, login_attempts, locked_until 
                FROM users 
                WHERE username = :identifier OR phone = :identifier
            ");
            $stmt->bindValue(':identifier', $identifier, SQLITE3_TEXT);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$user) {
                $errors[] = "该用户名或手机号未注册";
            } elseif ($user['is_active'] != 1) {
                $errors[] = "账户已被禁用，请联系管理员";
            } elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $waitTime = ceil((strtotime($user['locked_until']) - time()) / 60);
                $errors[] = "账户已被锁定，请{$waitTime}分钟后再试";
            } elseif (!verifyPassword($password, $user['password'])) {
                // 密码错误，增加登录尝试次数
                $newAttempts = $user['login_attempts'] + 1;
                
                if ($newAttempts >= 5) {
                    // 锁定账户30分钟
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    $updateStmt = $db->prepare("
                        UPDATE users 
                        SET login_attempts = :attempts, locked_until = :locked 
                        WHERE id = :id
                    ");
                    $updateStmt->bindValue(':attempts', $newAttempts, SQLITE3_INTEGER);
                    $updateStmt->bindValue(':locked', $lockedUntil, SQLITE3_TEXT);
                    $updateStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                    $updateStmt->execute();
                    
                    $errors[] = "密码错误次数过多，账户已被锁定30分钟";
                } else {
                    $updateStmt = $db->prepare("
                        UPDATE users 
                        SET login_attempts = :attempts 
                        WHERE id = :id
                    ");
                    $updateStmt->bindValue(':attempts', $newAttempts, SQLITE3_INTEGER);
                    $updateStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                    $updateStmt->execute();
                    
                    $remaining = 5 - $newAttempts;
                    $errors[] = "密码错误，请重新输入（剩余{$remaining}次尝试机会）";
                }
            } else {
                // 登录成功，重置尝试次数
                $resetStmt = $db->prepare("
                    UPDATE users 
                    SET login_attempts = 0, locked_until = NULL 
                    WHERE id = :id
                ");
                $resetStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $resetStmt->execute();
                
                // 创建会话
                createUserSession($user['id'], $rememberMe);
                
                // 记录日志
                logAction($user['id'], 'login', '用户登录成功');
                
                // 跳转到首页
                redirectTo('index.php');
            }
        }
        
    } elseif ($loginMethod === 'code') {
        // 手机号 + 验证码登录
        $phone = trim($_POST['phone'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $rememberMe = isset($_POST['remember_me']);
        
        if (empty($phone)) {
            $errors[] = "请输入手机号码";
        } elseif (!validatePhone($phone)) {
            $errors[] = "请输入有效的手机号码";
        } elseif (empty($code)) {
            $errors[] = "请输入验证码";
        } else {
            // 验证手机号是否已注册
            if (!isPhoneRegistered($phone)) {
                $errors[] = "该手机号未注册";
            } else {
                // 验证验证码
                $result = verifyCode($phone, $code, 'login');
                if ($result['valid']) {
                    // 获取用户信息
                    $db = getDBConnection();
                    $stmt = $db->prepare("
                        SELECT id, username, is_active 
                        FROM users 
                        WHERE phone = :phone
                    ");
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    $userResult = $stmt->execute();
                    $user = $userResult->fetchArray(SQLITE3_ASSOC);
                    
                    if ($user['is_active'] != 1) {
                        $errors[] = "账户已被禁用，请联系管理员";
                    } else {
                        // 创建会话
                        createUserSession($user['id'], $rememberMe);
                        
                        // 记录日志
                        logAction($user['id'], 'login', '验证码登录成功');
                        
                        // 跳转到首页
                        redirectTo('index.php');
                    }
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
    }
    
    // 处理获取验证码
    if (isset($_POST['get_code']) && !empty($_POST['phone'])) {
        $phone = trim($_POST['phone']);
        
        if (validatePhone($phone)) {
            if (isPhoneRegistered($phone)) {
                $code = sendVerificationCode($phone, 'login');
                $successMessage = "验证码已发送：" . $code . "（测试用，实际场景应通过短信发送）";
            } else {
                $errors[] = "该手机号未注册";
            }
        } else {
            $errors[] = "请输入有效的手机号码";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录 - 中职专业课程知识图谱系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .login-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
        }
        .login-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
        }
        .login-tab.active {
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
            margin-bottom: -2px;
            font-weight: 500;
        }
        .login-tab:hover {
            color: #4CAF50;
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
        .form-group input[type="text"],
        .form-group input[type="password"] {
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
        .btn-code {
            width: auto;
            padding: 10px 20px;
            background: #2196F3;
        }
        .btn-code:hover {
            background: #1976D2;
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
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .remember-me {
            display: flex;
            align-items: center;
        }
        .remember-me input {
            margin-right: 8px;
        }
        .forgot-password a {
            color: #4CAF50;
            text-decoration: none;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .register-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .input-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .login-form {
            display: none;
        }
        .login-form.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>用户登录</h1>
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
        
        <div class="login-tabs">
            <div class="login-tab active" onclick="switchLoginTab('password')">
                密码登录
            </div>
            <div class="login-tab" onclick="switchLoginTab('code')">
                验证码登录
            </div>
        </div>
        
        <!-- 密码登录表单 -->
        <form method="POST" action="" class="login-form active" id="passwordForm">
            <input type="hidden" name="login_method" value="password">
            
            <div class="form-group">
                <label for="identifier">用户名或手机号</label>
                <input type="text" id="identifier" name="identifier" required 
                       placeholder="请输入用户名或手机号"
                       value="<?php echo isset($_POST['identifier']) ? escapeHtml($_POST['identifier']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required 
                       placeholder="请输入密码"
                       minlength="8">
                <div class="input-hint">
                    密码不少于8位，建议包含大小写字母、数字和特殊符号
                </div>
            </div>
            
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember_me_pwd" name="remember_me" value="1">
                    <label for="remember_me_pwd" style="display: inline; margin: 0; cursor: pointer;">记住我</label>
                </div>
                <div class="forgot-password">
                    <a href="reset_password.php">忘记密码？</a>
                </div>
            </div>
            
            <button type="submit" class="btn">登录</button>
        </form>
        
        <!-- 验证码登录表单 -->
        <form method="POST" action="" class="login-form" id="codeForm">
            <input type="hidden" name="login_method" value="code">
            
            <div class="form-group">
                <label for="phone">手机号码</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="phone" name="phone" required 
                           placeholder="请输入已注册的手机号"
                           pattern="1[3-9]\d{9}"
                           style="flex: 1;"
                           value="<?php echo isset($_POST['phone']) ? escapeHtml($_POST['phone']) : ''; ?>">
                    <button type="submit" name="get_code" class="btn btn-code">
                        获取验证码
                    </button>
                </div>
                <div class="input-hint">
                    系统将发送6位短信验证码，有效期5分钟
                </div>
            </div>
            
            <div class="form-group">
                <label for="code">验证码</label>
                <input type="text" id="code" name="code" required 
                       placeholder="请输入6位验证码"
                       maxlength="6"
                       pattern="\d{6}">
            </div>
            
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember_me_code" name="remember_me" value="1">
                    <label for="remember_me_code" style="display: inline; margin: 0; cursor: pointer;">记住我（7天免登录）</label>
                </div>
            </div>
            
            <button type="submit" class="btn">登录</button>
        </form>
        
        <div class="register-link">
            还没有账号？<a href="register.php">立即注册</a>
        </div>
    </div>
    
    <script>
        function switchLoginTab(method) {
            // 更新标签页状态
            document.querySelectorAll('.login-tab').forEach(function(tab, index) {
                if ((method === 'password' && index === 0) || 
                    (method === 'code' && index === 1)) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            // 切换表单显示
            document.querySelectorAll('.login-form').forEach(function(form) {
                form.classList.remove('active');
            });
            
            if (method === 'password') {
                document.getElementById('passwordForm').classList.add('active');
            } else {
                document.getElementById('codeForm').classList.add('active');
            }
        }
    </script>
</body>
</html>
