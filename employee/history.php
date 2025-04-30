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

// Handle filters
$filter_status = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$filter_date = isset($_GET['date']) ? cleanInput($_GET['date']) : '';
$filter_priority = isset($_GET['priority']) ? cleanInput($_GET['priority']) : '';

// Build query with filters
$query = "SELECT a.*, e.name as employee_name
          FROM work_assignments a
          JOIN employees e ON a.employee_id = e.id
          WHERE a.employee_id = $employee_id";

$params = [];

if (!empty($filter_status)) {
    $query .= " AND a.status = '$filter_status'";
    $params[] = "status=$filter_status";
}

if (!empty($filter_date)) {
    $query .= " AND a.assigned_date = '$filter_date'";
    $params[] = "date=$filter_date";
}

if (!empty($filter_priority)) {
    $query .= " AND a.priority = '$filter_priority'";
    $params[] = "priority=$filter_priority";
}

// Add order by
$query .= " ORDER BY a.created_at DESC";

// Execute query
$assignments = $db->query($query);

// Get statistics
$total_assignments = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = $employee_id")->fetch_assoc()['count'];
$completed_assignments = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = $employee_id AND status = 'completed'")->fetch_assoc()['count'];
$pending_assignments = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = $employee_id AND status = 'pending'")->fetch_assoc()['count'];
$in_progress_assignments = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = $employee_id AND status = 'in_progress'")->fetch_assoc()['count'];
$cancelled_assignments = $db->query("SELECT COUNT(*) as count FROM work_assignments WHERE employee_id = $employee_id AND status = 'cancelled'")->fetch_assoc()['count'];

// Get total hours spent
$total_hours = $db->query("SELECT SUM(hours_spent) as total FROM work_updates WHERE employee_id = $employee_id")->fetch_assoc()['total'];
$total_hours = $total_hours ? $total_hours : 0;

