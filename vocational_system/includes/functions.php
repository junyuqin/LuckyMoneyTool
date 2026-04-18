<?php
/**
 * 通用工具函数库
 * 中职学生技能成长档案系统
 */

// 验证手机号码格式（中国大陆）
function validatePhone($phone) {
    // 检查是否为11位数字
    if (!is_numeric($phone)) {
        return false;
    }
    
    // 检查长度是否为11位
    if (strlen($phone) !== 11) {
        return false;
    }
    
    // 检查是否符合中国大陆手机号格式（1开头，第二位为3-9）
    $pattern = '/^1[3-9]\d{9}$/';
    if (!preg_match($pattern, $phone)) {
        return false;
    }
    
    return true;
}

// 验证密码强度
function validatePasswordStrength($password) {
    $length = strlen($password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasDigit = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
    
    $strength = 0;
    
    // 长度评分
    if ($length >= 8) {
        $strength += 1;
    }
    if ($length >= 12) {
        $strength += 1;
    }
    if ($length >= 16) {
        $strength += 1;
    }
    
    // 字符类型评分
    if ($hasLower) {
        $strength += 1;
    }
    if ($hasUpper) {
        $strength += 1;
    }
    if ($hasDigit) {
        $strength += 1;
    }
    if ($hasSpecial) {
        $strength += 1;
    }
    
    // 返回强度等级
    if ($strength <= 2) {
        return ['valid' => false, 'level' => '弱', 'message' => '密码强度太弱，请增加长度和字符类型'];
    } elseif ($strength <= 4) {
        return ['valid' => true, 'level' => '中', 'message' => '密码强度中等，建议继续增强'];
    } else {
        return ['valid' => true, 'level' => '强', 'message' => '密码强度很好'];
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
function sendVerificationCode($phone, $code, $type = 'register') {
    // 在实际应用中，这里会调用短信API
    // 这里只是模拟，将验证码存入数据库
    $pdo = getDBConnection();
    
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    $stmt = $pdo->prepare("INSERT INTO verification_codes 
                          (phone, code, type, expires_at) 
                          VALUES (:phone, :code, :type, :expires_at)");
    
    $stmt->execute([
        ':phone' => $phone,
        ':code' => $code,
        ':type' => $type,
        ':expires_at' => $expiresAt
    ]);
    
    // 返回true表示发送成功（实际应该返回短信API的响应）
    return true;
}

// 验证验证码
function verifyCode($phone, $code, $type = 'register') {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM verification_codes 
                          WHERE phone = :phone 
                          AND code = :code 
                          AND type = :type 
                          AND is_used = 0 
                          AND expires_at > datetime('now')
                          ORDER BY created_at DESC 
                          LIMIT 1");
    
    $stmt->execute([
        ':phone' => $phone,
        ':code' => $code,
        ':type' => $type
    ]);
    
    $record = $stmt->fetch();
    
    if ($record) {
        // 标记验证码已使用
        $updateStmt = $pdo->prepare("UPDATE verification_codes 
                                    SET is_used = 1 
                                    WHERE id = :id");
        $updateStmt->execute([':id' => $record['id']]);
        
        return true;
    }
    
    return false;
}

// 检查手机号是否已注册
function isPhoneRegistered($phone) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE phone = :phone");
    $stmt->execute([':phone' => $phone]);
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
}

// 检查用户名是否存在
function isUsernameExists($username) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
}

// 创建用户
function createUser($username, $phone, $password, $role = 'student') {
    $pdo = getDBConnection();
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users 
                              (username, phone, password_hash, role) 
                              VALUES (:username, :phone, :password_hash, :role)");
        
        $stmt->execute([
            ':username' => $username,
            ':phone' => $phone,
            ':password_hash' => $passwordHash,
            ':role' => $role
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

// 用户登录验证
function authenticateUser($identifier, $password) {
    $pdo = getDBConnection();
    
    // 判断是用户名还是手机号
    $field = validatePhone($identifier) ? 'phone' : 'username';
    
    $stmt = $pdo->prepare("SELECT * FROM users 
                          WHERE ($field = :identifier OR phone = :identifier) 
                          AND is_active = 1");
    
    $stmt->execute([':identifier' => $identifier]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // 更新最后登录时间
        $updateStmt = $pdo->prepare("UPDATE users 
                                    SET last_login = datetime('now') 
                                    WHERE id = :id");
        $updateStmt->execute([':id' => $user['id']]);
        
        return $user;
    }
    
    return false;
}

// 通过手机号获取用户
function getUserByPhone($phone) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = :phone");
    $stmt->execute([':phone' => $phone]);
    
    return $stmt->fetch();
}

// 重置密码
function resetPassword($phone, $newPassword) {
    $pdo = getDBConnection();
    
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users 
                          SET password_hash = :password_hash, 
                              updated_at = datetime('now') 
                          WHERE phone = :phone");
    
    return $stmt->execute([
        ':password_hash' => $passwordHash,
        ':phone' => $phone
    ]);
}

// 创建会话（记住我功能）
function createSession($userId, $rememberMe = false) {
    $pdo = getDBConnection();
    
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = $rememberMe 
        ? date('Y-m-d H:i:s', strtotime('+7 days')) 
        : date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $pdo->prepare("INSERT INTO user_sessions 
                          (user_id, session_token, expires_at, ip_address, user_agent) 
                          VALUES (:user_id, :session_token, :expires_at, :ip_address, :user_agent)");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':session_token' => $sessionToken,
        ':expires_at' => $expiresAt,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent
    ]);
    
    // 设置cookie
    setcookie('session_token', $sessionToken, strtotime($expiresAt), '/');
    
    return $sessionToken;
}

// 验证会话
function validateSession() {
    if (!isset($_COOKIE['session_token'])) {
        return false;
    }
    
    $pdo = getDBConnection();
    $sessionToken = $_COOKIE['session_token'];
    
    $stmt = $pdo->prepare("SELECT us.*, u.* 
                          FROM user_sessions us 
                          JOIN users u ON us.user_id = u.id 
                          WHERE us.session_token = :token 
                          AND us.expires_at > datetime('now')");
    
    $stmt->execute([':token' => $sessionToken]);
    $session = $stmt->fetch();
    
    if ($session) {
        return $session;
    }
    
    return false;
}

// 销毁会话
function destroySession() {
    if (isset($_COOKIE['session_token'])) {
        $pdo = getDBConnection();
        $sessionToken = $_COOKIE['session_token'];
        
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = :token");
        $stmt->execute([':token' => $sessionToken]);
        
        setcookie('session_token', '', time() - 3600, '/');
    }
    
    session_destroy();
}

// 获取当前登录用户
function getCurrentUser() {
    // 首先检查session
    if (isset($_SESSION['user_id'])) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    // 然后检查cookie（记住我）
    $sessionUser = validateSession();
    if ($sessionUser) {
        return $sessionUser;
    }
    
    return null;
}

// 记录系统日志
function logSystemAction($userId, $actionType, $actionDescription) {
    $pdo = getDBConnection();
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $pdo->prepare("INSERT INTO system_logs 
                          (user_id, action_type, action_description, ip_address) 
                          VALUES (:user_id, :action_type, :action_description, :ip_address)");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':action_type' => $actionType,
        ':action_description' => $actionDescription,
        ':ip_address' => $ipAddress
    ]);
}

// 文件上传处理
function handleFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
    // 检查是否有上传文件
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => '没有选择文件'];
    }
    
    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败，错误代码：' . $file['error']];
    }
    
    // 检查文件大小
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制（最大5MB）'];
    }
    
    // 检查文件类型
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    // 生成唯一文件名
    $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = __DIR__ . '/../uploads/' . $newFileName;
    
    // 移动上传文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true, 
            'message' => '文件上传成功',
            'file_path' => 'uploads/' . $newFileName,
            'file_name' => $file['name'],
            'file_size' => $file['size']
        ];
    }
    
    return ['success' => false, 'message' => '文件保存失败'];
}

