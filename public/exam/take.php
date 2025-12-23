<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('APP_URL'));
}

session_start(); // Ensure session is started

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';
require_once ROOT_PATH . '/app/models/Question.php';
require_once ROOT_PATH . '/app/models/ExamAssignment.php';
require_once ROOT_PATH . '/app/models/Submission.php';

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$candidate_email = isset($_SESSION['candidate_email']) ? $_SESSION['candidate_email'] : '';

// --- DEBUGGING BLOCK (Remove after fixing) ---
// echo "<h3>Debug Info</h3>";
// echo "Exam ID: " . $exam_id . "<br>";
// echo "Session Email: '" . $candidate_email . "'<br>";
// $assigned_emails = getAssignedEmailsByExamId($conn, $exam_id);
// echo "Assigned Emails in DB: <pre>" . print_r($assigned_emails, true) . "</pre>";
// ---------------------------------------------

$is_authorized = false;
if ($exam_id > 0 && !empty($candidate_email)) {
    $assigned_emails = getAssignedEmailsByExamId($conn, $exam_id);
    if (in_array($candidate_email, $assigned_emails)) {
        $is_authorized = true;
    }
}

if (!$is_authorized) {
    // If not logged in via session, check if email is passed in URL (Fallback for testing/direct link)
    // This is less secure but helps if SSO failed to set session
    $url_email = isset($_GET['email']) ? trim($_GET['email']) : '';
    if (!empty($url_email)) {
        $assigned_emails = getAssignedEmailsByExamId($conn, $exam_id);
        if (in_array($url_email, $assigned_emails)) {
            // Auto-login for this request since URL email is valid and assigned
            $candidate_email = $url_email;
            $is_authorized = true;
        }
    }
}

if (!$is_authorized) {
    die("Access Denied: You are not assigned to this exam, or the link is invalid.");
}

// NEW: Check if the candidate has already submitted this exam
$user_id = 0;
$sql_check_user = "SELECT id FROM users WHERE email = ?";
$stmt_check_user = mysqli_prepare($conn, $sql_check_user);
mysqli_stmt_bind_param($stmt_check_user, "s", $candidate_email);
mysqli_stmt_execute($stmt_check_user);
$result_check_user = mysqli_stmt_get_result($stmt_check_user);
if ($row = mysqli_fetch_assoc($result_check_user)) {
    $user_id = $row['id'];
}
mysqli_stmt_close($stmt_check_user);

if ($user_id > 0 && submissionExists($conn, $exam_id, $user_id)) {
    // Candidate has already submitted; prevent access
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — Secure Exam</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary-green: #4CAF50;
        --dark-green: #388E3C;
        --light-green-bg: #e8f5e9;
        --white: #fff;
        --light-gray: #f0f2f5;
        --text-dark: #202124;
        --text-muted: #5f6368;
        --border-color: #dadce0;
        --danger-red: #d33;
        --warning-orange: #f57c00;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        background-color: var(--light-gray);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .access-denied-screen {
        max-width: 500px;
        margin: 40px auto;
        padding: 0;
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
        overflow: hidden;
        text-align: center;
    }

    .access-denied-header {
        background: linear-gradient(135deg, var(--danger-red) 0%, #c0392b 100%);
        color: white;
        padding: 32px 40px;
    }

    .access-denied-header h1 {
        font-size: 28px;
        font-weight: 400;
        margin: 0 0 8px 0;
        color: white;
    }

    .access-denied-header p {
        font-size: 14px;
        margin: 0;
        opacity: 0.95;
    }

    .access-denied-content {
        padding: 40px;
    }

    .access-denied-content p {
        font-size: 16px;
        color: var(--text-dark);
        margin: 0 0 20px 0;
    }

    
    </style>
    </head>
    <body>
    <div class="access-denied-screen">
        <div class="access-denied-header">
            <h1>Access Denied</h1>
            <p>Exam Submission Portal</p>
        </div>
        <div class="access-denied-content">
            <p>You have already submitted this exam. Multiple attempts are not allowed.</p>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

$exam = getExamById($conn, $exam_id);
if (!$exam) {
    die("Exam not found.");
}

$questions = [];
$questions_result = getQuestionsByExamId($conn, $exam_id);
if ($questions_result) {
    while($row = mysqli_fetch_assoc($questions_result)) {
        $questions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Exam — <?php echo htmlspecialchars($exam['title']); ?></title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary-green: #4CAF50;
    --dark-green: #388E3C;
    --light-green-bg: #e8f5e9;
    --white: #fff;
    --light-gray: #f0f2f5;
    --text-dark: #202124;
    --text-muted: #5f6368;
    --border-color: #dadce0;
    --danger-red: #d33;
    --warning-orange: #f57c00;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
    background-color: var(--light-gray);
    user-select: none;
    overflow-x: hidden;
}

/* Pre-exam screen */
.pre-exam-screen {
    max-width: 700px;
    margin: 40px auto;
    padding: 0;
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
    overflow: hidden;
}

.pre-exam-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: white;
    padding: 32px 40px;
    text-align: center;
}

.pre-exam-header h1 {
    font-size: 28px;
    font-weight: 400;
    margin: 0 0 8px 0;
    color: white;
}

.pre-exam-header p {
    font-size: 14px;
    margin: 0;
    opacity: 0.95;
}

.pre-exam-content {
    padding: 40px;
}

.exam-rules-section {
    text-align: left;
    margin-bottom: 32px;
}

.exam-rules-section h2 {
    font-size: 18px;
    font-weight: 500;
    color: var(--text-dark);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.exam-rules-section h2::before {
    content: "📋";
    font-size: 20px;
}

.rules-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
}

.rules-list li {
    padding: 12px 0 12px 32px;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
    color: var(--text-dark);
    line-height: 1.6;
    position: relative;
}

.rules-list li:last-child {
    border-bottom: none;
}

.rules-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--primary-green);
    font-weight: bold;
    font-size: 16px;
}

