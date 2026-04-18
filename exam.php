<?php
/**
 * 中职技能竞赛训练辅助系统 - 在线模拟考试页面
 */
require_once __DIR__ . '/includes/config.php';

// 初始化数据库
checkAndInitializeDatabase();

session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$db = getDBConnection();
$user = null;
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// 获取所有科目和考试类型
$subjects = [
    'math' => '数学',
    'english' => '英语',
    'programming' => '编程'
];

$examTypes = [
    'mock' => '模拟考试',
    'final' => '期末考试'
];

// 处理开始考试请求
$examStarted = false;
$examData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    $subject = $_POST['subject'] ?? '';
    $examType = $_POST['exam_type'] ?? '';
    
    if ($subject && $examType) {
        // 查找或创建考试
        $stmt = $db->prepare("
            SELECT * FROM exams 
            WHERE subject = ? AND exam_type = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$subject, $examType]);
        $exam = $stmt->fetch();
        
        if ($exam) {
            // 创建用户考试记录
            $db->prepare("
                INSERT INTO user_exams (user_id, exam_id, status)
                VALUES (?, ?, 'in_progress')
            ")->execute([$user['id'], $exam['id']]);
            
            $userExamId = $db->lastInsertId();
            
            // 获取考试题目
            $stmt = $db->prepare("
                SELECT q.*, eq.score, eq.question_order
                FROM questions q
                JOIN exam_questions eq ON q.id = eq.question_id
                WHERE eq.exam_id = ?
                ORDER BY eq.question_order
            ");
            $stmt->execute([$exam['id']]);
            $questions = $stmt->fetchAll();
            
            // 如果没有预设题目，随机选择
            if (empty($questions)) {
                $stmt = $db->prepare("
                    SELECT * FROM questions 
                    WHERE category = ?
                    ORDER BY RANDOM()
                    LIMIT 10
                ");
                $stmt->execute([$subject]);
                $questions = $stmt->fetchAll();
            }
            
            $examStarted = true;
            $examData = [
                'exam' => $exam,
                'user_exam_id' => $userExamId,
                'questions' => $questions
            ];
        }
    }
}

// 获取可用考试列表
$availableExams = $db->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as question_count
    FROM exams e
    WHERE e.status = 'active'
    ORDER BY e.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线模拟考试 - 中职技能竞赛训练辅助系统</title>
    <link rel="stylesheet" href="assets/css/style.css.php">
    <style>
        .exam-setup { max-width: 600px; margin: 0 auto; }
        .exam-paper { display: none; }
        .exam-paper.active { display: block; }
        .timer { position: fixed; top: 80px; right: 20px; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); font-size: 18px; font-weight: bold; }
        .timer.warning { color: #e74c3c; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .question-nav { position: fixed; bottom: 20px; right: 20px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .question-nav-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; }
        .question-nav-item { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .question-nav-item.current { border-color: var(--primary-color); background: rgba(52, 152, 219, 0.1); }
        .question-nav-item.answered { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .exam-result { text-align: center; padding: 40px; }
        .score-display { font-size: 72px; font-weight: bold; color: var(--primary-color); margin: 20px 0; }
        .score-pass { color: var(--secondary-color); }
        .score-fail { color: var(--danger-color); }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">
                <span class="logo-icon">📚</span>
                <span>中职技能竞赛训练辅助系统</span>
            </a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">首页</a></li>
                    <li><a href="exam.php" class="active">在线模拟考试</a></li>
                    <li><a href="analysis.php">成绩分析</a></li>
                    <li><a href="progress.php">学习进度</a></li>
                    <li><a href="resources.php">资源下载</a></li>
                    <?php if ($user['role'] === 'teacher'): ?>
                        <li><a href="question_bank.php">题库管理</a></li>
                    <?php endif; ?>
                    <li><a href="feedback.php">反馈与建议</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <span class="user-name"><?php echo e($user['username']); ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline" style="color: #fff; border-color: #fff;">退出</a>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <?php if (!$examStarted): ?>
                <!-- 考试选择界面 -->
                <div class="exam-setup">
                    <div class="card">
                        <div class="card-header">
                            <h1 class="card-title">📝 在线模拟考试</h1>
                        </div>
                        <div class="card-body">
                            <p style="margin-bottom: 25px; color: #666;">
                                欢迎使用在线模拟考试功能！请根据您的学习需求选择科目和考试类型，系统将为您生成相应的试卷。
                                考试结束后，您将获得即时的成绩反馈和详细的错题分析。
                            </p>
                            
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label class="form-label">选择科目 <span class="required">*</span></label>
                                    <select name="subject" class="form-control" required>
                                        <option value="">请选择科目</option>
                                        <?php foreach ($subjects as $key => $name): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">选择考试类型 <span class="required">*</span></label>
                                    <select name="exam_type" class="form-control" required>
                                        <option value="">请选择考试类型</option>
                                        <?php foreach ($examTypes as $key => $name): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="alert alert-info" style="margin-top: 20px;">
                                    <strong>注意事项：</strong>
                                    <ul style="margin: 10px 0 0 20px; font-size: 13px;">
                                        <li>请确保网络连接稳定，避免中途断网影响考试</li>
                                        <li>选择科目和考试类型后请点击"开始考试"按钮</li>
                                        <li>考试过程中请保持专注，在规定时间内完成试卷</li>
                                        <li>考试结束后可查看成绩和错题分析</li>
                                    </ul>
                                </div>
                                
                                <div class="form-group" style="margin-top: 25px;">
                                    <button type="submit" name="start_exam" class="btn btn-primary btn-lg btn-block">开始考试</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 可用考试列表 -->
                    <div class="card" style="margin-top: 30px;">
                        <div class="card-header">
                            <h2 class="card-title">📋 可用考试列表</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($availableExams): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>考试名称</th>
                                                <th>科目</th>
                                                <th>类型</th>
                                                <th>题目数</th>
                                                <th>时长</th>
                                                <th>总分</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($availableExams as $exam): ?>
                                                <tr>
                                                    <td><?php echo e($exam['title']); ?></td>
                                                    <td><?php echo e($subjects[$exam['subject']] ?? $exam['subject']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $exam['exam_type'] === 'mock' ? 'badge-primary' : 'badge-warning'; ?>">
                                                            <?php echo $examTypes[$exam['exam_type']] ?? $exam['exam_type']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $exam['question_count'] ?: '随机'; ?></td>
                                                    <td><?php echo $exam['duration']; ?>分钟</td>
                                                    <td><?php echo $exam['total_score']; ?>分</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📭</div>
                                    <div class="empty-state-title">暂无可用考试</div>
                                    <div class="empty-state-description">系统将陆续添加更多考试内容</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($examData): ?>
                <!-- 考试界面 -->
                <div class="exam-container">
                    <div class="timer" id="timer">
                        ⏱️ <span id="timeRemaining"><?php echo $examData['exam']['duration']; ?>:00</span>
                    </div>
                    
                    <div class="exam-header card">
                        <h2><?php echo e($examData['exam']['title']); ?></h2>
                        <div class="exam-info-grid">
                            <div class="exam-info-item">
                                <span class="exam-info-label">科目：</span>
                                <span class="exam-info-value"><?php echo e($subjects[$examData['exam']['subject']] ?? $examData['exam']['subject']); ?></span>
                            </div>
                            <div class="exam-info-item">
                                <span class="exam-info-label">考试类型：</span>
                                <span class="exam-info-value"><?php echo e($examTypes[$examData['exam']['exam_type']] ?? $examData['exam']['exam_type']); ?></span>
                            </div>
                            <div class="exam-info-item">
                                <span class="exam-info-label">题目数量：</span>
                                <span class="exam-info-value"><?php echo count($examData['questions']); ?>题</span>
                            </div>
                            <div class="exam-info-item">
                                <span class="exam-info-label">总分：</span>
                                <span class="exam-info-value"><?php echo $examData['exam']['total_score']; ?>分</span>
                            </div>
                        </div>
                    </div>
                    
                    <form id="examForm" method="POST" action="submit_exam.php">
                        <input type="hidden" name="user_exam_id" value="<?php echo $examData['user_exam_id']; ?>">
                        
                        <?php foreach ($examData['questions'] as $index => $question): ?>
                            <div class="question-card" data-question-id="<?php echo $question['id']; ?>">
                                <div class="question-header">
                                    <span class="question-number">第 <?php echo $index + 1; ?> 题</span>
                                    <span class="question-score">（<?php echo $question['score'] ?: 5; ?>分）</span>
                                </div>
                                
                                <div class="question-content">
                                    <?php echo nl2br(e($question['content'])); ?>
                                </div>
                                
                                <?php if ($question['type'] === 'choice'): ?>
                                    <ul class="question-options">
                                        <?php 
                                        $options = json_decode($question['options'], true);
                                        if ($options):
                                            foreach ($options as $opt):
                                        ?>
                                            <li class="question-option" onclick="selectOption(this, '<?php echo $question['id']; ?>')">
                                                <span class="option-marker"><?php echo substr($opt, 0, 1); ?></span>
                                                <span><?php echo e(substr($opt, 3)); ?></span>
                                                <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="<?php echo substr($opt, 0, 1); ?>" style="display: none;">
                                            </li>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </ul>
                                    
                                <?php elseif ($question['type'] === 'fill'): ?>
                                    <div class="form-group">
                                        <input type="text" name="answer[<?php echo $question['id']; ?>]" class="form-control" placeholder="请输入答案">
                                    </div>
                                    
                                <?php elseif ($question['type'] === 'essay'): ?>
                                    <div class="form-group">
                                        <textarea name="answer[<?php echo $question['id']; ?>]" class="form-control" rows="6" placeholder="请输入您的解答..."></textarea>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="form-group" style="margin-top: 30px;">
                            <button type="submit" name="submit_exam" class="btn btn-primary btn-lg btn-block">提交试卷</button>
                        </div>
                    </form>
                    
                    <div class="question-nav">
                        <div style="font-size: 12px; margin-bottom: 10px; color: #666;">答题卡</div>
                        <div class="question-nav-grid" id="questionNavGrid">
                            <?php for ($i = 0; $i < count($examData['questions']); $i++): ?>
                                <div class="question-nav-item" data-index="<?php echo $i; ?>"><?php echo $i + 1; ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <strong>中职技能竞赛训练辅助系统</strong>
                    <p style="opacity: 0.7; font-size: 13px; margin-top: 10px;">为中职学生提供全方位的技能训练支持</p>
                </div>
                <div class="footer-copyright">© 2024 版权所有</div>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
    <script>
        <?php if ($examStarted && $examData): ?>
        // 考试计时器
        let totalSeconds = <?php echo $examData['exam']['duration'] * 60; ?>;
        const timerElement = document.getElementById('timeRemaining');
        const timerBox = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            totalSeconds--;
            
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            timerElement.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            if (totalSeconds <= 300) {
                timerBox.classList.add('warning');
            }
            
            if (totalSeconds <= 0) {
                clearInterval(countdown);
                alert('考试时间到！系统将自动提交试卷。');
                document.getElementById('examForm').submit();
            }
        }, 1000);
        
        // 选择题选项选择
        function selectOption(element, questionId) {
            element.parentElement.querySelectorAll('.question-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            
            updateQuestionNav();
        }
        
        // 更新答题卡状态
        function updateQuestionNav() {
            const navItems = document.querySelectorAll('.question-nav-item');
            const answeredInputs = document.querySelectorAll('input[type="radio"]:checked, input[type="text"]:not([value=""]), textarea:not([value=""])');
            
            navItems.forEach((item, index) => {
                const card = document.querySelectorAll('.question-card')[index];
                if (card) {
                    const hasAnswer = card.querySelector('input[type="radio"]:checked') || 
                                     (card.querySelector('input[type="text"]') && card.querySelector('input[type="text"]').value.trim()) ||
                                     (card.querySelector('textarea') && card.querySelector('textarea').value.trim());
                    
                    if (hasAnswer) {
                        item.classList.add('answered');
                    }
                }
            });
        }
        
        // 监听输入变化
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', updateQuestionNav);
        });
        
        // 确认提交
        document.getElementById('examForm').addEventListener('submit', function(e) {
            const unanswered = document.querySelectorAll('.question-card').length - 
                              document.querySelectorAll('.question-option.selected, input[type="text"][value]:not([value=""]), textarea[value]:not([value=""])').length;
            
            if (unanswered > 0) {
                if (!confirm(`还有 ${unanswered} 道题未作答，确定要提交试卷吗？`)) {
                    e.preventDefault();
                    return false;
                }
            } else {
                if (!confirm('确定要提交试卷吗？提交后将无法修改答案。')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
