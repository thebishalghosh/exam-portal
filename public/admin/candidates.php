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
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$candidates = [];
$pageTitle = "";
$pageDescription = "";

// Only fetch candidates if a search query is provided
if (!empty($search_query)) {
    if ($type === 'interview') {
        $candidates = fetchCandidatesFromInterview($search_query);
        $pageTitle = "Interview Candidates";
        $pageDescription = "Filtered list of candidates from the Interview Portal.";
    } elseif ($type === 'open') {
        $candidates = getOpenRegistrationCandidates($conn, $search_query);
        $pageTitle = "Open Registration Candidates";
        $pageDescription = "Filtered list of self-registered candidates.";
    } else { // Default to 'internal' (HR Portal)
        $candidates = fetchCandidatesFromHR($search_query);
        $pageTitle = "Internal Candidates";
        $pageDescription = "Filtered list of candidates from the HR Portal.";
    }
} else {
    $pageTitle = "Candidate Management";
    $pageDescription = "Please select a source and enter a search query to find candidates.";
}
?>

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

<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <div class="col-md-8">
                <label for="search" class="form-label">Search by Name or Email</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="e.g., John Doe or john.doe@example.com" value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Search Candidates</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Candidate List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Full Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Mobile Number</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($candidates)): ?>
                        <?php foreach($candidates as $candidate): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($candidate['employee_id'] ?? $candidate['id'] ?? 'N/A'); ?></th>
                                <td><?php echo htmlspecialchars($candidate['full_name'] ?? $candidate['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['mobile_number'] ?? $candidate['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/exams" class="btn btn-sm btn-outline-primary">Assign Exam</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <?php if (!empty($search_query)): ?>
                                    <p>No candidates found matching "<?php echo htmlspecialchars($search_query); ?>" for this source.</p>
                                <?php else: ?>
                                    <p>Please use the search bar above to find candidates.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>
