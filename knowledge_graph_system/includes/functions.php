<?php
/**
 * 通用函数库
 * 中职专业课程知识图谱系统
 */

// 启动会话
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 检查用户是否已登录
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

// 获取当前登录用户ID
function getCurrentUserId() {
    startSession();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
}

// 获取当前登录用户信息
function getCurrentUserInfo() {
    startSession();
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDBConnection();
    $userId = getCurrentUserId();
    
    $stmt = $db->prepare("
        SELECT id, username, phone, created_at 
        FROM users 
        WHERE id = :user_id AND is_active = 1
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    return $result->fetchArray(SQLITE3_ASSOC);
}

// 验证手机号格式
function validatePhone($phone) {
    // 中国大陆手机号格式：1开头，第二位为3-9，共11位数字
    $pattern = '/^1[3-9]\d{9}$/';
    return preg_match($pattern, $phone) === 1;
}

// 验证密码强度
function validatePassword($password) {
    $errors = [];
    
    // 检查长度
    if (strlen($password) < 8) {
        $errors[] = "密码长度不能少于8位";
    }
    
    // 检查是否包含空格
    if (strpos($password, ' ') !== false) {
        $errors[] = "密码不能包含空格";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// 计算密码强度等级
function calculatePasswordStrength($password) {
    $strength = 0;
    
    // 长度评分
    if (strlen($password) >= 8) {
        $strength += 20;
    }
    if (strlen($password) >= 12) {
        $strength += 10;
    }
    
    // 字符类型评分
    if (preg_match('/[a-z]/', $password)) {
        $strength += 15;
    }
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 15;
    }
    if (preg_match('/[0-9]/', $password)) {
        $strength += 15;
    }
    if (preg_match('/[^a-zA-Z0-9]/', $password)) {
        $strength += 25;
    }
    
    // 返回强度等级
    if ($strength >= 80) {
        return ['level' => '强', 'color' => '#28a745'];
    } elseif ($strength >= 50) {
        return ['level' => '中', 'color' => '#ffc107'];
    } else {
        return ['level' => '弱', 'color' => '#dc3545'];
    }
}

// 生成验证码
function generateVerificationCode($length = 6) {
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= rand(0, 9);
    }
    return $code;
}

// 发送验证码（模拟）
function sendVerificationCode($phone, $type = 'register') {
    $db = getDBConnection();
    
    // 生成验证码
    $code = generateVerificationCode(6);
    
    // 设置过期时间（5分钟）
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // 使旧验证码失效
    $stmt = $db->prepare("
        UPDATE verifications 
        SET used = 1 
        WHERE phone = :phone AND type = :type AND used = 0
    ");
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $stmt->execute();
    
    // 插入新验证码
    $stmt = $db->prepare("
        INSERT INTO verifications (phone, code, type, expires_at) 
        VALUES (:phone, :code, :type, :expires_at)
    ");
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':code', $code, SQLITE3_TEXT);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $stmt->bindValue(':expires_at', $expiresAt, SQLITE3_TEXT);
    $stmt->execute();
    
    // 在实际应用中，这里应该调用短信服务API
    // 此处仅返回验证码用于测试
    return $code;
}

// 验证验证码
function verifyCode($phone, $code, $type = 'register') {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT id, expires_at, used 
        FROM verifications 
        WHERE phone = :phone AND code = :code AND type = :type
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':code', $code, SQLITE3_TEXT);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $verification = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$verification) {
        return ['valid' => false, 'message' => '验证码不存在'];
    }
    
    if ($verification['used'] == 1) {
        return ['valid' => false, 'message' => '验证码已被使用'];
    }
    
    if (strtotime($verification['expires_at']) < time()) {
        return ['valid' => false, 'message' => '验证码已过期'];
    }
    
    // 标记验证码为已使用
    $stmt = $db->prepare("
        UPDATE verifications 
        SET used = 1 
        WHERE id = :id
    ");
    $stmt->bindValue(':id', $verification['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    return ['valid' => true, 'message' => '验证成功'];
}

// 加密密码
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证密码
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 检查手机号是否已注册
function isPhoneRegistered($phone) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE phone = :phone
    ");
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    return $row['count'] > 0;
}

// 检查用户名是否存在
function isUsernameExists($username) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE username = :username
    ");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    return $row['count'] > 0;
}

// 创建用户会话
function createUserSession($userId, $rememberMe = false) {
    startSession();
    
    $db = getDBConnection();
    
    // 生成会话令牌
    $sessionToken = bin2hex(random_bytes(32));
    
    // 设置过期时间
    if ($rememberMe) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $_SESSION['remember_me'] = true;
    } else {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $_SESSION['remember_me'] = false;
    }
    
    // 保存会话到数据库
    $stmt = $db->prepare("
        INSERT INTO sessions (user_id, session_token, expires_at, ip_address, user_agent) 
        VALUES (:user_id, :token, :expires_at, :ip, :ua)
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':token', $sessionToken, SQLITE3_TEXT);
    $stmt->bindValue(':expires_at', $expiresAt, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '', SQLITE3_TEXT);
    $stmt->execute();
    
    // 设置会话变量
    $_SESSION['user_id'] = $userId;
    $_SESSION['session_token'] = $sessionToken;
    $_SESSION['expires_at'] = $expiresAt;
    
    // 设置记住我的cookie
    if ($rememberMe) {
        setcookie('session_token', $sessionToken, time() + (7 * 24 * 60 * 60), '/');
    }
    
    return $sessionToken;
}

