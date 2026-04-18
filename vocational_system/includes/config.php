<?php
/**
 * 数据库配置文件
 * 中职学生技能成长档案系统
 */

// 数据库文件路径
define('DB_PATH', __DIR__ . '/../database/skills_archive.db');

// 获取数据库连接
function getDBConnection() {
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}

// 初始化数据库表结构
function initializeDatabase() {
    $pdo = getDBConnection();
    
    // 创建用户表
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE,
        phone VARCHAR(11) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'student',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME,
        is_active INTEGER DEFAULT 1
    )";
    $pdo->exec($sql_users);
    
    // 创建验证码表
    $sql_verifications = "CREATE TABLE IF NOT EXISTS verification_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone VARCHAR(11) NOT NULL,
        code VARCHAR(6) NOT NULL,
        type VARCHAR(20) DEFAULT 'register',
        expires_at DATETIME NOT NULL,
        is_used INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_verifications);
    
    // 创建技能数据表
    $sql_skills = "CREATE TABLE IF NOT EXISTS skill_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        skill_name VARCHAR(100) NOT NULL,
        skill_category VARCHAR(50),
        performance_description TEXT,
        score INTEGER,
        evaluation_level VARCHAR(20),
        file_path VARCHAR(255),
        teacher_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (teacher_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_skills);
    
    // 创建技能评估表
    $sql_evaluations = "CREATE TABLE IF NOT EXISTS skill_evaluations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        skill_category VARCHAR(50) NOT NULL,
        evaluation_period VARCHAR(50),
        mastery_level INTEGER,
        progress_rate REAL,
        teacher_comments TEXT,
        evaluation_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_evaluations);
    
    // 创建成长档案表
    $sql_archives = "CREATE TABLE IF NOT EXISTS growth_archives (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        skill_data TEXT,
        learning_progress TEXT,
        development_suggestions TEXT,
        archive_content TEXT,
        generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_exported INTEGER DEFAULT 0,
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_archives);
    
    // 创建互动交流表
    $sql_interactions = "CREATE TABLE IF NOT EXISTS interactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        user_name VARCHAR(100),
        user_role VARCHAR(20),
        message_type VARCHAR(20) DEFAULT 'comment',
        content TEXT NOT NULL,
        parent_id INTEGER,
        is_announcement INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (parent_id) REFERENCES interactions(id)
    )";
    $pdo->exec($sql_interactions);
    
    // 创建统计报告表
    $sql_reports = "CREATE TABLE IF NOT EXISTS statistical_reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        report_name VARCHAR(100) NOT NULL,
        report_type VARCHAR(50),
        time_period VARCHAR(50),
        skill_category VARCHAR(50),
        mastery_rate REAL,
        progress_rate REAL,
        course_feedback TEXT,
        chart_data TEXT,
        generated_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (generated_by) REFERENCES users(id)
    )";
    $pdo->exec($sql_reports);
    
    // 创建学习成果表
    $sql_achievements = "CREATE TABLE IF NOT EXISTS learning_achievements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        skill_name VARCHAR(100) NOT NULL,
        achievement_level VARCHAR(20),
        achievement_date DATE,
        description TEXT,
        certificate_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_achievements);
    
    // 创建会话表（用于记住我功能）
    $sql_sessions = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at DATETIME NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_sessions);
    
    // 创建系统日志表
    $sql_logs = "CREATE TABLE IF NOT EXISTS system_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action_type VARCHAR(50),
        action_description TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_logs);
}

// 检查数据库是否存在，不存在则创建
if (!file_exists(DB_PATH)) {
    // 确保数据库目录存在
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    initializeDatabase();
}
