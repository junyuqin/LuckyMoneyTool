<?php
/**
 * 中职技能竞赛训练辅助系统 - 首页
 */
require_once __DIR__ . '/includes/config.php';

// 初始化数据库
checkAndInitializeDatabase();

session_start();

// 检查用户是否登录
$user = null;
if (isset($_SESSION['user_id'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// 获取统计数据
$db = getDBConnection();
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalQuestions = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalExams = $db->query("SELECT COUNT(*) FROM exams WHERE status = 'active'")->fetchColumn();
$totalResources = $db->query("SELECT COUNT(*) FROM resources")->fetchColumn();

// 获取最近的考试
$recentExams = $db->query("
    SELECT e.*, u.username as creator_name
    FROM exams e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.status = 'active'
    ORDER BY e.created_at DESC
    LIMIT 5
")->fetchAll();

// 获取热门资源
$popularResources = $db->query("
    SELECT * FROM resources
    ORDER BY download_count DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>首页 - 中职技能竞赛训练辅助系统</title>
    <link rel="stylesheet" href="assets/css/style.css.php">
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">
                <span class="logo-icon">📚</span>
                <span>中职技能竞赛训练辅助系统</span>
            </a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">首页</a></li>
                    <?php if ($user): ?>
                        <li><a href="exam.php">在线模拟考试</a></li>
                        <li><a href="analysis.php">成绩分析</a></li>
                        <li><a href="progress.php">学习进度</a></li>
                        <li><a href="resources.php">资源下载</a></li>
                        <?php if ($user['role'] === 'teacher'): ?>
                            <li><a href="question_bank.php">题库管理</a></li>
                        <?php endif; ?>
                        <li><a href="feedback.php">反馈与建议</a></li>
                    <?php else: ?>
                        <li><a href="login.php">登录</a></li>
                        <li><a href="register.php">注册</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <?php if ($user): ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo e($user['username']); ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline" style="color: #fff; border-color: #fff;">退出</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <!-- 主体内容 -->
    <main class="main-content">
        <div class="container">
            <!-- 欢迎区域 -->
            <div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                <div class="card-body" style="padding: 40px;">
                    <h1 style="font-size: 32px; margin-bottom: 15px;">
                        <?php if ($user): ?>
                            欢迎回来，<?php echo e($user['username']); ?>！
                        <?php else: ?>
                            欢迎来到中职技能竞赛训练辅助系统
                        <?php endif; ?>
                    </h1>
                    <p style="font-size: 16px; opacity: 0.9; max-width: 600px;">
                        <?php if ($user): ?>
                            继续您的学习之旅，提升专业技能水平。
                        <?php else: ?>
                            为中职学生提供全方位的技能训练支持，包含在线模拟考试、题库管理、成绩分析等功能模块。
                        <?php endif; ?>
                    </p>
                    <?php if (!$user): ?>
                        <div style="margin-top: 25px;">
                            <a href="register.php" class="btn btn-lg" style="background: #fff; color: #667eea;">立即注册</a>
                            <a href="login.php" class="btn btn-lg btn-outline" style="color: #fff; border-color: #fff; margin-left: 10px;">登录系统</a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 25px;">
                            <a href="exam.php" class="btn btn-lg" style="background: #fff; color: #667eea;">开始考试</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">👨‍🎓</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalStudents; ?></div>
                        <div class="stat-label">注册用户</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">📝</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalQuestions; ?></div>
                        <div class="stat-label">题库数量</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">📋</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalExams; ?></div>
                        <div class="stat-label">可用考试</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">📖</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalResources; ?></div>
                        <div class="stat-label">学习资源</div>
                    </div>
                </div>
            </div>
            
            <!-- 主要内容区域 -->
            <div class="row">
                <!-- 左侧：最近考试 -->
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">📋 最近考试</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($recentExams): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>考试名称</th>
                                                <th>科目</th>
                                                <th>类型</th>
                                                <th>时长</th>
                                                <th>总分</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentExams as $exam): ?>
                                                <tr>
                                                    <td><?php echo e($exam['title']); ?></td>
                                                    <td>
                                                        <?php
                                                        $subjectNames = [
                                                            'math' => '数学',
                                                            'english' => '英语',
                                                            'programming' => '编程'
                                                        ];
                                                        echo e($subjectNames[$exam['subject']] ?? $exam['subject']);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $exam['exam_type'] === 'mock' ? 'badge-primary' : 'badge-warning'; ?>">
                                                            <?php echo $exam['exam_type'] === 'mock' ? '模拟考试' : '期末考试'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $exam['duration']; ?>分钟</td>
                                                    <td><?php echo $exam['total_score']; ?>分</td>
                                                    <td>
                                                        <?php if ($user): ?>
                                                            <a href="exam_detail.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">参加</a>
                                                        <?php else: ?>
                                                            <a href="login.php" class="btn btn-sm btn-outline">登录后参加</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📭</div>
                                    <div class="empty-state-title">暂无考试</div>
                                    <div class="empty-state-description">系统将陆续添加更多考试内容</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="exam.php" style="color: var(--primary-color); text-decoration: none;">查看全部考试 →</a>
                        </div>
                    </div>
                </div>
                
                <!-- 右侧：热门资源 -->
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">📖 热门资源</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($popularResources): ?>
                                <ul style="list-style: none; padding: 0;">
                                    <?php foreach ($popularResources as $resource): ?>
                                        <li style="padding: 12px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px;">
                                            <span style="font-size: 20px;">📄</span>
                                            <div style="flex: 1;">
                                                <a href="#" style="color: var(--dark-color); text-decoration: none; font-weight: 500;">
                                                    <?php echo e($resource['title']); ?>
                                                </a>
                                                <div style="font-size: 12px; color: var(--gray-color); margin-top: 3px;">
                                                    📥 <?php echo $resource['download_count']; ?> 次下载
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state" style="padding: 30px 0;">
                                    <div class="empty-state-icon">📭</div>
                                    <div class="empty-state-title">暂无资源</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="resources.php" style="color: var(--primary-color); text-decoration: none;">查看更多资源 →</a>
                        </div>
                    </div>
                    
                    <!-- 系统公告 -->
                    <div class="card" style="margin-top: 20px;">
                        <div class="card-header">
                            <h2 class="card-title">📢 系统公告</h2>
                        </div>
                        <div class="card-body">
                            <div style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
                                <span class="badge badge-danger" style="font-size: 11px;">新</span>
                                <span style="margin-left: 8px; font-size: 13px;">系统正式上线运行</span>
                                <div style="font-size: 12px; color: var(--gray-color); margin-top: 5px;">2024-01-15</div>
                            </div>
                            <div style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
                                <span class="badge badge-primary" style="font-size: 11px;">更新</span>
                                <span style="margin-left: 8px; font-size: 13px;">新增编程科目考试题库</span>
                                <div style="font-size: 12px; color: var(--gray-color); margin-top: 5px;">2024-01-10</div>
                            </div>
                            <div style="padding: 10px 0;">
                                <span class="badge badge-info" style="font-size: 11px;">通知</span>
                                <span style="margin-left: 8px; font-size: 13px;">期末考试即将开始</span>
                                <div style="font-size: 12px; color: var(--gray-color); margin-top: 5px;">2024-01-05</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 功能特点 -->
            <div style="margin-top: 40px;">
                <h2 style="text-align: center; margin-bottom: 30px; color: var(--dark-color);">系统特色功能</h2>
                <div class="stats-grid">
                    <div class="card" style="text-align: center; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📝</div>
                        <h3 style="margin-bottom: 10px; color: var(--dark-color);">在线模拟考试</h3>
                        <p style="color: var(--gray-color); font-size: 14px;">
                            支持数学、英语、编程等多科目模拟考试，实时评分，即时反馈
                        </p>
                    </div>
                    
                    <div class="card" style="text-align: center; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
                        <h3 style="margin-bottom: 10px; color: var(--dark-color);">成绩分析</h3>
                        <p style="color: var(--gray-color); font-size: 14px;">
                            详细的成绩趋势分析，各题型得分情况，帮助识别薄弱环节
                        </p>
                    </div>
                    
                    <div class="card" style="text-align: center; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📚</div>
                        <h3 style="margin-bottom: 10px; color: var(--dark-color);">题库管理</h3>
                        <p style="color: var(--gray-color); font-size: 14px;">
                            教师可自定义管理题库，支持多种题型，分类标签便捷管理
                        </p>
                    </div>
                    
                    <div class="card" style="text-align: center; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📈</div>
                        <h3 style="margin-bottom: 10px; color: var(--dark-color);">学习进度</h3>
                        <p style="color: var(--gray-color); font-size: 14px;">
                            清晰展示学习进度，时间投入统计，帮助学生制定合理学习计划
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <strong>中职技能竞赛训练辅助系统</strong>
                    <p style="opacity: 0.7; font-size: 13px; margin-top: 10px;">
                        为中职学生提供全方位的技能训练支持
                    </p>
                </div>
                <ul class="footer-links">
                    <li><a href="#">关于我们</a></li>
                    <li><a href="feedback.php">反馈建议</a></li>
                    <li><a href="#">使用帮助</a></li>
                    <li><a href="#">隐私政策</a></li>
                </ul>
                <div class="footer-copyright">
                    © 2024 中职技能竞赛训练辅助系统 版权所有
                </div>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
