<?php
/**
 * 中职技能竞赛训练辅助系统 - 成绩分析页面
 * 展示学生历次考试成绩趋势、各题型得分情况和错题反馈
 */

$page_title = '成绩分析';
require_once __DIR__ . '/includes/header.php';

// 检查登录状态
if (!$is_logged_in) {
    echo '<div class="container">';
    echo '<div class="alert alert-warning">';
    echo '请先登录后再查看成绩分析。';
    echo '<a href="login.php" class="btn btn-primary" style="margin-left: 10px;">去登录</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$db = getDBConnection();
$user_id = $_SESSION['user_id'];

// 获取用户所有考试记录
$exams_stmt = $db->prepare("
    SELECT 
        ue.id,
        e.title as exam_title,
        e.subject,
        e.exam_type,
        ue.score,
        ue.total_score,
        ue.status,
        ue.completed_at,
        ue.duration_used
    FROM user_exams ue
    JOIN exams e ON ue.exam_id = e.id
    WHERE ue.user_id = ?
    ORDER BY ue.completed_at DESC
");
$exams_stmt->execute([$user_id]);
$exam_records = $exams_stmt->fetchAll();

// 计算统计数据
$total_exams = count($exam_records);
$avg_score = 0;
$best_score = 0;
$latest_score = 0;

if ($total_exams > 0) {
    $scores = array_column($exam_records, 'score');
    $total_scores = array_column($exam_records, 'total_score');
    
    $avg_score = array_sum($scores) / count($scores);
    $best_score = max($scores);
    $latest_score = $scores[0];
}

// 获取各题型得分统计
$question_type_stats = [
    '选择题' => ['correct' => 0, 'total' => 0, 'score' => 0],
    '填空题' => ['correct' => 0, 'total' => 0, 'score' => 0],
    '问答题' => ['correct' => 0, 'total' => 0, 'score' => 0],
];

// 获取用户的答题记录
$answers_stmt = $db->prepare("
    SELECT 
        q.type,
        ua.is_correct,
        ua.score,
        ua.max_score
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN user_exams ue ON ua.user_exam_id = ue.id
    WHERE ue.user_id = ? AND ua.is_correct IS NOT NULL
");
$answers_stmt->execute([$user_id]);
$answers = $answers_stmt->fetchAll();

foreach ($answers as $answer) {
    $type = $answer['type'];
    if (isset($question_type_stats[$type])) {
        $question_type_stats[$type]['total']++;
        $question_type_stats[$type]['score'] += floatval($answer['score']);
        if ($answer['is_correct']) {
            $question_type_stats[$type]['correct']++;
        }
    }
}

// 获取错题反馈
$wrong_answers_stmt = $db->prepare("
    SELECT 
        q.id,
        q.title,
        q.type,
        q.content,
        q.answer,
        q.analysis,
        e.subject,
        ua.user_answer,
        ue.completed_at
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN user_exams ue ON ua.user_exam_id = ue.id
    JOIN exams e ON ue.exam_id = e.id
    WHERE ua.user_id = ? AND ua.is_correct = 0
    ORDER BY ue.completed_at DESC
    LIMIT 10
");
$wrong_answers_stmt->execute([$user_id]);
$wrong_answers = $wrong_answers_stmt->fetchAll();

// 准备图表数据
$chart_labels = [];
$chart_scores = [];
$chart_subjects = [];

foreach (array_reverse($exam_records) as $record) {
    $chart_labels[] = date('m-d', strtotime($record['completed_at']));
    $chart_scores[] = round(($record['score'] / $record['total_score']) * 100, 1);
    $chart_subjects[] = $record['subject'];
}

?>

<div class="container">
    <div class="page-header">
        <h1>📊 成绩分析</h1>
        <p>全面了解您的学习表现，制定更有效的学习计划</p>
    </div>
    
    <!-- 统计概览 -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                📝
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_exams; ?></div>
                <div class="stat-label">已完成考试</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                🎯
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo round($avg_score, 1); ?></div>
                <div class="stat-label">平均得分</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                🏆
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $best_score; ?></div>
                <div class="stat-label">最高得分</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                📈
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $latest_score; ?></div>
                <div class="stat-label">最近得分</div>
            </div>
        </div>
    </div>
    
    <!-- 成绩趋势图 -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>📈 成绩趋势</h2>
        </div>
        <div class="card-body">
            <?php if (count($chart_labels) > 0): ?>
                <canvas id="scoreChart" height="80"></canvas>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <p>暂无考试记录，请先参加模拟考试</p>
                    <a href="exam.php" class="btn btn-primary">开始考试</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 各题型得分情况 -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>📋 各题型得分情况</h2>
        </div>
        <div class="card-body">
            <div class="question-type-stats">
                <?php foreach ($question_type_stats as $type => $stats): ?>
                    <div class="question-type-item">
                        <div class="question-type-header">
                            <span class="question-type-name"><?php echo $type; ?></span>
                            <span class="question-type-score">
                                得分：<?php echo round($stats['score'], 1); ?> | 
                                正确率：<?php 
                                    if ($stats['total'] > 0) {
                                        echo round(($stats['correct'] / $stats['total']) * 100, 1);
                                    } else {
                                        echo '0';
                                    }
                                ?>%
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php 
                                if ($stats['total'] > 0) {
                                    echo min(100, ($stats['correct'] / $stats['total']) * 100);
                                } else {
                                    echo '0';
                                }
                            ?>%;"></div>
                        </div>
                        <div class="question-type-detail">
                            正确：<?php echo $stats['correct']; ?> / 总题：<?php echo $stats['total']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- 错题反馈 -->
    <div class="card">
        <div class="card-header">
            <h2>❌ 错题回顾</h2>
            <span class="badge"><?php echo count($wrong_answers); ?> 道错题</span>
        </div>
        <div class="card-body">
            <?php if (count($wrong_answers) > 0): ?>
                <div class="wrong-answers-list">
                    <?php foreach ($wrong_answers as $index => $wrong): ?>
                        <div class="wrong-answer-item">
                            <div class="wrong-answer-header">
                                <span class="wrong-answer-type badge-<?php 
                                    echo $wrong['type'] === '选择题' ? 'primary' : 
                                         ($wrong['type'] === '填空题' ? 'success' : 'warning');
                                ?>">
                                    <?php echo htmlspecialchars($wrong['type']); ?>
                                </span>
                                <span class="wrong-answer-subject"><?php echo htmlspecialchars($wrong['subject']); ?></span>
                                <span class="wrong-answer-date"><?php echo date('Y-m-d', strtotime($wrong['completed_at'])); ?></span>
                            </div>
                            <div class="wrong-answer-title">
                                <?php echo ($index + 1); ?>. <?php echo htmlspecialchars($wrong['title']); ?>
                            </div>
                            <div class="wrong-answer-content">
                                <?php echo nl2br(htmlspecialchars($wrong['content'])); ?>
                            </div>
                            <div class="wrong-answer-comparison">
                                <div class="wrong-answer-user">
                                    <strong>你的答案：</strong>
                                    <span><?php echo htmlspecialchars($wrong['user_answer'] ?? '未作答'); ?></span>
                                </div>
                                <div class="wrong-answer-correct">
                                    <strong>正确答案：</strong>
                                    <span><?php echo htmlspecialchars($wrong['answer']); ?></span>
                                </div>
                            </div>
                            <?php if (!empty($wrong['analysis'])): ?>
                                <div class="wrong-answer-analysis">
                                    <strong>💡 解析：</strong>
                                    <p><?php echo nl2br(htmlspecialchars($wrong['analysis'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🎉</div>
                    <p>太棒了！暂时没有错题记录</p>
                    <p style="color: #718096; font-size: 0.9rem;">继续保持，争取每次考试都全对！</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 学习建议 -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>💡 学习建议</h2>
        </div>
        <div class="card-body">
            <div class="learning-suggestions">
                <?php
                $suggestions = [];
                
                // 根据正确率生成建议
                foreach ($question_type_stats as $type => $stats) {
                    if ($stats['total'] > 0) {
                        $accuracy = ($stats['correct'] / $stats['total']) * 100;
                        if ($accuracy < 60) {
                            $suggestions[] = "⚠️ <strong>{$type}</strong>：正确率较低（" . round($accuracy, 1) . "%），建议重点复习相关知识点，多做专项练习。";
                        } elseif ($accuracy < 80) {
                            $suggestions[] = "📚 <strong>{$type}</strong>：掌握程度一般（" . round($accuracy, 1) . "%），建议加强练习，巩固基础知识。";
                        }
                    }
                }
                
                // 根据平均分生成建议
                if ($total_exams > 0) {
                    $avg_percent = ($avg_score / 100) * 100;
                    if ($avg_percent < 60) {
                        $suggestions[] = "📖 总体成绩有待提高，建议制定系统的复习计划，从基础开始逐步提升。";
                    } elseif ($avg_percent < 80) {
                        $suggestions[] = "🎯 成绩稳中有升，建议针对薄弱环节进行专项突破。";
                    } else {
                        $suggestions[] = "🌟 成绩优秀！建议挑战更高难度的题目，保持学习状态。";
                    }
                } else {
                    $suggestions[] = "📝 还没有考试记录，请尽快参加模拟考试来检验学习成果。";
                }
                
                foreach ($suggestions as $suggestion) {
                    echo '<div class="suggestion-item">' . $suggestion . '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($chart_labels) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('scoreChart').getContext('2d');
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
    gradient.addColorStop(1, 'rgba(102, 126, 234, 0.1)');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: '得分百分比',
                data: <?php echo json_encode($chart_scores); ?>,
                borderColor: '#667eea',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '得分：' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2d3748;
}

.stat-label {
    color: #718096;
    font-size: 0.9rem;
    margin-top: 3px;
}

.question-type-stats {
    display: grid;
    gap: 20px;
}

.question-type-item {
    padding: 15px;
    background: #f7fafc;
    border-radius: 8px;
}

.question-type-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.question-type-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 1.1rem;
}

.question-type-score {
    color: #718096;
    font-size: 0.9rem;
}

.progress-bar {
    height: 10px;
    background: #e2e8f0;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 5px;
    transition: width 0.5s ease;
}

.question-type-detail {
    color: #718096;
    font-size: 0.85rem;
}

.wrong-answers-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.wrong-answer-item {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    background: #fff;
}

.wrong-answer-header {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.wrong-answer-type {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-primary {
    background: #ebf4ff;
    color: #4299e1;
}

.badge-success {
    background: #f0fff4;
    color: #48bb78;
}

.badge-warning {
    background: #fffff0;
    color: #d69e2e;
}

.wrong-answer-subject {
    color: #718096;
    font-size: 0.85rem;
}

.wrong-answer-date {
    color: #a0aec0;
    font-size: 0.8rem;
    margin-left: auto;
}

.wrong-answer-title {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 10px;
    font-size: 1.05rem;
}

.wrong-answer-content {
    color: #4a5568;
    margin-bottom: 15px;
    line-height: 1.6;
}

.wrong-answer-comparison {
    background: #f7fafc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
}

.wrong-answer-user {
    margin-bottom: 8px;
}

.wrong-answer-user strong {
    color: #e53e3e;
}

.wrong-answer-user span {
    color: #e53e3e;
}

.wrong-answer-correct strong {
    color: #38a169;
}

.wrong-answer-correct span {
    color: #38a169;
}

.wrong-answer-analysis {
    background: #ebf8ff;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #4299e1;
}

.wrong-answer-analysis strong {
    color: #2b6cb0;
}

.wrong-answer-analysis p {
    margin: 8px 0 0 0;
    color: #4a5568;
    line-height: 1.6;
}

.learning-suggestions {
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
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 15px;
}

.empty-state p {
    color: #718096;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .wrong-answer-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .wrong-answer-date {
        margin-left: 0;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
