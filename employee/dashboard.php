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

// Get employee stats
$stats = $db->query("SELECT * FROM employee_stats WHERE employee_id = $employee_id")->fetch_assoc();

// Get today's work assignments
$today = date('Y-m-d');
$today_assignments = $db->query("
    SELECT * FROM work_assignments
    WHERE employee_id = $employee_id
    AND assigned_date = '$today'
    ORDER BY priority DESC, created_at DESC
");

// Get pending and in-progress tasks
$pending_assignments = $db->query("
    SELECT * FROM work_assignments
    WHERE employee_id = $employee_id
    AND status IN ('pending', 'in_progress')
    ORDER BY priority DESC, created_at DESC
    LIMIT 5
");

// Get recent work updates
$recent_updates = $db->query("
    SELECT wu.*, wa.title as task_title
    FROM work_updates wu
    JOIN work_assignments wa ON wu.assignment_id = wa.id
    WHERE wu.employee_id = $employee_id
    ORDER BY wu.created_at DESC
    LIMIT 5
");

// Get completion rate
$completion_rate = 0;
if (($stats['completed_tasks'] + $stats['pending_tasks'] + $stats['in_progress_tasks'] + $stats['cancelled_tasks']) > 0) {
    $completion_rate = round(($stats['completed_tasks'] / ($stats['completed_tasks'] + $stats['pending_tasks'] + $stats['in_progress_tasks'] + $stats['cancelled_tasks'])) * 100);
}

// Set page title
$page_title = 'Employee Dashboard';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name']); ?></h1>
    <div>
        <span class="d-none d-sm-inline text-secondary me-2"><?php echo formatDate(date('Y-m-d'), 'l, d F Y'); ?></span>
        <a href="<?php echo BASE_URL; ?>/employee/update_work.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
            <i class="fas fa-clipboard-check fa-sm text-white-50 me-1"></i> Update Work
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-primary shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Total Tasks</div>
                    <div class="stat-card-value"><?php echo $stats['total_tasks']; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-tasks text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-success shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Completed</div>
                    <div class="stat-card-value"><?php echo $stats['completed_tasks']; ?></div>
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
                    <div class="stat-card-title">Pending</div>
                    <div class="stat-card-value"><?php echo $stats['pending_tasks']; ?></div>
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
                    <div class="stat-card-title">Hours Spent</div>
                    <div class="stat-card-value"><?php echo round($stats['total_hours_spent'], 1); ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-hourglass-half text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Assignments -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary bg-gradient text-white">
                <h6 class="m-0 font-weight-bold">Today's Work Assignments</h6>
                <span><?php echo formatDate(date('Y-m-d'), 'd M Y'); ?></span>
            </div>
            <div class="card-body">
                <?php if ($today_assignments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($assignment = $today_assignments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                    <?php if (!empty($assignment['description'])): ?>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($assignment['description'], 0, 50)) . (strlen($assignment['description']) > 50 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </td>
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
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/employee/update_work.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i> Update
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No work assignments for today.</p>
                    <p class="text-muted">Check your pending tasks below.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pending Tasks & Work Progress -->
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Pending & In-Progress Tasks</h6>
                <a href="<?php echo BASE_URL; ?>/employee/history.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if ($pending_assignments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <thead class="table-light">
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($assignment = $pending_assignments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                </td>
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
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/employee/update_work.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i> Update
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="mb-0">No pending tasks. Great job!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Work Progress</h6>
            </div>
            <div class="card-body">
                <h4 class="small font-weight-bold">Completion Rate <span class="float-end"><?php echo $completion_rate; ?>%</span></h4>
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%" aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="row text-center mb-4">
                    <div class="col-4">
                        <div class="p-3 bg-light rounded">
                            <h5 class="mb-0"><?php echo $stats['completed_tasks']; ?></h5>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded">
                            <h5 class="mb-0"><?php echo $stats['pending_tasks']; ?></h5>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded">
                            <h5 class="mb-0"><?php echo $stats['in_progress_tasks']; ?></h5>
                            <small class="text-muted">In Progress</small>
                        </div>
                    </div>
                </div>

                <h4 class="small font-weight-bold">Recent Updates</h4>
                <?php if ($recent_updates->num_rows > 0): ?>
                <div class="timeline">
                    <?php while ($update = $recent_updates->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-text"><?php echo formatDate($update['created_at'], 'd M'); ?></div>
                            <div class="timeline-item-marker-indicator bg-primary"></div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="card mb-2">
                                <div class="card-body py-2 px-3">
                                    <div class="small text-muted"><?php echo formatDate($update['created_at'], 'h:i A'); ?></div>
                                    <div><strong><?php echo htmlspecialchars($update['task_title']); ?></strong></div>
                                    <div><?php echo getWorkStatusBadge($update['work_status']); ?></div>
                                    <?php if (!empty($update['update_description'])): ?>
                                    <div class="small mt-1"><?php echo htmlspecialchars($update['update_description']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($update['hours_spent'] > 0): ?>
                                    <div class="small text-muted mt-1">Time spent: <?php echo formatWorkTime($update['hours_spent']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No recent updates.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline style */
.timeline {
    position: relative;
    padding-left: 1rem;
    margin: 1rem 0;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 0.25rem;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: #e3e6ec;
}
.timeline-item {
    position: relative;
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
.timeline-item-marker {
    position: absolute;
    left: -1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.timeline-item-marker-text {
    font-size: 0.75rem;
    color: #a2acba;
}
.timeline-item-marker-indicator {
    height: 0.75rem;
    width: 0.75rem;
    border-radius: 100%;
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
}
</style>

<?php include_once '../templates/footer.php'; ?>
