-- ============================================================
-- 中职技能竞赛训练辅助系统 - 数据库设计文档
-- Database Design Document for Vocational Skills Competition Training System
-- ============================================================
-- 数据库类型：SQLite 3
-- 创建日期：2024
-- 版本：1.0
-- ============================================================

-- ============================================================
-- 1. 用户表 (users)
-- 描述：存储系统用户的基本信息，包括学生和管理员/教师
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 用户 ID，主键自增
    username VARCHAR(50) UNIQUE,                     -- 用户名，唯一标识
    phone VARCHAR(11) UNIQUE NOT NULL,               -- 手机号，11 位，唯一标识，用于登录和接收验证码
    password_hash VARCHAR(255) NOT NULL,             -- 密码哈希值，使用 bcrypt 加密存储
    role VARCHAR(20) DEFAULT 'student',              -- 用户角色：student（学生）/ teacher（教师）
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 账户创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 账户最后更新时间
    last_login DATETIME,                             -- 最后登录时间
    is_active BOOLEAN DEFAULT 1,                     -- 账户是否激活：1-激活，0-禁用
    remember_token VARCHAR(255)                      -- 记住我功能的令牌，用于自动登录
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- ============================================================
-- 2. 验证码表 (verification_codes)
-- 描述：存储短信验证码，用于注册、登录和密码重置时的身份验证
-- ============================================================
CREATE TABLE IF NOT EXISTS verification_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 验证码记录 ID
    phone VARCHAR(11) NOT NULL,                      -- 接收验证码的手机号
    code VARCHAR(6) NOT NULL,                        -- 验证码，4-6 位数字
    type VARCHAR(20) NOT NULL,                       -- 验证码类型：register（注册）/ login（登录）/ reset_password（重置密码）
    expires_at DATETIME NOT NULL,                    -- 验证码过期时间
    is_used BOOLEAN DEFAULT 0,                       -- 验证码是否已使用：1-已使用，0-未使用
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP    -- 验证码发送时间
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_verification_phone ON verification_codes(phone);
CREATE INDEX IF NOT EXISTS idx_verification_type ON verification_codes(type);
CREATE INDEX IF NOT EXISTS idx_verification_expires ON verification_codes(expires_at);

-- ============================================================
-- 3. 题库表 (questions)
-- 描述：存储考试题目，支持多种题型
-- ============================================================
CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 题目 ID
    title VARCHAR(200) NOT NULL,                     -- 题目标题，简要描述题目内容
    type VARCHAR(20) NOT NULL,                       -- 题目类型：choice（选择题）/ fill（填空题）/ essay（问答题）
    content TEXT NOT NULL,                           -- 题目内容，完整的题目描述
    options TEXT,                                    -- 选择题选项，JSON 格式存储 ["A. 选项 1", "B. 选项 2", ...]
    answer TEXT NOT NULL,                            -- 正确答案
    analysis TEXT,                                   -- 答案解析，帮助学生理解
    difficulty VARCHAR(10) DEFAULT 'medium',         -- 难度等级：easy（简单）/ medium（中等）/ hard（困难）
    tags VARCHAR(255),                               -- 标签，逗号分隔，用于分类和检索
    category VARCHAR(50),                            -- 科目分类：math（数学）/ english（英语）/ programming（编程）
    created_by INTEGER,                              -- 创建者 ID，关联 users 表
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 题目创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 题目最后更新时间
    FOREIGN KEY (created_by) REFERENCES users(id)    -- 外键关联
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_questions_type ON questions(type);
CREATE INDEX IF NOT EXISTS idx_questions_category ON questions(category);
CREATE INDEX IF NOT EXISTS idx_questions_difficulty ON questions(difficulty);
CREATE INDEX IF NOT EXISTS idx_questions_created_by ON questions(created_by);

-- ============================================================
-- 4. 考试表 (exams)
-- 描述：存储考试的基本信息
-- ============================================================
CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 考试 ID
    title VARCHAR(200) NOT NULL,                     -- 考试名称
    subject VARCHAR(50) NOT NULL,                    -- 考试科目：math/english/programming
    exam_type VARCHAR(50) NOT NULL,                  -- 考试类型：mock（模拟考试）/ final（期末考试）
    duration INTEGER DEFAULT 120,                    -- 考试时长（分钟）
    total_score INTEGER DEFAULT 100,                 -- 总分
    passing_score INTEGER DEFAULT 60,                -- 及格分数
    question_count INTEGER DEFAULT 20,               -- 题目数量
    status VARCHAR(20) DEFAULT 'active',             -- 考试状态：active（进行中）/ inactive（已结束）/ draft（草稿）
    created_by INTEGER,                              -- 创建者 ID
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 考试创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 考试最后更新时间
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_exams_subject ON exams(subject);
CREATE INDEX IF NOT EXISTS idx_exams_type ON exams(exam_type);
CREATE INDEX IF NOT EXISTS idx_exams_status ON exams(status);

