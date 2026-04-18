<?php
/**
 * 技能数据采集页面
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

// 处理数据提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = sanitizeInput($_POST['student_name'] ?? '');
    $skillName = sanitizeInput($_POST['skill_name'] ?? '');
    $skillCategory = sanitizeInput($_POST['skill_category'] ?? '');
    $performanceDescription = sanitizeInput($_POST['performance_description'] ?? '');
    $score = intval($_POST['score'] ?? 0);
    $evaluationLevel = sanitizeInput($_POST['evaluation_level'] ?? '');
    
    // 验证必填项
    if (empty($studentName)) {
        $error = '请输入学生姓名';
    } elseif (empty($skillName)) {
        $error = '请输入技能名称';
    } elseif (empty($performanceDescription)) {
        $error = '请输入表现描述';
    } else {
        // 处理文件上传
        $filePath = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $uploadResult = handleFileUpload($_FILES['attachment'], $allowedTypes);
            
            if ($uploadResult['success']) {
                $filePath = $uploadResult['file_path'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (empty($error)) {
            // 获取或创建学生记录
            $pdo = getDBConnection();
            
            // 查找学生
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :name OR phone = :name LIMIT 1");
            $stmt->execute([':name' => $studentName]);
            $student = $stmt->fetch();
            
            if (!$student) {
                // 如果没有找到学生，创建一个临时记录（实际应用中应该有专门的学生管理）
                $studentId = 0; // 使用 0 表示未关联具体学生
            } else {
                $studentId = $student['id'];
            }
            
            // 插入技能记录
            $stmt = $pdo->prepare("INSERT INTO skill_records 
                                  (student_id, student_name, skill_name, skill_category, 
                                   performance_description, score, evaluation_level, file_path, teacher_id) 
                                  VALUES (:student_id, :student_name, :skill_name, :skill_category, 
                                          :performance_description, :score, :evaluation_level, :file_path, :teacher_id)");
            
            $result = $stmt->execute([
                ':student_id' => $studentId,
                ':student_name' => $studentName,
                ':skill_name' => $skillName,
                ':skill_category' => $skillCategory,
                ':performance_description' => $performanceDescription,
                ':score' => $score,
                ':evaluation_level' => $evaluationLevel,
                ':file_path' => $filePath,
                ':teacher_id' => $user['id']
            ]);
            
            if ($result) {
                $success = '技能数据录入成功！';
                logSystemAction($user['id'], 'skill_collection', '录入技能数据：' . $skillName);
            } else {
                $error = '数据保存失败，请稍后重试';
            }
        }
    }
}

// 获取已录入的技能数据
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT sr.*, u.username as teacher_name 
                      FROM skill_records sr 
                      LEFT JOIN users u ON sr.teacher_id = u.id 
                      ORDER BY sr.created_at DESC 
                      LIMIT 50");
$stmt->execute();
$skillRecords = $stmt->fetchAll();

$pageTitle = '技能数据采集';
$currentModule = 'skill_collection';
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
                <i class="fas fa-database"></i> 技能数据采集
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
                <!-- 数据录入表单 -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> 录入技能数据</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="skillForm">
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
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="skill_name">
                                    技能名称 <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="skill_name" 
                                    name="skill_name" 
                                    placeholder="请输入技能名称"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="skill_category">
                                    技能类别
                                </label>
                                <select class="form-control" id="skill_category" name="skill_category">
                                    <option value="">请选择技能类别</option>
                                    <option value="编程">编程</option>
                                    <option value="设计">设计</option>
                                    <option value="沟通">沟通</option>
                                    <option value="管理">管理</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="performance_description">
                                    表现描述 <span class="required">*</span>
                                </label>
                                <textarea 
                                    class="form-control" 
                                    id="performance_description" 
                                    name="performance_description" 
                                    rows="4"
                                    placeholder="请详细描述学生的技能表现"
                                    required
                                ></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="score">
                                    评分（0-100）
                                </label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="score" 
                                    name="score" 
                                    min="0"
                                    max="100"
                                    placeholder="请输入评分"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="evaluation_level">
                                    评估等级
                                </label>
                                <select class="form-control" id="evaluation_level" name="evaluation_level">
                                    <option value="">请选择评估等级</option>
                                    <option value="优秀">优秀</option>
                                    <option value="良好">良好</option>
                                    <option value="中等">中等</option>
                                    <option value="及格">及格</option>
                                    <option value="不及格">不及格</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachment">
                                    附件上传（可选）
                                </label>
                                <div class="upload-area" onclick="document.getElementById('attachment').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>点击选择文件或拖拽文件到此处</p>
                                    <p class="text-muted" style="font-size: 12px;">支持 JPG、PNG、PDF、DOC、XLS 等格式，最大 5MB</p>
                                    <input 
                                        type="file" 
                                        id="attachment" 
                                        name="attachment" 
                                        style="display: none;"
                                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                                    >
                                </div>
                                <div id="fileName" class="mt-10 text-muted"></div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 提交数据
                                </button>
                                <button type="reset" class="btn btn-outline">
                                    <i class="fas fa-redo"></i> 重置
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- 快速操作提示 -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-lightbulb"></i> 使用说明</h3>
                    </div>
                    <div class="card-body">
                        <div style="line-height: 2;">
                            <p><strong>数据采集方式：</strong></p>
                            <ul>
                                <li><i class="fas fa-keyboard" style="color: var(--primary-color);"></i> 手动输入：通过表单直接录入学生技能数据</li>
                                <li><i class="fas fa-file-upload" style="color: var(--success-color);"></i> 文件上传：可上传相关证明材料作为附件</li>
                                <li><i class="fas fa-qrcode" style="color: var(--warning-color);"></i> 二维码扫描：支持扫描二维码快速录入（待实现）</li>
                            </ul>
                            
                            <p class="mt-20"><strong>注意事项：</strong></p>
                            <ul>
                                <li><i class="fas fa-exclamation-circle" style="color: var(--danger-color);"></i> 带<span class="required">*</span>的字段为必填项</li>
                                <li><i class="fas fa-info-circle" style="color: var(--info-color);"></i> 确保所有信息的准确性</li>
                                <li><i class="fas fa-shield-alt" style="color: var(--success-color);"></i> 上传的文件应符合格式和大小限制</li>
                                <li><i class="fas fa-trash-alt" style="color: var(--danger-color);"></i> 编辑或删除数据时请谨慎操作</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 数据列表 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> 已录入的技能数据</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>学生姓名</th>
                                    <th>技能名称</th>
                                    <th>技能类别</th>
                                    <th>评分</th>
                                    <th>评估等级</th>
                                    <th>录入教师</th>
                                    <th>日期</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($skillRecords as $record): ?>
                                    <tr>
                                        <td><?php echo $record['id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['skill_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['skill_category'] ?? '-'); ?></td>
                                        <td><?php echo $record['score'] > 0 ? $record['score'] : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($record['evaluation_level'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($record['teacher_name'] ?? '-'); ?></td>
                                        <td><?php echo formatDateTime($record['created_at'], 'Y-m-d H:i'); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm btn-outline" onclick="editRecord(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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
        // 文件上传显示文件名
        document.getElementById('attachment').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.getElementById('fileName').textContent = '已选择：' + fileName;
            }
        });
        
        // 编辑记录（示例）
        function editRecord(id) {
            alert('编辑功能开发中... ID: ' + id);
            // 实际应用中应该打开模态框或跳转到编辑页面
        }
        
        // 删除记录
        function deleteRecord(id) {
            if (confirm('确定要删除这条记录吗？此操作不可恢复。')) {
                fetch('../api/skill_record.php', {
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
