<?php
/**
 * 中职技能竞赛训练辅助系统 - 学习进度页面
 * 展示学生学习进度、待完成任务和学习成就
 */

$page_title = '学习进度';
require_once __DIR__ . '/includes/header.php';

// 检查登录状态
if (!$is_logged_in) {
    echo '<div class="container">';
    echo '<div class="alert alert-warning">';
    echo '请先登录后再查看学习进度。';
    echo '<a href="login.php" class="btn btn-primary" style="margin-left: 10px;">去登录</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$db = getDBConnection();
$user_id = $_SESSION['user_id'];

// 获取模拟考试完成数量
$exam_count_stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM user_exams 
    WHERE user_id = ? AND status = 'completed'
");
$exam_count_stmt->execute([$user_id]);
$exam_count = $exam_count_stmt->fetch()['count'];

// 获取题目练习数量
$practice_count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT ua.question_id) as count 
    FROM user_answers ua
    JOIN user_exams ue ON ua.user_exam_id = ue.id
    WHERE ue.user_id = ?
");
$practice_count_stmt->execute([$user_id]);
$practice_count = $practice_count_stmt->fetch()['count'];

// 获取总答题数量
$total_answers_stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM user_answers ua
    JOIN user_exams ue ON ua.user_exam_id = ue.id
    WHERE ue.user_id = ?
");
$total_answers_stmt->execute([$user_id]);
$total_answers = $total_answers_stmt->fetch()['count'];

