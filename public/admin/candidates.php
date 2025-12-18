<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/app/models/Candidate.php';
require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';

// Fetch candidates from the HR Portal API
$candidates = getAllCandidatesFromAPI();
?>

<h1 class="mt-4">Candidates</h1>
<p>This is the list of candidates fetched live from the HR Portal.</p>

<div class="card">
    <div class="card-header">
        Candidate List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">Employee ID</th>
                        <th scope="col">Full Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Mobile Number</th>
                        <th scope="col">College</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($candidates)): ?>
                        <?php foreach($candidates as $candidate): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($candidate['employee_id']); ?></th>
                                <td><?php echo htmlspecialchars($candidate['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['email']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['mobile_number']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['college']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <p>Could not fetch candidate data.</p>
                                <small class="text-muted">Please ensure the HR Portal is running and the API key is correct.</small>
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
