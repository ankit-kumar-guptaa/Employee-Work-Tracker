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

// Get dashboard stats
$total_employees = $db->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc()['count'];
$total_assignments = $db->query("SELECT COUNT(*) as count FROM work_assignments")->fetch_assoc()['count'];
$completed_tasks = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE status = 'completed'")->fetch_assoc()['count'];
$pending_tasks = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE status = 'pending'")->fetch_assoc()['count'];
$in_progress_tasks = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE status = 'in_progress'")->fetch_assoc()['count'];

// Get recent assignments
$recent_assignments = $db->query("
    SELECT a.*, e.name as employee_name
    FROM work_assignments a
    JOIN employees e ON a.employee_id = e.id
    ORDER BY a.created_at DESC
    LIMIT 5
");

// Get employees with most pending tasks
$employees_pending = $db->query("
    SELECT e.id, e.name, COUNT(a.id) as pending_count
    FROM employees e
    JOIN work_assignments a ON e.id = a.employee_id
    WHERE a.status = 'pending'
    GROUP BY e.id
    ORDER BY pending_count DESC
    LIMIT 5
");

// Get employees with most completed tasks this month
$current_month = date('Y-m');
$employees_completed = $db->query("
    SELECT e.id, e.name, COUNT(a.id) as completed_count
    FROM employees e
    JOIN work_assignments a ON e.id = a.employee_id
    WHERE a.status = 'completed' AND a.updated_at LIKE '$current_month%'
    GROUP BY e.id
    ORDER BY completed_count DESC
    LIMIT 5
");

// Get task completion rate
$completion_rate = 0;
if ($total_assignments > 0) {
    $completion_rate = round(($completed_tasks / $total_assignments) * 100);
}

// Set page title
$page_title = 'Admin Dashboard';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50 me-1"></i> Generate Report
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-primary shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Total Employees</div>
                    <div class="stat-card-value"><?php echo $total_employees; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-users text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-success shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Completed Tasks</div>
                    <div class="stat-card-value"><?php echo $completed_tasks; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-warning shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Pending Tasks</div>
                    <div class="stat-card-value"><?php echo $pending_tasks; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-clock text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-info shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">In Progress</div>
                    <div class="stat-card-value"><?php echo $in_progress_tasks; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-spinner text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Completion Progress -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Task Completion Rate</h6>
            </div>
            <div class="card-body">
                <h4 class="small font-weight-bold">Overall Completion Rate <span class="float-end"><?php echo $completion_rate; ?>%</span></h4>
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%" aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="mt-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <h5><?php echo $total_assignments; ?></h5>
                            <p class="text-muted small mb-0">Total Tasks</p>
                        </div>
                        <div class="col-4">
                            <h5><?php echo $completed_tasks; ?></h5>
                            <p class="text-muted small mb-0">Completed</p>
                        </div>
                        <div class="col-4">
                            <h5><?php echo $pending_tasks + $in_progress_tasks; ?></h5>
                            <p class="text-muted small mb-0">Pending/In Progress</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="<?php echo BASE_URL; ?>/admin/employees.php?action=add" class="btn btn-primary btn-block d-flex align-items-center justify-content-center py-3">
                            <i class="fas fa-user-plus fa-2x me-2"></i>
                            <div class="text-start">
                                <div>Add New</div>
                                <div class="small">Employee</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="<?php echo BASE_URL; ?>/admin/assign_work.php" class="btn btn-success btn-block d-flex align-items-center justify-content-center py-3">
                            <i class="fas fa-clipboard-list fa-2x me-2"></i>
                            <div class="text-start">
                                <div>Assign</div>
                                <div class="small">New Work</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="<?php echo BASE_URL; ?>/admin/view_work.php" class="btn btn-info btn-block d-flex align-items-center justify-content-center py-3">
                            <i class="fas fa-tasks fa-2x me-2"></i>
                            <div class="text-start">
                                <div>View All</div>
                                <div class="small">Work Tasks</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-secondary btn-block d-flex align-items-center justify-content-center py-3">
                            <i class="fas fa-chart-bar fa-2x me-2"></i>
                            <div class="text-start">
                                <div>Generate</div>
                                <div class="small">Reports</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Assignments & Top Employees -->
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Work Assignments</h6>
                <a href="<?php echo BASE_URL; ?>/admin/view_work.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent_assignments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <thead class="table-light">
                            <tr>
                                <th>Task</th>
                                <th>Employee</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($assignment = $recent_assignments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($assignment['employee_name']); ?></td>
                                <td><?php echo getPriorityBadge($assignment['priority']); ?></td>
                                <td><?php echo getWorkStatusBadge($assignment['status']); ?></td>
                                <td>
                                    <?php
                                    if (!empty($assignment['deadline'])) {
                                        echo formatDate($assignment['deadline'], 'd M Y');
                                    } else {
                                        echo '<span class="text-muted">No deadline</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No work assignments found.</p>
                    <a href="<?php echo BASE_URL; ?>/admin/assign_work.php" class="btn btn-primary mt-3">Assign New Work</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Employees This Month</h6>
            </div>
            <div class="card-body">
                <?php if ($employees_completed->num_rows > 0): ?>
                <h6 class="text-muted mb-3">Most Completed Tasks</h6>
                <ul class="list-group list-group-flush mb-4">
                    <?php while ($employee = $employees_completed->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employee['name']); ?>&background=4e73df&color=fff" class="rounded-circle me-2" width="30">
                            <?php echo htmlspecialchars($employee['name']); ?>
                        </div>
                        <span class="badge bg-success rounded-pill"><?php echo $employee['completed_count']; ?> Tasks</span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No completed tasks this month.</p>
                </div>
                <?php endif; ?>

                <?php if ($employees_pending->num_rows > 0): ?>
                <h6 class="text-muted mb-3">Most Pending Tasks</h6>
                <ul class="list-group list-group-flush">
                    <?php while ($employee = $employees_pending->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employee['name']); ?>&background=4e73df&color=fff" class="rounded-circle me-2" width="30">
                            <?php echo htmlspecialchars($employee['name']); ?>
                        </div>
                        <span class="badge bg-warning rounded-pill"><?php echo $employee['pending_count']; ?> Tasks</span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No pending tasks found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
