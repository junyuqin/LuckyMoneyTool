<?php
/**
 * 登出页面
 * 中职学生技能成长档案系统
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 获取当前用户用于记录日志
$user = getCurrentUser();
if ($user) {
    logSystemAction($user['id'], 'logout', '用户登出成功');
}

// 销毁会话
destroySession();

// 跳转到登录页面
header('Location: login.php');
exit;
