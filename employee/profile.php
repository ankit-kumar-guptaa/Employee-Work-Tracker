<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Initialize auth
$auth = new Auth();

// Require employee login
$auth->requireEmployee();

// Get database instance
$db = Database::getInstance();

// Get employee data
$employee_id = $_SESSION['employee_id'];
$employee = $db->query("SELECT * FROM employees WHERE id = $employee_id")->fetch_assoc();

// Set page title
$page_title = 'My Profile';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        // Update employee data
        $db->query("UPDATE employees SET 
            name = '$name', 
            email = '$email', 
            phone = '$phone', 
            updated_at = NOW() 
            WHERE id = $employee_id");
        
        // Refresh employee data
        $employee = $db->query("SELECT * FROM employees WHERE id = $employee_id")->fetch_assoc();
        
        $profile_success = 'Profile updated successfully';
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $password_errors = [];
        
        // Verify current password
        $result = $db->query("SELECT password FROM employees WHERE id = $employee_id")->fetch_assoc();
        if (!password_verify($current_password, $result['password'])) {
            $password_errors[] = 'Current password is incorrect';
        }
        
        // Validate new password
        if (strlen($new_password) < 6) {
            $password_errors[] = 'New password must be at least 6 characters long';
        }
        
        // Validate password confirmation
        if ($new_password !== $confirm_password) {
            $password_errors[] = 'New password and confirmation do not match';
        }
        
        // Update password if no errors
        if (empty($password_errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $db->query("UPDATE employees SET 
                password = '$hashed_password', 
                updated_at = NOW() 
                WHERE id = $employee_id");
            
            $password_success = 'Password updated successfully';
        }
    }
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">My Profile</h1>
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
                    <?php if (isset($profile_success)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($profile_success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($employee['username']); ?>" disabled>
                            <div class="form-text">Username cannot be changed</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <p class="form-control-static"><?php echo htmlspecialchars($employee['department']); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <p class="form-control-static"><?php echo htmlspecialchars($employee['position']); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Joining Date</label>
                            <p class="form-control-static"><?php echo formatDate($employee['joining_date']); ?></p>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
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
                    <?php if (isset($password_errors) && !empty($password_errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($password_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($password_success)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($password_success); ?>
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
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>