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

// Process form submissions
$error = '';
$message = '';

// Get all active employees for dropdown
$employees = $db->query("SELECT id, name FROM employees WHERE status = 'active' ORDER BY name ASC");

// Check if we have a preselected employee
$preselected_employee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// Handle form submission
if (isPostRequest()) {
    // Get form data
    $employee_id = (int)$_POST['employee_id'];
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $priority = cleanInput($_POST['priority']);
    $assigned_date = cleanInput($_POST['assigned_date']);
    $deadline = cleanInput($_POST['deadline']);

    // Validate form data
    if ($employee_id <= 0 || empty($title) || empty($assigned_date)) {
        $error = 'Please fill all required fields!';
    } else {
        // Insert work assignment
        $sql = "INSERT INTO work_assignments (employee_id, title, description, assigned_date, priority, status, deadline, assigned_by)
                VALUES ($employee_id, '$title', '$description', '$assigned_date', '$priority', 'pending', " .
                (!empty($deadline) ? "'$deadline'" : "NULL") . ", 'admin')";

        if ($db->query($sql)) {
            $_SESSION['success'] = 'Work assignment created successfully!';
            redirect(BASE_URL . '/admin/view_work.php');
        } else {
            $error = 'Error creating work assignment: ' . $db->getConnection()->error;
        }
    }
}

// Set default values
$today = date('Y-m-d');

// Set page title
$page_title = 'Assign Work';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Assign New Work</h1>
    <a href="<?php echo BASE_URL; ?>/admin/view_work.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Work List
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Assign Work to Employee</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">Select Employee <span class="text-danger">*</span></label>
                    <select class="form-select" id="employee_id" name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php while ($employee = $employees->fetch_assoc()): ?>
                            <option value="<?php echo $employee['id']; ?>" <?php if ($employee['id'] == $preselected_employee) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($employee['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="assigned_date" class="form-label">Assigned Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="assigned_date" name="assigned_date" value="<?php echo $today; ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Work Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Enter work title" required>
                </div>
                <div class="col-md-4">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter work description and instructions"></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="deadline" class="form-label">Deadline (Optional)</label>
                    <input type="datetime-local" class="form-control" id="deadline" name="deadline">
                    <small class="form-text text-muted">Leave blank if there's no specific deadline</small>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-clipboard-list me-1"></i> Assign Work
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/view_work.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
