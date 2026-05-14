<?php
$page_title = 'EVSU-OCC Evaluation System';
$active_page = 'home';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="jumbotron" style="background: linear-gradient(to right, rgba(139, 0, 0, 0.05), rgba(165, 42, 42, 0.05));">
        <h1 class="display-4" style="color: #8B0000;">Welcome to EVSU-OCC EVALUATION SURVEY SYSTEM FOR THE REGISTRAR</h1>
        <p class="lead">A comprehensive platform for course and instructor evaluations to enhance the quality of education at Eastern Visayas State University - Ormoc City Campus.</p>
        <hr class="my-4">
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id']): ?>
            <p>Welcome back! You are logged in as <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></strong>.</p>
            <a class="btn btn-primary btn-lg" href="<?php echo ($_SESSION['role'] == 'admin') ? 'admin/index.php' : '../student/index.php'; ?>" role="button" style="background-color: #8B0000; border-color: #8B0000;">
    Go to Dashboard
</a>
        <?php else: ?>
            <p>Please log in to access the evaluation system or register if you don't have an account yet.</p>
            <a class="btn btn-primary btn-lg" href="login.php" role="button" style="background-color: #8B0000; border-color: #8B0000; margin-right: 10px;">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a class="btn btn-outline-primary btn-lg" href="login.php?register" role="button" style="color: #8B0000; border-color: #8B0000;">
                <i class="fas fa-user-plus"></i> Register
            </a>
        <?php endif; ?>
    </div>

    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="fas fa-clipboard-check fa-3x mb-3" style="color: #8B0000;"></i>
                    <h4 class="card-title">Course Evaluations</h4>
                    <p class="card-text">Provide feedback on your courses and instructors to help improve teaching quality.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x mb-3" style="color: #A52A2A;"></i>
                    <h4 class="card-title">Data Analysis</h4>
                    <p class="card-text">Comprehensive reports help administrators make data-driven decisions for improvement.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="fas fa-shield-alt fa-3x mb-3" style="color: #D11111;"></i>
                    <h4 class="card-title">Secure & Confidential</h4>
                    <p class="card-text">All evaluations are anonymous and your data is protected with industry-standard security.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-6">
            <h3 class="mb-3" style="color: #8B0000;">System Features</h3>
            <ul class="list-group">
                <li class="list-group-item d-flex align-items-center">
                    <i class="fas fa-check-circle mr-3" style="color: #28a745;"></i>
                    Easy-to-use interface for students
                </li>
                <li class="list-group-item d-flex align-items-center">
                    <i class="fas fa-check-circle mr-3" style="color: #28a745;"></i>
                    Real-time evaluation tracking
                </li>
                <li class="list-group-item d-flex align-items-center">
                    <i class="fas fa-check-circle mr-3" style="color: #28a745;"></i>
                    Comprehensive reporting tools for administrators
                </li>
                <li class="list-group-item d-flex align-items-center">
                    <i class="fas fa-check-circle mr-3" style="color: #28a745;"></i>
                    Mobile-friendly design
                </li>
            </ul>
        </div>
        
        <div class="col-md-6">
            <h3 class="mb-3" style="color: #8B0000;">Important Dates</h3>
            <div class="card">
                <div class="card-body">
                    <p><strong>Current Semester:</strong> 2nd Semester AY 2023-2024</p>
                    <p><strong>Evaluation Period:</strong> November 15 - December 15, 2023</p>
                    <p><strong>Results Release:</strong> January 15, 2024</p>
                    <p class="mb-0"><strong>System Version:</strong> 1.0</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>