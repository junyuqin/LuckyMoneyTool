<?php
/**
 * API: 获取评估报告详情
 */

// 定义访问许可
define('ACCESS_ALLOWED', true);

// 引入配置文件
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 设置 JSON 响应头
header('Content-Type: application/json; charset=utf-8');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

// 获取参数
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($evaluation_id <= 0) {
    echo json_encode(['success' => false, 'message' => '无效的评估 ID']);
    exit;
}

// 获取评估详情
$evaluation = get_evaluation_by_id($evaluation_id);

if (!$evaluation) {
    echo json_encode(['success' => false, 'message' => '评估报告不存在']);
    exit;
}

// 返回成功响应
echo json_encode([
    'success' => true,
    'data' => $evaluation
]);
?>
