<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';

require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

// --- Pagination & Search Logic ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Exams per page
$offset = ($page - 1) * $limit;

// Fetch data
$exams = getAllExams($conn, $search, $limit, $offset);
$total_exams = getExamsCount($conn, $search);
$total_pages = ceil($total_exams / $limit);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mt-4">Manage Exams</h1>
        <p>Create, view, and manage exams for students.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal" style="background-color: var(--primary-green); border-color: var(--primary-green);">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg me-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/></svg>
        Add New Exam
    </button>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="position-relative">
            <div class="input-group">
                <input type="text" name="search" id="exam-search" class="form-control" placeholder="Search exams..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/exams" class="btn btn-outline-danger">Clear</a>
                <?php endif; ?>
            </div>
            <div id="search-suggestions" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></div>
        </form>
    </div>
</div>

<!-- Exams List Table -->
<div class="card">
    <div class="card-header">
        Existing Exams (<?php echo $total_exams; ?> found)
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($exams && mysqli_num_rows($exams) > 0): ?>
                        <?php while($exam = mysqli_fetch_assoc($exams)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                <td><?php echo $exam['duration']; ?> mins</td>
                                <td>
                                    <?php if ($exam['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info view-exam-btn"
                                            data-bs-toggle="modal" data-bs-target="#viewExamModal"
                                            data-title="<?php echo htmlspecialchars($exam['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($exam['description']); ?>"
                                            data-duration="<?php echo $exam['duration']; ?>"
                                            data-start="<?php echo date('F j, Y, g:i a', strtotime($exam['start_time'])); ?>"
                                            data-end="<?php echo date('F j, Y, g:i a', strtotime($exam['end_time'])); ?>"
                                            data-status="<?php echo ucfirst($exam['status']); ?>">
                                        View
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>/admin/exam/assign/<?php echo $exam['exam_id']; ?>" class="btn btn-sm btn-secondary">Assign</a>
                                    <a href="<?php echo BASE_URL; ?>/admin/exam/questions/<?php echo $exam['exam_id']; ?>" class="btn btn-sm btn-primary" style="background-color: var(--primary-green); border-color: var(--primary-green);">Manage Questions</a>
                                    <button type="button" class="btn btn-sm btn-danger delete-exam-btn"
                                            data-bs-toggle="modal" data-bs-target="#deleteExamModal"
                                            data-exam-id="<?php echo $exam['exam_id']; ?>"
                                            data-exam-title="<?php echo htmlspecialchars($exam['title']); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No exams found.</td>
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Add Exam Modal -->
<div class="modal fade" id="addExamModal" tabindex="-1" aria-labelledby="addExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addExamModalLabel">Create New Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addExamForm" action="<?php echo BASE_URL; ?>/admin/exam/create" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Duration (in minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="datetime-local" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="addExamForm" name="create_exam" class="btn btn-primary" style="background-color: var(--primary-green); border-color: var(--primary-green);">Create Exam</button>
            </div>
        </div>
    </div>
</div>

<!-- View Exam Modal -->
<div class="modal fade" id="viewExamModal" tabindex="-1" aria-labelledby="viewExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewExamModalLabel">Exam Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3 id="modal-title"></h3>
                <p id="modal-description"></p>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Duration:</strong> <span id="modal-duration"></span> minutes</p>
                        <p><strong>Status:</strong> <span id="modal-status"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Start Time:</strong> <span id="modal-start"></span></p>
                        <p><strong>End Time:</strong> <span id="modal-end"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Exam Modal -->
<div class="modal fade" id="deleteExamModal" tabindex="-1" aria-labelledby="deleteExamModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteExamModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the exam "<strong id="delete-exam-title"></strong>"?</p>
                <p class="text-danger">This action cannot be undone. All related questions, assignments, and submissions will be permanently deleted.</p>
                <form id="deleteExamForm" action="<?php echo BASE_URL; ?>/admin/exam/delete" method="POST">
                    <input type="hidden" name="exam_id" id="delete-exam-id">
                    <input type="hidden" name="delete_exam" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteExamForm" class="btn btn-danger">Delete Exam</button>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Modal Logic
    var viewExamModal = document.getElementById('viewExamModal');
    if(viewExamModal) {
        viewExamModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var title = button.getAttribute('data-title');
            var description = button.getAttribute('data-description');
            var duration = button.getAttribute('data-duration');
            var start = button.getAttribute('data-start');
            var end = button.getAttribute('data-end');
            var status = button.getAttribute('data-status');

            viewExamModal.querySelector('#modal-title').textContent = title;
            viewExamModal.querySelector('#modal-description').textContent = description;
            viewExamModal.querySelector('#modal-duration').textContent = duration;
            viewExamModal.querySelector('#modal-start').textContent = start;
            viewExamModal.querySelector('#modal-end').textContent = end;
            viewExamModal.querySelector('#modal-status').textContent = status;
        });
    }

    var deleteExamModal = document.getElementById('deleteExamModal');
    if(deleteExamModal) {
        deleteExamModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var examId = button.getAttribute('data-exam-id');
            var examTitle = button.getAttribute('data-exam-title');

            deleteExamModal.querySelector('#delete-exam-id').value = examId;
            deleteExamModal.querySelector('#delete-exam-title').textContent = examTitle;
        });
    }

    // AJAX Search Logic
    const searchInput = document.getElementById('exam-search');
    const suggestionsBox = document.getElementById('search-suggestions');

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        fetch('<?php echo BASE_URL; ?>/api/search-exams?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.href = '?search=' + encodeURIComponent(item.title);
                        a.className = 'list-group-item list-group-item-action';
                        a.textContent = item.title;
                        suggestionsBox.appendChild(a);
                    });
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            })
            .catch(error => console.error('Error fetching suggestions:', error));
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
});
</script>