// 销毁用户会话
function destroySession() {
    startSession();
    
    // 删除数据库中的会话
    if (isset($_SESSION['session_token'])) {
        $db = getDBConnection();
        $stmt = $db->prepare("
            DELETE FROM sessions 
            WHERE session_token = :token
        ");
        $stmt->bindValue(':token', $_SESSION['session_token'], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // 删除cookie
    if (isset($_COOKIE['session_token'])) {
        setcookie('session_token', '', time() - 3600, '/');
    }
    
    // 销毁会话
    session_unset();
    session_destroy();
}

// 清理过期的会话
function cleanupExpiredSessions() {
    $db = getDBConnection();
    
    $db->exec("
        DELETE FROM sessions 
        WHERE expires_at < datetime('now')
    ");
    
    $db->exec("
        DELETE FROM verifications 
        WHERE expires_at < datetime('now')
    ");
}

// 处理文件上传
function handleFileUpload($file, $allowedTypes = [], $maxSize = 10485760) {
    $result = [
        'success' => false,
        'message' => '',
        'file_path' => ''
    ];
    
    // 检查是否有文件上传
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = '文件上传失败';
        return $result;
    }
    
    // 检查文件大小
    if ($file['size'] > $maxSize) {
        $result['message'] = '文件大小超过限制';
        return $result;
    }
    
    // 检查文件类型
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($fileExtension, $allowedTypes)) {
        $result['message'] = '不支持的文件类型';
        return $result;
    }
    
    // 生成唯一的文件名
    $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    
    // 确定上传目录
    $uploadDir = __DIR__ . '/../uploads/';
    if (in_array($fileExtension, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'])) {
        $uploadDir .= 'documents/';
    } elseif (in_array($fileExtension, ['mp4', 'avi', 'mov', 'wmv'])) {
        $uploadDir .= 'videos/';
    }
    
    // 确保目录存在
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 移动文件
    $destination = $uploadDir . $newFileName;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $result['success'] = true;
        $result['message'] = '文件上传成功';
        $result['file_path'] = 'uploads/' . basename($uploadDir) . '/' . $newFileName;
    } else {
        $result['message'] = '文件移动失败';
    }
    
    return $result;
}

// 格式化文件大小
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $index = 0;
    
    while ($bytes >= 1024 && $index < count($units) - 1) {
        $bytes /= 1024;
        $index++;
    }
    
    return round($bytes, 2) . ' ' . $units[$index];
}

// 转义HTML特殊字符
function escapeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 重定向到指定页面
function redirectTo($url) {
    header("Location: " . $url);
    exit;
}

// 获取相对路径
function getRelativePath($absolutePath) {
    $baseDir = __DIR__ . '/../';
    return str_replace($baseDir, '', $absolutePath);
}

// 记录操作日志
function logAction($userId, $action, $details = '') {
    // 可以扩展为写入日志文件
    $logFile = __DIR__ . '/../data/action_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] User ID: $userId | Action: $action | Details: $details\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// 分页处理
function paginate($totalItems, $itemsPerPage = 10, $currentPage = 1) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// 生成CSRF令牌
function generateCsrfToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function validateCsrfToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 初始化或清理
cleanupExpiredSessions();
