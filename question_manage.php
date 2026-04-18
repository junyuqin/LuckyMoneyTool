<?php
/**
 * 中职技能竞赛训练辅助系统 - 题库管理页面
 * 教师可以新增、修改和删除题目
 */

$page_title = '题库管理';
require_once __DIR__ . '/includes/header.php';

// 检查是否为管理员
if (!$is_logged_in || !$current_user || $current_user['role'] !== 'admin') {
    echo '<div class="container">';
    echo '<div class="alert alert-warning">';
    echo '⚠️ 只有教师账号才能访问题库管理页面。';
    echo '<a href="index.php" class="btn btn-primary" style="margin-left: 10px;">返回首页</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$db = getDBConnection();
$success_message = '';
$error_message = '';

// 处理删除操作
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $db->prepare("DELETE FROM questions WHERE id = ?")->execute([$delete_id]);
    $success_message = '题目已删除成功！';
}

// 获取所有题目
$questions_stmt = $db->query("
    SELECT q.*, u.username as creator_name
    FROM questions q
    LEFT JOIN users u ON q.created_by = u.id
    ORDER BY q.created_at DESC
");
$questions = $questions_stmt->fetchAll();

// 按类型统计
$type_stats = [];
foreach ($questions as $q) {
    $type = $q['type'];
    if (!isset($type_stats[$type])) {
        $type_stats[$type] = 0;
    }
    $type_stats[$type]++;
}

?>

<div class="container">
    <div class="page-header">
        <h1>📋 题库管理</h1>
        <p>管理考试题目，支持多种题型的录入和分类</p>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- 统计信息 -->
    <div class="stats-row" style="margin-bottom: 30px;">
        <div class="stat-item">
            <span class="stat-icon">📝</span>
            <span class="stat-value"><?php echo count($questions); ?></span>
            <span class="stat-label">总题目数</span>
        </div>
        <?php foreach ($type_stats as $type => $count): ?>
            <div class="stat-item">
                <span class="stat-icon">
                    <?php echo $type === '选择题' ? '🔘' : ($type === '填空题' ? '✏️' : '💬'); ?>
                </span>
                <span class="stat-value"><?php echo $count; ?></span>
                <span class="stat-label"><?php echo $type; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- 新增题目表单 -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>➕ 新增题目</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="add_question.php" class="question-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="question_title">题目标题 *</label>
                        <input type="text" id="question_title" name="title" required 
                               placeholder="简明扼要地描述题目内容" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="question_type">题目类型 *</label>
                        <select id="question_type" name="type" required class="form-control">
                            <option value="">请选择类型</option>
                            <option value="选择题">选择题</option>
                            <option value="填空题">填空题</option>
                            <option value="问答题">问答题</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="question_category">所属科目</label>
                        <select id="question_category" name="category" class="form-control">
                            <option value="">请选择科目</option>
                            <option value="数学">数学</option>
                            <option value="英语">英语</option>
                            <option value="编程">编程</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="question_difficulty">难度等级</label>
                        <select id="question_difficulty" name="difficulty" class="form-control">
                            <option value="easy">简单</option>
                            <option value="medium" selected>中等</option>
                            <option value="hard">困难</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="question_content">题目内容 *</label>
                    <textarea id="question_content" name="content" required rows="4"
                              placeholder="请输入具体的题目描述..." class="form-control"></textarea>
                </div>
                
                <div class="form-group" id="options-group" style="display: none;">
                    <label>选项内容</label>
                    <div class="options-inputs">
                        <div class="option-item">
                            <span class="option-label">A.</span>
                            <input type="text" name="options[]" placeholder="选项 A" class="form-control option-input">
                        </div>
                        <div class="option-item">
                            <span class="option-label">B.</span>
                            <input type="text" name="options[]" placeholder="选项 B" class="form-control option-input">
                        </div>
                        <div class="option-item">
                            <span class="option-label">C.</span>
                            <input type="text" name="options[]" placeholder="选项 C" class="form-control option-input">
                        </div>
                        <div class="option-item">
                            <span class="option-label">D.</span>
                            <input type="text" name="options[]" placeholder="选项 D" class="form-control option-input">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="question_answer">正确答案 *</label>
                    <input type="text" id="question_answer" name="answer" required 
                           placeholder="请输入正确答案" class="form-control">
                    <small class="form-hint">选择题请输入选项字母（如：A），其他题型直接输入答案</small>
                </div>
                
                <div class="form-group">
                    <label for="question_analysis">答案解析</label>
                    <textarea id="question_analysis" name="analysis" rows="3"
                              placeholder="可选：输入题目的详细解析..." class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="question_tags">标签</label>
                    <input type="text" id="question_tags" name="tags" 
                           placeholder="多个标签用逗号分隔，如：函数，循环，数组" class="form-control">
                    <small class="form-hint">使用相关且统一的命名，方便后续查找和分类</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">新增题目</button>
                    <button type="reset" class="btn btn-secondary">重置</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 现有题目列表 -->
    <div class="card">
        <div class="card-header">
            <h2>📚 现有题目</h2>
            <div class="header-actions">
                <input type="text" id="search-input" placeholder="搜索题目..." class="search-input">
            </div>
        </div>
        <div class="card-body">
            <div class="questions-table-container">
                <table class="questions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>题目标题</th>
                            <th>类型</th>
                            <th>科目</th>
                            <th>难度</th>
                            <th>标签</th>
                            <th>创建者</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="questions-tbody">
                        <?php foreach ($questions as $q): ?>
                            <tr data-search="<?php echo htmlspecialchars(strtolower($q['title'] . ' ' . $q['tags'])); ?>">
                                <td><?php echo $q['id']; ?></td>
                                <td>
                                    <div class="question-title-cell">
                                        <?php echo htmlspecialchars($q['title']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge type-<?php echo $q['type']; ?>">
                                        <?php echo $q['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($q['category']): ?>
                                        <span class="subject-tag"><?php echo htmlspecialchars($q['category']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="difficulty-badge difficulty-<?php echo $q['difficulty']; ?>">
                                        <?php 
                                        $diff_labels = ['easy' => '简单', 'medium' => '中等', 'hard' => '困难'];
                                        echo $diff_labels[$q['difficulty']] ?? $q['difficulty'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($q['tags']): ?>
                                        <div class="tags-list">
                                            <?php 
                                            $tags = explode(',', $q['tags']);
                                            foreach (array_slice($tags, 0, 3) as $tag):
                                            ?>
                                                <span class="tag-item"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($tags) > 3): ?>
                                                <span class="tag-more">+<?php echo count($tags) - 3; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($q['creator_name'] ?? '系统'); ?>
                                </td>
                                <td>
                                    <span class="date-text">
                                        <?php echo date('Y-m-d', strtotime($q['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_question.php?id=<?php echo $q['id']; ?>" 
                                           class="btn-edit-small" title="编辑">
                                            ✏️
                                        </a>
                                        <a href="question_manage.php?delete=<?php echo $q['id']; ?>" 
                                           class="btn-delete-small" 
                                           title="删除"
                                           onclick="return confirm('确定要删除这道题目吗？此操作不可恢复！')">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($questions) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>暂无题目</h3>
                    <p>还没有录入任何题目，请使用上方表单添加新题目。</p>
                </div>
            <?php endif; ?>
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

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #c6f6d5;
    color: #276749;
    border: 1px solid #9ae6b4;
}

.alert-error {
    background: #fed7d7;
    color: #c53030;
    border: 1px solid #feb2b2;
}

.stats-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    flex: 1;
    min-width: 120px;
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
    font-size: 1.8rem;
    font-weight: bold;
    color: #667eea;
    display: block;
}

.stat-label {
    color: #718096;
    font-size: 0.85rem;
    margin-top: 5px;
    display: block;
}

.question-form .form-row {
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

.options-inputs {
    display: grid;
    gap: 10px;
}

.option-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.option-label {
    font-weight: bold;
    color: #4a5568;
    min-width: 25px;
}

.option-input {
    flex: 1;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.search-input {
    padding: 8px 15px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
    width: 250px;
}

.questions-table-container {
    overflow-x: auto;
}

.questions-table {
    width: 100%;
    border-collapse: collapse;
}

.questions-table th,
.questions-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.questions-table th {
    background: #f7fafc;
    font-weight: 600;
    color: #4a5568;
    font-size: 0.85rem;
    text-transform: uppercase;
}

.questions-table tr:hover {
    background: #f7fafc;
}

.question-title-cell {
    max-width: 300px;
    font-weight: 500;
    color: #2d3748;
}

.type-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.type-选择题 {
    background: #ebf4ff;
    color: #4299e1;
}

.type-填空题 {
    background: #c6f6d5;
    color: #276749;
}

.type-问答题 {
    background: #feebc8;
    color: #dd6b20;
}

.subject-tag {
    background: #e9d8fd;
    color: #6b46c1;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.difficulty-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.difficulty-easy {
    background: #c6f6d5;
    color: #276749;
}

.difficulty-medium {
    background: #feebc8;
    color: #dd6b20;
}

.difficulty-hard {
    background: #fed7d7;
    color: #e53e3e;
}

.tags-list {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.tag-item {
    background: #edf2f7;
    color: #4a5568;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.tag-more {
    color: #718096;
    font-size: 0.75rem;
}

.text-muted {
    color: #a0aec0;
}

.date-text {
    color: #718096;
    font-size: 0.85rem;
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-edit-small,
.btn-delete-small {
    padding: 5px 8px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background 0.2s ease;
}

.btn-edit-small {
    background: #ebf4ff;
}

.btn-edit-small:hover {
    background: #bee3f8;
}

.btn-delete-small {
    background: #fed7d7;
}

.btn-delete-small:hover {
    background: #feb2b2;
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
    .question-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-row {
        flex-direction: column;
    }
    
    .questions-table th,
    .questions-table td {
        padding: 8px;
        font-size: 0.8rem;
    }
    
    .search-input {
        width: 100%;
    }
}
</style>

<script>
// 题目类型变化时显示/隐藏选项
document.getElementById('question_type').addEventListener('change', function() {
    const optionsGroup = document.getElementById('options-group');
    if (this.value === '选择题') {
        optionsGroup.style.display = 'block';
    } else {
        optionsGroup.style.display = 'none';
    }
});

// 搜索功能
document.getElementById('search-input').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#questions-tbody tr');
    
    rows.forEach(row => {
        const searchText = row.getAttribute('data-search') || '';
        if (searchText.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
