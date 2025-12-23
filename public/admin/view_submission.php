<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Submission.php';
require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;

if ($submission_id === 0) {
    header("Location: " . BASE_URL . "/admin/submissions?error=invalid_id");
    exit();
}

$submission = getSubmissionDetails($conn, $submission_id);

if (!$submission) {
    header("Location: " . BASE_URL . "/admin/submissions?error=notfound");
    exit();
}

$submitted_answers = json_decode($submission['submitted_answers'], true);
$marks_breakdown = !empty($submission['marks_breakdown']) ? json_decode($submission['marks_breakdown'], true) : null;

$total_marks = 0;
$total_possible_marks = 0;
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/submissions">Submissions</a></li>
        <li class="breadcrumb-item active" aria-current="page">View Submission</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mt-2">Review Submission</h1>
        <p class="text-muted">Exam: <strong><?php echo htmlspecialchars($submission['exam_title']); ?></strong> | Candidate: <strong><?php echo htmlspecialchars($submission['candidate_email']); ?></strong></p>
    </div>
</div>

<form id="grading-form" action="<?php echo BASE_URL; ?>/admin/submission/save-grade" method="POST">
    <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
    <div class="row">
        <div class="col-lg-8">
            <?php foreach ($submission['questions'] as $question): ?>
                <?php
                    $q_id = $question['question_id'];
                    $candidate_answer = $submitted_answers[$q_id] ?? null;
                    $is_correct = false;
                    $awarded_marks = 0;

                    // Logic: Use saved marks if available, otherwise auto-calculate
                    if ($marks_breakdown && isset($marks_breakdown[$q_id])) {
                        $awarded_marks = (float)$marks_breakdown[$q_id];
                        // Check correctness just for display purposes
                        if ($question['type'] === 'mcq' && $candidate_answer === $question['correct_answer']) {
                            $is_correct = true;
                        }
                    } else {
                        // Auto-calculate for first time
                        if ($question['type'] === 'mcq') {
                            if ($candidate_answer === $question['correct_answer']) {
                                $is_correct = true;
                                $awarded_marks = (float)$question['marks'];
                            }
                        }
                    }

                    $total_possible_marks += (float)$question['marks'];
                    $total_marks += $awarded_marks;
                ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <strong>Question <?php echo $question['question_id']; ?></strong>
                        <span class="badge bg-secondary"><?php echo $question['marks']; ?> Mark(s)</span>
                    </div>
                    <div class="card-body">
                        <p class="fw-bold"><?php echo htmlspecialchars($question['question_text']); ?></p>
                        <hr>
                        <?php if ($question['type'] === 'mcq'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Correct Answer:</strong></p>
                                    <?php $options = json_decode($question['options'], true); ?>
                                    <p class="text-success fw-bold"><?php echo htmlspecialchars($options[$question['correct_answer']] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Candidate's Answer:</strong></p>
                                    <?php if ($candidate_answer === $question['correct_answer']): ?>
                                        <p class="text-success fw-bold"><?php echo htmlspecialchars($options[$candidate_answer] ?? 'Not Answered'); ?></p>
                                    <?php else: ?>
                                        <p class="text-danger fw-bold"><?php echo htmlspecialchars($options[$candidate_answer] ?? 'Not Answered'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: // Descriptive ?>
                            <p class="mb-1"><strong>Candidate's Answer:</strong></p>
                            <p class="p-2 bg-light border rounded"><?php echo nl2br(htmlspecialchars($candidate_answer ?? 'Not Answered')); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="row align-items-center">
                            <label class="col-sm-3 col-form-label">Awarded Marks:</label>
                            <div class="col-sm-4">
                                <input type="number" step="0.5" name="marks[<?php echo $q_id; ?>]" class="form-control marks-input" value="<?php echo $awarded_marks; ?>" min="0" max="<?php echo $question['marks']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">Final Score</h5>
                </div>
                <div class="card-body text-center">
                    <h1 id="total-score-display" class="display-4"><?php echo $total_marks; ?> / <?php echo $total_possible_marks; ?></h1>
                </div>
                <div class="card-footer">
                    <button type="submit" name="save_grade" class="btn btn-success w-100">Save & Finalize Score</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('grading-form');
    const totalScoreDisplay = document.getElementById('total-score-display');
    const totalPossibleMarks = <?php echo $total_possible_marks; ?>;

    function updateTotalScore() {
        let currentTotal = 0;
        form.querySelectorAll('.marks-input').forEach(input => {
            const value = parseFloat(input.value);
            if (!isNaN(value)) {
                currentTotal += value;
            }
        });
        totalScoreDisplay.textContent = `${currentTotal} / ${totalPossibleMarks}`;
    }

    form.addEventListener('input', function(e) {
        if (e.target.classList.contains('marks-input')) {
            updateTotalScore();
        }
    });
});
</script>
