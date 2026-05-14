<?php
require_once '_check_auth.php';

$studentId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT q.title, qa.score, qa.total_points, qa.completed_at
                        FROM quiz_attempts qa
                        JOIN quizzes q ON qa.quiz_id = q.id
                        WHERE qa.student_id = :sid AND qa.is_completed = 1
                        ORDER BY qa.completed_at DESC");
$stmt->execute(['sid' => $studentId]);
$attempts = $stmt->fetchAll();

$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <a href="dashboard.php" class="btn btn-outline" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Back to Quizzes
        </a>
        <h2>My Quiz History</h2>

        <?php if (empty($attempts)): ?>
            <p>You have not completed any quiz yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($attempts as $att): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($att['title']); ?></td>
                        <td><?php echo $att['score'] . '/' . $att['total_points']; ?></td>
                        <td><?php echo date('M j, Y \a\t g:i A', strtotime($att['completed_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</div><!-- .wrapper -->
</body>
</html>