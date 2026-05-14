<?php
require_once '_check_auth.php';
require_once __DIR__ . '/../src/QuizManager.php';

$qm = new QuizManager($pdo);
$quizzes = $qm->getTeacherQuizzes($_SESSION['user_id']);

$basePath = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="card">
        <h2>My Quizzes</h2>

        <?php if (empty($quizzes)): ?>
            <p>You have no quizzes yet. Create one from the sidebar.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Questions</th>
                        <th>Published</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($quizzes as $q): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($q['title']); ?></td>
                        <td><?php echo $q['question_count']; ?></td>
                        <td><?php echo $q['is_published'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="edit_quiz.php?id=<?php echo $q['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="create_quiz.php?quiz_id=<?php echo $q['id']; ?>" class="btn btn-success btn-sm">Questions</a>
                            <a href="delete_quiz.php?id=<?php echo $q['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
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