.disclaimer-section {
    background: #fff3cd;
    border-left: 4px solid var(--warning-orange);
    padding: 16px 20px;
    border-radius: 4px;
    margin-bottom: 24px;
}

.disclaimer-section h3 {
    font-size: 16px;
    font-weight: 500;
    color: var(--warning-orange);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.disclaimer-section h3::before {
    content: "⚠️";
    font-size: 18px;
}

.disclaimer-section p {
    font-size: 13px;
    color: #856404;
    margin: 0;
    line-height: 1.6;
}

.acknowledge-checkbox {
    display: flex;
    align-items: start;
    gap: 12px;
    padding: 16px;
    background: var(--light-gray);
    border-radius: 4px;
    margin-bottom: 24px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.acknowledge-checkbox:hover {
    background: #e8e8e8;
}

.acknowledge-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    cursor: pointer;
    accent-color: var(--primary-green);
    flex-shrink: 0;
}

.acknowledge-checkbox label {
    font-size: 14px;
    color: var(--text-dark);
    cursor: pointer;
    line-height: 1.5;
    flex: 1;
}

.acknowledge-checkbox label strong {
    color: var(--dark-green);
}

.start-button-section {
    text-align: center;
}

#startBtn {
    background-color: var(--primary-green);
    color: white;
    border: none;
    padding: 14px 40px;
    font-size: 16px;
    font-weight: 500;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 200px;
}

#startBtn:hover:not(:disabled) {
    background-color: var(--dark-green);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

#startBtn:disabled {
    background-color: #ccc;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Exam area layout */
#exam-area {
    display: none;
    min-height: 100vh;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Top bar with timer */
.exam-top-bar {
    background: var(--white);
    border-radius: 8px;
    border: 1px solid var(--border-color);
    padding: 16px 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.exam-title-section h2 {
    font-size: 24px;
    font-weight: 400;
    color: var(--text-dark);
    margin: 0;
}

.exam-timer {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 500;
}

.timer-display {
    background: var(--light-green-bg);
    color: var(--dark-green);
    padding: 8px 16px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: center;
}

.timer-display.warning {
    background: #fff3cd;
    color: var(--warning-orange);
}

.timer-display.danger {
    background: #f8d7da;
    color: var(--danger-red);
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Main content area */
.exam-content-wrapper {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1024px) {
    .exam-content-wrapper {
        grid-template-columns: 1fr;
    }
}

/* Questions area */
.questions-section {
    background: var(--white);
    border-radius: 8px;
    border: 1px solid var(--border-color);
    padding: 24px;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
}

#questions-container {
    margin-bottom: 24px;
}

.question-card {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-color);
}

