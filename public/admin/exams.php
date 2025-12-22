<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';

require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

$exams = getAllExams($conn);
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

<!-- Exams List Table -->
<div class="card">
    <div class="card-header">
        Existing Exams
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

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
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

            var modalTitle = viewExamModal.querySelector('#modal-title');
            var modalDescription = viewExamModal.querySelector('#modal-description');
            var modalDuration = viewExamModal.querySelector('#modal-duration');
            var modalStart = viewExamModal.querySelector('#modal-start');
            var modalEnd = viewExamModal.querySelector('#modal-end');
            var modalStatus = viewExamModal.querySelector('#modal-status');

            modalTitle.textContent = title;
            modalDescription.textContent = description;
            modalDuration.textContent = duration;
            modalStart.textContent = start;
            modalEnd.textContent = end;
            modalStatus.textContent = status;
        });
    }
});
</script>
