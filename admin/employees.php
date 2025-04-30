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
$message = '';
$error = '';

// Handle employee actions (add, edit, delete)
$action = isset($_GET['action']) ? $_GET['action'] : '';
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process employee deletion
if ($action === 'delete' && $employee_id > 0) {
    // Check if employee exists
    $check_employee = $db->query("SELECT id FROM employees WHERE id = $employee_id");

    if ($check_employee->num_rows === 1) {
        // Delete employee
        if ($db->query("DELETE FROM employees WHERE id = $employee_id")) {
            $_SESSION['success'] = "Employee deleted successfully!";
            redirect(BASE_URL . '/admin/employees.php');
        } else {
            $error = "Error deleting employee: " . $db->getConnection()->error;
        }
    } else {
        $error = "Employee not found!";
    }
}

// Handle employee activation/deactivation
if ($action === 'toggle_status' && $employee_id > 0) {
    // Check if employee exists
    $check_employee = $db->query("SELECT id, status FROM employees WHERE id = $employee_id");

    if ($check_employee->num_rows === 1) {
        $employee_data = $check_employee->fetch_assoc();
        $new_status = ($employee_data['status'] === 'active') ? 'inactive' : 'active';

        // Update employee status
        if ($db->query("UPDATE employees SET status = '$new_status' WHERE id = $employee_id")) {
            $_SESSION['success'] = "Employee status updated successfully!";
            redirect(BASE_URL . '/admin/employees.php');
        } else {
            $error = "Error updating employee status: " . $db->getConnection()->error;
        }
    } else {
        $error = "Employee not found!";
    }
}

// Process add/edit employee form submission
if (isPostRequest()) {
    // Get form data
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $name = cleanInput($_POST['name']);
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $department = cleanInput($_POST['department']);
    $position = cleanInput($_POST['position']);
    $joining_date = cleanInput($_POST['joining_date']);
    $status = cleanInput($_POST['status']);

    // Validate form data
    if (empty($name) || empty($username)) {
        $error = "Name and username are required!";
    } else {
        // Check if username already exists (for new employee or different employee)
        $username_check_query = "SELECT id FROM employees WHERE username = '$username'";
        if ($edit_id > 0) {
            $username_check_query .= " AND id != $edit_id";
        }

        $username_check = $db->query($username_check_query);

        if ($username_check->num_rows > 0) {
            $error = "Username already exists! Please choose another username.";
        } else {
            // Process password
            $password_set = false;
            $password_sql = "";

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $password_sql = ", password = '$password'";
                $password_set = true;
            }

            if ($edit_id > 0) {
                // Update existing employee
                $sql = "UPDATE employees SET
                        name = '$name',
                        username = '$username',
                        email = '$email',
                        phone = '$phone',
                        department = '$department',
                        position = '$position',
                        joining_date = " . (!empty($joining_date) ? "'$joining_date'" : "NULL") . ",
                        status = '$status'
                        $password_sql
                        WHERE id = $edit_id";

                if ($db->query($sql)) {
                    $_SESSION['success'] = "Employee updated successfully!";
                    redirect(BASE_URL . '/admin/employees.php');
                } else {
                    $error = "Error updating employee: " . $db->getConnection()->error;
                }
            } else {
                // Add new employee
                if (!$password_set) {
                    $error = "Password is required for new employee!";
                } else {
                    $sql = "INSERT INTO employees (name, username, password, email, phone, department, position, joining_date, status)
                            VALUES ('$name', '$username', '$password', '$email', '$phone', '$department', '$position', " .
                            (!empty($joining_date) ? "'$joining_date'" : "NULL") . ", '$status')";

                    if ($db->query($sql)) {
                        $_SESSION['success'] = "Employee added successfully!";
                        redirect(BASE_URL . '/admin/employees.php');
                    } else {
                        $error = "Error adding employee: " . $db->getConnection()->error;
                    }
                }
            }
        }
    }
}

// Get employee data for editing
$employee_data = array(
    'id' => '',
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'department' => '',
    'position' => '',
    'joining_date' => '',
    'status' => 'active'
);

if (($action === 'edit' || $action === 'view') && $employee_id > 0) {
    $employee_result = $db->query("SELECT * FROM employees WHERE id = $employee_id");

    if ($employee_result->num_rows === 1) {
        $employee_data = $employee_result->fetch_assoc();
    } else {
        $error = "Employee not found!";
    }
}

// Get all employees for listing
$employees = $db->query("SELECT * FROM employees ORDER BY name ASC");

