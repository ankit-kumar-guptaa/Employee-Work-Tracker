<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Initialize auth
$auth = new Auth();

// Redirect if admin already logged in
if ($auth->isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

// Process login form
$error = '';
if (isPostRequest()) {
    $username = cleanInput($_POST['username']);
    $password = cleanInput($_POST['password']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Attempt login
        if ($auth->loginAdmin($username, $password)) {
            $_SESSION['success'] = 'Welcome, Admin!';
            redirect(BASE_URL . '/admin/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Set page title
$page_title = 'Admin Login';

// Include header
include_once '../templates/header.php';
?>

<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i> Admin Login</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter admin username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3">
                    <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Home
                    </a>
                </div>
            </div>
           
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
