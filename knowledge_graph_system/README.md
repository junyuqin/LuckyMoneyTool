# 中职专业课程知识图谱系统

## 项目简介

本系统是一个面向中等职业学校的综合性课程学习与教学管理平台，旨在帮助师生更好地管理课程资源、理解知识结构、制定个性化学习计划。

## 主要功能模块

### 1. 注册模块
- 支持中国大陆手机号验证（11位合法数字）
- 三步注册流程：验证手机号 → 输入验证码 → 设置密码
- 密码强度实时检测（弱/中/强）
- 密码安全校验：不少于8位，不能包含空格，不能与手机号相同

### 2. 登录模块
- **密码登录**：用户名/手机号 + 密码
- **验证码登录**：手机号 + 短信验证码
- 记住我功能（7天免登录）
- 账户安全保护：5次密码错误锁定30分钟

### 3. 重置密码模块
- 三步找回流程：验证手机号 → 输入验证码 → 设置新密码
- 验证码有效期5分钟
- 新密码强度校验

### 4. 课程资源管理
- 教师可上传文档、视频等学习资源
- 支持资源分类管理和搜索筛选
- 资源信息包括：名称、类型、描述、作者、上传时间、文件大小
- 支持编辑和删除操作

### 5. 知识点关联图
- 图形化展示知识点之间的关联关系
- 支持关键词搜索知识点
- 点击节点查看详细信息
- 展示前置、包含、进阶等关联类型

### 6. 学习路径推荐
- 根据学习进度智能推荐学习路径
- 显示推荐学习资源及顺序
- 支持添加、删除、修改学习资源
- 个性化学习计划生成

### 7. 学习进度跟踪
- 学习目标设置（小时数）
- 各知识点学习时间和成绩记录
- 目标与实际进度对比
- 学习数据可视化展示

### 8. 教学评估管理
- 教师填写课程评估报告
- 评估结果统计分析
- 提供改进建议
- 支持查看、编辑、删除评估报告

### 9. 用户反馈与建议
- 用户提交使用体验反馈
- 提出功能改进建议
- 开发团队定期整理优化

## 技术栈

- **后端**: PHP 8.2
- **数据库**: SQLite3
- **前端**: HTML5, CSS3, JavaScript
- **架构**: MVC模式

## 目录结构

```
knowledge_graph_system/
├── index.php              # 首页
├── includes/              # 核心文件
│   ├── config.php         # 数据库配置
│   └── functions.php      # 通用函数库
├── pages/                 # 功能页面
│   ├── register.php       # 注册页面
│   ├── login.php          # 登录页面
│   ├── reset_password.php # 重置密码页面
│   ├── resources.php      # 课程资源管理
│   ├── knowledge_map.php  # 知识点关联图（待完成）
│   ├── learning_path.php  # 学习路径推荐（待完成）
│   ├── progress.php       # 学习进度跟踪（待完成）
│   ├── evaluation.php     # 教学评估管理（待完成）
│   ├── feedback.php       # 用户反馈（待完成）
│   └── logout.php         # 退出登录
├── data/                  # 数据文件
│   ├── knowledge_graph.db # SQLite数据库
│   └── generate_mock_data.php # 模拟数据生成脚本
├── assets/                # 静态资源
│   ├── css/               # 样式文件
│   └── js/                # JavaScript文件
└── uploads/               # 上传文件
    ├── documents/         # 文档资源
    └── videos/            # 视频资源
```

## 数据库设计

### 数据表列表

1. **users** - 用户表
   - id, username, phone, password, created_at, updated_at, is_active, login_attempts, locked_until

2. **verifications** - 验证码表
   - id, phone, code, type, expires_at, used, created_at

3. **courses** - 课程表
   - id, course_name, course_code, description, major, credit_hours, teacher_id, created_at

4. **knowledge_points** - 知识点表
   - id, point_name, point_code, description, course_id, parent_id, difficulty_level, estimated_hours, order_index, created_at

5. **knowledge_relations** - 知识点关联表
   - id, source_point_id, target_point_id, relation_type, strength, created_at

6. **course_resources** - 课程资源表
   - id, resource_name, resource_type, resource_description, file_path, upload_time, author_id, author_name, category, file_size, download_count

7. **learning_paths** - 学习路径表
   - id, user_id, path_name, description, total_hours, progress, status, created_at, updated_at

8. **learning_path_details** - 学习路径详情表
   - id, path_id, knowledge_point_id, resource_id, sequence_order, estimated_hours, completed, completed_at

9. **learning_progress** - 学习进度表
   - id, user_id, knowledge_point_id, study_time, score, status, last_study_at, created_at, updated_at

10. **learning_goals** - 学习目标表
    - id, user_id, goal_hours, goal_description, start_date, end_date, status, created_at, updated_at

11. **teaching_evaluations** - 教学评估表
    - id, teacher_name, teacher_id, course_name, course_id, evaluation_date, evaluation_content, suggestions, score, status, created_at, updated_at

12. **feedbacks** - 用户反馈表
    - id, username, user_id, feedback_content, feedback_type, status, reply_content, replied_at, created_at

13. **sessions** - 会话表
    - id, user_id, session_token, expires_at, ip_address, user_agent, created_at

## 安装步骤

1. 确保服务器已安装 PHP 8.2+ 和 SQLite3 扩展
2. 将项目文件部署到 Web 服务器
3. 确保 `data/` 和 `uploads/` 目录有写入权限
4. 首次访问时系统会自动创建数据库表结构
5. 运行模拟数据生成脚本（可选）:
   ```bash
   php data/generate_mock_data.php
   ```

## 测试账号

### 教师账号
| 用户名 | 手机号 | 密码 |
|--------|--------|------|
| teacher_zhang | 13800138001 | Teacher@123 |
| teacher_li | 13800138002 | Teacher@456 |
| teacher_wang | 13800138003 | Teacher@789 |

### 学生账号
| 用户名 | 手机号 | 密码 |
|--------|--------|------|
| student001 | 13900139001 | Student@123 |
| student002 | 13900139002 | Student@456 |
| student003 | 13900139003 | Student@789 |
| student004 | 13900139004 | Student@012 |
| student005 | 13900139005 | Student@345 |

## 代码统计

- 总代码行数：3130+ 行（PHP）
- 已完成模块：注册、登录、重置密码、首页、课程资源管理
- 待完成模块：知识点关联图、学习路径推荐、学习进度跟踪、教学评估、用户反馈

## 注意事项

1. 验证码功能当前为模拟实现，实际部署需接入短信服务API
2. 文件上传大小限制为100MB，可根据需要调整
3. 建议在生产环境启用HTTPS加密传输
4. 定期备份数据库文件（data/knowledge_graph.db）
5. 及时清理过期的会话和验证码数据

## 版权信息

© 2024 中职专业课程知识图谱系统 版权所有
