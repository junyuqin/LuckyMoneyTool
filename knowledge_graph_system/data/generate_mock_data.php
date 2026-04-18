<?php
/**
 * 模拟数据生成脚本
 * 中职专业课程知识图谱系统
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 生成模拟数据
function generateMockData() {
    $db = getDBConnection();
    
    echo "开始生成模拟数据...\n";
    
    // 生成教师用户
    $teachers = [
        ['username' => 'teacher_zhang', 'phone' => '13800138001', 'password' => 'Teacher@123'],
        ['username' => 'teacher_li', 'phone' => '13800138002', 'password' => 'Teacher@456'],
        ['username' => 'teacher_wang', 'phone' => '13800138003', 'password' => 'Teacher@789'],
    ];
    
    foreach ($teachers as $teacher) {
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO users (username, phone, password, created_at) 
            VALUES (:username, :phone, :password, datetime('now'))
        ");
        $stmt->bindValue(':username', $teacher['username'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $teacher['phone'], SQLITE3_TEXT);
        $stmt->bindValue(':password', hashPassword($teacher['password']), SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "已生成教师用户\n";
    
    // 生成学生用户
    $students = [
        ['username' => 'student001', 'phone' => '13900139001', 'password' => 'Student@123'],
        ['username' => 'student002', 'phone' => '13900139002', 'password' => 'Student@456'],
        ['username' => 'student003', 'phone' => '13900139003', 'password' => 'Student@789'],
        ['username' => 'student004', 'phone' => '13900139004', 'password' => 'Student@012'],
        ['username' => 'student005', 'phone' => '13900139005', 'password' => 'Student@345'],
    ];
    
    foreach ($students as $student) {
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO users (username, phone, password, created_at) 
            VALUES (:username, :phone, :password, datetime('now'))
        ");
        $stmt->bindValue(':username', $student['username'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $student['phone'], SQLITE3_TEXT);
        $stmt->bindValue(':password', hashPassword($student['password']), SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "已生成学生用户\n";
    
    // 生成课程
    $courses = [
        ['course_name' => '计算机基础', 'course_code' => 'CS101', 'major' => '计算机应用', 'credit_hours' => 64],
        ['course_name' => '编程语言基础', 'course_code' => 'CS102', 'major' => '计算机应用', 'credit_hours' => 72],
        ['course_name' => '数据库原理', 'course_code' => 'CS201', 'major' => '软件工程', 'credit_hours' => 64],
        ['course_name' => '网页设计与制作', 'course_code' => 'CS202', 'major' => '数字媒体', 'credit_hours' => 56],
        ['course_name' => '计算机网络', 'course_code' => 'CS301', 'major' => '网络技术', 'credit_hours' => 64],
    ];
    
    foreach ($courses as $index => $course) {
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO courses (course_name, course_code, description, major, credit_hours, teacher_id, created_at) 
            VALUES (:course_name, :course_code, :description, :major, :credit_hours, :teacher_id, datetime('now'))
        ");
        $stmt->bindValue(':course_name', $course['course_name'], SQLITE3_TEXT);
        $stmt->bindValue(':course_code', $course['course_code'], SQLITE3_TEXT);
        $stmt->bindValue(':description', '这是' . $course['course_name'] . '课程的详细介绍', SQLITE3_TEXT);
        $stmt->bindValue(':major', $course['major'], SQLITE3_TEXT);
        $stmt->bindValue(':credit_hours', $course['credit_hours'], SQLITE3_INTEGER);
        $stmt->bindValue(':teacher_id', ($index % 3) + 1, SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo "已生成课程\n";
    
    // 生成知识点
    $knowledgePoints = [
        // 计算机基础知识点
        ['point_name' => '计算机概述', 'point_code' => 'KP001', 'course_id' => 1, 'parent_id' => null, 'difficulty_level' => 1, 'estimated_hours' => 2.0],
        ['point_name' => '计算机硬件组成', 'point_code' => 'KP002', 'course_id' => 1, 'parent_id' => 1, 'difficulty_level' => 2, 'estimated_hours' => 3.0],
        ['point_name' => '计算机软件系统', 'point_code' => 'KP003', 'course_id' => 1, 'parent_id' => 1, 'difficulty_level' => 2, 'estimated_hours' => 3.0],
        ['point_name' => '操作系统基础', 'point_code' => 'KP004', 'course_id' => 1, 'parent_id' => 3, 'difficulty_level' => 3, 'estimated_hours' => 4.0],
        ['point_name' => '文件管理', 'point_code' => 'KP005', 'course_id' => 1, 'parent_id' => 4, 'difficulty_level' => 2, 'estimated_hours' => 2.0],
        
        // 编程语言基础知识点
        ['point_name' => '编程基础概念', 'point_code' => 'KP006', 'course_id' => 2, 'parent_id' => null, 'difficulty_level' => 1, 'estimated_hours' => 2.0],
        ['point_name' => '变量与数据类型', 'point_code' => 'KP007', 'course_id' => 2, 'parent_id' => 6, 'difficulty_level' => 2, 'estimated_hours' => 3.0],
        ['point_name' => '控制结构', 'point_code' => 'KP008', 'course_id' => 2, 'parent_id' => 6, 'difficulty_level' => 3, 'estimated_hours' => 4.0],
        ['point_name' => '函数与模块', 'point_code' => 'KP009', 'course_id' => 2, 'parent_id' => 6, 'difficulty_level' => 3, 'estimated_hours' => 4.0],
        ['point_name' => '面向对象编程', 'point_code' => 'KP010', 'course_id' => 2, 'parent_id' => 6, 'difficulty_level' => 4, 'estimated_hours' => 6.0],
        
        // 数据库原理知识点
        ['point_name' => '数据库概述', 'point_code' => 'KP011', 'course_id' => 3, 'parent_id' => null, 'difficulty_level' => 1, 'estimated_hours' => 2.0],
        ['point_name' => '关系型数据库', 'point_code' => 'KP012', 'course_id' => 3, 'parent_id' => 11, 'difficulty_level' => 2, 'estimated_hours' => 3.0],
        ['point_name' => 'SQL语言基础', 'point_code' => 'KP013', 'course_id' => 3, 'parent_id' => 11, 'difficulty_level' => 3, 'estimated_hours' => 5.0],
        ['point_name' => '数据库设计', 'point_code' => 'KP014', 'course_id' => 3, 'parent_id' => 11, 'difficulty_level' => 4, 'estimated_hours' => 5.0],
        ['point_name' => '数据库优化', 'point_code' => 'KP015', 'course_id' => 3, 'parent_id' => 14, 'difficulty_level' => 5, 'estimated_hours' => 4.0],
        
        // 网页设计知识点
        ['point_name' => 'HTML基础', 'point_code' => 'KP016', 'course_id' => 4, 'parent_id' => null, 'difficulty_level' => 1, 'estimated_hours' => 3.0],
        ['point_name' => 'CSS样式', 'point_code' => 'KP017', 'course_id' => 4, 'parent_id' => 16, 'difficulty_level' => 2, 'estimated_hours' => 4.0],
        ['point_name' => 'JavaScript基础', 'point_code' => 'KP018', 'course_id' => 4, 'parent_id' => 16, 'difficulty_level' => 3, 'estimated_hours' => 5.0],
        ['point_name' => '响应式设计', 'point_code' => 'KP019', 'course_id' => 4, 'parent_id' => 17, 'difficulty_level' => 4, 'estimated_hours' => 4.0],
        ['point_name' => '前端框架', 'point_code' => 'KP020', 'course_id' => 4, 'parent_id' => 18, 'difficulty_level' => 5, 'estimated_hours' => 6.0],
        
        // 计算机网络知识点
        ['point_name' => '网络基础', 'point_code' => 'KP021', 'course_id' => 5, 'parent_id' => null, 'difficulty_level' => 1, 'estimated_hours' => 3.0],
        ['point_name' => 'TCP/IP协议', 'point_code' => 'KP022', 'course_id' => 5, 'parent_id' => 21, 'difficulty_level' => 3, 'estimated_hours' => 5.0],
        ['point_name' => '网络设备', 'point_code' => 'KP023', 'course_id' => 5, 'parent_id' => 21, 'difficulty_level' => 2, 'estimated_hours' => 3.0],
        ['point_name' => '网络安全', 'point_code' => 'KP024', 'course_id' => 5, 'parent_id' => 21, 'difficulty_level' => 4, 'estimated_hours' => 5.0],
        ['point_name' => '网络编程', 'point_code' => 'KP025', 'course_id' => 5, 'parent_id' => 22, 'difficulty_level' => 5, 'estimated_hours' => 6.0],
    ];
    
    foreach ($knowledgePoints as $kp) {
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO knowledge_points 
            (point_name, point_code, description, course_id, parent_id, difficulty_level, estimated_hours, order_index, created_at) 
            VALUES (:point_name, :point_code, :description, :course_id, :parent_id, :difficulty_level, :estimated_hours, :order_index, datetime('now'))
        ");
        $stmt->bindValue(':point_name', $kp['point_name'], SQLITE3_TEXT);
        $stmt->bindValue(':point_code', $kp['point_code'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $kp['point_name'] . '的详细讲解，包括基本概念、应用场景和实践案例', SQLITE3_TEXT);
        $stmt->bindValue(':course_id', $kp['course_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':parent_id', $kp['parent_id'], $kp['parent_id'] === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $stmt->bindValue(':difficulty_level', $kp['difficulty_level'], SQLITE3_INTEGER);
        $stmt->bindValue(':estimated_hours', $kp['estimated_hours'], SQLITE3_FLOAT);
        $stmt->bindValue(':order_index', $kp['difficulty_level'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo "已生成知识点\n";
    
    // 生成知识点关联
    $relations = [
        [1, 2, '包含'], [1, 3, '包含'], [3, 4, '前置'], [4, 5, '包含'],
        [6, 7, '包含'], [6, 8, '包含'], [6, 9, '包含'], [6, 10, '包含'],
        [7, 8, '前置'], [8, 9, '前置'], [9, 10, '前置'],
        [11, 12, '包含'], [11, 13, '包含'], [11, 14, '包含'], [14, 15, '进阶'],
        [12, 13, '前置'], [13, 14, '前置'],
        [16, 17, '包含'], [16, 18, '包含'], [17, 19, '进阶'], [18, 20, '进阶'],
        [17, 18, '前置'], [19, 20, '相关'],
        [21, 22, '包含'], [21, 23, '包含'], [21, 24, '包含'], [22, 25, '进阶'],
        [22, 24, '相关'], [23, 24, '相关'],
        // 跨课程关联
        [5, 13, '应用'], [10, 20, '应用'], [13, 25, '应用'],
    ];
    
    foreach ($relations as $rel) {
        $stmt = $db->prepare("
            INSERT INTO knowledge_relations (source_point_id, target_point_id, relation_type, strength, created_at) 
            VALUES (:source_id, :target_id, :relation_type, :strength, datetime('now'))
        ");
        $stmt->bindValue(':source_id', $rel[0], SQLITE3_INTEGER);
        $stmt->bindValue(':target_id', $rel[1], SQLITE3_INTEGER);
        $stmt->bindValue(':relation_type', $rel[2], SQLITE3_TEXT);
        $stmt->bindValue(':strength', 0.8 + (rand(0, 20) / 100), SQLITE3_FLOAT);
        $stmt->execute();
    }
    echo "已生成知识点关联\n";
    
    // 生成课程资源
    $resources = [
        ['resource_name' => '计算机基础教程PDF', 'resource_type' => 'document', 'course_id' => 1],
        ['resource_name' => '计算机硬件介绍视频', 'resource_type' => 'video', 'course_id' => 1],
        ['resource_name' => '操作系统实验指导', 'resource_type' => 'document', 'course_id' => 1],
        ['resource_name' => 'Python编程入门', 'resource_type' => 'document', 'course_id' => 2],
        ['resource_name' => '面向对象编程详解', 'resource_type' => 'video', 'course_id' => 2],
        ['resource_name' => 'SQL语法速查表', 'resource_type' => 'document', 'course_id' => 3],
        ['resource_name' => '数据库设计案例', 'resource_type' => 'document', 'course_id' => 3],
        ['resource_name' => 'MySQL安装配置视频', 'resource_type' => 'video', 'course_id' => 3],
        ['resource_name' => 'HTML5快速上手', 'resource_type' => 'document', 'course_id' => 4],
        ['resource_name' => 'CSS3动画效果', 'resource_type' => 'video', 'course_id' => 4],
        ['resource_name' => 'JavaScript实战项目', 'resource_type' => 'document', 'course_id' => 4],
        ['resource_name' => '网络协议分析', 'resource_type' => 'document', 'course_id' => 5],
        ['resource_name' => '网络安全攻防演练', 'resource_type' => 'video', 'course_id' => 5],
        ['resource_name' => 'Socket编程指南', 'resource_type' => 'document', 'course_id' => 5],
    ];
    
    foreach ($resources as $index => $res) {
        $stmt = $db->prepare("
            INSERT INTO course_resources 
            (resource_name, resource_type, resource_description, file_path, author_id, author_name, category, file_size, upload_time) 
            VALUES (:resource_name, :resource_type, :description, :file_path, :author_id, :author_name, :category, :file_size, datetime('now'))
        ");
        $stmt->bindValue(':resource_name', $res['resource_name'], SQLITE3_TEXT);
        $stmt->bindValue(':resource_type', $res['resource_type'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $res['resource_name'] . '是' . ($res['resource_type'] === 'video' ? '视频' : '文档') . '资源，适合初学者学习', SQLITE3_TEXT);
        $stmt->bindValue(':file_path', 'uploads/' . $res['resource_type'] . 's/sample_' . ($index + 1) . '.' . ($res['resource_type'] === 'video' ? 'mp4' : 'pdf'), SQLITE3_TEXT);
        $stmt->bindValue(':author_id', ($index % 3) + 1, SQLITE3_INTEGER);
        $stmt->bindValue(':author_name', 'teacher_' . ['zhang', 'li', 'wang'][$index % 3], SQLITE3_TEXT);
        $stmt->bindValue(':category', ['基础教程', '进阶学习', '实践案例'][$index % 3], SQLITE3_TEXT);
        $stmt->bindValue(':file_size', rand(100000, 50000000), SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo "已生成课程资源\n";
    
    // 生成学习路径
    $learningPaths = [
        ['user_id' => 4, 'path_name' => '计算机基础学习路径', 'description' => '从零开始学习计算机基础知识'],
        ['user_id' => 5, 'path_name' => 'Web开发学习路径', 'description' => '系统学习Web前端开发技术'],
        ['user_id' => 6, 'path_name' => '数据库工程师路径', 'description' => '成为专业数据库工程师的学习路线'],
    ];
    
    foreach ($learningPaths as $path) {
        $stmt = $db->prepare("
            INSERT INTO learning_paths (user_id, path_name, description, total_hours, progress, status, created_at, updated_at) 
            VALUES (:user_id, :path_name, :description, :total_hours, :progress, :status, datetime('now'), datetime('now'))
        ");
        $stmt->bindValue(':user_id', $path['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':path_name', $path['path_name'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $path['description'], SQLITE3_TEXT);
        $stmt->bindValue(':total_hours', 40.0 + (rand(0, 20)), SQLITE3_FLOAT);
        $stmt->bindValue(':progress', rand(20, 80), SQLITE3_FLOAT);
        $stmt->bindValue(':status', 'active', SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "已生成学习路径\n";
    
    // 生成学习路径详情
    for ($pathId = 1; $pathId <= 3; $pathId++) {
        $pointIds = range(($pathId - 1) * 5 + 1, $pathId * 5);
        $order = 1;
        foreach ($pointIds as $pointId) {
            $stmt = $db->prepare("
                INSERT INTO learning_path_details 
                (path_id, knowledge_point_id, resource_id, sequence_order, estimated_hours, completed, completed_at) 
                VALUES (:path_id, :kp_id, :res_id, :order, :hours, :completed, :completed_at)
            ");
            $stmt->bindValue(':path_id', $pathId, SQLITE3_INTEGER);
            $stmt->bindValue(':kp_id', $pointId, SQLITE3_INTEGER);
            $stmt->bindValue(':res_id', $pointId <= 14 ? $pointId : null, $pointId <= 14 ? SQLITE3_INTEGER : SQLITE3_NULL);
            $stmt->bindValue(':order', $order, SQLITE3_INTEGER);
            $stmt->bindValue(':hours', 2.0 + (rand(0, 4)), SQLITE3_FLOAT);
            $stmt->bindValue(':completed', $order <= 2 ? 1 : 0, SQLITE3_INTEGER);
            $stmt->bindValue(':completed_at', $order <= 2 ? date('Y-m-d H:i:s') : null, $order <= 2 ? SQLITE3_TEXT : SQLITE3_NULL);
            $stmt->execute();
            $order++;
        }
    }
    echo "已生成学习路径详情\n";
    
    // 生成学习进度
    for ($userId = 4; $userId <= 8; $userId++) {
        for ($kpId = 1; $kpId <= 25; $kpId++) {
            $studyTime = rand(0, 10) + (rand(0, 100) / 100);
            $score = $studyTime > 0 ? rand(60, 100) : 0;
            $status = $studyTime > 5 ? 'completed' : ($studyTime > 0 ? 'in_progress' : 'not_started');
            
            $stmt = $db->prepare("
                INSERT INTO learning_progress 
                (user_id, knowledge_point_id, study_time, score, status, last_study_at, created_at, updated_at) 
                VALUES (:user_id, :kp_id, :study_time, :score, :status, :last_study, datetime('now'), datetime('now'))
            ");
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':kp_id', $kpId, SQLITE3_INTEGER);
            $stmt->bindValue(':study_time', $studyTime, SQLITE3_FLOAT);
            $stmt->bindValue(':score', $score, SQLITE3_FLOAT);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':last_study', $studyTime > 0 ? date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')) : null, $studyTime > 0 ? SQLITE3_TEXT : SQLITE3_NULL);
            $stmt->execute();
        }
    }
    echo "已生成学习进度\n";
    
    // 生成学习目标
    for ($userId = 4; $userId <= 8; $userId++) {
        $stmt = $db->prepare("
            INSERT INTO learning_goals 
            (user_id, goal_hours, goal_description, start_date, end_date, status, created_at, updated_at) 
            VALUES (:user_id, :goal_hours, :description, :start_date, :end_date, :status, datetime('now'), datetime('now'))
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':goal_hours', 50 + (rand(0, 50)), SQLITE3_FLOAT);
        $stmt->bindValue(':description', '完成本学期学习目标', SQLITE3_TEXT);
        $stmt->bindValue(':start_date', date('Y-m-01'), SQLITE3_TEXT);
        $stmt->bindValue(':end_date', date('Y-m-t', strtotime('+2 months')), SQLITE3_TEXT);
        $stmt->bindValue(':status', 'active', SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "已生成学习目标\n";
    
    // 生成教学评估
    $evaluations = [
        ['teacher_name' => '张老师', 'course_name' => '计算机基础', 'score' => 88],
        ['teacher_name' => '李老师', 'course_name' => '编程语言基础', 'score' => 92],
        ['teacher_name' => '王老师', 'course_name' => '数据库原理', 'score' => 85],
        ['teacher_name' => '张老师', 'course_name' => '网页设计与制作', 'score' => 90],
        ['teacher_name' => '李老师', 'course_name' => '计算机网络', 'score' => 87],
    ];
    
    foreach ($evaluations as $eval) {
        $stmt = $db->prepare("
            INSERT INTO teaching_evaluations 
            (teacher_name, course_name, evaluation_date, evaluation_content, suggestions, score, status, created_at, updated_at) 
            VALUES (:teacher_name, :course_name, :eval_date, :content, :suggestions, :score, :status, datetime('now'), datetime('now'))
        ");
        $stmt->bindValue(':teacher_name', $eval['teacher_name'], SQLITE3_TEXT);
        $stmt->bindValue(':course_name', $eval['course_name'], SQLITE3_TEXT);
        $stmt->bindValue(':eval_date', date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')), SQLITE3_TEXT);
        $stmt->bindValue(':content', '本课程教学内容丰富，学生参与度高，教学效果良好。', SQLITE3_TEXT);
        $stmt->bindValue(':suggestions', '建议增加实践环节，加强学生的动手能力培养。', SQLITE3_TEXT);
        $stmt->bindValue(':score', $eval['score'], SQLITE3_FLOAT);
        $stmt->bindValue(':status', 'completed', SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "已生成教学评估\n";
    
    // 生成用户反馈
    $feedbacks = [
        ['username' => 'student001', 'content' => '系统很好用，希望能增加更多视频教程'],
        ['username' => 'student002', 'content' => '知识点关联图很清晰，对学习帮助很大'],
        ['username' => 'student003', 'content' => '学习路径推荐功能很实用，建议可以自定义路径'],
        ['username' => 'student004', 'content' => '界面简洁美观，操作流畅'],
        ['username' => 'student005', 'content' => '希望增加移动端适配，方便随时随地学习'],
    ];
    
    foreach ($feedbacks as $fb) {
        $stmt = $db->prepare("
            INSERT INTO feedbacks 
            (username, feedback_content, feedback_type, status, created_at) 
            VALUES (:username, :content, :type, :status, datetime('now'))
        ");
        $stmt->bindValue(':username', $fb['username'], SQLITE3_TEXT);
        $stmt->bindValue(':content', $fb['content'], SQLITE3_TEXT);
        $stmt->bindValue(':type', ['feature_request', 'positive', 'suggestion', 'ui_feedback', 'mobile'][array_rand(['feature_request', 'positive', 'suggestion', 'ui_feedback', 'mobile'])], SQLITE3_TEXT);
        $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "已生成用户反馈\n";
    
    echo "\n模拟数据生成完成！\n";
    echo "=====================================\n";
    echo "测试账号信息：\n";
    echo "教师账号:\n";
    foreach ($teachers as $t) {
        echo "  用户名: {$t['username']}, 手机号: {$t['phone']}, 密码: {$t['password']}\n";
    }
    echo "\n学生账号:\n";
    foreach ($students as $s) {
        echo "  用户名: {$s['username']}, 手机号: {$s['phone']}, 密码: {$s['password']}\n";
    }
    echo "=====================================\n";
}

// 执行生成
generateMockData();
