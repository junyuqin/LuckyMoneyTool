<?php
/**
 * 中职技能竞赛训练辅助系统 - 资源下载页面
 * 提供学习资料和资源下载功能
 */

$page_title = '资源下载';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// 处理删除操作（仅管理员）
if ($is_logged_in && $current_user && $current_user['role'] === 'admin') {
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        $db->prepare("DELETE FROM resources WHERE id = ?")->execute([$delete_id]);
        header('Location: resources.php?deleted=1');
        exit;
    }
}

// 获取所有资源
$resources_stmt = $db->query("
    SELECT r.*, u.username as uploader_name 
    FROM resources r 
    LEFT JOIN users u ON r.uploaded_by = u.id 
    ORDER BY r.created_at DESC
");
$resources = $resources_stmt->fetchAll();

// 按类别分组
$categories = [];
foreach ($resources as $resource) {
    $cat = $resource['category'];
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $resource;
}

// 统计信息
$total_resources = count($resources);
$total_downloads = array_sum(array_column($resources, 'download_count'));

?>

<div class="container">
    <div class="page-header">
        <h1>📚 资源下载</h1>
        <p>获取与技能竞赛相关的学习资料和资源，支持自主学习和备考</p>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row" style="margin-bottom: 30px;">
        <div class="stat-item">
            <span class="stat-icon">📁</span>
            <span class="stat-value"><?php echo $total_resources; ?></span>
            <span class="stat-label">总资源数</span>
        </div>
        <div class="stat-item">
            <span class="stat-icon">⬇️</span>
            <span class="stat-value"><?php echo $total_downloads; ?></span>
            <span class="stat-label">总下载次数</span>
        </div>
        <div class="stat-item">
            <span class="stat-icon">📂</span>
            <span class="stat-value"><?php echo count($categories); ?></span>
            <span class="stat-label">资源类别</span>
        </div>
    </div>
    
    <?php if ($is_logged_in && $current_user && $current_user['role'] === 'admin'): ?>
        <!-- 管理员上传资源表单 -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h2>📤 上传新资源</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="upload_resource.php" enctype="multipart/form-data" class="upload-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="resource_name">资源名称 *</label>
                            <input type="text" id="resource_name" name="name" required 
                                   placeholder="请输入资源名称" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="resource_category">资源类别 *</label>
                            <select id="resource_category" name="category" required class="form-control">
                                <option value="">请选择类别</option>
                                <option value="复习资料">复习资料</option>
                                <option value="练习题">练习题</option>
                                <option value="参考书目">参考书目</option>
                                <option value="视频教程">视频教程</option>
                                <option value="历年真题">历年真题</option>
                                <option value="其他">其他</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="resource_description">资源描述</label>
                        <textarea id="resource_description" name="description" rows="3" 
                                  placeholder="请简要描述资源内容" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="resource_file">上传文件 *</label>
                        <input type="file" id="resource_file" name="file" required class="form-control">
                        <small class="form-hint">支持 PDF、DOC、DOCX、ZIP、RAR 等格式，最大 50MB</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">上传资源</button>
                        <button type="reset" class="btn btn-secondary">重置</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- 资源列表 -->
    <?php if (count($categories) > 0): ?>
        <?php foreach ($categories as $category => $items): ?>
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h2>
                        <?php 
                        $cat_icons = [
                            '复习资料' => '📖',
                            '练习题' => '✏️',
                            '参考书目' => '📚',
                            '视频教程' => '🎬',
                            '历年真题' => '📝',
                            '其他' => '📁'
                        ];
                        echo isset($cat_icons[$category]) ? $cat_icons[$category] : '📁';
                        echo ' ' . htmlspecialchars($category);
                        ?>
                        <span class="badge"><?php echo count($items); ?></span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="resources-table-container">
                        <table class="resources-table">
                            <thead>
                                <tr>
                                    <th>资源名称</th>
                                    <th>描述</th>
                                    <th>上传者</th>
                                    <th>大小</th>
                                    <th>下载次数</th>
                                    <th>上传时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $resource): ?>
                                    <tr>
                                        <td>
                                            <div class="resource-name">
                                                <span class="resource-icon">
                                                    <?php 
                                                    $ext = strtolower(pathinfo($resource['file_path'], PATHINFO_EXTENSION));
                                                    $icons = [
                                                        'pdf' => '📕',
                                                        'doc' => '📘',
                                                        'docx' => '📘',
                                                        'xls' => '📗',
                                                        'xlsx' => '📗',
                                                        'ppt' => '📙',
                                                        'pptx' => '📙',
                                                        'zip' => '📦',
                                                        'rar' => '📦',
                                                        'mp4' => '🎬',
                                                        'avi' => '🎬',
                                                        'mov' => '🎬',
                                                    ];
                                                    echo isset($icons[$ext]) ? $icons[$ext] : '📄';
                                                    ?>
                                                </span>
                                                <?php echo htmlspecialchars($resource['name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="resource-description">
                                                <?php echo htmlspecialchars(mb_substr($resource['description'] ?? '', 0, 50)); ?>
                                                <?php if (mb_strlen($resource['description'] ?? '') > 50): ?>...<?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="uploader-name">
                                                <?php echo htmlspecialchars($resource['uploader_name'] ?? '系统'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="file-size">
                                                <?php 
                                                $size = $resource['file_size'];
                                                if ($size >= 1024 * 1024) {
                                                    echo round($size / (1024 * 1024), 2) . ' MB';
                                                } elseif ($size >= 1024) {
                                                    echo round($size / 1024, 2) . ' KB';
                                                } else {
                                                    echo $size . ' B';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="download-count">
                                                <?php echo $resource['download_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="upload-date">
                                                <?php echo date('Y-m-d', strtotime($resource['created_at'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="download.php?id=<?php echo $resource['id']; ?>" 
                                                   class="btn-download" title="下载">
                                                    ⬇️ 下载
                                                </a>
                                                <?php if ($is_logged_in && $current_user && $current_user['role'] === 'admin'): ?>
                                                    <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" 
                                                       class="btn-edit" title="编辑">
                                                        ✏️
                                                    </a>
                                                    <a href="resources.php?delete=<?php echo $resource['id']; ?>" 
                                                       class="btn-delete" 
                                                       title="删除"
                                                       onclick="return confirm('确定要删除这个资源吗？此操作不可恢复！')">
                                                        🗑️
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3>暂无资源</h3>
            <p>还没有上传任何学习资源，请稍后再来查看。</p>
        </div>
    <?php endif; ?>
    
    <!-- 使用说明 -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>ℹ️ 使用说明</h2>
        </div>
        <div class="card-body">
            <div class="usage-tips">
                <div class="tip-item">
                    <span class="tip-icon">💡</span>
                    <div class="tip-content">
                        <strong>下载资源：</strong>点击资源列表中的"下载"按钮即可开始下载。某些资源可能需要特定权限才能下载。
                    </div>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🔒</span>
                    <div class="tip-content">
                        <strong>权限说明：</strong>部分资源仅对注册用户开放，请先登录后再下载。
                    </div>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">⚠️</span>
                    <div class="tip-content">
                        <strong>注意事项：</strong>下载时请确保网络连接稳定。编辑和删除操作仅限管理员执行。
                    </div>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🔄</span>
                    <div class="tip-content">
                        <strong>定期更新：</strong>资源下载页面会定期更新，建议定期访问以获取最新的学习资料。
                    </div>
                </div>
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

.stats-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    flex: 1;
    min-width: 150px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.stat-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    display: block;
}

.stat-label {
    color: #718096;
    font-size: 0.9rem;
    margin-top: 5px;
    display: block;
}

.upload-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #4a5568;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    display: block;
    margin-top: 6px;
    color: #718096;
    font-size: 0.85rem;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.resources-table-container {
    overflow-x: auto;
}

.resources-table {
    width: 100%;
    border-collapse: collapse;
}

.resources-table th,
.resources-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.resources-table th {
    background: #f7fafc;
    font-weight: 600;
    color: #4a5568;
    font-size: 0.85rem;
    text-transform: uppercase;
}

.resources-table tr:hover {
    background: #f7fafc;
}

.resource-name {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    color: #2d3748;
}

.resource-icon {
    font-size: 1.2rem;
}

.resource-description {
    color: #718096;
    font-size: 0.9rem;
    max-width: 300px;
}

.uploader-name {
    color: #4a5568;
    font-size: 0.9rem;
}

.file-size {
    color: #718096;
    font-size: 0.85rem;
    white-space: nowrap;
}

.download-count {
    color: #4a5568;
    font-size: 0.9rem;
}

.upload-date {
    color: #a0aec0;
    font-size: 0.85rem;
}

.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-download {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: inline-block;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-edit,
.btn-delete {
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 1rem;
    transition: background 0.2s ease;
}

.btn-edit {
    background: #ebf4ff;
}

.btn-edit:hover {
    background: #bee3f8;
}

.btn-delete {
    background: #fed7d7;
}

.btn-delete:hover {
    background: #feb2b2;
}

.usage-tips {
    display: grid;
    gap: 15px;
}

.tip-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f7fafc;
    border-radius: 8px;
}

.tip-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.tip-content {
    color: #4a5568;
    line-height: 1.6;
}

.tip-content strong {
    color: #2d3748;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #2d3748;
    margin-bottom: 10px;
}

.empty-state p {
    color: #718096;
}

@media (max-width: 768px) {
    .stats-row {
        flex-direction: column;
    }
    
    .resources-table th,
    .resources-table td {
        padding: 8px;
        font-size: 0.85rem;
    }
    
    .resource-description {
        max-width: 150px;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
