<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Submission.php';
require_once ROOT_PATH . '/app/models/Snapshot.php';
require_once ROOT_PATH . '/app/models/Log.php'; // Include the new Log model
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

// Fetch snapshots and logs
$snapshots = getSnapshotsForSubmission($submission['exam_id'], $submission['user_id']);
$logs = getLogsForSubmission($conn, $submission['exam_id'], $submission['user_id']);

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
            <?php $q_index = 1; ?>
            <?php foreach ($submission['questions'] as $question): ?>
                <?php
                    $q_id = $question['question_id'];
                    $candidate_answer = $submitted_answers[$q_id] ?? null;
                    $is_correct = false;
                    $awarded_marks = 0;

                    if ($marks_breakdown && isset($marks_breakdown[$q_id])) {
                        $awarded_marks = (float)$marks_breakdown[$q_id];
                        if ($question['type'] === 'mcq' && $candidate_answer === $question['correct_answer']) {
                            $is_correct = true;
                        }
                    } else {
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
                        <strong>Question <?php echo $q_index; ?></strong>
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
                        <?php else: ?>
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
                <?php $q_index++; ?>
            <?php endforeach; ?>
        </div>
        <div class="col-lg-4">
            <!-- Score Card -->
            <div class="card mb-4 sticky-top" style="top: 20px; z-index: 100;">
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

            <!-- Snapshots Gallery -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Proctoring Snapshots</h5>
                </div>
                <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;">
                    <?php if (!empty($snapshots)): ?>
                        <div class="row g-2">
                            <?php foreach ($snapshots as $snapshot): ?>
                                <div class="col-6">
                                    <a href="<?php echo BASE_URL; ?>/admin/image/view?file=<?php echo $snapshot; ?>" target="_blank">
                                        <img src="<?php echo BASE_URL; ?>/admin/image/view?file=<?php echo $snapshot; ?>" class="img-fluid rounded border" alt="Snapshot">
                                    </a>
                                    <small class="d-block text-center text-muted mt-1" style="font-size: 10px;">
                                        <?php
                                            $parts = explode('_', pathinfo($snapshot, PATHINFO_FILENAME));
                                            echo date('H:i:s', end($parts));
                                        ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted my-3">No snapshots recorded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Activity Log</h5>
                </div>
                <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;">
                    <?php if (!empty($logs)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($logs as $log): ?>
                                <?php
                                    $is_suspicious = str_contains($log['event_type'], 'hidden') || str_contains($log['event_type'], 'exit') || str_contains($log['event_type'], 'Blocked');
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $is_suspicious ? 'list-group-item-danger' : ''; ?>">
                                    <span style="font-size: 12px;"><?php echo htmlspecialchars($log['event_type']); ?></span>
                                    <small class="text-muted" style="font-size: 11px;"><?php echo date('H:i:s', strtotime($log['timestamp'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-center text-muted my-3">No suspicious activity recorded.</p>
                    <?php endif; ?>
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
        totalScoreDisplay.textContent = `${currentTotal.toFixed(2)} / ${totalPossibleMarks}`;
    }

    form.addEventListener('input', function(e) {
        if (e.target.classList.contains('marks-input')) {
            updateTotalScore();
        }
    });
});
</script>
