<?php
/**
 * 学习进度跟踪页面
 * 帮助用户查看个人学习进度和完成情况，设置学习目标
 */

// 定义访问许可
define('ACCESS_ALLOWED', true);

// 引入配置文件
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 启动会话
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 设置页面标题
$page_title = '学习进度跟踪';

// 获取当前用户ID
$user_id = $_SESSION['user_id'];

// 获取用户信息
$user_info = get_user_info($user_id);

// 处理保存学习目标
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save_goal') {
        $goal_hours = (float)$_POST['goal_hours'];
        
        if (save_learning_goal($user_id, $goal_hours)) {
            $success_message = '学习目标保存成功！';
        } else {
            $error_message = '保存失败，请重试。';
        }
    } elseif ($action === 'update_progress') {
        // 刷新学习进度
        $refresh_message = '学习进度已更新！';
    }
}

// 获取用户的学习目标
$learning_goal = get_learning_goal($user_id);
$goal_hours = $learning_goal ? (float)$learning_goal['goal_hours'] : 10.0;

// 获取用户的学习进度数据
$progress_data = get_user_learning_progress($user_id);

// 计算总学习时间
$total_study_time = 0;
foreach ($progress_data as $item) {
    $total_study_time += (float)$item['study_time'];
}

// 计算目标与实际对比
$gap_hours = round($goal_hours - $total_study_time, 2);

// 获取知识点列表用于表格展示
$knowledge_points = get_all_knowledge_points();

// 构建进度表格数据
$progress_table_data = [];
foreach ($knowledge_points as $point) {
    $point_progress = null;
    foreach ($progress_data as $progress) {
        if ($progress['knowledge_point_id'] == $point['id']) {
            $point_progress = $progress;
            break;
        }
    }
    
    $progress_table_data[] = [
        'point_id' => $point['id'],
        'point_name' => $point['name'],
        'course_name' => $point['course_name'] ?? '未分类',
        'study_time' => $point_progress ? (float)$point_progress['study_time'] : 0,
        'score' => $point_progress ? (int)$point_progress['score'] : 0,
        'last_study_time' => $point_progress ? $point_progress['last_study_time'] : null
    ];
}

