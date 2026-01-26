<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    require_once ROOT_PATH . '/config/app.php';
}

session_start();

if (!isset($_SESSION['candidate_logged_in']) || $_SESSION['candidate_logged_in'] !== true) {
    header("Location: " . BASE_URL . "/register/open-exam");
    exit();
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Exam.php';

$open_exams = getOpenRegistrationExams($conn);
$candidate_name = $_SESSION['candidate_name'] ?? 'Candidate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams - Exam Portal</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #4CAF50;
            --dark-green: #388E3C;
            --light-green-bg: #e8f5e9;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .navbar-brand img {
            height: 40px;
        }
        .exam-card {
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            height: 100%;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .exam-title {
            color: #333;
            font-weight: 500;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .exam-meta {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .btn-start {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            color: white;
            width: 100%;
        }
        .btn-start:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png" alt="Travarsa Logo">
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">Welcome, <strong><?php echo htmlspecialchars($candidate_name); ?></strong></span>
                <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-outline-secondary btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4">Available Exams</h2>

        <?php if ($open_exams && mysqli_num_rows($open_exams) > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while($exam = mysqli_fetch_assoc($open_exams)): ?>
                    <div class="col">
                        <div class="card exam-card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                <div class="exam-meta">
                                    <i class="bi bi-clock"></i> Duration: <?php echo $exam['duration']; ?> minutes
                                </div>
                                <p class="card-text text-muted flex-grow-1">
                                    <?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . (strlen($exam['description']) > 100 ? '...' : ''); ?>
                                </p>
                                <a href="<?php echo BASE_URL; ?>/exam/start-open/<?php echo $exam['exam_id']; ?>" class="btn btn-start mt-3">Start Exam</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <h4>No open exams available at the moment.</h4>
                <p>Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
