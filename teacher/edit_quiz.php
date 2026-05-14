<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../src/QuizManager.php';

$qm = new QuizManager($pdo);
$error = '';
$success = '';

$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$quizId) die("Invalid quiz ID.");
$quiz = $qm->getQuizById($quizId);
if (!$quiz || $quiz['teacher_id'] != $_SESSION['user_id']) die("Quiz not found or access denied.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $time_limit   = $_POST['time_limit'] !== '' ? (int)$_POST['time_limit'] : null;
    // Mananatili ang dating publish status
    $is_published = $quiz['is_published'];

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        $affected = $qm->updateQuiz($quizId, $title, $description, $time_limit, $is_published, $_SESSION['user_id']);
        if ($affected) {
            $success = "Quiz updated successfully.";
            // I-refresh ang quiz data
            $quiz = $qm->getQuizById($quizId);
        } else {
            $error = "No changes made or update failed.";
        }
    }
}

$csrf_token = generateCsrfToken();
$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <a href="quizzes.php" class="btn btn-outline" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Back to My Quizzes
        </a>
        <h2>Edit Quiz</h2>
        <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <label>Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>" required
                   style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;">

            <label>Description</label>
            <textarea name="description" rows="3"
                      style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;"><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></textarea>

            <label>Time Limit (minutes)</label>
            <input type="number" name="time_limit" min="1" max="180" value="<?php echo $quiz['time_limit_minutes'] ?? ''; ?>"
                   style="width: 100%; max-width: 420px; display: block; margin-bottom: 24px;">

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

</div><!-- .wrapper -->
</body>
</html>