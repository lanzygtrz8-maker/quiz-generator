<?php
header('Content-Type: application/json');

require_once '_check_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../src/QuizManager.php';

$pusherAvailable = class_exists('PusherService') && getenv('PUSHER_APP_ID') && getenv('PUSHER_APP_KEY');
if ($pusherAvailable) {
    require_once __DIR__ . '/../src/PusherService.php';
}

if (!isset($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Missing CSRF token.']);
    exit;
}
validateCsrfToken($_POST['csrf_token']);

$quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
if (!$quizId) {
    echo json_encode(['error' => 'Invalid quiz.']);
    exit;
}

$studentId = $_SESSION['user_id'];
$answers = $_POST['answer'] ?? [];

$qm = new QuizManager($pdo);
$quiz = $qm->getQuizById($quizId);
if (!$quiz || !$quiz['is_published']) {
    echo json_encode(['error' => 'Quiz not available.']);
    exit;
}

try {
    $stmt = $pdo->prepare("CALL sp_start_attempt(:quiz_id, :student_id)");
    $stmt->execute(['quiz_id' => $quizId, 'student_id' => $studentId]);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    if (!$row || !isset($row['attempt_id'])) {
        echo json_encode(['error' => 'Could not start attempt.']);
        exit;
    }
    $attemptId = $row['attempt_id'];
} catch (PDOException $e) {
    echo json_encode(['error' => 'Could not start attempt: ' . $e->getMessage()]);
    exit;
}

try {
    $stmtSubmit = $pdo->prepare("CALL sp_submit_answer(:attempt_id, :question_id, :selected_option_id, :short_answer_text)");
    foreach ($answers as $questionId => $value) {
        $selectedOptionId = null;
        $shortAnswerText = null;
        if (strpos($value, 'option_') === 0) {
            $selectedOptionId = (int)substr($value, 7);
        } else {
            $shortAnswerText = trim($value);
        }
        $stmtSubmit->execute([
            'attempt_id'         => $attemptId,
            'question_id'        => $questionId,
            'selected_option_id' => $selectedOptionId,
            'short_answer_text'  => $shortAnswerText
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error saving answers: ' . $e->getMessage()]);
    exit;
}

try {
    $stmtComplete = $pdo->prepare("CALL sp_complete_attempt(:attempt_id)");
    $stmtComplete->execute(['attempt_id' => $attemptId]);
    $stmtComplete->closeCursor();
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error completing attempt: ' . $e->getMessage()]);
    exit;
}

$stmtFetch = $pdo->prepare("SELECT score, total_points, is_completed FROM quiz_attempts WHERE id = :id");
$stmtFetch->execute(['id' => $attemptId]);
$attemptData = $stmtFetch->fetch();

if (!$attemptData) {
    echo json_encode(['error' => 'Attempt data not found.']);
    exit;
}

// --- Ipadala ang full name sa Pusher ---
if ($pusherAvailable) {
    try {
        $pusher = new PusherService();
        $pusher->trigger('quiz-channel', 'quiz-completed', [
            'attempt_id'   => $attemptId,
            'student'      => $_SESSION['full_name'] ?? $_SESSION['username'],
            'quiz_title'   => $quiz['title'],
            'score'        => $attemptData['score'],
            'total_points' => $attemptData['total_points'],
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log('Pusher error: ' . $e->getMessage());
    }
}

echo json_encode([
    'success'      => true,
    'attempt_id'   => $attemptId,
    'score'        => $attemptData['score'],
    'total_points' => $attemptData['total_points'],
    'is_completed' => (bool)$attemptData['is_completed']
]);