.question-card:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.question-header {
    display: flex;
    align-items: start;
    gap: 12px;
    margin-bottom: 16px;
}

.question-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--light-green-bg);
    color: var(--dark-green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    font-size: 14px;
    flex-shrink: 0;
}

.question-text {
    font-size: 16px;
    color: var(--text-dark);
    line-height: 1.5;
    flex: 1;
}

.question-options {
    margin-top: 16px;
    padding-left: 44px;
}

.option-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
    cursor: pointer;
}

.option-item:hover {
    background-color: #f8f9fa;
}

.option-item input[type="radio"] {
    width: 18px;
    height: 18px;
    margin-right: 12px;
    cursor: pointer;
    accent-color: var(--primary-green);
}

.option-item label {
    font-size: 14px;
    color: var(--text-dark);
    cursor: pointer;
    flex: 1;
}

.question-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-family: 'Roboto', sans-serif;
    font-size: 14px;
    resize: vertical;
    min-height: 120px;
    margin-top: 8px;
    padding-left: 44px;
}

.question-textarea:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
}

/* Sidebar - Camera and logs */
.exam-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.camera-section {
    background: var(--white);
    border-radius: 8px;
    border: 1px solid var(--border-color);
    padding: 16px;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
}

.camera-section h4 {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#videoPreview {
    width: 100%;
    height: auto;
    border-radius: 4px;
    border: 1px solid var(--border-color);
    display: none;
    background: #000;
}

.video-placeholder {
    width: 100%;
    aspect-ratio: 4/3;
    background: var(--light-gray);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 12px;
}

.logs-section {
    background: var(--white);
    border-radius: 8px;
    border: 1px solid var(--border-color);
    padding: 16px;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
    display: flex;
    flex-direction: column;
    max-height: 400px;
}

.logs-section h4 {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#log {
    font-size: 11px;
    color: var(--text-dark);
    background: var(--light-gray);
    padding: 12px;
    border-radius: 4px;
    max-height: 320px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    line-height: 1.6;
}

#log div {
    margin-bottom: 6px;
    padding: 4px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

#log div:last-child {
    border-bottom: none;
}

#log .warning {
    color: var(--danger-red);
    font-weight: 500;
}

/* Submit button */
.submit-section {
    margin-top: 24px;
    text-align: right;
}

#submitBtn {
    background-color: var(--primary-green);
    color: white;
    border: none;
    padding: 12px 32px;
    font-size: 16px;
    font-weight: 500;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

#submitBtn:hover {
    background-color: var(--dark-green);
}

/* Fullscreen warning modal */
#fsWarningModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 999999;
    justify-content: center;
    align-items: center;
}

#fsWarningModal.show {
    display: flex;
}

.fs-modal-content {
    background: white;
    padding: 40px;
    border-radius: 8px;
    text-align: center;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

.fs-modal-content h2 {
    color: var(--danger-red);
    margin-top: 0;
    font-size: 24px;
    font-weight: 500;
}

.fs-modal-content p {
    font-size: 16px;
    margin: 15px 0;
    color: var(--text-dark);
}

.fs-modal-timer {
    font-size: 32px;
    font-weight: bold;
    color: var(--danger-red);
    margin: 20px 0;
    font-family: 'Courier New', monospace;
}

.fs-modal-buttons {
    margin-top: 25px;
    display: flex;
    gap: 12px;
    justify-content: center;
}

.fs-modal-buttons button {
    padding: 12px 30px;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: opacity 0.2s;
}

.fs-modal-buttons button:hover {
    opacity: 0.9;
}

.fs-resume-btn {
    background: #3085d6;
    color: white;
}

.fs-submit-btn {
    background: var(--danger-red);
    color: white;
}

/* Scrollbar styling */
#log::-webkit-scrollbar {
    width: 6px;
}

#log::-webkit-scrollbar-track {
    background: transparent;
}

#log::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

#log::-webkit-scrollbar-thumb:hover {
    background: var(--text-muted);
}
</style>
</head>
<body>

