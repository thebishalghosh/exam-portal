<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit();
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';
require_once ROOT_PATH . '/app/models/Question.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('APP_URL'));
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($exam_id === 0) {
    header("Location: " . BASE_URL . "/admin/exams?error=invalid_id");
    exit();
}

$exam_title = getExamTitleById($conn, $exam_id);

if (!$exam_title) {
    header("Location: " . BASE_URL . "/admin/exams?error=notfound");
    exit();
}

$questions = getQuestionsByExamId($conn, $exam_id);

require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<div class="questions-wrapper">
    <div class="floating-menu">
        <button class="btn-floating" id="add-question-btn" title="Add New Question">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"/></svg>
        </button>
    </div>

    <div class="form-title-card">
        <h1><?php echo htmlspecialchars($exam_title); ?></h1>
        <p class="lead">Click on a question to edit, or use the '+' button to add a new one.</p>
    </div>

    <div id="questions-container" data-exam-id="<?php echo $exam_id; ?>">
        <?php if ($questions && mysqli_num_rows($questions) > 0): ?>
            <?php $qIndex = 1; ?>
            <?php while($question = mysqli_fetch_assoc($questions)): ?>
                <div class="question-card"
                     data-question-id="<?php echo $question['question_id']; ?>"
                     data-type="<?php echo $question['type']; ?>"
                     data-text="<?php echo htmlspecialchars($question['question_text']); ?>"
                     data-marks="<?php echo $question['marks']; ?>"
                     data-options='<?php echo $question['options'] ? htmlspecialchars($question['options'], ENT_QUOTES, 'UTF-8') : ""; ?>'
                     data-correct="<?php echo $question['correct_answer']; ?>">

                    <div class="view-mode">
                        <div class="card-actions">
                            <button class="btn-icon edit-question-btn" title="Edit Question"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-fill" viewBox="0 0 16 16"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/></svg></button>
                            <button class="btn-icon delete-question-btn" title="Delete Question"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5zM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06zm3.5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06zm3.5-.002l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06z"/></svg></button>
                        </div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="question-meta">
                                    <span class="question-index-badge"><?php echo $qIndex; ?></span>
                                    <span><?php echo strtoupper($question['type'] === 'mcq' ? 'Multiple choice' : 'Descriptive'); ?></span>
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
                                        <label class="form-check-label <?php if($key == $question['correct_answer']) echo 'fw-bold text-success'; ?>"><?php echo htmlspecialchars($value); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <textarea class="form-control form-control-sm question-descriptive-preview" disabled placeholder="Answer text"></textarea>
                        <?php endif; ?>
                    </div>
                    <div class="edit-mode" style="display: none;"></div>
                </div>
                <?php $qIndex++; ?>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<?php mysqli_close($conn); require_once ROOT_PATH . '/app/views/partials/admin_footer.php'; ?>

<script>
(function () {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const examId = questionsContainer?.getAttribute('data-exam-id');

    if (!questionsContainer || !addQuestionBtn || !examId) return;

    questionsContainer.addEventListener('click', function (e) {
        const card = e.target.closest('.question-card');
        if (!card) return;

        if (e.target.closest('.delete-question-btn')) {
            const questionId = card.getAttribute('data-question-id');
            Swal.fire({
                title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) deleteQuestion(questionId, card);
            });
            return;
        }

        if (e.target.closest('.edit-question-btn')) {
            enableEditMode(card);
            return;
        }

        if (card.querySelector('.edit-mode')?.contains(e.target)) return;
        document.querySelectorAll('.question-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
    });

    function createNewQuestionCard() {
        const existingNew = questionsContainer.querySelector('.question-card.new-question');
        if (existingNew) {
            existingNew.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'question-card active new-question';

        wrapper.innerHTML = `
            <div class="edit-mode">
                <form class="new-question-form">
                    <input type="hidden" name="exam_id" value="${examId}">
                    <input type="hidden" name="create_question" value="1">
                    <input type="text" name="question_text" class="question-input" placeholder="Untitled question" required>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Question type</label>
                            <select name="type" class="form-select question-type-select">
                                <option value="mcq" selected>Multiple choice</option>
                                <option value="descriptive">Short / long answer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Marks</label>
                            <input type="number" name="marks" class="form-control" value="1" min="1" required>
                        </div>
                    </div>
                    <div class="mcq-options-block">
                        <label class="form-label text-muted small mb-1 d-block">Options</label>
                        ${[1, 2, 3, 4].map((n, idx) => `
                            <div class="d-flex align-items-center mb-2 option-row">
                                <div class="form-check me-2">
                                    <input class="form-check-input" type="radio" name="correct_answer" value="${['A','B','C','D'][idx]}" ${idx === 0 ? 'checked' : ''}>
                                </div>
                                <input type="text" name="options[${['A','B','C','D'][idx]}]" class="form-control" placeholder="Option ${n}" ${idx < 2 ? 'required' : ''}>
                            </div>
                        `).join('')}
                        <p class="text-muted small mt-1 mb-0">Select the circle next to the correct answer.</p>
                    </div>
                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <button type="button" class="btn btn-light btn-sm cancel-new-question">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm" style="background-color: var(--primary-green); border-color: var(--primary-green);">Save question</button>
                    </div>
                </form>
            </div>
            <div class="view-mode" style="display:none;"></div>
        `;

        questionsContainer.insertBefore(wrapper, questionsContainer.firstChild);
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const typeSelect = wrapper.querySelector('.question-type-select');
        const mcqBlock = wrapper.querySelector('.mcq-options-block');
        const cancelBtn = wrapper.querySelector('.cancel-new-question');
        const form = wrapper.querySelector('.new-question-form');

        if (typeSelect && mcqBlock) {
            typeSelect.addEventListener('change', function () {
                const isMCQ = this.value === 'mcq';
                mcqBlock.style.display = isMCQ ? '' : 'none';
                mcqBlock.querySelectorAll('input').forEach(input => {
                    input.disabled = !isMCQ;
                    if(isMCQ) input.required = input.placeholder.includes('Option 1') || input.placeholder.includes('Option 2');
                    else input.required = false;
                });
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => wrapper.remove());
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerText = 'Saving...';

                fetch('<?php echo BASE_URL; ?>/admin/question/create', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Save question';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Save question';
                });
            });
        }
    }
    addQuestionBtn.addEventListener('click', createNewQuestionCard);

    function enableEditMode(card) {
        // ... (edit mode logic from previous step) ...
    }

    async function deleteQuestion(questionId, cardElement) {
        // ... (delete logic from previous step) ...
    }
})();
</script>
<style>.card-actions { position: absolute; top: 10px; right: 10px; display: flex; gap: 5px; opacity: 0; transition: opacity 0.2s; } .question-card:hover .card-actions { opacity: 1; } .btn-icon { background: none; border: none; cursor: pointer; color: #6c757d; padding: 5px; } .btn-icon:hover { color: #212529; }</style>
