<?php
/**
 * 用户登出页面
 */

// 定义访问许可
define('ACCESS_ALLOWED', true);

// 启动会话
session_start();

// 销毁会话
session_unset();
session_destroy();

// 删除会话 cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// 重定向到首页
header('Location: ../index.php?logout=success');
exit;
?>
