<?php
/**
 * 技能评估分析页面
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

$pageTitle = '技能评估分析';
$currentModule = 'skill_evaluation';

// 获取技能类别
$skillCategories = getSkillCategories();

// 处理统计请求
$statsData = null;
$chartData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $timePeriod = sanitizeInput($_POST['time_period'] ?? 'last_month');
    $skillCategory = sanitizeInput($_POST['skill_category'] ?? '');
    
    // 计算统计数据
    $statsData = calculateStatistics($skillCategory, $timePeriod);
    
    // 生成图表数据（模拟）
    $pdo = getDBConnection();
    
    // 按技能类别统计
    $stmt = $pdo->prepare("SELECT skill_category, COUNT(*) as count, AVG(score) as avg_score 
                          FROM skill_records 
                          WHERE skill_category IS NOT NULL 
                          GROUP BY skill_category");
    $stmt->execute();
    $categoryStats = $stmt->fetchAll();
    
    foreach ($categoryStats as $stat) {
        $chartData['categories'][] = $stat['skill_category'];
        $chartData['counts'][] = $stat['count'];
        $chartData['avgScores'][] = round($stat['avg_score'], 1);
    }
    
    // 按时间统计（最近 6 个月）
    $stmt = $pdo->prepare("SELECT strftime('%Y-%m', created_at) as month, 
                                  COUNT(*) as count,
                                  AVG(score) as avg_score
                          FROM skill_records 
                          WHERE created_at > date('now', '-6 months')
                          GROUP BY strftime('%Y-%m', created_at)
                          ORDER BY month");
    $stmt->execute();
    $timeStats = $stmt->fetchAll();
    
    foreach ($timeStats as $stat) {
        $chartData['months'][] = $stat['month'];
        $chartData['trendCounts'][] = $stat['count'];
        $chartData['trendScores'][] = round($stat['avg_score'], 1);
    }
} else {
    // 默认显示上个月的数据
    $timePeriod = 'last_month';
    $skillCategory = '';
    $statsData = calculateStatistics('', $timePeriod);
}
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
                <i class="fas fa-chart-line"></i> 技能评估分析
            </h2>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> 筛选条件</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="evaluationForm">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="time_period">
                                    <i class="fas fa-calendar"></i> 时间段
                                </label>
                                <select class="form-control" id="time_period" name="time_period">
                                    <option value="last_month" <?php echo $timePeriod === 'last_month' ? 'selected' : ''; ?>>上个月</option>
                                    <option value="last_3_months" <?php echo $timePeriod === 'last_3_months' ? 'selected' : ''; ?>>最近三个月</option>
                                    <option value="last_6_months" <?php echo $timePeriod === 'last_6_months' ? 'selected' : ''; ?>>最近六个月</option>
                                    <option value="last_year" <?php echo $timePeriod === 'last_year' ? 'selected' : ''; ?>>去年</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="skill_category">
                                    <i class="fas fa-tags"></i> 技能类别
                                </label>
                                <select class="form-control" id="skill_category" name="skill_category">
                                    <option value="">全部类别</option>
                                    <option value="编程" <?php echo $skillCategory === '编程' ? 'selected' : ''; ?>>编程</option>
                                    <option value="设计" <?php echo $skillCategory === '设计' ? 'selected' : ''; ?>>设计</option>
                                    <option value="沟通" <?php echo $skillCategory === '沟通' ? 'selected' : ''; ?>>沟通</option>
                                    <option value="管理" <?php echo $skillCategory === '管理' ? 'selected' : ''; ?>>管理</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 查看统计结果
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($statsData): ?>
                <!-- 统计卡片 -->
                <div class="stats-grid mt-20">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo $statsData['total_records'] ?? 0; ?></h4>
                            <p>总记录数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo round($statsData['avg_score'] ?? 0, 1); ?></h4>
                            <p>平均评分</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo $statsData['total_students'] ?? 0; ?></h4>
                            <p>参与学生数</p>
                        </div>
                    </div>
                </div>
                
                <!-- 图表区域 -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <!-- 技能类别分布图 -->
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-pie"></i> 技能类别分布</h4>
                        <div class="chart-wrapper">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- 趋势图 -->
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-line"></i> 技能发展趋势</h4>
                        <div class="chart-wrapper">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- 详细数据表 -->
                <div class="card mt-20">
                    <div class="card-header">
                        <h3><i class="fas fa-table"></i> 详细统计数据</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>技能类别</th>
                                        <th>记录数</th>
                                        <th>平均分</th>
                                        <th>最高分</th>
                                        <th>最低分</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $pdo = getDBConnection();
                                    $stmt = $pdo->prepare("SELECT skill_category, 
                                                                  COUNT(*) as count,
                                                                  AVG(score) as avg_score,
                                                                  MAX(score) as max_score,
                                                                  MIN(score) as min_score
                                                          FROM skill_records 
                                                          WHERE skill_category IS NOT NULL 
                                                          GROUP BY skill_category");
                                    $stmt->execute();
                                    $detailedStats = $stmt->fetchAll();
                                    
                                    foreach ($detailedStats as $stat): 
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['skill_category']); ?></td>
                                            <td><?php echo $stat['count']; ?></td>
                                            <td><?php echo round($stat['avg_score'], 1); ?></td>
                                            <td><?php echo $stat['max_score']; ?></td>
                                            <td><?php echo $stat['min_score']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-20">
                    <i class="fas fa-info-circle"></i>
                    请选择筛选条件并点击"查看统计结果"按钮以查看分析数据。
                </div>
            <?php endif; ?>
            
            <!-- 使用说明 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> 使用说明</h3>
                </div>
                <div class="card-body">
                    <div style="line-height: 2;">
                        <p><strong>功能介绍：</strong></p>
                        <ul>
                            <li><i class="fas fa-check" style="color: var(--success-color);"></i> 多维度评估指标：支持按时间段和技能类别进行分析</li>
                            <li><i class="fas fa-check" style="color: var(--success-color);"></i> 可视化图表：直观展示技能掌握情况和趋势</li>
                            <li><i class="fas fa-check" style="color: var(--success-color);"></i> 数据统计：提供详细的统计数据表格</li>
                        </ul>
                        
                        <p class="mt-20"><strong>使用建议：</strong></p>
                        <ul>
                            <li><i class="fas fa-lightbulb" style="color: var(--warning-color);"></i> 选择合适的时间段以获取准确的分析结果</li>
                            <li><i class="fas fa-lightbulb" style="color: var(--warning-color);"></i> 可根据具体技能类别进行针对性分析</li>
                            <li><i class="fas fa-lightbulb" style="color: var(--warning-color);"></i> 定期查看分析结果以调整教学方案</li>
                        </ul>
                        
                        <p class="mt-20"><strong>注意事项：</strong></p>
                        <ul>
                            <li><i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i> 请确保网络连接的稳定性</li>
                            <li><i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i> 数据量较大时加载可能需要一定时间</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // 技能类别分布图
        <?php if (!empty($chartData['categories'])): ?>
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartData['categories']); ?>,
                datasets: [{
                    label: '记录数',
                    data: <?php echo json_encode($chartData['counts']); ?>,
                    backgroundColor: 'rgba(74, 144, 217, 0.7)',
                    borderColor: 'rgba(74, 144, 217, 1)',
                    borderWidth: 1
                }, {
                    label: '平均分',
                    data: <?php echo json_encode($chartData['avgScores']); ?>,
                    type: 'line',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '记录数'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        title: {
                            display: true,
                            text: '平均分'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        // 趋势图
        <?php if (!empty($chartData['months'])): ?>
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartData['months']); ?>,
                datasets: [{
                    label: '记录数趋势',
                    data: <?php echo json_encode($chartData['trendCounts']); ?>,
                    borderColor: 'rgba(74, 144, 217, 1)',
                    backgroundColor: 'rgba(74, 144, 217, 0.2)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: '平均分趋势',
                    data: <?php echo json_encode($chartData['trendScores']); ?>,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '记录数'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        title: {
                            display: true,
                            text: '平均分'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
