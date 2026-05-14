<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../config/Env.php';

$pusherKey = getenv('PUSHER_APP_KEY') ?: '';
$pusherCluster = getenv('PUSHER_APP_CLUSTER') ?: 'ap1';

$stmt = $pdo->prepare("SELECT qa.id, qa.student_id, CONCAT(u.first_name, ' ', u.last_name) AS student_name, qa.score, qa.total_points, qa.completed_at, q.title AS quiz_title
                        FROM quiz_attempts qa
                        JOIN users u ON qa.student_id = u.id
                        JOIN quizzes q ON qa.quiz_id = q.id
                        WHERE qa.is_completed = 1
                        ORDER BY qa.completed_at DESC
                        LIMIT 20");
$stmt->execute();
$recentAttempts = $stmt->fetchAll();

$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <a href="quizzes.php" class="btn btn-outline" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Back to My Quizzes
        </a>
        <h2>Live Submissions Monitor</h2>
        <p style="margin-bottom: 20px;">Recent quiz completions appear in real time.</p>
        <table id="attempts-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Quiz</th>
                    <th>Score</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody id="attempts-body">
                <?php foreach ($recentAttempts as $att): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($att['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($att['quiz_title']); ?></td>
                        <td><?php echo $att['score']; ?>/<?php echo $att['total_points']; ?></td>
                        <td><?php echo date('M j, g:i A', strtotime($att['completed_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pusherKey): ?>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const pusher = new Pusher('<?php echo $pusherKey; ?>', { cluster: '<?php echo $pusherCluster; ?>' });
    const channel = pusher.subscribe('quiz-channel');
    channel.bind('quiz-completed', function(data) {
        const tbody = document.getElementById('attempts-body');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(data.student)}</td>
            <td>${escapeHtml(data.quiz_title)}</td>
            <td>${data.score} / ${data.total_points}</td>
            <td>${data.completed_at}</td>
        `;
        row.classList.add('new-attempt');
        tbody.prepend(row);
        setTimeout(() => row.classList.remove('new-attempt'), 3000);
    });

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
</script>
<?php endif; ?>

</div><!-- .wrapper -->
</body>
</html>