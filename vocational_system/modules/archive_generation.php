<?php
/**
 * 成长档案生成页面
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

$error = '';
$success = '';

// 处理档案生成请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = sanitizeInput($_POST['student_name'] ?? '');
    $skillData = sanitizeInput($_POST['skill_data'] ?? '');
    
    if (empty($studentName)) {
        $error = '请输入学生姓名';
    } elseif (empty($skillData)) {
        $error = '请输入技能数据';
    } else {
        $pdo = getDBConnection();
        
        // 获取学生的技能记录
        $stmt = $pdo->prepare("SELECT * FROM skill_records WHERE student_name = :name ORDER BY created_at DESC");
        $stmt->execute([':name' => $studentName]);
        $skills = $stmt->fetchAll();
        
        // 生成档案内容
        $archiveContent = generateArchiveContent($studentName, $skills, $skillData);
        
        // 插入档案记录
        $stmt = $pdo->prepare("INSERT INTO growth_archives 
                              (student_id, student_name, skill_data, learning_progress, 
                               development_suggestions, archive_content) 
                              VALUES (0, :student_name, :skill_data, :learning_progress, 
                                      :development_suggestions, :archive_content)");
        
        $result = $stmt->execute([
            ':student_name' => $studentName,
            ':skill_data' => $skillData,
            ':learning_progress' => generateLearningProgress($skills),
            ':development_suggestions' => generateSuggestions($skills),
            ':archive_content' => $archiveContent
        ]);
        
        if ($result) {
            $success = '成长档案生成成功！';
            logSystemAction($user['id'], 'archive_generation', '生成学生档案：' . $studentName);
        } else {
            $error = '档案生成失败，请稍后重试';
        }
    }
}

// 获取历史档案记录
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM growth_archives ORDER BY generated_at DESC LIMIT 50");
$stmt->execute();
$archives = $stmt->fetchAll();

// 辅助函数
function generateArchiveContent($studentName, $skills, $skillData) {
    $content = "<h2>学生成长档案 - {$studentName}</h2>";
    $content .= "<p>生成时间：" . date('Y-m-d H:i:s') . "</p>";
    $content .= "<h3>技能掌握情况</h3><ul>";
    
    foreach ($skills as $skill) {
        $content .= "<li>{$skill['skill_name']} - {$skill['evaluation_level']} (评分：{$skill['score']})</li>";
    }
    
    $content .= "</ul><h3>学习进度</h3><p>{$skillData}</p>";
    
    return $content;
}

function generateLearningProgress($skills) {
    if (empty($skills)) return '暂无学习记录';
    
    $totalScore = array_sum(array_column($skills, 'score'));
    $avgScore = round($totalScore / count($skills), 1);
    
    return "平均评分：{$avgScore}，共完成" . count($skills) . "项技能学习";
}

function generateSuggestions($skills) {
    if (empty($skills)) return '请先录入技能数据';
    
    $categories = array_unique(array_column($skills, 'skill_category'));
    
    return "建议继续加强" . implode('、', $categories) . "方面的学习，保持当前的学习进度";
}

$pageTitle = '成长档案生成';
$currentModule = 'archive_generation';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 中职学生技能成长档案系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <h2 style="margin-bottom: 30px;">
                <i class="fas fa-file-alt"></i> 成长档案生成
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- 档案生成表单 -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> 生成档案</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="archiveForm">
                            <div class="form-group">
                                <label for="student_name">
                                    学生姓名 <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="student_name" 
                                    name="student_name" 
                                    placeholder="请输入学生姓名"
                                    required
                                    list="student_list"
                                >
                                <datalist id="student_list">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT DISTINCT student_name FROM skill_records");
                                    $stmt->execute();
                                    $students = $stmt->fetchAll();
                                    foreach ($students as $student):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($student['student_name']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="skill_data">
                                    技能数据 <span class="required">*</span>
                                </label>
                                <textarea 
                                    class="form-control" 
                                    id="skill_data" 
                                    name="skill_data" 
                                    rows="6"
                                    placeholder="请输入学生的技能掌握情况和学习进度等信息"
                                    required
                                ></textarea>
                                <small class="text-muted">内容应详实且准确，反映学生的真实学习情况</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-magic"></i> 生成档案
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- 使用说明 -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> 使用说明</h3>
                    </div>
                    <div class="card-body">
                        <div style="line-height: 2;">
                            <p><strong>操作步骤：</strong></p>
                            <ol>
                                <li>在"学生姓名"框中输入学生的姓名</li>
                                <li>在"技能数据"框中输入学生的技能掌握情况</li>
                                <li>点击"生成档案"按钮</li>
                                <li>在下方查看生成的档案记录</li>
                            </ol>
                            
                            <p class="mt-20"><strong>注意事项：</strong></p>
                            <ul>
                                <li><i class="fas fa-exclamation-circle" style="color: var(--danger-color);"></i> 确保所有输入信息的准确性</li>
                                <li><i class="fas fa-shield-alt" style="color: var(--success-color);"></i> 遵循数据保护规定，妥善处理个人信息</li>
                                <li><i class="fas fa-download" style="color: var(--primary-color);"></i> 可使用导出功能分享档案</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 历史档案记录 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> 历史档案记录</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>学生姓名</th>
                                    <th>学习进度</th>
                                    <th>发展建议</th>
                                    <th>生成时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archives as $archive): ?>
                                    <tr>
                                        <td><?php echo $archive['id']; ?></td>
                                        <td><?php echo htmlspecialchars($archive['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars(mb_substr($archive['learning_progress'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars(mb_substr($archive['development_suggestions'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo formatDateTime($archive['generated_at']); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm btn-info" onclick="viewArchive(<?php echo $archive['id']; ?>)">
                                                <i class="fas fa-eye"></i> 查看
                                            </button>
                                            <button class="btn btn-sm btn-success" onclick="exportArchive(<?php echo $archive['id']; ?>)">
                                                <i class="fas fa-download"></i> 导出
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteArchive(<?php echo $archive['id']; ?>)">
                                                <i class="fas fa-trash"></i> 删除
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function viewArchive(id) {
            alert('查看档案详情功能开发中... ID: ' + id);
        }
        
        function exportArchive(id) {
            if (confirm('确定要导出这份档案吗？')) {
                window.location.href = '../api/export_archive.php?id=' + id;
            }
        }
        
        function deleteArchive(id) {
            if (confirm('确定要删除这份档案吗？此操作不可恢复！')) {
                fetch('../api/archive_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('删除成功');
                        location.reload();
                    } else {
                        alert('删除失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除失败，请稍后重试');
                });
            }
        }
    </script>
</body>
</html>
