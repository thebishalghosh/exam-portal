<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/app/models/Candidate.php';
require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

// Determine which candidate type to show. Default to 'internal'.
$type = isset($_GET['type']) && $_GET['type'] === 'interview' ? 'interview' : 'internal';

// Fetch candidates based on the selected type
if ($type === 'interview') {
    $candidates = getInterviewCandidatesFromAPI();
    $pageTitle = "Interview Candidates";
    $pageDescription = "This is the list of candidates fetched live from the Interview Portal.";
} else {
    $candidates = getAllCandidatesFromAPI(); // Internal candidates from HR Portal
    $pageTitle = "Internal Candidates";
    $pageDescription = "This is the list of candidates fetched live from the HR Portal.";
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
        </ul>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($candidates)): ?>
                        <?php foreach($candidates as $candidate): ?>
                            <tr>
                                <!-- Assuming both APIs return 'employee_id' or similar. Adjust if needed. -->
                                <th scope="row"><?php echo htmlspecialchars($candidate['employee_id'] ?? $candidate['id'] ?? 'N/A'); ?></th>
                                <td><?php echo htmlspecialchars($candidate['full_name'] ?? $candidate['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['mobile_number'] ?? $candidate['phone'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <p>Could not fetch candidate data for this source.</p>
                                <small class="text-muted">Please ensure the correct portal is running and the API key is set in the .env file.</small>
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
