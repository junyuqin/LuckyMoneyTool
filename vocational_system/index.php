<?php
/**
 * 主页面/首页
 * 中职学生技能成长档案系统
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
$user = getCurrentUser();
if (!$user) {
    header('Location: modules/login.php');
    exit;
}

// 获取统计数据
$pdo = getDBConnection();

// 总学生数
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stmt->execute();
$totalStudents = $stmt->fetch()['count'];

// 总技能记录数
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM skill_records");
$stmt->execute();
$totalSkills = $stmt->fetch()['count'];

// 总档案数
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM growth_archives");
$stmt->execute();
$totalArchives = $stmt->fetch()['count'];

// 总互动消息数
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interactions");
$stmt->execute();
$totalInteractions = $stmt->fetch()['count'];

// 获取最近的技能记录
$stmt = $pdo->prepare("SELECT sr.*, u.username as student_username 
                      FROM skill_records sr 
                      LEFT JOIN users u ON sr.student_id = u.id 
                      ORDER BY sr.created_at DESC 
                      LIMIT 5");
$stmt->execute();
$recentSkills = $stmt->fetchAll();

// 获取最新的互动消息
$stmt = $pdo->prepare("SELECT i.*, u.username as user_username 
                      FROM interactions i 
                      LEFT JOIN users u ON i.user_id = u.id 
                      ORDER BY i.created_at DESC 
                      LIMIT 5");
$stmt->execute();
$recentInteractions = $stmt->fetchAll();

$pageTitle = '首页';
$currentModule = 'dashboard';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 中职学生技能成长档案系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <h2 style="margin-bottom: 30px;">
                <i class="fas fa-home"></i> 欢迎回来，<?php echo htmlspecialchars($user['username'] ?? $user['phone']); ?>
            </h2>
            
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $totalStudents; ?></h4>
                        <p>注册学生</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $totalSkills; ?></h4>
                        <p>技能记录</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $totalArchives; ?></h4>
                        <p>成长档案</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $totalInteractions; ?></h4>
                        <p>互动消息</p>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
                <!-- 最近技能记录 -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> 最近技能记录</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentSkills)): ?>
                            <p class="text-muted text-center">暂无技能记录</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>学生姓名</th>
                                        <th>技能名称</th>
                                        <th>日期</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSkills as $skill): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($skill['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($skill['skill_name']); ?></td>
                                            <td><?php echo formatDateTime($skill['created_at'], 'm-d H:i'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 最近互动消息 -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> 最近互动消息</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentInteractions)): ?>
                            <p class="text-muted text-center">暂无互动消息</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>用户</th>
                                        <th>消息类型</th>
                                        <th>日期</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentInteractions as $interaction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($interaction['user_name'] ?? $interaction['user_username'] ?? '未知'); ?></td>
                                            <td>
                                                <?php if ($interaction['is_announcement']): ?>
                                                    <span style="color: var(--primary-color);"><i class="fas fa-bullhorn"></i> 公告</span>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);"><i class="fas fa-comment"></i> 留言</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($interaction['created_at'], 'm-d H:i'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 系统说明 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> 系统说明</h3>
                </div>
                <div class="card-body">
                    <p>欢迎使用<strong>中职学生技能成长档案系统</strong>！本系统旨在帮助教师和学生更好地管理技能学习过程，记录成长轨迹。</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                        <div>
                            <h4 style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--success-color);"></i> 主要功能</h4>
                            <ul style="line-height: 2;">
                                <li>技能数据采集与管理</li>
                                <li>技能评估与分析</li>
                                <li>成长档案生成与展示</li>
                                <li>教师与学生互动交流</li>
                                <li>统计分析报告</li>
                            </ul>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 10px;"><i class="fas fa-lightbulb" style="color: var(--warning-color);"></i> 使用提示</h4>
                            <ul style="line-height: 2;">
                                <li>首次使用请先完善个人信息</li>
                                <li>定期录入技能数据以跟踪成长</li>
                                <li>积极参与互动交流</li>
                                <li>定期查看统计分析报告</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
