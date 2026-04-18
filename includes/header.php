<?php
/**
 * 中职技能竞赛训练辅助系统 - 公共头部文件
 * 包含导航栏和通用 HTML 结构
 */

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 引入配置文件
require_once __DIR__ . '/config.php';

// 获取当前用户信息
$current_user = null;
$is_logged_in = false;

if (isset($_SESSION['user_id'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, username, phone, role, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    $is_logged_in = ($current_user !== false);
    if ($current_user === false) {
        $current_user = null;
        $is_logged_in = false;
    }
}

// 获取当前页面名称
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// 导航菜单项
$nav_items = [
    ['id' => 'index', 'name' => '首页', 'icon' => '🏠', 'url' => 'index.php'],
    ['id' => 'exam', 'name' => '在线考试', 'icon' => '📝', 'url' => 'exam.php'],
    ['id' => 'score_analysis', 'name' => '成绩分析', 'icon' => '📊', 'url' => 'score_analysis.php'],
    ['id' => 'learning_progress', 'name' => '学习进度', 'icon' => '📈', 'url' => 'learning_progress.php'],
    ['id' => 'resources', 'name' => '资源下载', 'icon' => '📚', 'url' => 'resources.php'],
    ['id' => 'feedback', 'name' => '反馈建议', 'icon' => '💬', 'url' => 'feedback.php'],
];

// 管理员专属菜单
$admin_nav_items = [
    ['id' => 'question_manage', 'name' => '题库管理', 'icon' => '📋', 'url' => 'question_manage.php'],
    ['id' => 'user_manage', 'name' => '用户管理', 'icon' => '👥', 'url' => 'user_manage.php'],
    ['id' => 'exam_manage', 'name' => '考试管理', 'icon' => '📑', 'url' => 'exam_manage.php'],
];

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>中职技能竞赛训练辅助系统</title>
    <link rel="stylesheet" href="assets/css/style.css.php">
    <style>
        /* 内联样式用于导航栏 */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: bold;
            transition: opacity 0.3s ease;
        }
        
        .navbar-brand:hover {
            opacity: 0.9;
        }
        
        .navbar-brand-icon {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .navbar-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
        }
        
        .navbar-item {
            position: relative;
        }
        
        .navbar-link {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .navbar-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .navbar-link.active {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            font-weight: 500;
        }
        
        .navbar-icon {
            margin-right: 6px;
            font-size: 1.1rem;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-name {
            font-size: 0.9rem;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-role {
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 15px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }
        
        .main-content {
            margin-top: 60px;
            min-height: calc(100vh - 60px - 60px);
            padding: 20px;
        }
        
        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
        
        .footer p {
            margin: 5px 0;
            font-size: 0.9rem;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .navbar-menu {
                display: none;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                flex-direction: column;
                padding: 10px;
                gap: 5px;
            }
            
            .navbar-menu.active {
                display: flex;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .navbar-link {
                padding: 12px 15px;
            }
            
            .navbar-user {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <span class="navbar-brand-icon">🎓</span>
                <span>中职技能竞赛训练辅助系统</span>
            </a>
            
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                ☰
            </button>
            
            <ul class="navbar-menu" id="navbarMenu">
                <?php foreach ($nav_items as $item): ?>
                    <li class="navbar-item">
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                           class="navbar-link <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
                            <span class="navbar-icon"><?php echo $item['icon']; ?></span>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
                
                <?php if ($is_logged_in && $current_user && $current_user['role'] === 'admin'): ?>
                    <?php foreach ($admin_nav_items as $item): ?>
                        <li class="navbar-item">
                            <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                               class="navbar-link <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
                                <span class="navbar-icon"><?php echo $item['icon']; ?></span>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            
            <div class="navbar-user">
                <?php if ($is_logged_in && $current_user): ?>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo mb_substr($current_user['username'] ?? $current_user['phone'], 0, 1); ?>
                        </div>
                        <div>
                            <div class="user-name">
                                <?php echo htmlspecialchars($current_user['username'] ?? $current_user['phone']); ?>
                                <span class="user-role">
                                    <?php echo $current_user['role'] === 'admin' ? '教师' : '学生'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <a href="logout.php" class="btn-logout">退出</a>
                <?php else: ?>
                    <a href="login.php" class="btn-logout" style="background: rgba(255,255,255,0.2);">登录</a>
                    <a href="register.php" class="btn-logout" style="background: rgba(255,255,255,0.2);">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- 主内容区 -->
    <div class="main-content">
