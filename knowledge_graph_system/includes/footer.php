<?php
/**
 * 公共底部文件
 * 包含页脚和脚本引用
 */

// 防止直接访问
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}
?>
    </main>
    
    <!-- 页脚 -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>关于系统</h4>
                <p>中职专业课程知识图谱系统是为中等职业学校师生设计的智能化学习平台，
                   提供课程资源整合、知识点关联可视化、学习路径推荐等功能。</p>
            </div>
            
            <div class="footer-section">
                <h4>快速链接</h4>
                <ul class="footer-links">
                    <li><a href="/knowledge_graph_system/index.php">首页</a></li>
                    <li><a href="/knowledge_graph_system/pages/resources.php">课程资源</a></li>
                    <li><a href="/knowledge_graph_system/pages/knowledge_map.php">知识图谱</a></li>
                    <li><a href="/knowledge_graph_system/pages/learning_path.php">学习路径</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>联系我们</h4>
                <ul class="contact-info">
                    <li>📧 Email: support@knowledge-graph.edu.cn</li>
                    <li>📞 电话：400-123-4567</li>
                    <li>🏠 地址：教育科技园区创新大厦</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> 中职专业课程知识图谱系统。All Rights Reserved.</p>
            <p>版本号：v1.0.0 | 技术支持：教育信息化研发中心</p>
        </div>
    </footer>
    
    <!-- JavaScript 脚本 -->
    <script src="/knowledge_graph_system/assets/js/main.js"></script>
    
    <!-- 页面特定脚本 -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- 消息提示框 -->
    <div id="toast-container" class="toast-container"></div>
</body>
</html>
