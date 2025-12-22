<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';
require_once ROOT_PATH . '/app/models/Candidate.php';
require_once ROOT_PATH . '/app/models/ExamAssignment.php';

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($exam_id === 0) {
    header("Location: " . BASE_URL . "/admin/exams?error=invalid_id");
    exit();
}

// Fetch exam details (title only now)
$exam = getExamById($conn, $exam_id);

if (!$exam) {
    header("Location: " . BASE_URL . "/admin/exams?error=notfound");
    exit();
}

// Fetch currently assigned emails using the new table
$assigned_emails = getAssignedEmailsByExamId($conn, $exam_id);

$type = isset($_GET['type']) && $_GET['type'] === 'interview' ? 'interview' : 'internal';

if ($type === 'interview') {
    $all_candidates = getInterviewCandidatesFromAPI();
} else {
    $all_candidates = getAllCandidatesFromAPI();
}

require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/exams">Exams</a></li>
        <li class="breadcrumb-item active" aria-current="page">Assign Candidates</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mt-2">Assign Candidates to "<?php echo htmlspecialchars($exam['title']); ?>"</h1>
        <p class="text-muted">Manage who can access this exam.</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sourceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Source: <?php echo ucfirst($type); ?> Candidates
        </button>
        <ul class="dropdown-menu" aria-labelledby="sourceDropdown">
            <li><a class="dropdown-item <?php if ($type === 'internal') echo 'active'; ?>" href="?type=internal">Internal Candidates (HR)</a></li>
            <li><a class="dropdown-item <?php if ($type === 'interview') echo 'active'; ?>" href="?type=interview">Interview Candidates</a></li>
        </ul>
    </div>
</div>

<div class="row">
    <!-- Left Panel: Available Candidates -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-2">Available Candidates</h5>
                <div class="row g-2 align-items-center">
                    <div class="col-sm-5">
                        <input type="text" id="search-available" class="form-control form-control-sm" placeholder="Search...">
                    </div>
                    <div class="col-sm-5">
                        <select id="college-filter" class="form-select form-select-sm"><option value="">All Colleges</option></select>
                    </div>
                    <div class="col-sm-2 text-end">
                        <button class="btn btn-sm btn-outline-secondary w-100" id="add-all-btn">Add All</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0" style="height: 500px; overflow-y: auto;">
                <ul class="list-group list-group-flush" id="available-list">
                    <?php if (!empty($all_candidates)): ?>
                        <?php foreach ($all_candidates as $candidate): ?>
                            <?php
                                $email = $candidate['email'] ?? '';
                                $name = $candidate['full_name'] ?? $candidate['name'] ?? 'Unknown';
                                $college = $candidate['college'] ?? 'N/A';
                                // Use 'employee_id' or 'id' depending on the API response
                                $id = $candidate['employee_id'] ?? $candidate['id'] ?? 0;

                                if (in_array($email, $assigned_emails)) continue;
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center candidate-item"
                                data-email="<?php echo htmlspecialchars($email); ?>"
                                data-name="<?php echo htmlspecialchars($name); ?>"
                                data-college="<?php echo htmlspecialchars($college); ?>"
                                data-id="<?php echo htmlspecialchars($id); ?>"
                                data-source="<?php echo htmlspecialchars($type); ?>">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($name); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($email); ?> | <?php echo htmlspecialchars($college); ?></small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary add-btn">Add &rarr;</button>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">No candidates found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Panel: Assigned Candidates -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assigned Candidates</h5>
                <span class="badge bg-white text-primary" id="assigned-count"><?php echo count($assigned_emails); ?></span>
            </div>
            <div class="card-body p-0" style="height: 500px; overflow-y: auto;">
                <ul class="list-group list-group-flush" id="assigned-list">
                    <?php
                        // Filter the master list to find details of already assigned candidates
                        $assigned_candidates_details = array_filter($all_candidates, function($candidate) use ($assigned_emails) {
                            return in_array($candidate['email'] ?? '', $assigned_emails);
                        });

                        foreach ($assigned_candidates_details as $candidate):
                            $email = $candidate['email'] ?? '';
                            $name = $candidate['full_name'] ?? $candidate['name'] ?? $email;
                            $id = $candidate['employee_id'] ?? $candidate['id'] ?? 0;
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center candidate-item"
                            data-email="<?php echo htmlspecialchars($email); ?>"
                            data-name="<?php echo htmlspecialchars($name); ?>"
                            data-id="<?php echo htmlspecialchars($id); ?>"
                            data-source="<?php echo htmlspecialchars($type); ?>">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($name); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($email); ?></small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger remove-btn">&larr; Remove</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-footer bg-white">
                <button id="save-assignments-btn" class="btn btn-success w-100" style="background-color: var(--primary-green); border-color: var(--primary-green);">Save Assignments</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <svg class="bd-placeholder-img rounded me-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false"><rect width="100%" height="100%" fill="#007aff"></rect></svg>
            <strong class="me-auto">Notification</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <!-- Message will be inserted here -->
        </div>
    </div>