<!-- Pre-exam screen -->
<div class="pre-exam-screen" id="preExamScreen">
    <div class="pre-exam-header">
        <h1><?php echo htmlspecialchars($exam['title']); ?></h1>
        <p>Duration: <?php echo $exam['duration']; ?> minutes</p>
    </div>

    <div class="pre-exam-content">
        <!-- Exam Rules Section -->
        <div class="exam-rules-section">
            <h2>Exam Rules & Guidelines</h2>
            <ul class="rules-list">
                <li>You must remain in fullscreen mode throughout the entire exam. Exiting fullscreen will result in automatic submission.</li>
                <li>Do not switch tabs or minimize the browser window. Multiple tab switches will result in automatic submission.</li>
                <li>Right-click, copy, paste, and certain keyboard shortcuts (F12, Ctrl+Shift+I, etc.) are disabled during the exam.</li>
                <li>Your webcam will be activated and monitored throughout the exam. Ensure good lighting and a clear view of your face.</li>
                <li>You have <strong><?php echo $exam['duration']; ?> minutes</strong> to complete this exam. The timer will start once you click "Start Exam".</li>
                <li>Answer all questions carefully. You can review your answers before submitting.</li>
                <li>Once you submit the exam, you cannot make any changes.</li>
                <li>Ensure you have a stable internet connection before starting the exam.</li>
            </ul>
        </div>

        <!-- Disclaimer Section -->
        <div class="disclaimer-section">
            <h3>Important Disclaimer</h3>
            <p>
                By proceeding with this exam, you acknowledge that any attempt to cheat, bypass security measures,
                or violate exam rules will result in immediate disqualification and automatic submission of your exam.
                The system monitors your activity, including webcam feed, tab switches, and suspicious behavior.
                All actions are logged and may be reviewed by exam administrators.
            </p>
        </div>

        <!-- Acknowledge Checkbox -->
        <div class="acknowledge-checkbox">
            <input type="checkbox" id="acknowledgeRules" required>
            <label for="acknowledgeRules">
                I have read and understood all the <strong>Exam Rules & Guidelines</strong> and the <strong>Disclaimer</strong> above.
                I agree to comply with all rules and understand the consequences of violating them.
            </label>
        </div>

        <!-- Start Button -->
        <div class="start-button-section">
            <button id="startBtn" disabled>Start Exam</button>
        </div>
    </div>
</div>

<!-- Exam area -->
<div id="exam-area">
    <!-- Top bar with timer -->
    <div class="exam-top-bar">
        <div class="exam-title-section">
            <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
        </div>
        <div class="exam-timer">
            <span>Time Remaining:</span>
            <div class="timer-display" id="timerDisplay">00:00:00</div>
        </div>
    </div>

    <!-- Main content wrapper -->
    <div class="exam-content-wrapper">
        <!-- Questions section -->
        <div class="questions-section">
            <div id="questions-container">
                <!-- Questions will be rendered here by JavaScript -->
            </div>
            <div class="submit-section">
                <button id="submitBtn">Submit Exam</button>
            </div>
        </div>

        <!-- Sidebar: Camera and logs -->
        <div class="exam-sidebar">
            <!-- Camera section -->
            <div class="camera-section">
                <h4>Camera Feed</h4>
                <video id="videoPreview" autoplay muted></video>
                <div class="video-placeholder" id="videoPlaceholder">Camera not active</div>
            </div>

            <!-- Logs section -->
            <div class="logs-section">
                <h4>Event Log</h4>
                <div class="log" id="log"></div>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen warning modal -->
<div id="fsWarningModal">
    <div class="fs-modal-content">
        <h2>⚠️ You left fullscreen!</h2>
        <p>Exiting fullscreen will terminate and auto-submit the exam.</p>
        <div class="fs-modal-timer">Time remaining: <span id="fsTimer">5</span> seconds</div>
        <div class="fs-modal-buttons">
            <button class="fs-resume-btn" id="fsResumeBtn">Resume Exam</button>
            <button class="fs-submit-btn" id="fsSubmitBtn">Submit Exam Now</button>
        </div>
    </div>
</div>

<script>
// --- Data from PHP ---
const EXAM_DATA = <?php echo json_encode($exam); ?>;
const QUESTIONS = <?php echo json_encode($questions); ?>;
const CANDIDATE_EMAIL = <?php echo json_encode($candidate_email); ?>;

// --- Config ---
const SNAPSHOT_INTERVAL_MS = 10000;
const AUTO_SUBMIT_AFTER_VISIBILITY = 3;
const SERVER_LOG_ENDPOINT = '<?php echo BASE_URL; ?>/api/log-activity';
const SERVER_SUBMIT_ENDPOINT = '<?php echo BASE_URL; ?>/api/submit-exam';
const SERVER_SNAPSHOT_ENDPOINT = '<?php echo BASE_URL; ?>/api/upload-snapshot';

