<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../src/QuizManager.php';

$qm = new QuizManager($pdo);
$studentId = $_SESSION['user_id'];

// Kunin ang lahat ng published quizzes
$stmt = $pdo->prepare("SELECT q.*, CONCAT(u.first_name, ' ', u.last_name) AS teacher_name 
                        FROM quizzes q 
                        JOIN users u ON q.teacher_id = u.id 
                        WHERE q.is_published = 1 
                        ORDER BY q.created_at DESC");
$stmt->execute();
$quizzes = $stmt->fetchAll();

// Kunin ang mga quiz na natapos na ng estudyanteng ito
$stmtCompleted = $pdo->prepare("SELECT quiz_id FROM quiz_attempts WHERE student_id = :sid AND is_completed = 1");
$stmtCompleted->execute(['sid' => $studentId]);
$completedQuizIds = $stmtCompleted->fetchAll(PDO::FETCH_COLUMN); // array ng quiz_id na tapos na

$pusherKey = getenv('PUSHER_APP_KEY') ?: '';
$pusherCluster = getenv('PUSHER_APP_CLUSTER') ?: 'ap1';

$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <h2>Available Quizzes</h2>
        <?php if (empty($quizzes)): ?>
            <p>No quizzes available at the moment.</p>
        <?php else: ?>
            <table id="quiz-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Teacher</th>
                        <th>Time Limit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr id="quiz-<?php echo $quiz['id']; ?>">
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['teacher_name']); ?></td>
                        <td><?php echo $quiz['time_limit_minutes'] ? $quiz['time_limit_minutes'].' min' : 'None'; ?></td>
                        <td>
                            <?php if (in_array($quiz['id'], $completedQuizIds)): ?>
                                <span style="color: #059669; font-weight: 600;">Completed</span>
                            <?php else: ?>
                                <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm">Take Quiz</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($pusherKey): ?>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const pusher = new Pusher('<?php echo $pusherKey; ?>', { cluster: '<?php echo $pusherCluster; ?>' });
    const channel = pusher.subscribe('quiz-channel');
    channel.bind('new-quiz', function(data) {
        let table = document.getElementById('quiz-table');
        if (!table) {
            location.reload();
            return;
        }
        let tbody = table.getElementsByTagName('tbody')[0];
        let row = tbody.insertRow(0);
        row.id = 'quiz-' + data.id;
        row.innerHTML = `<td>${escapeHtml(data.title)}</td>
                         <td>New Quiz</td>
                         <td>--</td>
                         <td><a href="take_quiz.php?id=${data.id}" class="btn btn-primary btn-sm">Take Quiz</a></td>`;
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