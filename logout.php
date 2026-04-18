<?php
/**
 * 中职技能竞赛训练辅助系统 - 退出登录
 */
require_once __DIR__ . '/includes/config.php';

session_start();

// 清除会话数据
$_SESSION = array();

// 删除会话 cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// 删除记住我 cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    
    // 清除数据库中的 remember_token
    if (isset($_SESSION['user_id'])) {
        $db = getDBConnection();
        $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")
           ->execute([$_SESSION['user_id']]);
    }
}

// 销毁会话
session_destroy();

// 重定向到登录页面
redirect('login.php');
