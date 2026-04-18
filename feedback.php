<?php
/**
 * 中职技能竞赛训练辅助系统 - 反馈与建议页面
 * 收集用户反馈和建议，持续优化系统功能
 */

$page_title = '反馈与建议';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$success_message = '';
$error_message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($name) || empty($email) || empty($content)) {
        $error_message = '请填写所有必填字段。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '请输入有效的邮箱地址。';
    } elseif (strlen($content) < 10) {
        $error_message = '反馈内容至少需要 10 个字符。';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO feedbacks (user_id, name, email, content, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$_SESSION['user_id'], $name, $email, $content]);
            $success_message = '感谢您的反馈！我们会认真考虑您的建议。';
        } catch (PDOException $e) {
            $error_message = '提交失败，请稍后重试。';
        }
    }
}

// 获取公开的反馈列表
$feedbacks_stmt = $db->query("
    SELECT f.*, u.role as user_role
    FROM feedbacks f
    LEFT JOIN users u ON f.user_id = u.id
    WHERE f.is_public = 1 AND f.status = 'replied'
    ORDER BY f.created_at DESC
    LIMIT 20
");
$public_feedbacks = $feedbacks_stmt->fetchAll();

// 获取用户自己的反馈
$user_feedbacks = [];
if ($is_logged_in) {
    $user_feedbacks_stmt = $db->prepare("
        SELECT * FROM feedbacks 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $user_feedbacks_stmt->execute([$_SESSION['user_id']]);
    $user_feedbacks = $user_feedbacks_stmt->fetchAll();
}

// 预填充用户信息
$prefill_name = '';
$prefill_email = '';
if ($is_logged_in && $current_user) {
    $prefill_name = $current_user['username'] ?? '';
    // 尝试从用户表或其他地方获取邮箱（这里简化处理）
}

?>

<div class="container">
    <div class="page-header">
        <h1>💬 反馈与建议</h1>
        <p>您的意见对我们非常重要，帮助我们持续优化系统功能</p>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            ✅ <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- 反馈表单 -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>📝 提交反馈</h2>
        </div>
        <div class="card-body">
            <?php if ($is_logged_in): ?>
                <form method="POST" action="feedback.php" class="feedback-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="feedback_name">您的姓名 *</label>
                            <input type="text" id="feedback_name" name="name" required 
                                   value="<?php echo htmlspecialchars($prefill_name); ?>"
                                   placeholder="请输入您的姓名" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="feedback_email">联系邮箱 *</label>
                            <input type="email" id="feedback_email" name="email" required 
                                   value="<?php echo htmlspecialchars($prefill_email); ?>"
                                   placeholder="请输入有效的邮箱地址" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback_type">反馈类型</label>
                        <select id="feedback_type" name="type" class="form-control">
                            <option value="建议">💡 功能建议</option>
                            <option value="问题">🐛 问题反馈</option>
                            <option value="其他">📝 其他</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback_content">反馈内容 *</label>
                        <textarea id="feedback_content" name="content" required rows="6"
                                  placeholder="请详细描述您的使用体验、遇到的问题或改进建议..."
                                  class="form-control"></textarea>
                        <small class="form-hint">请尽量详细描述，这将有助于我们更好地理解您的需求。</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_public" value="1">
                            <span>允许公开显示此反馈（匿名处理）</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">提交反馈</button>
                        <button type="reset" class="btn btn-secondary">重置</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="login-required">
                    <div class="login-icon">🔒</div>
                    <h3>请先登录</h3>
                    <p>您需要登录后才能提交反馈。</p>
                    <a href="login.php" class="btn btn-primary">去登录</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 我的反馈记录 -->
    <?php if ($is_logged_in && count($user_feedbacks) > 0): ?>
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h2>📋 我的反馈记录</h2>
            </div>
            <div class="card-body">
                <div class="feedback-list">
                    <?php foreach ($user_feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <span class="feedback-type-badge type-<?php echo htmlspecialchars($feedback['type'] ?? '其他'); ?>">
                                    <?php 
                                    $type_icons = ['建议' => '💡', '问题' => '🐛', '其他' => '📝'];
                                    echo ($type_icons[$feedback['type']] ?? '📝') . ' ';
                                    echo htmlspecialchars($feedback['type'] ?? '其他');
                                    ?>
                                </span>
                                <span class="feedback-status status-<?php echo htmlspecialchars($feedback['status']); ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => '⏳ 待处理',
                                        'processing' => '🔄 处理中',
                                        'replied' => '✅ 已回复',
                                        'closed' => '🔒 已关闭'
                                    ];
                                    echo $status_labels[$feedback['status']] ?? '待处理';
                                    ?>
                                </span>
                                <span class="feedback-date">
                                    <?php echo date('Y-m-d H:i', strtotime($feedback['created_at'])); ?>
                                </span>
                            </div>
                            <div class="feedback-content">
                                <?php echo nl2br(htmlspecialchars($feedback['content'])); ?>
                            </div>
                            <?php if (!empty($feedback['reply'])): ?>
                                <div class="feedback-reply">
                                    <strong>📌 官方回复：</strong>
                                    <p><?php echo nl2br(htmlspecialchars($feedback['reply'])); ?></p>
                                    <span class="reply-date">
                                        <?php echo date('Y-m-d H:i', strtotime($feedback['updated_at'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- 公开反馈展示 -->
    <?php if (count($public_feedbacks) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h2>🌟 精选反馈</h2>
                <span class="badge"><?php echo count($public_feedbacks); ?> 条</span>
            </div>
            <div class="card-body">
                <div class="public-feedback-list">
                    <?php foreach ($public_feedbacks as $index => $feedback): ?>
                        <div class="public-feedback-item">
                            <div class="feedback-user-info">
                                <div class="user-avatar-small">
                                    <?php echo mb_substr($feedback['name'], 0, 1); ?>
                                </div>
                                <div class="user-meta">
                                    <div class="user-name-display">
                                        <?php echo htmlspecialchars(mb_substr($feedback['name'], 0, 1)) . '**'; ?>
                                        <?php if ($feedback['user_role'] === 'admin'): ?>
                                            <span class="role-badge">教师</span>
                                        <?php else: ?>
                                            <span class="role-badge student">学生</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="feedback-time">
                                        <?php 
                                        $time_ago = time() - strtotime($feedback['created_at']);
                                        if ($time_ago < 3600) {
                                            echo floor($time_ago / 60) . ' 分钟前';
                                        } elseif ($time_ago < 86400) {
                                            echo floor($time_ago / 3600) . ' 小时前';
                                        } else {
                                            echo date('m-d', strtotime($feedback['created_at']));
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="public-feedback-content">
                                <?php echo nl2br(htmlspecialchars($feedback['content'])); ?>
                            </div>
                            <?php if (!empty($feedback['reply'])): ?>
                                <div class="official-reply">
                                    <div class="reply-header">
                                        <span class="official-badge">📌 官方回复</span>
                                    </div>
                                    <div class="reply-content">
                                        <?php echo nl2br(htmlspecialchars($feedback['reply'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="feedback-footer">
                                <span class="helpful-count">
                                    👍 <?php echo $feedback['helpful_count'] ?? 0; ?> 人觉得有帮助
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- 常见问题 -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>❓ 常见问题</h2>
        </div>
        <div class="card-body">
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <span class="faq-icon">❓</span>
                        <strong>反馈后多久能得到回复？</strong>
                    </div>
                    <div class="faq-answer">
                        我们通常会在 1-3 个工作日内处理并回复您的反馈。对于紧急问题，我们会尽快处理。
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <span class="faq-icon">❓</span>
                        <strong>如何查看我的反馈处理进度？</strong>
                    </div>
                    <div class="faq-answer">
                        登录后，您可以在本页面的"我的反馈记录"区域查看所有提交的反馈及其处理状态。
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <span class="faq-icon">❓</span>
                        <strong>什么样的反馈会被公开显示？</strong>
                    </div>
                    <div class="faq-answer">
                        只有您勾选"允许公开显示"且被管理员标记为"已回复"的反馈才会公开显示。公开时会隐藏您的个人信息。
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <span class="faq-icon">❓</span>
                        <strong>如何联系人工客服？</strong>
                    </div>
                    <div class="faq-answer">
                        如有紧急问题，请发送邮件至 support@vocational-skills.edu.cn，或通过学校相关部门联系我们。
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

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
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

.feedback-form .form-row {
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    color: #4a5568;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.login-required {
    text-align: center;
    padding: 40px 20px;
}

.login-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.login-required h3 {
    color: #2d3748;
    margin-bottom: 10px;
}

.login-required p {
    color: #718096;
    margin-bottom: 20px;
}

.feedback-list,
.public-feedback-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.feedback-item,
.public-feedback-item {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    background: white;
}

.feedback-header,
.public-feedback-item > .feedback-user-info {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.feedback-type-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.type-建议 {
    background: #ebf4ff;
    color: #4299e1;
}

.type-问题 {
    background: #fed7d7;
    color: #e53e3e;
}

.type-其他 {
    background: #e2e8f0;
    color: #4a5568;
}

.feedback-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background: #feebc8;
    color: #dd6b20;
}

.status-processing {
    background: #bee3f8;
    color: #2b6cb0;
}

.status-replied {
    background: #c6f6d5;
    color: #276749;
}

.status-closed {
    background: #e2e8f0;
    color: #4a5568;
}

.feedback-date,
.feedback-time {
    color: #a0aec0;
    font-size: 0.85rem;
}

.feedback-content,
.public-feedback-content {
    color: #4a5568;
    line-height: 1.7;
    margin-bottom: 15px;
}

.feedback-reply,
.official-reply {
    background: #ebf8ff;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #4299e1;
}

.feedback-reply strong,
.official-badge {
    color: #2b6cb0;
}

.official-badge {
    display: inline-block;
    margin-bottom: 10px;
    font-weight: 600;
}

.reply-content,
.feedback-reply p {
    margin: 10px 0 0 0;
    color: #4a5568;
    line-height: 1.6;
}

.reply-date {
    display: block;
    margin-top: 10px;
    color: #a0aec0;
    font-size: 0.85rem;
}

.user-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.user-meta {
    flex: 1;
}

.user-name-display {
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 8px;
}

.role-badge {
    background: #667eea;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
}

.role-badge.student {
    background: #48bb78;
}

.feedback-footer {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}

.helpful-count {
    color: #718096;
    font-size: 0.9rem;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.faq-item {
    padding: 15px;
    background: #f7fafc;
    border-radius: 8px;
}

.faq-question {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.faq-icon {
    font-size: 1.2rem;
}

.faq-question strong {
    color: #2d3748;
}

.faq-answer {
    color: #4a5568;
    line-height: 1.6;
    padding-left: 30px;
}

@media (max-width: 768px) {
    .feedback-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .feedback-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
