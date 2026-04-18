<?php
/**
 * 中职技能竞赛训练辅助系统 - 公共样式文件
 */
header('Content-Type: text/css; charset=utf-8');
?>

/* 全局样式重置 */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* 基础变量定义 */
:root {
    --primary-color: #3498db;
    --primary-dark: #2980b9;
    --secondary-color: #2ecc71;
    --danger-color: #e74c3c;
    --warning-color: #f39c12;
    --info-color: #1abc9c;
    --dark-color: #2c3e50;
    --light-color: #ecf0f1;
    --gray-color: #95a5a6;
    --border-color: #ddd;
    --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 5px 20px rgba(0, 0, 0, 0.15);
    --radius: 8px;
    --transition: all 0.3s ease;
}

/* 基础样式 */
body {
    font-family: 'Microsoft YaHei', 'PingFang SC', 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #333;
    background-color: #f5f6fa;
    min-height: 100vh;
}

/* 容器样式 */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.container-fluid {
    width: 100%;
    padding: 0 15px;
}

/* 头部导航样式 */
.header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: #fff;
    padding: 15px 0;
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-icon {
    font-size: 28px;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 5px;
}

.nav-menu li {
    position: relative;
}

.nav-menu a {
    color: #fff;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: var(--radius);
    transition: var(--transition);
    display: block;
}

.nav-menu a:hover,
.nav-menu a.active {
    background-color: rgba(255, 255, 255, 0.2);
}

.nav-menu .dropdown {
    position: relative;
}

.nav-menu .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    min-width: 180px;
    box-shadow: var(--shadow-hover);
    border-radius: var(--radius);
    padding: 10px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition);
    z-index: 1001;
}

.nav-menu .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.nav-menu .dropdown-menu a {
    color: var(--dark-color);
    padding: 10px 20px;
}

.nav-menu .dropdown-menu a:hover {
    background-color: var(--light-color);
    color: var(--primary-color);
}

.nav-menu .divider {
    height: 1px;
    background-color: var(--border-color);
    margin: 8px 0;
}

/* 用户信息区域 */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #fff;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.user-name {
    color: #fff;
    font-weight: 500;
}

/* 主体内容区域 */
.main-content {
    padding: 30px 0;
    min-height: calc(100vh - 180px);
}

/* 卡片样式 */
.card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--shadow-hover);
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    background-color: #fafafa;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark-color);
    margin: 0;
}

.card-body {
    padding: 20px;
}

.card-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    background-color: #fafafa;
}

/* 按钮样式 */
.btn {
    display: inline-block;
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: #fff;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: #fff;
}

.btn-secondary:hover {
    background-color: #27ae60;
}

.btn-danger {
    background-color: var(--danger-color);
    color: #fff;
}

.btn-danger:hover {
    background-color: #c0392b;
}

.btn-warning {
    background-color: var(--warning-color);
    color: #fff;
}

.btn-warning:hover {
    background-color: #e67e22;
}

.btn-info {
    background-color: var(--info-color);
    color: #fff;
}

.btn-info:hover {
    background-color: #16a085;
}

.btn-outline {
    background-color: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color);
    color: #fff;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 12px;
}

.btn-lg {
    padding: 14px 32px;
    font-size: 16px;
}

.btn-block {
    display: block;
    width: 100%;
}

.btn-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* 表单样式 */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-color);
}

.form-label .required {
    color: var(--danger-color);
    margin-left: 3px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    font-size: 14px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    transition: var(--transition);
    background-color: #fff;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-control::placeholder {
    color: var(--gray-color);
}

.form-control.error {
    border-color: var(--danger-color);
}

.form-control.success {
    border-color: var(--secondary-color);
}

.form-hint {
    font-size: 12px;
    color: var(--gray-color);
    margin-top: 5px;
}

.form-error {
    font-size: 12px;
    color: var(--danger-color);
    margin-top: 5px;
}

/* 密码强度指示器 */
.password-strength {
    margin-top: 10px;
}

.strength-bar {
    height: 6px;
    background-color: var(--border-color);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    width: 0;
    transition: var(--transition);
    border-radius: 3px;
}

.strength-fill.weak {
    width: 33%;
    background-color: var(--danger-color);
}

