<?php
class QuizManager {
    private $pdo;
    private $pusherService;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if (getenv('PUSHER_APP_ID') && getenv('PUSHER_APP_KEY')) {
            require_once __DIR__ . '/PusherService.php';
            $this->pusherService = new PusherService();
        } else {
            $this->pusherService = null;
        }
    }

    public function createQuiz($title, $description, $teacherId, $timeLimit = null) {
        $stmt = $this->pdo->prepare("CALL sp_create_quiz(:title, :description, :teacher_id, :time_limit)");
        $stmt->execute([
            'title'       => $title,
            'description' => $description ?: '',
            'teacher_id'  => $teacherId,
            'time_limit'  => $timeLimit
        ]);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $quizId = $row['quiz_id'] ?? $this->pdo->lastInsertId();

        if ($this->pusherService) {
            $this->pusherService->trigger('quiz-channel', 'new-quiz', [
                'id'         => $quizId,
                'title'      => $title,
                'teacher_id' => $teacherId
            ]);
        }
        return $quizId;
    }

    public function getTeacherQuizzes($teacherId) {
        $stmt = $this->pdo->prepare("CALL sp_get_teacher_quizzes(:teacher_id)");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll();
    }

    public function getQuizById($quizId) {
        $stmt = $this->pdo->prepare("CALL sp_get_quiz_by_id(:quiz_id)");
        $stmt->execute(['quiz_id' => $quizId]);
        return $stmt->fetch();
    }

    public function updateQuiz($quizId, $title, $description, $timeLimit, $isPublished, $teacherId) {
        $stmt = $this->pdo->prepare("CALL sp_update_quiz(:quiz_id, :title, :description, :time_limit, :is_published, :teacher_id)");
        $stmt->execute([
            'quiz_id'      => $quizId,
            'title'        => $title,
            'description'  => $description,
            'time_limit'   => $timeLimit,
            'is_published' => $isPublished,
            'teacher_id'   => $teacherId
        ]);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['affected_rows'] ?? 0;
    }

    public function deleteQuiz($quizId, $teacherId) {
        $stmt = $this->pdo->prepare("CALL sp_delete_quiz(:quiz_id, :teacher_id)");
        $stmt->execute(['quiz_id' => $quizId, 'teacher_id' => $teacherId]);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row['affected_rows'] ?? 0;
    }

    // ** ITO ANG KRITIKAL NA PAGBABAGO: wastong pagkuha ng result set **
    public function addQuestion($quizId, $questionText, $questionType, $points, $orderIndex) {
        $stmt = $this->pdo->prepare("CALL sp_add_question(:quiz_id, :question_text, :question_type, :points, :order_index)");
        $stmt->execute([
            'quiz_id'        => $quizId,
            'question_text'  => $questionText,
            'question_type'  => $questionType,
            'points'         => $points,
            'order_index'    => $orderIndex
        ]);
        $row = $stmt->fetch();           // kunin ang result set (question_id)
        $stmt->closeCursor();            // isara ang cursor para sa susunod na query
        return $row['question_id'] ?? 0;
    }

    public function addOption($questionId, $optionText, $isCorrect, $orderIndex) {
        $stmt = $this->pdo->prepare("CALL sp_add_option(:question_id, :option_text, :is_correct, :order_index)");
        $stmt->execute([
            'question_id' => $questionId,
            'option_text' => $optionText,
            'is_correct'  => $isCorrect,
            'order_index' => $orderIndex
        ]);
        // Walang result set dito; hindi na kailangan ng closeCursor
    }

    public function getQuestionsWithOptions($quizId) {
        $stmt = $this->pdo->prepare("CALL sp_get_questions_with_options(:quiz_id)");
        $stmt->execute(['quiz_id' => $quizId]);
        $rows = $stmt->fetchAll();
        $questions = [];
        foreach ($rows as $row) {
            $qId = $row['id'];
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    'id'            => $row['id'],
                    'question_text' => $row['question_text'],
                    'question_type' => $row['question_type'],
                    'points'        => $row['points'],
                    'order_index'   => $row['order_index'],
                    'options'       => []
                ];
            }
            if ($row['option_id']) {
                $questions[$qId]['options'][] = [
                    'option_id'   => $row['option_id'],
                    'option_text' => $row['option_text'],
                    'is_correct'  => $row['is_correct'],
                    'order_index' => $row['order_index']
                ];
            }
        }
        return array_values($questions);
    }
}