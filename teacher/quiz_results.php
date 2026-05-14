<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../src/QuizManager.php';

$qm = new QuizManager($pdo);
$quizzes = $qm->getTeacherQuizzes($_SESSION['user_id']);

$selectedQuiz = null;
$results = [];
if (isset($_GET['quiz_id'])) {
    $quizId = (int)$_GET['quiz_id'];
    $selectedQuiz = $qm->getQuizById($quizId);
    if ($selectedQuiz && $selectedQuiz['teacher_id'] == $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT CONCAT(u.first_name, ' ', u.last_name) AS student_name, qa.score, qa.total_points, qa.completed_at
                                FROM quiz_attempts qa
                                JOIN users u ON qa.student_id = u.id
                                WHERE qa.quiz_id = :qid AND qa.is_completed = 1
                                ORDER BY qa.completed_at DESC");
        $stmt->execute(['qid' => $quizId]);
        $results = $stmt->fetchAll();
    }
}

$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <a href="quizzes.php" class="btn btn-outline" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Back to My Quizzes
        </a>
        <h2>Quiz Results</h2>

        <!-- Professional Select Wrapper -->
        <form method="get" style="margin-bottom: 24px;">
            <label style="margin-bottom: 8px; font-weight: 600;">Select Quiz</label>
            <div class="select-wrapper" style="position: relative; max-width: 420px;">
                <select name="quiz_id" onchange="this.form.submit()" style="
                    width: 100%;
                    padding: 12px 40px 12px 16px;
                    font-size: 15px;
                    border: 1.5px solid #d1d5db;
                    border-radius: 10px;
                    background: #fff;
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    color: #374151;
                    cursor: pointer;
                    transition: border-color 0.2s, box-shadow 0.2s;
                " onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)';" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';">
                    <option value="" disabled selected>— Select a Quiz —</option>
                    <?php foreach ($quizzes as $q): ?>
                        <option value="<?php echo $q['id']; ?>" <?php echo (isset($_GET['quiz_id']) && $_GET['quiz_id']==$q['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($q['title']); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" style="
                    position: absolute;
                    right: 16px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #6b7280;
                    pointer-events: none;
                    font-size: 14px;
                "></i>
            </div>
        </form>

        <?php if ($selectedQuiz): ?>
            <h3><?php echo htmlspecialchars($selectedQuiz['title']); ?></h3>
            <?php if (empty($results)): ?>
                <p style="color: #6b7280;">No students have completed this quiz yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                            <td><?php echo $r['score'] . '/' . $r['total_points']; ?></td>
                            <td><?php echo date('M j, Y \a\t g:i A', strtotime($r['completed_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

</div><!-- .wrapper -->
</body>
</html>