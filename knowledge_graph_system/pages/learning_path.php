<?php
/**
 * 学习路径推荐页面
 * 为用户提供个性化的学习计划和学习资源推荐
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
$page_title = '学习路径推荐';

// 获取当前用户ID
$user_id = $_SESSION['user_id'];

// 获取用户信息
$user_info = get_user_info($user_id);

// 处理添加学习资源请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_resource') {
        $resource_id = (int)$_POST['resource_id'];
        $position = (int)$_POST['position'];
        
        if (add_learning_path_resource($user_id, $resource_id, $position)) {
            $success_message = '学习资源添加成功！';
        } else {
            $error_message = '添加失败，请重试。';
        }
    } elseif ($action === 'remove_resource') {
        $path_detail_id = (int)$_POST['path_detail_id'];
        
        if (remove_learning_path_resource($path_detail_id)) {
            $success_message = '学习资源已移除！';
        } else {
            $error_message = '移除失败，请重试。';
        }
    } elseif ($action === 'update_position') {
        $path_detail_id = (int)$_POST['path_detail_id'];
        $new_position = (int)$_POST['new_position'];
        
        if (update_learning_path_position($path_detail_id, $new_position)) {
            $success_message = '学习顺序已更新！';
        } else {
            $error_message = '更新失败，请重试。';
        }
    }
}

// 获取用户的学习路径
$learning_path = get_user_learning_path($user_id);

// 获取所有可用资源
$all_resources = get_all_course_resources();

// 获取推荐的学习路径（基于用户学习进度）
$recommended_path = get_recommended_learning_path($user_id);

// 引入头部文件
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h2 class="page-title">🎯 学习路径推荐</h2>
        <p class="page-description">
            根据您的学习进度和知识掌握情况，为您提供个性化的学习计划。
        </p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- 推荐学习路径区域 -->
    <div class="recommendation-section">
        <h3>✨ 为您推荐的学习路径</h3>
        <p class="section-description">
            以下学习资源是根据您当前的学习进度智能推荐的，建议按照顺序学习。
        </p>
        
        <?php if (empty($recommended_path)): ?>
            <div class="empty-state">
                <p>暂无推荐内容，请先完成一些学习任务以获取个性化推荐。</p>
            </div>
        <?php else: ?>
            <div class="path-list">
                <?php foreach ($recommended_path as $index => $item): ?>
                    <div class="path-item">
                        <div class="path-order"><?php echo $index + 1; ?></div>
                        <div class="path-info">
                            <h4><?php echo htmlspecialchars($item['resource_name']); ?></h4>
                            <p class="path-meta">
                                <span class="type-badge"><?php echo htmlspecialchars($item['resource_type']); ?></span>
                                <span>📅 预计学习时间：<?php echo $item['estimated_time']; ?>分钟</span>
                            </p>
                            <p class="path-desc"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 100)); ?>...</p>
                        </div>
                        <div class="path-actions">
                            <a href="<?php echo htmlspecialchars($item['file_path']); ?>" 
                               class="btn btn-primary" target="_blank">查看</a>
                            <button class="btn btn-outline btn-sm" 
                                    onclick="addToMyPath(<?php echo $item['id']; ?>, <?php echo count($learning_path) + 1; ?>)">
                                加入我的路径
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 我的学习路径区域 -->
    <div class="my-path-section">
        <h3>📋 我的学习路径</h3>
        <p class="section-description">
            您自定义的学习计划，可以随时调整学习顺序和时间安排。
        </p>
        
        <?php if (empty($learning_path)): ?>
            <div class="empty-state">
                <p>暂无学习内容，请从推荐列表中添加学习资源。</p>
            </div>
        <?php else: ?>
            <div class="path-list custom-path">
                <form method="post" action="" id="path-form">
                    <?php foreach ($learning_path as $index => $item): ?>
                        <div class="path-item">
                            <div class="path-order"><?php echo $index + 1; ?></div>
                            <div class="path-info">
                                <h4><?php echo htmlspecialchars($item['resource_name']); ?></h4>
                                <p class="path-meta">
                                    <span class="type-badge"><?php echo htmlspecialchars($item['resource_type']); ?></span>
                                    <span>📊 完成度：<?php echo $item['progress']; ?>%</span>
                                </p>
                                <p class="path-desc"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 80)); ?>...</p>
                            </div>
                            <div class="path-actions">
                                <a href="<?php echo htmlspecialchars($item['file_path']); ?>" 
                                   class="btn btn-primary btn-sm" target="_blank">查看</a>
                                <select name="positions[<?php echo $item['path_detail_id']; ?>]" 
                                        class="position-select"
                                        onchange="updatePosition(this, <?php echo $item['path_detail_id']; ?>)">
                                    <?php for ($i = 1; $i <= count($learning_path); $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo $i == $index + 1 ? 'selected' : ''; ?>>
                                            第<?php echo $i; ?>位
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <button type="button" 
                                        class="btn btn-danger btn-sm"
                                        onclick="confirmRemove(<?php echo $item['path_detail_id']; ?>)">
                                    删除
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 添加学习资源区域 -->
    <div class="add-resource-section">
        <h3>➕ 添加学习资源</h3>
        <p class="section-description">
            从课程资源库中选择需要添加到学习计划的资源。
        </p>
        
        <form method="post" action="" class="add-resource-form">
            <input type="hidden" name="action" value="add_resource">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="resource_select">选择资源：</label>
                    <select name="resource_id" id="resource_select" required>
                        <option value="">-- 请选择 --</option>
                        <?php foreach ($all_resources as $resource): ?>
                            <option value="<?php echo $resource['id']; ?>">
                                <?php echo htmlspecialchars($resource['name']); ?> 
                                (<?php echo htmlspecialchars($resource['resource_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="position_input">学习顺序：</label>
                    <input type="number" 
                           name="position" 
                           id="position_input" 
                           min="1" 
                           max="100" 
                           value="<?php echo count($learning_path) + 1; ?>"
                           required>
                </div>
                
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">添加学习资源</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 学习计划管理提示 -->
    <div class="tips-section">
        <h3>💡 使用提示</h3>
        <ul class="tips-list">
            <li><strong>个性化推荐：</strong>系统会根据您的学习进度和成绩智能推荐学习资源。</li>
            <li><strong>灵活调整：</strong>您可以随时修改学习路径中的资源顺序，以适应自己的学习节奏。</li>
            <li><strong>定期更新：</strong>建议定期查看推荐列表，获取最新的学习资源。</li>
            <li><strong>完成跟踪：</strong>系统会自动记录您的学习进度，帮助您了解完成情况。</li>
            <li><strong>删除操作：</strong>移除不再需要的学习资源，保持学习路径的简洁性。</li>
        </ul>
    </div>
    
    <!-- 学习统计 -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-value"><?php echo count($learning_path); ?></div>
            <div class="stat-label">我的学习资源数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($recommended_path); ?></div>
            <div class="stat-label">推荐资源数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?php 
                $completed = 0;
                foreach ($learning_path as $item) {
                    if ($item['progress'] >= 100) $completed++;
                }
                echo $completed;
                ?>
            </div>
            <div class="stat-label">已完成资源</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?php 
                $total_progress = 0;
                if (count($learning_path) > 0) {
                    foreach ($learning_path as $item) {
                        $total_progress += (int)$item['progress'];
                    }
                    echo round($total_progress / count($learning_path));
                } else {
                    echo 0;
                }
                ?>%
            </div>
            <div class="stat-label">平均完成度</div>
        </div>
    </div>
</div>

<script>
// 添加到学习路径
function addToMyPath(resourceId, position) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'add_resource';
    
    const resourceInput = document.createElement('input');
    resourceInput.type = 'hidden';
    resourceInput.name = 'resource_id';
    resourceInput.value = resourceId;
    
    const positionInput = document.createElement('input');
    positionInput.type = 'hidden';
    positionInput.name = 'position';
    positionInput.value = position;
    
    form.appendChild(actionInput);
    form.appendChild(resourceInput);
    form.appendChild(positionInput);
    document.body.appendChild(form);
    form.submit();
}

// 更新位置
function updatePosition(selectElement, pathDetailId) {
    const newPosition = selectElement.value;
    
    if (confirm('确定要调整该资源的学习顺序吗？')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_position';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'path_detail_id';
        idInput.value = pathDetailId;
        
        const positionInput = document.createElement('input');
        positionInput.type = 'hidden';
        positionInput.name = 'new_position';
        positionInput.value = newPosition;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(positionInput);
        document.body.appendChild(form);
        form.submit();
    } else {
        // 恢复原值
        selectElement.value = <?php echo $index + 1; ?>;
    }
}

// 确认删除
function confirmRemove(pathDetailId) {
    if (confirm('确定要移除该学习资源吗？此操作不可撤销。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'remove_resource';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'path_detail_id';
        idInput.value = pathDetailId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

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
