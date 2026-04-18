<?php
/**
 * 数据库设计 SQL 语句
 * 中职学生技能成长档案系统
 * 
 * 此文件包含完整的数据库表结构定义，可用于制作数据库设计文档
 */

$sql = <<<SQL
-- =====================================================
-- 中职学生技能成长档案系统 - 数据库设计文档
-- =====================================================
-- 数据库类型：SQLite3
-- 创建日期：2024
-- 版本：1.0
-- =====================================================

-- =====================================================
-- 1. 用户表 (users)
-- =====================================================
-- 描述：存储系统用户的基本信息，包括学生、教师和管理员
-- 用途：用户认证、权限管理、个人信息存储

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 用户 ID，主键，自增
    username VARCHAR(50) UNIQUE,                     -- 用户名，唯一标识
    phone VARCHAR(11) UNIQUE NOT NULL,               -- 手机号，11 位，唯一，必填
    password_hash VARCHAR(255) NOT NULL,             -- 密码哈希值，加密存储
    role VARCHAR(20) DEFAULT 'student',              -- 用户角色：student/teacher/admin
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- 更新时间
    last_login DATETIME,                             -- 最后登录时间
    is_active INTEGER DEFAULT 1                      -- 账户状态：1-激活，0-禁用
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- =====================================================
-- 2. 验证码表 (verification_codes)
-- =====================================================
-- 描述：存储短信验证码，用于注册、登录和密码重置
-- 用途：身份验证、安全防护

CREATE TABLE IF NOT EXISTS verification_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 验证码 ID，主键，自增
    phone VARCHAR(11) NOT NULL,                      -- 手机号
    code VARCHAR(6) NOT NULL,                        -- 验证码，4-6 位数字
    type VARCHAR(20) DEFAULT 'register',             -- 验证码类型：register/login/reset_password
    expires_at DATETIME NOT NULL,                    -- 过期时间
    is_used INTEGER DEFAULT 0,                       -- 是否已使用：0-未使用，1-已使用
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP    -- 创建时间
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_verification_phone ON verification_codes(phone);
CREATE INDEX IF NOT EXISTS idx_verification_type ON verification_codes(type);
CREATE INDEX IF NOT EXISTS idx_verification_expires ON verification_codes(expires_at);

-- =====================================================
-- 3. 技能记录表 (skill_records)
-- =====================================================
-- 描述：存储学生的技能学习和表现记录
-- 用途：技能数据采集、学习过程跟踪

CREATE TABLE IF NOT EXISTS skill_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,                   -- 记录 ID，主键，自增
    student_id INTEGER NOT NULL,                            -- 学生 ID，外键关联 users 表
    student_name VARCHAR(100) NOT NULL,                     -- 学生姓名
    skill_name VARCHAR(100) NOT NULL,                       -- 技能名称
    skill_category VARCHAR(50),                             -- 技能类别：编程/设计/沟通/管理
    performance_description TEXT,                           -- 表现描述
    score INTEGER,                                          -- 评分（0-100）
    evaluation_level VARCHAR(20),                           -- 评估等级：优秀/良好/中等/及格/不及格
    file_path VARCHAR(255),                                 -- 附件文件路径
    teacher_id INTEGER,                                     -- 录入教师 ID，外键关联 users 表
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,          -- 创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,          -- 更新时间
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_skill_student ON skill_records(student_id);
CREATE INDEX IF NOT EXISTS idx_skill_category ON skill_records(skill_category);
CREATE INDEX IF NOT EXISTS idx_skill_teacher ON skill_records(teacher_id);
CREATE INDEX IF NOT EXISTS idx_skill_created ON skill_records(created_at);

-- =====================================================
-- 4. 技能评估表 (skill_evaluations)
-- =====================================================
-- 描述：存储学生的技能评估结果和分析数据
-- 用途：技能评估分析、学习进度跟踪

CREATE TABLE IF NOT EXISTS skill_evaluations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 评估 ID，主键，自增
    student_id INTEGER NOT NULL,                    -- 学生 ID，外键关联 users 表
    skill_category VARCHAR(50) NOT NULL,            -- 技能类别
    evaluation_period VARCHAR(50),                  -- 评估周期
    mastery_level INTEGER,                          -- 掌握程度（0-100）
    progress_rate REAL,                             -- 进步率（百分比）
    teacher_comments TEXT,                          -- 教师评语
    evaluation_date DATE,                           -- 评估日期
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 创建时间
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_evaluation_student ON skill_evaluations(student_id);
CREATE INDEX IF NOT EXISTS idx_evaluation_category ON skill_evaluations(skill_category);
CREATE INDEX IF NOT EXISTS idx_evaluation_date ON skill_evaluations(evaluation_date);

