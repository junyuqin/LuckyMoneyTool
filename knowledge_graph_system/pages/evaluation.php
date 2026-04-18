<?php
/**
 * 教学评估管理页面
 * 教师对课程进行评估和反馈，支持评估结果的统计分析
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

// 获取当前用户信息
$user_id = $_SESSION['user_id'];
$user_info = get_user_info($user_id);

// 检查是否为教师角色
if ($user_info['role'] !== 'teacher') {
    header('Location: ../index.php?error=permission_denied');
    exit;
}

// 设置页面标题
$page_title = '教学评估管理';

// 处理新增评估报告
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_evaluation') {
        $teacher_name = trim($_POST['teacher_name']);
        $course_name = trim($_POST['course_name']);
        $evaluation_date = $_POST['evaluation_date'];
        $content = trim($_POST['content']);
        $suggestions = trim($_POST['suggestions']);
        $score = (int)$_POST['score'];
        
        // 验证必填项
        if (empty($teacher_name) || empty($course_name) || empty($evaluation_date)) {
            $error_message = '请填写所有必填项！';
        } else {
            $result = add_teaching_evaluation([
                'teacher_id' => $user_id,
                'teacher_name' => $teacher_name,
                'course_name' => $course_name,
                'evaluation_date' => $evaluation_date,
                'content' => $content,
                'suggestions' => $suggestions,
                'score' => $score
            ]);
            
            if ($result) {
                $success_message = '评估报告提交成功！';
            } else {
                $error_message = '提交失败，请重试。';
            }
        }
    } elseif ($action === 'delete_evaluation') {
        $evaluation_id = (int)$_POST['evaluation_id'];
        
        if (delete_teaching_evaluation($evaluation_id)) {
            $success_message = '评估报告已删除！';
        } else {
            $error_message = '删除失败，请重试。';
        }
    } elseif ($action === 'update_evaluation') {
        $evaluation_id = (int)$_POST['evaluation_id'];
        $teacher_name = trim($_POST['teacher_name']);
        $course_name = trim($_POST['course_name']);
        $evaluation_date = $_POST['evaluation_date'];
        $content = trim($_POST['content']);
        $suggestions = trim($_POST['suggestions']);
        $score = (int)$_POST['score'];
        
        if (update_teaching_evaluation($evaluation_id, [
            'teacher_name' => $teacher_name,
            'course_name' => $course_name,
            'evaluation_date' => $evaluation_date,
            'content' => $content,
            'suggestions' => $suggestions,
            'score' => $score
        ])) {
            $success_message = '评估报告更新成功！';
        } else {
            $error_message = '更新失败，请重试。';
        }
    }
}

// 获取教师的评估报告列表
$evaluations = get_teacher_evaluations($user_id);

// 获取所有课程用于选择
$courses = get_all_courses();

// 编辑模式
$edit_mode = false;
$edit_evaluation = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_evaluation = get_evaluation_by_id($edit_id);
    if ($edit_evaluation && $edit_evaluation['teacher_id'] == $user_id) {
        $edit_mode = true;
    }
}

// 引入头部文件
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h2 class="page-title">📝 教学评估管理</h2>
        <p class="page-description">
            对课程进行评估和反馈，提供改进建议，促进教学质量提升。
        </p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- 统计概览 -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-value"><?php echo count($evaluations); ?></div>
            <div class="stat-label">评估报告总数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?php 
                $avg_score = 0;
                if (count($evaluations) > 0) {
                    $total = 0;
                    foreach ($evaluations as $e) {
                        $total += (int)$e['score'];
                    }
                    $avg_score = round($total / count($evaluations), 1);
                }
                echo $avg_score;
                ?>
            </div>
            <div class="stat-label">平均评分</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($courses); ?></div>
            <div class="stat-label">评估课程数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?php 
                $excellent = 0;
                foreach ($evaluations as $e) {
                    if ((int)$e['score'] >= 90) $excellent++;
                }
                echo $excellent;
                ?>
            </div>
            <div class="stat-label">优秀评价</div>
        </div>
    </div>
    
    <!-- 新增/编辑评估报告表单 -->
    <div class="form-section">
        <h3><?php echo $edit_mode ? '✏️ 编辑评估报告' : '➕ 新增评估报告'; ?></h3>
        
        <form method="post" action="" class="evaluation-form">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_evaluation' : 'add_evaluation'; ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="evaluation_id" value="<?php echo $edit_evaluation['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="teacher_name">教师姓名 <span class="required">*</span></label>
                    <input type="text" 
                           name="teacher_name" 
                           id="teacher_name" 
                           value="<?php echo $edit_mode ? htmlspecialchars($edit_evaluation['teacher_name']) : htmlspecialchars($user_info['username']); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="course_name">课程名称 <span class="required">*</span></label>
                    <select name="course_name" id="course_name" required>
                        <option value="">-- 请选择课程 --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['name']); ?>"
                                    <?php echo ($edit_mode && $edit_evaluation['course_name'] == $course['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="evaluation_date">评估日期 <span class="required">*</span></label>
                    <input type="date" 
                           name="evaluation_date" 
                           id="evaluation_date" 
                           value="<?php echo $edit_mode ? htmlspecialchars($edit_evaluation['evaluation_date']) : date('Y-m-d'); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="score">评分（0-100）</label>
                    <input type="number" 
                           name="score" 
                           id="score" 
                           min="0" 
                           max="100" 
                           value="<?php echo $edit_mode ? (int)$edit_evaluation['score'] : 85; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="content">评估内容</label>
                <textarea name="content" 
                          id="content" 
                          rows="5" 
                          placeholder="请描述课程的教学效果、学生表现等情况..."><?php echo $edit_mode ? htmlspecialchars($edit_evaluation['content']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="suggestions">改进建议</label>
                <textarea name="suggestions" 
                          id="suggestions" 
                          rows="4" 
                          placeholder="请提出具体的改进建议..."><?php echo $edit_mode ? htmlspecialchars($edit_evaluation['suggestions']) : ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? '💾 更新评估报告' : '📤 提交评估报告'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="?page=evaluation" class="btn btn-outline">取消编辑</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- 评估报告列表 -->
    <div class="list-section">
        <h3>📋 评估报告列表</h3>
        
        <?php if (empty($evaluations)): ?>
            <div class="empty-state">
                <p>暂无评估报告，请添加新的评估。</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>教师姓名</th>
                            <th>课程名称</th>
                            <th>评估日期</th>
                            <th>评分</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $evaluation): ?>
                            <tr>
                                <td><?php echo $evaluation['id']; ?></td>
                                <td><?php echo htmlspecialchars($evaluation['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars($evaluation['course_name']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($evaluation['evaluation_date'])); ?></td>
                                <td>
                                    <span class="score-badge score-<?php echo get_score_level($evaluation['score']); ?>">
                                        <?php echo $evaluation['score']; ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="?edit=<?php echo $evaluation['id']; ?>" 
                                       class="btn btn-sm btn-outline" title="编辑">
                                        ✏️ 编辑
                                    </a>
                                    <button class="btn btn-sm btn-outline" 
                                            onclick="viewEvaluation(<?php echo $evaluation['id']; ?>)" title="查看">
                                        👁️ 查看
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete(<?php echo $evaluation['id']; ?>)" title="删除">
                                        🗑️ 删除
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 评估详情弹窗 -->
    <div id="evaluation-modal" class="modal">
        <div class="modal-content modal-large">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div id="modal-body">
                <!-- 动态加载评估详情 -->
            </div>
        </div>
    </div>
    
    <!-- 使用说明 -->
    <div class="info-section">
        <h3>📖 使用说明</h3>
        <ul class="info-list">
            <li><strong>必填项：</strong>教师姓名、课程名称和评估日期为必填项，请确保填写完整。</li>
            <li><strong>评估内容：</strong>详细描述课程的教学效果、学生学习情况等内容。</li>
            <li><strong>改进建议：</strong>提出具体可行的改进建议，帮助提升教学质量。</li>
            <li><strong>评分标准：</strong>0-100分，90分以上为优秀，80-89分为良好，70-79分为中等。</li>
            <li><strong>编辑操作：</strong>点击编辑按钮可修改已有评估报告，请谨慎修改以免影响评估结果准确性。</li>
            <li><strong>定期查看：</strong>建议定期检查评估报告，及时了解课程的优缺点并进行调整。</li>
        </ul>
    </div>
</div>

<script>
// 查看评估详情
function viewEvaluation(evaluationId) {
    fetch(`/knowledge_graph_system/api/get_evaluation.php?id=${evaluationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const evalData = data.data;
                const modalBody = document.getElementById('modal-body');
                
                modalBody.innerHTML = `
                    <h3>评估报告详情</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>报告ID：</strong>${evalData.id}
                        </div>
                        <div class="detail-item">
                            <strong>教师姓名：</strong>${evalData.teacher_name}
                        </div>
                        <div class="detail-item">
                            <strong>课程名称：</strong>${evalData.course_name}
                        </div>
                        <div class="detail-item">
                            <strong>评估日期：</strong>${evalData.evaluation_date}
                        </div>
                        <div class="detail-item">
                            <strong>评分：</strong>
                            <span class="score-badge score-${getScoreLevel(evalData.score)}">${evalData.score}</span>
                        </div>
                    </div>
                    <div class="detail-section">
                        <h4>评估内容</h4>
                        <p>${evalData.content || '无'}</p>
                    </div>
                    <div class="detail-section">
                        <h4>改进建议</h4>
                        <p>${evalData.suggestions || '无'}</p>
                    </div>
                    <div class="modal-actions">
                        <a href="?edit=${evalData.id}" class="btn btn-primary">编辑此报告</a>
                        <button class="btn btn-outline" onclick="closeModal()">关闭</button>
                    </div>
                `;
                
                document.getElementById('evaluation-modal').style.display = 'block';
            } else {
                showToast('获取详情失败', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('网络错误', 'error');
        });
}

// 获取评分等级
function getScoreLevel(score) {
    if (score >= 90) return 'excellent';
    if (score >= 80) return 'good';
    if (score >= 70) return 'average';
    if (score >= 60) return 'pass';
    return 'fail';
}

// 确认删除
function confirmDelete(evaluationId) {
    if (confirm('确定要删除该评估报告吗？此操作不可撤销。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_evaluation';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'evaluation_id';
        idInput.value = evaluationId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// 关闭弹窗
function closeModal() {
    document.getElementById('evaluation-modal').style.display = 'none';
}

// 点击背景关闭弹窗
window.onclick = function(event) {
    const modal = document.getElementById('evaluation-modal');
    if (event.target === modal) {
        closeModal();
    }
};

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
