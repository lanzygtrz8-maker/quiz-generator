<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../src/QuizManager.php';

$qm = new QuizManager($pdo);
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$quizId) die("Invalid quiz.");

$quiz = $qm->getQuizById($quizId);
if (!$quiz || !$quiz['is_published']) die("Quiz not available.");

// ✅ BAWAL NA KUNG MAY COMPLETED ATTEMPT NA
$studentId = $_SESSION['user_id'];
$check = $pdo->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = :qid AND student_id = :sid AND is_completed = 1 LIMIT 1");
$check->execute(['qid' => $quizId, 'sid' => $studentId]);
if ($check->fetch()) {
    // Nakuha na niya → ibalik sa dashboard
    header("Location: dashboard.php");
    exit;
}

$questions = $qm->getQuestionsWithOptions($quizId);
if (empty($questions)) die("No questions in this quiz.");

$csrf_token = generateCsrfToken();
$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <a href="dashboard.php" class="btn btn-outline" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Back to Quizzes
        </a>
        <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
        <p><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></p>
        <?php if ($quiz['time_limit_minutes']): ?>
            <div class="timer" id="timer" style="font-size: 1.1em; margin-bottom: 16px; color: #b91c1c;">Time left: <span id="timeLeft">--:--</span></div>
        <?php endif; ?>
        <div id="message"></div>

        <form id="quizForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="quiz_id" value="<?php echo $quizId; ?>">
            <?php foreach ($questions as $index => $q): ?>
                <div style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                    <p><strong><?php echo ($index+1) . '. ' . htmlspecialchars($q['question_text']); ?></strong> (<?php echo $q['points']; ?> pts)</p>
                    <?php if ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false'): ?>
                        <?php foreach ($q['options'] as $opt): ?>
                            <label style="display: block; margin: 6px 0; font-weight: 400;">
                                <input type="radio" name="answer[<?php echo $q['id']; ?>]" value="option_<?php echo $opt['option_id']; ?>" required>
                                <?php echo htmlspecialchars($opt['option_text']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <textarea name="answer[<?php echo $q['id']; ?>]" rows="2" style="width:100%"></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" id="submitBtn" class="btn btn-primary">Submit Quiz</button>
        </form>
        <div id="result" class="alert success" style="display:none; margin-top:20px;"></div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    const quizId = <?php echo $quizId; ?>;
    const timeLimit = <?php echo $quiz['time_limit_minutes'] ? $quiz['time_limit_minutes'] * 60 : 'null'; ?>;
    let timerInterval = null;

    if (timeLimit) {
        let remaining = timeLimit;
        const timerEl = document.getElementById('timeLeft');
        function updateTimer() {
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            timerEl.textContent = `${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
            if (remaining <= 0) {
                clearInterval(timerInterval);
                document.getElementById('submitBtn').disabled = true;
                alert("Time is up! Your answers will be submitted automatically.");
                submitQuiz();
            }
            remaining--;
        }
        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }

    document.getElementById('quizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (confirm("Are you sure you want to submit all answers?")) {
            submitQuiz();
        }
    });

    function submitQuiz() {
        const form = document.getElementById('quizForm');
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        document.getElementById('message').innerHTML = '<div class="alert info">Submitting...</div>';

        fetch('submit_quiz.php', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('message').innerHTML = '<div class="alert error">'+data.error+'</div>';
                submitBtn.disabled = false;
            } else {
                if (timerInterval) clearInterval(timerInterval);
                document.getElementById('message').innerHTML = '';
                const resultDiv = document.getElementById('result');
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `<h2>Quiz Completed!</h2>
                    <p>Your Score: <strong>${data.score} / ${data.total_points}</strong></p>`;
                form.style.display = 'none';
            }
        })
        .catch(err => {
            document.getElementById('message').innerHTML = '<div class="alert error">Network error. Please try again.</div>';
            submitBtn.disabled = false;
        });
    }
</script>

</div><!-- .wrapper -->
</body>
</html>