-- =====================================================
-- 5. 成长档案表 (growth_archives)
-- =====================================================
-- 描述：存储生成的学生成长档案
-- 用途：档案管理、档案展示、档案导出

CREATE TABLE IF NOT EXISTS growth_archives (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 档案 ID，主键，自增
    student_id INTEGER NOT NULL,                    -- 学生 ID，外键关联 users 表
    student_name VARCHAR(100) NOT NULL,             -- 学生姓名
    skill_data TEXT,                                -- 技能数据（JSON 格式）
    learning_progress TEXT,                         -- 学习进度描述
    development_suggestions TEXT,                   -- 发展建议
    archive_content TEXT,                           -- 档案完整内容
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,-- 生成时间
    is_exported INTEGER DEFAULT 0,                  -- 是否已导出：0-未导出，1-已导出
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_archive_student ON growth_archives(student_id);
CREATE INDEX IF NOT EXISTS idx_archive_generated ON growth_archives(generated_at);

-- =====================================================
-- 6. 互动交流表 (interactions)
-- =====================================================
-- 描述：存储师生互动交流消息
-- 用途：师生沟通、公告发布、问题反馈

CREATE TABLE IF NOT EXISTS interactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 消息 ID，主键，自增
    user_id INTEGER NOT NULL,                       -- 用户 ID，外键关联 users 表
    user_name VARCHAR(100),                         -- 用户名称
    user_role VARCHAR(20),                          -- 用户角色
    message_type VARCHAR(20) DEFAULT 'comment',     -- 消息类型：comment/announcement/question
    content TEXT NOT NULL,                          -- 消息内容
    parent_id INTEGER,                              -- 父消息 ID（回复功能）
    is_announcement INTEGER DEFAULT 0,              -- 是否公告：0-普通消息，1-公告
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 更新时间
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES interactions(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_interaction_user ON interactions(user_id);
CREATE INDEX IF NOT EXISTS idx_interaction_parent ON interactions(parent_id);
CREATE INDEX IF NOT EXISTS idx_interaction_type ON interactions(is_announcement);
CREATE INDEX IF NOT EXISTS idx_interaction_created ON interactions(created_at);

-- =====================================================
-- 7. 统计报告表 (statistical_reports)
-- =====================================================
-- 描述：存储系统生成的统计分析报告
-- 用途：数据分析、教学决策支持

CREATE TABLE IF NOT EXISTS statistical_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 报告 ID，主键，自增
    report_name VARCHAR(100) NOT NULL,              -- 报告名称
    report_type VARCHAR(50),                        -- 报告类型
    time_period VARCHAR(50),                        -- 时间周期
    skill_category VARCHAR(50),                     -- 技能类别
    mastery_rate REAL,                              -- 掌握率
    progress_rate REAL,                             -- 进步率
    course_feedback TEXT,                           -- 课程反馈
    chart_data TEXT,                                -- 图表数据（JSON 格式）
    generated_by INTEGER,                           -- 生成者 ID，外键关联 users 表
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 创建时间
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 更新时间
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_report_type ON statistical_reports(report_type);
CREATE INDEX IF NOT EXISTS idx_report_category ON statistical_reports(skill_category);
CREATE INDEX IF NOT EXISTS idx_report_created ON statistical_reports(created_at);

-- =====================================================
-- 8. 学习成果表 (learning_achievements)
-- =====================================================
-- 描述：存储学生的学习成果和证书信息
-- 用途：成果展示、档案管理

CREATE TABLE IF NOT EXISTS learning_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 成果 ID，主键，自增
    student_id INTEGER NOT NULL,                    -- 学生 ID，外键关联 users 表
    skill_name VARCHAR(100) NOT NULL,               -- 技能名称
    achievement_level VARCHAR(20),                  -- 成果等级
    achievement_date DATE,                          -- 获得日期
    description TEXT,                               -- 成果描述
    certificate_path VARCHAR(255),                  -- 证书文件路径
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 创建时间
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_achievement_student ON learning_achievements(student_id);
CREATE INDEX IF NOT EXISTS idx_achievement_date ON learning_achievements(achievement_date);

-- =====================================================
-- 9. 用户会话表 (user_sessions)
-- =====================================================
-- 描述：存储用户登录会话信息，支持"记住我"功能
-- 用途：会话管理、自动登录

CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 会话 ID，主键，自增
    user_id INTEGER NOT NULL,                       -- 用户 ID，外键关联 users 表
    session_token VARCHAR(255) UNIQUE NOT NULL,     -- 会话令牌，唯一
    expires_at DATETIME NOT NULL,                   -- 过期时间
    ip_address VARCHAR(45),                         -- IP 地址
    user_agent TEXT,                                -- 用户代理
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 创建时间
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_session_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_session_user ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_session_expires ON user_sessions(expires_at);

-- =====================================================
-- 10. 系统日志表 (system_logs)
-- =====================================================
-- 描述：存储系统操作日志
-- 用途：安全审计、行为追踪

CREATE TABLE IF NOT EXISTS system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,           -- 日志 ID，主键，自增
    user_id INTEGER,                                -- 用户 ID，外键关联 users 表
    action_type VARCHAR(50),                        -- 操作类型
    action_description TEXT,                        -- 操作描述
    ip_address VARCHAR(45),                         -- IP 地址
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- 创建时间
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 索引
CREATE INDEX IF NOT EXISTS idx_log_user ON system_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_log_type ON system_logs(action_type);
CREATE INDEX IF NOT EXISTS idx_log_created ON system_logs(created_at);

-- =====================================================
-- 视图定义
-- =====================================================

-- 学生技能统计视图
CREATE VIEW IF NOT EXISTS view_student_skill_summary AS
SELECT 
    u.id as student_id,
    u.username,
    u.phone,
    COUNT(sr.id) as total_skills,
    AVG(sr.score) as average_score,
    MAX(sr.created_at) as last_record_date
FROM users u
LEFT JOIN skill_records sr ON u.id = sr.student_id
WHERE u.role = 'student'
GROUP BY u.id;

-- 技能类别统计视图
CREATE VIEW IF NOT EXISTS view_skill_category_stats AS
SELECT 
    skill_category,
    COUNT(*) as record_count,
    AVG(score) as average_score,
    MAX(score) as max_score,
    MIN(score) as min_score,
    COUNT(DISTINCT student_id) as student_count
FROM skill_records
WHERE skill_category IS NOT NULL
GROUP BY skill_category;

-- =====================================================
-- 初始数据插入（模拟数据）
-- =====================================================

-- 插入管理员账户 (密码：password)
INSERT INTO users (username, phone, password_hash, role) 
VALUES ('admin', '13800138000', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 插入教师账户 (密码：password)
INSERT INTO users (username, phone, password_hash, role) 
VALUES ('teacher1', '13800138001', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- 插入学生账户 (密码：password)
INSERT INTO users (username, phone, password_hash, role) 
VALUES ('student1', '13800138002', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO users (username, phone, password_hash, role) 
VALUES ('student2', '13800138003', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- 插入技能记录示例
INSERT INTO skill_records (student_id, student_name, skill_name, skill_category, performance_description, score, evaluation_level, teacher_id)
VALUES 
(3, '张三', 'Python 编程', '编程', '能够熟练使用 Python 进行数据处理和分析', 85, '良好', 2),
(3, '张三', '网页设计', '设计', '掌握了 HTML、CSS 和 JavaScript 基础', 78, '中等', 2),
(4, '李四', 'Java 编程', '编程', '理解面向对象编程概念，能编写基本程序', 92, '优秀', 2),
(4, '李四', '团队协作', '沟通', '在团队项目中表现积极，沟通能力强', 88, '良好', 2);

-- 插入互动交流示例
INSERT INTO interactions (user_id, user_name, user_role, message_type, content, is_announcement)
VALUES 
(2, '王老师', 'teacher', 'announcement', '欢迎同学们使用技能成长档案系统！', 1),
(3, '张三', 'student', 'comment', '老师，请问如何提升编程能力？', 0);

SQL;

// 输出 SQL 内容
echo $sql;
