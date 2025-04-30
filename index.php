<?php
// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize auth
$auth = new Auth();

// Redirect to dashboard if already logged in
if ($auth->isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
} elseif ($auth->isEmployeeLoggedIn()) {
    redirect(BASE_URL . '/employee/dashboard.php');
}

// Set page title
$page_title = 'Welcome';

// Include header
include_once 'templates/header.php';
?>

<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-8 text-center">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <img src="https://elitecorporatesolutions.com/images/logo/logo.png" style="height: 60px;" alt="logo">
                    <h1 class="mb-4 fw-bold text-primary">
                        <i class="fas fa-tasks me-2"></i> <?php echo APP_NAME; ?>
                    </h1>
                    <p class="lead mb-5">A powerful tool for tracking and managing employee daily work tasks.</p>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card dashboard-card h-100 bg-primary text-white">
                                <div class="card-body p-4 text-center">
                                    <i class="fas fa-user-shield fa-3x mb-3"></i>
                                    <h5 class="card-title">Admin Login</h5>
                                    <p class="card-text">Access the admin dashboard to manage employees and track their work progress.</p>
                                    <a href="<?php echo BASE_URL; ?>/admin/login.php" class="btn btn-light mt-3">
                                        <i class="fas fa-sign-in-alt me-2"></i> Login as Admin
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card dashboard-card h-100 bg-success text-white">
                                <div class="card-body p-4 text-center">
                                    <i class="fas fa-user-tie fa-3x mb-3"></i>
                                    <h5 class="card-title">Employee Login</h5>
                                    <p class="card-text">Login to update your daily work status and view assigned tasks.</p>
                                    <a href="<?php echo BASE_URL; ?>/employee/login.php" class="btn btn-light mt-3">
                                        <i class="fas fa-sign-in-alt me-2"></i> Login as Employee
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2 text-start">
                        <div class="col-md-4">
                            <div class="feature d-flex">
                                <div class="me-3">
                                    <i class="fas fa-chart-line fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5>Track Progress</h5>
                                    <p class="text-muted">Monitor task completion and employee productivity</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature d-flex">
                                <div class="me-3">
                                    <i class="fas fa-clipboard-check fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5>Assign Tasks</h5>
                                    <p class="text-muted">Easily assign and manage daily tasks</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature d-flex">
                                <div class="me-3">
                                    <i class="fas fa-file-alt fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5>Generate Reports</h5>
                                    <p class="text-muted">Create detailed performance reports</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
