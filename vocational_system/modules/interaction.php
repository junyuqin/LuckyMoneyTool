<?php
/**
 * 教师互动交流页面
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

// 处理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = sanitizeInput($_POST['content'] ?? '');
    $isAnnouncement = ($user['role'] === 'teacher' || $user['role'] === 'admin') && isset($_POST['is_announcement']);
    
    if (empty($content)) {
        $error = '请输入留言内容';
    } elseif (strlen($content) > 1000) {
        $error = '留言内容不能超过 1000 字';
    } else {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("INSERT INTO interactions 
                              (user_id, user_name, user_role, content, is_announcement) 
                              VALUES (:user_id, :user_name, :user_role, :content, :is_announcement)");
        
        $result = $stmt->execute([
            ':user_id' => $user['id'],
            ':user_name' => $user['username'] ?? $user['phone'],
            ':user_role' => $user['role'],
            ':content' => $content,
            ':is_announcement' => $isAnnouncement ? 1 : 0
        ]);
        
        if ($result) {
            $success = $isAnnouncement ? '公告发布成功！' : '留言提交成功！';
            logSystemAction($user['id'], 'interaction', '发布' . ($isAnnouncement ? '公告' : '留言'));
        } else {
            $error = '提交失败，请稍后重试';
        }
    }
}

// 获取留言列表
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT i.*, u.username as sender_username 
                      FROM interactions i 
                      LEFT JOIN users u ON i.user_id = u.id 
                      WHERE i.parent_id IS NULL 
                      ORDER BY i.is_announcement DESC, i.created_at DESC 
                      LIMIT 50");
$stmt->execute();
$interactions = $stmt->fetchAll();

// 获取回复列表
$stmt = $pdo->prepare("SELECT i.*, u.username as sender_username 
                      FROM interactions i 
                      LEFT JOIN users u ON i.user_id = u.id 
                      WHERE i.parent_id IS NOT NULL 
                      ORDER BY i.created_at ASC");
$stmt->execute();
$replies = $stmt->fetchAll();

// 组织回复数据
$replyMap = [];
foreach ($replies as $reply) {
    $parentId = $reply['parent_id'];
    if (!isset($replyMap[$parentId])) {
        $replyMap[$parentId] = [];
    }
    $replyMap[$parentId][] = $reply;
}

$pageTitle = '教师互动交流';
$currentModule = 'interaction';
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
                <i class="fas fa-comments"></i> 教师互动交流
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
            
            <!-- 发布公告/留言区域 -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> 发布公告/留言</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="interactionForm">
                        <div class="form-group">
                            <label for="content">
                                留言内容 <span class="required">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="content" 
                                name="content" 
                                rows="4"
                                placeholder="<?php echo $user['role'] === 'teacher' || $user['role'] === 'admin' ? '请输入公告或留言内容...' : '请输入您的问题或反馈...'; ?>"
                                required
                            ></textarea>
                            <small class="text-muted">留言长度限制：1000 字以内</small>
                        </div>
                        
                        <?php if ($user['role'] === 'teacher' || $user['role'] === 'admin'): ?>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input 
                                    type="checkbox" 
                                    id="is_announcement" 
                                    name="is_announcement"
                                >
                                <label for="is_announcement">设为公告（所有用户可见）</label>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> 提交
                            </button>
                            <button type="reset" class="btn btn-outline">
                                <i class="fas fa-redo"></i> 重置
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 留言反馈区域 -->
            <div class="card mt-20">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> 留言反馈列表</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($interactions)): ?>
                        <p class="text-muted text-center">暂无留言</p>
                    <?php else: ?>
                        <div class="interaction-list">
                            <?php foreach ($interactions as $interaction): ?>
                                <div class="interaction-item" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <div>
                                            <strong style="font-size: 16px;">
                                                <?php if ($interaction['is_announcement']): ?>
                                                    <i class="fas fa-bullhorn" style="color: var(--primary-color);"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user-circle" style="color: var(--text-muted);"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($interaction['sender_username'] ?? $interaction['user_name'] ?? '未知'); ?>
                                            </strong>
                                            <span class="badge" style="background: var(--light-color); padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px;">
                                                <?php 
                                                $roleNames = ['student' => '学生', 'teacher' => '教师', 'admin' => '管理员'];
                                                echo $roleNames[$interaction['user_role']] ?? $interaction['user_role']; 
                                                ?>
                                            </span>
                                        </div>
                                        <span class="text-muted" style="font-size: 12px;">
                                            <?php echo formatDateTime($interaction['created_at']); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px; line-height: 1.8;">
                                        <?php echo nl2br(htmlspecialchars($interaction['content'])); ?>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button class="btn btn-sm btn-outline" onclick="showReplyForm(<?php echo $interaction['id']; ?>)">
                                            <i class="fas fa-reply"></i> 回复
                                        </button>
                                        <?php if ($user['id'] === $interaction['user_id'] || $user['role'] === 'admin'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteInteraction(<?php echo $interaction['id']; ?>)">
                                            <i class="fas fa-trash"></i> 删除
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 回复表单（隐藏） -->
                                    <div id="replyForm<?php echo $interaction['id']; ?>" class="reply-form" style="display: none; margin-top: 15px; padding: 15px; background: var(--light-color); border-radius: 4px;">
                                        <form method="POST" action="../api/reply_api.php">
                                            <input type="hidden" name="parent_id" value="<?php echo $interaction['id']; ?>">
                                            <div class="form-group" style="margin-bottom: 10px;">
                                                <textarea 
                                                    class="form-control" 
                                                    name="content" 
                                                    rows="2"
                                                    placeholder="输入回复内容..."
                                                    required
                                                ></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-paper-plane"></i> 提交回复
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline" onclick="hideReplyForm(<?php echo $interaction['id']; ?>)">
                                                取消
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- 回复列表 -->
                                    <?php if (isset($replyMap[$interaction['id']]) && !empty($replyMap[$interaction['id']])): ?>
                                        <div class="replies" style="margin-top: 15px; padding-left: 20px; border-left: 2px solid var(--border-color);">
                                            <?php foreach ($replyMap[$interaction['id']] as $reply): ?>
                                                <div class="reply-item" style="padding: 10px; margin-bottom: 10px; background: var(--light-color); border-radius: 4px;">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                        <strong style="font-size: 14px;">
                                                            <?php echo htmlspecialchars($reply['sender_username'] ?? $reply['user_name'] ?? '未知'); ?>
                                                        </strong>
                                                        <span class="text-muted" style="font-size: 11px;">
                                                            <?php echo formatDateTime($reply['created_at'], 'Y-m-d H:i'); ?>
                                                        </span>
                                                    </div>
                                                    <p style="margin: 0; font-size: 14px;">
                                                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                                    </p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                            <li><i class="fas fa-bullhorn" style="color: var(--primary-color);"></i> 发布公告：教师和管理员可以发布重要通知</li>
                            <li><i class="fas fa-comment" style="color: var(--success-color);"></i> 留言互动：所有用户可以发表问题和反馈</li>
                            <li><i class="fas fa-reply" style="color: var(--warning-color);"></i> 回复功能：支持对留言进行回复讨论</li>
                        </ul>
                        
                        <p class="mt-20"><strong>注意事项：</strong></p>
                        <ul>
                            <li><i class="fas fa-exclamation-circle" style="color: var(--danger-color);"></i> 请确保信息清晰礼貌，避免使用不当语言</li>
                            <li><i class="fas fa-ruler" style="color: var(--info-color);"></i> 留言长度应适度，确保信息易于阅读</li>
                            <li><i class="fas fa-users" style="color: var(--success-color);"></i> 积极参与互动，共同推动学习效果提升</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function showReplyForm(id) {
            document.getElementById('replyForm' + id).style.display = 'block';
        }
        
        function hideReplyForm(id) {
            document.getElementById('replyForm' + id).style.display = 'none';
        }
        
        function deleteInteraction(id) {
            if (confirm('确定要删除这条留言吗？此操作不可恢复。')) {
                fetch('../api/interaction_api.php', {
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