// Calculate exam duration in seconds
const EXAM_DURATION_MINUTES = parseInt(EXAM_DATA.duration) || 60;
const EXAM_DURATION_SECONDS = EXAM_DURATION_MINUTES * 60;

// --- DOM Elements ---
const startBtn = document.getElementById('startBtn');
const preExamScreen = document.getElementById('preExamScreen');
const examArea = document.getElementById('exam-area');
const submitBtn = document.getElementById('submitBtn');
const video = document.getElementById('videoPreview');
const videoPlaceholder = document.getElementById('videoPlaceholder');
const logBox = document.getElementById('log');
const questionsContainer = document.getElementById('questions-container');
const timerDisplay = document.getElementById('timerDisplay');
const acknowledgeCheckbox = document.getElementById('acknowledgeRules');

// --- Enable/Disable Start Button based on acknowledgment ---
if (acknowledgeCheckbox && startBtn) {
    acknowledgeCheckbox.addEventListener('change', function() {
        startBtn.disabled = !this.checked;
    });
}

// --- State ---
let fullscreenHandling = false;
let visibilityCount = 0;
let snapshotTimer = null;
let webcamStream = null;
let examStarted = false;
let allowExit = false;
let logsBuffer = [];
let examTimer = null;
let remainingSeconds = EXAM_DURATION_SECONDS;

// --- Timer Functions ---
function startExamTimer() {
    updateTimerDisplay();
    examTimer = setInterval(() => {
        remainingSeconds--;
        updateTimerDisplay();

        if (remainingSeconds <= 0) {
            clearInterval(examTimer);
            log('Exam time expired — auto-submitting.', true);
            autoSubmitExam();
        } else if (remainingSeconds <= 60) {
            timerDisplay.classList.add('danger');
        } else if (remainingSeconds <= 300) {
            timerDisplay.classList.add('warning');
        }
    }, 1000);
}

function updateTimerDisplay() {
    const hours = Math.floor(remainingSeconds / 3600);
    const minutes = Math.floor((remainingSeconds % 3600) / 60);
    const seconds = remainingSeconds % 60;

    timerDisplay.textContent =
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');
}

function stopExamTimer() {
    if (examTimer) {
        clearInterval(examTimer);
        examTimer = null;
    }
}

// --- Logging ---
function log(msg, danger = false) {
    const t = new Date().toLocaleTimeString();
    const el = document.createElement('div');
    el.textContent = `[${t}] ${msg}`;
    if (danger) el.classList.add('warning');
    logBox.prepend(el);
    logsBuffer.push({ ts: Date.now(), message: msg, danger });
    flushLogsToServer().catch(e => console.warn(e));
}

async function flushLogsToServer() {
    if (logsBuffer.length === 0) return;
    try {
        const payload = logsBuffer.splice(0);
        await fetch(SERVER_LOG_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ exam_id: EXAM_DATA.exam_id, email: CANDIDATE_EMAIL, events: payload })
        });
    } catch (e) {
        console.warn('Failed to send logs', e);
    }
}

// --- Fullscreen ---
function requestFullscreen() {
    const el = document.documentElement;
    if (el.requestFullscreen) return el.requestFullscreen();
    if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
    if (el.msRequestFullscreen) return el.msRequestFullscreen();
    return Promise.reject(new Error('Fullscreen not supported'));
}

function exitFullscreen() {
    if (document.exitFullscreen) return document.exitFullscreen();
    if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
    if (document.msExitFullscreen) return document.msExitFullscreen();
    return Promise.resolve();
}

function isFullscreenActive() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
}

// --- Exam Start ---
startBtn.addEventListener('click', async () => {
    try {
        await requestFullscreen();
        preExamScreen.style.display = 'none';
        examArea.style.display = 'block';
        examStarted = true;
        log('Exam started — entered fullscreen.');
        startExamTimer();
        renderQuestions();
        startWebcamIfAllowed();
        snapshotTimer = setInterval(takeAndSendSnapshot, SNAPSHOT_INTERVAL_MS);
    } catch (err) {
        log('Could not enter fullscreen: ' + err.message, true);
        Swal.fire({ icon:'error', title:'Fullscreen failed', text:'Allow fullscreen to proceed.' });
    }
});

