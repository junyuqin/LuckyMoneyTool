<?php
/**
 * 用户反馈与建议页面
 * 收集用户对系统的使用体验和功能建议
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
$page_title = '用户反馈与建议';

// 获取当前用户信息
$user_id = $_SESSION['user_id'];
$user_info = get_user_info($user_id);

// 处理提交反馈
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $feedback_content = trim($_POST['feedback_content']);
    $feedback_type = $_POST['feedback_type'];
    $contact_email = trim($_POST['contact_email']);
    
    // 验证必填项
    if (empty($username)) {
        $error_message = '用户名不能为空！';
    } elseif (empty($feedback_content)) {
        $error_message = '反馈内容不能为空！';
    } elseif (strlen($feedback_content) < 10) {
        $error_message = '反馈内容太短，请至少输入10个字符！';
    } else {
        // 提交反馈
        $result = submit_feedback([
            'user_id' => $user_id,
            'username' => $username,
            'content' => $feedback_content,
            'type' => $feedback_type,
            'contact_email' => $contact_email
        ]);
        
        if ($result) {
            $success_message = '感谢您的反馈！我们会认真考虑您的建议。';
            // 清空表单
            $_POST = [];
        } else {
            $error_message = '提交失败，请重试。';
        }
    }
}

// 获取用户的历史反馈
$user_feedbacks = get_user_feedbacks($user_id);

// 引入头部文件
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h2 class="page-title">💬 用户反馈与建议</h2>
        <p class="page-description">
            欢迎提交您对系统的使用体验和功能建议，帮助我们不断改进和优化。
        </p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- 反馈表单区域 -->
    <div class="feedback-form-section">
        <h3>📝 提交反馈</h3>
        <p class="section-description">
            请详细描述您遇到的问题或提出的建议，这将帮助我们更好地改进系统。
        </p>
        
        <form method="post" action="" class="feedback-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">用户名 <span class="required">*</span></label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : htmlspecialchars($user_info['username']); ?>"
                           required
                           readonly>
                    <small class="form-hint">用户名自动填充，不可修改</small>
                </div>
                
                <div class="form-group">
                    <label for="feedback_type">反馈类型</label>
                    <select name="feedback_type" id="feedback_type">
                        <option value="suggestion" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'suggestion') ? 'selected' : ''; ?>>💡 功能建议</option>
                        <option value="bug" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'bug') ? 'selected' : ''; ?>>🐛 问题报告</option>
                        <option value="improvement" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'improvement') ? 'selected' : ''; ?>>🔧 功能优化</option>
                        <option value="other" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'other') ? 'selected' : ''; ?>>📌 其他</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="feedback_content">反馈内容 <span class="required">*</span></label>
                <textarea name="feedback_content" 
                          id="feedback_content" 
                          rows="8" 
                          placeholder="请详细描述您的问题或建议...&#10;&#10;例如：&#10;1. 在使用 XX 功能时遇到了什么问题&#10;2. 希望增加什么样的新功能&#10;3. 对现有功能的具体改进建议"
                          required><?php echo isset($_POST['feedback_content']) ? htmlspecialchars($_POST['feedback_content']) : ''; ?></textarea>
                <small class="form-hint">请至少输入 10 个字符，描述越详细越好</small>
            </div>
            
            <div class="form-group">
                <label for="contact_email">联系邮箱（选填）</label>
                <input type="email" 
                       name="contact_email" 
                       id="contact_email" 
                       value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : htmlspecialchars($user_info['email'] ?? ''); ?>"
                       placeholder="方便我们与您联系，进一步了解情况">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">📤 提交反馈</button>
                <button type="reset" class="btn btn-outline">🔄 重置表单</button>
            </div>
        </form>
    </div>
    
    <!-- 我的反馈历史 -->
    <div class="feedback-history-section">
        <h3>📋 我的反馈历史</h3>
        <p class="section-description">
            查看您之前提交的反馈及其处理状态。
        </p>
        
        <?php if (empty($user_feedbacks)): ?>
            <div class="empty-state">
                <p>暂无反馈记录，欢迎您提交第一条反馈！</p>
            </div>
        <?php else: ?>
            <div class="feedback-list">
                <?php foreach ($user_feedbacks as $feedback): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <span class="feedback-type-badge type-<?php echo htmlspecialchars($feedback['type']); ?>">
                                <?php echo get_feedback_type_name($feedback['type']); ?>
                            </span>
                            <span class="feedback-status status-<?php echo htmlspecialchars($feedback['status']); ?>">
                                <?php echo get_feedback_status_name($feedback['status']); ?>
                            </span>
                            <span class="feedback-date">
                                📅 <?php echo date('Y-m-d H:i', strtotime($feedback['created_at'])); ?>
                            </span>
                        </div>
                        <div class="feedback-content">
                            <?php echo nl2br(htmlspecialchars($feedback['content'])); ?>
                        </div>
                        <?php if (!empty($feedback['admin_reply'])): ?>
                            <div class="admin-reply">
                                <strong>👨‍💼 管理员回复：</strong>
                                <p><?php echo nl2br(htmlspecialchars($feedback['admin_reply'])); ?></p>
                                <small class="reply-time">
                                    回复时间：<?php echo date('Y-m-d H:i', strtotime($feedback['reply_time'])); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 常见问题 -->
    <div class="faq-section">
        <h3>❓ 常见问题</h3>
        
        <div class="faq-item">
            <div class="faq-question">
                <span class="faq-icon">❓</span>
                <strong>反馈后多久能得到回复？</strong>
            </div>
            <div class="faq-answer">
                <p>我们通常会在 1-3 个工作日内处理您的反馈。对于紧急问题，我们会尽快响应。</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <span class="faq-icon">❓</span>
                <strong>如何查看反馈的处理进度？</strong>
            </div>
            <div class="faq-answer">
                <p>您可以在本页面的"我的反馈历史"区域查看所有已提交反馈的状态和处理结果。</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <span class="faq-icon">❓</span>
                <strong>反馈内容有什么要求吗？</strong>
            </div>
            <div class="faq-answer">
                <p>请确保反馈内容真实、具体、有建设性。避免发布无关、重复或不当内容。描述越详细，我们越能快速定位和解决问题。</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <span class="faq-icon">❓</span>
                <strong>我的反馈会被公开吗？</strong>
            </div>
            <div class="faq-answer">
                <p>您的反馈内容仅对系统管理员可见，不会被公开显示。我们会严格保护用户的隐私信息。</p>
            </div>
        </div>
    </div>
    
    <!-- 使用说明 -->
    <div class="info-section">
        <h3>📖 使用提示</h3>
        <ul class="info-list">
            <li><strong>必填字段：</strong>用户名和反馈内容为必填项，请确保填写完整。</li>
            <li><strong>详细描述：</strong>反馈内容应清晰具体，便于开发团队准确理解您的需求。</li>
            <li><strong>联系方式：</strong>建议填写联系邮箱，方便我们与您进一步沟通。</li>
            <li><strong>反馈类型：</strong>选择合适的反馈类型有助于我们快速分类处理。</li>
            <li><strong>定期查看：</strong>建议定期查看反馈历史，了解处理进度和回复。</li>
            <li><strong>建设性意见：</strong>提供详细且有建设性的意见，将极大地帮助系统优化和改进。</li>
        </ul>
        <p class="info-note">
            💡 感谢您对中职专业课程知识图谱系统的支持与信任！
            您的每一条反馈都是我们改进和提升用户体验的重要依据。
        </p>
    </div>
</div>

<?php
/**
 * 获取反馈类型名称
 */
function get_feedback_type_name($type) {
    $types = [
        'suggestion' => '💡 功能建议',
        'bug' => '🐛 问题报告',
        'improvement' => '🔧 功能优化',
        'other' => '📌 其他'
    ];
    return $types[$type] ?? '📌 其他';
}

/**
 * 获取反馈状态名称
 */
function get_feedback_status_name($status) {
    $statuses = [
        'pending' => '⏳ 待处理',
        'processing' => '🔄 处理中',
        'resolved' => '✅ 已解决',
        'rejected' => '❌ 已拒绝'
    ];
    return $statuses[$status] ?? '⏳ 待处理';
}
?>

<script>
// 表单验证
document.querySelector('.feedback-form').addEventListener('submit', function(e) {
    const content = document.getElementById('feedback_content').value.trim();
    
    if (content.length < 10) {
        e.preventDefault();
        showToast('反馈内容太短，请至少输入 10 个字符', 'error');
        document.getElementById('feedback_content').focus();
        return false;
    }
    
    // 显示提交确认
    if (!confirm('确定要提交反馈吗？')) {
        e.preventDefault();
        return false;
    }
});

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
