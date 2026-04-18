<?php
/**
 * 发送验证码 API
 * 中职学生技能成长档案系统
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
    exit;
}

$phone = sanitizeInput($_POST['phone'] ?? '');
$type = sanitizeInput($_POST['type'] ?? 'register');

// 验证手机号
if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => '请输入手机号']);
    exit;
}

if (!validatePhone($phone)) {
    echo json_encode(['success' => false, 'message' => '请输入有效的 11 位手机号码']);
    exit;
}

// 根据类型进行不同验证
if ($type === 'register') {
    if (isPhoneRegistered($phone)) {
        echo json_encode(['success' => false, 'message' => '该手机号已被注册']);
        exit;
    }
} elseif ($type === 'login' || $type === 'reset_password') {
    if (!isPhoneRegistered($phone)) {
        echo json_encode(['success' => false, 'message' => '该手机号未注册']);
        exit;
    }
}

// 检查最近是否已发送验证码（60 秒内）
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM verification_codes 
                      WHERE phone = :phone 
                      AND type = :type 
                      AND created_at > datetime('now', '-60 seconds')");
$stmt->execute([
    ':phone' => $phone,
    ':type' => $type
]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo json_encode(['success' => false, 'message' => '验证码已发送，请 60 秒后再试']);
    exit;
}

// 生成验证码
$code = generateVerificationCode(6);

// 发送验证码
if (sendVerificationCode($phone, $code, $type)) {
    // 在实际应用中，这里会返回成功
    // 为了开发测试，我们在响应中返回验证码
    echo json_encode([
        'success' => true, 
        'message' => '验证码发送成功',
        'test_code' => $code  // 开发测试用，生产环境应移除
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '验证码发送失败，请稍后重试']);
}
