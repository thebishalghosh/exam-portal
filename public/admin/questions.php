<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Question.php';

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($exam_id === 0) {
    header("Location: " . BASE_URL . "/admin/exams?error=invalid_id");
    exit();
}

$exam = getExamById($conn, $exam_id);

if (!$exam) {
    header("Location: " . BASE_URL . "/admin/exams?error=notfound");
    exit();
}

$questions = getQuestionsByExamId($conn, $exam_id);

require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';
?>

<!-- Add Google Fonts for Roboto -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<div class="questions-wrapper">
    <!-- Floating Action Menu -->
    <div class="floating-menu">
        <button class="btn-floating" id="add-question-btn" title="Add New Question">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"/>
            </svg>
        </button>
    </div>

    <!-- Exam Title Card -->
    <div class="form-title-card">
        <h1><?php echo htmlspecialchars($exam['title']); ?></h1>
        <p class="lead">Click on a question to edit, or use the '+' button to add a new one.</p>
    </div>

    <!-- Questions Container -->
    <div id="questions-container" data-exam-id="<?php echo $exam_id; ?>">
        <?php if ($questions && mysqli_num_rows($questions) > 0): ?>
            <?php $qIndex = 1; ?>
            <?php while($question = mysqli_fetch_assoc($questions)): ?>
                <div class="question-card" data-question-id="<?php echo $question['question_id']; ?>">
                    <div class="view-mode">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="question-meta">
                                    <span class="question-index-badge"><?php echo $qIndex; ?></span>
                                    <span>
                                        <?php echo strtoupper($question['type'] === 'mcq' ? 'Multiple choice' : 'Descriptive'); ?>
                                    </span>
                                </div>
                                <p class="question-text-display mb-1"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            </div>
                            <span class="badge rounded-pill marks-badge"><?php echo $question['marks']; ?> Mark(s)</span>
                        </div>

                        <?php if ($question['type'] === 'mcq' && $question['options']): ?>
                            <?php $options = json_decode($question['options'], true); ?>
                            <div class="question-options">
                                <?php foreach($options as $key => $value): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" disabled <?php if($key == $question['correct_answer']) echo 'checked'; ?>>
                                        <label class="form-check-label <?php if($key == $question['correct_answer']) echo 'fw-bold text-success'; ?>">
                                            <?php echo htmlspecialchars($value); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <textarea class="form-control form-control-sm question-descriptive-preview" disabled placeholder="Answer text"></textarea>
                        <?php endif; ?>
                    </div>
                    <div class="edit-mode" style="display: none;">
                        <!-- The edit form will be dynamically inserted here by JavaScript -->
                    </div>
                </div>
                <?php $qIndex++; ?>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script>
// --- Google Forms–like interactions for managing questions ---
(function () {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const examId = questionsContainer?.getAttribute('data-exam-id');

    if (!questionsContainer || !addQuestionBtn || !examId) return;

    // Highlight a card when clicked (similar to active state in Google Forms)
    questionsContainer.addEventListener('click', function (e) {
        const card = e.target.closest('.question-card');
        if (!card) return;

        document
            .querySelectorAll('.question-card')
            .forEach(c => c.classList.remove('active'));
        card.classList.add('active');
    });

    // Create the HTML for a new editable question card
    function createNewQuestionCard() {
        // If there is already a "new" card, focus that instead of creating another
        const existingNew = questionsContainer.querySelector('.question-card.new-question');
        if (existingNew) {
            existingNew.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'question-card active new-question';

        // Build the edit form (posts to the existing QuestionController)
        wrapper.innerHTML = `
            <div class="edit-mode">
                <form action="<?php echo BASE_URL; ?>/admin/question/create" method="POST">
                    <input type="hidden" name="exam_id" value="${examId}">
                    <input type="hidden" name="create_question" value="1">

                    <input
                        type="text"
                        name="question_text"
                        class="question-input"
                        placeholder="Untitled question"
                        required
                    >

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Question type</label>
                            <select name="type" class="form-select" id="question-type-select">
                                <option value="mcq" selected>Multiple choice</option>
                                <option value="descriptive">Short / long answer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Marks</label>
                            <input type="number" name="marks" class="form-control" value="1" min="1" required>
                        </div>
                    </div>

                    <div id="mcq-options-block">
                        <label class="form-label text-muted small mb-1 d-block">Options</label>

                        ${[1, 2, 3, 4].map((n, idx) => `
                            <div class="d-flex align-items-center mb-2 option-row">
                                <div class="form-check me-2">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="correct_answer"
                                        value="${idx}"
                                        ${idx === 0 ? 'checked' : ''}
                                    >
                                </div>
                                <input
                                    type="text"
                                    name="options[${idx}]"
                                    class="form-control"
                                    placeholder="Option ${n}"
                                    ${idx < 2 ? 'required' : ''}
                                >
                            </div>
                        `).join('')}

                        <p class="text-muted small mt-1 mb-0">
                            Select the circle next to the correct answer. At least 2 options are required.
                        </p>
                    </div>

                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <button type="button" class="btn btn-light btn-sm" id="cancel-new-question">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-sm" style="background-color: var(--primary-green); border-color: var(--primary-green);">
                            Save question
                        </button>
                    </div>
                </form>
            </div>
        `;

        questionsContainer.insertBefore(wrapper, questionsContainer.firstChild);
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Wire up type toggle and cancel within this new card
        const typeSelect = wrapper.querySelector('#question-type-select');
        const mcqBlock = wrapper.querySelector('#mcq-options-block');
        const cancelBtn = wrapper.querySelector('#cancel-new-question');

        if (typeSelect && mcqBlock) {
            typeSelect.addEventListener('change', function () {
                if (this.value === 'mcq') {
                    mcqBlock.style.display = '';
                } else {
                    mcqBlock.style.display = 'none';
                }
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                wrapper.remove();
            });
        }
    }

    addQuestionBtn.addEventListener('click', createNewQuestionCard);
})();
</script>
