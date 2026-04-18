<?php
/**
 * API: 添加到学习路径
 */

// 定义访问许可
define('ACCESS_ALLOWED', true);

// 引入配置文件
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 设置 JSON 响应头
header('Content-Type: application/json; charset=utf-8');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

// 启动会话
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 获取 JSON 数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['point_id'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要参数']);
    exit;
}

$user_id = $_SESSION['user_id'];
$point_id = (int)$data['point_id'];

// 获取知识点关联的资源
$resource = get_resource_by_knowledge_point($point_id);

if (!$resource) {
    // 如果没有关联资源，尝试创建一个虚拟资源
    $knowledge_point = get_knowledge_point_by_id($point_id);
    if (!$knowledge_point) {
        echo json_encode(['success' => false, 'message' => '知识点不存在']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => '已加入学习路径（暂无关联资源）',
        'point' => $knowledge_point
    ]);
    exit;
}

// 添加到学习路径
$position = get_user_learning_path_count($user_id) + 1;

if (add_learning_path_resource($user_id, $resource['id'], $position)) {
    echo json_encode([
        'success' => true,
        'message' => '已成功加入学习路径',
        'resource' => $resource
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '添加失败，请重试']);
}
?>
