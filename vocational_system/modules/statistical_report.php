<?php
/**
 * 统计分析报告页面
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

$pageTitle = '统计分析报告';
$currentModule = 'statistical_report';

$pdo = getDBConnection();

// 获取统计数据
// 技能掌握率
$stmt = $pdo->query("SELECT skill_category, 
                            COUNT(*) as total,
                            SUM(CASE WHEN score >= 60 THEN 1 ELSE 0 END) as passed,
                            ROUND(AVG(score), 2) as avg_score
                     FROM skill_records 
                     WHERE skill_category IS NOT NULL 
                     GROUP BY skill_category");
$masteryStats = $stmt->fetchAll();

// 进步率统计（按月份）
$stmt = $pdo->query("SELECT strftime('%Y-%m', created_at) as month,
                            COUNT(*) as record_count,
                            ROUND(AVG(score), 2) as avg_score
                     FROM skill_records 
                     WHERE created_at > date('now', '-6 months')
                     GROUP BY strftime('%Y-%m', created_at)
                     ORDER BY month");
$progressStats = $stmt->fetchAll();

// 课程反馈统计
$stmt = $pdo->query("SELECT evaluation_level, COUNT(*) as count 
                     FROM skill_records 
                     WHERE evaluation_level IS NOT NULL 
                     GROUP BY evaluation_level");
$feedbackStats = $stmt->fetchAll();

// 获取已有报告
$stmt = $pdo->query("SELECT * FROM statistical_reports ORDER BY created_at DESC LIMIT 10");
$reports = $stmt->fetchAll();
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
                <i class="fas fa-chart-bar"></i> 统计分析报告
            </h2>
            
            <!-- 简要介绍 -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> 报告说明</h3>
                </div>
                <div class="card-body">
                    <p>本页面展示学校整体的技能发展趋势分析。系统通过分析学生的技能数据，生成多种统计图表，帮助管理人员评估教学效果，识别问题，并优化课程设置，提高教育质量。</p>
                </div>
            </div>
            
            <!-- 统计图表区域 -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px;">
                <!-- 技能掌握率图表 -->
                <div class="chart-container">
                    <h4><i class="fas fa-chart-pie"></i> 技能掌握率图表</h4>
                    <div class="chart-wrapper">
                        <canvas id="masteryChart"></canvas>
                    </div>
                </div>
                
                <!-- 进步率图表 -->
                <div class="chart-container">
                    <h4><i class="fas fa-chart-line"></i> 进步率图表</h4>
                    <div class="chart-wrapper">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- 课程反馈图表 -->
            <div class="chart-container mt-20">
                <h4><i class="fas fa-comments"></i> 课程反馈图表</h4>
                <div class="chart-wrapper" style="height: 350px;">
                    <canvas id="feedbackChart"></canvas>
                </div>
            </div>
            
            <!-- 报告管理 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt"></i> 历史报告管理</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="addReport()">
                            <i class="fas fa-plus"></i> 添加报告
                        </button>
                        <button class="btn btn-warning" onclick="editReport()">
                            <i class="fas fa-edit"></i> 编辑报告
                        </button>
                        <button class="btn btn-danger" onclick="deleteReport()">
                            <i class="fas fa-trash"></i> 删除报告
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>报告名称</th>
                                    <th>报告类型</th>
                                    <th>时间周期</th>
                                    <th>技能类别</th>
                                    <th>生成时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><?php echo $report['id']; ?></td>
                                        <td><?php echo htmlspecialchars($report['report_name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['report_type'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($report['time_period'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($report['skill_category'] ?? '-'); ?></td>
                                        <td><?php echo formatDateTime($report['created_at']); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm btn-info" onclick="viewReport(<?php echo $report['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-success" onclick="exportReport(<?php echo $report['id']; ?>)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                            <li><i class="fas fa-chart-pie" style="color: var(--primary-color);"></i> 技能掌握率图表：展示各技能类别的掌握情况</li>
                            <li><i class="fas fa-chart-line" style="color: var(--success-color);"></i> 进步率图表：展示学生技能的进步趋势</li>
                            <li><i class="fas fa-comments" style="color: var(--warning-color);"></i> 课程反馈图表：展示学生对课程的反馈评价</li>
                            <li><i class="fas fa-file-alt" style="color: var(--info-color);"></i> 报告管理：添加、编辑、删除统计分析报告</li>
                        </ul>
                        
                        <p class="mt-20"><strong>注意事项：</strong></p>
                        <ul>
                            <li><i class="fas fa-wifi" style="color: var(--info-color);"></i> 请确保网络连接稳定，以避免数据加载问题</li>
                            <li><i class="fas fa-save" style="color: var(--warning-color);"></i> 建议定期备份重要的统计数据，以防数据丢失</li>
                            <li><i class="fas fa-question-circle" style="color: var(--primary-color);"></i> 如遇问题，可点击页面右上角的帮助按钮获取支持</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // 技能掌握率图表
        const masteryCtx = document.getElementById('masteryChart').getContext('2d');
        new Chart(masteryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($masteryStats, 'skill_category')); ?>,
                datasets: [{
                    label: '总记录数',
                    data: <?php echo json_encode(array_column($masteryStats, 'total')); ?>,
                    backgroundColor: 'rgba(74, 144, 217, 0.7)',
                    borderColor: 'rgba(74, 144, 217, 1)',
                    borderWidth: 1
                }, {
                    label: '通过率',
                    data: <?php 
                        $passRates = [];
                        foreach ($masteryStats as $stat) {
                            $passRates[] = round(($stat['passed'] / $stat['total']) * 100, 1);
                        }
                        echo json_encode($passRates); 
                    ?>,
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
                            text: '通过率 (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        // 进步率图表
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($progressStats, 'month')); ?>,
                datasets: [{
                    label: '记录数量',
                    data: <?php echo json_encode(array_column($progressStats, 'record_count')); ?>,
                    borderColor: 'rgba(74, 144, 217, 1)',
                    backgroundColor: 'rgba(74, 144, 217, 0.2)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: '平均分数',
                    data: <?php echo json_encode(array_column($progressStats, 'avg_score')); ?>,
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
        
        // 课程反馈图表
        const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
        new Chart(feedbackCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($feedbackStats, 'evaluation_level')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($feedbackStats, 'count')); ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(74, 144, 217, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(255, 152, 0, 0.7)',
                        'rgba(244, 67, 54, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(74, 144, 217, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(255, 152, 0, 1)',
                        'rgba(244, 67, 54, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        function addReport() {
            alert('添加报告功能开发中...');
        }
        
        function editReport() {
            alert('请先选择要编辑的报告');
        }
        
        function deleteReport() {
            if (confirm('确定要删除选中的报告吗？此操作不可恢复。')) {
                alert('删除功能开发中...');
            }
        }
        
        function viewReport(id) {
            alert('查看报告详情功能开发中... ID: ' + id);
        }
        
        function exportReport(id) {
            if (confirm('确定要导出这份报告吗？')) {
                window.location.href = '../api/export_report.php?id=' + id;
            }
        }
    </script>
</body>
</html>
