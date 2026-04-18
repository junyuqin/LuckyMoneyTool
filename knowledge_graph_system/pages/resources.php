<?php
/**
 * 课程资源管理页面
 * 中职专业课程知识图谱系统
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// 检查是否已登录
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$userInfo = getCurrentUserInfo();
$isTeacher = strpos($userInfo['username'], 'teacher') === 0;

$db = getDBConnection();
$errors = [];
$successMessage = '';

// 处理资源上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isTeacher) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        $resourceName = trim($_POST['resource_name'] ?? '');
        $resourceType = $_POST['resource_type'] ?? '';
        $resourceDescription = trim($_POST['resource_description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        if (empty($resourceName)) {
            $errors[] = "请输入资源名称";
        } elseif (empty($resourceType)) {
            $errors[] = "请选择资源类型";
        } else {
            // 处理文件上传
            $file = $_FILES['resource_file'] ?? null;
            $filePath = '';
            $fileSize = 0;
            
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4', 'avi', 'mov', 'wmv'];
                $uploadResult = handleFileUpload($file, $allowedTypes, 104857600);
                
                if ($uploadResult['success']) {
                    $filePath = $uploadResult['file_path'];
                    $fileSize = $file['size'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            if (empty($errors)) {
                $stmt = $db->prepare("
                    INSERT INTO course_resources 
                    (resource_name, resource_type, resource_description, file_path, author_id, author_name, category, file_size, upload_time) 
                    VALUES (:name, :type, :desc, :path, :author_id, :author_name, :category, :size, datetime('now'))
                ");
                $stmt->bindValue(':name', $resourceName, SQLITE3_TEXT);
                $stmt->bindValue(':type', $resourceType, SQLITE3_TEXT);
                $stmt->bindValue(':desc', $resourceDescription, SQLITE3_TEXT);
                $stmt->bindValue(':path', $filePath, SQLITE3_TEXT);
                $stmt->bindValue(':author_id', $userInfo['id'], SQLITE3_INTEGER);
                $stmt->bindValue(':author_name', $userInfo['username'], SQLITE3_TEXT);
                $stmt->bindValue(':category', $category, SQLITE3_TEXT);
                $stmt->bindValue(':size', $fileSize, SQLITE3_INTEGER);
                
                if ($stmt->execute()) {
                    $successMessage = "资源上传成功！";
                    logAction($userInfo['id'], 'upload_resource', "上传资源：{$resourceName}");
                } else {
                    $errors[] = "资源上传失败，请稍后重试";
                }
            }
        }
    } elseif ($action === 'delete' && $isTeacher) {
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        if ($resourceId > 0) {
            $stmt = $db->prepare("DELETE FROM course_resources WHERE id = :id AND author_id = :author_id");
            $stmt->bindValue(':id', $resourceId, SQLITE3_INTEGER);
            $stmt->bindValue(':author_id', $userInfo['id'], SQLITE3_INTEGER);
            $stmt->execute();
            $successMessage = "资源删除成功！";
        }
    }
}

// 获取资源列表
$filterType = $_GET['type'] ?? '';
$searchKeyword = trim($_GET['search'] ?? '');

$sql = "SELECT cr.*, u.username as author_username 
        FROM course_resources cr 
        LEFT JOIN users u ON cr.author_id = u.id 
        WHERE 1=1";

$params = [];
if ($filterType) {
    $sql .= " AND cr.resource_type = :type";
    $params[':type'] = $filterType;
}
if ($searchKeyword) {
    $sql .= " AND (cr.resource_name LIKE :keyword OR cr.resource_description LIKE :keyword)";
    $params[':keyword'] = '%' . $searchKeyword . '%';
}

$sql .= " ORDER BY cr.upload_time DESC";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}
$result = $stmt->execute();
$resources = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $resources[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>课程资源管理 - 中职专业课程知识图谱系统</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 20px; font-weight: bold; }
        .nav-menu a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 15px; border-radius: 5px; }
        .nav-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .page-title { font-size: 24px; color: #333; margin-bottom: 20px; }
        .content-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .upload-form, .resource-list { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
        .form-group textarea { height: 100px; resize: vertical; }
        .btn { background: #4CAF50; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .error-message { background: #fee; color: #c00; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #c00; }
        .success-message { background: #efe; color: #080; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #080; }
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px; }
        .resource-table { width: 100%; border-collapse: collapse; }
        .resource-table th, .resource-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .resource-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .resource-table tr:hover { background: #f8f9fa; }
        .type-badge { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 12px; }
        .type-document { background: #e3f2fd; color: #1976d2; }
        .type-video { background: #fce4ec; color: #c2185b; }
        .action-btns { display: flex; gap: 5px; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .btn-edit { background: #2196F3; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .no-teacher-hint { background: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">中职专业课程知识图谱系统</div>
        <nav class="nav-menu">
            <a href="../index.php">首页</a>
            <a href="resources.php">课程资源</a>
            <a href="knowledge_map.php">知识图谱</a>
            <a href="learning_path.php">学习路径</a>
            <a href="progress.php">学习进度</a>
            <?php if ($isTeacher): ?>
                <a href="evaluation.php">教学评估</a>
            <?php endif; ?>
            <a href="feedback.php">用户反馈</a>
            <a href="logout.php">退出</a>
        </nav>
    </div>
    
    <div class="container">
        <h1 class="page-title">课程资源管理</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escapeHtml($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div class="success-message"><?php echo escapeHtml($successMessage); ?></div>
        <?php endif; ?>
        
        <div class="content-grid">
            <?php if ($isTeacher): ?>
            <div class="upload-form">
                <h2 style="margin-top: 0;">上传资源</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label for="resource_name">资源名称 *</label>
                        <input type="text" id="resource_name" name="resource_name" required placeholder="请输入资源名称">
                    </div>
                    <div class="form-group">
                        <label for="resource_type">资源类型 *</label>
                        <select id="resource_type" name="resource_type" required>
                            <option value="">请选择类型</option>
                            <option value="document">文档</option>
                            <option value="video">视频</option>
                            <option value="other">其他</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">资源分类</label>
                        <input type="text" id="category" name="category" placeholder="如：基础教程、实践案例">
                    </div>
                    <div class="form-group">
                        <label for="resource_file">上传文件</label>
                        <input type="file" id="resource_file" name="resource_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.mp4,.avi,.mov,.wmv">
                        <small style="color: #666;">支持 PDF、Word、PPT、Excel 及常见视频格式，最大 100MB</small>
                    </div>
                    <div class="form-group">
                        <label for="resource_description">资源描述</label>
                        <textarea id="resource_description" name="resource_description" placeholder="请简要描述资源内容"></textarea>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">上传资源</button>
                </form>
            </div>
            <?php else: ?>
            <div class="no-teacher-hint">
                <h3>📢 提示</h3>
                <p>只有教师账号可以上传和管理课程资源。</p>
                <p>当前账号为学生账号，仅可浏览和下载资源。</p>
            </div>
            <?php endif; ?>
            
            <div class="resource-list">
                <h2 style="margin-top: 0;">资源列表</h2>
                
                <div class="filter-bar">
                    <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <select name="type">
                            <option value="">全部类型</option>
                            <option value="document" <?php echo $filterType === 'document' ? 'selected' : ''; ?>>文档</option>
                            <option value="video" <?php echo $filterType === 'video' ? 'selected' : ''; ?>>视频</option>
                            <option value="other" <?php echo $filterType === 'other' ? 'selected' : ''; ?>>其他</option>
                        </select>
                        <input type="text" name="search" placeholder="搜索资源..." value="<?php echo escapeHtml($searchKeyword); ?>">
                        <button type="submit" class="btn">筛选</button>
                    </form>
                </div>
                
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>资源名称</th>
                            <th>类型</th>
                            <th>分类</th>
                            <th>作者</th>
                            <th>上传时间</th>
                            <th>大小</th>
                            <?php if ($isTeacher): ?><th>操作</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($resources)): ?>
                            <tr><td colspan="<?php echo $isTeacher ? 7 : 6; ?>" style="text-align: center; color: #999;">暂无资源</td></tr>
                        <?php else: ?>
                            <?php foreach ($resources as $res): ?>
                                <tr>
                                    <td><?php echo escapeHtml($res['resource_name']); ?></td>
                                    <td><span class="type-badge type-<?php echo $res['resource_type']; ?>">
                                        <?php echo $res['resource_type'] === 'document' ? '文档' : ($res['resource_type'] === 'video' ? '视频' : '其他'); ?>
                                    </span></td>
                                    <td><?php echo escapeHtml($res['category'] ?? '-'); ?></td>
                                    <td><?php echo escapeHtml($res['author_username'] ?? $res['author_name']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($res['upload_time'])); ?></td>
                                    <td><?php echo $res['file_size'] > 0 ? formatFileSize($res['file_size']) : '-'; ?></td>
                                    <?php if ($isTeacher): ?>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn btn-edit" onclick="alert('编辑功能开发中')">编辑</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除该资源吗？');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="resource_id" value="<?php echo $res['id']; ?>">
                                                <button type="submit" class="action-btn btn-delete">删除</button>
                                            </form>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
