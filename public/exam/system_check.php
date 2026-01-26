<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

session_start();

// Ensure candidate is logged in
if (!isset($_SESSION['candidate_logged_in']) || $_SESSION['candidate_logged_in'] !== true) {
    die("Unauthorized access.");
}

if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('APP_URL'));
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if ($exam_id === 0) die("Invalid Exam ID");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check - Exam Portal</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Travarsa-Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .check-card { max-width: 600px; width: 100%; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        #video-preview { width: 100%; max-width: 400px; height: 300px; background: #000; border-radius: 8px; margin: 20px auto; display: block; object-fit: cover; }
        .status-indicator { font-weight: bold; margin-bottom: 15px; display: block; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
    </style>
</head>
<body>

<div class="check-card">
    <h3>System Check</h3>
    <p class="text-muted">We need to verify your camera before the exam starts.</p>

    <div class="position-relative">
        <video id="video-preview" autoplay playsinline muted></video>
        <canvas id="capture-canvas" style="display:none;"></canvas>
    </div>

    <span id="camera-status" class="status-indicator text-danger">Camera not detected</span>

    <button id="btn-request-camera" class="btn btn-primary mb-3">Allow Camera Access</button>
    <button id="btn-start-exam" class="btn btn-success mb-3" style="display:none;" disabled>Capture Photo & Start Exam</button>

    <p class="small text-muted mt-2">Please ensure your face is clearly visible.</p>
</div>

<script>
    const video = document.getElementById('video-preview');
    const canvas = document.getElementById('capture-canvas');
    const btnRequest = document.getElementById('btn-request-camera');
    const btnStart = document.getElementById('btn-start-exam');
    const statusText = document.getElementById('camera-status');
    const examId = <?php echo $exam_id; ?>;
    const baseUrl = '<?php echo BASE_URL; ?>';

    let stream = null;

    btnRequest.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            video.srcObject = stream;
            statusText.textContent = "Camera Connected";
            statusText.className = "status-indicator text-success";
            btnRequest.style.display = 'none';
            btnStart.style.display = 'inline-block';
            btnStart.disabled = false;
        } catch (err) {
            console.error("Camera Error:", err);
            statusText.textContent = "Camera Access Denied. Please allow permission.";
            statusText.className = "status-indicator text-danger";
            alert("Could not access camera. Please check your browser permissions.");
        }
    });

    btnStart.addEventListener('click', () => {
        btnStart.disabled = true;
        btnStart.textContent = "Processing...";

        // Capture photo
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.7); // Low quality for speed

        // Send to server
        fetch(baseUrl + '/api/save-entry-photo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'exam_id=' + examId + '&image=' + encodeURIComponent(dataUrl)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Redirect to actual exam
                window.location.href = baseUrl + '/exam/take/' + examId;
            } else {
                alert("Error saving photo: " + data.message);
                btnStart.disabled = false;
                btnStart.textContent = "Capture Photo & Start Exam";
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network error. Please try again.");
            btnStart.disabled = false;
            btnStart.textContent = "Capture Photo & Start Exam";
        });
    });
</script>

</body>
</html>