// --- Fullscreen Exit Detection ---
function handleFullscreenChange() {
    if (!examStarted || isFullscreenActive() || allowExit || fullscreenHandling) return;
    fullscreenHandling = true;
    log('Fullscreen exit detected — showing timed warning.', true);
    const modal = document.getElementById('fsWarningModal');
    const timerSpan = document.getElementById('fsTimer');
    const resumeBtn = document.getElementById('fsResumeBtn');
    const submitBtn = document.getElementById('fsSubmitBtn');
    let timer = 5;
    timerSpan.textContent = timer;
    modal.classList.add('show');
    const intervalId = setInterval(() => {
        timer--;
        timerSpan.textContent = timer;
        if (timer <= 0) {
            clearInterval(intervalId);
            modal.classList.remove('show');
            fullscreenHandling = false;
            autoSubmitExam();
        }
    }, 1000);

    const handleResume = () => {
        clearInterval(intervalId);
        modal.classList.remove('show');
        fullscreenHandling = false;
        requestFullscreen().then(() => log('User resumed fullscreen.')).catch(err => log('Failed to re-enter fullscreen: ' + err.message, true));
    };
    const handleSubmit = () => {
        clearInterval(intervalId);
        modal.classList.remove('show');
        fullscreenHandling = false;
        allowExit = true;
        autoSubmitExam();
    };

    resumeBtn.replaceWith(resumeBtn.cloneNode(true));
    submitBtn.replaceWith(submitBtn.cloneNode(true));
    document.getElementById('fsResumeBtn').addEventListener('click', handleResume);
    document.getElementById('fsSubmitBtn').addEventListener('click', handleSubmit);
}

document.addEventListener('fullscreenchange', handleFullscreenChange);
document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
document.addEventListener('mozfullscreenchange', handleFullscreenChange);
document.addEventListener('MSFullscreenChange', handleFullscreenChange);

// --- Visibility & Restrictions ---
document.addEventListener('visibilitychange', () => {
    if (!examStarted) return;
    if (document.hidden) {
        visibilityCount++;
        log(`Tab hidden/visibilitychange #${visibilityCount}`, true);
        flushLogsToServer();
        Swal.fire({ icon:'warning', title:'Tab switched', text:'Switching tabs is not allowed. Multiple switches will auto-submit.' });
        if (visibilityCount >= AUTO_SUBMIT_AFTER_VISIBILITY) {
            log('Visibility change threshold reached — auto-submitting.', true);
            autoSubmitExam();
        }
    } else {
        log('Returned to tab.');
    }
});

document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('copy', e => e.preventDefault());
document.addEventListener('paste', e => e.preventDefault());
document.addEventListener('keydown', (e) => {
    const blocked = e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','C','J'].includes(e.key.toUpperCase())) || (e.ctrlKey && ['T','N','W','R'].includes(e.key.toUpperCase())) || (e.metaKey && ['T','N'].includes(e.key.toUpperCase()));
    if (blocked) {
        e.preventDefault();
        e.stopPropagation();
        log('Blocked keyboard shortcut: ' + (e.key || JSON.stringify(e)), true);
        return false;
    }
});

// --- Webcam ---
async function startWebcamIfAllowed() {
    try {
        webcamStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        video.srcObject = webcamStream;
        video.style.display = 'block';
        videoPlaceholder.style.display = 'none';
        log('Webcam started (permission granted).');
        takeAndSendSnapshot();
    } catch (err) {
        log('Webcam not allowed or failed: ' + (err.message || err), true);
        videoPlaceholder.textContent = 'Camera access denied';
    }
}

async function takeAndSendSnapshot() {
    if (!webcamStream) return;
    try {
        const track = webcamStream.getVideoTracks()[0];
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 320;
        canvas.height = video.videoHeight || 240;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataURL = canvas.toDataURL('image/jpeg', 0.6);
        await fetch(SERVER_SNAPSHOT_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ exam_id: EXAM_DATA.exam_id, email: CANDIDATE_EMAIL, ts: Date.now(), image: dataURL })
        });
        log('Snapshot captured and sent to server.');
    } catch (err) {
        log('Snapshot failed: ' + (err.message || err), true);
    }
}