// 计算时间投入（基于考试时长和答题时间估算）
$time_spent_stmt = $db->prepare("
    SELECT COALESCE(SUM(duration_used), 0) as total_minutes 
    FROM user_exams 
    WHERE user_id = ? AND status = 'completed'
");
$time_spent_stmt->execute([$user_id]);
$total_minutes = $time_spent_stmt->fetch()['total_minutes'];
$total_hours = round($total_minutes / 60, 1);

// 获取错题数量
$wrong_count_stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM user_answers ua
    JOIN user_exams ue ON ua.user_exam_id = ue.id
    WHERE ue.user_id = ? AND ua.is_correct = 0
");
$wrong_count_stmt->execute([$user_id]);
$wrong_count = $wrong_count_stmt->fetch()['count'];

// 获取正确率
$accuracy = 0;
if ($total_answers > 0) {
    $correct_count_stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM user_answers ua
        JOIN user_exams ue ON ua.user_exam_id = ue.id
        WHERE ue.user_id = ? AND ua.is_correct = 1
    ");
    $correct_count_stmt->execute([$user_id]);
    $correct_count = $correct_count_stmt->fetch()['count'];
    $accuracy = round(($correct_count / $total_answers) * 100, 1);
}

// 获取最近完成的考试
$recent_exams_stmt = $db->prepare("
    SELECT 
        e.title,
        e.subject,
        ue.score,
        ue.total_score,
        ue.completed_at
    FROM user_exams ue
    JOIN exams e ON ue.exam_id = e.id
    WHERE ue.user_id = ? AND ue.status = 'completed'
    ORDER BY ue.completed_at DESC
    LIMIT 5
");
$recent_exams_stmt->execute([$user_id]);
$recent_exams = $recent_exams_stmt->fetchAll();

// 获取用户创建时间，计算学习天数
$user_info_stmt = $db->prepare("SELECT created_at FROM users WHERE id = ?");
$user_info_stmt->execute([$user_id]);
$user_info = $user_info_stmt->fetch();
$learning_days = 0;
if ($user_info) {
    $created_date = new DateTime($user_info['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created_date);
    $learning_days = $diff->days + 1; // 至少算 1 天
}

// 计算学习成就
$achievements = [];

// 首次考试成就
if ($exam_count >= 1) {
    $achievements[] = [
        'icon' => '🎯',
        'title' => '初出茅庐',
        'description' => '完成了第一次模拟考试',
        'unlocked' => true
    ];
}

// 多次考试成就
if ($exam_count >= 5) {
    $achievements[] = [
        'icon' => '📚',
        'title' => '勤学苦练',
        'description' => '累计完成 5 次模拟考试',
        'unlocked' => true
    ];
}

// 高分成就
$best_score_stmt = $db->prepare("
    SELECT MAX(score * 100.0 / total_score) as max_percent 
    FROM user_exams 
    WHERE user_id = ?
");
$best_score_stmt->execute([$user_id]);
$best_percent = $best_score_stmt->fetch()['max_percent'];

if ($best_percent >= 90) {
    $achievements[] = [
        'icon' => '🏆',
        'title' => '成绩优异',
        'description' => '单次考试得分率达到 90% 以上',
        'unlocked' => true
    ];
}

// 全对成就
$perfect_exam_stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM user_exams 
    WHERE user_id = ? AND score = total_score
");
$perfect_exam_stmt->execute([$user_id]);
$perfect_count = $perfect_exam_stmt->fetch()['count'];

if ($perfect_count >= 1) {
    $achievements[] = [
        'icon' => '⭐',
        'title' => '完美表现',
        'description' => '获得过一次满分',
        'unlocked' => true
    ];
}

// 持续学习成就
if ($learning_days >= 7) {
    $achievements[] = [
        'icon' => '🔥',
        'title' => '持之以恒',
        'description' => '连续学习 7 天',
        'unlocked' => true
    ];
}

// 添加未解锁的成就提示
if (count($achievements) < 3) {
    $achievements[] = [
        'icon' => '🔒',
        'title' => '更多成就',
        'description' => '继续学习以解锁更多成就',
        'unlocked' => false
    ];
}

// 待完成任务
$pending_tasks = [];

if ($exam_count < 1) {
    $pending_tasks[] = [
        'icon' => '📝',
        'title' => '完成第一次模拟考试',
        'description' => '开始你的第一次模拟考试体验',
        'priority' => 'high',
        'link' => 'exam.php'
    ];
}

if ($practice_count < 20) {
    $pending_tasks[] = [
        'icon' => '✏️',
        'title' => '继续进行题目练习',
        'description' => '已完成 ' . $practice_count . ' 道题，继续加油！',
        'priority' => 'medium',
        'link' => 'exam.php'
    ];
}

if ($wrong_count > 0) {
    $pending_tasks[] = [
        'icon' => '📖',
        'title' => '复习错题',
        'description' => '有 ' . $wrong_count . ' 道错题需要复习',
        'priority' => 'high',
        'link' => 'score_analysis.php'
    ];
}

if (count($pending_tasks) === 0) {
    $pending_tasks[] = [
        'icon' => '🌟',
        'title' => '挑战更高难度',
        'description' => '你已经很棒了，尝试挑战更难的题目吧！',
        'priority' => 'low',
        'link' => 'exam.php'
    ];
}

?>

<div class="container">
    <div class="page-header">
        <h1>📈 学习进度</h1>
        <p>清晰了解您的学习情况，合理安排学习时间和内容</p>
    </div>
    
    <!-- 进度概览表格 -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>📊 学习数据概览</h2>
        </div>
        <div class="card-body">
            <div class="progress-table-container">
                <table class="progress-table">
                    <thead>
                        <tr>
                            <th>项目</th>
                            <th>数量</th>
                            <th>时间投入（小时）</th>
                            <th>详情</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="table-item-icon">📝</div>
                                <span>模拟考试完成数量</span>
                            </td>
                            <td><strong><?php echo $exam_count; ?></strong> 次</td>
                            <td><?php echo $total_hours; ?> 小时</td>
                            <td>
                                <span class="status-badge status-success">
                                    <?php echo $exam_count > 0 ? '进行中' : '未开始'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="table-item-icon">✏️</div>
                                <span>题目练习数量</span>
                            </td>
                            <td><strong><?php echo $practice_count; ?></strong> 道</td>
                            <td>-</td>
                            <td>
                                <span class="status-badge status-info">
                                    <?php echo $practice_count > 50 ? '优秀' : ($practice_count > 20 ? '良好' : '加油'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="table-item-icon">✅</div>
                                <span>总答题数量</span>
                            </td>
                            <td><strong><?php echo $total_answers; ?></strong> 题</td>
                            <td>-</td>
                            <td>
                                <span class="status-badge status-primary">
                                    累计
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="table-item-icon">❌</div>
                                <span>错题数量</span>
                            </td>
                            <td><strong><?php echo $wrong_count; ?></strong> 道</td>
                            <td>-</td>
                            <td>
                                <span class="status-badge status-warning">
                                    <?php echo $wrong_count > 0 ? '需复习' : '无错题'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="table-item-icon">🎯</div>
                                <span>平均正确率</span>
                            </td>
                            <td><strong><?php echo $accuracy; ?>%</strong></td>
                            <td>-</td>
                            <td>
                                <span class="status-badge <?php echo $accuracy >= 80 ? 'status-success' : ($accuracy >= 60 ? 'status-info' : 'status-warning'); ?>">
                                    <?php echo $accuracy >= 80 ? '优秀' : ($accuracy >= 60 ? '良好' : '需努力'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="table-item-icon">📅</div>
                                <span>学习天数</span>
                            </td>
                            <td><strong><?php echo $learning_days; ?></strong> 天</td>
                            <td><?php echo round($total_hours / max(1, $learning_days), 2); ?> 小时/天</td>
                            <td>
                                <span class="status-badge status-purple">
                                    <?php echo $learning_days >= 30 ? '资深学员' : ($learning_days >= 7 ? '活跃学员' : '新学员'); ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 待完成任务和学习成就 -->
    <div class="dashboard-grid">
        <!-- 待完成任务 -->
        <div class="card">
            <div class="card-header">
                <h2>📋 待完成任务</h2>
            </div>
            <div class="card-body">
                <div class="task-list">
                    <?php foreach ($pending_tasks as $index => $task): ?>
                        <div class="task-item task-priority-<?php echo $task['priority']; ?>">
                            <div class="task-icon"><?php echo $task['icon']; ?></div>
                            <div class="task-content">
                                <div class="task-title">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                        <?php echo $task['priority'] === 'high' ? '高优先级' : ($task['priority'] === 'medium' ? '中优先级' : '低优先级'); ?>
                                    </span>
                                </div>
                                <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                                <a href="<?php echo htmlspecialchars($task['link']); ?>" class="task-link">
                                    去完成 →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- 学习成就 -->
        <div class="card">
            <div class="card-header">
                <h2>🏅 学习成就</h2>
            </div>
            <div class="card-body">
                <div class="achievements-list">
                    <?php foreach ($achievements as $achievement): ?>
                        <div class="achievement-item <?php echo !$achievement['unlocked'] ? 'locked' : ''; ?>">
                            <div class="achievement-icon">
                                <?php echo $achievement['icon']; ?>
                            </div>
                            <div class="achievement-content">
                                <div class="achievement-title">
                                    <?php echo htmlspecialchars($achievement['title']); ?>
                                    <?php if ($achievement['unlocked']): ?>
                                        <span class="unlocked-badge">已解锁</span>
                                    <?php endif; ?>
                                </div>
                                <div class="achievement-description">
                                    <?php echo htmlspecialchars($achievement['description']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 最近考试记录 -->
    <?php if (count($recent_exams) > 0): ?>
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h2>📝 最近考试记录</h2>
                <a href="score_analysis.php" class="view-all-link">查看全部 →</a>
            </div>
            <div class="card-body">
                <div class="recent-exams-list">
                    <?php foreach ($recent_exams as $exam): ?>
                        <div class="recent-exam-item">
                            <div class="recent-exam-info">
                                <div class="recent-exam-title"><?php echo htmlspecialchars($exam['title']); ?></div>
                                <div class="recent-exam-meta">
                                    <span class="subject-tag"><?php echo htmlspecialchars($exam['subject']); ?></span>
                                    <span class="exam-date"><?php echo date('Y-m-d H:i', strtotime($exam['completed_at'])); ?></span>
                                </div>
                            </div>
                            <div class="recent-exam-score">
                                <span class="score-value"><?php echo $exam['score']; ?></span>
                                <span class="score-total">/ <?php echo $exam['total_score']; ?></span>
                                <span class="score-percent">(<?php echo round(($exam['score'] / $exam['total_score']) * 100, 1); ?>%)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- 学习建议 -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>💡 学习建议</h2>
        </div>
        <div class="card-body">
            <div class="suggestions-container">
                <?php
                $suggestions = [];
                
                if ($exam_count === 0) {
                    $suggestions[] = "📝 您还没有参加过模拟考试，建议尽快开始第一次考试来检验学习成果。";
                }
                
                if ($total_hours < 5) {
                    $suggestions[] = "⏰ 学习时间投入较少，建议每天安排固定时间进行学习，保持学习的连续性。";
                }
                
                if ($accuracy < 60 && $total_answers > 0) {
                    $suggestions[] = "📚 正确率有待提高，建议先巩固基础知识，再进行模拟考试。";
                } elseif ($accuracy >= 80 && $exam_count > 3) {
                    $suggestions[] = "🌟 成绩优秀！可以尝试挑战更高难度的题目或参加竞赛培训。";
                }
                
                if ($wrong_count > 10) {
                    $suggestions[] = "📖 错题数量较多，建议定期复习错题，避免重复犯错。";
                }
                
                if ($learning_days >= 7 && $total_hours / max(1, $learning_days) < 1) {
                    $suggestions[] = "🔥 学习很持续，但每天投入时间较少，建议适当增加每日学习时长。";
                }
                
                if (count($suggestions) === 0) {
                    $suggestions[] = "✨ 您的学习状态很好！继续保持当前的学习节奏，相信会取得更好的成绩。";
                }
                
                foreach ($suggestions as $suggestion) {
                    echo '<div class="suggestion-item">' . $suggestion . '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 2rem;
    color: #2d3748;
    margin-bottom: 10px;
}

.page-header p {
    color: #718096;
    font-size: 1.1rem;
}

.progress-table-container {
    overflow-x: auto;
}

.progress-table {
    width: 100%;
    border-collapse: collapse;
}

.progress-table th,
.progress-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.progress-table th {
    background: #f7fafc;
    font-weight: 600;
    color: #4a5568;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress-table tr:hover {
    background: #f7fafc;
}

.table-item-icon {
    display: inline-block;
    margin-right: 10px;
    font-size: 1.2rem;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-success {
    background: #c6f6d5;
    color: #276749;
}

.status-info {
    background: #bee3f8;
    color: #2b6cb0;
}

.status-warning {
    background: #feebc8;
    color: #c05621;
}

.status-primary {
    background: #ebf4ff;
    color: #4299e1;
}

.status-purple {
    background: #e9d8fd;
    color: #6b46c1;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.task-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.task-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f7fafc;
    border-radius: 10px;
    border-left: 4px solid #cbd5e0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.task-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.task-priority-high {
    border-left-color: #e53e3e;
}

.task-priority-medium {
    border-left-color: #dd6b20;
}

.task-priority-low {
    border-left-color: #38a169;
}

.task-icon {
    font-size: 1.8rem;
    flex-shrink: 0;
}

.task-content {
    flex: 1;
}

.task-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
}

.priority-badge {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 500;
}

.priority-high {
    background: #fed7d7;
    color: #c53030;
}

.priority-medium {
    background: #feebc8;
    color: #c05621;
}

.priority-low {
    background: #c6f6d5;
    color: #276749;
}

.task-description {
    color: #718096;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.task-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.2s ease;
}

.task-link:hover {
    color: #5a67d8;
}

.achievements-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.achievement-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f7fafc;
    border-radius: 10px;
    transition: transform 0.2s ease;
}

.achievement-item.locked {
    opacity: 0.6;
    filter: grayscale(0.5);
}

.achievement-item:not(.locked):hover {
    transform: scale(1.02);
}

.achievement-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.achievement-content {
    flex: 1;
}

.achievement-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
}

.unlocked-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
}

.achievement-description {
    color: #718096;
    font-size: 0.9rem;
}

.recent-exams-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.recent-exam-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f7fafc;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.recent-exam-item:hover {
    background: #edf2f7;
}

.recent-exam-info {
    flex: 1;
}

.recent-exam-title {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
}

.recent-exam-meta {
    display: flex;
    gap: 10px;
    align-items: center;
}

.subject-tag {
    background: #ebf4ff;
    color: #4299e1;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.exam-date {
    color: #a0aec0;
    font-size: 0.85rem;
}

.recent-exam-score {
    text-align: right;
}

.score-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #667eea;
}

.score-total {
    color: #718096;
    font-size: 0.9rem;
}

.score-percent {
    display: block;
    color: #a0aec0;
    font-size: 0.8rem;
}

.view-all-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

.suggestions-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.suggestion-item {
    padding: 15px;
    background: #f7fafc;
    border-radius: 8px;
    border-left: 4px solid #667eea;
    line-height: 1.6;
    color: #4a5568;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .progress-table th,
    .progress-table td {
        padding: 10px;
        font-size: 0.85rem;
    }
    
    .recent-exam-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .recent-exam-score {
        text-align: left;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
