<?php
require_once __DIR__ . '/config/init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? $_SESSION['username'];

if ($role === 'teacher') {
    // Total Quizzes
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM quizzes WHERE teacher_id = :tid");
    $stmt->execute(['tid' => $userId]);
    $totalQuizzes = $stmt->fetch()['total'];

    // Total Questions
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM questions q JOIN quizzes z ON q.quiz_id = z.id WHERE z.teacher_id = :tid");
    $stmt->execute(['tid' => $userId]);
    $totalQuestions = $stmt->fetch()['total'];

    // Total Students (unique students who attempted any quiz of this teacher)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT qa.student_id) AS total FROM quiz_attempts qa
                           JOIN quizzes q ON qa.quiz_id = q.id
                           WHERE q.teacher_id = :tid");
    $stmt->execute(['tid' => $userId]);
    $totalStudents = $stmt->fetch()['total'];
} else {
    // Completed Quizzes
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM quiz_attempts WHERE student_id = :sid AND is_completed = 1");
    $stmt->execute(['sid' => $userId]);
    $completedQuizzes = $stmt->fetch()['total'];

    // Average Score
    $stmt = $pdo->prepare("SELECT COALESCE(ROUND(AVG(score / total_points * 100)), 0) AS avg_score FROM quiz_attempts WHERE student_id = :sid AND is_completed = 1 AND total_points > 0");
    $stmt->execute(['sid' => $userId]);
    $avgScore = $stmt->fetch()['avg_score'];

    // Total Points Earned
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(score), 0) AS total_earned FROM quiz_attempts WHERE student_id = :sid AND is_completed = 1");
    $stmt->execute(['sid' => $userId]);
    $totalEarned = $stmt->fetch()['total_earned'];
}

$basePath = '';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <!-- Page Heading -->
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 26px; font-weight: 700; color: #111827; margin-bottom: 4px;">Dashboard</h1>
        <p style="color: #6b7280; font-size: 15px;">
            <?php echo $role === 'teacher' ? 'Teaching summary at a glance' : 'Your quiz performance overview'; ?>
        </p>
    </div>

    <?php if ($role === 'teacher'): ?>
        <!-- TEACHER STAT CARDS (3 cards) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">

            <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px;">
                <div style="background: #e0e7ff; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #4338ca; font-size: 22px;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <p style="font-size: 13px; color: #6b7280; margin: 0;">Total Quizzes</p>
                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;"><?php echo $totalQuizzes; ?></p>
                </div>
            </div>

            <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px;">
                <div style="background: #d1fae5; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #065f46; font-size: 22px;">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div>
                    <p style="font-size: 13px; color: #6b7280; margin: 0;">Total Questions</p>
                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;"><?php echo $totalQuestions; ?></p>
                </div>
            </div>

            <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px;">
                <div style="background: #ede9fe; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #6d28d9; font-size: 22px;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <p style="font-size: 13px; color: #6b7280; margin: 0;">Total Students</p>
                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;"><?php echo $totalStudents; ?></p>
                </div>
            </div>
        </div>

        <!-- Professional Message for Teacher -->
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 20px; color: #374151; font-size: 14px;">
            <i class="fas fa-chalkboard-teacher" style="color: #2563eb; margin-right: 8px;"></i>
            <?php if ($totalQuizzes == 0): ?>
                You haven't created any quizzes yet. Click <strong>Create Quiz</strong> in the sidebar to get started.
            <?php else: ?>
                You have created <strong><?php echo $totalQuizzes; ?></strong> quiz(es) with a total of <strong><?php echo $totalQuestions; ?></strong> questions. 
                So far, <strong><?php echo $totalStudents; ?></strong> student(s) have participated in your quizzes.
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- STUDENT STAT CARDS (3 cards: Completed, Average Score, Total Points) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">

            <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px;">
                <div style="background: #d1fae5; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #065f46; font-size: 22px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <p style="font-size: 13px; color: #6b7280; margin: 0;">Completed</p>
                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;"><?php echo $completedQuizzes; ?></p>
                </div>
            </div>

            <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px;">
                <div style="background: #fef3c7; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #b45309; font-size: 22px;">
                    <i class="fas fa-percent"></i>
                </div>
                <div>
                    <p style="font-size: 13px; color: #6b7280; margin: 0;">Average Score</p>
                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;"><?php echo $avgScore; ?>%</p>
                </div>
            </div>

            <div style="background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px;">
                <div style="background: #e0e7ff; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #4338ca; font-size: 22px;">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <p style="font-size: 13px; color: #6b7280; margin: 0;">Total Points</p>
                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;"><?php echo $totalEarned; ?></p>
                </div>
            </div>
        </div>

        <!-- Professional Message for Student with points explanation -->
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 20px; color: #374151; font-size: 14px;">
            <i class="fas fa-lightbulb" style="color: #2563eb; margin-right: 8px;"></i>
            <?php if ($completedQuizzes == 0): ?>
                You haven't attempted any quiz yet. Head over to <strong>Available Quizzes</strong> in the sidebar to get started.
            <?php else: ?>
                You've completed <strong><?php echo $completedQuizzes; ?></strong> quiz(es) with an average of <strong><?php echo $avgScore; ?>%</strong> 
                and earned a total of <strong><?php echo $totalEarned; ?></strong> points. Keep up the great work!
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</div><!-- .wrapper -->
</body>
</html>