-- ============================================================
-- 5. 考试题目关联表 (exam_questions)
-- 描述：存储考试与题目的关联关系，支持一套试卷包含多道题目
-- ============================================================
CREATE TABLE IF NOT EXISTS exam_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 关联记录 ID
    exam_id INTEGER NOT NULL,                        -- 考试 ID
    question_id INTEGER NOT NULL,                    -- 题目 ID
    score INTEGER DEFAULT 5,                         -- 该题分值
    question_order INTEGER DEFAULT 0,                -- 题目在试卷中的顺序
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 关联创建时间
    FOREIGN KEY (exam_id) REFERENCES exams(id),      -- 外键关联考试表
    FOREIGN KEY (question_id) REFERENCES questions(id) -- 外键关联题库表
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_exam_questions_exam ON exam_questions(exam_id);
CREATE INDEX IF NOT EXISTS idx_exam_questions_question ON exam_questions(question_id);

-- ============================================================
-- 6. 用户考试记录表 (user_exams)
-- 描述：存储用户参加考试的记录
-- ============================================================
CREATE TABLE IF NOT EXISTS user_exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 考试记录 ID
    user_id INTEGER NOT NULL,                        -- 用户 ID
    exam_id INTEGER NOT NULL,                        -- 考试 ID
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 开始考试时间
    end_time DATETIME,                               -- 结束考试时间
    score INTEGER,                                   -- 考试成绩
    status VARCHAR(20) DEFAULT 'in_progress',        -- 考试状态：in_progress（进行中）/ completed（已完成）/ submitted（已提交）
    time_spent INTEGER DEFAULT 0,                    -- 实际用时（秒）
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 记录创建时间
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_user_exams_user ON user_exams(user_id);
CREATE INDEX IF NOT EXISTS idx_user_exams_exam ON user_exams(exam_id);
CREATE INDEX IF NOT EXISTS idx_user_exams_status ON user_exams(status);

-- ============================================================
-- 7. 用户答题记录表 (user_answers)
-- 描述：存储用户在考试中的每道题的作答情况
-- ============================================================
CREATE TABLE IF NOT EXISTS user_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 答题记录 ID
    user_exam_id INTEGER NOT NULL,                   -- 用户考试记录 ID
    question_id INTEGER NOT NULL,                    -- 题目 ID
    user_answer TEXT,                                -- 用户答案
    is_correct BOOLEAN,                              -- 答案是否正确：1-正确，0-错误
    score_obtained INTEGER DEFAULT 0,                -- 该题得分
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 答题时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 最后更新时间
    FOREIGN KEY (user_exam_id) REFERENCES user_exams(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_user_answers_exam ON user_answers(user_exam_id);
CREATE INDEX IF NOT EXISTS idx_user_answers_question ON user_answers(question_id);
CREATE INDEX IF NOT EXISTS idx_user_answers_correct ON user_answers(is_correct);

-- ============================================================
-- 8. 学习进度表 (learning_progress)
-- 描述：存储用户在各科目的学习进度统计
-- ============================================================
CREATE TABLE IF NOT EXISTS learning_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 进度记录 ID
    user_id INTEGER NOT NULL,                        -- 用户 ID
    subject VARCHAR(50),                             -- 科目：math/english/programming
    exams_completed INTEGER DEFAULT 0,               -- 完成的考试数量
    questions_practiced INTEGER DEFAULT 0,           -- 练习的题目数量
    correct_rate DECIMAL(5,2) DEFAULT 0,             -- 正确率（百分比，如 85.50 表示 85.5%）
    time_invested REAL DEFAULT 0,                    -- 投入时间（小时）
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP, -- 最后活动时间
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 记录创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 记录最后更新时间
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_learning_progress_user ON learning_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_learning_progress_subject ON learning_progress(subject);

-- ============================================================
-- 9. 资源下载表 (resources)
-- 描述：存储学习资源的元数据信息
-- ============================================================
CREATE TABLE IF NOT EXISTS resources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 资源 ID
    title VARCHAR(200) NOT NULL,                     -- 资源标题
    category VARCHAR(50) NOT NULL,                   -- 资源分类：math/english/programming/general
    description TEXT,                                -- 资源描述
    file_path VARCHAR(255) NOT NULL,                 -- 文件存储路径
    file_size INTEGER,                               -- 文件大小（字节）
    download_count INTEGER DEFAULT 0,                -- 下载次数
    uploaded_by INTEGER,                             -- 上传者 ID
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 上传时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 最后更新时间
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_resources_category ON resources(category);
CREATE INDEX IF NOT EXISTS idx_resources_uploaded_by ON resources(uploaded_by);

-- ============================================================
-- 10. 反馈建议表 (feedbacks)
-- 描述：存储用户提交的反馈和建议
-- ============================================================
CREATE TABLE IF NOT EXISTS feedbacks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 反馈 ID
    name VARCHAR(100) NOT NULL,                      -- 反馈人姓名
    email VARCHAR(100) NOT NULL,                     -- 反馈人邮箱
    content TEXT NOT NULL,                           -- 反馈内容
    status VARCHAR(20) DEFAULT 'pending',            -- 处理状态：pending（待处理）/ processing（处理中）/ resolved（已解决）
    reply TEXT,                                      -- 管理员回复
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 提交时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP    -- 最后更新时间
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_feedbacks_status ON feedbacks(status);
CREATE INDEX IF NOT EXISTS idx_feedbacks_created ON feedbacks(created_at);

-- ============================================================
-- 11. 系统日志表 (system_logs)
-- 描述：记录系统操作日志，用于安全审计和问题追踪
-- ============================================================
CREATE TABLE IF NOT EXISTS system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,            -- 日志 ID
    user_id INTEGER,                                 -- 操作用户 ID
    action VARCHAR(100) NOT NULL,                    -- 操作类型：login/register/logout/reset_password 等
    description TEXT,                                -- 操作描述
    ip_address VARCHAR(45),                          -- 操作 IP 地址（支持 IPv6）
    user_agent TEXT,                                 -- 浏览器 User-Agent
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 操作时间
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 索引优化
CREATE INDEX IF NOT EXISTS idx_system_logs_user ON system_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_system_logs_action ON system_logs(action);
CREATE INDEX IF NOT EXISTS idx_system_logs_created ON system_logs(created_at);

-- ============================================================
-- 视图定义 (Views)
-- ============================================================

-- 用户考试统计视图
CREATE VIEW IF NOT EXISTS view_user_exam_stats AS
SELECT 
    u.id as user_id,
    u.username,
    u.phone,
    COUNT(DISTINCT ue.id) as total_exams,
    SUM(CASE WHEN ue.status = 'completed' THEN 1 ELSE 0 END) as completed_exams,
    AVG(ue.score) as average_score,
    MAX(ue.score) as highest_score,
    SUM(ue.time_spent) as total_time_spent
FROM users u
LEFT JOIN user_exams ue ON u.id = ue.user_id
WHERE u.role = 'student'
GROUP BY u.id, u.username, u.phone;

-- 题目使用情况视图
CREATE VIEW IF NOT EXISTS view_question_usage AS
SELECT 
    q.id as question_id,
    q.title,
    q.type,
    q.category,
    q.difficulty,
    COUNT(ua.id) as times_used,
    SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
    ROUND(CAST(SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) AS FLOAT) / COUNT(ua.id) * 100, 2) as correct_rate