</div>


<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableList = document.getElementById('available-list');
    const assignedList = document.getElementById('assigned-list');
    const assignedCount = document.getElementById('assigned-count');
    const saveBtn = document.getElementById('save-assignments-btn');
    const searchInput = document.getElementById('search-available');
    const collegeFilter = document.getElementById('college-filter');
    const addAllBtn = document.getElementById('add-all-btn');
    const toastElement = document.getElementById('notificationToast');
    const toast = new bootstrap.Toast(toastElement, { delay: 5000, autohide: true });

    // --- Initialization ---
    function populateCollegeFilter() {
        const colleges = new Set();
        availableList.querySelectorAll('.candidate-item').forEach(item => {
            const college = item.dataset.college;
            if (college && college !== 'N/A') {
                colleges.add(college);
            }
        });

        colleges.forEach(college => {
            const option = document.createElement('option');
            option.value = college;
            option.textContent = college;
            collegeFilter.appendChild(option);
        });
    }
    populateCollegeFilter();

    // --- Event Listeners ---
    searchInput.addEventListener('keyup', applyFilters);
    collegeFilter.addEventListener('change', applyFilters);

    availableList.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-btn')) {
            moveItem(e.target.closest('.list-group-item'), assignedList);
        }
    });

    assignedList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-btn')) {
            moveItem(e.target.closest('.list-group-item'), availableList);
        }
    });

    addAllBtn.addEventListener('click', function() {
        const visibleItems = Array.from(availableList.querySelectorAll('.candidate-item')).filter(item => item.style.display !== 'none');
        visibleItems.forEach(item => moveItem(item, assignedList));
    });

    saveBtn.addEventListener('click', saveAssignments);

    // --- Core Functions ---
    function applyFilters() {
        const searchText = searchInput.value.toLowerCase();
        const selectedCollege = collegeFilter.value;

        availableList.querySelectorAll('.candidate-item').forEach(item => {
            const itemText = item.innerText.toLowerCase();
            const itemCollege = item.dataset.college;

            const matchesSearch = itemText.includes(searchText);
            const matchesCollege = !selectedCollege || itemCollege === selectedCollege;

            item.style.display = (matchesSearch && matchesCollege) ? '' : 'none';
        });
    }

    function moveItem(item, destinationList) {
        const isAdding = destinationList.id === 'assigned-list';
        const button = item.querySelector('button');

        if (isAdding) {
            button.className = 'btn btn-sm btn-outline-danger remove-btn';
            button.innerHTML = '&larr; Remove';
        } else {
            button.className = 'btn btn-sm btn-outline-primary add-btn';
            button.innerHTML = 'Add &rarr;';
        }

        destinationList.appendChild(item);
        updateCount();
        applyFilters();
    }

    function updateCount() {
        assignedCount.innerText = assignedList.children.length;
    }

    function showToast(message, isError = false) {
        const toastBody = toastElement.querySelector('.toast-body');
        const toastHeader = toastElement.querySelector('.toast-header');
        const toastIcon = toastElement.querySelector('svg rect');

        toastBody.textContent = message;
        if (isError) {
            toastHeader.classList.add('bg-danger', 'text-white');
            toastHeader.classList.remove('bg-light');
            toastIcon.setAttribute('fill', '#fff');
        } else {
            toastHeader.classList.remove('bg-danger', 'text-white');
            toastHeader.classList.add('bg-light');
            toastIcon.setAttribute('fill', '#007aff');
        }
        toast.show();
    }

    function saveAssignments() {
        const assignments = Array.from(assignedList.querySelectorAll('.candidate-item')).map(item => {
            return {
                candidate_id: item.dataset.id,
                candidate_email: item.dataset.email,
                candidate_source: item.dataset.source
            };
        });

        const originalText = saveBtn.innerText;
        saveBtn.innerText = 'Saving...';
        saveBtn.disabled = true;

        fetch('<?php echo BASE_URL; ?>/admin/exam/save-assignment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                exam_id: <?php echo $exam_id; ?>,
                assignments: assignments
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Assignments saved successfully!');
            } else {
                showToast('Error: ' + data.message, true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An unexpected error occurred.', true);
        })
        .finally(() => {
            saveBtn.innerText = originalText;
            saveBtn.disabled = false;
        });
    }
});
</script>
