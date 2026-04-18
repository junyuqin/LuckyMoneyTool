<?php
/**
 * 中职技能竞赛训练辅助系统 - 数据库配置文件
 * 负责数据库连接和初始化
 */

// 数据库文件路径
define('DB_PATH', __DIR__ . '/../database.sqlite3');

// 获取数据库连接
function getDBConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    return $db;
}

// 初始化数据库表结构
function initializeDatabase() {
    $db = getDBConnection();
    
    // 创建用户表
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE,
            phone VARCHAR(11) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'student',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            is_active BOOLEAN DEFAULT 1,
            remember_token VARCHAR(255)
        )
    ");
    
    // 创建验证码表
    $db->exec("
        CREATE TABLE IF NOT EXISTS verification_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone VARCHAR(11) NOT NULL,
            code VARCHAR(6) NOT NULL,
            type VARCHAR(20) NOT NULL,
            expires_at DATETIME NOT NULL,
            is_used BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // 创建题库表
    $db->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            type VARCHAR(20) NOT NULL,
            content TEXT NOT NULL,
            options TEXT,
            answer TEXT NOT NULL,
            analysis TEXT,
            difficulty VARCHAR(10) DEFAULT 'medium',
            tags VARCHAR(255),
            category VARCHAR(50),
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");
    
    // 创建考试表
    $db->exec("
        CREATE TABLE IF NOT EXISTS exams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            subject VARCHAR(50) NOT NULL,
            exam_type VARCHAR(50) NOT NULL,
            duration INTEGER DEFAULT 120,
            total_score INTEGER DEFAULT 100,
            passing_score INTEGER DEFAULT 60,
            question_count INTEGER DEFAULT 20,
            status VARCHAR(20) DEFAULT 'active',
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");
    
    // 创建考试题目关联表
    $db->exec("
        CREATE TABLE IF NOT EXISTS exam_questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            exam_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            score INTEGER DEFAULT 5,
            question_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id),
            FOREIGN KEY (question_id) REFERENCES questions(id)
        )
    ");
    
    // 创建用户考试记录表
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_exams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            exam_id INTEGER NOT NULL,
            start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_time DATETIME,
            score INTEGER,
            status VARCHAR(20) DEFAULT 'in_progress',
            time_spent INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (exam_id) REFERENCES exams(id)
        )
    ");
    
    // 创建用户答题记录表
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_exam_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            user_answer TEXT,
            is_correct BOOLEAN,
            score_obtained INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_exam_id) REFERENCES user_exams(id),
            FOREIGN KEY (question_id) REFERENCES questions(id)
        )
    ");
    
    // 创建学习进度表
    $db->exec("
        CREATE TABLE IF NOT EXISTS learning_progress (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject VARCHAR(50),
            exams_completed INTEGER DEFAULT 0,
            questions_practiced INTEGER DEFAULT 0,
            correct_rate DECIMAL(5,2) DEFAULT 0,
            time_invested REAL DEFAULT 0,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // 创建资源下载表
    $db->exec("
        CREATE TABLE IF NOT EXISTS resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            category VARCHAR(50) NOT NULL,
            description TEXT,
            file_path VARCHAR(255) NOT NULL,
            file_size INTEGER,
            download_count INTEGER DEFAULT 0,
            uploaded_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ");
    
    // 创建反馈建议表
    $db->exec("
        CREATE TABLE IF NOT EXISTS feedbacks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            reply TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // 创建系统日志表
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
}

// 检查并初始化数据库
function checkAndInitializeDatabase() {
    if (!file_exists(DB_PATH)) {
        initializeDatabase();
        seedDatabase();
    }
}

// 生成模拟数据
function seedDatabase() {
    $db = getDBConnection();
    
    // 插入管理员账户
    $adminPassword = password_hash('admin123456', PASSWORD_DEFAULT);
    $db->exec("
        INSERT INTO users (username, phone, password_hash, role) 
        VALUES ('admin', '13800000000', '$adminPassword', 'teacher')
    ");
    
    // 插入示例学生账户
    $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
    for ($i = 1; $i <= 10; $i++) {
        $phone = '1390000' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $db->exec("
            INSERT INTO users (username, phone, password_hash, role) 
            VALUES ('student$i', '$phone', '$studentPassword', 'student')
        ");
    }
    
    // 插入示例题目
    $questions = [
        [
            'title' => '数学基础题',
            'type' => 'choice',
            'content' => '已知函数 f(x) = 2x + 3，求 f(5) 的值。',
            'options' => json_encode(['A. 10', 'B. 13', 'C. 15', 'D. 8']),
            'answer' => 'B',
            'analysis' => '将 x=5 代入函数：f(5) = 2×5 + 3 = 13',
            'difficulty' => 'easy',
            'tags' => '数学,函数,基础',
            'category' => 'math'
        ],
        [
            'title' => '英语词汇题',
            'type' => 'choice',
            'content' => '选择正确的单词完成句子：The weather is very _____ today.',
            'options' => json_encode(['A. beauty', 'B. beautiful', 'C. beautifully', 'D. beautify']),
            'answer' => 'B',
            'analysis' => '此处需要形容词修饰名词 weather，故选 beautiful',
            'difficulty' => 'easy',
            'tags' => '英语,词汇,形容词',
            'category' => 'english'
        ],
        [
            'title' => '编程基础题',
            'type' => 'choice',
            'content' => '在 PHP 中，以下哪个符号用于变量名前缀？',
            'options' => json_encode(['A. @', 'B. #', 'C. $', 'D. &']),
            'answer' => 'C',
            'analysis' => 'PHP 中变量必须以$符号开头',
            'difficulty' => 'easy',
            'tags' => '编程,PHP,变量',
            'category' => 'programming'
        ],
        [
            'title' => '数学填空题',
            'type' => 'fill',
            'content' => '等差数列 1, 3, 5, 7, ... 的第 10 项是 ____。',
            'options' => null,
            'answer' => '19',
            'analysis' => '首项 a1=1，公差 d=2，第 n 项公式：an = a1 + (n-1)d = 1 + 9×2 = 19',
            'difficulty' => 'medium',
            'tags' => '数学,数列,等差',
            'category' => 'math'
        ],
        [
            'title' => '编程填空题',
            'type' => 'fill',
            'content' => '在 PHP 中，使用 ____ 函数可以获取数组的长度。',
            'options' => null,
            'answer' => 'count',
            'analysis' => 'count() 函数用于返回数组中的元素个数',
            'difficulty' => 'easy',
            'tags' => '编程,PHP,数组',
            'category' => 'programming'
        ],
        [
            'title' => '数学解答题',
            'type' => 'essay',
            'content' => '请证明勾股定理：在直角三角形中，斜边的平方等于两直角边的平方和。',
            'options' => null,
            'answer' => '证明过程略',
            'analysis' => '可通过几何方法或代数方法证明',
            'difficulty' => 'hard',
            'tags' => '数学,几何,证明',
            'category' => 'math'
        ],
        [
            'title' => '编程解答题',
            'type' => 'essay',
            'content' => '请编写一个 PHP 函数，实现冒泡排序算法。',
            'options' => null,
            'answer' => 'function bubbleSort($arr) { ... }',
            'analysis' => '考察基本排序算法的实现能力',
            'difficulty' => 'medium',
            'tags' => '编程,算法,排序',
            'category' => 'programming'
        ],
        [
            'title' => '英语阅读理解',
            'type' => 'choice',
            'content' => '阅读短文后回答问题：What is the main idea of the passage?',
            'options' => json_encode(['A. Technology development', 'B. Environmental protection', 'C. Education reform', 'D. Economic growth']),
            'answer' => 'B',
            'analysis' => '文章主要讨论环境保护的重要性',
            'difficulty' => 'medium',
            'tags' => '英语,阅读,理解',
            'category' => 'english'
        ],
        [
            'title' => '数学计算题',
            'type' => 'fill',
            'content' => '计算：∫(2x + 1)dx = ____',
            'options' => null,
            'answer' => 'x²+x+C',
            'analysis' => '不定积分的基本计算',
            'difficulty' => 'medium',
            'tags' => '数学,微积分,积分',
            'category' => 'math'
        ],
        [
            'title' => '编程判断题',
            'type' => 'choice',
            'content' => '在面向对象编程中，封装、继承和多态是三大基本特性。这个说法正确吗？',
            'options' => json_encode(['A. 正确', 'B. 错误']),
            'answer' => 'A',
            'analysis' => '这是面向对象编程的三大核心特性',
            'difficulty' => 'easy',
            'tags' => '编程,面向对象,概念',
            'category' => 'programming'
        ]
    ];
    
    foreach ($questions as $q) {
        $stmt = $db->prepare("
            INSERT INTO questions (title, type, content, options, answer, analysis, difficulty, tags, category)
            VALUES (:title, :type, :content, :options, :answer, :analysis, :difficulty, :tags, :category)
        ");
        $stmt->execute($q);
    }
    
    // 插入示例考试
    $exams = [
        ['title' => '数学模拟考试（一）', 'subject' => 'math', 'exam_type' => 'mock', 'duration' => 90, 'total_score' => 100],
        ['title' => '英语模拟考试（一）', 'subject' => 'english', 'exam_type' => 'mock', 'duration' => 90, 'total_score' => 100],
        ['title' => '编程模拟考试（一）', 'subject' => 'programming', 'exam_type' => 'mock', 'duration' => 120, 'total_score' => 100],
        ['title' => '数学期末考试', 'subject' => 'math', 'exam_type' => 'final', 'duration' => 120, 'total_score' => 150],
        ['title' => '英语期末考试', 'subject' => 'english', 'exam_type' => 'final', 'duration' => 120, 'total_score' => 150],
        ['title' => '编程期末考试', 'subject' => 'programming', 'exam_type' => 'final', 'duration' => 150, 'total_score' => 150]
    ];
    
    foreach ($exams as $exam) {
        $stmt = $db->prepare("
            INSERT INTO exams (title, subject, exam_type, duration, total_score)
            VALUES (:title, :subject, :exam_type, :duration, :total_score)
        ");
        $stmt->execute($exam);
    }
    
    // 插入示例学习进度
    for ($i = 1; $i <= 10; $i++) {
        $subjects = ['math', 'english', 'programming'];
        foreach ($subjects as $subject) {
            $db->exec("
                INSERT INTO learning_progress (user_id, subject, exams_completed, questions_practiced, correct_rate, time_invested)
                VALUES ($i, '$subject', " . rand(1, 20) . ", " . rand(50, 500) . ", " . (rand(60, 95) / 100) . ", " . (rand(10, 100) * 0.5) . ")
            ");
        }
    }
    
    // 插入示例资源
    $resources = [
        ['title' => '高等数学复习指南', 'category' => 'math', 'description' => '包含重点知识点总结'],
        ['title' => '英语四六级词汇表', 'category' => 'english', 'description' => '常用词汇汇总'],
        ['title' => 'PHP 编程入门教程', 'category' => 'programming', 'description' => '适合初学者的编程教程'],
        ['title' => '历年真题解析', 'category' => 'general', 'description' => '历年竞赛真题及详细解析'],
        ['title' => '数据结构与算法', 'category' => 'programming', 'description' => '经典算法讲解']
    ];
    
    foreach ($resources as $res) {
        $stmt = $db->prepare("
            INSERT INTO resources (title, category, description, file_path, file_size, uploaded_by)
            VALUES (:title, :category, :description, :file_path, :file_size, :uploaded_by)
        ");
        $stmt->execute([
            ':title' => $res['title'],
            ':category' => $res['category'],
            ':description' => $res['description'],
            ':file_path' => '/uploads/' . strtolower($res['category']) . '_resource.pdf',
            ':file_size' => rand(100000, 5000000),
            ':uploaded_by' => 1
        ]);
    }
    
    // 插入示例反馈
    $feedbacks = [
        ['name' => '张三', 'email' => 'zhangsan@example.com', 'content' => '系统很好用，希望能增加更多练习题。'],
        ['name' => '李四', 'email' => 'lisi@example.com', 'content' => '界面设计很美观，操作流畅。'],
        ['name' => '王五', 'email' => 'wangwu@example.com', 'content' => '建议增加视频讲解功能。']
    ];
    
    foreach ($feedbacks as $fb) {
        $stmt = $db->prepare("
            INSERT INTO feedbacks (name, email, content)
            VALUES (:name, :email, :content)
        ");
        $stmt->execute($fb);
    }
}

// 记录系统日志
function logSystemAction($userId, $action, $description = '') {
    $db = getDBConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $db->prepare("
        INSERT INTO system_logs (user_id, action, description, ip_address, user_agent)
        VALUES (:user_id, :action, :description, :ip_address, :user_agent)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':description' => $description,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent
    ]);
}

// 验证手机号格式
function validatePhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

// 验证密码强度
function validatePasswordStrength($password) {
    $strength = 0;
    $feedback = [];
    
    if (strlen($password) >= 8) {
        $strength++;
    } else {
        $feedback[] = '密码长度至少为 8 位';
    }
    
    if (preg_match('/[a-z]/', $password)) {
        $strength++;
    } else {
        $feedback[] = '建议包含小写字母';
    }
    
    if (preg_match('/[A-Z]/', $password)) {
        $strength++;
    } else {
        $feedback[] = '建议包含大写字母';
    }
    
    if (preg_match('/\d/', $password)) {
        $strength++;
    } else {
        $feedback[] = '建议包含数字';
    }
    
    if (preg_match('/[^a-zA-Z0-9]/', $password)) {
        $strength++;
    } else {
        $feedback[] = '建议包含特殊符号';
    }
    
    $level = '弱';
    if ($strength >= 4) {
        $level = '强';
    } elseif ($strength >= 3) {
        $level = '中';
    }
    
    return [
        'valid' => strlen($password) >= 8,
        'strength' => $strength,
        'level' => $level,
        'feedback' => $feedback
    ];
}

// 生成验证码
function generateVerificationCode($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// 发送验证码（模拟）
function sendVerificationCode($phone, $code, $type = 'register') {
    $db = getDBConnection();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // 使旧验证码失效
    $db->prepare("UPDATE verification_codes SET is_used = 1 WHERE phone = ? AND type = ? AND is_used = 0")
       ->execute([$phone, $type]);
    
    // 存储新验证码
    $stmt = $db->prepare("
        INSERT INTO verification_codes (phone, code, type, expires_at)
        VALUES (:phone, :code, :type, :expires_at)
    ");
    $stmt->execute([
        ':phone' => $phone,
        ':code' => $code,
        ':type' => $type,
        ':expires_at' => $expiresAt
    ]);
    
    // 实际应用中这里会调用短信服务商 API
    // 此处仅做模拟，返回成功
    return true;
}

// 验证验证码
function verifyCode($phone, $code, $type = 'register') {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM verification_codes 
        WHERE phone = :phone AND code = :code AND type = :type 
        AND is_used = 0 AND expires_at > datetime('now')
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([
        ':phone' => $phone,
        ':code' => $code,
        ':type' => $type
    ]);
    
    $record = $stmt->fetch();
    
    if ($record) {
        // 标记验证码已使用
        $db->prepare("UPDATE verification_codes SET is_used = 1 WHERE id = ?")
           ->execute([$record['id']]);
        return true;
    }
    
    return false;
}

// 检查手机号是否已注册
function isPhoneRegistered($phone) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetchColumn() > 0;
}

// 获取当前登录用户
function getCurrentUser() {
    session_start();
    if (isset($_SESSION['user_id'])) {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// 检查用户是否登录
function isLoggedIn() {
    return getCurrentUser() !== null;
}

// 检查用户角色
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

// 重定向到指定页面
function redirect($url) {
    header("Location: $url");
    exit;
}

// 显示错误消息
function showError($message) {
    echo "<div class='alert alert-error'>$message</div>";
}

// 显示成功消息
function showSuccess($message) {
    echo "<div class='alert alert-success'>$message</div>";
}

// 转义输出防止 XSS
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 格式化文件大小
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// 格式化时间
function formatTime($datetime, $format = 'Y-m-d H:i') {
    return date($format, strtotime($datetime));
}

// 计算时间差
function timeDiff($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } else {
        return floor($diff / 86400) . '天前';
    }
}