// --- Exam Logic ---
function renderQuestions() {
    questionsContainer.innerHTML = '';
    QUESTIONS.forEach((q, index) => {
        const qDiv = document.createElement('div');
        qDiv.className = 'question-card';

        const questionHeader = document.createElement('div');
        questionHeader.className = 'question-header';

        const questionNumber = document.createElement('div');
        questionNumber.className = 'question-number';
        questionNumber.textContent = index + 1;

        const questionText = document.createElement('div');
        questionText.className = 'question-text';
        questionText.textContent = q.question_text;

        questionHeader.appendChild(questionNumber);
        questionHeader.appendChild(questionText);
        qDiv.appendChild(questionHeader);

        if (q.type === 'mcq' && q.options) {
            const options = JSON.parse(q.options);
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'question-options';

            for (const key in options) {
                const optionItem = document.createElement('div');
                optionItem.className = 'option-item';

                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = `q_${q.question_id}`;
                radio.value = key;
                radio.id = `q_${q.question_id}_${key}`;

                const label = document.createElement('label');
                label.htmlFor = `q_${q.question_id}_${key}`;
                label.textContent = options[key];

                optionItem.appendChild(radio);
                optionItem.appendChild(label);
                optionsContainer.appendChild(optionItem);
            }

            qDiv.appendChild(optionsContainer);
        } else {
            const textarea = document.createElement('textarea');
            textarea.name = `q_${q.question_id}`;
            textarea.className = 'question-textarea';
            textarea.placeholder = 'Type your answer here...';
            qDiv.appendChild(textarea);
        }

        questionsContainer.appendChild(qDiv);
    });
}

function getAnswers() {
    const answers = {};
    QUESTIONS.forEach(q => {
        const input = document.querySelector(`[name="q_${q.question_id}"]:checked`) || document.querySelector(`[name="q_${q.question_id}"]`);
        if (input) {
            answers[q.question_id] = input.value;
        }
    });
    return answers;
}

// --- Submit Logic ---
submitBtn.addEventListener('click', () => {
    Swal.fire({
        title:'Submit Exam?',
        text:'Are you sure you want to submit?',
        icon:'question',
        showCancelButton:true
    }).then(res => {
        if (res.isConfirmed) manualSubmitExam();
    });
});

async function manualSubmitExam() {
    allowExit = true;
    log('Manual submit initiated.');
    stopExamTimer();
    const answers = getAnswers();
    try {
        await fetch(SERVER_SUBMIT_ENDPOINT, {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ exam_id: EXAM_DATA.exam_id, email: CANDIDATE_EMAIL, ts: Date.now(), answers, logs: logsBuffer })
        });
        log('Answers submitted to server.');
    } catch (err) {
        log('Submit failed: ' + err.message, true);
    } finally {
        cleanupAfterSubmit();
        Swal.fire({ icon:'success', title:'Submitted', text:'Exam submitted.' });
        try { await exitFullscreen(); } catch(e){/*ignore*/};
    }
}

async function autoSubmitExam() {
    allowExit = true;
    log('Auto-submitting exam due to suspicious activity.', true);
    stopExamTimer();
    const answers = getAnswers();
    try {
        await fetch(SERVER_SUBMIT_ENDPOINT, {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ exam_id: EXAM_DATA.exam_id, email: CANDIDATE_EMAIL, ts: Date.now(), auto: true, reason: 'suspicious_activity', answers, logs: logsBuffer })
        });
        log('Auto-submitted to server.');
    } catch (err) {
        log('Auto-submit failed: ' + err.message, true);
    } finally {
        cleanupAfterSubmit();
        Swal.fire({ icon:'info', title:'Exam Auto-Submitted', text:'Your exam was auto-submitted.' });
        try { await exitFullscreen(); } catch(e){/*ignore*/};
    }
}

function cleanupAfterSubmit() {
    examArea.style.display = 'none';
    examStarted = false;
    stopExamTimer();
    clearInterval(snapshotTimer);
    snapshotTimer = null;
    if (webcamStream) {
        webcamStream.getTracks().forEach(t => t.stop());
        webcamStream = null;
        video.style.display = 'none';
        videoPlaceholder.style.display = 'flex';
    }
    flushLogsToServer();
}

// --- Warn on page unload ---
window.addEventListener('beforeunload', (e) => {
    if (examStarted) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
</body>
</html>