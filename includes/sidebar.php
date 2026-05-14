<div class="sidebar">
    <div class="user-info">
        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
    </div>
    <ul>
        <li><a href="<?php echo $basePath ?? ''; ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

        <?php if ($_SESSION['role'] === 'teacher'): ?>
            <li><a href="<?php echo $basePath ?? ''; ?>teacher/quizzes.php"><i class="fas fa-list"></i> My Quizzes</a></li>
            <li><a href="<?php echo $basePath ?? ''; ?>teacher/create_quiz.php"><i class="fas fa-plus-circle"></i> Create Quiz</a></li>
            <li><a href="<?php echo $basePath ?? ''; ?>teacher/monitor.php"><i class="fas fa-desktop"></i> Live Monitor</a></li>
            <li><a href="<?php echo $basePath ?? ''; ?>teacher/quiz_results.php"><i class="fas fa-chart-bar"></i> Results</a></li>
        <?php else: ?>
            <li><a href="<?php echo $basePath ?? ''; ?>student/dashboard.php"><i class="fas fa-book"></i> Available Quizzes</a></li>
            <li><a href="<?php echo $basePath ?? ''; ?>student/scores.php"><i class="fas fa-star"></i> My Scores</a></li>
        <?php endif; ?>
        <li class="divider"></li>
        <li><a href="<?php echo $basePath ?? ''; ?>profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
        <li><a href="<?php echo $basePath ?? ''; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>