// 引入头部文件
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h2 class="page-title">📊 学习进度跟踪</h2>
        <p class="page-description">
            查看您的学习进度和完成情况，设置学习目标并进行自我监测。
        </p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($refresh_message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($refresh_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- 学习目标设置区域 -->
    <div class="goal-setting-section">
        <h3>🎯 学习目标设置</h3>
        <p class="section-description">
            设置您的学习目标时间，系统将帮助您跟踪实际学习进度。
        </p>
        
        <form method="post" action="" class="goal-form">
            <input type="hidden" name="action" value="save_goal">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="goal_hours">学习目标（小时）：</label>
                    <input type="number" 
                           name="goal_hours" 
                           id="goal_hours" 
                           min="1" 
                           max="1000" 
                           step="0.5"
                           value="<?php echo htmlspecialchars($goal_hours); ?>"
                           required>
                    <small class="form-hint">建议设置合理的学习目标，如：10小时、20小时等</small>
                </div>
                
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">保存目标</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 目标与实际对比区域 -->
    <div class="comparison-section">
        <h3>📈 目标与实际对比</h3>
        
        <div class="comparison-cards">
            <div class="comparison-card">
                <div class="card-label">学习目标</div>
                <div class="card-value"><?php echo number_format($goal_hours, 1); ?> 小时</div>
                <div class="card-icon">🎯</div>
            </div>
            
            <div class="comparison-card">
                <div class="card-label">实际学习时间</div>
                <div class="card-value"><?php echo number_format($total_study_time, 2); ?> 小时</div>
                <div class="card-icon">⏱️</div>
            </div>
            
            <div class="comparison-card <?php echo $gap_hours >= 0 ? 'positive' : 'negative'; ?>">
                <div class="card-label">差距</div>
                <div class="card-value">
                    <?php if ($gap_hours >= 0): ?>
                        <span class="text-success">+<?php echo number_format($gap_hours, 2); ?></span>
                    <?php else: ?>
                        <span class="text-danger"><?php echo number_format($gap_hours, 2); ?></span>
                    <?php endif; ?>
                    小时
                </div>
                <div class="card-icon">
                    <?php if ($gap_hours >= 0): ?>👍<?php else: ?>💪<?php endif; ?>
                </div>
            </div>
            
            <div class="comparison-card">
                <div class="card-label">完成度</div>
                <div class="card-value">
                    <?php 
                    $completion_rate = $goal_hours > 0 ? 
                        round(($total_study_time / $goal_hours) * 100, 1) : 0;
                    echo min($completion_rate, 100);
                    ?>%
                </div>
                <div class="card-icon">📊</div>
            </div>
        </div>
        
        <!-- 进度条可视化 -->
        <div class="progress-visualization">
            <div class="progress-bar-container">
                <div class="progress-bar" 
                     style="width: <?php echo min($completion_rate, 100); ?>%;"></div>
            </div>
            <div class="progress-labels">
                <span>0%</span>
                <span>50%</span>
                <span>100%</span>
            </div>
        </div>
        
        <?php if ($gap_hours < 0): ?>
            <div class="warning-tip">
                <strong>⚠️ 提示：</strong>
                您的实际学习时间已超过目标时间 <?php echo abs($gap_hours); ?> 小时。
                建议适当调整学习目标或注意休息，保持学习的可持续性。
            </div>
        <?php elseif ($gap_hours > 0 && $gap_hours < 5): ?>
            <div class="encouragement-tip">
                <strong>🎉 加油！</strong>
                您距离达成目标只差 <?php echo number_format($gap_hours, 2); ?> 小时了，
                继续努力就能完成学习目标！
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 学习进度表格区域 -->
    <div class="progress-table-section">
        <h3>📋 学习进度详情</h3>
        <p class="section-description">
            查看每个知识点的学习时间和成绩，了解各部分的掌握情况。
        </p>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>序号</th>
                        <th>知识点名称</th>
                        <th>所属课程</th>
                        <th>学习时间（小时）</th>
                        <th>成绩</th>
                        <th>最后学习时间</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($progress_table_data)): ?>
                        <tr>
                            <td colspan="7" class="empty-cell">暂无学习记录</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($progress_table_data as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['point_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo number_format($row['study_time'], 2); ?></td>
                                <td>
                                    <?php if ($row['score'] > 0): ?>
                                        <span class="score-badge score-<?php echo get_score_level($row['score']); ?>">
                                            <?php echo $row['score']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">未测试</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['last_study_time']): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($row['last_study_time'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">未学习</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status = get_progress_status($row['study_time'], $row['score']);
                                    echo '<span class="status-badge status-' . $status['class'] . '">' . 
                                         $status['text'] . '</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="table-actions">
            <form method="post" action="">
                <input type="hidden" name="action" value="update_progress">
                <button type="submit" class="btn btn-primary">🔄 更新进度</button>
            </form>
        </div>
    </div>
    
    <!-- 学习统计图表 -->
    <div class="charts-section">
        <div class="chart-container">
            <h4>📊 学习时间分布</h4>
            <canvas id="studyTimeChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h4>📈 成绩分布</h4>
            <canvas id="scoreChart"></canvas>
        </div>
    </div>
    
    <!-- 使用说明 -->
    <div class="info-section">
        <h3>📖 使用说明</h3>
        <ul class="info-list">
            <li><strong>目标设置：</strong>在上方输入框中设置您的学习目标时间（单位：小时），点击"保存目标"即可。</li>
            <li><strong>进度查看：</strong>表格中列出了每个知识点的学习时间和成绩，数据由系统自动记录。</li>
            <li><strong>目标对比：</strong>通过对比区域可以直观看到目标时间与实际时间的差距。</li>
            <li><strong>状态说明：</strong>
                <span class="status-badge status-completed">已完成</span>
                <span class="status-badge status-in-progress">进行中</span>
                <span class="status-badge status-not-started">未开始</span>
            </li>
            <li><strong>定期更新：</strong>建议定期查看和更新学习目标与进度，及时调整学习策略。</li>
        </ul>
        <p class="info-note">
            💡 提示：如果实际学习时间超过了目标时间，差距会以负值显示（红色），
            反之为正值（绿色）。这有助于您及时发现学习策略的不足并进行调整。
        </p>
    </div>
</div>

<script>
// 学习进度数据
const progressData = <?php echo json_encode($progress_table_data, JSON_UNESCAPED_UNICODE); ?>;

// 初始化图表
document.addEventListener('DOMContentLoaded', function() {
    initStudyTimeChart();
    initScoreChart();
});

// 学习时间分布图表
function initStudyTimeChart() {
    const ctx = document.getElementById('studyTimeChart').getContext('2d');
    
    // 按课程分组学习时间
    const courseTimeMap = {};
    progressData.forEach(item => {
        const course = item.course_name;
        if (!courseTimeMap[course]) {
            courseTimeMap[course] = 0;
        }
        courseTimeMap[course] += item.study_time;
    });
    
    const labels = Object.keys(courseTimeMap);
    const data = Object.values(courseTimeMap);
    const colors = [
        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', 
        '#9b59b6', '#1abc9c', '#34495e', '#e67e22'
    ];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw.toFixed(2) + '小时';
                        }
                    }
                }
            }
        }
    });
}

// 成绩分布图表
function initScoreChart() {
    const ctx = document.getElementById('scoreChart').getContext('2d');
    
    // 统计成绩分布
    const scoreRanges = {
        '优秀 (90-100)': 0,
        '良好 (80-89)': 0,
        '中等 (70-79)': 0,
        '及格 (60-69)': 0,
        '不及格 (<60)': 0
    };
    
    progressData.forEach(item => {
        const score = item.score;
        if (score > 0) {
            if (score >= 90) scoreRanges['优秀 (90-100)']++;
            else if (score >= 80) scoreRanges['良好 (80-89)']++;
            else if (score >= 70) scoreRanges['中等 (70-79)']++;
            else if (score >= 60) scoreRanges['及格 (60-69)']++;
            else scoreRanges['不及格 (<60)']++;
        }
    });
    
    const labels = Object.keys(scoreRanges);
    const data = Object.values(scoreRanges);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '人数',
                data: data,
                backgroundColor: [
                    '#2ecc71', '#3498db', '#f39c12', '#e74c3c', '#95a5a6'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// 移动端菜单切换
function toggleMobileMenu() {
    const sidebar = document.getElementById('mobileSidebar');
    sidebar.classList.toggle('active');
}
</script>

<?php
// 引入底部文件
include '../includes/footer.php';
?>
