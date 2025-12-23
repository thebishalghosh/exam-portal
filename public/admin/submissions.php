<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Submission.php';
require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

// Fetch all submissions
$submissions = getAllSubmissions($conn);
?>

<h1 class="mt-4">Exam Submissions</h1>
<p>Review all completed and disqualified exam attempts.</p>

<div class="card">
    <div class="card-header">
        All Submissions
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">Exam Title</th>
                        <th scope="col">Candidate Email</th>
                        <th scope="col">Date Submitted</th>
                        <th scope="col">Submission Status</th>
                        <th scope="col">Score</th>
                        <th scope="col">Grading Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($submissions && mysqli_num_rows($submissions) > 0): ?>
                        <?php while($submission = mysqli_fetch_assoc($submissions)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['exam_title']); ?></td>
                                <td><?php echo htmlspecialchars($submission['candidate_email']); ?></td>
                                <td><?php echo date('F j, Y, g:i a', strtotime($submission['end_time'])); ?></td>
                                <td>
                                    <?php
                                        $status = $submission['submission_status'];
                                        $badge_class = 'bg-secondary';
                                        if ($status === 'Completed') {
                                            $badge_class = 'bg-success';
                                        } elseif ($status === 'Disqualified') {
                                            $badge_class = 'bg-danger';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $submission['score'] !== null ? htmlspecialchars(number_format($submission['score'], 2)) : 'N/A'; ?></strong>
                                </td>
                                <td>
                                    <?php if ($submission['score'] !== null): ?>
                                        <span class="badge bg-info">Graded</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/submission/view/<?php echo $submission['submission_id']; ?>" class="btn btn-sm btn-primary">
                                        <?php echo $submission['score'] !== null ? 'View' : 'Grade'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No submissions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>
