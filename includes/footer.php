    </div>
    
    <!-- 页脚 -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> 中职技能竞赛训练辅助系统 | 版权所有</p>
        <p>技术支持：系统开发团队 | 联系方式：support@vocational-skills.edu.cn</p>
        <p>
            <a href="#">使用帮助</a> | 
            <a href="#">隐私政策</a> | 
            <a href="#">服务条款</a>
        </p>
    </footer>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script>
        // 移动端菜单切换
        function toggleMobileMenu() {
            const menu = document.getElementById('navbarMenu');
            menu.classList.toggle('active');
        }
        
        // 页面加载时关闭移动端菜单
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('navbarMenu');
            if (window.innerWidth > 768) {
                menu.style.display = 'flex';
            }
        });
        
        // 窗口大小变化时调整菜单显示
        window.addEventListener('resize', function() {
            const menu = document.getElementById('navbarMenu');
            if (window.innerWidth > 768) {
                menu.style.display = 'flex';
                menu.classList.remove('active');
            } else {
                menu.style.display = '';
            }
        });
    </script>
</body>
</html>