// 格式化日期时间
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) {
        return '';
    }
    return date($format, strtotime($datetime));
}

// 安全过滤输入
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// 生成随机用户名
function generateRandomUsername() {
    $prefixes = ['student', 'user', 'member'];
    $prefix = $prefixes[array_rand($prefixes)];
    $random = rand(10000, 99999);
    return $prefix . $random;
}

// 获取技能类别列表
function getSkillCategories() {
    return [
        '编程' => 'programming',
        '设计' => 'design',
        '沟通' => 'communication',
        '管理' => 'management'
    ];
}

// 获取评估等级列表
function getEvaluationLevels() {
    return [
        '优秀' => 90,
        '良好' => 80,
        '中等' => 70,
        '及格' => 60,
        '不及格' => 0
    ];
}

// 计算统计数据
function calculateStatistics($skillCategory = null, $timePeriod = 'last_month') {
    $pdo = getDBConnection();
    
    // 根据时间周期确定日期范围
    $dateRange = getDateRange($timePeriod);
    
    $whereClause = "WHERE created_at BETWEEN :start_date AND :end_date";
    if ($skillCategory) {
        $whereClause .= " AND skill_category = :skill_category";
    }
    
    $sql = "SELECT 
            COUNT(*) as total_records,
            AVG(score) as avg_score,
            COUNT(DISTINCT student_id) as total_students
            FROM skill_records
            $whereClause";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':start_date' => $dateRange['start'],
        ':end_date' => $dateRange['end']
    ];
    
    if ($skillCategory) {
        $params[':skill_category'] = $skillCategory;
    }
    
    $stmt->execute($params);
    
    return $stmt->fetch();
}

// 获取日期范围
function getDateRange($period) {
    $now = new DateTime();
    
    switch ($period) {
        case 'last_month':
            $start = new DateTime('first day of last month');
            $end = new DateTime('last day of last month');
            break;
        case 'last_3_months':
            $start = new DateTime('-3 months');
            $end = $now;
            break;
        case 'last_6_months':
            $start = new DateTime('-6 months');
            $end = $now;
            break;
        case 'last_year':
            $start = new DateTime('first day of January last year');
            $end = new DateTime('last day of December last year');
            break;
        default:
            $start = new DateTime('first day of last month');
            $end = new DateTime('last day of last month');
    }
    
    return [
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s')
    ];
}

// JSON响应
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
