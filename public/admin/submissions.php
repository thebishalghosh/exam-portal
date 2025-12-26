<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Submission.php';
require_once ROOT_PATH . '/app/models/Exam.php';
require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

// --- Pagination & Search Logic ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Submissions per page
$offset = ($page - 1) * $limit;

// Fetch data
$submissions = getAllSubmissions($conn, $filter_exam_id, $search, $limit, $offset);
$total_submissions = getSubmissionsCount($conn, $filter_exam_id, $search);
$total_pages = ceil($total_submissions / $limit);
$all_exams = getAllExams($conn);
?>

<h1 class="mt-4">Exam Submissions</h1>
<p>Review all completed and disqualified exam attempts.</p>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET">
            <div class="row align-items-end g-3">
                <div class="col-md-5">
                    <label for="exam_id_filter" class="form-label">Filter by Exam</label>
                    <select name="exam_id" id="exam_id_filter" class="form-select">
                        <option value="">All Exams</option>
                        <?php if ($all_exams && mysqli_num_rows($all_exams) > 0): ?>
                            <?php mysqli_data_seek($all_exams, 0); // Reset pointer ?>
                            <?php while($exam = mysqli_fetch_assoc($all_exams)): ?>
                                <option value="<?php echo $exam['exam_id']; ?>" <?php if ($filter_exam_id == $exam['exam_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($exam['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="search" class="form-label">Search by Candidate Email</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="e.g., user@example.com" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-header">
        Submissions (<?php echo $total_submissions; ?> found)
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
                            <td colspan="7" class="text-center">No submissions found for the selected filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&exam_id=<?php echo $filter_exam_id; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&exam_id=<?php echo $filter_exam_id; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&exam_id=<?php echo $filter_exam_id; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>
