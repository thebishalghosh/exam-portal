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

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="mt-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/exams">Exams</a></li>
        <li class="breadcrumb-item active" aria-current="page">Manage Questions</li>
    </ol>
</nav>

<h1 class="mt-2">Manage Questions for "<?php echo htmlspecialchars($exam['title']); ?>"</h1>
<p>Add, view, and manage questions for this exam.</p>

<!-- Add Question Form -->
<div class="card mb-4">
    <div class="card-header">
        Add New Question
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>/admin/question/create" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <div class="mb-3">
                <label for="question_text" class="form-label">Question Text</label>
                <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="type" class="form-label">Question Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="mcq">Multiple Choice (MCQ)</option>
                        <option value="descriptive">Descriptive</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="marks" class="form-label">Marks</label>
                    <input type="number" class="form-control" id="marks" name="marks" value="1" required>
                </div>
            </div>
            <div id="mcq_options_container">
                <label class="form-label">MCQ Options</label>
                <div class="input-group mb-2"><span class="input-group-text">A</span><input type="text" class="form-control" name="options[A]" placeholder="Option A"></div>
                <div class="input-group mb-2"><span class="input-group-text">B</span><input type="text" class="form-control" name="options[B]" placeholder="Option B"></div>
                <div class="input-group mb-2"><span class="input-group-text">C</span><input type="text" class="form-control" name="options[C]" placeholder="Option C"></div>
                <div class="input-group mb-3"><span class="input-group-text">D</span><input type="text" class="form-control" name="options[D]" placeholder="Option D"></div>
                <div class="mb-3">
                    <label for="correct_answer" class="form-label">Correct Answer (for MCQ)</label>
                    <select class="form-select" id="correct_answer" name="correct_answer"><option value="">Select Correct Option</option><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select>
                </div>
            </div>
            <button type="submit" name="create_question" class="btn btn-primary" style="background-color: var(--primary-green); border-color: var(--primary-green);">Add Question</button>
        </form>
    </div>
</div>

<!-- Existing Questions List -->
<div class="card">
    <div class="card-header">
        Existing Questions
    </div>
    <div class="card-body">
        <?php if ($questions && mysqli_num_rows($questions) > 0): ?>
            <ul class="list-group list-group-flush">
                <?php while($question = mysqli_fetch_assoc($questions)): ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($question['question_text']); ?></h5>
                            <small><?php echo $question['marks']; ?> Mark(s)</small>
                        </div>
                        <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst($question['type']); ?></p>
                        <?php if ($question['type'] === 'mcq' && $question['options']): ?>
                            <?php $options = json_decode($question['options'], true); ?>
                            <div class="mt-2">
                                <?php foreach($options as $key => $value): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" disabled <?php if($key == $question['correct_answer']) echo 'checked'; ?>>
                                        <label class="form-check-label <?php if($key == $question['correct_answer']) echo 'fw-bold text-success'; ?>"><?php echo htmlspecialchars($value); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3"><a href="#" class="btn btn-sm btn-warning">Edit</a><a href="#" class="btn btn-sm btn-danger">Delete</a></div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-center">No questions added yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const mcqOptionsContainer = document.getElementById('mcq_options_container');
    function toggleMcqOptions() {
        mcqOptionsContainer.style.display = (typeSelect.value === 'mcq') ? 'block' : 'none';
    }
    toggleMcqOptions();
    typeSelect.addEventListener('change', toggleMcqOptions);
});
</script>