.strength-fill.medium {
    width: 66%;
    background-color: var(--warning-color);
}

.strength-fill.strong {
    width: 100%;
    background-color: var(--secondary-color);
}

.strength-text {
    font-size: 12px;
    color: var(--gray-color);
}

/* 表格样式 */
.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.table th,
.table td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.table th {
    background-color: #fafafa;
    font-weight: 600;
    color: var(--dark-color);
    white-space: nowrap;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table-striped tbody tr:nth-child(odd) {
    background-color: #fafafa;
}

.table-striped tbody tr:nth-child(odd):hover {
    background-color: #f8f9fa;
}

/* 徽章样式 */
.badge {
    display: inline-block;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 20px;
    white-space: nowrap;
}

.badge-primary {
    background-color: var(--primary-color);
    color: #fff;
}

.badge-success {
    background-color: var(--secondary-color);
    color: #fff;
}

.badge-danger {
    background-color: var(--danger-color);
    color: #fff;
}

.badge-warning {
    background-color: var(--warning-color);
    color: #fff;
}

.badge-info {
    background-color: var(--info-color);
    color: #fff;
}

.badge-secondary {
    background-color: var(--gray-color);
    color: #fff;
}

/* 警告框样式 */
.alert {
    padding: 15px 20px;
    border-radius: var(--radius);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error,
.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* 网格系统 */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col {
    flex: 1;
    padding: 0 15px;
}

.col-1 { flex: 0 0 8.333%; max-width: 8.333%; }
.col-2 { flex: 0 0 16.666%; max-width: 16.666%; }
.col-3 { flex: 0 0 25%; max-width: 25%; }
.col-4 { flex: 0 0 33.333%; max-width: 33.333%; }
.col-6 { flex: 0 0 50%; max-width: 50%; }
.col-8 { flex: 0 0 66.666%; max-width: 66.666%; }
.col-12 { flex: 0 0 100%; max-width: 100%; }

/* 统计卡片 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.stat-icon.primary {
    background-color: rgba(52, 152, 219, 0.1);
    color: var(--primary-color);
}

.stat-icon.success {
    background-color: rgba(46, 204, 113, 0.1);
    color: var(--secondary-color);
}

.stat-icon.warning {
    background-color: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.stat-icon.danger {
    background-color: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: var(--dark-color);
    line-height: 1.2;
}

.stat-label {
    font-size: 14px;
    color: var(--gray-color);
    margin-top: 5px;
}

/* 进度条样式 */
.progress-container {
    margin-bottom: 20px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.progress-bar {
    height: 10px;
    background-color: var(--border-color);
    border-radius: 5px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    border-radius: 5px;
    transition: width 0.5s ease;
}

/* 分页样式 */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    list-style: none;
    margin-top: 30px;
}

.pagination li a,
.pagination li span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    color: var(--dark-color);
    text-decoration: none;
    transition: var(--transition);
}

.pagination li a:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: #fff;
}

.pagination li.active span {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: #fff;
}

.pagination li.disabled span {
    color: var(--gray-color);
    cursor: not-allowed;
}

/* 标签页样式 */
.tabs {
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 20px;
}

.tab-list {
    display: flex;
    list-style: none;
    gap: 5px;
}

.tab-item {
    margin-bottom: -2px;
}

.tab-link {
    display: block;
    padding: 12px 24px;
    color: var(--dark-color);
    text-decoration: none;
    border: 2px solid transparent;
    border-bottom: none;
    border-radius: var(--radius) var(--radius) 0 0;
    transition: var(--transition);
    cursor: pointer;
}

.tab-link:hover {
    color: var(--primary-color);
}

.tab-link.active {
    color: var(--primary-color);
    border-color: var(--border-color);
    border-bottom-color: #fff;
    background-color: #fff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* 模态框样式 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow-hover);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.9);
    transition: var(--transition);
}

.modal-overlay.active .modal {
    transform: scale(1);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark-color);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--gray-color);
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: var(--transition);
}

