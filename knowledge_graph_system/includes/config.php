<?php
/**
 * 数据库配置文件
 * 中职专业课程知识图谱系统
 */

// 数据库文件路径
define('DB_PATH', __DIR__ . '/../data/knowledge_graph.db');

// 获取数据库连接
function getDBConnection() {
    try {
        $db = new SQLite3(DB_PATH);
        $db->enableExceptions(true);
        return $db;
    } catch (Exception $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}

// 初始化数据库表
function initDatabase() {
    $db = getDBConnection();
    
    // 创建用户表
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        phone VARCHAR(11) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active INTEGER DEFAULT 1,
        login_attempts INTEGER DEFAULT 0,
        locked_until DATETIME NULL
    );
    ";
    
    // 创建验证码表
    $createVerificationsTable = "
    CREATE TABLE IF NOT EXISTS verifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone VARCHAR(11) NOT NULL,
        code VARCHAR(6) NOT NULL,
        type VARCHAR(20) NOT NULL,
        expires_at DATETIME NOT NULL,
        used INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    // 创建课程资源表
    $createResourcesTable = "
    CREATE TABLE IF NOT EXISTS course_resources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        resource_name VARCHAR(255) NOT NULL,
        resource_type VARCHAR(20) NOT NULL,
        resource_description TEXT,
        file_path VARCHAR(500),
        upload_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        author_id INTEGER NOT NULL,
        author_name VARCHAR(100),
        category VARCHAR(100),
        file_size INTEGER,
        download_count INTEGER DEFAULT 0,
        FOREIGN KEY (author_id) REFERENCES users(id)
    );
    ";
    
    // 创建知识点表
    $createKnowledgePointsTable = "
    CREATE TABLE IF NOT EXISTS knowledge_points (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        point_name VARCHAR(255) NOT NULL,
        point_code VARCHAR(50) UNIQUE,
        description TEXT,
        course_id INTEGER,
        parent_id INTEGER NULL,
        difficulty_level INTEGER DEFAULT 1,
        estimated_hours REAL DEFAULT 1.0,
        order_index INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES knowledge_points(id)
    );
    ";
    
    // 创建知识点关联表
    $createKnowledgeRelationsTable = "
    CREATE TABLE IF NOT EXISTS knowledge_relations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source_point_id INTEGER NOT NULL,
        target_point_id INTEGER NOT NULL,
        relation_type VARCHAR(50) NOT NULL,
        strength REAL DEFAULT 1.0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (source_point_id) REFERENCES knowledge_points(id),
        FOREIGN KEY (target_point_id) REFERENCES knowledge_points(id)
    );
    ";
    
    // 创建课程表
    $createCoursesTable = "
    CREATE TABLE IF NOT EXISTS courses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_name VARCHAR(255) NOT NULL,
        course_code VARCHAR(50) UNIQUE,
        description TEXT,
        major VARCHAR(100),
        credit_hours INTEGER,
        teacher_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id)
    );
    ";
    
    // 创建学习路径表
    $createLearningPathsTable = "
    CREATE TABLE IF NOT EXISTS learning_paths (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        path_name VARCHAR(255) NOT NULL,
        description TEXT,
        total_hours REAL DEFAULT 0,
        progress REAL DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    ";
    
    // 创建学习路径详情表
    $createLearningPathDetailsTable = "
    CREATE TABLE IF NOT EXISTS learning_path_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        path_id INTEGER NOT NULL,
        knowledge_point_id INTEGER NOT NULL,
        resource_id INTEGER,
        sequence_order INTEGER NOT NULL,
        estimated_hours REAL DEFAULT 1.0,
        completed INTEGER DEFAULT 0,
        completed_at DATETIME NULL,
        FOREIGN KEY (path_id) REFERENCES learning_paths(id),
        FOREIGN KEY (knowledge_point_id) REFERENCES knowledge_points(id),
        FOREIGN KEY (resource_id) REFERENCES course_resources(id)
    );
    ";
    
    // 创建学习进度表
    $createLearningProgressTable = "
    CREATE TABLE IF NOT EXISTS learning_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        knowledge_point_id INTEGER NOT NULL,
        study_time REAL DEFAULT 0,
        score REAL DEFAULT 0,
        status VARCHAR(20) DEFAULT 'not_started',
        last_study_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (knowledge_point_id) REFERENCES knowledge_points(id)
    );
    ";
    
    // 创建学习目标表
    $createLearningGoalsTable = "
    CREATE TABLE IF NOT EXISTS learning_goals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        goal_hours REAL NOT NULL,
        goal_description TEXT,
        start_date DATE,
        end_date DATE,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    ";
    
    // 创建教学评估表
    $createTeachingEvaluationsTable = "
    CREATE TABLE IF NOT EXISTS teaching_evaluations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        teacher_name VARCHAR(100) NOT NULL,
        teacher_id INTEGER,
        course_name VARCHAR(255) NOT NULL,
        course_id INTEGER,
        evaluation_date DATE NOT NULL,
        evaluation_content TEXT,
        suggestions TEXT,
        score REAL DEFAULT 0,
        status VARCHAR(20) DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    );
    ";
    
    // 创建用户反馈表
    $createFeedbacksTable = "
    CREATE TABLE IF NOT EXISTS feedbacks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(100) NOT NULL,
        user_id INTEGER,
        feedback_content TEXT NOT NULL,
        feedback_type VARCHAR(50) DEFAULT 'general',
        status VARCHAR(20) DEFAULT 'pending',
        reply_content TEXT,
        replied_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    ";
    
    // 创建会话表
    $createSessionsTable = "
    CREATE TABLE IF NOT EXISTS sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at DATETIME NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    ";
    
    // 执行创建表的语句
    $db->exec($createUsersTable);
    $db->exec($createVerificationsTable);
    $db->exec($createResourcesTable);
    $db->exec($createKnowledgePointsTable);
    $db->exec($createKnowledgeRelationsTable);
    $db->exec($createCoursesTable);
    $db->exec($createLearningPathsTable);
    $db->exec($createLearningPathDetailsTable);
    $db->exec($createLearningProgressTable);
    $db->exec($createLearningGoalsTable);
    $db->exec($createTeachingEvaluationsTable);
    $db->exec($createFeedbacksTable);
    $db->exec($createSessionsTable);
    
    // 创建索引
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_verifications_phone ON verifications(phone);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_resources_author ON course_resources(author_id);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_knowledge_points_course ON knowledge_points(course_id);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_knowledge_relations_source ON knowledge_relations(source_point_id);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_learning_progress_user ON learning_progress(user_id);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_learning_paths_user ON learning_paths(user_id);");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedbacks_user ON feedbacks(user_id);");
    
    return true;
}

// 检查数据库是否已初始化
function isDatabaseInitialized() {
    if (!file_exists(DB_PATH)) {
        return false;
    }
    
    $db = getDBConnection();
    $result = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    return $result !== null;
}

// 初始化或检查数据库
if (!isDatabaseInitialized()) {
    initDatabase();
}
