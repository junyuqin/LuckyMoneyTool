<?php
/**
 * 首页 - 中职专业课程知识图谱系统
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

startSession();

// 获取当前用户信息
$userInfo = getCurrentUserInfo();
$isLoggedIn = isLoggedIn();

// 获取统计数据
$db = getDBConnection();

// 课程数量
$courseCount = $db->querySingle("SELECT COUNT(*) FROM courses");

// 知识点数量
$knowledgePointCount = $db->querySingle("SELECT COUNT(*) FROM knowledge_points");

// 资源数量
$resourceCount = $db->querySingle("SELECT COUNT(*) FROM course_resources");

// 如果是登录用户，获取个人学习数据
$learningData = null;
if ($isLoggedIn) {
    $userId = getCurrentUserId();
    
    // 已学习知识点数量
    $completedPoints = $db->querySingle("
        SELECT COUNT(*) FROM learning_progress 
        WHERE user_id = $userId AND status = 'completed'
    ");
    
    // 总学习时长
    $totalStudyTime = $db->querySingle("
        SELECT COALESCE(SUM(study_time), 0) FROM learning_progress 
        WHERE user_id = $userId
    ");
    
    // 平均成绩
    $avgScore = $db->querySingle("
        SELECT COALESCE(ROUND(AVG(score), 1), 0) FROM learning_progress 
        WHERE user_id = $userId AND score > 0
    ");
    
    $learningData = [
        'completed_points' => $completedPoints,
        'total_study_time' => round($totalStudyTime, 1),
        'avg_score' => $avgScore
    ];
}

// 获取最新课程资源
$latestResources = [];
$result = $db->query("
    SELECT cr.*, u.username as author_username
    FROM course_resources cr
    LEFT JOIN users u ON cr.author_id = u.id
    ORDER BY cr.upload_time DESC
    LIMIT 5
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $latestResources[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>首页 - 中职专业课程知识图谱系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .nav-menu {
            display: flex;
            gap: 20px;
        }
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-menu a:hover {
            background: rgba(255,255,255,0.2);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-name {
            font-size: 14px;
        }
        .btn-login, .btn-register {
            background: rgba(255,255,255,0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-login:hover, .btn-register:hover {
            background: white;
            color: #667eea;
        }
        .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .welcome-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .welcome-section h1 {
            color: #333;
            margin-bottom: 15px;
        }
        .welcome-section p {
            color: #666;
            line-height: 1.8;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .features-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .features-section h2 {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .feature-item {
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .feature-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        .feature-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .feature-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .feature-description {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        .resources-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .resources-section h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .resource-list {
            list-style: none;
            padding: 0;
        }
        .resource-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .resource-item:last-child {
            border-bottom: none;
        }
        .resource-name {
            color: #333;
            font-weight: 500;
        }
        .resource-meta {
            color: #999;
            font-size: 13px;
        }
        .resource-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 10px;
        }
        .type-document {
            background: #e3f2fd;
            color: #1976d2;
        }
        .type-video {
            background: #fce4ec;
            color: #c2185b;
        }
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">中职专业课程知识图谱系统</div>
            <nav class="nav-menu">
                <a href="index.php">首页</a>
                <?php if ($isLoggedIn): ?>
                    <a href="pages/resources.php">课程资源</a>
                    <a href="pages/knowledge_map.php">知识图谱</a>
                    <a href="pages/learning_path.php">学习路径</a>
                    <a href="pages/progress.php">学习进度</a>
                    <?php if (strpos($userInfo['username'], 'teacher') === 0): ?>
                        <a href="pages/evaluation.php">教学评估</a>
                    <?php endif; ?>
                    <a href="pages/feedback.php">用户反馈</a>
                <?php endif; ?>
            </nav>
            <div class="user-info">
                <?php if ($isLoggedIn): ?>
                    <span class="user-name">欢迎，<?php echo escapeHtml($userInfo['username']); ?></span>
                    <form method="POST" action="pages/logout.php" style="display: inline;">
                        <button type="submit" class="btn-logout">退出登录</button>
                    </form>
                <?php else: ?>
                    <a href="pages/login.php" class="btn-login">登录</a>
                    <a href="pages/register.php" class="btn-register">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-section">
            <h1>欢迎来到中职专业课程知识图谱系统</h1>
            <p>
                本系统旨在为中等职业学校师生提供一个全面的课程学习与教学管理平台。
                通过整合专业课程资源、构建知识点关联图谱、智能推荐学习路径以及跟踪学习进度，
                帮助学生更好地掌握专业知识结构，制定个性化学习计划，提高学习效率。
                同时，系统也为教师提供课程资料管理、教学评估等功能，促进教学质量的持续提升。
            </p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $courseCount; ?></div>
                <div class="stat-label">专业课程</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $knowledgePointCount; ?></div>
                <div class="stat-label">知识点</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $resourceCount; ?></div>
                <div class="stat-label">课程资源</div>
            </div>
            <?php if ($isLoggedIn && $learningData): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $learningData['completed_points']; ?></div>
                    <div class="stat-label">已学知识点</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $learningData['total_study_time']; ?></div>
                    <div class="stat-label">学习时长（小时）</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $learningData['avg_score']; ?></div>
                    <div class="stat-label">平均成绩</div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="features-section">
            <h2>系统功能</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">📚</div>
                    <div class="feature-title">课程资源管理</div>
                    <div class="feature-description">
                        教师可以上传和管理各类课程资料，包括文档、视频等学习资源，支持分类管理和快速检索。
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔗</div>
                    <div class="feature-title">知识点关联图</div>
                    <div class="feature-description">
                        以图形化方式展示各专业知识点的关联关系，帮助学生全面理解课程内容及其结构。
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🎯</div>
                    <div class="feature-title">学习路径推荐</div>
                    <div class="feature-description">
                        根据学生的学习进度和知识掌握情况，智能推荐个性化学习路径，提高学习效率。
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-title">学习进度跟踪</div>
                    <div class="feature-description">
                        实时记录学习时间和成绩，设置学习目标，对比目标与实际进度，及时调整学习策略。
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📝</div>
                    <div class="feature-title">教学评估管理</div>
                    <div class="feature-description">
                        教师可对课程进行评估和反馈，填写评估报告，系统支持统计分析，提升教学质量。
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💬</div>
                    <div class="feature-title">用户反馈与建议</div>
                    <div class="feature-description">
                        收集用户使用体验和功能建议，帮助开发团队持续优化和改进系统功能。
                    </div>
                </div>
            </div>
        </div>
        
        <div class="resources-section">
            <h2>最新课程资源</h2>
            <ul class="resource-list">
                <?php foreach ($latestResources as $resource): ?>
                    <li class="resource-item">
                        <div>
                            <span class="resource-type type-<?php echo $resource['resource_type']; ?>">
                                <?php echo $resource['resource_type'] === 'document' ? '文档' : '视频'; ?>
                            </span>
                            <span class="resource-name"><?php echo escapeHtml($resource['resource_name']); ?></span>
                        </div>
                        <div class="resource-meta">
                            <?php echo escapeHtml($resource['author_username'] ?? '未知'); ?> · 
                            <?php echo date('Y-m-d', strtotime($resource['upload_time'])); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> 中职专业课程知识图谱系统 版权所有</p>
    </div>
</body>
</html>
