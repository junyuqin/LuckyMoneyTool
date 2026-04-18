<!-- 头部组件 -->
<header class="header">
    <div class="logo">
        <i class="fas fa-graduation-cap"></i>
        <span>中职学生技能成长档案系统</span>
    </div>
    
    <div class="user-info">
        <span>
            <i class="fas fa-user-circle"></i>
            <?php echo htmlspecialchars($user['username'] ?? $user['phone']); ?>
            (<?php 
                $roleNames = ['student' => '学生', 'teacher' => '教师', 'admin' => '管理员'];
                echo $roleNames[$user['role']] ?? $user['role']; 
            ?>)
        </span>
        <a href="modules/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> 退出
        </a>
    </div>
</header>
