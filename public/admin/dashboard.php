<?php
if (!defined('ROOT_PATH')) {
    die("Direct access not allowed.");
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';
require_once ROOT_PATH . '/app/models/Candidate.php';
require_once ROOT_PATH . '/app/models/Submission.php';

// --- Fetch Data for Dashboard ---

// Summary Cards
$total_exams = getTotalExamsCount($conn);
$total_submissions = getTotalSubmissionsCount($conn);
// For candidates, we fetch both and add them up. This could be slow with huge APIs.
$total_internal_candidates = count(getAllCandidatesFromAPI());
$total_interview_candidates = count(getInterviewCandidatesFromAPI());
$total_candidates = $total_internal_candidates + $total_interview_candidates;

// Chart Data
$recent_submissions_data = getRecentSubmissionsCount($conn);
$exam_status_data = getExamStatusCounts($conn);


require_once ROOT_PATH . '/app/views/partials/admin_header.php';
require_once ROOT_PATH . '/app/views/partials/admin_sidebar.php';
?>

<h1 class="mt-4">Dashboard</h1>
<p class="lead">Overview of the examination system.</p>

<!-- Summary Cards -->
<div class="row">
    <!-- Total Exams Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Exams</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_exams; ?></div>
                    </div>
                    <div class="col-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-collection text-gray-300" viewBox="0 0 16 16"><path d="M0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zM2 3a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 0-1h-11A.5.5 0 0 0 2 3zm2-2a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 0-1h-7A.5.5 0 0 0 4 1z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Candidates Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Candidates</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_candidates; ?></div>
                    </div>
                    <div class="col-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-people text-gray-300" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submissions Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Submissions</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_submissions; ?></div>
                    </div>
                    <div class="col-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-file-earmark-check text-gray-300" viewBox="0 0 16 16"><path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l3-3z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Exams Card (Example of another dynamic metric) -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Exams</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo array_sum(array_column(array_filter($exam_status_data, fn($d) => $d[0] === 'Active'), 1)); ?></div>
                    </div>
                    <div class="col-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-broadcast text-gray-300" viewBox="0 0 16 16"><path d="M3.05 3.05a7 7 0 0 0 0 9.9.5.5 0 0 1-.707.707 8 8 0 0 1 0-11.314.5.5 0 0 1 .707.707zm2.122 2.122a4 4 0 0 0 0 5.656.5.5 0 1 1-.708.708 5 5 0 0 1 0-7.072.5.5 0 0 1 .708.708zm5.656-.708a.5.5 0 0 1 .708 0 5 5 0 0 1 0 7.072.5.5 0 1 1-.708-.708 4 4 0 0 0 0-5.656.5.5 0 0 1 0-.708zm2.122-2.122a.5.5 0 0 1 .707 0 8 8 0 0 1 0 11.314.5.5 0 0 1-.707-.707 7 7 0 0 0 0-9.9.5.5 0 0 1 0-.707zM10 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Exam Submissions (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <div id="submissions_chart" style="width: 100%; height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Exam Status Distribution</h6>
            </div>
            <div class="card-body">
                <div id="exam_status_chart" style="width: 100%; height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_close($conn);
require_once ROOT_PATH . '/app/views/partials/admin_footer.php';
?>

<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawAllCharts);

    function drawAllCharts() {
        drawSubmissionsChart();
        drawStatusChart();
    }

    function drawSubmissionsChart() {
        var data = google.visualization.arrayToDataTable(<?php echo json_encode($recent_submissions_data); ?>);

        var options = {
            title: 'Exam Submissions Trend',
            titleTextStyle: { color: '#333', fontSize: 16, bold: false },
            hAxis: {title: 'Day',  titleTextStyle: {color: '#333'}},
            vAxis: {minValue: 0, gridlines: { count: -1 }},
            chartArea: {width: '85%', height: '70%'},
            legend: { position: 'none' },
            colors: ['#4CAF50'],
            areaOpacity: 0.1,
            pointSize: 7,
            pointShape: 'circle',
            animation: {
                startup: true,
                duration: 1000,
                easing: 'out',
            },
        };

        var chart = new google.visualization.AreaChart(document.getElementById('submissions_chart'));
        chart.draw(data, options);
    }

    function drawStatusChart() {
        var data = google.visualization.arrayToDataTable(<?php echo json_encode($exam_status_data); ?>);

        var options = {
            title: 'Exam Status Distribution',
            pieHole: 0.4,
            colors: ['#4CAF50', '#f44336', '#9e9e9e'], // Green for Active, Red for Inactive, Gray for others
            chartArea: {width: '90%', height: '80%'},
            legend: { position: 'bottom' }
        };

        var chart = new google.visualization.PieChart(document.getElementById('exam_status_chart'));
        chart.draw(data, options);
    }

    window.addEventListener('resize', drawAllCharts);
</script>
