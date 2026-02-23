<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Api.php'; // Updated to use the correct model
require_once ROOT_PATH . '/app/models/ExamAssignment.php'; // For open candidates

require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

// Determine which candidate type to show. Default to 'internal'.
$type = isset($_GET['type']) ? $_GET['type'] : 'internal';

// Fetch candidates based on the selected type
// We fetch ALL candidates and let the frontend DataTable handle pagination/search
if ($type === 'interview') {
    $candidates = fetchCandidatesFromInterview();
    $pageTitle = "Interview Candidates";
    $pageDescription = "List of candidates fetched from the Interview Portal.";
} elseif ($type === 'open') {
    $candidates = getOpenRegistrationCandidates($conn);
    $pageTitle = "Open Registration Candidates";
    $pageDescription = "List of candidates who self-registered for exams.";
} else {
    $candidates = fetchCandidatesFromHR();
    $pageTitle = "Internal Candidates";
    $pageDescription = "List of candidates fetched from the HR Portal.";
}
?>

<!-- Simple DataTables CSS -->
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
        <p><?php echo $pageDescription; ?></p>
    </div>
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="candidateTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Change View
        </button>
        <ul class="dropdown-menu" aria-labelledby="candidateTypeDropdown">
            <li><a class="dropdown-item <?php if ($type === 'internal') echo 'active'; ?>" href="<?php echo BASE_URL; ?>/admin/candidates?type=internal">Internal Candidates</a></li>
            <li><a class="dropdown-item <?php if ($type === 'interview') echo 'active'; ?>" href="<?php echo BASE_URL; ?>/admin/candidates?type=interview">Interview Candidates</a></li>
            <li><a class="dropdown-item <?php if ($type === 'open') echo 'active'; ?>" href="<?php echo BASE_URL; ?>/admin/candidates?type=open">Open Registration Candidates</a></li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Candidate List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatablesSimple" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Full Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Mobile Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($candidates)): ?>
                        <?php foreach($candidates as $candidate): ?>
                            <tr>
                                <!-- Handle different field names from different sources -->
                                <th scope="row"><?php echo htmlspecialchars($candidate['employee_id'] ?? $candidate['id'] ?? 'N/A'); ?></th>
                                <td><?php echo htmlspecialchars($candidate['full_name'] ?? $candidate['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['mobile_number'] ?? $candidate['phone'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple);
        }
    });
</script>

<?php
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>