FROM questions q
LEFT JOIN user_answers ua ON q.id = ua.question_id
GROUP BY q.id, q.title, q.type, q.category, q.difficulty;

-- ============================================================
-- 触发器定义 (Triggers)
-- ============================================================

-- 用户表更新时间触发器
CREATE TRIGGER IF NOT EXISTS trigger_users_updated_at 
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- 题目表更新时间触发器
CREATE TRIGGER IF NOT EXISTS trigger_questions_updated_at 
AFTER UPDATE ON questions
BEGIN
    UPDATE questions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- 考试表更新时间触发器
CREATE TRIGGER IF NOT EXISTS trigger_exams_updated_at 
AFTER UPDATE ON exams
BEGIN
    UPDATE exams SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- 学习进度表更新时间触发器
CREATE TRIGGER IF NOT EXISTS trigger_learning_progress_updated_at 
AFTER UPDATE ON learning_progress
BEGIN
    UPDATE learning_progress SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- 资源表更新时间触发器
CREATE TRIGGER IF NOT EXISTS trigger_resources_updated_at 
AFTER UPDATE ON resources
BEGIN
    UPDATE resources SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- 反馈表更新时间触发器
CREATE TRIGGER IF NOT EXISTS trigger_feedbacks_updated_at 
AFTER UPDATE ON feedbacks
BEGIN
    UPDATE feedbacks SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- ============================================================
-- 初始数据插入 (Seed Data)
-- ============================================================

-- 插入管理员账户（密码：admin123456）
INSERT INTO users (username, phone, password_hash, role) 
VALUES ('admin', '13800000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- 插入示例学生账户（密码：student123）
INSERT INTO users (username, phone, password_hash, role) 
VALUES ('student1', '13900000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO users (username, phone, password_hash, role) 
VALUES ('student2', '13900000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- ============================================================
-- 数据库设计说明
-- ============================================================
/*
一、核心功能模块对应的数据表：

1. 用户管理模块
   - users: 用户基本信息
   - verification_codes: 验证码管理
   
2. 题库管理模块
   - questions: 题目存储
   - exam_questions: 考试题目关联
   
3. 在线考试模块
   - exams: 考试信息
   - user_exams: 用户考试记录
   - user_answers: 用户答题记录
   
4. 成绩分析模块
   - user_exams: 考试成绩
   - user_answers: 答题详情
   - learning_progress: 学习进度统计
   
5. 资源管理模块
   - resources: 学习资源

6. 反馈系统模块
   - feedbacks: 用户反馈

二、数据安全设计：
   - 密码使用 bcrypt 哈希加密存储
   - 验证码有过期时间和使用状态控制
   - 敏感操作记录系统日志

三、性能优化：
   - 关键字段建立索引
   - 使用视图简化复杂查询
   - 触发器自动维护更新时间字段

四、扩展性考虑：
   - 支持多科目、多题型
   - 支持用户角色扩展
   - 资源分类灵活可配置
*/

-- ============================================================
-- 文档结束
-- ============================================================
