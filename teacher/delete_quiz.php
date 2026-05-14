<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../src/QuizManager.php';

$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$quizId) die("Invalid quiz ID.");

$qm = new QuizManager($pdo);
$quiz = $qm->getQuizById($quizId);
if (!$quiz || $quiz['teacher_id'] != $_SESSION['user_id']) die("Access denied.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $deleted = $qm->deleteQuiz($quizId, $_SESSION['user_id']);
        if ($deleted) {
            header("Location: quizzes.php");
            exit;
        } else {
            $error = "Deletion failed.";
        }
    } else {
        header("Location: quizzes.php");
        exit;
    }
}

$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <h2>Delete Quiz</h2>
        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>? This action cannot be undone.</p>
        <div style="display: flex; gap: 12px; align-items: center; margin-top: 20px;">
            <form method="post" style="margin:0;">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </form>
            <a href="quizzes.php" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</div>

</div><!-- .wrapper -->
</body>
</html>