// Set page title
$page_title = 'Work History';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Work History</h1>
    <a href="<?php echo BASE_URL; ?>/employee/dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Dashboard
    </a>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Assignments</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_assignments; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_assignments; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending / In-Progress</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_assignments + $in_progress_assignments; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Hours Spent</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo round($total_hours, 1); ?> hrs</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Filter Work History</h6>
        <?php if (!empty($params)): ?>
            <a href="<?php echo BASE_URL; ?>/employee/history.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-times me-1"></i> Clear Filters
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php if ($filter_status === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="in_progress" <?php if ($filter_status === 'in_progress') echo 'selected'; ?>>In Progress</option>
                    <option value="completed" <?php if ($filter_status === 'completed') echo 'selected'; ?>>Completed</option>
                    <option value="cancelled" <?php if ($filter_status === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">All Priorities</option>
                    <option value="low" <?php if ($filter_priority === 'low') echo 'selected'; ?>>Low</option>
                    <option value="medium" <?php if ($filter_priority === 'medium') echo 'selected'; ?>>Medium</option>
                    <option value="high" <?php if ($filter_priority === 'high') echo 'selected'; ?>>High</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Assigned Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Work History Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Work Assignments</h6>
    </div>
    <div class="card-body">
        <?php if ($assignments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Assigned Date</th>
                            <th>Deadline</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $assignment['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                    <?php if (!empty($assignment['description'])): ?>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($assignment['description'], 0, 50)) . (strlen($assignment['description']) > 50 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($assignment['assigned_date'], 'd M Y'); ?></td>
                                <td>
                                    <?php
                                    if (!empty($assignment['deadline'])) {
                                        echo formatDate($assignment['deadline'], 'd M Y h:i A');
                                    } else {
                                        echo '<span class="text-muted">No deadline</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo getPriorityBadge($assignment['priority']); ?></td>
                                <td><?php echo getWorkStatusBadge($assignment['status']); ?></td>
                                <td><?php echo formatDate($assignment['updated_at'], 'd M Y h:i A'); ?></td>
                                <td>
                                    <!-- View Details Button (using Bootstrap modal) -->
                                    <button type="button" class="btn btn-sm btn-info view-details" data-bs-toggle="modal" data-bs-target="#detailsModal"
                                            data-id="<?php echo $assignment['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($assignment['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($assignment['description']); ?>"
                                            data-assigned-date="<?php echo formatDate($assignment['assigned_date'], 'd M Y'); ?>"
                                            data-deadline="<?php echo !empty($assignment['deadline']) ? formatDate($assignment['deadline'], 'd M Y h:i A') : ''; ?>"
                                            data-priority="<?php echo ucfirst($assignment['priority']); ?>"
                                            data-status="<?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>"
                                            data-created-at="<?php echo formatDate($assignment['created_at'], 'd M Y h:i A'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <?php if ($assignment['status'] !== 'completed' && $assignment['status'] !== 'cancelled'): ?>
                                    <!-- Update Button -->
                                    <a href="<?php echo BASE_URL; ?>/employee/update_work.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
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
                <a href="<?php echo BASE_URL; ?>/employee/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Work Assignment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 id="modal-title"></h4>
                        <p class="text-muted mb-4" id="modal-created-at"></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="modal-priority" class="badge bg-primary me-1"></span>
                        <span id="modal-status" class="badge bg-success"></span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Description</h6>
                            </div>
                            <div class="card-body">
                                <p id="modal-description" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Assigned Date:</strong>
                            <p id="modal-assigned-date" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Deadline:</strong>
                            <p id="modal-deadline" class="mb-0"></p>
                        </div>
                    </div>
                </div>

                <div class="row mt-4" id="updates-container">
                    <div class="col-md-12">
                        <h5>My Updates</h5>
                        <div id="updates-loading" class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mb-0 mt-2">Loading updates...</p>
                        </div>
                        <div id="updates-list"></div>
                        <div id="no-updates" class="text-center py-3" style="display: none;">
                            <p class="text-muted mb-0">No updates found for this work assignment.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle details modal
    const detailsModal = document.getElementById('detailsModal');
    const viewButtons = document.querySelectorAll('.view-details');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const assignedDate = this.getAttribute('data-assigned-date');
            const deadline = this.getAttribute('data-deadline');
            const priority = this.getAttribute('data-priority');
            const status = this.getAttribute('data-status');
            const createdAt = this.getAttribute('data-created-at');

            // Set modal values
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-created-at').textContent = 'Created: ' + createdAt;
            document.getElementById('modal-description').textContent = description || 'No description provided.';
            document.getElementById('modal-assigned-date').textContent = assignedDate;
            document.getElementById('modal-deadline').textContent = deadline || 'No deadline set';

            // Set priority badge
            const priorityBadge = document.getElementById('modal-priority');
            priorityBadge.textContent = priority;
            priorityBadge.className = 'badge me-1';
            if (priority === 'Low') {
                priorityBadge.classList.add('bg-secondary');
            } else if (priority === 'Medium') {
                priorityBadge.classList.add('bg-primary');
            } else if (priority === 'High') {
                priorityBadge.classList.add('bg-danger');
            }

            // Set status badge
            const statusBadge = document.getElementById('modal-status');
            statusBadge.textContent = status;
            statusBadge.className = 'badge';
            if (status === 'Pending') {
                statusBadge.classList.add('bg-warning');
            } else if (status === 'In progress') {
                statusBadge.classList.add('bg-info');
            } else if (status === 'Completed') {
                statusBadge.classList.add('bg-success');
            } else if (status === 'Cancelled') {
                statusBadge.classList.add('bg-danger');
            }

            // Load updates
            loadUpdates(id);
        });
    });

    // Function to load updates for a work assignment
    function loadUpdates(assignmentId) {
        const updatesLoading = document.getElementById('updates-loading');
        const updatesList = document.getElementById('updates-list');
        const noUpdates = document.getElementById('no-updates');

        // Show loading, hide content
        updatesLoading.style.display = 'block';
        updatesList.innerHTML = '';
        noUpdates.style.display = 'none';

        // Simulate AJAX call (in a real app, you'd fetch from server)
        setTimeout(function() {
            // For demo purposes, we'll just show "no updates" for now
            updatesLoading.style.display = 'none';
            noUpdates.style.display = 'block';

            // In a real implementation, you would fetch updates from the server
            // and populate the updatesList
        }, 1000);
    }
});
</script>

<?php include_once '../templates/footer.php'; ?>
