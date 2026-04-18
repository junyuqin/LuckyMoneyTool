<?php
/**
 * 成长档案展示页面
 * 中职学生技能成长档案系统
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 检查是否登录
$user = getCurrentUser();
if (!$user) {
    header('Location: ../modules/login.php');
    exit;
}

$pageTitle = '成长档案展示';
$currentModule = 'archive_display';

// 获取当前学生的档案数据
$pdo = getDBConnection();

// 获取学习成果列表
$stmt = $pdo->prepare("SELECT la.*, sr.skill_name 
                      FROM learning_achievements la 
                      LEFT JOIN skill_records sr ON la.skill_name = sr.skill_name 
                      WHERE la.student_id = :student_id 
                      ORDER BY la.achievement_date DESC");
$stmt->execute([':student_id' => $user['id']]);
$achievements = $stmt->fetchAll();

// 如果没有学习成果，从技能记录中获取
if (empty($achievements)) {
    $stmt = $pdo->prepare("SELECT DISTINCT skill_name, evaluation_level as achievement_level, 
                                  created_at as achievement_date, performance_description as description
                          FROM skill_records 
                          WHERE student_id = :student_id OR student_name = :student_name
                          ORDER BY created_at DESC");
    $stmt->execute([
        ':student_id' => $user['id'],
        ':student_name' => $user['username']
    ]);
    $achievements = $stmt->fetchAll();
}

// 获取成长档案
$stmt = $pdo->prepare("SELECT * FROM growth_archives 
                      WHERE student_name = :student_name 
                      ORDER BY generated_at DESC 
                      LIMIT 10");
$stmt->execute([':student_name' => $user['username']]);
$archives = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 中职学生技能成长档案系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <h2 style="margin-bottom: 30px;">
                <i class="fas fa-presentation"></i> 成长档案展示
            </h2>
            
            <!-- 学习成果图表 -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> 学习成果图表</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="achievementChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- 学习成果列表 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-list-alt"></i> 学习成果列表</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>技能名称</th>
                                    <th>评估等级</th>
                                    <th>获得日期</th>
                                    <th>描述</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($achievements as $achievement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($achievement['skill_name'] ?? '-'); ?></td>
                                        <td>
                                            <?php 
                                            $level = $achievement['achievement_level'] ?? '';
                                            $levelClass = '';
                                            if ($level === '优秀') $levelClass = 'success';
                                            elseif ($level === '良好') $levelClass = 'primary';
                                            elseif ($level === '中等') $levelClass = 'warning';
                                            else $levelClass = 'danger';
                                            ?>
                                            <span class="badge badge-<?php echo $levelClass; ?>">
                                                <?php echo htmlspecialchars($level); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($achievement['achievement_date'] ?? $achievement['created_at'], 'Y-m-d'); ?></td>
                                        <td><?php echo htmlspecialchars(mb_substr($achievement['description'] ?? '-', 0, 50)) . '...'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 教师互动交流区域 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-comments"></i> 与教师互动</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="../api/interaction_api.php">
                        <div class="form-group">
                            <label for="message">
                                输入您的问题或建议
                            </label>
                            <textarea 
                                class="form-control" 
                                id="message" 
                                name="content" 
                                rows="4"
                                placeholder="请输入您对学习的疑问或对教师的建议..."
                                required
                            ></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> 提交
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-20">
                        <h4>最近的消息</h4>
                        <?php
                        $stmt = $pdo->prepare("SELECT i.*, u.username as sender_name 
                                              FROM interactions i 
                                              LEFT JOIN users u ON i.user_id = u.id 
                                              WHERE i.user_id = :user_id OR i.parent_id IN (
                                                  SELECT id FROM interactions WHERE user_id = :user_id2
                                              )
                                              ORDER BY i.created_at DESC 
                                              LIMIT 5");
                        $stmt->execute([
                            ':user_id' => $user['id'],
                            ':user_id2' => $user['id']
                        ]);
                        $messages = $stmt->fetchAll();
                        
                        if (empty($messages)):
                        ?>
                            <p class="text-muted text-center">暂无消息</p>
                        <?php else: ?>
                            <div class="message-list">
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message-item" style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                            <strong><?php echo htmlspecialchars($msg['sender_name'] ?? '未知'); ?></strong>
                                            <span class="text-muted" style="font-size: 12px;">
                                                <?php echo formatDateTime($msg['created_at'], 'Y-m-d H:i'); ?>
                                            </span>
                                        </div>
                                        <p style="margin: 0;"><?php echo htmlspecialchars($msg['content']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 使用说明 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> 使用说明</h3>
                </div>
                <div class="card-body">
                    <div style="line-height: 2;">
                        <p><strong>功能介绍：</strong></p>
                        <ul>
                            <li><i class="fas fa-chart-line" style="color: var(--primary-color);"></i> 学习成果图表：直观展示各项技能的进步和发展</li>
                            <li><i class="fas fa-list" style="color: var(--success-color);"></i> 学习成果列表：详细列出各项技能的评估结果</li>
                            <li><i class="fas fa-comment" style="color: var(--warning-color);"></i> 互动区域：向教师提问或提供建议</li>
                        </ul>
                        
                        <p class="mt-20"><strong>使用建议：</strong></p>
                        <ul>
                            <li><i class="fas fa-lightbulb" style="color: var(--info-color);"></i> 定期查看成长档案，跟踪个人技能提升</li>
                            <li><i class="fas fa-lightbulb" style="color: var(--info-color);"></i> 积极与教师互动，解决学习中的问题</li>
                            <li><i class="fas fa-lightbulb" style="color: var(--info-color);"></i> 保持网络连接稳定以确保数据加载</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // 学习成果图表
        const ctx = document.getElementById('achievementChart').getContext('2d');
        
        // 准备图表数据
        const skillLevels = {
            '优秀': 90,
            '良好': 80,
            '中等': 70,
            '及格': 60,
            '不及格': 40
        };
        
        const achievements = <?php echo json_encode($achievements); ?>;
        const skillData = {};
        
        achievements.forEach(item => {
            const skillName = item.skill_name || '其他技能';
            const level = item.achievement_level || '中等';
            skillData[skillName] = skillLevels[level] || 70;
        });
        
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: Object.keys(skillData),
                datasets: [{
                    label: '技能水平',
                    data: Object.values(skillData),
                    backgroundColor: 'rgba(74, 144, 217, 0.2)',
                    borderColor: 'rgba(74, 144, 217, 1)',
                    pointBackgroundColor: 'rgba(74, 144, 217, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(74, 144, 217, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