// Set page title
$page_title = 'Manage Employees';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Employees</h1>
    <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=add" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-user-plus fa-sm text-white-50 me-1"></i> Add New Employee
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Employee Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <?php echo ($action === 'add') ? 'Add New Employee' : 'Edit Employee'; ?>
        </h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="edit_id" value="<?php echo $employee_data['id']; ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($employee_data['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($employee_data['username']); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">
                        Password
                        <?php if ($action === 'add'): ?>
                            <span class="text-danger">*</span>
                        <?php else: ?>
                            <small class="text-muted">(Leave blank to keep current password)</small>
                        <?php endif; ?>
                    </label>
                    <input type="password" class="form-control" id="password" name="password" <?php if ($action === 'add') echo 'required'; ?>>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee_data['email']); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($employee_data['phone']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="joining_date" class="form-label">Joining Date</label>
                    <input type="date" class="form-control" id="joining_date" name="joining_date" value="<?php echo htmlspecialchars($employee_data['joining_date']); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($employee_data['department']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="position" class="form-label">Position</label>
                    <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($employee_data['position']); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php if ($employee_data['status'] === 'active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if ($employee_data['status'] === 'inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> <?php echo ($action === 'add') ? 'Add Employee' : 'Update Employee'; ?>
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/employees.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
<?php elseif ($action === 'view'): ?>
<!-- View Employee Details -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Employee Details</h6>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=edit&id=<?php echo $employee_data['id']; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/employees.php" class="btn btn-sm btn-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employee_data['name']); ?>&background=4e73df&color=fff&size=200" class="rounded-circle img-thumbnail mb-3">
                <h4><?php echo htmlspecialchars($employee_data['name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($employee_data['position']); ?> - <?php echo htmlspecialchars($employee_data['department']); ?></p>
                <p>
                    <span class="badge <?php echo ($employee_data['status'] === 'active') ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo ucfirst($employee_data['status']); ?>
                    </span>
                </p>
            </div>
            <div class="col-md-8">
                <table class="table">
                    <tr>
                        <th width="150">Username:</th>
                        <td><?php echo htmlspecialchars($employee_data['username']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($employee_data['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?php echo htmlspecialchars($employee_data['phone']); ?></td>
                    </tr>
                    <tr>
                        <th>Department:</th>
                        <td><?php echo htmlspecialchars($employee_data['department']); ?></td>
                    </tr>
                    <tr>
                        <th>Position:</th>
                        <td><?php echo htmlspecialchars($employee_data['position']); ?></td>
                    </tr>
                    <tr>
                        <th>Joining Date:</th>
                        <td>
                            <?php
                            echo !empty($employee_data['joining_date'])
                                ? formatDate($employee_data['joining_date'], 'd F, Y')
                                : 'Not specified';
                            ?>
                        </td>
                    </tr>
                </table>

                <?php
                // Get employee statistics
                $stats = $db->query("SELECT * FROM employee_stats WHERE employee_id = {$employee_data['id']}")->fetch_assoc();

                // Get work assignment count
                $total_work = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = {$employee_data['id']}")->fetch_assoc()['count'];
                $completed_work = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = {$employee_data['id']} AND status = 'completed'")->fetch_assoc()['count'];
                $pending_work = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = {$employee_data['id']} AND status = 'pending'")->fetch_assoc()['count'];
                $in_progress = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = {$employee_data['id']} AND status = 'in_progress'")->fetch_assoc()['count'];

                // Calculate completion rate
                $completion_rate = ($total_work > 0) ? round(($completed_work / $total_work) * 100) : 0;
                ?>

                <h5 class="mt-4">Work Statistics</h5>
                <div class="row text-center mb-2">
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <h3 class="mb-0"><?php echo $total_work; ?></h3>
                            <small class="text-muted">Total Tasks</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <h3 class="mb-0"><?php echo $completed_work; ?></h3>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <h3 class="mb-0"><?php echo $pending_work; ?></h3>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded p-2">
                            <h3 class="mb-0"><?php echo round($stats['total_hours_spent'], 1); ?></h3>
                            <small class="text-muted">Hours Spent</small>
                        </div>
                    </div>
                </div>

                <h6 class="small font-weight-bold mt-3">Completion Rate <span class="float-end"><?php echo $completion_rate; ?>%</span></h6>
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%" aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>/admin/view_work.php?employee_id=<?php echo $employee_data['id']; ?>" class="btn btn-info">
                        <i class="fas fa-tasks me-1"></i> View Assigned Work
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/assign_work.php?employee_id=<?php echo $employee_data['id']; ?>" class="btn btn-success ms-2">
                        <i class="fas fa-clipboard-list me-1"></i> Assign New Work
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Employees List -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Employees</h6>
    </div>
    <div class="card-body">
        <?php if ($employees->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($employee = $employees->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $employee['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employee['name']); ?>&background=4e73df&color=fff&size=32" class="rounded-circle me-2">
                                <?php echo htmlspecialchars($employee['name']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($employee['username']); ?></td>
                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td>
                            <?php
                            echo !empty($employee['joining_date'])
                                ? formatDate($employee['joining_date'], 'd M Y')
                                : '-';
                            ?>
                        </td>
                        <td>
                            <span class="badge <?php echo ($employee['status'] === 'active') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ucfirst($employee['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=view&id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=edit&id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=toggle_status&id=<?php echo $employee['id']; ?>" class="btn btn-sm <?php echo ($employee['status'] === 'active') ? 'btn-warning' : 'btn-success'; ?>"
                               onclick="return confirm('Are you sure you want to <?php echo ($employee['status'] === 'active') ? 'deactivate' : 'activate'; ?> this employee?')">
                                <i class="fas <?php echo ($employee['status'] === 'active') ? 'fa-ban' : 'fa-check'; ?>"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=delete&id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this employee? This will also delete all associated work assignments and updates.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <p class="mb-0">No employees found.</p>
            <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=add" class="btn btn-primary mt-3">Add New Employee</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include_once '../templates/footer.php'; ?>
