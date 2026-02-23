<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/app/views/partials/header.php'; // Assuming a general header for candidates
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['candidate_name'] ?? $_SESSION['candidate_email']); ?>!</h4>
                </div>
                <div class="card-body">
                    <p class="lead">Please select an interview exam from the list below to begin your assessment.</p>

                    <?php if ($exams && mysqli_num_rows($exams) > 0): ?>
                        <div class="list-group">
                            <?php while($exam = mysqli_fetch_assoc($exams)): ?>
                                <div class="list-group-item list-group-item-action flex-column align-items-start mb-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                        <small class="text-muted"><?php echo $exam['duration']; ?> mins</small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($exam['description']); ?></p>
                                    <a href="<?php echo BASE_URL; ?>/exam/check/<?php echo $exam['exam_id']; ?>" class="btn btn-success btn-sm mt-2">Start Exam</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            No active interview exams are currently available. Please check back later or contact support.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once ROOT_PATH . '/app/views/partials/footer.php'; // Assuming a general footer for candidates
?>
