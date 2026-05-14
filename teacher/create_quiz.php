<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../src/QuizManager.php';

$qm = new QuizManager($pdo);
$error = '';
$success = '';

$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$quiz = null;
if ($quizId) {
    $quiz = $qm->getQuizById($quizId);
    if (!$quiz || $quiz['teacher_id'] != $_SESSION['user_id']) {
        die("Quiz not found or access denied.");
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Question added successfully.";
}

// --- GUMAWA NG QUIZ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_quiz') {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $time_limit   = $_POST['time_limit'] !== '' ? (int)$_POST['time_limit'] : null;
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        $newId = $qm->createQuiz($title, $description, $_SESSION['user_id'], $time_limit);
        if ($newId) {
            if ($is_published) {
                $stmt = $pdo->prepare("UPDATE quizzes SET is_published = 1 WHERE id = :id AND teacher_id = :tid");
                $stmt->execute(['id' => $newId, 'tid' => $_SESSION['user_id']]);
            }
            header("Location: create_quiz.php?quiz_id=$newId");
            exit;
        } else {
            $error = "Failed to create quiz.";
        }
    }
}

// --- MAGDAGDAG NG TANONG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question' && $quizId) {
    if (!isset($_POST['csrf_token'])) die("Missing CSRF token.");
    validateCsrfToken($_POST['csrf_token']);

    $questionText = trim($_POST['question_text'] ?? '');
    $questionType = $_POST['question_type'] ?? 'multiple_choice';
    $points       = (int)($_POST['points'] ?? 1);

    $stmt = $pdo->prepare("SELECT COALESCE(MAX(order_index), -1) AS max_idx FROM questions WHERE quiz_id = :qid");
    $stmt->execute(['qid' => $quizId]);
    $maxIndex = (int) $stmt->fetch()['max_idx'];
    $orderIndex = $maxIndex + 1;

    if (empty($questionText)) {
        $error = "Question text is required.";
    } else {
        $questionId = $qm->addQuestion($quizId, $questionText, $questionType, $points, $orderIndex);
        if ($questionId) {
            if ($questionType === 'true_false') {
                $tfCorrect = $_POST['tf_correct'] ?? 'true';
                $trueIsCorrect = ($tfCorrect === 'true') ? 1 : 0;
                $falseIsCorrect = ($tfCorrect === 'false') ? 1 : 0;
                $qm->addOption($questionId, 'True', $trueIsCorrect, 0);
                $qm->addOption($questionId, 'False', $falseIsCorrect, 1);
            } elseif ($questionType === 'multiple_choice') {
                $options = $_POST['option_text'] ?? [];
                $correctFlags = $_POST['is_correct'] ?? [];
                foreach ($options as $i => $optText) {
                    $optText = trim($optText);
                    if ($optText === '') continue;
                    $isCorrect = in_array((string)$i, $correctFlags) ? 1 : 0;
                    $qm->addOption($questionId, $optText, $isCorrect, $i);
                }
            }
            header("Location: create_quiz.php?quiz_id=$quizId&success=1");
            exit;
        } else {
            $error = "Failed to add question.";
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

        <?php if (!$quizId): ?>
            <h2>Create New Quiz</h2>
            <?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create_quiz">

                <label>Title</label>
                <input type="text" name="title" required maxlength="200"
                       style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;"
                       placeholder="e.g. First Long Examination in Philippine History">

                <label>Description</label>
                <textarea name="description" rows="3" maxlength="1000"
                          style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;"
                          placeholder="e.g. Instructions: Read each question carefully..."></textarea>

                <label>Time Limit (minutes, optional)</label>
                <input type="number" name="time_limit" min="1" max="180"
                       style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;"
                       placeholder="e.g. 30">

                <div style="max-width: 420px; margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" style="width: auto;">
                        Publish immediately (students can see this quiz)
                    </label>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Quiz & Continue</button>
            </form>

        <?php else: ?>
            <h2>Add Questions to: “<?php echo htmlspecialchars($quiz['title']); ?>”</h2>
            <p style="color:#6b7280; margin-bottom:20px;">Published: <?php echo $quiz['is_published'] ? 'Yes' : 'No'; ?></p>
            <a href="edit_quiz.php?id=<?php echo $quizId; ?>" class="btn btn-outline btn-sm">Edit Quiz Info</a>
            <hr style="margin: 20px 0;">

            <?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>

            <h3>Add New Question</h3>
            <form id="addQuestionForm" method="post" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add_question">

                <label>Question Text</label>
                <textarea name="question_text" rows="3" required
                          style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;"></textarea>

                <label>Type</label>
                <select name="question_type" id="qtype" onchange="toggleOptionsSection()"
                        style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                </select>

                <label>Points</label>
                <input type="number" name="points" value="1" min="1"
                       style="width: 100%; max-width: 420px; display: block; margin-bottom: 16px;">

                <div id="multipleChoiceOptions" style="margin-top: 15px; max-width: 420px;">
                    <label>Options <small>(A, B, C... automatically)</small></label>
                    <div id="optionsContainer">
                        <div class="option-row" style="display: flex; gap: 10px; margin-bottom: 8px;">
                            <span style="width: 24px; font-weight: bold; display: flex; align-items: center;">A</span>
                            <input type="text" name="option_text[]" placeholder="Option text" style="flex:1">
                            <label style="display: flex; align-items: center; gap: 4px; font-weight: 400;">
                                <input type="checkbox" name="is_correct[]" value="0"> Correct?
                            </label>
                        </div>
                        <div class="option-row" style="display: flex; gap: 10px; margin-bottom: 8px;">
                            <span style="width: 24px; font-weight: bold; display: flex; align-items: center;">B</span>
                            <input type="text" name="option_text[]" placeholder="Option text" style="flex:1">
                            <label style="display: flex; align-items: center; gap: 4px; font-weight: 400;">
                                <input type="checkbox" name="is_correct[]" value="1"> Correct?
                            </label>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addOptionRow()">
                        <i class="fas fa-plus"></i> Add Another Option
                    </button>
                </div>

                <div id="trueFalseOptions" style="display: none; margin-top: 15px; max-width: 420px;">
                    <label>Correct Answer</label>
                    <div style="display: flex; gap: 20px; margin-top: 5px;">
                        <label style="font-weight: 400;">
                            <input type="radio" name="tf_correct" value="true" checked> True
                        </label>
                        <label style="font-weight: 400;">
                            <input type="radio" name="tf_correct" value="false"> False
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Add Question</button>
            </form>

            <hr style="margin: 30px 0;">

            <h3>Existing Questions (<?php echo count($qm->getQuestionsWithOptions($quizId)); ?>)</h3>
            <?php foreach ($qm->getQuestionsWithOptions($quizId) as $q): ?>
                <div class="card" style="margin-bottom: 16px;">
                    <strong><?php echo htmlspecialchars($q['question_text']); ?></strong>
                    <div style="font-size: 13px; color: #6b7280; margin: 5px 0;">Type: <?php echo $q['question_type']; ?> | Points: <?php echo $q['points']; ?></div>

                    <?php if ($q['question_type'] !== 'short_answer'): ?>
                        <div style="margin-top: 10px;">
                            <p><em>Options:</em></p>
                            <?php
                            $sortedOptions = $q['options'];
                            usort($sortedOptions, function($a, $b) { return $a['order_index'] - $b['order_index']; });
                            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                            foreach ($sortedOptions as $idx => $opt):
                                $letter = $letters[$idx] ?? '?';
                            ?>
                                <p><?php echo $opt['is_correct'] ? '<strong>✔</strong>' : '○'; ?> <?php echo $letter . '. ' . htmlspecialchars($opt['option_text']); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p><em>(Short answer — manual grading)</em></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    let optionCount = 2;
    function addOptionRow() {
        const container = document.getElementById('optionsContainer');
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const letter = letters[optionCount] || '?';
        optionCount++;
        const row = document.createElement('div');
        row.className = 'option-row';
        row.style.cssText = 'display: flex; gap: 10px; margin-bottom: 8px;';
        row.innerHTML = `
            <span style="width: 24px; font-weight: bold; display: flex; align-items: center;">${letter}</span>
            <input type="text" name="option_text[]" placeholder="Option text" style="flex:1">
            <label style="display: flex; align-items: center; gap: 4px; font-weight: 400;">
                <input type="checkbox" name="is_correct[]" value="${optionCount-1}"> Correct?
            </label>
        `;
        container.appendChild(row);
    }
    function toggleOptionsSection() {
        const type = document.getElementById('qtype').value;
        document.getElementById('multipleChoiceOptions').style.display = (type === 'multiple_choice') ? 'block' : 'none';
        document.getElementById('trueFalseOptions').style.display = (type === 'true_false') ? 'block' : 'none';
    }
    toggleOptionsSection();
    function validateForm() {
        const type = document.getElementById('qtype').value;
        if (type === 'multiple_choice') {
            const optionInputs = document.querySelectorAll('input[name="option_text[]"]');
            if (!optionInputs[0] || optionInputs[0].value.trim() === '' ||
                !optionInputs[1] || optionInputs[1].value.trim() === '') {
                alert('Please fill in at least options A and B.');
                return false;
            }
        }
        return true;
    }
</script>

</div><!-- .wrapper -->
</body>
</html>