.modal-close:hover {
    background-color: var(--light-color);
    color: var(--dark-color);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* 空状态样式 */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-color);
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state-title {
    font-size: 20px;
    font-weight: 500;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.empty-state-description {
    font-size: 14px;
    margin-bottom: 20px;
}

/* 加载动画 */
.loading {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--border-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 页脚样式 */
.footer {
    background-color: var(--dark-color);
    color: #fff;
    padding: 30px 0;
    margin-top: 50px;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.footer-links {
    display: flex;
    gap: 20px;
    list-style: none;
}

.footer-links a {
    color: #fff;
    text-decoration: none;
    opacity: 0.8;
    transition: var(--transition);
}

.footer-links a:hover {
    opacity: 1;
}

.footer-copyright {
    opacity: 0.7;
    font-size: 13px;
}

/* 登录注册页面专用样式 */
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    padding: 20px;
}

.auth-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow-hover);
    padding: 40px;
    width: 100%;
    max-width: 450px;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-logo {
    font-size: 48px;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.auth-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.auth-subtitle {
    color: var(--gray-color);
    font-size: 14px;
}

.auth-divider {
    display: flex;
    align-items: center;
    margin: 25px 0;
    color: var(--gray-color);
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background-color: var(--border-color);
}

.auth-divider span {
    padding: 0 15px;
    font-size: 13px;
}

.auth-footer {
    text-align: center;
    margin-top: 25px;
    color: var(--gray-color);
    font-size: 14px;
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}

/* 验证码输入框 */
.verification-input-group {
    display: flex;
    gap: 10px;
}

.verification-input-group .form-control {
    flex: 1;
}

.verification-btn {
    white-space: nowrap;
}

/* 考试页面样式 */
.exam-container {
    max-width: 900px;
    margin: 0 auto;
}

.exam-header {
    background: #fff;
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
}

.exam-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.exam-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.exam-info-label {
    color: var(--gray-color);
    font-size: 13px;
}

.exam-info-value {
    font-weight: 600;
    color: var(--dark-color);
}

.question-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 25px;
    margin-bottom: 20px;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.question-number {
    font-weight: 600;
    color: var(--primary-color);
}

.question-score {
    color: var(--gray-color);
    font-size: 13px;
}

.question-content {
    font-size: 16px;
    line-height: 1.8;
    margin-bottom: 20px;
}

.question-options {
    list-style: none;
}

.question-option {
    padding: 15px 20px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius);
    margin-bottom: 10px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 12px;
}

.question-option:hover {
    border-color: var(--primary-color);
    background-color: rgba(52, 152, 219, 0.05);
}

.question-option.selected {
    border-color: var(--primary-color);
    background-color: rgba(52, 152, 219, 0.1);
}

.question-option.correct {
    border-color: var(--secondary-color);
    background-color: rgba(46, 204, 113, 0.1);
}

.question-option.incorrect {
    border-color: var(--danger-color);
    background-color: rgba(231, 76, 60, 0.1);
}

.option-marker {
    width: 24px;
    height: 24px;
    border: 2px solid var(--border-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    transition: var(--transition);
}

.question-option.selected .option-marker {
    border-color: var(--primary-color);
    background-color: var(--primary-color);
    color: #fff;
}

/* 侧边栏布局 */
.sidebar-layout {
    display: flex;
    gap: 30px;
}

.sidebar {
    width: 250px;
    flex-shrink: 0;
}

.sidebar-menu {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.sidebar-menu-item {
    display: block;
    padding: 15px 20px;
    color: var(--dark-color);
    text-decoration: none;
    transition: var(--transition);
    border-left: 3px solid transparent;
}

.sidebar-menu-item:hover {
    background-color: var(--light-color);
}

.sidebar-menu-item.active {
    background-color: rgba(52, 152, 219, 0.1);
    border-left-color: var(--primary-color);
    color: var(--primary-color);
}

.sidebar-content {
    flex: 1;
}

/* 响应式设计 */
@media (max-width: 768px) {
    .header .container {
        flex-direction: column;
        gap: 15px;
    }
    
    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .sidebar-layout {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
    }
    
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        width: 100%;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        min-width: 600px;
    }
}

/* 打印样式 */
@media print {
    .header,
    .footer,
    .sidebar,
    .btn,
    .no-print {
        display: none !important;
    }
    
    .main-content {
        padding: 0;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
