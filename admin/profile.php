<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Initialize auth
$auth = new Auth();

// Require admin login
$auth->requireAdmin();

// Get database instance
$db = Database::getInstance();

// Set page title
$page_title = 'Admin Profile';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate current password
    if ($current_password !== ADMIN_PASSWORD) {
        $errors[] = 'Current password is incorrect';
    }
    
    // Validate new password
    if (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters long';
    }
    
    // Validate password confirmation
    if ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match';
    }
    
    // Update password if no errors
    if (empty($errors)) {
        // In a real application, you would update the password in a database
        // For this example, we'll just show a success message
        $success = 'Password updated successfully. Please note that in this demo, the password is not actually changed.';
    }
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Admin Profile</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Profile Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username:</label>
                        <p><?php echo htmlspecialchars(ADMIN_USERNAME); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role:</label>
                        <p>Administrator</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i>
                    Change Password
                </div>
                <div class="card-body">
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>