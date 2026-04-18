<!-- 侧边栏组件 -->
<aside class="sidebar">
    <ul class="nav-menu">
        <li>
            <a href="index.php" class="<?php echo $currentModule === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>首页</span>
            </a>
        </li>
        <li>
            <a href="modules/skill_collection.php" class="<?php echo $currentModule === 'skill_collection' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i>
                <span>技能数据采集</span>
            </a>
        </li>
        <li>
            <a href="modules/skill_evaluation.php" class="<?php echo $currentModule === 'skill_evaluation' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>技能评估分析</span>
            </a>
        </li>
        <li>
            <a href="modules/archive_generation.php" class="<?php echo $currentModule === 'archive_generation' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>成长档案生成</span>
            </a>
        </li>
        <li>
            <a href="modules/archive_display.php" class="<?php echo $currentModule === 'archive_display' ? 'active' : ''; ?>">
                <i class="fas fa-presentation"></i>
                <span>成长档案展示</span>
            </a>
        </li>
        <li>
            <a href="modules/interaction.php" class="<?php echo $currentModule === 'interaction' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i>
                <span>教师互动交流</span>
            </a>
        </li>
        <li>
            <a href="modules/statistical_report.php" class="<?php echo $currentModule === 'statistical_report' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>统计分析报告</span>
            </a>
        </li>
    </ul>
</aside>
