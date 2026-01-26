<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('APP_URL'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Registration - Exam Portal</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #43A047;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
            --bg-light: #F3F4F6;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: white;
            height: 100vh;
            overflow: hidden;
        }
        .split-layout {
            display: flex;
            height: 100%;
            width: 100%;
        }
        /* Left Side - Image */
        .left-panel {
            flex: 1;
            background-image: url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
            display: none; /* Hidden on mobile */
        }
        .left-panel::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)); /* Darker Gradient Overlay */
        }
        .left-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: white;
        }
        .left-content h1 {
            font-weight: 700;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            line-height: 1.1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .left-content p {
            font-size: 1.25rem;
            opacity: 0.95;
            max-width: 500px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        /* Right Side - Form */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-color: white;
            overflow-y: auto;
        }
        .form-container {
            width: 100%;
            max-width: 550px;
            padding: 2rem;
        }
        .logo-img {
            height: 50px;
            margin-bottom: 2rem;
        }
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .form-subtitle {
            color: var(--text-muted);
            margin-bottom: 2.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            margin-top: 1.5rem;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        /* Responsive */
        @media (min-width: 992px) {
            .left-panel { display: block; }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <!-- Left Side -->
        <div class="left-panel">
            <div class="left-content">
                <h1>Start Your<br>Journey Here.</h1>
                <p>Join thousands of candidates taking the next step in their career with our secure assessment platform.</p>
            </div>
        </div>

        <!-- Right Side -->
        <div class="right-panel">
            <div class="form-container">
                <img src="<?php echo BASE_URL; ?>/public/assets/images/Travarsa-Logo.png" alt="Travarsa Logo" class="logo-img">

                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Enter your details to access available exams.</p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger py-2 mb-4 text-center border-0 bg-danger bg-opacity-10 text-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label for="roll_number" class="form-label">Roll Number</label>
                            <input type="text" class="form-control" id="roll_number" name="roll_number" placeholder="e.g. 12345">
                        </div>
                        <div class="col-md-6">
                            <label for="college_name" class="form-label">College Name</label>
                            <select class="form-select" id="college_name" name="college_name">
                                <option value="" selected disabled>Select College</option>
                                <option value="Jadavpur University">Jadavpur University</option>
                                <option value="University of Calcutta">University of Calcutta</option>
                                <option value="Maulana Abul Kalam Azad University of Technology (MAKAUT)">Maulana Abul Kalam Azad University of Technology (MAKAUT)</option>
                                <option value="University of Engineering and Management (UEM), Kolkata">University of Engineering and Management (UEM), Kolkata</option>
                                <option value="Aliah University">Aliah University</option>
                                <option value="Government College of Engineering and Ceramic Technology">Government College of Engineering and Ceramic Technology</option>
                                <option value="Government College of Engineering and Leather Technology">Government College of Engineering and Leather Technology</option>
                                <option value="Heritage Institute of Technology">Heritage Institute of Technology</option>
                                <option value="Institute of Engineering and Management (IEM)">Institute of Engineering and Management (IEM)</option>
                                <option value="Netaji Subhash Engineering College">Netaji Subhash Engineering College</option>
                                <option value="Techno India University">Techno India University</option>
                                <option value="Techno Main Salt Lake">Techno Main Salt Lake</option>
                                <option value="Techno International New Town">Techno International New Town</option>
                                <option value="Techno International Batanagar">Techno International Batanagar</option>
                                <option value="Narula Institute of Technology">Narula Institute of Technology</option>
                                <option value="St. Thomas’ College of Engineering and Technology">St. Thomas’ College of Engineering and Technology</option>
                                <option value="MCKV Institute of Engineering">MCKV Institute of Engineering</option>
                                <option value="Bengal Institute of Technology">Bengal Institute of Technology</option>
                                <option value="Budge Budge Institute of Technology">Budge Budge Institute of Technology</option>
                                <option value="Camellia Institute of Technology">Camellia Institute of Technology</option>
                                <option value="Dream Institute of Technology">Dream Institute of Technology</option>
                                <option value="Future Institute of Engineering and Management">Future Institute of Engineering and Management</option>
                                <option value="Calcutta Institute of Engineering and Management">Calcutta Institute of Engineering and Management</option>
                                <option value="Dr. Sudhir Chandra Sur Institute of Technology and Sports Complex">Dr. Sudhir Chandra Sur Institute of Technology and Sports Complex</option>
                                <option value="Gargi Memorial Institute of Technology">Gargi Memorial Institute of Technology</option>
                                <option value="Swami Vivekananda Institute of Science and Technology">Swami Vivekananda Institute of Science and Technology</option>
                                <option value="Adamas University">Adamas University</option>
                                <option value="Amity University Kolkata">Amity University Kolkata</option>
                                <option value="Brainware University">Brainware University</option>
                                <option value="Sister Nivedita University">Sister Nivedita University</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Continue to Exams</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
