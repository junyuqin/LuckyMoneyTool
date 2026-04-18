<?php
/**
 * 公共头部文件
 * 包含导航栏和页面头部结构
 */

// 防止直接访问
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

// 获取当前页面名称
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// 检查用户是否登录
$is_logged_in = isset($_SESSION['user_id']);
$user_info = $is_logged_in ? get_user_info($_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>中职专业课程知识图谱系统</title>
    <link rel="stylesheet" href="/knowledge_graph_system/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>
<body>
    <!-- 顶部导航栏 -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <h1 class="system-title">
                    <a href="/knowledge_graph_system/index.php">
                        <span class="logo-icon">📚</span>
                        中职专业课程知识图谱系统
                    </a>
                </h1>
            </div>
            
            <nav class="main-nav">
                <?php if ($is_logged_in): ?>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/index.php" 
                               class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                                <span class="nav-icon">🏠</span>
                                <span class="nav-text">首页</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/pages/resources.php" 
                               class="nav-link <?php echo $current_page === 'resources' ? 'active' : ''; ?>">
                                <span class="nav-icon">📁</span>
                                <span class="nav-text">课程资源</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/pages/knowledge_map.php" 
                               class="nav-link <?php echo $current_page === 'knowledge_map' ? 'active' : ''; ?>">
                                <span class="nav-icon">🗺️</span>
                                <span class="nav-text">知识图谱</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/pages/learning_path.php" 
                               class="nav-link <?php echo $current_page === 'learning_path' ? 'active' : ''; ?>">
                                <span class="nav-icon">🎯</span>
                                <span class="nav-text">学习路径</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/pages/progress.php" 
                               class="nav-link <?php echo $current_page === 'progress' ? 'active' : ''; ?>">
                                <span class="nav-icon">📊</span>
                                <span class="nav-text">学习进度</span>
                            </a>
                        </li>
                        <?php if ($user_info && $user_info['role'] === 'teacher'): ?>
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/pages/evaluation.php" 
                               class="nav-link <?php echo $current_page === 'evaluation' ? 'active' : ''; ?>">
                                <span class="nav-icon">📝</span>
                                <span class="nav-text">教学评估</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="/knowledge_graph_system/pages/feedback.php" 
                               class="nav-link <?php echo $current_page === 'feedback' ? 'active' : ''; ?>">
                                <span class="nav-icon">💬</span>
                                <span class="nav-text">意见反馈</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="user-menu">
                        <span class="user-greeting">欢迎，<?php echo htmlspecialchars($user_info['username']); ?></span>
                        <a href="/knowledge_graph_system/pages/logout.php" class="btn btn-outline btn-sm">退出登录</a>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="/knowledge_graph_system/pages/login.php" class="btn btn-outline">登录</a>
                        <a href="/knowledge_graph_system/pages/register.php" class="btn btn-primary">注册</a>
                    </div>
                <?php endif; ?>
            </nav>
            
            <!-- 移动端菜单按钮 -->
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </header>
    
    <!-- 移动端侧边栏 -->
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="sidebar-header">
            <h3>菜单导航</h3>
            <button class="close-sidebar" onclick="toggleMobileMenu()">×</button>
        </div>
        <nav class="mobile-nav">
            <?php if ($is_logged_in): ?>
                <a href="/knowledge_graph_system/index.php" class="mobile-nav-link">首页</a>
                <a href="/knowledge_graph_system/pages/resources.php" class="mobile-nav-link">课程资源</a>
                <a href="/knowledge_graph_system/pages/knowledge_map.php" class="mobile-nav-link">知识图谱</a>
                <a href="/knowledge_graph_system/pages/learning_path.php" class="mobile-nav-link">学习路径</a>
                <a href="/knowledge_graph_system/pages/progress.php" class="mobile-nav-link">学习进度</a>
                <?php if ($user_info && $user_info['role'] === 'teacher'): ?>
                <a href="/knowledge_graph_system/pages/evaluation.php" class="mobile-nav-link">教学评估</a>
                <?php endif; ?>
                <a href="/knowledge_graph_system/pages/feedback.php" class="mobile-nav-link">意见反馈</a>
                <a href="/knowledge_graph_system/pages/logout.php" class="mobile-nav-link">退出登录</a>
            <?php else: ?>
                <a href="/knowledge_graph_system/pages/login.php" class="mobile-nav-link">登录</a>
                <a href="/knowledge_graph_system/pages/register.php" class="mobile-nav-link">注册</a>
            <?php endif; ?>
        </nav>
    </div>
    
    <!-- 主内容区域 -->
    <main class="main-content">
